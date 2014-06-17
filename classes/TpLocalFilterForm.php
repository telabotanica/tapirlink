<?php
/**
 * $Id: TpLocalFilterForm.php 596 2008-04-04 21:56:12Z rdg $
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
require_once('TpFilter.php');

require_once(TP_XPATH_LIBRARY);

class TpLocalFilterForm extends TpWizardForm 
{
    var $mStep = 4;
    var $mLabel = 'Local filter';
    var $mXpr;        // XML parser to original data
    var $mXpt;        // XML parser to current data
    var $mTablesAndColumns = array(); // array (table_name => array(column obj) )
    var $mHtml = ''; // this property was created to call FilterToHtml::GetHtml 
                     // before DisplayForm (otherwise some errors will not be displayed)

    function TpLocalFilterForm( )
    {

    } // end of member function TpLocalFilterForm

    function LoadDefaults( ) 
    {
        if ( $this->mResource->ConfiguredLocalFilter() )
        {
            $this->LoadFromXml();
        }
        else
        {
            $this->SetMessage( "Here you can optionally set a local filter to select which records you want to provide." );

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

            $this->LoadDatabaseMetadata(); 

            // Default filter
            $r_local_filter =& $this->mResource->GetLocalFilter();

            $r_local_filter->LoadDefaults();

            $this->mHtml = $r_local_filter->GetHtml( $this->mTablesAndColumns );
        }

    } // end of member function LoadDefaults

    function LoadFromSession( ) 
    {
        $r_local_filter =& $this->mResource->GetLocalFilter();

        // Local filter must already be stored in the session
        if ( ! $r_local_filter->IsLoaded() )
        {
            // If not, the only reason I can think of is that the session expired,
            // so load everything from XML
            $this->LoadFromXml();

            return;
        }

        // Note: there should be no need to load datasource and tables!
        // situation 1: "Restarting the wizard from this step"
        //              In this case LoadDefaults would be called first
        //              so the datasource properties would be in the resources
        //              session object.
        // situation 2: "Coming from the previous step"
        //              In this case the datasource properties would also be in 
        //              the resources session object already.
        // situation 3: "Session expired"
        //              In this case LoaFromXml would be called above. 

        $r_local_filter->LoadFromSession();

        $r_tables =& $this->mResource->GetTables();

        // Get available tables/columns
        $this->LoadDatabaseMetadata();

        $this->mHtml = $r_local_filter->GetHtml( $this->mTablesAndColumns );

    } // end of member function LoadFromSession

    function LoadFromXml( ) 
    {
        if ( $this->mResource->ConfiguredLocalFilter() )
        {
            $config_file = $this->mResource->GetConfigFile();

            $xpr = $this->GetXmlParserForOriginalData( );

            if ( ! $xpr ) 
            {
                return;
            }

            // Load datasource
            $r_data_source =& $this->mResource->GetDataSource();

            $r_data_source->LoadFromXml( $config_file, $xpr );

            // Load tables
            $r_tables =& $this->mResource->GetTables();

            $r_tables->LoadFromXml( $config_file, $xpr );

            $this->LoadDatabaseMetadata();

            // Load filter
            $r_local_filter =& $this->mResource->GetLocalFilter();

            $r_local_filter->LoadFromXml( $config_file, $xpr );

            $this->mHtml = $r_local_filter->GetHtml( $this->mTablesAndColumns );
        }
        else
        {
            $err_str = 'There is no local filter XML configuration to be loaded!';
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

            $valid_tables = $cn->MetaTables();

            // Get tables involved

            $r_tables =& $this->mResource->GetTables();

            $root_table = $r_tables->GetRootTable();

            $tables = $root_table->GetAllTables();

            $convert_case = false;

            foreach ( $tables as $table )
            {
                $columns = $cn->MetaColumns( $table, $convert_case );

                $this->mTablesAndColumns[$table] = TpUtils::FixAdodbColumnsArray( $columns );

                if ( ! in_array( $table, $valid_tables ) )
                {
                    $msg = 'Table "'.$table.'" is referenced by the "Tables" section '.
                           'but does not exist in the database.';
                    TpDiagnostics::Append( CFG_INTERNAL_ERROR, $msg, DIAG_ERROR );
                }
            }

            $r_data_source->ResetConnection();
        }
        else
        {
            // No need to raise errors (it happens inside "Validate")
        }

    } // end of member function LoadDatabaseMetadata

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
        $html  = "\n<!-- beginning of LocalFilterForm -->";
        $html .= "\n<br/>";
        $html .= $this->mHtml;
        $html .= "\n<br/>";
        $html .= "\n<!-- end of LocalFilterForm -->";

        echo $html;

    } // end of member function DisplayForm

    function HandleEvents( ) 
    {
        $r_local_filter =& $this->mResource->GetLocalFilter();

        $r_local_filter->Refresh( $this->mTablesAndColumns );

        if ( isset( $_REQUEST['remove'] ) )
        {
            $op_location = TpUtils::GetVar( 'refresh' );

            $r_local_filter->Remove( $op_location );
        }
        else if ( isset( $_REQUEST['add_cop'] ) )
        {
            $op_location = TpUtils::GetVar( 'refresh' );

            $r_local_filter->AddOperator( $op_location, COP_TYPE, COP_EQUALS );
        }
        else if ( isset( $_REQUEST['add_multi_lop'] ) )
        {
            $op_location = TpUtils::GetVar( 'refresh' );

            $r_local_filter->AddOperator( $op_location, LOP_TYPE, LOP_AND );
        }
        else if ( isset( $_REQUEST['add_not_lop'] ) )
        {
            $op_location = TpUtils::GetVar( 'refresh' );

            $r_local_filter->AddOperator( $op_location, LOP_TYPE, LOP_NOT );
        }
        else if ( substr( TpUtils::GetVar( 'refresh' ), -10 ) == '_lopchange' )
        {
	        $refresh = explode( '_', TpUtils::GetVar( 'refresh' ) );

            $op_location = $refresh[0];

            $r_lop =& $r_local_filter->Find( $op_location );

            if ( $r_lop != null )
            {
                $operators = $r_lop->GetBooleanOperators(); // need a copy here!

                $num_operators = count( $operators );

                // Try to split the operator
                if ( $num_operators > 2 )
                {
                    $r_lop->ResetBooleanOperators();

                    $cut_point = $refresh[1];

                    $num_operators_before_cut = $cut_point + 1;

                    $new_type = $r_lop->GetLogicalType();

                    $old_type = ( $new_type == LOP_AND ) ? LOP_OR :LOP_AND;

                    if ( $num_operators_before_cut > 1 )
                    {
                        $upper_lop = new TpLogicalOperator( $old_type );

                        for ( $i = 0; $i < $num_operators_before_cut; ++$i )
                        {
                            $upper_lop->AddBooleanOperator( $operators[$i] );
                        }
                        $r_lop->AddBooleanOperator( $upper_lop );
                    }
                    else
                    {
                        $r_lop->AddBooleanOperator( $operators[0] );
                    }

                    $num_operators_after_cut = $num_operators - $num_operators_before_cut;

                    if ( $num_operators_after_cut > 1 )
                    {
                        $bottom_lop = new TpLogicalOperator( $old_type );

                        for ( $i = $num_operators_before_cut; $i < $num_operators; ++$i )
                        {
                            $bottom_lop->AddBooleanOperator( $operators[$i] );
                        }
                        $r_lop->AddBooleanOperator( $bottom_lop );
                    }
                    else
                    {
                        $r_lop->AddBooleanOperator( $operators[$num_operators-1] );
                    }
                }
            }
        }
        elseif ( isset( $_REQUEST['update'] ) or isset( $_REQUEST['next'] ) )
        {
            $force = true;

            if ( ! $r_local_filter->IsValid( $force ) )
            {
                $this->mHtml = $r_local_filter->GetHtml( $this->mTablesAndColumns );

                return;
            }

            if ( ! $this->mResource->SaveLocalFilter() ) 
            {
                $this->mHtml = $r_local_filter->GetHtml( $this->mTablesAndColumns );

                return;
            }

            if ( isset( $_REQUEST['update'] ) )
            {
                $this->SetMessage( 'Changes successfully saved!' );
            }

            $this->mResource->UpdateResources();

            $this->mDone = true;
        }

        $this->mHtml = $r_local_filter->GetHtml( $this->mTablesAndColumns );

    } // end of member function HandleEvents

} // end of TpLocalFilterForm
?>