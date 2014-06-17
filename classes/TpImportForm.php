<?php
/**
 * $Id: TpImportForm.php 1979 2009-02-25 14:22:41Z rdg $
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

require_once('TpPage.php');
require_once('TpDiagnostics.php');
require_once('TpUtils.php');
require_once('TpResources.php');
require_once('TpResource.php');
require_once('TpContact.php');
require_once('TpRelatedContact.php');
require_once('TpEntity.php');
require_once('TpRelatedEntity.php');
require_once('TpTable.php');
require_once('TpConceptualSchema.php');
require_once('TpConcept.php');
require_once('SingleColumnMapping.php');
require_once('TpFilter.php');

class TpImportForm extends TpPage
{
    var $mResources = array(); // resource code => config file
    var $mHostRelatedEntity;   // TpRelatedEntity generated from providerMeta.xml
    var $mInTags;
    var $mCurrentResource;
    var $mRootTable;
    var $mCurrentTable;
    var $mSchemas = array();   // prefix => TpConceptualSchema obj
    var $mLastSchemaLocation;
    var $mRootBooleanOperator; // Root COP or LOP
    var $mOperatorsStack = array();
    var $mConcatenatedCharData; // SAX character data handlers can be called multiple times even when processing a single string, so we need to concatenate before doing something useful

    function TpImportForm( )
    {
        if ( isset( $_REQUEST['first'] ) )
        {
            $this->mMessage = "Here you can import the configuration from a DiGIR provider. Please type the location\nof the DiGIR &quot;config&quot; directory on the server as well as the names of the &quot;resources&quot; and\n&quot;metadata&quot; configuration files. These files reside in the &quot;config&quot; directory with the\ndefault names indicated below.\n\nIMPORTANT: all imported resources will not be active, you will still need to click on\neach one to complete the configuration process.";
        }

    } // end of member function TpImportForm

    function HandleEvents( ) 
    {
        // Validate parameters

        $digir_config_directory = TpUtils::GetVar( 'config_dir' );

        if ( empty( $digir_config_directory ) )
        {
            $msg = 'Please type the location of the DiGIR &quot;config&quot; '.
                   'directory on the server';
            TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $msg, DIAG_ERROR );
            return;
        }

        $digir_resource_file = TpUtils::GetVar( 'resource_file' );

        if ( empty( $digir_resource_file ) )
        {
            $msg = 'Please type the name of the DiGIR resource configuration file';
            TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $msg, DIAG_ERROR );
            return;
        }

        $digir_metadata_file = TpUtils::GetVar( 'metadata_file' );

        if ( empty( $digir_metadata_file ) )
        {
            $msg = 'Please type the name of the DiGIR metadata configuration file';
            TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $msg, DIAG_ERROR );
            return;
        }

        if ( ! file_exists( $digir_config_directory ) )
        {
            $msg = 'Could not find the DiGIR "config" directory ('.
                   $digir_config_directory.')';
            TpDiagnostics::Append( DC_IO_ERROR, $msg, DIAG_ERROR );
            return;
        }

        $digir_resource_file = $digir_config_directory . '/'. $digir_resource_file;

        if ( ! file_exists( $digir_resource_file ) )
        {
            $msg = 'Could not find the DiGIR resource file ('.
                   $digir_resource_file.')';
            TpDiagnostics::Append( DC_IO_ERROR, $msg, DIAG_ERROR );
            return;
        }

        if ( ! is_readable( $digir_resource_file ) )
        {
            $msg = 'Could not read the DiGIR resource file ('.
                   $digir_resource_file.'). Please check permissions.';
            TpDiagnostics::Append( DC_IO_ERROR, $msg, DIAG_ERROR );
            return;
        }

        $digir_metadata_file = $digir_config_directory . '/'. $digir_metadata_file;

        if ( ! file_exists( $digir_metadata_file ) )
        {
            $msg = 'Could not find the DiGIR metadata file ('.
                   $digir_metadata_file.')';
            TpDiagnostics::Append( DC_IO_ERROR, $msg, DIAG_ERROR );
            return;
        }

        if ( ! is_readable( $digir_metadata_file ) )
        {
            $msg = 'Could not read the DiGIR metadata file ('.
                   $digir_metadata_file.'). Please check permissions.';
            TpDiagnostics::Append( DC_IO_ERROR, $msg, DIAG_ERROR );
            return;
        }

        // Get resources

        if ( ! $this->_LoadResources( $digir_resource_file ) )
        {
            $msg = 'Could not load DiGIR resources from ('.
                   $digir_resource_file.')';
            TpDiagnostics::Append( DC_XML_PARSE_ERROR, $msg, DIAG_ERROR );
            return;
        }

        if ( count( $this->mResources ) == 0 )
        {
            $msg = 'Could not find any DiGIR resource in ('.
                   $digir_resource_file.')';
            TpDiagnostics::Append( DC_GENERAL_ERROR, $msg, DIAG_ERROR );
            return;
        }

        if ( isset( $_REQUEST['process'] ) )
        {
            $selected_resources = TpUtils::GetVar( 'resources', array() );

            if ( count( $selected_resources ) == 0 )
            {
                $msg = 'No resources selected';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $msg, DIAG_ERROR );
                return;
            }

            // Load provider metadata (common to all resources)

            if ( ! $this->_LoadProviderMetadata( $digir_metadata_file ) )
            {
                $msg = 'Could not load DiGIR metadata from ('.
                       $digir_metadata_file.')';
                TpDiagnostics::Append( DC_GENERAL_ERROR, $msg, DIAG_ERROR );
                return;
            }

            // Load each resource configuration

            foreach ( $selected_resources as $resource_code )
            {
                if ( ! array_key_exists( $resource_code, $this->mResources ) )
                {
                    $msg = 'Inconsistent resource code!';
                    TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $msg, DIAG_ERROR );
                    continue;
                }

                $config_file = $digir_config_directory .'/'. $this->mResources[$resource_code];

                if ( ! file_exists( $config_file ) )
                {
                    $msg = 'Could not find resource configuration file ('.
                           $config_file.')';
                    TpDiagnostics::Append( DC_IO_ERROR, $msg, DIAG_ERROR );
                    continue;
                }

                if ( ! is_readable( $config_file ) )
                {
                    $msg = 'Could not read resource configuration file ('.
                           $config_file.'). Please check permissions.';
                    TpDiagnostics::Append( DC_IO_ERROR, $msg, DIAG_ERROR );
                    continue;
                }

                // Force code to be lower case (better since since now it 
                // is going to be part of the service accesspoint)
                if ( ! $this->_LoadResource( strtolower( $resource_code ), $config_file ) )
                {
                    $msg = 'Could not load "'.$resource_code.'" resource';
                    TpDiagnostics::Append( DC_GENERAL_ERROR, $msg, DIAG_ERROR );
                }
                else
                {
                    $r_resources =& TpResources::GetInstance();

                    if ( $this->mCurrentResource->SaveMetadata( false ) and 
                         $this->mCurrentResource->SaveDataSource( false ) and 
                         $this->mCurrentResource->SaveTables( false ) and 
                         $this->mCurrentResource->SaveLocalFilter( false ) and 
                         $this->mCurrentResource->SaveLocalMapping( false ) and 
                         $this->mCurrentResource->SaveSettings( false ) )
                    {
                        $this->mCurrentResource->SetStatus( 'pending' );

                        $r_resources->AddResource( $this->mCurrentResource );

                        if ( ! $r_resources->Save() )
                        {
                            continue;
                        }
                    }
                    else
                    {
                        continue;
                    }

                    $new_code = $this->mCurrentResource->GetCode();

                    $this->mMessage .= "\nImported resource '".$resource_code."'";

                    if ( $new_code != $resource_code )
                    {
                        $this->mMessage .= " with code '".$new_code."'";
                    }
                }
            }
        }

    } // end of member function HandleEvents

    function GetJavascript( )
    {
        return '
  function InvertAll()
  {
    for ( var i=0; i < document.forms[\'import\'].elements.length; i++ )
    {
      if (document.forms[\'import\'].elements[i].type == \'checkbox\' )
      {
        document.forms[\'import\'].elements[i].checked = !(document.forms[\'import\'].elements[i].checked);
      }
    }
  }';

    } // end of member function GetJavascript

    function DisplayHtml( )
    {
        $errors = TpDiagnostics::GetMessages();

        $available_resources = $this->mResources;

        include('TpImportForm.tmpl.php');

    } // end of member function DisplayHtml

    function _LoadResources( $digirResourcesFile )
    {
        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_element_handler( $parser, '_StartResourceElement', '_EndResourceElement' );

        if ( !( $fp = fopen( $digirResourcesFile, 'r' ) ) ) 
        {
            $error = "Could not open file: $digirResourcesFile";
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

        return true;

    } // end of member function _LoadResources

    function _StartResourceElement( $parser, $name, $attrs ) 
    {
        if ( strcasecmp( $name, 'resource' ) == 0 ) 
        {
            if ( isset( $attrs['name'] ) and isset( $attrs['configFile'] ) )
            {
                $this->mResources[$attrs['name']] = $attrs['configFile'];
            }
        }

    } // end of _StartResourceElement

    function _EndResourceElement( $parser, $name ) 
    {

    } // end of _EndResourceElement

    function _LoadProviderMetadata( $digirMetadataFile )
    {
        $this->mInTags = array();

        $entity = new TpEntity();
        
        $this->mHostRelatedEntity = new TpRelatedEntity();
        $this->mHostRelatedEntity->AddRole( 'technical host' );
        $this->mHostRelatedEntity->SetEntity( $entity );

        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_element_handler( $parser, '_StartMetadataElement', '_EndMetadataElement' );
        xml_set_character_data_handler( $parser, '_ConcatenateCharacterData' );

        $this->mConcatenatedCharData = '';

        if ( !( $fp = fopen( $digirMetadataFile, 'r' ) ) ) 
        {
            $error = "Could not open file: $digirMetadataFile";
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

        return true;

    } // end of member function _LoadProviderMetadata

    function _StartMetadataElement( $parser, $name, $attrs ) 
    {
        array_push( $this->mInTags, $name );

        // <contact>
        if ( strcasecmp( $name, 'contact' ) == 0 )
        {
            $contact = new TpContact();

            $related_contact = new TpRelatedContact();

            $related_contact->SetContact( $contact );

            $r_entity =& $this->mHostRelatedEntity->GetEntity();

            $r_entity->AddRelatedContact( $related_contact );
        }

    } // end of _StartMetadataElement

    function _EndMetadataElement( $parser, $name )
    {
        $this->_MetadataCharacterData( $this->mConcatenatedCharData );

        $this->mConcatenatedCharData = '';

        array_pop( $this->mInTags );

    } // end of _EndMetadataElement

    function _ConcatenateCharacterData( $parser, $data )
    {
        $this->mConcatenatedCharData .= $data;

    } // end of _ConcatenateCharacterData

    function _MetadataCharacterData( $data )
    {
        if ( ! strlen( trim( $data ) ) ) 
        {
            return;
        }

        $depth = count( $this->mInTags );

        $in_tag = $this->mInTags[$depth-1];

        // Sub elements of <host>
        if ( $depth > 1 and strcasecmp( $this->mInTags[$depth-2], 'host' ) == 0 )
        {
            $r_entity =& $this->mHostRelatedEntity->GetEntity();

            // host/name => entity name (role = technical host)
            if ( strcasecmp( $in_tag, 'name' ) == 0 ) 
            {
                $r_entity->AddName( $data, null );
            }
            // host/code => entity code
            else if ( strcasecmp( $in_tag, 'code' ) == 0 ) 
            {
                $r_entity->SetAcronym( $data );
            }
            // host/relatedInformation => entity related information
            else if ( strcasecmp( $in_tag, 'relatedInformation' ) == 0 ) 
            {
                $r_entity->SetRelatedInformation( $data );
            }
            // host/abstract => entity description
            else if ( strcasecmp( $in_tag, 'abstract' ) == 0 ) 
            {
                $r_entity->AddDescription( $data, null );
            }
        }
        // Sub elements of <contact>
        else if ( $depth > 1 and strcasecmp( $this->mInTags[$depth-2], 'contact' ) == 0 )
        {
            $r_entity =& $this->mHostRelatedEntity->GetEntity();

            $r_related_contact =& $r_entity->GetLastRelatedContact();

            $r_contact =& $r_related_contact->GetContact();

            // contact/name => contact full name
            if ( strcasecmp( $in_tag, 'name' ) == 0 ) 
            {
                $r_contact->SetFullName( $data );
            }
            // contact/email => contact email
            else if ( strcasecmp( $in_tag, 'emailAddress' ) == 0 ) 
            {
                $r_contact->SetEmail( $data );
            }
            // contact/phone => contact telephone
            else if ( strcasecmp( $in_tag, 'phone' ) == 0 ) 
            {
                $r_contact->SetTelephone( $data );
            }
            // contact/title => contact title
            else if ( strcasecmp( $in_tag, 'title' ) == 0 ) 
            {
                $r_contact->AddTitle( $data, null );
            }
        }

    } // end of _MetadataCharacterData

    function _LoadResource( $resourceCode, $configFile )
    {
        $this->mInTags = array();
        $this->mRootTable = null;
        $this->mCurrentTable = null;
        $this->mRootBooleanOperator = null;
        $this->mOperatorsStack = array();

        $r_resources =& TpResources::GetInstance();

        $raise_error = false;

        $i = 0;

        $resource_code = $resourceCode;

        // Assign new code if this one already exists
        while ( $r_resources->GetResource( $resource_code, $raise_error ) != null )
        {
            ++$i;

            if ( $i == 20 )
            {
                $error = 'Exceeded number of attempts to generate a new resource '.
                         'code to "'.$resourceCode.'"';
                TpDiagnostics::Append( DC_GENERAL_ERROR, $error, DIAG_ERROR );

                return false;
            }

            $resource_code = $resourceCode . "_$i";
        }

        $this->mCurrentResource = new TpResource();
        $this->mCurrentResource->SetCode( $resource_code );

        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_element_handler( $parser, '_StartConfigElement', '_EndConfigElement' );
        xml_set_character_data_handler( $parser, '_ConcatenateCharacterData' );

        $this->mConcatenatedCharData = '';

        if ( !( $fp = fopen( $configFile, 'r' ) ) ) 
        {
            $error = "Could not open file: $configFile";
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

        $r_tables =& $this->mCurrentResource->GetTables();

        $r_tables->SetRootTable( $this->mRootTable );

        $r_settings =& $this->mCurrentResource->GetSettings();

        $timestamp = TpUtils::TimestampToXsdDateTime( TpUtils::MicrotimeFloat() );

        $r_settings->SetModified( $timestamp );

        // Just instantiate the local filter obj because old configuration
        // files may not have it. In this case an empty local filter should be saved.
        $r_local_filter =& $this->mCurrentResource->GetLocalFilter();

        return true;

    } // end of member function _LoadResource

    function _StartConfigElement( $parser, $name, $attrs ) 
    {
        array_push( $this->mInTags, $name );

        $depth = count( $this->mInTags );

        if ( strcasecmp( $name, 'datasource' ) == 0 ) 
        {
            $r_datasource =& $this->mCurrentResource->GetDatasource();

            $r_datasource->LoadDefaults();

            if ( array_key_exists( 'dbtype', $attrs ) )
            {
                $r_datasource->SetDriverName( $attrs['dbtype'] );
            }
            if ( array_key_exists( 'encoding', $attrs ) )
            {
                $r_datasource->SetEncoding( $attrs['encoding'] );
            }
            if ( array_key_exists( 'constr', $attrs ) )
            {
                // This is probably not necessary, but it was used with the XPath class
                $constr = str_replace( array('&quot;', '&amp;'), 
                                       array('"'     , '&'), 
                                       $attrs['constr'] );

                $r_datasource->SetConnectionString( $constr );
            }
            if ( array_key_exists( 'uid', $attrs ) )
            {
                $r_datasource->SetUsername( $attrs['uid'] );
            }
            if ( array_key_exists( 'pwd', $attrs ) )
            {
                $r_datasource->SetPassword( $attrs['pwd'] );
            }
            if ( array_key_exists( 'database', $attrs ) )
            {
                $r_datasource->SetDatabase( $attrs['database'] );
            }
        }
        else if ( strcasecmp( $name, 'table' ) == 0 )
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
        // Sub elements of <filter>
        if ( $depth > 1 and in_array( 'filter', $this->mInTags ) )
        {
            $size = count( $this->mOperatorsStack );

            if ( $size > 0 )
            {
                $current_operator =& $this->mOperatorsStack[$size-1];
            }

            $last_tag = $this->mInTags[$depth-2];

            if ( strcasecmp( $last_tag, 'andNot' ) == 0 or 
                 strcasecmp( $last_tag, 'orNot' ) == 0 )
            {
                // In these conditions, we should be able to assume that 
                // $current_operator is set and is a LOP!

                // Include NOT only for the second term
                if ( count( $current_operator->GetBooleanOperators() ) )
                {
                    $this->_AddOperator( new TpLogicalOperator( LOP_NOT ) );
                }
            }

            if ( strcasecmp( $name, 'equals' ) == 0 )
            {
                $this->_AddOperator( new TpComparisonOperator( COP_EQUALS ) );
            }
            else if ( strcasecmp( $name, 'notEquals' ) == 0 )
            {
                $this->_AddOperator( new TpLogicalOperator( LOP_NOT ) );

                $this->_AddOperator( new TpComparisonOperator( COP_EQUALS ) );
            }
            else if ( strcasecmp( $name, 'lessThan' ) == 0 )
            {
                $this->_AddOperator( new TpComparisonOperator( COP_LESSTHAN ) );
            }
            else if ( strcasecmp( $name, 'lessThanOrEquals' ) == 0 )
            {
                $this->_AddOperator( new TpComparisonOperator( COP_LESSTHANOREQUALS ) );
            }
            else if ( strcasecmp( $name, 'greaterThan' ) == 0 )
            {
                $this->_AddOperator( new TpComparisonOperator( COP_GREATERTHAN ) );
            }
            else if ( strcasecmp( $name, 'greaterThanOrEquals' ) == 0 )
            {
                $this->_AddOperator( new TpComparisonOperator( COP_GREATERTHANOREQUALS ) );
            }
            else if ( strcasecmp( $name, 'like' ) == 0 )
            {
                $this->_AddOperator( new TpComparisonOperator( COP_LIKE ) );
            }
            else if ( strcasecmp( $name, 'in' ) == 0 )
            {
                $this->_AddOperator( new TpComparisonOperator( COP_IN ) );
            }
            else if ( strcasecmp( $name, 'term' ) == 0 )
            {
                if ( isset( $current_operator ) and 
                     $current_operator->GetBooleanType() == COP_TYPE and
                     isset( $attrs['table'] ) )
                {
                    // Only add t_concept for the first term
                    // (concept expressions inside DiGIR "in" operators are redundant)
                    if ( $current_operator->GetComparisonType() != COP_IN or 
                         $current_operator->GetBaseConcept() == null )
                    {
                        $concept = new TpTransparentConcept( $attrs['table'], 
                                                             $attrs['field'],
                                                             $attrs['type'] );

                        $current_operator->SetExpression( new TpExpression( EXP_COLUMN, 
                                                                            $concept ) );
                    }
                }
            }
            else if ( strcasecmp( $name, 'and' ) == 0 )
            {
                $this->_AddOperator( new TpLogicalOperator( LOP_AND ) );
            }
            else if ( strcasecmp( $name, 'or' ) == 0 )
            {
                $this->_AddOperator( new TpLogicalOperator( LOP_OR ) );
            }
            else if ( strcasecmp( $name, 'andNot' ) == 0 )
            {
                $this->_AddOperator( new TpLogicalOperator( LOP_AND ) );
            }
            else if ( strcasecmp( $name, 'orNot' ) == 0 )
            {
                $this->_AddOperator( new TpLogicalOperator( LOP_OR ) );
            }
            else if ( strcasecmp( $name, 'list' ) == 0 )
            {
                // nothing to do here ("list" is part of "in")
            }
        }
        else if ( strcasecmp( $name, 'concepts' ) == 0 )
        {
            foreach ( $attrs as $name => $value )
            {
                if ( substr( $name, 0, 6 ) == 'xmlns:' )
                {
                    $prefix = substr( $name, 6 );

                    $schema = new TpConceptualSchema();

                    $schema->SetNamespace( $value );

                    $schema->SetHandler( 'DarwinSchemaHandler_v1' );

                    $this->mSchemas[$prefix] = $schema;
                }
            }
        }
        else if ( strcasecmp( $name, 'concept' ) == 0 )
        {
            if ( ! array_key_exists( 'name', $attrs ) )
            {
                return;
            }

            $parts = explode( ':', $attrs['name'] );

            if ( count( $parts ) != 2 )
            {
                return;
            }

            $prefix = $parts[0];

            if ( ! isset( $this->mSchemas[$prefix] ) )
            {
                return;
            }

            if ( array_key_exists( 'returnable', $attrs ) and 
                 ! (bool)$attrs['returnable'] )
            {
                // ignore non returnable concepts
                return;
            }

            $concept = new TpConcept();

            $concept->SetName( $parts[1] );

            $id = $this->mSchemas[$prefix]->GetNamespace() . '/' . $parts[1];

            $concept->SetId( $id );

            if ( array_key_exists( 'searchable', $attrs ) )
            {
                $concept->SetSearchable( (bool)$attrs['searchable'] );
            }
            if ( array_key_exists( 'table', $attrs ) and 
                 array_key_exists( 'field', $attrs ) and 
                 array_key_exists( 'type', $attrs ) )
            {
                if ( strpos( $attrs['field'], ',' ) !== false )
                {
                    // ignore multi mapping
                    return;
                }

                if ( $attrs['type'] != 'text' and $attrs['type'] != 'numeric' and 
                     $attrs['type'] != 'date' and $attrs['type'] != 'datetime' )
                {
                    // ignore unknown types
                    return;
                }

                $mapping = new SingleColumnMapping();

                $mapping->SetTable( $attrs['table'] );
                $mapping->SetField( $attrs['field'] );
                $mapping->SetLocalType( $attrs['type'] );

                $concept->SetMapping( $mapping );
            }

            $this->mSchemas[$prefix]->AddConcept( $concept );
        }
        else if ( strcasecmp( $name, 'conceptualSchema' ) == 0 and 
                  array_key_exists( 'schemaLocation', $attrs ) )
        {
            $this->mLastSchemaLocation = $attrs['schemaLocation'];
        }

    } // end of _StartConfigElement

    function _EndConfigElement( $parser, $name )
    {
        $this->_ConfigCharacterData( $this->mConcatenatedCharData );

        $this->mConcatenatedCharData = '';

        if ( strcasecmp( $name, 'table' ) == 0 )
        {
            if ( is_object( $this->mCurrentTable ) )
            {
                $r_current_table =& $this->mTables[$this->mCurrentTable->GetName()];

                $this->mCurrentTable =& $r_current_table->GetParent();
            }
        }
        else if ( strcasecmp( $name, 'filter' ) == 0 )
        {
            $r_local_filter =& $this->mCurrentResource->GetLocalFilter();

            $filter = new TpFilter();

            $filter->SetRootBooleanOperator( $this->mRootBooleanOperator );

            $r_local_filter->SetFilter( $filter );
        }
        else if ( strcasecmp( $name, 'concepts' ) == 0 )
        {
            $r_local_mapping =& $this->mCurrentResource->GetLocalMapping();

            foreach ( $this->mSchemas as $prefix => $schema )
            {
                $r_local_mapping->AddMappedSchema( $schema );
            }
        }
        else if ( strcasecmp( $name, 'equals' )              == 0 or 
                  strcasecmp( $name, 'lessThan' )            == 0 or 
                  strcasecmp( $name, 'lessThanOrEquals' )    == 0 or
                  strcasecmp( $name, 'greaterThan' )         == 0 or 
                  strcasecmp( $name, 'greaterThanOrEquals' ) == 0 or
                  strcasecmp( $name, 'like' )                == 0 or
                  strcasecmp( $name, 'in' )                  == 0 or
                  strcasecmp( $name, 'and' )                 == 0 or
                  strcasecmp( $name, 'or' )                  == 0 )
        {
            array_pop( $this->mOperatorsStack );
        }
        else if ( strcasecmp( $name, 'notEquals' )  == 0 or 
                  strcasecmp( $name, 'andNot' )     == 0 or
                  strcasecmp( $name, 'orNot' )      == 0 )
        {
            array_pop( $this->mOperatorsStack );
            array_pop( $this->mOperatorsStack );
        }

        array_pop( $this->mInTags );

    } // end of _EndConfigElement

    function _ConfigCharacterData( $data )
    {
        $depth = count( $this->mInTags );

        $in_tag = $this->mInTags[$depth-1];

        if ( $depth > 1 and strcasecmp( $in_tag, 'term' ) == 0 )
        {
            $size = count( $this->mOperatorsStack );

            if ( $size > 0 )
            {
                $current_operator =& $this->mOperatorsStack[$size-1];
            }

            if ( isset( $current_operator ) and 
                 $current_operator->GetBooleanType() == COP_TYPE )
            {
                $current_operator->SetExpression( new TpExpression( EXP_LITERAL, $data ) );
            }
        }
        else if ( strlen( trim( $data ) ) ) 
        {
            // Sub elements of <metadata>
            if ( $depth > 1 and strcasecmp( $this->mInTags[$depth-2], 'metadata' ) == 0 )
            {
                $r_metadata =& $this->mCurrentResource->GetMetadata();

                $r_metadata->SetType( 'http://purl.org/dc/dcmitype/Service' );

                $r_metadata->SetId( $this->mCurrentResource->GetCode() );

                $timestamp = TpUtils::TimestampToXsdDateTime( TpUtils::MicrotimeFloat() );

                $r_metadata->SetCreated( $timestamp );

                // metadata/name => resource title
                if ( strcasecmp( $in_tag, 'name' ) == 0 ) 
                {
                    $r_metadata->AddTitle( $data, null );

                    // Maybe here is not the best place to add the host entity, anyway
                    $r_metadata->AddRelatedEntity( $this->mHostRelatedEntity );

                    // If resource name is different from host name
                    // then create a new related entity

                    $host_entity = $this->mHostRelatedEntity->GetEntity();

                    $host_names = $host_entity->GetNames();
                    $host_name = $host_names[0]->GetValue(); // only one name

                    if ( strcasecmp( $host_name, $data ) != 0 )
                    {
                        $entity = new TpEntity();
                        $entity->AddName( $data, '' );
        
                        $related_entity = new TpRelatedEntity();
                        $related_entity->AddRole( 'data supplier' );
                        $related_entity->SetEntity( $entity );

                        $r_metadata->AddRelatedEntity( $related_entity );
                    }
                }
                // metadata/relatedInformation => resource website
                else if ( strcasecmp( $in_tag, 'relatedinformation' ) == 0 ) 
                {
                    $r_last_related_entity =& $r_metadata->GetLastRelatedEntity();

                    $r_entity =& $r_last_related_entity->GetEntity();

                    $r_entity->SetRelatedInformation( $data );
                }
                // metadata/abstract => resource description
                else if ( strcasecmp( $in_tag, 'abstract' ) == 0 ) 
                {
                    $r_metadata->AddDescription( $data, null );
                }
                // metadata/citation => resource biblioraphic citation
                else if ( strcasecmp( $in_tag, 'citation' ) == 0 ) 
                {
                    $r_metadata->AddBibliographicCitation( $data, null );
                }
                // metadata/keywords => resource subject
                else if ( strcasecmp( $in_tag, 'keywords' ) == 0 ) 
                {
                    $r_metadata->AddSubjects( $data, null );
                }
                // metadata/useRestrictions => resource rights
                else if ( strcasecmp( $in_tag, 'useRestrictions' ) == 0 ) 
                {
                    $r_metadata->AddRights( $data, null );
                }
                // metadata/maxSearchResponseRecords => settings maxElementRepetitions
                else if ( strcasecmp( $in_tag, 'maxSearchResponseRecords' ) == 0 ) 
                {
                    $r_settings =& $this->mCurrentResource->GetSettings();

                    $r_settings->SetMaxElementRepetitions( $data, null );
                }
                // metadata/conceptualSchema
                else if ( strcasecmp( $in_tag, 'conceptualSchema' ) == 0 ) 
                {
                    foreach ( $this->mSchemas as $prefix => $schema )
                    {
                        $ns = $schema->GetNamespace();

                        if ( $ns == $data and $this->mLastSchemaLocation != null )
                        {
                            $this->mSchemas[$prefix]->SetLocation( $this->mLastSchemaLocation );
                        }
                    }
                }
            }
            // Sub elements of <contact>
            else if ( $depth > 1 and strcasecmp( $this->mInTags[$depth-2], 'contact' ) == 0 )
            {
                // These references may be needed below (for new contacts)
                $r_metadata =& $this->mCurrentResource->GetMetadata();

                $r_last_related_entity =& $r_metadata->GetLastRelatedEntity();

                $r_entity =& $r_last_related_entity->GetEntity();

                // contact/name => contact full name
                if ( strcasecmp( $in_tag, 'name' ) == 0 ) 
                {
                    $related_contacts = $r_entity->GetRelatedContacts();

                    $this->mNewContact = true;

                    foreach ( $related_contacts as $related_contact )
                    {
                        $contact = $related_contact->GetContact();

                        if ( strcasecmp( $contact->GetFullName(), $data ) == 0 )
                        {
                            $this->mNewContact = false;
                        }
                    }

                    // Only add contact if it is a new name
                    if ( $this->mNewContact )
                    {
                        $contact = new TpContact();

                        $contact->SetFullName( $data );

                        $related_contact = new TpRelatedContact();

                        $related_contact->SetContact( $contact );

                        $r_entity->AddRelatedContact( $related_contact );
                    }
                }
                // contact/email => contact email
                else if ( strcasecmp( $in_tag, 'emailAddress' ) == 0 ) 
                {
                    if ( $this->mNewContact )
                    {
                        $r_related_contact =& $r_entity->GetLastRelatedContact();

                        $r_contact =& $r_related_contact->GetContact();

                        $r_contact->SetEmail( $data );
                    }
                }
                // contact/phone => contact telephone
                else if ( strcasecmp( $in_tag, 'phone' ) == 0 ) 
                {
                    if ( $this->mNewContact )
                    {
                        $r_related_contact =& $r_entity->GetLastRelatedContact();

                        $r_contact =& $r_related_contact->GetContact();

                        $r_contact->SetTelephone( $data );
                    }
                }
                // contact/title => contact title
                else if ( strcasecmp( $in_tag, 'title' ) == 0 ) 
                {
                    if ( $this->mNewContact )
                    {
                        $r_related_contact =& $r_entity->GetLastRelatedContact();

                        $r_contact =& $r_related_contact->GetContact();

                        $r_contact->AddTitle( $data, null );
                    }
                }
            }
        }

    } // end of _ConfigCharacterData

    function _AddOperator( &$operator )
    {
        $size = count( $this->mOperatorsStack );

        if ( ! isset( $this->mRootBooleanOperator ) )
        {
            $this->mRootBooleanOperator =& $operator;
        }
        else 
        {
            $current_operator =& $this->mOperatorsStack[$size-1];

            if ( $current_operator->GetBooleanType() == LOP_TYPE )
            {
                $current_operator->AddBooleanOperator( $operator );
            }
        }

        $this->mOperatorsStack[$size] =& $operator;

    } // end of member function _AddOperator

} // end of TpImportForm
?>
