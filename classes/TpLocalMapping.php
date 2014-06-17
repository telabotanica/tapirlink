<?php
/**
 * $Id: TpLocalMapping.php 2008 2010-06-18 14:20:11Z rdg $
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

require_once('TpConceptualSchema.php');
require_once('TpConceptMappingFactory.php');
require_once('TpDiagnostics.php');
require_once('TpUtils.php');
require_once('TpConfigUtils.php');

class TpLocalMapping
{
    var $mMappedSchemas = array();     // namespace => conceptual schema object
    var $mAvailableSchemas = array();  // namespace => conceptual schema object
    var $mFetchedListOfSchemas;        // boolean
    var $mInTag;
    var $mCurrentSchema;
    var $mCurrentConcept;
    var $mCurrentMapping;
    var $mrResource;
    var $mRevision = null; // Revision number when this mapping was generated

    function TpLocalMapping( )
    {

    } // end of member function TpLocalMapping

    function SetResource( &$rResource ) 
    {
        $this->mrResource =& $rResource;

    } // end of member function SetResource

    function Reset( )
    {
        $this->mMappedSchemas = array();
        $this->mAvailableSchemas = array();
        $this->mFetchedListOfSchemas = false;

    } // end of member function Reset

    function GetSchemasFile( )
    {
        return realpath( TP_CONFIG_DIR.'/'.TP_SCHEMAS_FILE );

    } // end of member function GetSchemasFile

    function AddMappedSchema( $schema ) 
    {
        $namespace = $schema->GetNamespace();

        $this->mMappedSchemas[$namespace] = $schema;

    } // end of member function AddMappedSchema

    function &GetMappedSchemas( ) 
    {
        asort( $this->mMappedSchemas );

        return $this->mMappedSchemas;

    } // end of member function GetMappedSchemas

    function GetUnmappedSchemas( ) 
    {
        $this->FetchListOfSchemas();

        $unmapped_schemas = array();

        foreach ( $this->mAvailableSchemas as $namespace => $schema ) 
        {
            $is_mapped = false;

            foreach ( $this->mMappedSchemas as $mapped_namespace => $mapped_schema ) 
            {
                if ( $mapped_namespace == $namespace ) 
                {
                    $is_mapped = true;
                }
            }

            if ( ! $is_mapped ) 
            {
                $unmapped_schemas[$namespace] = $schema;
            }
        }

        asort( $unmapped_schemas );

        return $unmapped_schemas;

    } // end of member function GetMappedSchemas

    function FetchListOfSchemas( ) 
    {
        if ( $this->mFetchedListOfSchemas )
        {
            return;
        }

        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_element_handler( $parser, 'SchemasStartElement', 'SchemasEndElement' );
        xml_set_character_data_handler( $parser, 'SchemasCharacterData' );

        $file = $this->GetSchemasFile();

        if ( !( $fp = fopen( $file, 'r' ) ) ) 
        {
            $error = "Could not open file with list of schemas: $file";
            TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );

            return;
        }

        $this->mInTag = null;
      
        while ( $data = fread( $fp, 4096 ) ) 
        {
            if ( ! xml_parse( $parser, $data, feof( $fp ) ) ) 
            {
                $error = sprintf( "XML error: %s at line %d",
                                  xml_error_string( xml_get_error_code( $parser ) ),
                                  xml_get_current_line_number( $parser ) );

                TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_ERROR );
                return;
            }
        }

        xml_parser_free( $parser );
        fclose( $fp );

        $this->mFetchedListOfSchemas = true;

    } // end of member function FetchListOfSchemas

    function SchemasStartElement( $parser, $name, $attrs ) 
    {
        $this->mInTag = $name;

        if ( strcasecmp( $name, 'schema' ) == 0 ) 
        {
            $this->mCurrentSchema = new TpConceptualSchema();

            $this->mCurrentSchema->SetAlias( $attrs['alias'] );
        }

    } // end of member function SchemasStartElement

    function SchemasEndElement( $parser, $name ) 
    {
        if ( strcasecmp( $name, 'schema' ) == 0 ) 
        {
            $namespace = $this->mCurrentSchema->GetNamespace();

            $this->mAvailableSchemas[$namespace] = $this->mCurrentSchema;
        }

    } // end of member function SchemasEndElement

    function SchemasCharacterData( $parser, $data ) 
    {
        if ( strlen( trim( $data ) ) ) 
        {
            if ( strcasecmp( $this->mInTag, 'namespace' ) == 0 ) 
            {
                $this->mCurrentSchema->SetNamespace( $data );
            }
            else if ( strcasecmp( $this->mInTag, 'location' ) == 0 ) 
            {
                $this->mCurrentSchema->SetSource( $data );
            }
            else if ( strcasecmp( $this->mInTag, 'handler' ) == 0 ) 
            {
                $this->mCurrentSchema->SetHandler( $data );
            }
        }

    } // end of member function SchemasCharacterData

    function LoadSuggestedSchema( $namespace ) 
    {
        $this->FetchListOfSchemas();

        if ( ! isset( $this->mAvailableSchemas[$namespace] ) )
        {
            $error = 'Selected schema is not available!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return;
        }

        $schema = $this->mAvailableSchemas[$namespace];

        if ( $schema->FetchConcepts() ) {

            // Move schema from lists
            $this->mMappedSchemas[$namespace] = $schema;
            unset( $this->mAvailableSchemas[$namespace] );
        }
        else
        {
            $error = 'Failed to load concepts from schema. Check the server access to: '.$schema->GetLocation();
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
        }

    } // end of member function LoadSuggestedSchema

    function LoadNewSchema( $location, $schemaHandler='CnsSchemaHandler_v2' )
    {
        $schema = new TpConceptualSchema();
        $schema->SetSource( $location );
        $schema->SetHandler( $schemaHandler );

        if ( $schema->FetchConcepts() ) {

            $namespace = $schema->GetNamespace();

            if ( isset( $this->mMappedSchemas[$namespace] ) )
            {
                // ignore because schema is already loaded/mapped
                $error = sprintf( 'The specified schema is already loaded' );
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
                return null;
            }

            // Move schema from lists
            $this->mMappedSchemas[$namespace] = $schema;
            unset( $this->mAvailableSchemas[$namespace] );
            return $schema;
        }

        return null;

    } // end of member function LoadNewSchema

    function UnmapSchema( $namespace )
    {
        if ( isset( $this->mMappedSchemas[$namespace] ) )
        {
            // Note: Assuming that all schemas in schemas.xml have an alias which
            //       is different than the namespace, the following condition
            //       does not add to the list of available schemas a schema that
            //       was not originally proposed in the list.
            if ( $namespace != $this->mMappedSchemas[$namespace]->GetAlias() )
            {
                $this->mAvailableSchemas[$namespace] = $this->mMappedSchemas[$namespace];
            }

            unset( $this->mMappedSchemas[$namespace] );
        }

    } // end of member function UnmapSchema

    function LoadFromXml( $file )
    {
        $this->Reset();

        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_element_handler( $parser, 'StartElement', 'EndElement' );
        xml_set_character_data_handler( $parser, 'CharacterData' );

        if ( !( $fp = fopen( $file, 'r' ) ) ) 
        {
            $error = "Could not open file: $file";
            TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );

            return false;
        }

        while ( $data = fread( $fp, 4096 ) ) 
        {
            if ( !xml_parse( $parser, $data, feof($fp) ) ) 
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

    } // end of member function LoadFromXml

    function StartElement( $parser, $name, $attrs ) 
    {
        if ( strcasecmp( $name, 'mapping' ) == 0 ) 
        {
            if ( isset( $attrs['rev'] ) )
            {
                $this->mRevision = (int)$attrs['rev'];
            }
        }
        else if ( strcasecmp( $name, 'schema' ) == 0 ) 
        {
            $this->mCurrentSchema = new TpConceptualSchema();

            $this->mCurrentSchema->SetNamespace( $attrs['namespace'] );
            $this->mCurrentSchema->SetLocation( $attrs['location'] );
            $this->mCurrentSchema->SetAlias( $attrs['alias'] );
            $this->mCurrentSchema->SetHandler( $attrs['handler'] );

            // "source" attribtue was included later
            if ( isset( $attrs['source'] ) and ! empty( $attrs['source'] ) )
            {
                $this->mCurrentSchema->SetSource( $attrs['source'] );
            }
        }
        // Assuming "<concept>" can only occur inside "<schema>"
        else if ( strcasecmp( $name, 'concept' ) == 0 ) 
        {
            $this->mCurrentConcept = new TpConcept();
            $this->mCurrentConcept->SetId( $attrs['id'] );
            $this->mCurrentConcept->SetName( $attrs['name'] );

            $required = ( $attrs['required'] == 'true' ) ? true : false;
            $searchable = ( $attrs['searchable'] == 'true' ) ? true : false;

            $this->mCurrentConcept->SetRequired( $required );
            $this->mCurrentConcept->SetSearchable( $searchable );

            if ( isset( $attrs['type'] ) )
            {
                $type = null;

                // Try to fix old types
                if ( is_null( $this->mRevision ) or $this->mRevision < 641 )
                {
                    $handler = $this->mCurrentSchema->GetHandler();

                    if ( $handler == 'DarwinSchemaHandler_v1' )
                    {
                        $parts = explode( ':', $attrs['type'] );

                        // Assuming that the namespace starts with "http:"
                        if ( count( $parts ) >= 3 )
                        {
                            $type_name = array_pop( $parts );

                            $ns = implode( ':', $parts );

                            $type = $ns.'#'.$type_name;

                            // Just in case...
                            $type = TpConfigUtls::GetPrimitiveXsdType();
                        }
                    }
                    else if ( $handler == 'DarwinSchemaHandler_v2' )
                    {
                        $parts = explode( ':', $attrs['type'] );

                        // Assuming that the namespace starts with "http:"
                        if ( count( $parts ) >= 3 )
                        {
                            $type_name = array_pop( $parts );

                            $ns = implode( ':', $parts );

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
                                $type = $ns.'#'.$type_name;

                                // Just in case...
                                $type = TpConfigUtils::GetPrimitiveXsdType( $type );
                            }
                        }
                    }
                }
                else
                {
                    $type = $attrs['type'];
                }

                $this->mCurrentConcept->SetType( $type );
            }
            if ( isset( $attrs['documentation'] ) )
            {
                $this->mCurrentConcept->SetDocumentation( $attrs['documentation'] );
            }
        }
        else if ( strcasecmp( $name, 'singleColumnMapping' ) == 0 ) 
        {
            $this->mCurrentMapping = TpConceptMappingFactory::GetInstance( 'SingleColumnMapping' );
            if ( isset( $attrs['type'] ) )
            {
                $this->mCurrentMapping->SetLocalType( $attrs['type'] );
            }
        }
        else if ( strcasecmp( $name, 'fixedValueMapping' ) == 0 ) 
        {
            $this->mCurrentMapping = TpConceptMappingFactory::GetInstance( 'FixedValueMapping' );
            if ( isset( $attrs['type'] ) )
            {
                $this->mCurrentMapping->SetLocalType( $attrs['type'] );
            }

        }
        else if ( strcasecmp( $name, 'environmentVariableMapping' ) == 0 ) 
        {
            $this->mCurrentMapping = TpConceptMappingFactory::GetInstance( 'EnvironmentVariableMapping' );

            $this->mCurrentMapping->SetResource( $this->mrResource );
        }
        // Assuming "<column>" can only occur inside "<singleColumnMapping>"
        else if ( strcasecmp( $name, 'column' ) == 0 ) 
        {
            $this->mCurrentMapping->SetTable( $attrs['table'] );
            $this->mCurrentMapping->SetField( $attrs['field'] );
        }
        // Assuming "<value>" can only occur inside "<fixedValueMapping>"
        else if ( strcasecmp( $name, 'value' ) == 0 ) 
        {
            $this->mCurrentMapping->SetValue( $attrs['v'] );
        }
        // Assuming "<varName>" can only occur inside "<environmentVariableMapping>"
        else if ( strcasecmp( $name, 'varName' ) == 0 ) 
        {
            $this->mCurrentMapping->SetVariable( $attrs['v'] );
        }

    } // end of member function StartElement

    function EndElement( $parser, $name ) 
    {
        if ( strcasecmp( $name, 'schema' ) == 0 ) 
        {
            $namespace = $this->mCurrentSchema->GetNamespace();

            $this->mMappedSchemas[$namespace] = $this->mCurrentSchema;

            unset( $this->mAvailableSchemas[$namespace] );
        }
        else if ( strcasecmp( $name, 'concept' ) == 0 ) 
        {
            $this->mCurrentSchema->AddConcept( $this->mCurrentConcept );
        }
        else if ( strcasecmp( $name, 'singleColumnMapping' ) == 0 or 
                  strcasecmp( $name, 'fixedValueMapping' )   == 0 or 
                  strcasecmp( $name, 'environmentVariableMapping' ) == 0 ) 
        {
            $this->mCurrentConcept->SetMapping( $this->mCurrentMapping );
        }

    } // end of member function EndElement

    function CharacterData( $parser, $data ) 
    {

    } // end of member function CharacterData

    function GetConfigXml( ) 
    {
        $xml = "\t".'<mapping rev="'.TP_REVISION.'">'."\n";

        foreach ( $this->mMappedSchemas as $namespace => $schema ) 
        {
            $xml .= $schema->GetConfigXml();
        }

        $xml .= "\t</mapping>\n";

        return $xml;

    } // end of member function GetConfigXml

    function GetCapabilitiesXml( ) 
    {
        $xml = "\t<concepts>\n";

        foreach ( $this->mMappedSchemas as $namespace => $schema ) 
        {
            $xml .= $schema->GetCapabilitiesXml();
        }

        $xml .= "\t</concepts>\n";

        return $xml;

    } // end of member function GetCapabilitiesXml

    function Validate( ) 
    {
        $ret_val = true;
        $set_errors = true;

        foreach ( $this->mMappedSchemas as $namespace => $schema ) 
        {
            if ( ! $schema->IsMapped( $set_errors ) ) 
            {
                $ret_val = false;
            }
        }

        return $ret_val;

    } // end of member function Validate

    function IsMappedConcept( $conceptId ) 
    {
        foreach ( $this->mMappedSchemas as $namespace => $schema ) 
        {
            $r_concepts =& $schema->GetConcepts();

            foreach ( $r_concepts as $id => $concept ) 
            {
                if ( $id == $conceptId and $concept->IsMapped() )
                {
                    return true;
                }
            }
        }

        return false;

    } // end of member function IsMappedConcept

    function GetConcept( $conceptId ) 
    {
        foreach ( $this->mMappedSchemas as $namespace => $schema ) 
        {
            $concepts = $schema->GetConcepts();

            foreach ( $concepts as $id => $concept ) 
            {
                if ( $id == $conceptId )
                {
                    return $concept;
                }
            }
        }

        return null;

    } // end of member function GetConcept

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
      return array( 'mMappedSchemas', 'mAvailableSchemas', 'mFetchedListOfSchemas',
                    'mRevision' );

    } // end of member function __sleep

} // end of TpLocalMapping
?>