<?php
/**
 * $Id: TpTables.php 2001 2010-03-04 14:14:55Z rdg $
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

require_once('TpUtils.php');
require_once('TpDiagnostics.php');
require_once('TpTable.php');
require_once( TP_XPATH_LIBRARY );

class TpTables
{
    var $mRootTable;        // TpTable object
    var $mCurrentTable;     // Ancilary property when building table structure
    var $mTables = array(); // DEPRECATED: table name => TpTable object

    function TpTables( )
    {

    } // end of member function TpTables

    function IsLoaded( ) 
    {
        return is_object( $this->mRootTable );

    } // end of member function IsLoaded

    function LoadDefaults( ) 
    {
        $this->mRootTable = new TpTable();

    } // end of member function LoadDefaults

    function LoadFromSession( ) 
    {

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

        $path_to_table = '/configuration[1]/table[1]';

        // Second parameter should be empty (no header) 
        $xml = $xpr->exportAsXml( $path_to_table, '' );

        $this->LoadTables( $xml );

    } // end of member function LoadFromXml

    function GetXml( ) 
    {
        if ( is_object( $this->mRootTable ) )
        {
            return $this->mRootTable->GetXml();
        }

        return '';
        
    } // end of member function GetXml

    function GetRoot( ) 
    {
        if ( is_object( $this->mRootTable ) )
        {
            $table_name = $this->mRootTable->GetName();
            $table_key  = $this->mRootTable->GetKey();

            if ( $table_name and $table_key )
            {
                return $table_name.'.'.$table_key;
            }
        }

        return '';
        
    } // end of member function GetRoot

    function SetRootTable( $rootTable )
    {
        $this->mRootTable = $rootTable;
        
    } // end of member function SetRootTable

    function &GetRootTable( ) 
    {
        return $this->mRootTable;
        
    } // end of member function GetRootTable

    function IsEmpty( ) 
    {
        if ( ! is_object( $this->mRootTable ) )
        {
            return false;
        }

        $root_table_name = $this->mRootTable->GetName();

        return empty( $root_table_name );
        
    } // end of member function IsEmpty

    function LoadTables( $xml )
    {
        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_element_handler( $parser, 'StartElement', 'EndElement' );
        xml_set_character_data_handler( $parser, 'CharacterData' );

        if ( ! xml_parse( $parser, $xml, true ) ) 
        {
            $error = sprintf( "XML error: %s at line %d",
                              xml_error_string( xml_get_error_code( $parser ) ),
                              xml_get_current_line_number( $parser ) );

            TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_ERROR );
            return false;
        }

        xml_parser_free( $parser );

        return true;

    } // end of member function LoadTables

    function StartElement( $parser, $name, $attrs ) 
    {
        if ( strcasecmp( $name, 'table' ) == 0 )
        {
            $t_name = isset( $attrs['name'] ) ? $attrs['name'] : '';
            $key    = isset( $attrs['key']  ) ? $attrs['key'] : '';
            $join   = isset( $attrs['join'] ) ? $attrs['join'] : '';

            if ( strlen( $t_name ) > 0 )
            {
                $table = new TpTable();

                $table->SetName( $t_name );
                $table->SetKey( $key );

                if ( is_object( $this->mCurrentTable ) )
                {
                    $table->SetJoin( $join );

                    $this->mCurrentTable->AddChild( $table );
                }
                else
                {
                    $this->mRootTable =& $table;
                }

                $this->mTables[$t_name] =& $table;
                $this->mCurrentTable =& $table;
            }
        }

    } // end of member function StartElement

    function EndElement( $parser, $name ) 
    {
        if ( strcasecmp( $name, 'table' ) == 0 )
        {
            if ( is_object( $this->mCurrentTable ) )
            {
                $r_current_table =& $this->mTables[$this->mCurrentTable->GetName()];

                $this->mCurrentTable =& $r_current_table->GetParent();
            }
        }

    } // end of member function EndElement

    function CharacterData( $parser, $data ) 
    {

    } // end of member function CharacterData

    function GetStructure( ) 
    {
        return $this->mTables;
        
    } // end of member function GetStructure

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
      return array( 'mRootTable', 'mTables' );

    } // end of member function __sleep

} // end of TpTables
?>