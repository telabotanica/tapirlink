<?php
/**
 * $Id: index.php 456 2007-10-28 23:34:43Z rdg $
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
 * @author Kevin Richards <richardsk [at] landcareresearch . co . nz>
 */

require_once('../tapir_globals.php');

// Get path to the script
$script_parts = explode( '/', $_SERVER['SCRIPT_NAME'] );
array_pop( $script_parts );

$path = $_SERVER['SERVER_NAME'] . implode( '/', $script_parts );


// check if they are submitting an LSID
if ( isset( $_GET['lsid'] ) )
{
    require_once('TpLsidResolver.php');

    $lsid = $_GET['lsid'];

    // an lsid has been passed so they are calling
    // the getAvailableServices(lsid) method and we
    // should return the appropriate WSDL.

    $res = new TpLsidResolver( $lsid );

    $url = $res->GetTemplateUrl( $lsid );

    if ( $url == '' )
    {
        // Error handling

        $error = $res->GetError(); // TODO: can we show this somewhere?

        $status = '404 Not Found';

        if ( substr( php_sapi_name(), 0, 3 ) == 'cgi' )
        {
            header('Status: '.$status, TRUE);
        }
        else
        {
            header($_SERVER['SERVER_PROTOCOL'].' '.$status);
        }

        die( $error );
    }
    else 
    {
        header('Content-Type: text/xml');

        // Metadata will be a direct TAPIR call
        $LSIDMetadataAddress = $url;

        // data.php will return nothing
        $LSIDDataAddress = 'http://'. $path .'/data.php';

        include('LsidDataServices.wsdl.php');
    }
}
else
{
    header('Content-Type: text/xml');

    // no lsid has been passed so they are asking
    // how they should call the getAvailableServices(lsid) method.
    // we should return an WSDL with the right location in it.
    $LSIDAuthorityAddress = 'http://'. $path . '/index.php';
    include('LsidAuthority.wsdl.php');
}
?>