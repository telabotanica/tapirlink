<?php
/**
 * $Id: EnvironmentVariableMapping.php 648 2008-04-23 18:51:54Z rdg $
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
require_once('TpHtmlUtils.php');

class EnvironmentVariableMapping extends TpConceptMapping
{
    var $mMappingType = 'EnvironmentVariableMapping';
    var $mVariable;
    var $mrResource;

    function EnvironmentVariableMapping( ) 
    {

    } // end of member function EnvironmentVariableMapping

    function SetResource( &$rResource ) 
    {
        $this->mrResource =& $rResource;

    } // end of member function SetResource

    function SetVariable( $name ) 
    {
        $this->mVariable = $name;

    } // end of member function SetVariable

    function GetVariable( ) 
    {
        return $this->mVariable;

    } // end of member function GetVariable

    function IsMapped( )
    {
        if ( is_null( $this->mVariable ) ) 
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
            $this->mVariable = $_REQUEST[$input_name];
        }

    } // end of member function Refresh

    function GetHtml( ) 
    {
        if ( $this->mrConcept == null ) 
        {
            return 'Mapping has no associated concept!';
        }

        include('EnvironmentVariableMapping.tmpl.php');

    } // end of member function GetHtml

    function GetInputName( )
    {
        if ( $this->mrConcept == null ) 
        {
            $error = 'Environment variable mapping cannot give an input name without '.
                     'having an associated concept!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return '';
        }

        return strtr( $this->mrConcept->GetId() . '_varname', '.', '_' );

    } // end of member function GetInputName

    function GetOptions( )
    {
        return array( ''                      => '-- variable --',
                      'accesspoint'           => 'accesspoint',
                      'contentcontactemail'   => 'contentcontactemail',
                      'contentcontactname'    => 'contentcontactname',
                      'datasourcedescription' => 'datasourcedescription',
                      'datasourcelanguage'    => 'datasourcelanguage',
                      'datasourcename'        => 'datasourcename', 
                      'date'                  => 'date', 
                      'datecreated'           => 'datecreated',
                      'lastupdate'            => 'lastupdate',
                      'metadatalanguage'      => 'metadatalanguage',
                      'rights'                => 'rights',
                      'technicalcontactemail' => 'technicalcontactemail',
                      'technicalcontactname'  => 'technicalcontactname',
                      'timestamp'             => 'timestamp' );

    } // end of member function GetOptions

    function GetXml( ) 
    {
        $xml = "\t\t\t\t";

        $xml .= '<environmentVariableMapping>'.
                "\n\t\t\t\t\t".
                '<varName v="'.TpUtils::EscapeXmlSpecialChars( $this->mVariable ).'"/>'.
                "\n\t\t\t\t".
                '</environmentVariableMapping>';

        return $xml;

    } // end of member function GetXml

    function GetSqlTarget( &$rAdodb, $inWhereClause=false ) 
    {
        $value = $this->mrResource->GetVariable( $this->mVariable );

        return "'".$value."'";

    } // end of member function GetSqlTarget

    function GetSqlFrom( ) 
    {
        return array();

    } // end of member function GetSqlFrom

    function GetLocalXsdType( ) 
    {
        if ( $this->mVariable == 'timestamp' )
        {
            return 'http://www.w3.org/2001/XMLSchema#dateTime';
        }
        else if ( $this->mVariable == 'datecreated' )
        {
            return 'http://www.w3.org/2001/XMLSchema#dateTime';
        }
        else if ( $this->mVariable == 'lastupdate' )
        {
            return 'http://www.w3.org/2001/XMLSchema#dateTime';
        }
        else if ( $this->mVariable == 'date' )
        {
            return 'http://www.w3.org/2001/XMLSchema#date';
        }

        return 'http://www.w3.org/2001/XMLSchema#string';

    } // end of member function GetLocalXsdType

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
      return array( 'mMappingType', 'mVariable', 'mLocalType' );

    } // end of member function __sleep

} // end of FixedValueMapping
?>