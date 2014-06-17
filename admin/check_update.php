<?php
/**
 * $Id: check_update.php 1978 2009-02-13 18:03:57Z rdg $
 * 
 * LICENSE INFORMATION
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details:
 * 
 * http://www.gnu.org/copyleft/gpl.html
 * 
 * 
 * @author Renato De Giovanni <renato [at] cria . org . br>
 * 
 */

// When TapirLink is installed with Subversion, this script can be used to check 
// if there's a new stable version available, and in this case update the local copy.

require_once('../www/tapir_globals.php');
require_once('../classes/TpUtils.php');

global $g_local_revision;
global $g_recommended_revision;

$g_local_revision = 0;
$g_recommended_revision = 0;

// Internal function to handle start elements of the svn info XML
function _SvnStartElement( $parser, $name, $attrs )
{
    // <entry> element
    if ( strcasecmp( $name, 'entry' ) == 0 and isset( $attrs['revision'] ) )
    {
        global $g_local_revision;
        
        $g_local_revision = (int)$attrs['revision'];
    }
}

// This function was only created because older PHP versions through warnings
// when callbacks are NULL
function _SvnEndElement( $parser, $name )
{
    // nothing here
}

// Internal function to handle start elements of the TapirLink remote XML
function _TpStartElement( $parser, $name, $attrs )
{
    // <version> element
    if ( strcasecmp( $name, 'version' ) == 0 and isset( $attrs['revision'] ) and
         isset( $attrs['latest'] ) and (bool)$attrs['latest'] )
    {
        global $g_recommended_revision;
        
        $g_recommended_revision = (int)$attrs['revision'];
    }
}

// This function was only created because older PHP versions through warnings
// when callbacks are NULL
function _TpEndElement( $parser, $name )
{
    // nothing here
}

///// Main code /////

// Check if TapirLink was installed from subversion
if ( ! is_dir( dirname(__FILE__).'/.svn' ) )
{
    die( 'Not installed with Subversion' );
}

// Check if shell execution is working
$test = shell_exec( 'echo TEST' );

if ( empty( $test ) )
{
    die( 'Shell execution does not seem to be working' );
}

// Get current revision number using Subversion command-line
$output_svn_info = shell_exec( 'svn info --xml ..' );

if ( empty( $output_svn_info ) )
{
    die( 'Could not use Subversion to determine the local installation revision number. Please make sure that Subversion (command-line program called svn) is installed and is in the path for the Web Server user.' );
}

// Parse XML to get revision
$parser = xml_parser_create();

if ( ! is_resource( $parser ) )
{
    die( 'Could not instantiate XML parser. Please check your PHP installation.' );
}

xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
xml_set_element_handler( $parser, '_SvnStartElement', '_SvnEndElement' );

if ( ! xml_parse( $parser, $output_svn_info ) )
{
    xml_parser_free( $parser );
    die( 'Could not parse Subversion response to get local revision number' );
}

xml_parser_free( $parser );

// Get latest recommended revision number
$fh = TpUtils::OpenFile( TP_CHECK_UPDATE_URL );

if ( ! is_resource( $fh ) )
{
    die( 'Could not retrieve recommended revision number' );
}

// Parse XML
$parser = xml_parser_create();
xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
xml_set_element_handler( $parser, '_TpStartElement', '_TpEndElement' );

while ( $data = fread( $fh, 4096 ) ) 
{
    if ( ! xml_parse( $parser, $data, feof( $fh ) ) ) 
    {
        xml_parser_free( $parser );
        die( 'Could not parse remote XML to get recommended revision number: '.$error );
    }
}

xml_parser_free( $parser );

fclose( $fh );

echo( 'Local revision number is: '.$g_local_revision );
echo( '<br/>Recommended revision number is: '.$g_recommended_revision );

// Compare revisions

if ( $g_recommended_revision > 0 and $g_local_revision > 0 and 
     $g_recommended_revision > $g_local_revision )
{
    // Update local copy
    $svn_config_dir = '../config/subversion';
    
    $cmd = 'svn update -r '.$g_recommended_revision.' --config-dir '.$svn_config_dir.' ..';
    
    $output_svn_update = shell_exec( $cmd );

    // Get current revision number again
    $output_svn_info = shell_exec( 'svn info --xml ..' );

    // Parse XML to get revision
    $parser = xml_parser_create();
    xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
    xml_set_element_handler( $parser, '_SvnStartElement', NULL );
    xml_parse( $parser, $output_svn_info );
    xml_parser_free( $parser );

    if ( $g_recommended_revision == $g_local_revision )
    {
        echo '<br/>Action: Updated local copy';
    }
    else
    {
        echo '<br/>Action: Failed to update local copy';
        // no sf certificate found
        // no write access
    }
}
else
{
    echo '<br/>Action: No need to update';
}

?>