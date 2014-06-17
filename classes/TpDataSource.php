<?php
/**
 * $Id: TpDataSource.php 439 2007-10-08 05:42:11Z rdg $
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

require_once('TpUtils.php');
require_once('TpDiagnostics.php');
require_once( TP_ADODB_LIBRARY );
require_once( TP_XPATH_LIBRARY );

class TpDataSource
{
    var $mDriverName;
    var $mEncoding;
    var $mConnectionString;
    var $mUserName;
    var $mPassword;
    var $mDatabaseName;
    var $mConnection;
    var $mIsLoaded;

    function TpDataSource( ) 
    {

    } // end of member function TpDataSource

    function IsLoaded( ) 
    {
        return $this->mIsLoaded;

    } // end of member function IsLoaded

    function LoadDefaults( ) 
    {
        $this->mDriverName       = '';
        $this->mEncoding         = 'ISO-8859-1';
        $this->mConnectionString = '';
        $this->mUserName         = '';
        $this->mPassword         = '';
        $this->mDatabaseName     = '';

        $this->mIsLoaded = true;

        $this->ResetConnection();

    } // end of member function LoadDefaults

    function LoadFromSession( ) 
    {
        $this->mDriverName       = TpUtils::GetVar( 'dbtype'  , '' );
        $this->mEncoding         = TpUtils::GetVar( 'encoding', '' );
        $this->mConnectionString = TpUtils::GetVar( 'constr'  , '' );
        $this->mUserName         = TpUtils::GetVar( 'uid'     , '' );
        $this->mPassword         = TpUtils::GetVar( 'pwd'     , '' );
        $this->mDatabaseName     = TpUtils::GetVar( 'database', '' );

        $this->mIsLoaded = true;

        $this->ResetConnection();

    } // end of member function LoadFromSession

    function LoadFromXml( $file, $xpr=false )
    {
       if ( ! is_object( $xpr ) ) 
        {
            $xpr = new XPath();
            $xpr->setVerbose( 1 );
            $xpr->setXmlOption( XML_OPTION_CASE_FOLDING, false );
            $xpr->setXmlOption( XML_OPTION_SKIP_WHITE, true );

            if ( ! $xpr->importFromFile( $file ) )
            {
                $error = 'Could not import content from XML file: '.$xpr->getLastError();
                TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
                return;
            }
        }
	  
        $path_to_datasource = '/configuration/datasource';

        $datasource_attrs = $xpr->getAttributes( $path_to_datasource );

        if ( count( $datasource_attrs ) )
        {
            $this->mDriverName       = $datasource_attrs['dbtype'];
            $this->mEncoding         = $datasource_attrs['encoding'];
            $this->mConnectionString = str_replace( array('&quot;', '&amp;'), 
                                                array('"'     , '&'), 
                                                $datasource_attrs['constr'] );
            $this->mUserName         = $datasource_attrs['uid'];
            $this->mPassword         = $datasource_attrs['pwd'];
            $this->mDatabaseName     = $datasource_attrs['database'];

            $this->mIsLoaded = true;
        }
        else
        {
            $error = 'Could not find data source attributes in configuration file';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return;
        }

        $this->ResetConnection();

    } // end of member function LoadFromXml

    function Validate( $raiseErrors=true )
    {
        $ret_val = true;

        // Try catch block
        do {

            if ( is_object( $this->mConnection ) ) 
            {
                // No need to continue if there is a valid connection
                break;
            }

            // Connection property is null here

            // First check that all necessary parameters are present
            if ( empty( $this->mDriverName ) or 
                (empty( $this->mConnectionString ) and empty( $this->mDatabaseName )) ) 
            {
                if ( $raiseErrors )
                {
                    $error = "Please, provide a minimum set of data to connect to ".
                             "a SQL database!\nIt won't be possible to continue ".
                             "without openning a connection.";
                    TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
                }
                $ret_val = false;
                break;
            }

            // Then try connecting to database
            $this->mConnection = &ADONewConnection( $this->mDriverName );

            if ( ! is_object( $this->mConnection ) ) 
            {
                if ( $raiseErrors )
                {
                    $error = "Could not create connection using database type '".
                             $this->mDriverName."'!";
                    TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
                }
                $ret_val = false;
                break;
            }

            // Xpath does not convert special chars back to their original form, so:
            $clean_constr = str_replace( array('&quot;', '&amp;'), 
                                         array('"'     , '&'), 
                                         $this->mConnectionString );

            $ret_val = $this->mConnection->PConnect( $clean_constr, 
                                                     $this->mUserName, 
                                                     $this->mPassword, 
                                                     $this->mDatabaseName );

            if ( ! $ret_val ) 
            {
                $error = "Could not open a database connection using these settings!\n";
                $ado_error = $this->mConnection->errorMsg();
		  
                if ( $ado_error ) 
                {
                    $error .= "ADODB: $ado_error";
                }

                if ( $raiseErrors )
                {
                    TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
                }

                $this->ResetConnection();
                break;
            }

            // Set the charPage attribute of the connection.
            // This only works for OLEDB based connections, but will not hurt
            // other types of connections
            $char_page = $this->_GetCodePage( $this->mEncoding );

            if ( ! is_null( $char_page ) )
            {
                $this->mConnection->charPage = $char_page;
            }

            $this->mConnection->fmtDate = $this->mConnection->fmtTimeStamp;

        } while ( false );

        return $ret_val;

    } // end of member function Validate

    function ResetConnection( ) 
    {
        if ( is_object( $this->mConnection ) ) 
        {
            $this->mConnection->Close();
        }

        $this->mConnection = NULL;

    } // end of member function ResetConnection

    function GetXml( ) 
    {
        $xml = '<datasource';

        $xml .= ' constr="'.TpUtils::EscapeXmlSpecialChars( $this->mConnectionString ).'"';
        $xml .= ' uid="'.TpUtils::EscapeXmlSpecialChars( $this->mUserName ).'"';
        $xml .= ' pwd="'.TpUtils::EscapeXmlSpecialChars( $this->mPassword ).'"';
        $xml .= ' database="'.TpUtils::EscapeXmlSpecialChars( $this->mDatabaseName ).'"';
        $xml .= ' dbtype="'.$this->mDriverName.'"';
        $xml .= ' encoding="'.$this->mEncoding.'"/>';

        return $xml;

    } // end of member function GetXml

    function SetDriverName( $driverName ) 
    {
        $this->mDriverName = $driverName;

    } // end of member function SetDriverName

    function GetDriverName( ) 
    {
        return $this->mDriverName;

    } // end of member function GetDriverName

    function SetEncoding( $encoding ) 
    {
        $this->mEncoding = $encoding;

    } // end of member function SetEncoding

    function GetEncoding( ) 
    {
        return $this->mEncoding;

    } // end of member function GetEncoding

    function SetConnectionString( $connectionString ) 
    {
        $this->mConnectionString = $connectionString;

    } // end of member function SetConnectionString

    function GetConnectionString( ) 
    {
        return $this->mConnectionString;

    } // end of member function GetConnectionString

    function SetDatabase( $databaseName ) 
    {
        $this->mDatabaseName = $databaseName;

    } // end of member function SetDatabase

    function GetDatabase( ) 
    {
        return $this->mDatabaseName;

    } // end of member function GetDatabase

    function SetUsername( $userName )
    {
        $this->mUserName = $userName;

    } // end of member function SetUsername

    function GetUsername( ) 
    {
        return $this->mUserName;

    } // end of member function GetUsername

    function SetPassword( $password ) 
    {
        $this->mPassword = $password;

    } // end of member function SetPassword

    function GetPassword( ) 
    {
        return $this->mPassword;

    } // end of member function GetPassword

    function GetConnection( ) 
    {
        // Validate should always be called before this method
        // to catch possible errors. But just in case, we call it
        // here too (but ignoring possible errors).
        $this->Validate();

        if ( $this->mConnection->IsConnected() )
        {
            return $this->mConnection;
        }

        return null;

    } // end of member function GetConnection

    /**
     * Returns a windows code page for the specified encoding parameter.
     * This is used to set the code page for connection to COM objects, such as
     * database connections using OLEDB.
     */
    function _GetCodePage( $encoding )
    {
        if ( strcasecmp( $encoding, 'UTF-8' ) === 0 )
        {
            return 65001;
        }

        return NULL;

    } // end of _GetCodePage

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
      $this->ResetConnection();

      return array( 'mDriverName', 'mEncoding', 'mConnectionString', 'mUserName',
                    'mPassword', 'mDatabaseName', 'mIsLoaded' );

    } // end of member function __sleep

} // end of TpDataSource
?>