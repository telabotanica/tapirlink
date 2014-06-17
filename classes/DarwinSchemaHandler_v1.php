<?php
/**
 * $Id: DarwinSchemaHandler_v1.php 641 2008-04-22 19:00:45Z rdg $
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

require_once('TpConceptualSchemaHandler.php');
require_once('TpDiagnostics.php');
require_once('TpConfigUtils.php');

class DarwinSchemaHandler_v1 extends TpConceptualSchemaHandler
{
    var $mConceptualSchema;
    var $mXmlSchemaNs = 'http://www.w3.org/2001/XMLSchema';
    var $mDigirPrefix = '?';
    var $mInTags = array();
    var $mNamespaces = array();
    var $mConcept;

    function DarwinSchemaHandler_v1( ) 
    {

    } // end of member function DarwinSchemaHandler_v1

    function Load( &$conceptualSchema ) 
    {
        $this->mConceptualSchema =& $conceptualSchema;

        $parser = xml_parser_create_ns();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_start_namespace_decl_handler( $parser, '_DeclareNamespace' );
        xml_set_element_handler( $parser, '_StartElement', '_EndElement' );
        xml_set_character_data_handler( $parser, '_CharacterData' );

        $file = $conceptualSchema->GetLocation();

        if ( !( $fp = fopen( $file, 'r' ) ) ) 
        {
            $error = "Could not open remote file: $file";
            TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );

            return false;
        }
      
        while ( $data = fread( $fp, 4096 ) ) 
        {
            if ( ! xml_parse( $parser, $data, feof( $fp ) ) ) 
            {
                $error = sprintf( "XML error: %s at line %d",
                                  xml_error_string( xml_get_error_code( $parser ) ),
                                  xml_get_current_line_number( $parser ) );

                TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_ERROR );
                return false;
            }
        }

        xml_parser_free( $parser );
        fclose( $fp );

        return true;

    } // end of member function Load

    function _StartElement( $parser, $name, $attrs ) 
    {
        array_push( $this->mInTags, $name );

        $depth = count( $this->mInTags );

        // Schema root element
        if ( $depth == 1 )
        {
            $remoteNamespace = $attrs['targetNamespace'];
            $expectedNamespace = $this->mConceptualSchema->GetNamespace();

            if ( $expectedNamespace == null ) 
            {
                // Incorporate the namespace if object has no ns defined
                $this->mConceptualSchema->SetNamespace( $remoteNamespace );
            }
            else if ( $expectedNamespace != $remoteNamespace )
            {
                // Check if namespaces match
                $error = sprintf( 'Remote schema targetNamespace (%s) does not '.
                                  'match the expected namespace (%s)!',
                                  $remoteNamespace, $expectedNamespace );
                TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_ERROR );

                // Interrupt parsing
                xml_set_element_handler( $parser, null, null );
            }
        }
        // Global elements are potential concept candidates
        else if ( strcasecmp( $name, $this->mXmlSchemaNs.':element' ) == 0 and 
                  $depth == 2 )
        {
            // If there is a substitutionGroup attribute pointing to 
            // a DiGIR content element, then it is a concept
            if ( isset( $attrs['substitutionGroup'] ) and 
                 ( $attrs['substitutionGroup'] == $this->mDigirPrefix.':searchableReturnableData' or
                   $attrs['substitutionGroup'] == $this->mDigirPrefix.':searchableData' or
                   $attrs['substitutionGroup'] == $this->mDigirPrefix.':returnableData' ) )
            {
                $this->_PrepareConcept( $attrs );
            }
        }

    } // end of member function _StartElement

    function _EndElement( $parser, $name ) 
    {
        if ( strcasecmp( $name, $this->mXmlSchemaNs.':element' ) == 0 and 
             is_object( $this->mConcept ) )
        {
            // Assuming that if this is closing an element tag and there is
            // a pending concept, then it is time to add the concept

            $this->mConceptualSchema->AddConcept( $this->mConcept );

            $this->mConcept = null;
        }

        array_pop( $this->mInTags );

    } // end of member function _EndElement

    function _CharacterData( $parser, $data ) 
    {
        $depth = count( $this->mInTags );

        $in_tag = $this->mInTags[$depth-1];

        if ( strcasecmp( $in_tag, $this->mXmlSchemaNs.':documentation' ) == 0 and 
             is_object( $this->mConcept ) )
        {
            // Add documentation to the concept
            $this->mConcept->SetDocumentation( $data );
        }

    } // end of member function _CharacterData

    function _DeclareNamespace( $parser, $prefix, $uri ) 
    {
        if ( $uri == 'http://digir.net/schema/protocol/2003/1.0' )
        {
            $this->mDigirPrefix = $prefix;
        }

        $this->mNamespaces[$prefix] = $uri;

    } // end of member function _DeclareNamespace

    function _PrepareConcept( $attrs ) 
    {
        if ( ! isset( $attrs['name'] ) )
        {
            $error = 'Could not add concept without a "name" attribute. '.
                     'Did you use "ref"? DarwinSchemaHandler is not prepared '.
                     'to understand referenced concepts...';
            TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_ERROR );

            return;
        }

        $local_name = $attrs['name'];

        $ns = $this->mConceptualSchema->GetNamespace();

        $id = $ns . '/'. $local_name;

        $required = true;

        if ( isset( $attrs['nillable'] ) and $attrs['nillable'] == 'true' )
        {
            $required = false;
        }

        $type = null;

        if ( isset( $attrs['type'] ) )
        {
            // Try to get the fully qualified type, but only from the type attribute
            $parts = explode( ':', $attrs['type'] );

            if ( count( $parts ) == 2 )
            {
                $prefix = $parts[0];
                $type_name = $parts[1];
 
                if ( isset( $this->mNamespaces[$prefix] ) )
                {
                    $ns = $this->mNamespaces[$prefix];

                    $type = TpConfigUtils::GetPrimitiveXsdType( $type_name, $ns );
                }
            }
        }

        $this->mConcept = new TpConcept();
        $this->mConcept->SetId( $id );
        $this->mConcept->SetName( $local_name );
        $this->mConcept->SetRequired( $required );
        $this->mConcept->SetType( $type );

    } // end of member function _PrepareConcept

} // end of DarwinSchemaHandler_v1
?>