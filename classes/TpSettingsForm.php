<?php
/**
 * $Id: TpSettingsForm.php 1997 2009-09-07 22:45:02Z rdg $
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

require_once('TpWizardForm.php');
require_once('TpUtils.php');
require_once('TpHtmlUtils.php');

class TpSettingsForm extends TpWizardForm 
{
    var $mStep = 6;
    var $mLabel = 'Settings';
    var $mTablesAndColumns = array();  // array (table_name.column_name)

    function TpSettingsForm( ) 
    {

    } // end of member function TpSettingsForm

    function LoadDefaults( ) 
    {
        if ( $this->mResource->ConfiguredSettings() )
        {
            $this->LoadFromXml();
        }
        else
        {
            $this->SetMessage( "To finish the configuration, please specify the additional settings below.\nYou can find more information about each field by clicking over the label." );

            $r_data_source =& $this->mResource->GetDataSource();

            $update_session_data = false;

            if ( ! $r_data_source->IsLoaded() )
            {
                $config_file = $this->mResource->GetConfigFile();

                $r_data_source->LoadFromXml( $config_file );

                $update_session_data = true;
            }

            $r_tables =& $this->mResource->GetTables();

            if ( ! $r_tables->IsLoaded() )
            {
                $config_file = $this->mResource->GetConfigFile();

                $r_tables->LoadFromXml( $config_file );

                $update_session_data = true;
            }

            if ( $update_session_data )
            {
                $r_resources =& TpResources::GetInstance();

                $r_resources->SaveOnSession();
            }

            $r_settings =& $this->mResource->GetSettings();

            $r_settings->LoadDefaults();

            $this->LoadDatabaseMetadata(); 
        }

    } // end of member function LoadDefaults

    function LoadFromSession( ) 
    {
        $r_settings =& $this->mResource->GetSettings();

        // Settings must already be stored in the session
        if ( ! $r_settings->GetLogOnly() == null )
        {
            // If not, the only reason I can think of is that the session expired,
            // so load everything from XML
            $this->LoadFromXml();

            return;
        }

        // Get datasource
        $r_data_source =& $this->mResource->GetDataSource();

        // If data source is not valid it's because it's empty, so load it from XML
        $raiseErrors = false;

        if ( ! $r_data_source->Validate( $raiseErrors ) ) 
        {
            $config_file = $this->mResource->GetConfigFile();

            $r_data_source->LoadFromXml( $config_file );
        }

        $r_tables =& $this->mResource->GetTables();

        $r_settings->LoadFromSession( $r_data_source->GetConnection() );

        $this->LoadDatabaseMetadata(); 

    } // end of member function LoadFromSession

    function LoadFromXml( ) 
    {
        if ( $this->mResource->ConfiguredSettings() )
        {
            $r_settings =& $this->mResource->GetSettings();

            $config_file = $this->mResource->GetConfigFile();

            $capabilities_file = $this->mResource->GetCapabilitiesFile();

            $r_settings->LoadFromXml( $config_file, $capabilities_file );

            $r_data_source =& $this->mResource->GetDataSource();

            $r_data_source->LoadFromXml( $config_file );

            $r_tables =& $this->mResource->GetTables();

            $r_tables->LoadFromXml( $config_file );

            $this->LoadDatabaseMetadata();

            $modifier = $r_settings->GetModifier();

            if ( ! empty( $modifier ) )
            {
                if ( ! in_array( $modifier,  $this->mTablesAndColumns ) )
                {
                    $msg = 'Date last modified table/field ('.$modifier.') '.
                           'does not exist in the database';
                    TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $msg, DIAG_ERROR );
                }
            }
        }
        else
        {
            $err_str = 'There is no "settings" XML configuration to be loaded!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $err_str, DIAG_ERROR );
            return;
        }

    } // end of member function LoadFromXml

    function LoadDatabaseMetadata( ) 
    {
        $r_data_source =& $this->mResource->GetDataSource();

        $r_tables =& $this->mResource->GetTables();

        $root_table = $r_tables->GetRootTable();

        $valid_tables = $root_table->GetAllTables();

        if ( $r_data_source->Validate() ) 
        {
            $cn = $r_data_source->GetConnection();

            if ( ! is_object( $cn ) )
            {
                $err_str = 'Problem when getting connection to database!';
                TpDiagnostics::Append( CFG_INTERNAL_ERROR, $err_str, DIAG_ERROR );
                return;
	    }

            $tables = $cn->MetaTables();

            if ( ! count( $tables) )
            {
                $err_str = 'No tables inside database!';
                TpDiagnostics::Append( DC_DATABASE_ERROR, $err_str, DIAG_ERROR );
	    }
            else 
            {
                $convert_case = false;

                foreach ( $tables as $table )
                {
                    if ( in_array( $table, $valid_tables ) )
                    {
                        $columns = $cn->MetaColumns( $table, $convert_case );

                        foreach ( $columns as $column )
                        {
                            array_push( $this->mTablesAndColumns, $table.'.'.$column->name );
                        }
                    }
		}
            }

            $r_data_source->ResetConnection();
        }
        else
        {
            // No need to raise errors (it happens inside "Validate")
        }

    } // end of member function LoadDatabaseMetadata

    function DisplayForm( ) 
    {
        $r_settings =& $this->mResource->GetSettings();

        include('TpSettingsForm.tmpl.php');

    } // end of member function DisplayForm

    function HandleEvents( ) 
    {
        $r_settings =& $this->mResource->GetSettings();

        // Clicked next or save
        if ( isset( $_REQUEST['next'] ) or isset( $_REQUEST['update'] ) or 
             isset( $_REQUEST['save'] ) ) 
        {
            if ( ! $r_settings->Validate() ) 
            {
                return;
            }

            if ( ! $this->mResource->SaveSettings() ) 
            {
                return;
            }

            // Clicked update
            if ( isset( $_REQUEST['update'] ) ) 
            {
                $this->SetMessage( 'Changes successfully saved!' );
            }

            $this->mDone = true;
        }
        // Clicked on button "set to now"for date last modified
        else if ( isset( $_REQUEST['set_modified'] ) ) 
        {
            $modified = TpUtils::TimestampToXsdDateTime( TpUtils::MicrotimeFloat() );

            $r_settings->SetModified( $modified );
        }

    } // end of member function HandleEvents

    function GetOptions( $id ) 
    {
        $options = array();

        if ( $id == 'logonly') 
        {
            $options = array( 'required' => 'required',
                              'accepted' => 'accepted',
                              'denied'   => 'denied' );
        }
        else if ( $id == 'boolean') 
        {
            $options = array( 'true'  => 'yes',
                              'false' => 'no' );
        }
        else if ( $id == 'tables_and_columns') 
        {
            $options = TpUtils::GetHash( $this->mTablesAndColumns );
            asort( $options );
            array_unshift( $options, '-- columns --' );
        }
        else if ( $id == 'custom_output_models') 
        {
            $options = array( 'true'  => 'accepted',
                              'false' => 'rejected' );
        }

        return $options;

    } // end of member function GetOptions

    function GetHtmlLabel( $labelId, $required ) 
    {
        $label = '?';
        $doc = '';

        if ( $labelId == 'max_repetitions') 
        {
            $label = 'Maximum element repetitions';
            $doc = 'Maximum element repetitions in search and inventory responses. '.
                   'This setting defines the maximum number of occurrences for any '.
                   'XML element inside the search or inventory envelope. You can '.
                   'see this as a way of limiting the number of records returned, '.
                   'forcing clients to page over results (instead of getting '.
                   'big XML responses with all records, clients will be forced to '.
                   'send individual requests to retrieve data in smaller parts).';
        }
        else if ( $labelId == 'max_levels') 
        {
            $label = 'Maximum element levels';
            $doc = 'Maximum element levels in search responses. '.
                   'This setting defines the maximum number of nested XML elements '.
                   'inside the search envelope. It can be used to prevent processing '.
                   'output models that are too deeply nested.';
        }
        else if ( $labelId == 'log_only') 
        {
            $label = 'Log only requests';
            $doc = 'Indicates if "log only" requests are desired, accepted or denied. '.
                   '"Log only" requests are used to propagate remote usage on top of '.
                   'data aggregators. Imagine that your data is copied and then served '.
                   'from a third-party web portal, so other users are searching and '.
                   'retrieving data that originally came from your database. "Log '.
                   'only" requests allow that portal to forward search requests to your '.
                   'provider just as a means of communicating what searches are being '.
                   'performed on your data. These kind of requests don\'t require the '.
                   'software to process the request as it would normally do, only log it. '.
                   'Setting this option to "denied" will make the provider respond with '.
                   'an error to "log only" requests. Setting to "desired" will enforce '.
                   'your wish to receive any searches performed on your data on top of '.
                   'third-party servers. "Accepted" means that you are not worried about '.
                   'receiving these kind of requests.';
        }
        else if ( $labelId == 'case_sensitive_equals') 
        {
            $label = 'Equals operators are case sensitive';
            $doc = 'Indicates if equals operators should be case sensitive or not.';
        }
        else if ( $labelId == 'case_sensitive_like') 
        {
            $label = 'Like operators are case sensitive';
            $doc = 'Indicates if like operators should be case sensitive or not.';
        }
        else if ( $labelId == 'date_last_modified') 
        {
            $label = 'Date last modified';
            $doc = 'Date and time when data was last modified. It can be either a '.
                   'timestamp field from where this value will be dynamically '.
                   'retrieved or a fixed value following the xsd:dateTime format: '.
                   '[-]CCYY-MM-DDThh:mm:ss[Z|(+|-)hh:mm]';
        }
        else if ( $labelId == 'inventory_templates') 
        {
            $label = 'Inventory templates';
            $doc = 'If you wish to explicitly declare that you support one or more '.
                   'specific inventory templates, indicate here alias and location '.
                   'for each one.';
        }
        else if ( $labelId == 'search_templates') 
        {
            $label = 'Search templates';
            $doc = 'If you wish to explicitly declare that you support one or more '.
                   'specific search templates, indicate here alias and location '.
                   'for each one.';
        }
        else if ( $labelId == 'pre_output_models') 
        {
            $label = 'Pre-defined output models';
            $doc = 'If you wish to explicitly declare that you support one or more '.
                   'specific output models, indicate here alias and location '.
                   'for each one.';
        }
        else if ( $labelId == 'custom_output_models') 
        {
            $label = 'Custom output models';
            $doc = 'Indicates whether custom (on-the-fly) output models '.
                   'based on the mapped concepts are accepted or not.';
        }

        $js = sprintf("onClick=\"javascript:window.open('help.php?name=%s&amp;doc=%s','help','width=400,height=250,menubar=no,toolbar=no,scrollbars=yes,resizable=yes,personalbar=no,locationbar=no,statusbar=no').focus(); return false;\" onMouseOver=\"javascript:window.status='%s'; return true;\" onMouseOut=\"window.status=''; return true;\"", $label, urlencode($doc), $doc);

        $form_label = $label;

        $html = sprintf('<a href="help.php?name=%s&amp;doc=%s" %s>%s: </a>',
                        $label, urlencode($doc), $js, $form_label);
        if ( $required )
        {
            $html = TP_MANDATORY_FIELD_FLAG.$html;
        }
        return $html;

    } // end of member function GetHtmlLabel

} // end of TpSettingsForm
?>