<?php
/**
 * $Id: TpOperationParameters.php 564 2008-02-29 23:21:14Z rdg $
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
 */

require_once('TpDiagnostics.php');
require_once('phpxsd/XsNamespaceManager.php');

class TpOperationParameters
{
    var $mRevision = '$Revision: 564 $';
    var $mInTags = array(); // name element stack during XML parsing
    var $mLabel;
    var $mDocumentation;
    var $mTemplate;
    var $mFilter;

    function TpOperationParameters( )
    {
        $this->mFilter = new TpFilter();

    } // end of member function TpOperationParameters

    function GetRevision( )
    {
        $revision_regexp = '/^\$'.'Revision:\s(\d+)\s\$$/';

        if ( preg_match( $revision_regexp, $this->mRevision, $matches ) )
        {
            return (int)$matches[1];
        }

        return null;

    } // end of member function GetRevision

    function LoadKvpParameters()
    {
        // Filter
        if ( isset( $_REQUEST['filter'] ) )
        {
            $filter = $_REQUEST['filter'];
        }
        else if ( isset( $_REQUEST['f'] ) )
        {
            $filter = $_REQUEST['f'];
        }

        if ( isset( $filter ) )
        {
            $this->mFilter->LoadFromKvp( urldecode( $filter ) );
        }

        return true;

    } // end of member function LoadKvpParameters

    function ParseTemplate( $location )
    {
        $this->mTemplate = $location;

        $parser = xml_parser_create_ns();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_start_namespace_decl_handler( $parser, 'DeclareNamespace' );
        xml_set_element_handler( $parser, 'StartElement', 'EndElement' );
        xml_set_character_data_handler( $parser, 'CharacterData' );

        $fp = TpUtils::GetFileHandle( $location );

        if ( ! is_resource( $fp ) )
        {
            // Replace PHP warning with a better message
            TpDiagnostics::PopDiagnostic();

            $error = "Could not open the template file: $location";
            TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_FATAL );

            return false;
        }
      
        while ( $data = fread( $fp, 4096 ) ) 
        {
            if ( ! xml_parse( $parser, $data, feof( $fp ) ) ) 
            {
                $error = sprintf( "Error parsing template: %s at line %d",
                                  xml_error_string( xml_get_error_code( $parser ) ),
                                  xml_get_current_line_number( $parser ) );

                TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_FATAL );
                return false;
            }
        }

        fclose( $fp );

        xml_parser_free( $parser );

        return true;

    } // end of member function ParseTemplate

    function DeclareNamespace( $parser, $prefix, $uri ) 
    {
        $path = implode( '/', $this->mInTags );

        $flag = null;

        if ( strcasecmp( $path, 'request/search' ) == 0 )
        {
            // Namespaces declared in output model or query template.
            // This flag indicates a potential namespace declared in
            // an output model element.
            $flag = 'm';
        }

        $r_namespace_manager =& XsNamespaceManager::GetInstance();

        $r_namespace_manager->AddNamespace( $parser, $prefix, $uri, $flag );

    } // end of member function DeclareNamespace

    function StartElement( $parser, $qualified_name, $attrs )
    {
        $name = TpUtils::GetUnqualifiedName( $qualified_name );

        array_push( $this->mInTags, strtolower( $name ) );

        if ( in_array( 'filter', $this->mInTags ) )
        {
            // Delegate to filter parser
            $this->mFilter->StartElement( $parser, $qualified_name, $attrs );
        }

    } // end of member function StartElement

    function EndElement( $parser, $qualified_name ) 
    {
        if ( in_array( 'filter', $this->mInTags ) )
        {
            // Delegate to filter parser
            $this->mFilter->EndElement( $parser, $qualified_name );
        }

        array_pop( $this->mInTags );

    } // end of member function EndElement

    function CharacterData( $parser, $data ) 
    {
        if ( in_array( 'filter', $this->mInTags ) )
        {
            // Delegate to filter parser
            $this->mFilter->CharacterData( $parser, $data );
        }

    } // end of member function CharacterData

    function GetTemplate( )
    {
        return $this->mTemplate;

    } // end of member function GetTemplate

    function GetLabel( )
    {
        return $this->mLabel;

    } // end of member function GetLabel

    function GetDocumentation( )
    {
        return $this->mDocumentation;

    } // end of member function GetDocumentation

    function GetFilter( )
    {
        return $this->mFilter;

    } // end of member function GetFilter

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array( 'mRevision', 'mTemplate', 'mFilter' );

    } // end of member function __sleep

} // end of TpOperationParameters
?>