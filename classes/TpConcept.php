<?php
/**
 * $Id: TpConcept.php 641 2008-04-22 19:00:45Z rdg $
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
require_once('TpUtils.php');

class TpConcept
{
    var $mId;
    var $mName;
    var $mRequired = false;  // This comes from the conceptual schema and it indicates
                             // if the concept should be mapped or not. It has nothing
                             // to do with the "required" attribute of TAPIR 
                             // capabilities responses which is intended to indicate
                             // if the concept should be present in output models.
    var $mSearchable = true;
    var $mMapping;
    var $mType;              // Concept type (string). Usually Namespace#TypeName.

    var $mDocumentation;

    function TpConcept( ) 
    {

    } // end of member function TpConcept

    function SetId( $id ) 
    {
        $this->mId = $id;

    } // end of member function SetId

    function GetId( ) 
    {
        return $this->mId;

    } // end of member function GetId

    function SetName( $name ) 
    {
        $this->mName = $name;

    } // end of member function SetName

    function GetName( ) 
    {
        return ( empty( $this->mName ) ) ? $this->mId : $this->mName;

    } // end of member function GetName

    function SetType( $type ) 
    {
        $this->mType = $type;

    } // end of member function SetType

    function GetType( ) 
    {
        return $this->mType;

    } // end of member function GetType

    function SetRequired( $required ) 
    {
        $this->mRequired = $required;

    } // end of member function SetRequired

    function IsRequired( ) 
    {
        return $this->mRequired;

    } // end of member function IsRequired

    function SetSearchable( $searchable ) 
    {
        $this->mSearchable = $searchable;

    } // end of member function SetSearchable

    function IsSearchable( ) 
    {
        return $this->mSearchable;

    } // end of member function IsSearchable

    function SetDocumentation( $doc ) 
    {
        $this->mDocumentation = $doc;

    } // end of member function SetDocumentation

    function GetDocumentation( ) 
    {
        return $this->mDocumentation;

    } // end of member function GetDocumentation

    function IsMapped( ) 
    {
        if ( $this->mMapping == null ) 
        {
            return false;
        }
        else
        {
            return $this->mMapping->IsMapped();
        }

    } // end of member function IsMapped

    function SetMapping( $mapping ) 
    {
        $this->mMapping = $mapping; // work on a copy!

        if ( $mapping != null )
        {
            $this->mMapping->SetConcept( $this );
        }

    } // end of member function SetMapping

    function &GetMapping( ) 
    {
        // Just in case, set the concept
        // (property mConcept is not serialized in concept mappings to 
        //  avoid recursion problems)
        if ( $this->mMapping != null ) 
        {
            $this->mMapping->SetConcept( $this );
        }

        return $this->mMapping;

    } // end of member function GetMapping

    function GetMappingType( ) 
    {
        if ( $this->mMapping != null ) 
        {
            return $this->mMapping->GetMappingType();
        }

        return 'unmapped';

    } // end of member function GetMappingType

    function GetMappingHtml( ) 
    {
        if ( $this->mMapping != null ) 
        {
            return $this->mMapping->GetHtml();
        }

        return '';

    } // end of member function GetMappingHtml

    function GetConfigXml( ) 
    {
        $required = ( $this->IsRequired() ) ? 'true' : 'false';
        $searchable = ( $this->IsSearchable() ) ? 'true' : 'false';

        $type_str = '';

        if ( ! is_null( $this->mType ) )
        {
            $type_str = 'type="'.$this->mType.'" ';
        }

        $doc_str = '';

        if ( ! is_null( $this->mDocumentation ) )
        {
            $doc_str = 'documentation="'.TpUtils::EscapeXmlSpecialChars( $this->mDocumentation ).'" ';
        }

        $xml = "\t\t\t";
        $xml .= '<concept id="'.TpUtils::EscapeXmlSpecialChars( $this->GetId() ).'" '.
                         'name="'.TpUtils::EscapeXmlSpecialChars( $this->GetName() ).'" '.
                         $type_str . $doc_str .
                         'required="'. $required .'" '.
                         'searchable="'. $searchable.'">'."\n";

        if ( $this->IsMapped() ) 
        {
            $mapping = $this->GetMapping();

            $xml .= $mapping->GetXml();
        }

        $xml .= "\t\t\t</concept>\n";

        return $xml;

    } // end of member function GetConfigXml

    function GetCapabilitiesXml( ) 
    {
        $searchable = ( $this->IsSearchable() ) ? 'true' : 'false';

        $datatype_xml = '';

        if ( $this->IsMapped() )
        {
            $mapping = $this->GetMapping();

            $datatype = $mapping->GetLocalXsdType();

            if ( ! is_null( $datatype ) )
            {
                $datatype_xml = ' datatype="'.$datatype.'"';
            }
        }

        $xml = "\t\t\t";
        $xml .= '<mappedConcept id="'.TpUtils::EscapeXmlSpecialChars( $this->GetId() ).
                     '" searchable="'. $searchable .'"'.$datatype_xml.'/>'."\n";

        return $xml;

    } // end of member function GetCapabilitiesXml

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
      return array( 'mId', 'mName', 'mType', 'mRequired', 'mSearchable', 
                    'mDocumentation', 'mMapping' );

    } // end of member function __sleep

} // end of TpConcept
?>