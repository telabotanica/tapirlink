<?php 
/**
 * $Id: tapir_client.php 1984 2009-03-21 20:25:04Z rdg $
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

require_once('tapir_globals.php');
require_once('TpUtils.php');
require_once('TpHtmlUtils.php');
require_once('TpResources.php');
require_once('TpResource.php');
require_once('HTTP/Request.php'); // pear package

// Show form when user didn't click on submit button
if ( ! TpUtils::GetVar( 'send' ) ) 
{
    // Accesspoint
    $local_accesspoints = array();

    $rResourcesManager =& TpResources::GetInstance();

    $resources = $rResourcesManager->GetActiveResources();

    foreach ( $resources as $resource ) 
    {
        $accesspoint = $resource->GetAccessPoint();
        $local_accesspoints[$accesspoint] = $accesspoint;
    }

    // Operation
    $operations = array( 'ping'         => 'Ping',
                         'capabilities' => 'Capabilities',
                         'metadata'     => 'Metadata',
                         'inventory'    => 'Inventory',
                         'search'       => 'Search' );

    // Encodings
    $encodings = array( 'rawpost' => 'RAW POST',
                        'get'     => 'GET w/ request parameter',
                        'post'    => 'POST w/ request parameter' );

    // Include HTML template
    include_once('tapir_client.tmpl.php');

}
// Process request if user clicked on submit
else
{
    $url = $_REQUEST['local_accesspoint'];

    $body = str_replace( '\"', '"', $_REQUEST['request'] );

    $http_request = new HTTP_Request();

    if ( $_REQUEST['encoding'] == 'get' )
    {
        $http_request->setMethod( 'GET' );

        $body = urlencode( $body );

        $url .= ( strpos( $url, '?' ) === false ) ? '?' : '&';

        $url .= "request=$body";
    }
    else
    {
        $http_request->setMethod( 'POST' );

        if ( $_REQUEST['encoding'] == 'rawpost' )
        {
            $http_request->addHeader('Content-Type', 'text/xml');

            $http_request->addRawPostData( $body );
        }
        else
        {
            $http_request->addHeader('Content-Type', 'application/x-www-form-urlencoded');
            $http_request->addPostData( 'request', $body );
        }
    }

    $http_request->setURL( $url );

    $http_request->_timeout = 30;
    $http_request->_readTimeout = 30;

    $res = $http_request->sendRequest();

    $response = $http_request->getResponseBody();

    // This can be used to see the entire request
    //$raw_request = $http_request->_buildRequest();
    //echo $raw_request;
    //exit;

    // This can be used to inspect the HTTP header received
    //$header = $http_request->getResponseHeader();
    //var_dump($header);
    //exit;

    // Check the HTTP code returned
    $code = $http_request->getResponseCode();

    if ( $code != 200 ) // 200 = OK
    {
        $label = 'Unknown Error';

        switch ( $code )
        {
            case 201: $label = 'Created'; break;
            case 202: $label = 'Accepted'; break;
            case 203: $label = 'Non-Authoritative Information'; break;
            case 204: $label = 'No Content'; break;
            case 205: $label = 'Reset Content'; break;
            case 206: $label = 'Partial Content'; break;
            case 300: $label = 'Multiple Choices'; break;
            case 301: $label = 'Moved Permanently'; break;
            case 302: $label = 'Found'; break;
            case 303: $label = 'See Other'; break;
            case 304: $label = 'Not Modified'; break;
            case 305: $label = 'Use Proxy'; break;
            case 307: $label = 'Temporary Redirect'; break;
            case 400: $label = 'Bad Request'; break;
            case 401: $label = 'Unauthorized'; break;
            case 402: $label = 'Payment Required'; break;
            case 403: $label = 'Forbidden'; break;
            case 404: $label = 'Not Found'; break;
            case 405: $label = 'Method Not Allowed'; break;
            case 406: $label = 'Not Acceptable'; break;
            case 407: $label = 'Proxy Authentication Required'; break;
            case 408: $label = 'Request Timeout'; break;
            case 409: $label = 'Conflict'; break;
            case 410: $label = 'Gone'; break;
            case 411: $label = 'Length Required'; break;
            case 412: $label = 'Precondition Failed'; break;
            case 413: $label = 'Request Entity Too Large'; break;
            case 414: $label = 'Request-URI Too Long'; break;
            case 415: $label = 'Unsupported Media Type'; break;
            case 416: $label = 'Requested Range Not Satisfiable'; break;
            case 417: $label = 'Expectation Failed'; break;
            case 500: $label = 'Internal Server Error'; break;
            case 501: $label = 'Not Implemented'; break;
            case 502: $label = 'Bad Gateway'; break;
            case 503: $label = 'Service Unavailable'; break;
            case 504: $label = 'Gateway Timeout'; break;
            case 505: $label = 'HTTP Version Not Supported'; break;
	}

       echo '<h1>Service responded with HTTP '.$code.' code: <br/>'.$label.'</h1>';
       exit;
    }

    if ( ! headers_sent() ) 
    {
        header( 'Content-type: text/xml' );
        echo $response;
    }
}
?>