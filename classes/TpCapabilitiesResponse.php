<?php
/**
 * $Id: TpCapabilitiesResponse.php 6 2007-01-06 01:38:13Z rdg $
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
 */

require_once('TpResponse.php');

class TpCapabilitiesResponse extends TpResponse
{
    function TpCapabilitiesResponse( $request )
    {
        $this->TpResponse( $request );

        $this->mCacheLife = TP_CAPABILITIES_CACHE_LIFE_SECS;

    } // end of member function TpCapabilitiesResponse

    function Body()
    {
        $resource = $this->mRequest->GetResource();

        $file = $resource->GetCapabilitiesFile();

        if ( !( $fp = fopen( $file, 'r' ) ) ) 
        {
            $error = "Could not open resource capabilities file.";
            $this->ReturnError( $error );
        }
      
        while ( $data = fread( $fp, 4096 ) ) 
        {
            echo $data;
        }

        fclose( $fp );

    } // end of member function Body

} // end of TpCapabilitiesResponse
?>