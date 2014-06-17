<?php
/**
 * $Id: TpConceptualSchema.php 641 2008-04-22 19:00:45Z rdg $
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

require_once('TpConcept.php');
require_once('TpConceptualSchemaHandlerFactory.php');
require_once('TpConceptualSchemaHandler.php');
require_once('TpDiagnostics.php');

class TpConceptualSchema
{
    var $mAlias; // IMPORTANT: this is actually a label, not an alias in the TAPIR sense 
    var $mNamespace;
    var $mLocation;
    var $mSource;
    var $mConcepts = array();   // $concept_id => $concept
    var $mSchemaHandler;

    function TpConceptualSchema( ) 
    {

    } // end of member function TpConceptualSchema

    function IsMapped( $setErrors=false ) 
    {
        $ret_val = true;

        foreach ( $this->mConcepts as $concept ) 
        {
            if ( $concept->IsRequired() and ! $concept->IsMapped() )
            {
                if ( $setErrors ) 
                {
                    $error = 'Concept "'.$concept->GetName().'" was not mapped!';
                    TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
                }

                $ret_val = false;
            }
        }

        return $ret_val;

    } // end of member function IsMapped

    function SetHandler( $handlerId ) 
    {
        $this->mSchemaHandler = $handlerId;

    } // end of member function SetHandler

    function GetHandler( ) 
    {
        return $this->mSchemaHandler;

    } // end of member function GetHandler

    function SetAlias( $alias ) 
    {
        $this->mAlias = $alias;

    } // end of member function SetAlias

    function GetAlias( ) 
    {
        return ( $this->mAlias ) ? $this->mAlias : $this->mNamespace;

    } // end of member function GetAlias

    function SetNamespace( $namespace ) 
    {
        $this->mNamespace = $namespace;

    } // end of member function SetNamespace

    function GetNamespace( ) 
    {
        return $this->mNamespace;

    } // end of member function GetNamespace

    function SetLocation( $location ) 
    {
        $this->mLocation = $location;

    } // end of member function SetLocation

    function GetLocation( ) 
    {
        return isset( $this->mLocation ) ? $this->mLocation : $this->mSource;

    } // end of member function GetLocation

    function SetSource( $source ) 
    {
        $this->mSource = $source;

    } // end of member function SetSource

    function GetSource( ) 
    {
        return isset( $this->mSource ) ? $this->mSource : $this->mLocation;

    } // end of member function GetSource

    function AddConcept( $concept ) 
    {
        $this->mConcepts[$concept->GetId()] = $concept;

    } // end of member function AddConcept

    function &GetConcepts( ) 
    {
        return $this->mConcepts;

    } // end of member function GetConcepts

    function FetchConcepts( ) 
    {
        if ( $this->mSchemaHandler == null ) 
        {
            $error = 'Schema has no associated schema handler!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return false;
        }

        $handler = TpConceptualSchemaHandlerFactory::GetInstance( $this->mSchemaHandler );

        if ( $handler == null ) 
        {
            $error = 'Could not instantiate schema handler "'.$this->mSchemaHandler.'"';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return false;
        }

        return $handler->Load( $this );

    } // end of member function FetchConcepts

    function GetConfigXml( ) 
    {
        $source = '';

        if ( ! empty( $this->mSource ) )
        {
            $source = ' source="'.TpUtils::EscapeXmlSpecialChars( $this->mSource ).'"';
        }

        $xml = "\t\t";
        $xml .= '<schema namespace="'.TpUtils::EscapeXmlSpecialChars( $this->mNamespace ).'" '.
                        'location="'.TpUtils::EscapeXmlSpecialChars( $this->GetLocation() ).'" '.
                        'alias="'.TpUtils::EscapeXmlSpecialChars( $this->GetAlias() ).'" '.
                        'handler="'.TpUtils::EscapeXmlSpecialChars( $this->mSchemaHandler ).'"'.
                        $source.">\n";

        foreach ( $this->mConcepts as $id => $concept ) 
        {
            $xml .= $concept->GetConfigXml();
        }

        $xml .= "\t\t</schema>\n";

        return $xml;

    } // end of member function GetConfigXml

    function GetCapabilitiesXml( ) 
    {
        $xml = "\t\t";
        $xml .= '<schema namespace="'.TpUtils::EscapeXmlSpecialChars( $this->mNamespace ).'" '.
                        'location="'.TpUtils::EscapeXmlSpecialChars( $this->GetLocation() ).'">'."\n";

        foreach ( $this->mConcepts as $id => $concept ) 
        {
            if ( $concept->IsMapped() )
            {
                $xml .= $concept->GetCapabilitiesXml();
            }
        }

        $xml .= "\t\t</schema>\n";

        return $xml;

    } // end of member function GetCapabilitiesXml

    function Reset( ) 
    {
        $this->mConcepts = array();

    } // end of member function Reset

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
      return array( 'mAlias', 'mNamespace', 'mLocation', 'mSource', 'mConcepts', 
                    'mSchemaHandler' );

    } // end of member function __sleep

} // end of TpConceptualSchema
?>