<?php
/**
 * $Id: DarwinSchemaHandler_v2.php 641 2008-04-22 19:00:45Z rdg $
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

class DarwinSchemaHandler_v2 extends TpConceptualSchemaHandler
{
    var $mConceptualSchema;
    var $mXmlSchemaNs = 'http://www.w3.org/2001/XMLSchema';
    var $mDarwinElementPrefix = '?';
    var $mInTags = array();
    var $mNamespaces = array();
    var $mConcept;

    function DarwinSchemaHandler_v2( ) 
    {

    } // end of member function DarwinSchemaHandler_v2

    function Load( &$conceptualSchema ) 
    {
        $this->mConceptualSchema =& $conceptualSchema;

        $parser = xml_parser_create_ns();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_start_namespace_decl_handler( $parser, 'DeclareNamespace' );
        xml_set_element_handler( $parser, 'StartElement', 'EndElement' );

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

    function StartElement( $parser, $name, $attrs ) 
    {
        array_push( $this->mInTags, $name );

        $depth = count( $this->mInTags );

        // Schema root element
        if ( $depth == 1 )
        {
            foreach ( $attrs as $attr_name => $attr_val ) 
            {
                if ( $attr_val == 'http://www.w3.org/2001/XMLSchema' ) 
                {
                    $this->mXmlSchemaPrefix = substr( $attr_name, 
                                                      strpos( $attr_name, ':' ) + 1 );
                }
            }

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

                $this->mInTags = array();
                $this->mNamespaces = array();
                $this->mDarwinElementPrefix = '?';

                // Interrupt parsing
                xml_set_element_handler( $parser, null, null );
            }
        }
        // Global elements are potential concept candidates
        else if ( strcasecmp( $name, $this->mXmlSchemaNs.':element' ) == 0 and 
                  $depth == 2 )
        {
            // If there is a substitutionGroup attribute pointing to 
            // a darwin element, then it is a concept
            if ( isset( $attrs['substitutionGroup'] ) and 
                 $attrs['substitutionGroup'] == $this->mDarwinElementPrefix.':dwElement' )
            {
                $this->PrepareConcept( $attrs );
            }
        }
        else if ( strcasecmp( $name, $this->mXmlSchemaNs.':documentation' ) == 0 and 
                  is_object( $this->mConcept ) and isset( $attrs['source'] ) )
        {
            // Add documentation to the concept
            $this->mConcept->SetDocumentation( $attrs['source'] );
        }

    } // end of member function StartElement

    function EndElement( $parser, $name ) 
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

    } // end of member function EndElement

    function DeclareNamespace( $parser, $prefix, $uri ) 
    {
        if ( $uri == 'http://rs.tdwg.org/dwc/dwelement' )
        {
            $this->mDarwinElementPrefix = $prefix;
        }

        $this->mNamespaces[$prefix] = $uri;

    } // end of member function DeclareNamespace

    function PrepareConcept( $attrs ) 
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

        $id = $ns . $local_name;

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

                    if ( $ns == 'http://rs.tdwg.org/dwc/dwcore/' )
                    {
                        $xsd_namespace = 'http://www.w3.org/2001/XMLSchema';

                        // Hard coded
                        switch ( $type_name )
                        {
                            case 'dayOfYearDataType':
                                $type = $xsd_namespace.'#decimal';
                                break;
                            case 'positiveDouble':
                            case 'decimalLatitudeDataType':
                            case 'decimalLongitudeDataType':
                                $type = $xsd_namespace.'#double';
                                break;
                            case 'spatialFitDataType':
                            case 'DateTimeISO':
                                $type = $xsd_namespace.'#string';
                                break;
                        }
                    }
                    else
                    {
                        $type = TpConfigUtils::GetPrimitiveXsdType( $type_name, $ns );
                    }
                }
            }
        }

        $this->mConcept = new TpConcept();
        $this->mConcept->SetId( $id );
        $this->mConcept->SetName( $local_name );
        $this->mConcept->SetRequired( $required );
        $this->mConcept->SetType( $type );

    } // end of member function AddConcept

} // end of DarwinSchemaHandler_v2
?>