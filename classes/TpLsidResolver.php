<?php
/**
 * $Id: TpLsidResolver.php 457 2007-10-28 23:38:36Z rdg $
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

require_once( 'TpResources.php' );
require_once( 'TpResource.php' );

class TpLsidResolver
{
    var $mLsid;
    var $mAuthorityCode;
    var $mNamespace;

    var $mInTags = array(); // property used during XML parsing
    var $mInNamespace;      // property used during XML parsing

    var $mSettings; // array ( LSID namespace => array ( 'res'  => TAPIR resource code,
                    //                                   'op'   => TAPIR operation,
                    //                                   'tmpl' => TAPIR template ) )

    var $mError = '';

    function TpLsidResolver( $lsid )
    {
        $this->mLsid = $lsid;

        $parts = explode( ':', $lsid );

        if ( count( $parts ) > 4 )
        {
            $this->mAuthorityCode = $parts[2];

            $this->mNamespace = $parts[3];

            $this->LoadSettings();
        }
        else
        {
            $this->mError = 'LSID has less then 5 parts';
        }
    }

    function LoadSettings( $configFile='' )
    {
        if ( empty( $configFile ) )
        {
            $configFile = TP_CONFIG_DIR.DIRECTORY_SEPARATOR.'lsid_settings.xml';
        }

        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_element_handler( $parser, 'StartElement', 'EndElement' );
        xml_set_character_data_handler( $parser, 'CharacterData' );

        if ( !( $fp = fopen( $configFile, 'r' ) ) ) 
        {
            $this->mError = 'Could not open file configuration file for LSID resolution';
            return false;
        }

        while ( $data = fread( $fp, 4096 ) ) 
        {
            if ( ! xml_parse( $parser, $data, feof($fp) ) ) 
            {
                // XML parsing error
                $this->mError = sprintf( "Error parsing the configuration file for LSID resolution: %s at line %d",
                                         xml_error_string( xml_get_error_code( $parser ) ),
                                         xml_get_current_line_number( $parser ) );

                xml_parser_free( $parser );
                fclose( $fp );
                return false;
            }
        }

        xml_parser_free( $parser );
        fclose( $fp );

        return true;
    }

    function StartElement( $parser, $name, $attrs ) 
    {
        array_push( $this->mInTags, $name );

        if ( strcasecmp( $name, 'LSIDNamespace' ) == 0 )
        {
            if ( isset( $attrs['Name'] ) )
            {
                $this->mInNamespace = $attrs['Name'];
                $this->mSettings[$this->mInNamespace] = array();
            }
        }

    } // end of member function StartElement

    function EndElement( $parser, $name ) 
    {
        array_pop( $this->mInTags );

    } // end of member function EndElement

    function CharacterData( $parser, $data ) 
    {
        $data = trim( $data );

        if ( (!empty( $this->mInNamespace )) and strlen( $data ) ) 
        {
            $depth = count( $this->mInTags );
            $inTag = $this->mInTags[$depth-1];

            if ( strcasecmp( $inTag, 'TAPIRResource' ) == 0 ) 
            {
                $this->mSettings[$this->mInNamespace]['res'] = $data;
            }
            else if ( strcasecmp( $inTag, 'TAPIROperation' ) == 0 )
            {
                $this->mSettings[$this->mInNamespace]['op'] = $data;
            }
            else if ( strcasecmp( $inTag, 'TAPIRTemplate' ) == 0 )
            {
                $this->mSettings[$this->mInNamespace]['tmpl'] = $data;
            }
        }

    } // end of member function CharacterData

    function GetTemplateUrl( )
    {
        if ( ! isset( $this->mNamespace ) )
        {
            $this->mError = 'LSID has no namespace defined';
            return '';
        }

        if ( ! isset( $this->mSettings[$this->mNamespace] ) )
        {
            $this->mError = 'Unknown LSID namespace: '.$this->mNamespace;
            return '';
        }

        $resources = new TpResources();
        $resources->Load();

        $res_code = $this->mSettings[$this->mNamespace]['res'];

        $resource = $resources->GetResource( $res_code );

        if ( is_null( $resource ) )
        {
            $this->mError = 'Unknown TAPIR resource: '.$res_code;

            return '';
        }

        $op = $this->mSettings[$this->mNamespace]['op'];

        $tmpl = $this->mSettings[$this->mNamespace]['tmpl'];

        $access_point = $resource->GetAccessPoint();

        $url = $access_point.'?op='.$op.'&amp;e=0&amp;t='.$tmpl;

        return $url;

    } // end of member function GetTemplateUrl

    function GetError( )
    {
        return $this->mError;

    } // end of member function GetError
}

?>
