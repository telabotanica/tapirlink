<?php
/**
 * $Id: TpMappingForm.php 2023 2010-09-03 14:37:12Z rdg $
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

require_once('TpWizardForm.php');
require_once('TpUtils.php');
require_once('TpConfigUtils.php');
require_once('TpHtmlUtils.php');
require_once('TpDiagnostics.php');
require_once('TpConceptMappingFactory.php');
require_once('Cache.php'); // pear
require_once(TP_XPATH_LIBRARY);

class TpMappingForm extends TpWizardForm
{
    var $mStep = 5;
    var $mLabel = 'Mapping';
    var $mTablesAndColumns = array();  // array (table_name => array(column obj) )
    var $mXpr;                         // XML parser to original data
    var $mAutomapReferencesFiles;      // will become array: namespace => references file
    var $mLastNs = '';                 // Last namespace parsed in the automapping index
    var $mConceptsWithReferences;      // will become array: concept id => array of concept ids
    var $mBaseConceptId;               // base concept id being parsed
    var $mLocalMappingCopy;            // Copy of the local mapping object to be used during automapping

    function TpMappingForm( )
    {

    } // end of member function TpMappingForm

    function LoadDefaults( )
    {
        if ( $this->mResource->ConfiguredMapping() )
        {
            $this->LoadFromXml();
        }
        else
        {
            $this->SetMessage( "In this step, you'll need to choose the federation schema(s) that you want to use \nand then map each concept from the federation schema(s) to a field in your local database:" );

            $r_data_source =& $this->mResource->GetDataSource();

            $update_session_data = false;

            if ( ! $r_data_source->IsLoaded() )
            {
                $config_file = $this->mResource->GetConfigFile();

                $r_data_source->LoadFromXml( $config_file );

                $update_session_data = true;
            }

            $r_tables =& $this->mResource->GetTables();

            if ( ! $r_tables->IsLoaded() )
            {
                $config_file = $this->mResource->GetConfigFile();

                $r_tables->LoadFromXml( $config_file );

                $update_session_data = true;
            }

            if ( $update_session_data )
            {
                $r_resources =& TpResources::GetInstance();

                $r_resources->SaveOnSession();
            }

            $this->LoadDatabaseMetadata(); 

            // Local mapping
            $r_local_mapping =& $this->mResource->GetLocalMapping();

            // If local mapping is already stored in session, initialize 
            // individual mappings
            if ( count( $r_local_mapping->GetMappedSchemas() ) )
            {
                $this->InitializeMappings();
            }
        }

    } // end of member function LoadDefaults

    function LoadFromSession( ) 
    {
        $r_local_mapping =& $this->mResource->GetLocalMapping();

        // Local mapping must already be stored in the session
        if ( $this->mResource->ConfiguredMapping() and 
             ! count( $r_local_mapping->GetMappedSchemas() ) )
        {
            // If not, the only reason I can think of is that the session expired,
            // so load everything from XML
            $this->LoadFromXml();

            return;
        }

        // Note: there should be no need to load datasource and tables!
        // situation 1: "Restarting the wizard from this step"
        //              In this case LoadDefaults would be called first
        //              so the datasource properties would be in the resources
        //              session object.
        // situation 2: "Coming from the previous step"
        //              In this case the datasource properties would also be in 
        //              the resources session object already.
        // situation 3: "Session expired"
        //              In this case LoaFromXml would be called above. 

        $r_tables =& $this->mResource->GetTables();

        // Get available tables/columns
        $this->LoadDatabaseMetadata();

        $this->InitializeMappings();

    } // end of member function LoadFromSession

    function LoadFromXml( ) 
    {
        if ( $this->mResource->ConfiguredMapping() )
        {
            $config_file = $this->mResource->GetConfigFile();

            $xpr = $this->GetXmlParserForOriginalData( );

            if ( ! $xpr ) 
            {
                return;
            }

            // Load datasource
            $r_data_source =& $this->mResource->GetDataSource();

            $r_data_source->LoadFromXml( $config_file, $xpr );

            // Load tables
            $r_tables =& $this->mResource->GetTables();

            $r_tables->LoadFromXml( $config_file, $xpr );

            $this->LoadDatabaseMetadata();

            $r_local_mapping =& $this->mResource->GetLocalMapping();

            $r_local_mapping->LoadFromXml( $config_file );

            $check_consistency = true;

            $this->InitializeMappings( $check_consistency );
        }
        else
        {
            $err_str = 'There is no local mapping XML configuration to be loaded!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $err_str, DIAG_ERROR );
            return;
        }

    } // end of member function LoadFromXml

    function LoadDatabaseMetadata()
    {
        $r_data_source =& $this->mResource->GetDataSource();

        if ( $r_data_source->Validate() ) 
        {
            $cn = $r_data_source->GetConnection();

            if ( ! is_object( $cn ) )
            {
                $err_str = 'Problem when getting connection to database!';
                TpDiagnostics::Append( CFG_INTERNAL_ERROR, $err_str, DIAG_ERROR );
                return;
	    }

            // Get tables involved

            $r_tables =& $this->mResource->GetTables();

            $root_table = $r_tables->GetRootTable();

            $tables = $root_table->GetAllTables();

            $valid_tables = $cn->MetaTables();

            $convert_case = false;

            foreach ( $tables as $table )
            {
                if ( in_array( $table, $valid_tables ) )
                {
                    $columns = $cn->MetaColumns( $table, $convert_case );

                    $this->mTablesAndColumns[$table] = TpUtils::FixAdodbColumnsArray( $columns );
                }
            }

            $r_data_source->ResetConnection();
        }
        else
        {
            // No need to raise errors (it happens inside "Validate")
        }

    } // end of member function LoadDatabaseMetadata

    function GetXmlParserForOriginalData( ) 
    {
        if ( ! is_object( $this->mXpr ) )
        {
            $this->mXpr = new XPath();
            $this->mXpr->setVerbose( 1 );
            $this->mXpr->setXmlOption( XML_OPTION_CASE_FOLDING, false );
            $this->mXpr->setXmlOption( XML_OPTION_SKIP_WHITE, true );

            if ( ! $this->mXpr->importFromFile( $this->mResource->GetConfigFile() ) )
            {
                $error = 'Could not import content from XML file: '.
                         $this->mXpr->getLastError();
                TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
                return;
            }
        }

        return $this->mXpr;

    } // end of member function GetXmlParserForOriginalData

    function InitializeMappings( $checkConsistency=false )
    {
        $r_local_mapping =& $this->mResource->GetLocalMapping();

        $r_mapped_schemas =& $r_local_mapping->GetMappedSchemas();

        $missing_mandatory_mappings = false;

        foreach ( $r_mapped_schemas as $ns => $schema ) 
        {
            $r_concepts =& $r_mapped_schemas[$ns]->GetConcepts();

            foreach ( $r_concepts as $concept_id => $concept ) 
            {
                $r_mapping =& $r_concepts[$concept_id]->GetMapping();

                if ( $r_mapping == null )
                {
                    if ( $checkConsistency )
                    {
                        if ( $concept->IsRequired() and ! $missing_mandatory_mappings )
                        {
                            $msg = 'Please specify mappings for all mandatory concepts';
                            TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $msg, DIAG_ERROR );
                            $missing_mandatory_mappings = true;
                        }
                    }
                }
                else
                {
                    // Single column mappings need tables and columns to render properly
                    if ( $r_mapping->GetMappingType() == 'SingleColumnMapping' )
                    {
                        $table = $r_mapping->GetTable();
                        $field = $r_mapping->GetField();

                        // Note: SetTablesAndColumns erases table/field data if they
                        //       are not valid!
                        $r_mapping->SetTablesAndColumns( $this->mTablesAndColumns );

                        if ( $checkConsistency )
                        {
                            if ( ! isset( $this->mTablesAndColumns[$table] ) or 
                                 ! is_array( $this->mTablesAndColumns[$table] ) or 
                                 ! isset( $this->mTablesAndColumns[$table][$field] ) )
                            {
                                $msg = 'Current mapping for concept "'.$concept->GetName().'"'.
                                       ' ('.$table.'.'.$field.') does not exist in the '.
                                       'database';
                                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $msg, DIAG_ERROR );
                            }
                        }
                    }
                }
            }
        }

    } // end of member function InitializeMappings

    function ReadyToProceed( )
    {
        $r_local_mapping =& $this->mResource->GetLocalMapping();

        $num_schemas = count( $r_local_mapping->GetMappedSchemas() );

        return ( $num_schemas > 0 ) ? true : false;

    } // end of member function ReadyToProceed

    function DisplayForm( )
    {
        $r_local_mapping =& $this->mResource->GetLocalMapping();

        include('TpMappingForm.tmpl.php');

    } // end of member function DisplayForm

    function HandleEvents( ) 
    {
        $r_local_mapping =& $this->mResource->GetLocalMapping();

        // Clicked on load schemas
        if ( isset( $_REQUEST['load_schemas'] ) ) 
        {
            if ( ! isset( $_REQUEST['schema'] ) and 
                 ! isset( $_REQUEST['load_from_location'] ) ) 
            { 
                $warn = 'Please select a schema to load!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $warn, DIAG_ERROR );
                return;
            }

            if ( isset( $_REQUEST['schema'] ) )
            {
                $schemas = $_REQUEST['schema'];

                // Load each selected schema
                foreach ( $schemas as $namespace ) 
                {
                    $r_local_mapping->LoadSuggestedSchema( $namespace );
                    // Force automapping
                    $_REQUEST['refresh'] = $namespace . '^automap';
                }
            }

            // Load additional schema, if specified
            if ( isset( $_REQUEST['load_from_location'] ) and 
                 strlen( $_REQUEST['load_from_location'] ) > 0 )
            {
                $location = $_REQUEST['load_from_location'];

                if ( isset( $_REQUEST['handler'] ) and 
                     strlen( $_REQUEST['handler'] ) > 0 )
                {
                    $handler = $_REQUEST['handler'];

                    $schema = $r_local_mapping->LoadNewSchema( $location, $handler );
                    if ( $schema )
                    {
                        // Force automapping
                        $namespace = $schema->GetNamespace();
                        $_REQUEST['refresh'] = $namespace . '^automap';
                    }
                }
                else
                {
                    $warn = 'Please select the format of the additional schema to load!';
                    TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $warn, DIAG_ERROR );
                    return;
                }
            }
        }
        // Simple refresh or next or save
        if ( isset( $_REQUEST['refresh'] ) or 
             isset( $_REQUEST['next'] ) or 
             isset( $_REQUEST['update'] ) )
        {
            if ( isset( $_REQUEST['next'] ) and ! $this->ReadyToProceed() )
            {
                $msg = "Not a single schema has been mapped.\n".
                       "It is necessary to map at least one schema.";
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $msg, DIAG_ERROR );
                return;
            }

            if ( isset( $_REQUEST['refresh'] ) and 
                 strlen( $_REQUEST['refresh'] ) > 6 and 
                 substr( $_REQUEST['refresh'], -6 ) == '^unmap' )
            {
                $parts = explode( '^', $_REQUEST['refresh'] );

                $namespace = $parts[0];

                $r_local_mapping->UnmapSchema( $namespace );
            }

            $automap_namespace = '';

            if ( isset( $_REQUEST['refresh'] ) and 
                 strlen( $_REQUEST['refresh'] ) > 8 and 
                 substr( $_REQUEST['refresh'], -8 ) == '^automap' )
            {
                $parts = explode( '^', $_REQUEST['refresh'] );

                $automap_namespace = $parts[0];

                $this->_LoadIndexOfMappingReferences();

                if ( isset( $this->mAutomapReferencesFiles[$automap_namespace] ) )
                {
                    $references_file = $this->mAutomapReferencesFiles[$automap_namespace];

                    $this->_LoadReferences( $references_file );
                }
            }

            $fill_namespace = '';

            if ( isset( $_REQUEST['refresh'] ) and 
                 strlen( $_REQUEST['refresh'] ) > 5 and 
                 substr( $_REQUEST['refresh'], -5 ) == '^fill' )
            {
                $parts = explode( '^', $_REQUEST['refresh'] );

                $fill_namespace = $parts[0];
            }

            // Refresh mappings

            // Create copy of local mapping to avoid messing with iterators
            if ( version_compare( phpversion(), '5.0.0', '<' ) > 0  )
            {
                $this->mLocalMappingCopy = $this->mResource->GetLocalMapping();
            }
            else
            {
                $this->mLocalMappingCopy = clone $this->mResource->GetLocalMapping();
            }

            $r_mapped_schemas =& $r_local_mapping->GetMappedSchemas();

            foreach ( $r_mapped_schemas as $ns => $schema ) 
            {
                $r_concepts =& $r_mapped_schemas[$ns]->GetConcepts();

                foreach ( $r_concepts as $concept_id => $concept ) 
                {
                    /** Searchable **/

                    $searchable = false;

                    if ( isset( $_REQUEST[$this->GetInputName( $concept, 'searchable')] ) )
                    {
                        $searchable = true;
                    }

                    $r_concepts[$concept_id]->SetSearchable( $searchable );

                    /** Mapping **/

                    $mapping_input_name = $this->GetInputName( $concept, 'mapping' );

                    // If there's something specified for the concept
                    if ( isset( $_REQUEST[$mapping_input_name] ) )
                    {
                        $mapping_type = $_REQUEST[$mapping_input_name];

                        $mapping = $concept->GetMapping();

                        // If a new mapping type was specified, overwrite existing one
                        if ( $mapping_type != $concept->GetMappingType() )
                        {
                            if ( $mapping_type == 'unmapped' )
                            {
                                if ( $automap_namespace == $ns )
                                {
                                    // Automap
                                    $mapping = $this->GetAutoMapping( $concept );
                                }
                                else if ( $fill_namespace == $ns )
                                {
                                    // Fill with fixed value mapping
                                    $mapping = TpConceptMappingFactory::GetInstance( 'FixedValueMapping' );
                                    $mapping->SetLocalType( TYPE_TEXT );
                                    $mapping->SetValue( '' );
                                }
                                else
                                {
                                    // Erase mapping
                                    $mapping = null;
                                }
                            }
                            else
                            {
                                $mapping = TpConceptMappingFactory::GetInstance( $mapping_type );

                                // Initialize single column mappings
                                if ( $mapping_type == 'SingleColumnMapping' )
                                {
                                    $mapping->SetTablesAndColumns( $this->mTablesAndColumns );
                                }
                            }
                        }
                        // Mapping type is the same
                        else
                        {
                            if ( $mapping_type == 'unmapped' )
                            { 
                                if ( $automap_namespace == $ns )
                                {
                                    // Automap
                                    $mapping = $this->GetAutoMapping( $concept );
                                }
                                else if ( $fill_namespace == $ns )
                                {
                                    // Fill with fixed value mapping
                                    $mapping = TpConceptMappingFactory::GetInstance( 'FixedValueMapping' );
                                    $mapping->SetLocalType( TYPE_TEXT );
                                    $mapping->SetValue( '' );
                                }
                            }
                            else
                            {
                                // Refresh the existing mapping
                                $mapping->Refresh( $this );
                            }
                        }
                    }
                    // Nothing is specified for the concept
                    else
                    {
                        // Automap
                        $mapping = $this->GetAutoMapping( $concept );
                        $r_concepts[$concept_id]->SetSearchable( true );
                    }

                    $r_concepts[$concept_id]->SetMapping( $mapping );
                }
            }

            $this->mLocalMappingCopy = null;

            // Clicked next or save
            if ( isset( $_REQUEST['next'] ) or isset( $_REQUEST['update'] ) ) 
            {
                if ( ! $r_local_mapping->Validate() ) 
                {
                    return;
                }

                if ( ! $this->mResource->SaveLocalMapping() ) 
                {
                    return;
                }

                // Clicked update
                if ( isset( $_REQUEST['update'] ) ) 
                {
                    // Remove all possible content from cache (query templates,
                    // output models and response structures) since this kind of
                    // cached data largely depends on what is mapped.

                    $cache_dir = TP_CACHE_DIR . '/' . $this->mResource->GetCode();

                    $cache_options = array( 'cache_dir' => $cache_dir );

                    $cache = new Cache( 'file', $cache_options );

                    if ( file_exists( $cache_dir . '/models' ) )
                    {
                        $cache->flush( 'models' );
                    }
                    if ( file_exists( $cache_dir . '/structures' ) )
                    {
                        $cache->flush( 'structures' );
                    }
                    if ( file_exists( $cache_dir . '/templates' ) )
                    {
                        $cache->flush( 'templates' );
                    }

                    // Also flush response cache
                    if ( file_exists( $cache_dir . '/function_cache' ) )
                    {
                        $cache->flush( 'function_cache' );
                    }

                    $this->SetMessage( 'Changes successfully saved!' );
                }

                $this->mDone = true;
            }
        }

    } // end of member function HandleEvents

    function GetAutoMapping( $concept )
    {
        // Note: This method assumes that mLocalMappingCopy is set and
        //       _LoadIndexOfMappingReferences and _LoadReferences were 
        //       already called on the respective file.

        // Check if there's an equivalent mapping that can be reused

        $concept_id = $concept->GetId();

        if ( is_object( $this->mLocalMappingCopy ) and 
             is_array( $this->mConceptsWithReferences ) and
             isset( $this->mConceptsWithReferences[$concept_id] ) )
        {
            $references = $this->mConceptsWithReferences[$concept_id];

            foreach ( $references as $reference ) // $reference is a concept id
            {
                $equivalent_concept = $this->mLocalMappingCopy->GetConcept( $reference );

                if ( ( ! is_null( $equivalent_concept ) ) and 
                     $equivalent_concept->IsMapped() )
                {
                    return $equivalent_concept->GetMapping(); // get a copy, not reference
                }
            }
        }

        // If could not find an existing reference concept,
        // check local field names

        $mapping = TpConceptMappingFactory::GetInstance( 'SingleColumnMapping' );
        $mapping->SetTablesAndColumns( $this->mTablesAndColumns );

        if ( count( $this->mTablesAndColumns == 1 ) ) // There is only one table
        {
             $tables = array_keys( $this->mTablesAndColumns );

             $mapping->SetTable( $tables[0] );

             foreach ( $this->mTablesAndColumns[$tables[0]] as $field_id => $field ) 
             {
                  // If the table has a field with the same name of the concept
                  if ( strcasecmp( $concept->GetName(), $field->name ) == 0 ) 
                  {
                       $mapping->SetField( $field->name );

                       $field_type = TpConfigUtils::GetFieldType( $field );

                       $mapping->SetLocalType( $field_type );

                       break;
                  }
             }
        }

        return $mapping;

    } // end of member function GetAutoMapping

    function _LoadIndexOfMappingReferences( ) 
    {
        if ( is_array( $this->mAutomapReferencesFiles ) )
        {
            return;
        }

        // Initialize here (when the property is an array this will also 
        // indicate that the index file has been already loaded)
        $this->mAutomapReferencesFiles = array();

        if ( defined( 'TP_INDEX_OF_MAPPING_REFERENCES' ) )
        {
            $fp = TpUtils::GetFileHandle( TP_INDEX_OF_MAPPING_REFERENCES );

            if ( $fp )
            {
                $parser = xml_parser_create();
                xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
                xml_set_object( $parser, $this );
                xml_set_element_handler( $parser, 'StartIndexElement', 'EndIndexElement' );

                while ( $data = fread( $fp, 4096 ) ) 
                {
                    if ( ! xml_parse( $parser, $data, feof( $fp ) ) ) 
                    {
                        $error = sprintf( "Error parsing references index file: %s at line %d",
                                          xml_error_string( xml_get_error_code( $parser ) ),
                                          xml_get_current_line_number( $parser ) );

                        TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_ERROR );
                        return false;
                    }
                }

                fclose( $fp );

                xml_parser_free( $parser );
            }
        }

    } // end of member function _LoadIndexOfMappingReferences

    function StartIndexElement( $parser, $elementName, $attrs )
    {
        if ( strcasecmp( $elementName, 'forSchema' ) == 0 )
        {
            if ( isset( $attrs['namespace'] ) )
            {
                $this->mLastNs = $attrs['namespace'];
            }
        }
        else if ( strcasecmp( $elementName, 'ref' ) == 0 )
        {
            if ( $this->mLastNs and isset( $attrs['location'] ))
            {
                $this->mAutomapReferencesFiles[$this->mLastNs] = $attrs['location'];
            }
        }

    } // end of member function StartIndexElement

    function EndIndexElement( $parser, $elementName )
    {
        // Nothing so far

    } // end of member function EndIndexElement

    function _LoadReferences( $referencesFile ) 
    {
        if ( ! is_null( $this->mConceptsWithReferences ) )
        {
            return;
        }

        // Initialize here (when the property is an array this will also 
        // indicate that the references file has been already loaded)
        $this->mConceptsWithReferences = array();

        $fp = TpUtils::GetFileHandle( $referencesFile );

        if ( $fp )
        {
            $parser = xml_parser_create();
            xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
            xml_set_object( $parser, $this );
            xml_set_element_handler( $parser, 'StartRefElement', 'EndRefElement' );

            while ( $data = fread( $fp, 4096 ) ) 
            {
                if ( ! xml_parse( $parser, $data, feof( $fp ) ) ) 
                {
                    $error = sprintf( "Error parsing references file: %s at line %d",
                                      xml_error_string( xml_get_error_code( $parser ) ),
                                      xml_get_current_line_number( $parser ) );

                    TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_ERROR );
                    return false;
                }
            }

            fclose( $fp );

            xml_parser_free( $parser );
        }

    } // end of member function _LoadReferences

    function StartRefElement( $parser, $elementName, $attrs )
    {
        if ( strcasecmp( $elementName, 'baseConcept' ) == 0 )
        {
            if ( isset( $attrs['id'] ) )
            {
                $this->mBaseConceptId = $attrs['id'];
                $this->mConceptsWithReferences[$this->mBaseConceptId] = array();
            }
        }
        else if ( strcasecmp( $elementName, 'concept' ) == 0 )
        {
            if ( $this->mBaseConceptId and isset( $attrs['id'] ))
            {
                array_push( $this->mConceptsWithReferences[$this->mBaseConceptId], $attrs['id'] );
            }
        }

    } // end of member function StartRefElement

    function EndRefElement( $parser, $elementName )
    {
        // Nothing so far

    } // end of member function EndRefElement

    function GetInputName( $concept, $suffix )
    {
        return strtr( $concept->GetId() . '^' . $suffix, '.', '_' );

    } // end of member function GetInputName

    function GetTablesAndColumns( ) 
    {
        // Needed by Refresh method of SingleColumnMapping class

        return $this->mTablesAndColumns;

    } // end of member function GetTablesAndColumns

    function GetTable( $concept )
    {
        $mapping = $concept->GetMapping();

        if ( $mapping != null )
        {
            $mapped_table = $mapping->GetTable();

            if ( $mapped_table != null )
            {
                return $mapped_table;
            }
        }

        return '0';

    } // end of member function GetTable

    function GetOptions( $id ) 
    {
        $options = array();

        if ( $id == 'handler') 
        {
            $options = array( ''                       => '-- format --',
                              'DarwinSchemaHandler_v1' => 'Old DarwinCore (tied to DiGIR)',
                              'DarwinSchemaHandler_v2' => 'New DarwinCore',
                              'CnsSchemaHandler_v1'    => 'CNS text file',
                              'CnsSchemaHandler_v2'    => 'CNS XML file' );
        }

        return $options;

    } // end of member function GetOptions

} // end of TpMappingForm
?>