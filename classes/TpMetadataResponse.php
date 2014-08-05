<?php
/**
 * $Id: TpMetadataResponse.php 268 2007-02-23 00:58:22Z rdg $
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

class TpMetadataResponse extends TpResponse
{
    function TpMetadataResponse( $request )
    {
        if ( defined( 'TP_SKIN' ) )
        {
            $this->mDefaultXslt = 'skins/'.TP_SKIN.'/metadata.xsl';
        }

        $this->TpResponse( $request );

        $this->mCacheLife = TP_METADATA_CACHE_LIFE_SECS;

        $dc_ns = new TpXmlNamespace( 'http://purl.org/dc/elements/1.1/',
                                      TP_DC_PREFIX, '' );
        $this->AddXmlNamespace( $dc_ns );

        $dct_ns = new TpXmlNamespace( 'http://purl.org/dc/terms/',
                                      TP_DCT_PREFIX, '' );
        $this->AddXmlNamespace( $dct_ns );

        $vcard_ns = new TpXmlNamespace( 'http://www.w3.org/2001/vcard-rdf/3.0#',
                                        TP_VCARD_PREFIX, '' );
        $this->AddXmlNamespace( $vcard_ns );

        $geo_ns = new TpXmlNamespace( 'http://www.w3.org/2003/01/geo/wgs84_pos#',
                                      TP_GEO_PREFIX, '' );
        $this->AddXmlNamespace( $geo_ns );

    } // end of member function TpMetadataResponse

    function Body()
    {
        // Note: don't change this to $r_resource because this variable is used
        //       in the automatically generated metadata templates!
        $resource =& $this->mRequest->GetResource();

        $r_settings =& $resource->GetSettings();

        $config_file = $resource->GetConfigFile();

        $r_settings->LoadFromXml( $config_file, '' );

        $date_last_modified = $resource->GetDateLastModified();

        $file = $resource->GetMetadataFile();

        echo "\n";

        if ( ! include_once( $file ) )
        {
            $error = "Could not open resource metadata file.";
            $this->ReturnError( $error );
        }

    } // end of member function Body

} // end of TpMetadataResponse
?>