<?php
/**
 * $Id: FixedValueMapping.php 653 2008-04-28 23:00:51Z rdg $
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

class FixedValueMapping extends TpConceptMapping
{
    var $mMappingType = 'FixedValueMapping';
    var $mValue;

    function FixedValueMapping( )
    {

    } // end of member function FixedValueMapping

    function SetValue( $value )
    {
        $this->mValue = $value;

    } // end of member function SetValue

    function GetValue( )
    {
        return $this->mValue;

    } // end of member function GetValue

    function IsMapped( )
    {
        if ( ( ! parent::IsMapped() ) or is_null( $this->mValue ) )
        {
            return false;
        }

        return true;

    } // end of member function IsMapped

    function Refresh( &$rForm )
    {
        parent::Refresh( $rForm );

        $input_name = $this->GetInputName();

        if ( isset( $_REQUEST[$input_name] ) )
        {
            $this->mValue = $_REQUEST[$input_name];
        }

    } // end of member function Refresh

    function GetHtml( )
    {
        if ( $this->mrConcept == null )
        {
            return 'Mapping has no associated concept!';
        }

        include('FixedValueMapping.tmpl.php');

        parent::GetHtml();

    } // end of member function GetHtml

    function GetInputName( )
    {
        if ( $this->mrConcept == null )
        {
            $error = 'Fixed value mapping cannot give an input name without having '.
                     'an associated concept!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return 'undefined_concept_value';
        }

        return strtr( $this->mrConcept->GetId() . '_value', '.', '_' );

    } // end of member function GetInputName

    function GetXml( )
    {
        $xml = "\t\t\t\t";

        $xml .= '<fixedValueMapping type="'.$this->mLocalType.'">'.
                "\n\t\t\t\t\t".
                '<value v="'.TpUtils::EscapeXmlSpecialChars( $this->mValue ).'"/>'.
                "\n\t\t\t\t".
                '</fixedValueMapping>';

        return $xml;

    } // end of member function GetXml

    function GetSqlTarget( &$rAdodb, $inWhereClause=false )
    {
        if ( $this->mLocalType == TYPE_NUMERIC )
        {
            return $this->mValue;
        }
        else
        {
            return $rAdodb->qstr($this->mValue);
        }

    } // end of member function GetSqlTarget

    function GetSqlFrom( )
    {
        return array();

    } // end of member function GetSqlFrom

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
      return array( 'mMappingType', 'mValue', 'mLocalType' );

    } // end of member function __sleep

} // end of FixedValueMapping
?>