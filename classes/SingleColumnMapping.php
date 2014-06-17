<?php
/**
 * $Id: SingleColumnMapping.php 648 2008-04-23 18:51:54Z rdg $
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

require_once('TpConceptMapping.php');
require_once('TpDiagnostics.php');
require_once('TpUtils.php');
require_once('TpConfigUtils.php');
require_once('TpSqlBuilder.php');

class SingleColumnMapping extends TpConceptMapping
{
    var $mMappingType = 'SingleColumnMapping';
    var $mTablesAndColumns = array();  // array (table_name => array(adodb_column_obj) )
    var $mTable;
    var $mField;

    function SingleColumnMapping( ) 
    {

    } // end of member function SingleColumnMapping

    function SetTablesAndColumns( $tablesAndColumns ) 
    {
        $this->mTablesAndColumns = $tablesAndColumns;

        if ( ! empty( $this->mTable ) ) 
        {
            if ( ! isset( $this->mTablesAndColumns[$this->mTable] ) )
            {
                $this->mTable = null;
                $this->mField = null;
            }
            else
            {
                if ( ! empty( $this->mField ) ) 
                {
                    if ( ! isset( $this->mTablesAndColumns[$this->mTable][$this->mField] ) )
                    {
                        $this->mField = null;
                    }
                }
            }
        }

    } // end of member function SetTablesAndColumns

    function SetTable( $table ) 
    {
        $this->mTable = $table;

    } // end of member function SetTable

    function GetTable( ) 
    {
        return $this->mTable;

    } // end of member function GetTable

    function SetField( $field ) 
    {
        $this->mField = $field;

    } // end of member function SetField

    function GetField( ) 
    {
        return $this->mField;

    } // end of member function GetField

    function IsMapped( )
    {
        if ( ( ! parent::IsMapped() ) or empty( $this->mTable ) or empty( $this->mField ) ) 
        {
            return false;
        }

        return true;

    } // end of member function IsMapped

    function Refresh( &$rForm )
    {
        parent::Refresh( $rForm );

        $this->SetTablesAndColumns( $rForm->GetTablesAndColumns() );

        $table_input_name = $this->GetInputName( 'table' );
        $field_input_name = $this->GetInputName( 'field' );

        if ( isset( $_REQUEST[$table_input_name] ) )
        {
            // TODO: check if table belongs to database
            $this->mTable = $_REQUEST[$table_input_name];
        }

        if ( isset( $_REQUEST[$field_input_name] ) )
        {
            // TODO: check if field belongs to table
            $this->mField = $_REQUEST[$field_input_name];

            if ( $_REQUEST['refresh'] == $field_input_name  )
            {
                // If changed this field or chose this field for the first time
                // then try to figure out the type

                if ( isset( $this->mTablesAndColumns[$this->mTable][$this->mField] ) )
                {
                    $field = $this->mTablesAndColumns[$this->mTable][$this->mField];

                    $field_type = TpConfigUtils::GetFieldType( $field );

                    $this->SetLocalType( $field_type );
                }
            }
        }

    } // end of member function Refresh

    function GetHtml( ) 
    {
        if ( $this->mrConcept == null ) 
        {
            return 'Mapping has no associated concept!';
        }

        if ( ! count( $this->mTablesAndColumns ) )
        {
            return 'Mapping has no associated db metadata!';
        }
        
        include('SingleColumnMapping.tmpl.php');

        parent::GetHtml();

    } // end of member function GetHtml

    function GetXml( ) 
    {
        $xml = "\t\t\t\t";

        $xml .= '<singleColumnMapping type="'.$this->mLocalType.'">'.
                "\n\t\t\t\t\t".
                '<column table="'.TpUtils::EscapeXmlSpecialChars( $this->mTable ).'" '.
                        'field="'.TpUtils::EscapeXmlSpecialChars( $this->mField ).'"/>'.
                "\n\t\t\t\t".
                '</singleColumnMapping>';

        return $xml;

    } // end of member function GetXtml

    function GetInputName( $suffix )
    {
        if ( $this->mrConcept == null ) 
        {
            $error = 'Single column mapping cannot give an input name without having '.
                     'an associated concept!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return 'undefined_concept_' . $suffix;
        }

        return strtr( $this->mrConcept->GetId() . '_' . $suffix, '.', '_' );

    } // end of member function GetInputName

    function GetOptions( $id ) 
    {
        $options = array();

        if ( $id == 'tables')
        {
            if ( $this->mTablesAndColumns != null ) 
            {
                $options = array_keys( $this->mTablesAndColumns );

                $options = TpUtils::GetHash( $options );
            }

            asort( $options );
            array_unshift( $options, '-- table --' );
        }
        else if ( $id == 'fields')
        {
            if ( $this->mTablesAndColumns != null and 
                 ! empty( $this->mTable ) and $this->mTable != '0' )
            {
                if ( is_array( $this->mTablesAndColumns[$this->mTable] ) )
                {
                    $columns = $this->mTablesAndColumns[$this->mTable];

                    foreach ( $columns as $column ) 
                    {
                        array_push( $options, $column->name );
                    }

                    $options = TpUtils::GetHash( $options );
                }
            }

            asort( $options );
            array_unshift( $options, '-- column --' );
        }

        return $options;

    } // end of member function GetOptions

    function GetSqlTarget( &$rAdodb, $inWhereClause=false ) 
    {
        $target = TpSqlBuilder::GetSqlName( $this->mTable .'.'. $this->mField );

        if ( ! $inWhereClause )
        {
            if ( $this->GetLocalType() == TYPE_DATETIME )
            {
                // TODO: How to handle timezones?
                $target = $rAdodb->SQLDate( 'Y-m-d H:i:s', $target );
            }
            else if ( $this->GetLocalType() == TYPE_DATE )
            {
                $target = $rAdodb->SQLDate( 'Y-m-d', $target );
            }
        }

        return $target;

    } // end of member function GetSqlTarget

    function GetSqlFrom( ) 
    {
        return array( $this->mTable => $this->mTable );

    } // end of member function GetSqlFrom

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
      return array( 'mMappingType', 'mTable', 'mField', 'mLocalType' );

    } // end of member function __sleep

} // end of SingleColumnMapping
?>