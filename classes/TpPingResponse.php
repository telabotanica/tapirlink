<?php
/**
 * $Id: TpPingResponse.php 1958 2009-01-06 12:58:21Z rdg $
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

require_once('TpResponse.php');
require_once('TpDiagnostics.php');

class TpPingResponse extends TpResponse
{
    function TpPingResponse( $request )
    {
        $this->TpResponse( $request );

        $this->mCacheable = false;

    } // end of member function TpPingResponse

    function Body()
    {
        $r_resource =& $this->mRequest->GetResource();

        $r_resource->LoadConfig();
        
        if ( ! $r_resource->IsValid() )
        {
            echo "\n<error>Service configuration error</error>";
        
            return;
        }

        $r_data_source =& $r_resource->GetDatasource();

        if ( ! $r_data_source->Validate() )
        {
            echo "\n<error>Database connection error</error>";
        
            return;
        }

        if ( TpDiagnostics::Count( array( DIAG_ERROR, DIAG_FATAL ) ) )
        {
            echo "\n<error>Service runtime error</error>";
            return;
        }

        echo "\n<pong/>";

    } // end of member function Body

} // end of TpPingResponse
?>