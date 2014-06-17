<?php
/**
 * $Id: TpResourceForm.php 660 2008-04-30 19:05:19Z rdg $
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

require_once('TpPage.php');
require_once('TpResources.php');
require_once('TpUtils.php');
require_once('TpDiagnostics.php');
require_once('pear/HTTP/Request.php');

class TpResourceForm extends TpPage
{
    var $mrResource;
    var $mRemoved = false;

    function TpResourceForm( $rResource )
    {
        $this->mrResource =& $rResource;

    } // end of member function TpResourceForm

    function HandleEvents( ) 
    {
        $r_resources =& TpResources::GetInstance();

        if ( isset( $_REQUEST['remove'] ) )
        {
            if ( $r_resources->RemoveResource( $this->mrResource->GetCode() ) )
            {
                $this->mRemoved = true;
            }
        }

    } // end of member function HandleEvents

    function RemovedResource( ) 
    {
        return $this->mRemoved;

    } // end of member function RemovedResource

    function DisplayHtml( ) 
    {
        $errors = TpDiagnostics::GetMessages();

        // Expose $resource variable to template
        $resource = $this->mrResource;

        // Check access point
        $ping_url = $resource->GetAccesspoint();

        $access_msg = '';
        $access_ok = false;

        while ( true )
        {
            if ( empty( $ping_url ) )
            {
                break;
            }

            $ping_url .= ( strpos( $ping_url, '?' ) === false ) ? '?' : '&';

            $ping_url .= 'op=p';

            $http_request = new HTTP_Request();

            $http_request->setMethod( 'GET' );

            $http_request->setURL( $ping_url );

            $pear_call = $http_request->sendRequest();

            if ( PEAR::isError( $pear_call ) )
            {
                $access_msg = 'HTTP request error ('.$pear_call->getCode().'):'.$pear_call->getMessage();
                break;
            }

            $code = $http_request->getResponseCode();

            if ( $code != 200 )
            {
                $access_msg = 'Unexpected HTTP status code '.$code;
                break;
            }

            $header = $http_request->getResponseHeader();

            $content_type = $http_request->getResponseHeader( 'content-type' );

            if ( $content_type === false )
            {
                $access_msg = 'No content-type returned';
                break;
            }

            if ( strlen( $content_type ) < 8 or substr( $content_type, 0, 8 ) != 'text/xml' )
            {
                $access_msg = 'Unexpected content-type: '.$content_type;
                break;
            }

            $content = $http_request->getResponseBody();

            if ( empty( $content ) )
            {
                $access_msg = 'Empty HTTP response body';
                break;
            }

            if ( strpos( $content, '<pong/>' ) === false )
            {
                $access_msg = 'No pong response';
                break;
            }

            $access_msg = '&nbsp;&nbsp;(OK)';

            $access_ok = true;

            break;
        }

        if ( ! $access_ok )
        {
            $access_msg = '<br/>ERROR: '.$access_msg.
                          '<br/>Please check the access point field in your metadata!';
        }

        include('TpResourceForm.tmpl.php');

    } // end of member function DisplayHtml

} // end of TpResourceForm
?>
