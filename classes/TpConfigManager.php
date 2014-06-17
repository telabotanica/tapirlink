<?php
/**
 * $Id: TpConfigManager.php 1994 2009-08-26 20:49:04Z rdg $
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

require_once('TpConfigUtils.php');
require_once('TpUtils.php');
require_once('TpResources.php');
require_once('TpPage.php');
require_once('TpWizardForm.php');

class TpConfigManager
{
    function TpConfigManager( )
    {

    } // end of member function TpConfigManager

    function CheckEnvironment( )
    {
        global $g_dlog;

        $g_dlog->debug('Checking environment');

        // The same browser can be used with different TapirLink installations,
        // so session data should distinguish between these
        $instance_id = TpConfigUtils::GetServiceId();

        session_name( $instance_id );

        // It's important to start the session here (and not on configurator.php)
        // because there are objects being stored in the session, and they can
        // only be reloaded when the session is started after importing all necessary
        // class definitions.
        // Also, from the PHP documentation: "If you are using cookie-based sessions, 
        // you must call session_start() before anything is outputted to the browser".
        session_start();

        // Do nothing if environment was already checked and if 
        // "force_reload" is not present
        if ( isset( $_SESSION['envOk'] ) and ! isset( $_REQUEST['force_reload'] ) )
        {
            $g_dlog->debug('Environment was already checked');
            return true;
        }

        // Check PHP version
        $current_version = phpversion();

        if ( version_compare( $current_version, '5.0', '<' ) > 0 )
        {
            if ( version_compare( $current_version, TP_MIN_PHP_VERSION, '<' ) > 0 )
            {
                $msg = 'PHP Version '.TP_MIN_PHP_VERSION.' or later required. '.
                       'Some features may not be available. Detected version '.
                       $current_version;
                TpDiagnostics::Append( DC_VERSION_MISMATCH, $msg, DIAG_WARN );
            }
        }
        else if ( version_compare( $current_version, '6.0', '<' ) > 0 )
        {
            if ( version_compare( $current_version, '5.0.3', '<' ) > 0 )
            {
                // Avoid bug in "xml_set_start_namespace_decl_handler"
                $msg = 'Provider error: Unsupported PHP version ('.$current_version.
                       '). To use PHP5 it is necessary to have at least version 5.0.3';

                TpDiagnostics::Append( DC_VERSION_MISMATCH, $msg, DIAG_FATAL );
            }
        }

        if ( version_compare( phpversion(), TP_MIN_PHP_VERSION, '<' ) > 0 )
        {
            $msg = 'PHP Version '.TP_MIN_PHP_VERSION.' or later is recommended to '.
                   'run a provider (detected version '.phpversion().')';

            TpDiagnostics::Append( DC_VERSION_MISMATCH, $msg, DIAG_WARN );
        }

        // Check mbstring
        if ( ! TpUtils::LoadLibrary( 'mbstring' ) )
        {
            $msg = 'Could not load the PHP "mbstring" extension. It is highly '.
                   'recommended to use it, so you may want to check your '.
                   'PHP configuration before proceeding';

            TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, $msg, DIAG_FATAL );
        }

        // Check Session control

        $session_auto_start = ini_get('session.auto_start');

        if ( $session_auto_start ) 
        {
            $error_msg = 'The PHP environment variable "session.auto_start" is '.
                         'set to 1. You must disable this option before using the '.
                         'web configurator. Please change that setting in your '.
                         'PHP configuration file.';

            TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, $error_msg, DIAG_FATAL );
        }

        // Check that session is working
        
        session_destroy();
        session_start();
        $_SESSION['test'] = '1';
        session_write_close();
        $_SESSION = array();
        session_start();
        
        if ( ! isset( $_SESSION['test'] ) )
        {
            $error_msg = 'PHP session control is not working properly (please check '.
                         'your PHP configuration file if session support is enabled, '.
                         'and also if session.save_path exists and is writable by '.
                         'the Web Server)';
  
            TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, $error_msg, DIAG_FATAL );
        }
        
        unset( $_SESSION['test'] );
        
        // Check config directory permissions

        $config_dir = TP_CONFIG_DIR;

        $msg = 'Configuration directory ('.$config_dir.') ';

        $config_dir_is_writable = false;

        if ( empty( $config_dir ) )
        {
            $msg .= 'is not defined';
            TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, $msg, DIAG_FATAL );
        }
        else if ( ! file_exists( $config_dir ) )
        {
            $msg .= 'does not exist';
            TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, $msg, DIAG_FATAL );
        }
        else if ( ! is_readable( $config_dir ) )
        {
            $msg .= 'is not readable';
            TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, $msg, DIAG_FATAL );
        }
        else if ( ! is_writable( $config_dir ) )
        {
            $msg .= 'is not writable';
            TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, $msg, DIAG_FATAL );
        }
        else
        {
            $config_dir_is_writable = true;
        }

        $res_file = $config_dir.'/'.TP_RESOURCES_FILE;

        $msg = 'Resources file ('.$res_file.') ';

        if ( empty( $res_file ) )
        {
            $msg .= 'is not defined';
            TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, $msg, DIAG_FATAL );
        }
        else 
        {
            $res_file_exists = false;

            if ( ! file_exists( $res_file ) )
            {
                if ( $config_dir_is_writable )
                {
                    $f_handle = @fopen( $res_file, 'w' );

                    if ( $f_handle )
                    {
                        if ( fwrite( $f_handle, '<resources/>' ) === FALSE )
                        {
                            $msg .= 'could not be prepared with initial content';
                            TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, $msg, DIAG_FATAL );
                        }
                        else
                        {
                            $res_file_exists = true;
                        }

                        fclose( $f_handle );
                    }
                    else
                    {
                        $msg .= 'could not be created';
                        TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, $msg, DIAG_FATAL );
                    }
                }
            }
            else
            {
                $res_file_exists = true;
            }

            if ( $res_file_exists )
            {
                if ( ! is_readable( $res_file ) )
                {
                    $msg .= 'is not readable';
                    TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, $msg, DIAG_FATAL );
                }
                else if ( ! is_writable( $res_file ) )
                {
                    $msg .= 'is not writable';
                    TpDiagnostics::Append( DC_SERVER_SETUP_ERROR, $msg, DIAG_FATAL );
                }
            }
        }

        // Summing up
        if ( ! TpDiagnostics::Count() )
        {
            // The same browser can be used with different TapirLink installations,
            // so session data should distinguish between these
            $_SESSION['envOk'] = 1;
            return true;
        }
        else
        {
            return false;
        }

    } // end of member function CheckEnvironment

    function HandleEvents( )
    {
        global $g_dlog;

        $g_dlog->debug('Handling events');

        // Get resources anyway (need to display them on the main panel)
        $r_resources =& TpResources::GetInstance();

        // Clicked on "add resource"
        if ( isset( $_REQUEST['add_resource'] ) )
        {
            $page = $this->GetWizardPage( 1 );

            $page->Initialize( new TpResource() );
        }
        // If a resource parameter was specified
        else if ( isset( $_REQUEST['resource'] ) )
        {
            $passed_resource = $_REQUEST['resource'];

            // Before going through the first step, resource is empty.
            // Need to instantiate a new object in this case.
            if ( strlen( $passed_resource ) == 0 ) 
            {
                $r_resource = new TpResource();
            }
            // If resource code has content, check it
            else
            {
                $r_resource =& $r_resources->GetResource( $passed_resource );
            }

            // Resource not found!
            if ( strlen( $passed_resource ) > 0 and $r_resource == null )
            {
                $error = TpDiagnostics::PopDiagnostic();

                $page = new TpPage();
                $page->SetMessage( $error->GetDescription() );
            }
            // Resource found or empty (new)
            else 
            {
                // If a form (step) was specified
                if ( isset( $_REQUEST['form'] ) ) 
                {
                    $form_id = $_REQUEST['form'];

                    $page = $this->GetWizardPage( $form_id );

                    $page->Initialize( $r_resource );

                    if ( $_SERVER['REQUEST_METHOD'] == 'POST' )
                    {
                        // Aborted
                        if ( isset( $_REQUEST['abort'] ) )
                        {
                            if ( $r_resources->RemoveResource( $passed_resource ) )
                            {
                                $page = new TpPage();
                                $page->SetMessage( 'Resource successfully removed!' );
                            }
                        }
                        // Did not abort
                        else
                        {
                            // Need to call IsNew before HandleEvents, because
                            // it may not be new anymore after that...
                            $is_new = $r_resource->IsNew();

                            $page->HandleEvents();

                            if ( $page->Done() )
                            {
                                TpDiagnostics::Reset();

                                // New resource
                                if ( $is_new ) 
                                {
                                    $step = $form_id;

                                    // Are there any steps left?
                                    if ( $page->GetNumSteps() > $step )
                                    {
                                        $page = $this->GetWizardPage( $step + 1 );

                                        $page->Initialize( $r_resource );
                                    }
                                    // Last step!
                                    else
                                    {
                                        $page = new TpPage();
                                        $page->SetMessage( 'New resource successfully saved!' );
                                    }
                                }
                            }
                        }
                    }
                    else
                    {
                        // If not done yet, just show page again in the end of this method
                    }
                }
                // No form specified
                else 
                {
                    // New resource
                    if ( $r_resource->IsNew() ) 
                    {
                        // Find the step to continue the wizard
                        // No processing required
                        if ( ! $r_resource->ConfiguredMetadata() )
                        {
                            // Should never fall here, but anyway...
                            $page = $this->GetWizardPage( 1 );
                        }
                        else if ( ! $r_resource->ConfiguredDatasource() )
                        {
                            $page = $this->GetWizardPage( 2 );
                        }
                        else if ( ! $r_resource->ConfiguredTables() )
                        {
                            $page = $this->GetWizardPage( 3 );
                        }
                        else if ( ! $r_resource->ConfiguredLocalFilter() )
                        {
                            $page = $this->GetWizardPage( 4 );
                        }
                        else if ( ! $r_resource->ConfiguredMapping() )
                        {
                            $page = $this->GetWizardPage( 5 );
                        }
                        else if ( ! $r_resource->ConfiguredSettings() )
                        {
                            $page = $this->GetWizardPage( 6 );
                        }
                        else
                        {
                            // Apparently configured everything!
                            // Go to last step....
                            $page = $this->GetWizardPage( $page->GetNumSteps() );
                        }

                        $page->Initialize( $r_resource );
                    }
                    // Existing resource
                    else 
                    {
                        require_once('TpResourceForm.php');

                        $page = new TpResourceForm( $r_resource );

                        if ( $_SERVER['REQUEST_METHOD'] == 'POST' )
                        {
                            $page->HandleEvents();

                            if ( $page->RemovedResource() )
                            {
                                // Overwrite page with a new one not involving any
                                // particular resource and display message
                                $page = new TpPage();
                                $page->SetMessage( 'Resource successfully removed!' );
                            }
                        }
                    }
                }
            }
        }
        else if ( isset( $_REQUEST['uddi'] ) )
        {
            require_once('TpUddiForm.php');

            $page = new TpUddiForm();

            // In this case, processing happens inside DisplayHtml because it can  
            // take a long time for several resources, so better flush as much as
            // possible after each resource is processed
        }
        else if ( isset( $_REQUEST['import'] ) )
        {
            require_once('TpImportForm.php');

            $page = new TpImportForm();

            if ( $_SERVER['REQUEST_METHOD'] == 'POST' )
            {
                 $page->HandleEvents();
            }
        }
        // No resource and no action involved
        else
        {
            // Empty page
            $page = new TpPage();
        }

        $resources_list = $r_resources->GetAllResources();

        flush();
        session_write_close();

        // Display main page - which always needs a Page object in variable $page!
        include('main_panel.tmpl.php');

    } // end of member function HandleEvents

    function GetWizardPage( $step )
    {
        if ( $step == 1 )
        {
            require_once('TpMetadataForm.php');

            $page = new TpMetadataForm();
        }
        else if ( $step == 2 )
        {
            require_once('TpDataSourceForm.php');

            $page = new TpDataSourceForm();
        }
        else if ( $step == 3 )
        {
            require_once('TpTablesForm.php');

            $page = new TpTablesForm();
        }
        else if ( $step == 4 )
        {
            require_once('TpLocalFilterForm.php');

            $page = new TpLocalFilterForm();
        }
        else if ( $step == 5 )
        {
            require_once('TpMappingForm.php');

            $page = new TpMappingForm();
        }
        else if ( $step == 6 )
        {
            require_once('TpSettingsForm.php');

            $page = new TpSettingsForm();
        }
        else 
        {
            // Display a form with an error message
            $page = new TpWizardForm();
            $error = 'Unknown wizard step!';
            TpDiagnostics::Append( DC_GENERAL_ERROR, $error, DIAG_ERROR );
        }

        return $page;

    } // end of member function GetWizardPage

    function GetNumSteps( )
    {
        $page = new TpWizardForm();

        return $page->GetNumSteps();

    } // end of member function GetNumSteps

} // end of Entity
?>