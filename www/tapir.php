<?php
/**
 * $Id: tapir.php 1997 2009-09-07 22:45:02Z rdg $
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
 * @author Dave Vieglais (Biodiversity Research Center, University of Kansas)
 * 
 * ACKNOWLEDGEMENTS
 * 
 * TapirLink has been generously funded by the Biodiversity 
 * Information Standards, TDWG, with resources from the Gordon and 
 * Betty Moore Foundation. The Global Biodiversity Information 
 * Facility, GBIF, has also been a major supporter of the TAPIR 
 * initiative since its very beginning and also collaborated to 
 * test this software.
 *
 * TapirLink was based on the DiGIR PHP provider, which was 
 * originally developed by Dave Vieglais from the Biodiversity 
 * Research Center, University of Kansas. The software is now being 
 * distributed under the GPL with permission from its original
 * author. The original copyright information is also 
 * reproduced below.
 * 
 * --------------------------------
 * 
 * Copyright (c) 2002 The University of Kansas Natural History 
 * Museum and Biodiversity Research Center. All rights reserved.
 * 
 * Permission is hereby granted, free of charge, to any person 
 * obtaining a copy of this software and associated documentation 
 * files (the "Software"), to deal with the Software without 
 * restriction, including without limitation the rights to use, 
 * copy, modify, merge, publish, distribute, sublicense, and/or 
 * sell copies of the Software, and to permit persons to whom the 
 * Software is furnished to do so, subject to the following 
 * conditions:
 * 
 * - Redistributions of source code must retain the above copyright 
 * notice, this list of conditions and the following disclaimers.
 * 
 * - Redistributions in binary form must reproduce the above 
 * copyright notice, this list of conditions and the following 
 * disclaimers in the documentation and/or other materials provided 
 * with the distribution.
 * 
 * - Neither the names of The University of Kansas Natural History 
 * Museum and Biodiversity Research Center, The University of Kansas 
 * at Lawrence, nor the names of its contributors may be used to 
 * endorse or promote products derived from this Software without 
 * specific prior written permission.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, 
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES 
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 * NONINFRINGEMENT. IN NO EVENT SHALL THE CONTRIBUTORS OR COPYRIGHT 
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, 
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER 
 * DEALINGS IN THE SOFTWARE.
 */

// Definition of this constant can be used to indicate that
// the Web Service (tapir.php) was called
define( 'TP_RUNNING_TAPIR', 1 );

require_once('tapir_globals.php');
require_once('TpUtils.php');
require_once('TpServiceUtils.php');
require_once('TpDiagnostics.php');
require_once('TpResources.php');
require_once('TpRequest.php');
require_once('TpResponse.php');

// Register a new error handler
$old_error_handler = set_error_handler( 'TapirErrorHandler' );

// Avoid HTML in errors
ini_set( 'html_errors', false );

// Store timestamp for profiling
define( 'INITIAL_TIMESTAMP', TpUtils::MicrotimeFloat() );

// Logs
TpUtils::InitializeLogs();

// Main logger
global $g_log;

// Debug logger
global $g_dlog;

$g_dlog->debug( '[Starting debug log]' );
$g_dlog->debug( 'TapirLink version: '.TP_VERSION.' (revision '.TP_REVISION.')' );

$request_uri = ( isset( $_SERVER['REQUEST_URI'] ) ) ? $_SERVER['REQUEST_URI'] : 'null';

$g_dlog->debug( 'Request URI: '.$request_uri );
$g_dlog->debug( 'Include path: '.ini_get( 'include_path' ) );

// Set the maximum script run time
// Note: if php set safe mode is on then this can't be set from here, 
// and will need to be set in php.ini file
set_time_limit( TP_MAX_RUNTIME );

// Debugging
if ( ! defined( '_DEBUG' ) )
{
    // Load the value from a request variable called "debug", default is false
    // for security reasons
    if ( TP_ALLOW_DEBUG )
    {
        define( '_DEBUG', (bool)TpUtils::GetVar( 'debug', false ) );
    }
    else
    {
        define( '_DEBUG', false );
    }
}

// Instantiate request object
$request = new TpRequest();

// If no resource was specified in the request URI, then dump some help information
if ( ! $request->ExtractResourceCode() )
{
   include_once( 'index.php' );
   die();
}

// Get resource code and check if it's valid
$resource_code = $request->GetResourceCode();

$r_resources =& TpResources::GetInstance();

$raise_errors = false;
$r_resource =& $r_resources->GetResource( $resource_code, $raise_errors );

if ( $r_resource == null )
{
    $response = new TpResponse( $request );
    $response->ReturnError( 'Resource "'.$resource_code.'" not found.' );
    die();
}

$r_resources->SetCurrentResourceCode( $resource_code );

if ( $r_resource->GetStatus() != 'active' )
{
    $response = new TpResponse( $request );
    $response->ReturnError( 'Resource "'.$resource_code.'" is not active.' );
    die();
}

// Check PHP version
$current_version = phpversion();

if ( version_compare( $current_version, '5.0', '<' ) > 0 )
{
    if ( version_compare( $current_version, TP_MIN_PHP_VERSION, '<' ) > 0 )
    {
        $msg = 'PHP Version '.TP_MIN_PHP_VERSION.' or later required. '.
               'Some features may not be available. Detected version '.$current_version;
        TpDiagnostics::Append( DC_VERSION_MISMATCH, $msg, DIAG_WARN );
    }
}
else if ( version_compare( $current_version, '6.0', '<' ) > 0 )
{
    if ( version_compare( $current_version, '5.0.3', '<' ) > 0 )
    {
        // Avoid bug in "xml_set_start_namespace_decl_handler"
        $msg = 'Provider error: Unsupported PHP version ('.$current_version.'). To '.
               'use PHP5 it is necessary to have at least version 5.0.3';

        $response = new TpResponse( $request );
        $response->ReturnError( $msg );
        die();
    }
}

// Get parameters
if ( ! $request->InitializeParameters() or 
     TpDiagnostics::Count( array( DIAG_ERROR, DIAG_FATAL ) ) )
{
    $response = new TpResponse( $request );
    $response->ReturnError( 'Failed to process request' );
    die();
}

// By default, assume that the database encoding cannot be detected 
// by the mb_detect_encoding PHP function
global $g_encoding_can_be_detected;

$operation = $request->GetOperation();

if ( $operation == 'ping' )
{
    require_once('TpPingResponse.php');

    $response = new TpPingResponse( $request );
}
else if ( $operation == 'capabilities' )
{
    require_once('TpCapabilitiesResponse.php');

    $response = new TpCapabilitiesResponse( $request );
}
else if ( $operation == 'metadata' )
{
    require_once('TpMetadataResponse.php');

    $response = new TpMetadataResponse( $request );
}
else if ( $operation == 'inventory' )
{
    require_once('TpInventoryResponse.php');

    $response = new TpInventoryResponse( $request );
}
else if ( $operation == 'search' )
{
    require_once('TpSearchResponse.php');

    $response = new TpSearchResponse( $request );
}
else
{
    // Unknown operation 
    $response = new TpResponse( $request );
    $response->ReturnError( 'Unknown operation "'.$operation.'"' );
    die();
}

$response->Process();

exit();

?>
