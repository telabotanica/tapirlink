<?php
/**
 * $Id: TpDataSourceForm.php 2020 2010-08-28 23:06:46Z rdg $
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

class TpDataSourceForm extends TpWizardForm 
{
    var $mStep = 2;
    var $mLabel = 'Data source';
    var $mTemplate = '';
    var $mTemplateTitle = '';

    function TpDataSourceForm( ) 
    {

    } // end of member function TpDataSourceForm

    function LoadDefaults( ) 
    {
        $r_data_source =& $this->mResource->GetDataSource();

        if ( $this->mResource->ConfiguredDataSource() )
        {
            $r_data_source->LoadFromXml( $this->mResource->GetConfigFile() );
        }
        else
        {
            $this->SetMessage( "The next steps depend on an open connection with your database.\nPlease, provide the necessary information here.\n\nNote: if you get a blank page after clicking on 'next step'\nor PHP errors from ADOdb, you probably didn't install the corresponding\nPHP module to connect to a database using the selected driver." );

            $r_data_source->LoadDefaults();
        }

    } // end of member function LoadDefaults

    function LoadFromSession( ) 
    {
        $r_data_source =& $this->mResource->GetDataSource();
        $r_data_source->LoadFromSession();

    } // end of member function LoadFromSession

    function LoadFromXml( ) 
    {
        $r_data_source =& $this->mResource->GetDataSource();

        if ( $this->mResource->ConfiguredDataSource() ) 
        {
            $r_data_source->LoadFromXml( $this->mResource->GetConfigFile() );
        }
        else
        {
            $err_str = 'There is no data source XML configuration to be loaded!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $err_str, DIAG_ERROR );
            return;
        }

    } // end of member function LoadFromXml

    function DisplayForm( ) 
    {
        $r_data_source =& $this->mResource->GetDataSource();

        include('TpDataSourceForm.tmpl.php');

        $r_data_source->resetConnection();

    } // end of member function DisplayForm

    function HandleEvents( ) 
    {
        // Clicked next or save
        if ( isset( $_REQUEST['next'] ) or isset( $_REQUEST['update'] ) ) 
        {
            $r_data_source =& $this->mResource->GetDataSource();

            // Validate connection
            if ( ! $r_data_source->Validate() ) 
            {
                return;
            }

            if ( ! $this->mResource->SaveDataSource() ) 
            {
                // Error message is already set internally (GetConfigFile)
                $r_data_source->ResetConnection();
                return;
            }

            if ( isset( $_REQUEST['update'] ) ) 
            {
                $this->SetMessage( 'Changes successfully saved!' );
            }
	      
            $r_data_source->ResetConnection();

            $this->mDone = true;
        }
        // Simple refresh
        else if ( isset( $_REQUEST['refresh'] ) ) 
        {
            $r_data_source =& $this->mResource->GetDataSource();

            $templates = $this->GetOptions( 'adodbTemplates' );
            $drivers = $this->GetOptions( 'adodbDrivers' );

            $dbtype = $r_data_source->GetDriverName();

	    if ( isset( $templates[$dbtype] ) )
            {
                $this->mTemplate = urlencode( htmlspecialchars( $templates[$dbtype] ) );

                $this->mTemplateTitle = urlencode( htmlspecialchars( "Connection string template\n".$drivers[$dbtype] ) );
            }
            else 
            {
                $this->mTemplate = '';
                $this->mTemplateTitle = '';
            }
        }

    } // end of member function HandleEvents

    function GetOptions( $id ) 
    {
        $options = array();

        if ( $id == 'adodbDrivers') 
        {
            $options = array( '' => '-- Select --',
                              'ado' => 'ADO generic driver',
                              'db2' => 'DB2',
                              'odbc_db2' => 'DB2 using generic ODBC extension',
                              'firebird' => 'Firebird version of interbase',
                              'fbsql' => 'FrontBase',
                              'informix' => 'Informix generic driver',
                              'informix72' => 'Informix databases before version 7.3',
                              'borland_ibase' => 'Interbase 6.5 or later (Borland version)',
                              'ibase' => 'Interbase 6 or earlier',
                              'ldap' => 'LDAP',
                              'access' => 'Microsoft Access/Jet',
                              'ado_access' => 'Microsoft Access/Jet (using ADO)',
                              'ado_mssql' => 'Microsoft SQL Server (using ADO)',
                              'mssqlpo' => 'Microsoft SQL Server (portable driver)',
                              'mssql' => 'Microsoft SQL Server 7 and later',
                              'odbc_mssql' => 'Microsoft SQL Server (using ODBC)',
                              'mssql_n' => 'Microsoft SQL Server with auto-prepended "N" (correct unicode storage)',
                              'pdo_mssql' => 'Microsoft SQL Server PDO driver (only for PHP5)',
                              'vfp' => 'Microsoft Visual FoxPro',
                              'mysqlt' => 'MySQL with transaction support',
                              'mysql' => 'MySQL without transaction support',
                              'mysqli' => 'MySQL using newer PHP5 API',
                              'pdo_mysql' => 'MySQL PDO driver (only for PHP5)',
                              'netezza' => 'Netezza',
                              'odbtp' => 'Odbtp generic driver',
                              'odbtp_unicode' => 'Odbtp with unicode support',
                              'odbc' => 'ODBC generic driver',
                              'oci8' => 'Oracle 8/9',
                              'oci8po' => 'Oracle 8/9 portable driver',
                              'oci805' => 'Oracle 8.0.5',
                              'oracle' => 'Oracle 7 (old client API)',
                              'odbc_oracle' => 'Oracle (using ODBC)',
                              'pdo_oci' => 'Oracle PDO driver (only for PHP5)',
                              'pdo' => 'Generic PDO driver for PHP5',
                              'postgres' => 'PostgreSQL generic driver',
                              'postgres64' => 'PostgreSQL 6.4 and earlier',
                              'postgres7' => 'PostgreSQL 7',
                              'postgres8' => 'PostgreSQL 8',
                              'pdo_pgsql' => 'PostgreSQL PDO driver (only for PHP5)',
                              'sapdb' => 'SAP DB',
                              'sqlanywhere' => 'SQL Anywhere (Sybase)',
                              'sqlite' => 'SQLite',
                              'sqlitepo' => 'SQLite portable driver',
                              'sybase' => 'Sybase'
                            );

            if ( ! $this->mResource->IsNew() ) 
            {
                // Remove "-- Select --"
                array_shift( $options );
            }
        }
        else if ( $id == 'encodings') 
        {
            // TODO: figure out a way to get this list dynamically from php
            $fullListOfEncodings = 'UCS-4, UCS-4BE, UCS-4LE, UCS-2, UCS-2BE, UCS-2LE, UTF-32, UTF-32BE, UTF-32LE, UCS-2LE, UTF-16, UTF-16BE, UTF-16LE, UTF-8, UTF-7, ASCII, EUC-JP, SJIS, eucJP-win, SJIS-win, ISO-2022-JP, JIS, ISO-8859-1, ISO-8859-2, ISO-8859-3, ISO-8859-4, ISO-8859-5, ISO-8859-6, ISO-8859-7, ISO-8859-8, ISO-8859-9, ISO-8859-10, ISO-8859-13, ISO-8859-14, ISO-8859-15, byte2be, byte2le, byte4be, byte4le, BASE64, 7bit, 8bit, UTF7-IMAP, EUC-CN, CP936, HZ, EUC-TW, CP950, BIG-5, EUC-KR, UHC (CP949), ISO-2022-KR, Windows-1251 (CP1251), Windows-1252 (CP1252), CP866, KOI8-R';

            $tmp = explode( ', ', $fullListOfEncodings );
            sort( $tmp );

            $options = TpUtils::GetHash( $tmp );
        }
        else if ( $id == 'adodbTemplates') 
        {
            $options = array( 'ado_access' => 'Provider=Microsoft.JET.OLEDB.4.0;Data Source="c:\MyDirectory\MyDatabase.mdb"' );
        }

        return $options;

    } // end of member function GetOptions

} // end of TpDataSourceForm
?>