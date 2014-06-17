<?php
/**
 * $Id: TpTablesForm.php 573 2008-03-28 16:57:58Z rdg $
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
require_once('TpConfigUtils.php');
require_once('TpHtmlUtils.php');
require_once('TpDiagnostics.php');
require_once(TP_XPATH_LIBRARY);

class TpTablesForm extends TpWizardForm 
{
    var $mStep = 3;
    var $mLabel = 'Tables';
    var $mAllTablesAndColumns = array();  // array (table_name => array(column_name) )
    var $mXpr;                            // XML parser to original data
    var $mXpt;                            // XML parser to current data
    var $mDetectedInconsistency = false;

    function TpTablesForm( ) 
    {

    } // end of member function TpTablesForm

    function LoadDefaults( ) 
    {
        if ( $this->mResource->ConfiguredTables() )
        {
            $this->LoadFromXml();
        }
        else
        {
            $this->SetMessage( "At this point, you'll need to indicate the main table that will serve records to the network (root table).\nYou can optionally add links to other tables if the relationship between them is one-to-one.\nIf you are unsure about which parts of your database you will need to use, just choose the main table, complete the configuration process, and then refine the configuration later." );

            $r_data_source =& $this->mResource->GetDataSource();

            if ( ! $r_data_source->IsLoaded() )
            {
                $config_file = $this->mResource->GetConfigFile();

                $r_data_source->LoadFromXml( $config_file );

                // Update session data
                $r_resources =& TpResources::GetInstance();

                $r_resources->SaveOnSession();
            }

            $r_tables =& $this->mResource->GetTables();

            $r_tables->LoadDefaults();

            $this->LoadDatabaseMetadata(); 
        }

    } // end of member function LoadDefaults

    function LoadFromSession( ) 
    {
        $r_tables =& $this->mResource->GetTables();

        // Tables must already be stored in the session
        if ( ! $r_tables->GetXml() )
        {
            // If not, the only reason I can think of is that the session expired,
            // so load everything from XML
            $this->LoadFromXml();

            return;
        }

        // Note: there should be no need to load the datasource!
        // situation 1: "Restarting the wizard from this step"
        //              In this case LoadDefaults would be called first
        //              so the datasource properties would be in the resources
        //              session object.
        // situation 2: "Coming from the previous step"
        //              In this case the datasource properties would also be in 
        //              the resources session object already.
        // situation 3: "Session expired"
        //              In this case LoaFromXml would be called above. 

        $this->LoadDatabaseMetadata();

        $r_tables->LoadFromSession();

    } // end of member function LoadFromSession

    function LoadFromXml( ) 
    {
        if ( $this->mResource->ConfiguredTables() )
        {
            $config_file = $this->mResource->GetConfigFile();

            $xpr = $this->GetXmlParserForOriginalData( );

            if ( ! $xpr ) 
            {
                return;
            }

            // Load tables
            $r_tables =& $this->mResource->GetTables();

            $r_tables->LoadFromXml( $config_file, $xpr );

            // Load datasource
            $r_data_source =& $this->mResource->GetDataSource();

            $r_data_source->LoadFromXml( $config_file, $xpr );

            $this->LoadDatabaseMetadata();

            // Check that the root table exists in the db metadata
            $r_root_table =& $r_tables->GetRootTable();

            if ( is_object( $r_root_table ) ) 
            {
                $root_table_name = $r_root_table->GetName();

                $root_key = $r_root_table->GetKey();

                if ( ! isset( $this->mAllTablesAndColumns[$root_table_name] ) )
                {
                    $msg = 'The root table currently configured ('.$root_table_name.
                           ') does not exist in the database. '."\n".
                           'Please select another table and save the changes.';
                    TpDiagnostics::Append( CFG_INTERNAL_ERROR, $msg, DIAG_WARN );

                    $this->mDetectedInconsistency = true;
                }
                else if ( ! isset( $this->mAllTablesAndColumns[$root_table_name][$root_key] ) )
                {
                    $msg = 'The key field for the root table currently configured ('.
                           $root_key.") does not seem to exist in the table anymore. \n".
                           'Please select another combination root table/key field '.
                           'and save the changes.';
                    TpDiagnostics::Append( CFG_INTERNAL_ERROR, $msg, DIAG_WARN );

                    $this->mDetectedInconsistency = true;
                }
            }
        }
        else
        {
            $err_str = 'There is no XML configuration for tables to be loaded!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $err_str, DIAG_ERROR );
            return;
        }

    } // end of member function LoadFromXml

    function LoadDatabaseMetadata( ) 
    {
        $r_data_source =& $this->mResource->GetDataSource();

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

            if ( is_null( $tables ) or ! count( $tables) )
            {
                $err_str = 'No tables inside database!';
                TpDiagnostics::Append( DC_DATABASE_ERROR, $err_str, DIAG_ERROR );
	    }
            else 
            {
                $convert_case = false;

                foreach ( $tables as $table )
                {
                    $columns = $cn->MetaColumns( $table, $convert_case );

                    if ( is_array( $columns ) )
                    {
                        $names = array();

                        foreach ( $columns as $column )
                        {
                            $names[$column->name] = $column->name;
                        }

                        $this->mAllTablesAndColumns[$table] = $names;
                    }
		}

                if ( empty( $this->mAllTablesAndColumns ) )
                {
                    $err_str = 'No field names could be retrieved for all tables!';

                    if ( $r_data_source->GetDriverName() == 'ado_access' )
                    {
                        $err_str .= ' It is possible that your access database has one or more broken links to external tables. Please, check it.';
                    }
                    else
                    {
                        // What should we do here? 
                    }

                    TpDiagnostics::Append( DC_DATABASE_ERROR, $err_str, DIAG_ERROR );
                }
            }

            $r_data_source->ResetConnection();
        }
        else
        {
            // No need to raise errors (it happens inside "Validate")
        }

    } // end of member function LoadDatabaseMetadata

    function GetRoot( ) 
    {
        $r_tables =& $this->mResource->GetTables();

        return $r_tables->GetRoot();

    } // end of member function GetRoot

    function GetRootTable( ) 
    {
        $r_tables =& $this->mResource->GetTables();

        $root = $r_tables->GetRootTable();

        if ( is_object( $root ) ) 
        {
            return $root;
        }

        return null;

    } // end of member function GetRootTable

    function GetXmlParserForOriginalData( ) 
    {
        if ( ! is_object( $this->mXpr ) )
        {
            $this->mXpr = new XPath();
            $this->mXpr->setVerbose( 1 );
            $this->mXpr->setXmlOption( XML_OPTION_CASE_FOLDING, false );
            $this->mXpr->setXmlOption( XML_OPTION_SKIP_WHITE, true );

            if ( ! $this->mXpr->importFromFile( $this->mResource->GetConfigFile() ) )
            {
                $error = 'Could not import content from XML file: '.
                         $this->mXpr->getLastError();
                TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
                return;
            }
        }

        return $this->mXpr;

    } // end of member function GetXmlParserForOriginalData

    function DisplayForm( ) 
    {
        $r_data_source =& $this->mResource->GetDataSource();

        include('TpTablesForm.tmpl.php');

        $r_data_source->resetConnection();

    } // end of member function DisplayForm

    function HandleEvents( ) 
    {
        $r_tables =& $this->mResource->GetTables();

        // Clicked save or next
        if ( isset( $_REQUEST['update'] ) or isset( $_REQUEST['next'] ) )
        {
            if ( ! $r_tables->GetRoot() )
            {
                $error = 'Please, select at least a root table and its key field!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
                return;
            }

            if ( TpUtils::GetVar( 'from' ) <> '0' and 
                 TpUtils::GetVar( 'to' ) <> '0' )
            {
                $error = 'The new join you have just selected will not be '.
                         'automatically added.'."\n".'Please, click on the '.
                         '"add" button or unselect the values before '.
                         'jumping to the next step.';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
                return;
            }

            // Save changes

            if ( ! $this->mResource->SaveTables() ) 
            {
                return;
            }

            $this->mDone = true;

            $this->SetMessage( 'Changes successfully saved!' );
        }

        // Changed root table/key
        elseif ( TpUtils::GetVar( 'refresh', '' ) == 'root' ) 
        {
            $root = TpUtils::GetVar( 'root' );

            $r_root_table =& $r_tables->GetRootTable();

            if ( ! empty( $root ) )
            {
                $parts = explode( '.', $root );

                $new_table_name = $parts[0];
                $new_key_name   = $parts[1];

                $r_root_table->SetName( $new_table_name );
                $r_root_table->SetKey( $new_key_name );
            }
        }

        // Removed join
        elseif ( isset( $_REQUEST['remove'] ) )
        {
            $path_to_remove = TpUtils::GetVar( 'refresh' );

            $r_table =& $r_tables->GetRootTable();

            $table_stack = explode( '/', $path_to_remove );

            array_shift( $table_stack ); // remove root table

            while ( count( $table_stack ) )
            {
                $table_name = array_shift( $table_stack );

                if ( count( $table_stack ) == 0 )
                {
                    if ( ! $r_table->RemoveChild( $table_name ) )
                    {
                        return;
                    }
                }
                else
                {
                    $parent_table_name = $r_table->GetName();

                    $r_table =& $r_table->GetChild( $table_name );

                    if ( $r_table == null )
                    {
                        return;
                    }
                }
            }
        }

        // Added join
        elseif ( isset( $_REQUEST['addjoin'] ) or 
                 TpUtils::GetVar('refresh', '') == 'addjoin' )
        {
            $from_table_plus_field = TpUtils::GetVar('from');
            $to_table_plus_field = TpUtils::GetVar('to');

            if ( ! $from_table_plus_field or ! $to_table_plus_field )
            {
                $err_str = 'Please select a value from both combos before adding!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $err_str, DIAG_ERROR );
                return;
            }

            $from_table = substr( $from_table_plus_field, 0, 
                                  strpos( $from_table_plus_field, '.' ) );

            $from_field = substr( $from_table_plus_field, 
                                  strpos( $from_table_plus_field, '.' ) + 1 );

            $to_table = substr( $to_table_plus_field, 0, 
                                strpos( $to_table_plus_field, '.' ) );

            $to_field = substr( $to_table_plus_field, 
                                strpos( $to_table_plus_field, '.' )+ 1 );

            $new_table = new TpTable();
            $new_table->SetName( $to_table );
            $new_table->SetKey( $to_field );
            $new_table->SetJoin( $from_field );

            $r_root_table =& $r_tables->GetRootTable();

            $r_parent_table =& $r_root_table->Find( $from_table );

            if ( $r_parent_table == null )
            {
                $msg = 'Could not find "'.$from_table.'" in the tables data structure';
                TpDiagnostics::Append( CFG_INTERNAL_ERROR, $msg, DIAG_ERROR );

                return;
            }

            $r_parent_table->AddChild( $new_table );

            unset( $_REQUEST['from'] );
            unset( $_REQUEST['to'] );
        }

    } // end of member function HandleEvents

    function GetOptions( $id ) 
    {
        $options = array();

        if ( $id == 'AllTablesAndColumns') 
        {
            foreach ( $this->mAllTablesAndColumns as $table => $columns )
            {
                if ( is_array( $columns ) )
                {
                    foreach ( $columns as $column )
                    {
                        array_push( $options, $table . '.' . $column );
                    }
                }
            }

            $options = TpUtils::GetHash( $options );
            asort( $options );

            if ( $this->mResource->IsNew() or $this->mDetectedInconsistency )
            {
               array_unshift( $options, '-- select --' );
            }
        }
        elseif ( $id == 'TablesAndColumnsInside') 
        {
            $r_tables =& $this->mResource->GetTables();

            $r_root_table =& $r_tables->GetRootTable();

            $tables_inside = $r_root_table->GetAllTables();

            foreach ( $this->mAllTablesAndColumns as $table => $columns )
            {
                if ( is_array( $columns ) )
                {
                    foreach ( $columns as $column )
                    {
                        if ( in_array( $table, $tables_inside ) )
                        {
                            array_push( $options, $table . '.' . $column );
                        }
                    }
                }
            }

            $options = TpUtils::GetHash( $options );
            asort( $options );

            array_unshift( $options , '-- between --' );
        }
        elseif ( $id == 'TablesAndColumnsOutside') 
        {
            $all = array_keys( $this->GetOptions('AllTablesAndColumns') );

            $inside = array_keys( $this->GetOptions('TablesAndColumnsInside') );

            $options = array_diff( $all, $inside );

            $options = TpUtils::GetHash( $options );
            asort( $options );

            array_unshift( $options, '-- and --' );
        }

        return $options;

    } // end of member function GetOptions

    /** Get HTML representation of table joins inside a specified table.
     *  
     * @param string parentTable TpTable object
     *
     * @return string HTML representing all joins with the specified table
     */
    function GetJoins( $parentTable )
    {
        $out = '';

        $level = $parentTable->GetLevel() -1;

        $parent_table_name = $parentTable->GetName();

        $joins = $parentTable->GetChildren();

        $detected_error_in_join = false;

        foreach ( $joins as $table_name => $table )
        {
            $key  = $table->GetKey();
            $join = $table->GetJoin();
            $path = $table->GetPath();

            if ( ! isset( $this->mAllTablesAndColumns[$table_name] ) )
            {
                $msg = 'Table "'.$table_name.'" is still part of the configuration'."\n".
                       'but is not shown in a join below because it does not '."\n".
                       'exist in the database. Please make any necessary updates '."\n".
                       'in this form and save the changes.';
                TpDiagnostics::Append( CFG_INTERNAL_ERROR, $msg, DIAG_WARN );

                continue;
            }
            else if ( ! isset( $this->mAllTablesAndColumns[$table_name][$key] ) )
            {
                $msg = 'Field "'.$table_name.'.'.$key.'" is still part of the '.
                       'configuration'."\n".
                       'but is not shown in a join below because it does not '."\n".
                       'exist in the database. Please make any necessary updates '."\n".
                       'in this form and save the changes.';
                TpDiagnostics::Append( CFG_INTERNAL_ERROR, $msg, DIAG_WARN );

                continue;
            }
            else if ( ( ! $detected_error_in_join ) and 
                      ! isset( $this->mAllTablesAndColumns[$parent_table_name][$join] ) )
            {
                $msg = 'Field "'.$parent_table_name.'.'.$join.'" is still part of the '.
                       'configuration'."\n".
                       'but is not shown in a join below because it does not '."\n".
                       'exist in the database. Please make any necessary updates '."\n".
                       'in this form and save the changes.';
                TpDiagnostics::Append( CFG_INTERNAL_ERROR, $msg, DIAG_WARN );

                $detected_error_in_join = true;

                continue;
            }

            $indent = str_repeat( '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $level );

            $remove_button = '&nbsp;<input type="submit" name="remove" value="remove join" onClick="document.wizard.refresh.value=\''.$path.'\';document.wizard.submit();"><br/>';

            $tree_symbol = '-';

            $out .= "\n".'           <br/><span class="label">'.$indent.
                    ' <span class="text">'.$tree_symbol.'</span> join with </span>'.
                    '<span class="text">'.$table_name.'</span>'.
                    '<span class="label"> when </span>'.
                    '<span class="text">'.$parent_table_name.'.'.$join.'</span>'.
                    '<span class="label"> equals </span>'.
                    '<span class="text">'.$table_name.'.'.$key.'</span>'.
                    $remove_button;

            $out .= $this->GetJoins( $table );
        }

        return $out;

    } // end of GetJoins

} // end of TpTablesForm
?>