<?php
/**
 * $Id: TpResources.php 594 2008-04-04 19:26:12Z rdg $
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

require_once('TpDiagnostics.php');
require_once('TpResource.php');
require_once('TpConfigUtils.php');

class TpResources
{
    var $mResources = array();  // TpResource objects

    // No constructor - this class uses the singleton pattern
    // Use GetInstance instead

    static function &GetInstance( )
    {
        static $instance;

        global $g_dlog;

        // Note: There is no need to use session when running the web service (tapir.php)

        if ( ! isset( $instance ) )
        {
            if ( ( ! defined( 'TP_RUNNING_TAPIR' ) ) and 
                 isset ( $_SESSION['resources'] ) and 
                 ! isset( $_REQUEST['force_reload'] ) ) 
            {
                if ( is_object( $g_dlog ) )
                {
                    $g_dlog->debug('Loading resources instance from session');
                }

                $instance =& $_SESSION['resources'];
            }
            else 
            {
                $instance = new TpResources();

                $instance->Load();
            }
        }
        else
        {
            if ( isset( $_REQUEST['force_reload'] ) and ! defined( 'TP_RUNNING_TAPIR' ) ) 
            {
                $instance = new TpResources();

                $instance->Load();
            }
        }

        return $instance;

    } // end of member function GetInstance

    /**
     * Set the current resource code. This function was created to be called 
     * when processing a service request to indicate the current resource code.
     *
     * @param $code string Resource code.
     * @return Boolean True if resource exists, false otherwise
     */
    function SetCurrentResourceCode( $code )
    {
        $r_resource =& $this->GetResource( $code );

        if ( is_null( $r_resource ) )
        {
            return false;
        }

        $this->_CurrentResourceCode( $code );

        return true;

    } // end of function SetCurrentResourceCode

    /**
     * Return the current resource code.
     *
     * @return string Current resource code.
     */
    function GetCurrentResourceCode()
    {
        return $this->_CurrentResourceCode();

    } // end of function GetCurrentResourceCode

    /**
     * Set the current resource code when the parameter is specified and 
     * return the resource code stored in a static variable. This way we
     * keep compatibility with PHP4 and PHP5.
     *
     * @param $setCode string needle.
     * @return string Current resource code (can be null).
     */
    function _CurrentResourceCode( $setCode=null )
    {
        static $code;

        if ( ! is_null( $setCode ) )
        {
            $code = $setCode;
        }
        
        return $code;

    } // end of function _CurrentResourceCode

    function &GetResource( $code, $raiseError=true )
    {
        for ( $i = 0; $i < count( $this->mResources ); ++$i ) 
        {
            if ( strcasecmp( $code, $this->mResources[$i]->GetCode() ) == 0 ) 
            {
                return $this->mResources[$i];
            }
        }

        if ( $raiseError ) 
        {
            $error = 'Could not find resource identified by code "'.$code.'"';
            TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, $error, DIAG_ERROR );
        }

        $resource = null;

        return $resource;

    } // end of member function GetResource

    function GetAllResources( )
    {
        return $this->mResources;

    } // end of member function GetAllResources

    function GetActiveResources( )
    {
        $active = array();

        foreach ( $this->mResources as $resource )
        {
            if ( $resource->GetStatus() == 'active' )
            {
                array_push( $active, $resource );
            }
        }

        return $active;

    } // end of member function GetActiveResources

    function Load( ) 
    {
        global $g_dlog;

        $file = $this->GetFile();

        if ( is_object( $g_dlog ) )
        {
            $g_dlog->debug('Loading resources from file: '.$file);
        }

        if ( !( $fp = fopen( $file, 'r' ) ) ) 
        {
            $error = 'Could not load the resources XML file ('.$file.
                     '). Please check provider installation.';
            TpDiagnostics::Append( DC_CONFIG_FAILURE, $error, DIAG_ERROR );

            return;
        }

        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_element_handler( $parser, 'StartElement', 'EndElement' );
        xml_set_character_data_handler( $parser, 'CharacterData' );
      
        while ( $data = fread( $fp, 4096 ) ) 
        {
            if ( ! xml_parse( $parser, $data, feof( $fp ) ) ) 
            {
                $error = sprintf( "Error parsing resources file: %s at line %d",
                                  xml_error_string( xml_get_error_code( $parser ) ),
                                  xml_get_current_line_number( $parser ) );

                TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_FATAL );
                return;
            }
        }

        fclose( $fp );

        if ( ! defined( 'TP_RUNNING_TAPIR' ) )
        {
            // No need to use session when running the web service (tapir.php)
            $this->SaveOnSession();
        }

    } // end of member function Load

    function StartElement( $parser, $name, $attrs ) 
    {
        if ( strcasecmp( $name, 'resource' ) == 0 ) 
        {
            $resource = new TpResource();

            if ( isset( $attrs['code'] ) )
            {
                $resource->SetCode( $attrs['code'] );
            }
            if ( isset( $attrs['status'] ) )
            {
                $resource->SetStatus( $attrs['status'] );
            }
            if ( isset( $attrs['accesspoint'] ) )
            {
                $resource->SetAccesspoint( $attrs['accesspoint'] );
            }
            if ( isset( $attrs['metadataFile'] ) )
            {
                $resource->SetMetadataFile( $attrs['metadataFile'] );
            }
            if ( isset( $attrs['configFile'] ) )
            {
                $resource->SetConfigFile( $attrs['configFile'] );
            }
            if ( isset( $attrs['capabilitiesFile'] ) )
            {
                $resource->SetCapabilitiesFile( $attrs['capabilitiesFile'] );
            }

            $this->AddResource( $resource );
        }

    } // end of member function StartElement

    function EndElement( $parser, $name ) 
    {

    } // end of member function EndElement

    function CharacterData( $parser, $data ) 
    {

    } // end of member function CharacterData

    function AddResource( $resource )
    {
        array_push( $this->mResources, $resource );

    } // end of member function AddResource

    function RemoveResource( $code )
    {
        $index = 0;

        foreach ( $this->mResources as $resource ) 
        {
            if ( strcasecmp( $code, $resource->GetCode() ) == 0 ) 
            {
                $files = $resource->GetAssociatedFiles();

                foreach ( $files as $file )
                {
                    if ( file_exists( $file ) and ! unlink( $file ) )
                    {
                        $error = 'Could not remove associated file "'.$file.
                                 '". Please check file system permissions.';
                                 TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, 
                                                        $error, DIAG_ERROR );
                        return false;
                    }
                }

                array_splice( $this->mResources, $index, 1 );

                if ( ! $this->Save() )
                {
                    return false;
                }

                unset( $_REQUEST['resource'] );

                return true;
            }

            ++$index;
        }

        $error = 'Could not find resource identified by code "'.$code.
                 '". Please check installation.';
        TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, $error, DIAG_ERROR );

        return false;

    } // end of member function RemoveResource

    function GetFile( ) 
    {
        $file = realpath( TP_CONFIG_DIR.'/'.TP_RESOURCES_FILE );

        if ( ! $file ) 
        {
            $file = TP_CONFIG_DIR.'/'.TP_RESOURCES_FILE;
        }

        return $file;

    } // end of member function GetFile

    function GetXml( ) 
    {
        $xml = "<resources>\n";

        foreach ( $this->mResources as $resource ) 
        {
            $xml .= "\t".$resource->GetXml()."\n";
        }

        $xml .= "</resources>\n";

        return $xml;

    } // end of member function GetXtml

    function Save( ) 
    {
        global $g_dlog;

        if ( is_object( $g_dlog ) )
        {
            $g_dlog->debug('Saving resources on file');
        }

        if ( ! TpConfigUtils::WriteToFile( $this->GetXml(), $this->GetFile() ) ) 
        {
            $last_error = TpDiagnostics::PopDiagnostic();

            $new_error = sprintf( "Could not write resources file: %s", $last_error );

            TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );

            return false;
        }

        $this->SaveOnSession();

        return true;

    } // end of member function Save

    function SaveOnSession( ) 
    {
        global $g_dlog;

        if ( is_object( $g_dlog ) )
        {
            $g_dlog->debug('Saving resources on session');
        }

        $_SESSION['resources'] =& $this;

    } // end of member function SaveOnSession

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
      return array( 'mResources' );

    } // end of member function __sleep

} // end of TpResources
?>