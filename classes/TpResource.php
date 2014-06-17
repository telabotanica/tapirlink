<?php
/**
 * $Id: TpResource.php 752 2008-09-04 19:15:28Z rdg $
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

require_once(TP_XPATH_LIBRARY);
require_once('TpDiagnostics.php');
require_once('TpResourceMetadata.php');
require_once('TpDataSource.php');
require_once('TpTables.php');
require_once('TpLocalFilter.php');
require_once('TpLocalMapping.php');
require_once('TpSettings.php');
require_once('TpUtils.php');
require_once('TpSqlBuilder.php');
require_once('TpConceptMapping.php');

class TpResource
{
    var $mCode;
    var $mMetadataFile;       // just the file name (no path)
    var $mConfigFile;         // just the file name (no path)
    var $mCapabilitiesFile;   // just the file name (no path)
    var $mStatus = 'new';     // "new", "pending", "active" or "disabled"
                              // "new"     = resource that didn't complete the 
                              //             wizard process
                              // "pending" = resource that completed the wizard 
                              //             process but for some reason has 
                              //             pending issues (eg. recently 
                              //             imported resources)
    var $mAccesspoint;        // Repeats attribute in mMetadata for performance reasons
    var $mConfigXp;           // XPath parser for config file
    var $mMetadata;           // TpResourceMetadata object
    var $mDataSource;         // TpDataSource object
    var $mTables;             // TpTables object
    var $mLocalFilter;        // TpLocalFilter object
    var $mLocalMapping;       // TpLocalMapping object
    var $mSettings;           // TpSettings object
    var $mConfigLoaded;       // Indicates if the configuration was already loaded

    function TpResource( ) 
    {

    } // end of member function TpResource

    function SetCode( $code ) 
    {
        $this->mCode = $code;

    } // end of member function SetCode

    function GetCode( ) 
    {
        return $this->mCode;

    } // end of member function GetCode

    function SetStatus( $status ) 
    {
        $this->mStatus = $status;

    } // end of member function SetStatus

    function GetStatus( ) 
    {
        return $this->mStatus;

    } // end of member function GetStatus

    function SetAccesspoint( $accesspoint ) 
    {
        $this->mAccesspoint = $accesspoint;

    } // end of member function SetAccesspoint

    function GetAccesspoint( ) 
    {
        return $this->mAccesspoint;

    } // end of member function GetAccesspoint

    function SetMetadataFile( $file ) 
    {
        $this->mMetadataFile = $file;

    } // end of member function SetMetadataFile

    function GetMetadataFile( ) 
    {
        if ( $this->mMetadataFile == null ) 
        {
            $this->mMetadataFile = $this->GetFile( 'metadata' );
        }

        return TP_CONFIG_DIR.DIRECTORY_SEPARATOR.$this->mMetadataFile;

    } // end of member function GetMetadataFile

    function SetConfigFile( $file ) 
    {
        $this->mConfigFile = $file;

    } // end of member function SetConfigFile

    function GetConfigFile( ) 
    {
        if ( $this->mConfigFile == null ) 
        {
            $this->mConfigFile = $this->GetFile( 'config' );
        }

        return TP_CONFIG_DIR.DIRECTORY_SEPARATOR.$this->mConfigFile;

    } // end of member function GetConfigFile

    function SetCapabilitiesFile( $file ) 
    {
        $this->mCapabilitiesFile = $file;

    } // end of member function SetCapabilitiesFile

    function GetCapabilitiesFile( ) 
    {
        if ( $this->mCapabilitiesFile == null ) 
        {
            $this->mCapabilitiesFile = $this->GetFile( 'capabilities' );
        }

        return TP_CONFIG_DIR.DIRECTORY_SEPARATOR.$this->mCapabilitiesFile;

    } // end of member function GetCapabilitiesFile

    function GetFile( $label ) 
    {
        $suffix = '_'.$label.'.xml';

        if ( $this->mCode == null ) 
        {
            $error = 'Could not create '.$label.' file name without having '.
                     'a resource code!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return null;
        }

        $file_name = $this->mCode.$suffix;

        $file = TP_CONFIG_DIR.DIRECTORY_SEPARATOR.$file_name;

        $attempt = 1;

        while ( file_exists( $file ) and $attempt < 10 ) 
        {
            $file_name = $this->mCode.'_'.$attempt.$suffix;

            $file = TP_CONFIG_DIR.DIRECTORY_SEPARATOR.$file_name;

            ++$attempt;
        }

        if ( $attempt == 10 ) 
        {
            $error = 'Could not create '.$label.' file name - exceeded number '.
                     'of attempts to find a unique name!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return null;
        }

        return $file_name;

    } // end of member function GetFile

    function HasMetadata( ) 
    {
        if ( ! $this->mMetadataFile )
        {
            return false;
        }

        return true;

    } // end of member function HasMetadata

    function ConfiguredMetadata( ) 
    {
        if ( ! $this->HasMetadata() )
        {
            return false;
        }

        // Important: don't use GetMetadata here, since the reference it
        // returns may need to be loaded in a different way later!
        $resource_metadata = new TpResourceMetadata();

        if ( $resource_metadata->LoadFromXml( $this->mCode, $this->GetMetadataFile() ) )
        {
            // Set access point to pass validation
            $resource_metadata->SetAccesspoint( $this->GetAccesspoint() );

            $raiseErrors = true;

            if ( ! $resource_metadata->Validate( $raiseErrors ) )
            {
                return false;
            }
        }

        return true;

    } // end of member function ConfiguredMetadata

    function ConfiguredDatasource( ) 
    {
        if ( $this->mCode == null or $this->mConfigFile == null )
        {
            return false;
        }

        if ( $this->LoadConfigXp() and 
             count( $this->mConfigXp->match( '/configuration[1]/datasource[1]' ) ) )
        {
            return true;
        }

        return false;     

    } // end of member function ConfiguredDatasource

    function ConfiguredTables( ) 
    {
        if ( $this->mCode == null or $this->mConfigFile == null )
        {
            return false;
        }

        if ( $this->LoadConfigXp() and 
             count( $this->mConfigXp->match( '/configuration[1]/table[1]' ) ) )
        {
            return true;
        }

        return false;     

    } // end of member function ConfiguredTables

    function ConfiguredLocalFilter( ) 
    {
        if ( $this->mCode == null or $this->mConfigFile == null )
        {
            return false;
        }

        if ( $this->LoadConfigXp() and 
             count( $this->mConfigXp->match( '/configuration[1]/filter[1]' ) ) )
        {
            return true;
        }

        return false;     

    } // end of member function ConfiguredLocalMapping

    function ConfiguredMapping( ) 
    {
        if ( $this->mCode == null or $this->mConfigFile == null or 
             $this->mCapabilitiesFile == null )
        {
            return false;
        }

        if ( $this->LoadConfigXp() and 
             count( $this->mConfigXp->match( '/configuration[1]/mapping[1]' ) ) )
        {
            // Assuming that capabilities also has the <concepts> section present
            return true;
        }

        return false;     

    } // end of member function ConfiguredMapping

    function ConfiguredSettings( ) 
    {
        if ( $this->mCode == null or $this->mConfigFile == null or 
             $this->mCapabilitiesFile == null )
        {
            return false;
        }

        if ( $this->LoadConfigXp() and 
             count( $this->mConfigXp->match( '/configuration[1]/settings[1]' ) ) )
        {
            // Assuming that capabilities is valid
            return true;
        }

        return false;

    } // end of member function ConfiguredSettings

    function GetAssociatedFiles( ) 
    {
        $files = array();

        if ( isset( $this->mMetadataFile ) )
        {
            array_push( $files, $this->GetMetadataFile() );
        }

        if ( isset( $this->mConfigFile ) )
        {
            array_push( $files, $this->GetConfigFile() );
        }

        if ( isset( $this->mCapabilitiesFile ) )
        {
            array_push( $files, $this->GetCapabilitiesFile() );
        }

        return $files;

    } // end of member function GetAssociatedFiles

    function IsValid( ) 
    {
        if ( $this->ConfiguredMetadata() and $this->ConfiguredDatasource() and 
             $this->ConfiguredTables()   and $this->ConfiguredMapping()    and 
             $this->ConfiguredSettings() ) 
        {
            return true;
        }

        return false;

    } // end of member function IsValid

    function IsNew( ) 
    {
        if ( $this->mStatus == 'new' )
        {
            return true;
        }

        return false;

    } // end of member function IsNew

    function LoadConfigXp( )
    {
        if ( ! is_object( $this->mConfigXp ) )
        {
            $this->mConfigXp = new XPath();
            $this->mConfigXp->setVerbose( 0 );
            $this->mConfigXp->setXmlOption( XML_OPTION_CASE_FOLDING, false );
            $this->mConfigXp->setXmlOption( XML_OPTION_SKIP_WHITE, true );

            if ( ! $this->mConfigXp->importFromFile( $this->GetConfigFile() ) )
            {
                $error = 'Could not load the XML file ('.$this->mConfigFile.
                         ') associated with resource "'.$this->mCode.'". '.
                         'Please check provider installation.';
                TpDiagnostics::Append( DC_CONFIG_FAILURE, $error, DIAG_ERROR );
                return false;
            }
        }

        return true;

    } // end of member function GetConfigXp

    function GetXml( ) 
    {
        $metadataAttr = $configAttr = $capabilitiesAttr = '';

        if ( $this->mMetadataFile )
        {
            $metadataAttr = ' metadataFile="'.basename( $this->mMetadataFile ).'"';
        }

        if ( $this->mConfigFile )
        {
            $configAttr = ' configFile="'.basename( $this->mConfigFile ).'"';
        }

        if ( $this->mCapabilitiesFile )
        {
            $capabilitiesAttr = ' capabilitiesFile="'.basename( $this->mCapabilitiesFile ).'"';
        }

        return sprintf( '<resource code="%s" status="%s" accesspoint="%s"%s%s%s/>', 
                        $this->mCode, $this->mStatus, $this->mAccesspoint,
                        $metadataAttr, $configAttr, $capabilitiesAttr );

    } // end of member function GetXtml

    function &GetMetadata( ) 
    {
        if ( ! is_object( $this->mMetadata ) )
        {
            $this->mMetadata = new TpResourceMetadata();
        }

        return $this->mMetadata;

    } // end of member function GetMetadata

    function &GetDataSource( ) 
    {
        if ( ! is_object( $this->mDataSource ) )
        {
            $this->mDataSource = new TpDataSource();
        }

        return $this->mDataSource;

    } // end of member function GetDataSource

    function &GetTables( ) 
    {
        if ( ! is_object( $this->mTables ) )
        {
            $this->mTables = new TpTables();
        }

        return $this->mTables;

    } // end of member function GetTables

    function &GetLocalFilter( ) 
    {
        if ( ! is_object( $this->mLocalFilter ) )
        {
            $this->mLocalFilter = new TpLocalFilter();
        }

        return $this->mLocalFilter;

    } // end of member function GetLocalFilter

    function &GetLocalMapping( ) 
    {
        if ( ! is_object( $this->mLocalMapping ) )
        {
            $this->mLocalMapping = new TpLocalMapping();

            $this->mLocalMapping->SetResource( $this );
        }

        return $this->mLocalMapping;

    } // end of member function GetLocalMapping

    function &GetSettings( ) 
    {
        if ( ! is_object( $this->mSettings ) )
        {
            $this->mSettings = new TpSettings();
        }

        return $this->mSettings;

    } // end of member function GetSettings

    function LoadConfig( ) 
    {
        if ( $this->mConfigLoaded )
        {
            return true;
        }

        if ( ! $this->LoadConfigXp() )
        {
            return false;
        }

        // Data source
        $r_data_source =& $this->GetDataSource();

        $r_data_source->LoadFromXml( $this->GetConfigFile(), $this->mConfigXp );

        // Tables
        $r_tables =& $this->GetTables();

        $r_tables->LoadFromXml( $this->GetConfigFile(), $this->mConfigXp );

        // Local Filter
        $r_local_filter =& $this->GetLocalFilter();

        $r_local_filter->LoadFromXml( $this->GetConfigFile(), $this->mConfigXp );

        // Local Mapping
        $r_local_mapping =& $this->GetLocalMapping();

        $r_local_mapping->LoadFromXml( $this->GetConfigFile() );

        // Settings
        $r_settings =& $this->GetSettings();

        $r_settings->LoadFromXml( $this->GetConfigFile(), $this->GetCapabilitiesFile() );

        $this->mConfigLoaded = true;

        return true;

    } // end of member function LoadConfig

    function SaveMetadata( $updateResources=true )
    {
        if ( ! is_object( $this->mMetadata ) )
        {
            $error = 'Cannot save metadata when the corresponding '.
                     'property is not loaded!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return false;
        }

        // Save in metadata file

        if ( $this->mMetadataFile == null )
        {
            // Create metadata file

            if ( $this->GetMetadataFile() == null )
            {
                return false;
            }
        }

        $offset = '';
        $indent_with = "\t";

        $version = TP_VERSION.' (revision '.TP_REVISION.')';

        $xml = "<!-- Generated by TapirLink $version -->\n";
        $xml .= $this->mMetadata->GetXml( $offset, $indent_with );

        if ( ! TpConfigUtils::WriteToFile( $xml, $this->GetMetadataFile() ) ) 
        {
            $last_error = TpDiagnostics::PopDiagnostic();

            $new_error = sprintf( "Could not write metadata file: %s", $last_error );

            TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );

            return;
        }

        // Always update resources because code and accesspoint can change
        $force_update = true;

        $this->SetAccesspoint( $this->mMetadata->GetAccesspoint() );

        $this->SetCode( $this->mMetadata->GetId() );

        if ( $updateResources )
        {
            $this->UpdateResources( $force_update );
        }

        return true;

    } // end of member function SaveMetadata

    function SaveDataSource( $updateResources=true )
    {
        if ( ! is_object( $this->mDataSource ) )
        {
            $error = 'Cannot save data source when the corresponding '.
                     'property is not loaded!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return false;
        }

        // Save in config file

        $force_update = false;

        if ( $this->mConfigFile == null )
        {
            // Create config file
            if ( $this->GetConfigFile() == null )
            {
                return false;
            }

            $content = XML_HEADER."\n".
                       '<configuration>'."\n".
                       "\t".$this->mDataSource->GetXml()."\n".
                       '</configuration>';

            if ( ! TpConfigUtils::WriteToFile( $content, $this->GetConfigFile() ) ) 
            {
                $last_error = TpDiagnostics::PopDiagnostic();

                $new_error = sprintf( "Could not write config file: %s", 
                                      $last_error->GetDescription() );

                TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );
                return false;
            }

            // After this, TpResources must be saved
            $force_update = true;
        }
        else
        {
            $position = '/configuration[1]/datasource[1]';

            $prev_position = '/configuration[1]';

            if ( ! TpConfigUtils::WriteXmlPiece( $this->mDataSource->GetXml(), 
                                                 $position, $prev_position, 
                                                 $this->GetConfigFile() ) )
            {
                $last_error = TpDiagnostics::PopDiagnostic();

                $new_error = sprintf( "Could not update config file: %s", 
                                      $last_error->GetDescription() );

                TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );
                return false;
            }
        }

        if ( $updateResources )
        {
            $this->UpdateResources( $force_update );
        }

        return true;

    } // end of member function SaveDataSource

    function SaveTables( $updateResources=true )
    {
        if ( ! is_object( $this->mTables ) )
        {
            $error = 'Cannot save tables when the corresponding '.
                     'property is not loaded!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return false;
        }

        // Save in config file

        $force_update = false;

        if ( $this->mConfigFile == null )
        {
            // Create config file

            if ( $this->GetConfigFile() == null )
            {
                return false;
            }

            $content = XML_HEADER."\n".
                       '<configuration>'."\n".
                       "\t".$this->mTables->GetXml()."\n".
                       '</configuration>';

            if ( ! TpConfigUtils::WriteToFile( $content, $this->GetConfigFile() ) ) 
            {
                $last_error = TpDiagnostics::PopDiagnostic();

                $new_error = sprintf( "Could not write config file: %s", 
                                      $last_error->GetDescription() );

                TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );
                return false;
            }

            // After this, TpResources must be saved
            $force_update = true;
        }
        else
        {
            $position = '/configuration[1]/table[1]';

            $prev_position = '/configuration[1]/datasource[1]';

            if ( ! TpConfigUtils::WriteXmlPiece( $this->mTables->GetXml(), 
                                                 $position, $prev_position, 
                                                 $this->GetConfigFile() ) )
            {
                $last_error = TpDiagnostics::PopDiagnostic();

                $new_error = sprintf( "Could not update config file: %s", 
                                      $last_error->GetDescription() );

                TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );
                return false;
            }

            // If any table was removed, it is necessary to remove possible 
            // related mappings

            $root_table = $this->mTables->GetRootTable();

            $valid_tables = $root_table->GetAllTables();

            if ( $this->ConfiguredMapping() )
            {
                if ( ! is_object( $this->mLocalMapping ) )
                {
                    $this->GetLocalMapping(); // no need to get result (property ref)

                    $this->mLocalMapping->LoadFromXml( $this->GetConfigFile() );
                }

                $updated_mapping = false;

                foreach ( $this->mLocalMapping->GetMappedSchemas() as $ns => $schema ) 
                {
                    foreach ( $schema->GetConcepts() as $concept_id => $concept ) 
                    {
                        if ( $concept->GetMappingType() == 'SingleColumnMapping' )
                        {
                            $mapping = $concept->GetMapping();

                            $table = $mapping->GetTable();

                            // Unmap concepts that are not related to "valid" tables
                            if ( ! in_array( $table, $valid_tables ) )
                            {
                                $concept->SetMapping( null );

                                $updated_mapping = true;
                            }
                        }
                    }
                }

                if ( $updated_mapping )
                {
                    if ( ! $this->SaveLocalMapping() )
                    {
                        $last_error = TpDiagnostics::PopDiagnostic();

                        $new_error = sprintf( "Could not remove mappings that are ".
                                              "referencing removed tables: %s", 
                                              $last_error );

                        TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );
                        return;
                    }
                }
            }
        }

        if ( $updateResources )
        {
            $this->UpdateResources( $force_update );
        }

        return true;

    } // end of member function SaveTables

    function SaveLocalFilter( $updateResources=true )
    {
        if ( ! is_object( $this->mLocalFilter ) )
        {
            $error = 'Cannot save local filter when the corresponding '.
                     'property is not loaded!';
                     
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return false;
        }

        // Save in config file

        $force_update = false;

        if ( $this->mConfigFile == null )
        {
            // Create config file

            if ( $this->GetConfigFile() == null )
            {
                return false;
            }

            $content = XML_HEADER."\n".
                       '<configuration>'."\n".
                       "\t<filter>".$this->mLocalFilter->GetXml()."</filter>\n".
                       '</configuration>';

            if ( ! TpConfigUtils::WriteToFile( $content, $this->GetConfigFile() ) ) 
            {
                $last_error = TpDiagnostics::PopDiagnostic();

                $new_error = sprintf( "Could not write config file: %s", 
                                      $last_error->GetDescription() );

                TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );
                return false;
            }

            // After this, TpResources must be saved
            $force_update = true;
        }
        else
        {
            $position = '/configuration[1]/filter[1]';

            $prev_position = '/configuration[1]/table[1]';

            $content = '<filter>'.$this->mLocalFilter->GetXml().'</filter>';

            if ( ! TpConfigUtils::WriteXmlPiece( $content, 
                                                 $position, $prev_position, 
                                                 $this->GetConfigFile() ) )
            {
                $last_error = TpDiagnostics::PopDiagnostic();

                $new_error = sprintf( "Could not update config file: %s", 
                                      $last_error->GetDescription() );

                TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );
                return false;
            }
        }

        if ( $updateResources )
        {
            $this->UpdateResources( $force_update );
        }

        return true;

    } // end of member function SaveLocalFilter

    function SaveLocalMapping( $updateResources=true ) 
    {
        if ( ! is_object( $this->mLocalMapping ) )
        {
            $error = 'Cannot save local mapping when the corresponding '.
                     'resource property is not loaded!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return false;
        }

        $update_resources = false;

        // Need to use this variable name because of the capabilities template!
        $r_local_mapping =& $this->GetLocalMapping();

        // Save in config file
        if ( $this->mConfigFile == null )
        {
            // Should never fall here, but in any case, create the file

            if ( $this->GetConfigFile() == null )
            {
                return false;
            }

            $content = XML_HEADER."\n".
                       '<configuration>'."\n".
                       "\t".$r_local_mapping->GetConfigXml()."\n".
                       '</configuration>';

            if ( ! TpConfigUtils::WriteToFile( $content, $this->GetConfigFile() ) ) 
            {
                $last_error = TpDiagnostics::PopDiagnostic();

                $new_error = sprintf( "Could not write config file: %s", 
                                      $last_error->GetDescription() );

                TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );
                return false;
            }

            // After this, TpResources must be saved
            $update_resources = true;
        }
        else
        {
            $position = '/configuration[1]/mapping[1]';

            $prev_position = '/configuration[1]/filter[1]';

            if ( ! TpConfigUtils::WriteXmlPiece( $r_local_mapping->GetConfigXml(), 
                                                 $position, $prev_position, 
                                                 $this->GetConfigFile() ) )
            {
                $last_error = TpDiagnostics::PopDiagnostic();

                $new_error = sprintf( "Could not update config file: %s", 
                                      $last_error->GetDescription() );

                TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );
                return false;
            }
        }

        // Save in capabilities file

        if ( $this->mCapabilitiesFile == null )
        {
            // Create the capabilities file
            if ( $this->GetCapabilitiesFile() == null )
            {
                return false;
            }

            // After defining the file, TpResources must be saved
            $update_resources = true;
        }

        // Load settings for new resources

        $r_settings =& $this->GetSettings();

        if ( $this->IsNew() )
        {
            $r_settings->LoadDefaults();
        }
        else
        {
            // Otherwise always load them from XML
            $r_settings->LoadFromXml( $this->GetConfigFile(), $this->GetCapabilitiesFile() );
        }

        // Capture output of capabilities template being interpreted
        ob_start();

        $version = TP_VERSION.' (revision '.TP_REVISION.')';

        echo("<!-- Generated by TapirLink $version -->\n");

        include('capabilities.tmpl.php');

        $content = ob_get_contents();

        ob_end_clean();

        if ( ! TpConfigUtils::WriteToFile( $content, $this->GetCapabilitiesFile() ) ) 
        {
            $last_error = TpDiagnostics::PopDiagnostic();

            $new_error = sprintf( "Could not write capabilities file: %s", 
                                  $last_error->GetDescription() );

            TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );
            return false;
        }

        if ( $updateResources )
        {
            $this->UpdateResources( $update_resources );
        }

        return true;

    } // end of member function SaveLocalMapping

    function SaveSettings( $updateResources=true ) 
    {
        if ( ! is_object( $this->mSettings ) )
        {
            $error = 'Cannot save settings when the corresponding '.
                     'resource property is not loaded!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return false;
        }

        $update_resources = false;

        // Need to use this variable name because of the capabilities template!
        $r_settings =& $this->GetSettings();

        // Save in config file
        if ( $this->mConfigFile == null )
        {
            // Should never fall here, but in any case, create the file

            if ( $this->GetConfigFile() == null )
            {
                return false;
            }

            $content = XML_HEADER."\n".
                       '<configuration>'."\n".
                       "\t".$r_settings->GetConfigXml()."\n".
                       '</configuration>';

            if ( ! TpConfigUtils::WriteToFile( $content, $this->GetConfigFile() ) ) 
            {
                $last_error = TpDiagnostics::PopDiagnostic();

                $new_error = sprintf( "Could not write config file: %s", 
                                      $last_error->GetDescription() );

                TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );
                return false;
            }

            // After this, TpResources must be saved
            $update_resources = true;
        }
        else
        {
            $position = '/configuration[1]/settings[1]';

            $prev_position = '/configuration[1]/mapping[1]';

            if ( ! TpConfigUtils::WriteXmlPiece( $r_settings->GetConfigXml(), 
                                                 $position, $prev_position, 
                                                 $this->GetConfigFile() ) )
            {
                $last_error = TpDiagnostics::PopDiagnostic();

                $new_error = sprintf( "Could not update config file: %s", 
                                      $last_error->GetDescription() );

                TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );
                return false;
            }
        }

        // Always load local mapping from XML to avoid saving unchanged things
        $r_local_mapping =& $this->GetLocalMapping();
 
        $r_local_mapping->LoadFromXml( $this->GetConfigFile() );
 
        // Save in capabilities file

        if ( $this->mCapabilitiesFile == null )
        {
            // Create the capabilities file
            if ( $this->GetCapabilitiesFile() == null )
            {
                return false;
            }

            // After defining the file, TpResources must be saved
            $update_resources = true;
        }

        // Capture output of capabilities template being interpreted
        ob_start();

        $version = TP_VERSION.' (revision '.TP_REVISION.')';

        echo("<!-- Generated by TapirLink $version -->\n");

        include('capabilities.tmpl.php');

        $content = ob_get_contents();

        ob_end_clean();

        if ( ! TpConfigUtils::WriteToFile( $content, $this->GetCapabilitiesFile() ) ) 
        {
            $last_error = TpDiagnostics::PopDiagnostic();

            $new_error = sprintf( "Could not write capabilities file: %s", 
                                  $last_error->GetDescription() );

            TpDiagnostics::Append( DC_IO_ERROR, $new_error, DIAG_ERROR );
            return false;
        }

        // Reset mConfigXp (otherwise ConfiguredSettings will use the existing
        // xparser object and report false)
        $this->mConfigXp = null;

        if ( $updateResources )
        {
            $this->UpdateResources( $update_resources );
        }

        return true;

    } // end of member function SaveSettings

    function UpdateResources( $force=false )
    {
        if ( ( $this->mStatus == 'new' or $this->mStatus == 'pending') and 
               $this->IsValid() )
        {
            $this->mStatus = 'active';

            $force = true;
        }

        if ( $force )
        {
            $r_resources =& TpResources::GetInstance();

            if ( ! $r_resources->Save() )
            {
                // What should we do here?
                return;
            }
        }

    } // end of member function UpdateResources

    function HasVariable( $var )
    {
        $vars = array( 'date', 'timestamp', 'datasourcename', 'accesspoint', 
                       'lastupdate', 'datecreated', 'metadatalanguage', 
                       'datasourcelanguage', 'datasourcedescription', 'rights',
                       'technicalcontactname', 'technicalcontactemail',
                       'contentcontactname', 'contentcontactemail' );

        return in_array( strtolower( $var ), $vars );

    } // end of member function HasVariable

    function GetVariable( $var )
    {
        if ( ! $this->HasVariable( $var ) )
        {
            return null;
        }

        if ( strcasecmp( $var, 'date' ) == 0 )
        {
            $timestamp = TpUtils::MicrotimeFloat();

            return strftime( '%Y-%m-%d', $timestamp );
        }
        else if ( strcasecmp( $var, 'timestamp' ) == 0 )
        {
            return TpUtils::TimestampToXsdDateTime( TpUtils::MicrotimeFloat() );
        }
        else if ( strcasecmp( $var, 'dataSourceName' ) == 0 )
        {
            if ( ! is_object( $this->mMetadata ) )
            {
                $this->mMetadata = new TpResourceMetadata();

                $metadata_file = $this->GetMetadataFile();

                $this->mMetadata->LoadFromXml( $this->mCode, $metadata_file );
            }

            $default_lang = $this->mMetadata->GetDefaultLanguage();

            $titles = $this->mMetadata->GetTitles();

            foreach ( $titles as $title )
            {
                if ( empty( $default_lang ) )
                {
                    // Return first title if there's no default language
                    return $title->GetValue();
                }
                else
                {
                    $lang = $title->GetLang();

                    if ( empty( $lang ) )
                    {
                        // Return title with empty lang if there's a default language
                        return $title->GetValue();
                    }
                }
            }

            return $titles[0]->GetValue();
        }
        else if ( strcasecmp( $var, 'accessPoint' ) == 0 )
        {
            return $this->mAccesspoint;
        }
        else if ( strcasecmp( $var, 'lastUpdate' ) == 0 )
        {
            return $this->GetDateLastModified();
        }
        else if ( strcasecmp( $var, 'dateCreated' ) == 0 )
        {
            if ( ! is_object( $this->mMetadata ) )
            {
                $this->mMetadata = new TpResourceMetadata();

                $metadata_file = $this->GetMetadataFile();

                $this->mMetadata->LoadFromXml( $this->mCode, $metadata_file );
            }

            return $this->mMetadata->GetCreated();
        }
        else if ( strcasecmp( $var, 'metadataLanguage' ) == 0 )
        {
            if ( ! is_object( $this->mMetadata ) )
            {
                $this->mMetadata = new TpResourceMetadata();

                $metadata_file = $this->GetMetadataFile();

                $this->mMetadata->LoadFromXml( $this->mCode, $metadata_file );
            }

            $default_lang = $this->mMetadata->GetDefaultLanguage();

            $titles = $this->mMetadata->GetTitles();

            foreach ( $titles as $title )
            {
                if ( empty( $default_lang ) )
                {
                    // Return language of first title if there's no default language
                    return $title->GetLang();
                }
                else
                {
                    $lang = $title->GetLang();

                    if ( empty( $lang ) )
                    {
                        // Return default language if there's a title with no lang
                        return $default_lang;
                    }
                }
            }

            return $titles[0]->GetLang();
        }
        else if ( strcasecmp( $var, 'datasourcelanguage' ) == 0 )
        {
            if ( ! is_object( $this->mMetadata ) )
            {
                $this->mMetadata = new TpResourceMetadata();

                $metadata_file = $this->GetMetadataFile();

                $this->mMetadata->LoadFromXml( $this->mCode, $metadata_file );
            }

            return $this->mMetadata->GetLanguage();
        }
        else if ( strcasecmp( $var, 'dataSourceDescription' ) == 0 )
        {
            if ( ! is_object( $this->mMetadata ) )
            {
                $this->mMetadata = new TpResourceMetadata();

                $metadata_file = $this->GetMetadataFile();

                $this->mMetadata->LoadFromXml( $this->mCode, $metadata_file );
            }

            $default_lang = $this->mMetadata->GetDefaultLanguage();

            $descriptions = $this->mMetadata->GetDescriptions();

            foreach ( $descriptions as $description )
            {
                if ( empty( $default_lang ) )
                {
                    // Return first description if there's no default language
                    return $description->GetValue();
                }
                else
                {
                    $lang = $description->GetLang();

                    if ( empty( $lang ) )
                    {
                        // Return description with empty lang if there's a default language
                        return $description->GetValue();
                    }
                }
            }

            return $descriptions[0]->GetValue();
        }
        else if ( strcasecmp( $var, 'rights' ) == 0 )
        {
            if ( ! is_object( $this->mMetadata ) )
            {
                $this->mMetadata = new TpResourceMetadata();

                $metadata_file = $this->GetMetadataFile();

                $this->mMetadata->LoadFromXml( $this->mCode, $metadata_file );
            }

            $default_lang = $this->mMetadata->GetDefaultLanguage();

            $rights = $this->mMetadata->GetRights();

            foreach ( $rights as $rights_in_lang )
            {
                if ( empty( $default_lang ) )
                {
                    // Return first rights if there's no default language
                    return $rights_in_lang->GetValue();
                }
                else
                {
                    $lang = $rights_in_lang->GetLang();

                    if ( empty( $lang ) )
                    {
                        // Return rights with empty lang if there's a default language
                        return $rights_in_lang->GetValue();
                    }
                }
            }

            if ( isset( $rights[0] ) ) 
            {
                return $rights[0]->GetValue();
            }

            return '';
        }
        else if ( strcasecmp( $var, 'technicalcontactname' ) == 0 )
        {
            if ( ! is_object( $this->mMetadata ) )
            {
                $this->mMetadata = new TpResourceMetadata();

                $metadata_file = $this->GetMetadataFile();

                $this->mMetadata->LoadFromXml( $this->mCode, $metadata_file );
            }

            $related_entities = $this->mMetadata->GetRelatedEntities();

            foreach ( $related_entities as $related_entity )
            {
                $entity_roles = $related_entity->GetRoles();

                if ( in_array( 'technical host', $entity_roles ) )
                {
                    $entity = $related_entity->GetEntity();

                    $related_contacts = $entity->GetRelatedContacts();

                    foreach ( $related_contacts as $related_contact )
                    {
                        $contact_roles = $related_contact->GetRoles();

                        if ( in_array( 'system administrator', $contact_roles ) )
                        {
                            $contact = $related_contact->GetContact();

                            return $contact->GetFullName();
                        }
                    }
                }
            }
        }
        else if ( strcasecmp( $var, 'technicalcontactemail' ) == 0 )
        {
            if ( ! is_object( $this->mMetadata ) )
            {
                $this->mMetadata = new TpResourceMetadata();

                $metadata_file = $this->GetMetadataFile();

                $this->mMetadata->LoadFromXml( $this->mCode, $metadata_file );
            }

            $related_entities = $this->mMetadata->GetRelatedEntities();

            foreach ( $related_entities as $related_entity )
            {
                $entity_roles = $related_entity->GetRoles();

                if ( in_array( 'technical host', $entity_roles ) )
                {
                    $entity = $related_entity->GetEntity();

                    $related_contacts = $entity->GetRelatedContacts();

                    foreach ( $related_contacts as $related_contact )
                    {
                        $contact_roles = $related_contact->GetRoles();

                        if ( in_array( 'system administrator', $contact_roles ) )
                        {
                            $contact = $related_contact->GetContact();

                            return $contact->GetEmail();
                        }
                    }
                }
            }
        }
        else if ( strcasecmp( $var, 'contentcontactname' ) == 0 )
        {
            if ( ! is_object( $this->mMetadata ) )
            {
                $this->mMetadata = new TpResourceMetadata();

                $metadata_file = $this->GetMetadataFile();

                $this->mMetadata->LoadFromXml( $this->mCode, $metadata_file );
            }

            $related_entities = $this->mMetadata->GetRelatedEntities();

            foreach ( $related_entities as $related_entity )
            {
                $entity_roles = $related_entity->GetRoles();

                if ( in_array( 'data supplier', $entity_roles ) )
                {
                    $entity = $related_entity->GetEntity();

                    $related_contacts = $entity->GetRelatedContacts();

                    foreach ( $related_contacts as $related_contact )
                    {
                        $contact_roles = $related_contact->GetRoles();

                        if ( in_array( 'data administrator', $contact_roles ) )
                        {
                            $contact = $related_contact->GetContact();

                            return $contact->GetFullName();
                        }
                    }
                }
            }
        }
        else if ( strcasecmp( $var, 'contentcontactemail' ) == 0 )
        {
            if ( ! is_object( $this->mMetadata ) )
            {
                $this->mMetadata = new TpResourceMetadata();

                $metadata_file = $this->GetMetadataFile();

                $this->mMetadata->LoadFromXml( $this->mCode, $metadata_file );
            }

            $related_entities = $this->mMetadata->GetRelatedEntities();

            foreach ( $related_entities as $related_entity )
            {
                $entity_roles = $related_entity->GetRoles();

                if ( in_array( 'data supplier', $entity_roles ) )
                {
                    $entity = $related_entity->GetEntity();

                    $related_contacts = $entity->GetRelatedContacts();

                    foreach ( $related_contacts as $related_contact )
                    {
                        $contact_roles = $related_contact->GetRoles();

                        if ( in_array( 'data administrator', $contact_roles ) )
                        {
                            $contact = $related_contact->GetContact();

                            return $contact->GetEmail();
                        }
                    }
                }
            }
        }

        return null;

    } // end of member function GetVariable

    function GetDateLastModified( )
    {
        $config_file = $this->GetConfigFile();

        if ( ! is_object( $this->mSettings ) )
        {
            $this->mSettings = new TpSettings();

            $this->mSettings->LoadFromXml( $config_file, '' );
        }

        // First try the fixed value
        $date_last_modified = $this->mSettings->GetModified();

        // If empty then there should be a field
        if ( ! empty( $date_last_modified ) )
        {
            return $date_last_modified;
        }

        $modifier = $this->mSettings->GetModifier();

        if ( empty( $modifier ) )
        {
            $error = 'Date Last Modified setting was not configured.';
            TpDiagnostics::Append( DC_CONFIG_FAILURE, $error, DIAG_WARN );

            return null;
        }

        if ( ! is_object( $this->mDataSource ) )
        {
            if ( ! $this->LoadConfigXp() )
            {
                return null;
            }

            $this->mDataSource = new TpDataSource();

            $this->mDataSource->LoadFromXml( $config_file, $this->mConfigXp );
        }

        $raise_errors = false;

        if ( ! $this->mDataSource->Validate( $raise_errors ) )
        {
            $err_str = 'Incorrect local database connection settings';
            TpDiagnostics::Append( DC_DB_CONNECTION_ERROR, $err_str, DIAG_ERROR );
            return null;
        }

        $cn = $this->mDataSource->GetConnection();

        if ( ! is_object( $cn ) )
        {
            $err_str = 'Could not establish a connection with the database!';
            TpDiagnostics::Append( DC_DB_CONNECTION_ERROR, $err_str, DIAG_ERROR );
            return null;
        }

        // Get field datatype
        $datatype = $this->mSettings->GetModifierDatatype();

        $sql_builder = new TpSqlBuilder( $cn );

        $target = TpSqlBuilder::GetSqlName( $modifier );

        if ( $datatype == TYPE_DATETIME )
        {
            $target = $cn->SQLDate( 'Y-m-d H:i:s', $target );
        }

        $sql_builder->AddTargetColumn( $target );

        // Tables
        if ( ! is_object( $this->mTables ) )
        {
            if ( ! $this->LoadConfigXp() )
            {
                return null;
            }

            $this->mTables = new TpTables();

            $this->mTables->LoadFromXml( $config_file, $this->mConfigXp );
        }

        // Local Filter
        if ( ! is_object( $this->mLocalFilter ) )
        {
            if ( ! $this->LoadConfigXp() )
            {
                return null;
            }

            $this->mLocalFilter = new TpLocalFilter();

            $this->mLocalFilter->LoadFromXml( $config_file, $this->mConfigXp );
        }

        // Local Mapping
        if ( ! is_object( $this->mLocalMapping ) )
        {
            if ( ! $this->LoadConfigXp() )
            {
                return null;
            }

            $this->mLocalMapping = new TpLocalMapping();

            $this->mLocalMapping->LoadFromXml( $config_file );
        }

        $sql_builder->AddRecordSource( $this->mTables->GetStructure() );

        if ( ! $this->mLocalFilter->IsEmpty() )
        {
            $local_filter_sql = $this->mLocalFilter->GetSql( $this );

            $sql_builder->AddCondition( $local_filter_sql );
        }

        $descend = true;

        $sql_builder->OrderBy( array( $target => $descend ) );

        $sql = $sql_builder->GetSql();

        TpDiagnostics::Append( DC_DEBUG_MSG, 'SQL: '.$sql, DIAG_DEBUG );

        $rs = &$cn->SelectLimit( $sql, 1 );

        if ( ! is_object( $rs ) )
        {
            $err = $cn->ErrorMsg();
            TpDiagnostics::Append( DC_DATABASE_ERROR, htmlentities($err), DIAG_ERROR );
            TpDiagnostics::Append( DC_DEBUG_MSG, htmlentities($sql), DIAG_DEBUG );
            $cn->Close();
            return null;
        }

        if ( ! $rs->EOF )
        {
            $date_time_parsed = strtotime( $rs->fields[0] );

            if ( $date_time_parsed === -1 or $date_time_parsed === false )
            {
                // could not parse value returned from db!
                // return current timestamp, but warn users
                $err = 'Could not parse datetime provided by local database: '.$rs->fields[0];
                TpDiagnostics::Append( DC_GENERAL_ERROR, htmlentities($err), DIAG_WARN );
            }
            else
            {
                $rs->Close();

                $this->mDataSource->ResetConnection();

                return TpUtils::TimestampToXsdDateTime( $date_time_parsed );
            }
        }

        $rs->Close();

        $this->mDataSource->ResetConnection();

        return null;

    } // end of member function GetDateLastModified

    function GetMetadataTemplateRevision( ) 
    {
        if ( ! $this->HasMetadata() )
        {
            return null;
        }

        $file = $this->GetMetadataFile();

        return $this->_GetRevisionFromFile( $file );

    } // end of member function GetMetadataTemplateRevision

    function GetCapabilitiesTemplateRevision( ) 
    {
        if ( ! $this->mCapabilitiesFile )
        {
            return null;
        }

        $file = $this->GetCapabilitiesFile();

        return $this->_GetRevisionFromFile( $file );

    } // end of member function GetCapabilitiesTemplateRevision

    function _GetRevisionFromFile( $file ) 
    {
        $lines = file( $file );

        if ( ! count( $lines ) )
        {
            return null;
        }

        $first_line = $lines[0];

        $revision_regexp = '/\(revision\s(\d+)\)/';

        if ( preg_match( $revision_regexp, $first_line, $matches ) )
        {
            return $matches[1];
        }

        return null;

    } // end of member function _GetRevisionFromFile

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
        return array( 'mCode', 'mMetadataFile', 'mConfigFile', 'mCapabilitiesFile',
                      'mDataSource', 'mTables', 'mLocalFilter', 'mLocalMapping',
                      'mStatus', 'mAccesspoint' );

    } // end of member function __sleep

} // end of TpResource
?>