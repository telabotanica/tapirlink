<?php
/**
 * $Id: TpSettings.php 1997 2009-09-07 22:45:02Z rdg $
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

require_once('TpUtils.php');
require_once('TpConfigUtils.php');
require_once('TpDiagnostics.php');
require_once('TpConceptMapping.php');
require_once( TP_XPATH_LIBRARY );

class TpSettings
{
    var $mMaxElementRepetitions;
    var $mMaxElementLevels;
    var $mLogOnly;
    var $mCaseSensitiveInEquals;
    var $mCaseSensitiveInLike;
    var $mModifier;              // table.field that will update date last modified value
    var $mModifierDatatype;      // local datatype of mModifier field 
                                 // (note: property added in revision 670)
    var $mModified;
    var $mInventoryTemplates = array(); // alias => location
    var $mSearchTemplates = array();    // alias => location
    var $mOutputModels = array();       // alias => location
    var $mCustomOutputModels = true;
    var $mInTags = array();
    var $mIsLoaded = false;

    function TpSettings( ) 
    {

    } // end of member function TpSettings

    function IsLoaded( ) 
    {
        return $this->mIsLoaded;

    } // end of member function IsLoaded

    function LoadDefaults( $force=false )
    {
        if ( $this->mIsLoaded and ! $force )
        {
            return;
        }

        $this->mMaxElementRepetitions = 200;
        $this->mMaxElementLevels      = 20;
        $this->mLogOnly               = 'accepted';
        $this->mCaseSensitiveInEquals = false;
        $this->mCaseSensitiveInLike   = false;
        $this->mModifier              = '';
        $this->mCustomOutputModels    = true;

        $this->mIsLoaded = true;

    } // end of member function LoadDefaults

    function LoadFromSession( $adodbConn ) 
    {
        $this->mMaxElementRepetitions = TpUtils::GetVar( 'max_repetitions', null );
        $this->mMaxElementLevels      = TpUtils::GetVar( 'max_levels', null );
        $this->mLogOnly               = TpUtils::GetVar( 'log_only', null );

        $caseSensitiveInEquals = TpUtils::GetVar( 'case_sensitive_equals', 'false' );

        $this->mCaseSensitiveInEquals = ($caseSensitiveInEquals == 'true') ? true : false;

        $caseSensitiveInLike = TpUtils::GetVar( 'case_sensitive_like', 'false' );

        $this->mCaseSensitiveInLike = ($caseSensitiveInLike == 'true') ? true : false;

        $customOutputModels = TpUtils::GetVar( 'custom_output_models', 'true' );

        $this->mCustomOutputModels = ($customOutputModels == 'true') ? true : false;

        $this->mModifier = TpUtils::GetVar( 'modifier', '' );

        // Update datatype, initializing it with NULL
        $this->mModifierDatatype = null;

        $parts = explode( '.', $this->mModifier );

        // Try catch block
        do {

            if ( count( $parts ) != 2 ) // table.column
            {
                $msg = 'dateLastModified setting could not be split into table/column';
                //TpDiagnostics::Append( DC_CONFIG_FAILURE, $msg, DIAG_WARN );
                break;
            }

            $convert_case = false;

            $columns = $adodbConn->MetaColumns( $parts[0], $convert_case );

            if ( ! is_array( $columns ) )
            {
                $msg = 'Could not get database metadata for dateLastModified setting';
                //TpDiagnostics::Append( DC_CONFIG_FAILURE, $msg, DIAG_WARN );
                break;
            }

            foreach ( $columns as $column )
            {
                if ( $column->name == $parts[1] )
                {
                    $this->mModifierDatatype = TpConfigUtils::GetFieldType( $column );
                    break;
                }
            }

        } while ( false );

        $this->mModified = TpUtils::GetVar( 'modified', '' );

        // Inventory templates
        $this->mInventoryTemplates = array();

        for ( $i = 1; $i < 50; ++$i )
        {
            $alias_param = 'inv_alias_'.$i;
            $location_param = 'inv_loc_'.$i;

            $alias = TpUtils::GetVar( $alias_param );
            $location = TpUtils::GetVar( $location_param );

            if ( $alias and $location )
            {
                $this->AddInventoryTemplate( $alias, $location );
            }
            else
            {
                break;
            }
        }

        $alias_param = 'inv_alias_new';
        $location_param = 'inv_loc_new';

        $alias = TpUtils::GetVar( $alias_param );
        $location = TpUtils::GetVar( $location_param );

        if ( $alias and $location )
        {
            if ( $this->AddInventoryTemplate( $alias, $location ) )
            {
                unset( $_REQUEST[$alias_param] );
                unset( $_REQUEST[$location_param] );
            }
        }

        // Search templates
        $this->mSearchTemplates = array();

        for ( $i = 1; $i < 50; ++$i )
        {
            $alias_param = 'search_alias_'.$i;
            $location_param = 'search_loc_'.$i;

            $alias = TpUtils::GetVar( $alias_param );
            $location = TpUtils::GetVar( $location_param );

            if ( $alias and $location )
            {
                $this->AddSearchTemplate( $alias, $location );
            }
            else
            {
                break;
            }
        }

        $alias_param = 'search_alias_new';
        $location_param = 'search_loc_new';

        $alias = TpUtils::GetVar( $alias_param );
        $location = TpUtils::GetVar( $location_param );

        if ( $alias and $location )
        {
            if ( $this->AddSearchTemplate( $alias, $location ) )
            {
                unset( $_REQUEST[$alias_param] );
                unset( $_REQUEST[$location_param] );
            }
        }

        // Output models
        $this->mOutputModels = array();

        for ( $i = 1; $i < 50; ++$i )
        {
            $alias_param = 'model_alias_'.$i;
            $location_param = 'model_loc_'.$i;

            $alias = TpUtils::GetVar( $alias_param );
            $location = TpUtils::GetVar( $location_param );

            if ( $alias and $location )
            {
                $this->AddOutputModel( $alias, $location );
            }
            else
            {
                break;
            }
        }

        $alias_param = 'model_alias_new';
        $location_param = 'model_loc_new';

        $alias = TpUtils::GetVar( $alias_param );
        $location = TpUtils::GetVar( $location_param );

        if ( $alias and $location )
        {
            if ( $this->AddOutputModel( $alias, $location ) )
            {
                unset( $_REQUEST[$alias_param] );
                unset( $_REQUEST[$location_param] );
            }
        }

        $this->mIsLoaded = true;

    } // end of member function LoadFromSession

    function LoadFromXml( $configFile, $capabilitiesFile, $force=false )
    {
        if ( $this->mIsLoaded and ! $force )
        {
            return true;
        }

        // Load from capabilities file, if specified
        if ( ! empty( $capabilitiesFile ) )
        {
            $parser = xml_parser_create();
            xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
            xml_set_object( $parser, $this );
            xml_set_element_handler( $parser, 'StartElement', 'EndElement' );
            xml_set_character_data_handler( $parser, 'CharacterData' );

            if ( !( $fp = fopen( $capabilitiesFile, 'r' ) ) ) 
            {
                $error = "Could not open file: $file";
                TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );
                return false;
            }

            $this->mCustomOutputModels = false;

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
	}

        // Then load from config file
        $xpr = new XPath();
        $xpr->setVerbose( 1 );
        $xpr->setXmlOption( XML_OPTION_CASE_FOLDING, false );
        $xpr->setXmlOption( XML_OPTION_SKIP_WHITE, true );

        if ( ! $xpr->importFromFile( $configFile ) )
        {
            $error = 'Could not import content from XML file: '.$xpr->getLastError();
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
            return false;
        }
	  
        $path_to_modifier = '/configuration[1]/settings[1]/dateLastModified[1]';

        $attrs = $xpr->getAttributes( $path_to_modifier );

        if ( count( $attrs ) )
        {
            if ( isset( $attrs['fromField'] ) )
            {
                $this->mModifier = $attrs['fromField'];

                if ( isset( $attrs['datatype'] ) )
                {
                    $this->mModifierDatatype = $attrs['datatype'];
                }
            }
            if ( isset( $attrs['fixedValue'] ) )
            {
                $this->mModified = $attrs['fixedValue'];
            }
        }

        $this->mIsLoaded = true;

        return true;

    } // end of member function LoadFromXml

    function StartElement( $parser, $name, $attrs ) 
    {
        array_push( $this->mInTags, $name );

        if ( strcasecmp( $name, 'equals' ) == 0 )
        {
            if ( isset( $attrs['caseSensitive'] ) )
            {
                $value = ( $attrs['caseSensitive'] == 'true' ) ? true : false;

                $this->mCaseSensitiveInEquals = $value;
            }
        }
        else if ( strcasecmp( $name, 'like' ) == 0 )
        {
            if ( isset( $attrs['caseSensitive'] ) )
            {
                $value = ( $attrs['caseSensitive'] == 'true' ) ? true : false;

                $this->mCaseSensitiveInLike = $value;
            }
        }
        else if ( strcasecmp( $name, 'template' ) == 0 )
        {
            $num_elements = count( $this->mInTags );

            if ( isset( $attrs['alias'] ) and isset( $attrs['location'] ) and 
                 isset( $this->mInTags[$num_elements-3] ) )
            {
                if ( strcasecmp( $this->mInTags[$num_elements-3], 'inventory' ) == 0 )
                { 
                    $this->AddInventoryTemplate( $attrs['alias'], $attrs['location'] );
                }
                else if ( strcasecmp( $this->mInTags[$num_elements-3], 'search' ) == 0 )
                {
                    $this->AddSearchTemplate( $attrs['alias'], $attrs['location'] );
                }
            }
        }
        else if ( strcasecmp( $name, 'anyOutputModels' ) == 0 )
        {
            $this->mCustomOutputModels = true;
        }
        else if ( strcasecmp( $name, 'outputModel' ) == 0 )
        {
            $num_elements = count( $this->mInTags );

            if ( isset( $attrs['alias'] ) and isset( $attrs['location'] ) )
            {
                $this->AddOutputModel( $attrs['alias'], $attrs['location'] );
            }
        }

    } // end of member function StartElement

    function EndElement( $parser, $name ) 
    {
        array_pop( $this->mInTags );

    } // end of member function EndElement

    function CharacterData( $parser, $data ) 
    {
        if ( strlen( trim( $data ) ) ) 
        {
            $depth = count( $this->mInTags );
            $inTag = $this->mInTags[$depth-1];

            if ( strcasecmp( $inTag, 'logOnly' ) == 0 ) 
            {
                $this->mLogOnly = trim( $data );
            }
            else if ( strcasecmp( $inTag, 'maxElementRepetitions' ) == 0 )
            {
                $this->mMaxElementRepetitions = (int)trim( $data );
            }
            else if ( strcasecmp( $inTag, 'maxElementLevels' ) == 0 )
            {
                $this->mMaxElementLevels = (int)trim( $data );
            }
        }

    } // end of member function CharacterData

    function Validate( $raiseErrors=true )
    {
        $ret_val = true;

        // Max element repetitions
        if ( strlen( $this->mMaxElementRepetitions ) == 0 ) 
        {
            if ( $raiseErrors )
            {
                $error = 'Please specifiy the maximum element repetitions.';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }
        else
        {
            $max_repetitions = (int)$this->mMaxElementRepetitions;

            if ( $max_repetitions == 0 ) 
            {
                if ( $raiseErrors )
                {
                    $error = 'Search and inventory operations will never return any '.
                             'result if you set maximum element repetitions to zero. '.
                             'Please specify a greater value.';
                    TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
                }
                $ret_val = false;
            }
        }

        // Max element levels
        if ( strlen( $this->mMaxElementLevels ) == 0 )
        {
            if ( $raiseErrors )
            {
                $error = 'Please specifiy the maximum element levels.';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }
        else
        {
            $max_levels = (int)$this->mMaxElementLevels;

            if ( $max_levels < 3 ) 
            {
                if ( $raiseErrors )
                {
                    $error = 'Search operations will not be useful if you set '.
                             'maximum element levels to zero or one. Setting to '.
                             'zero will make search responses return no content. '.
                             'Setting to one will make search responses return only '.
                             'the root element (which by definition cannot be repeatable). '.
                             'Please keep the suggested default value, or use at least '.
                             '5 for this setting.';
                    TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
                }
                $ret_val = false;
            }
        }

        // Log only
        if ( empty( $this->mLogOnly ) ) 
        {
            if ( $raiseErrors )
            {
                $error = 'Please specifiy the log only setting.';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        if ( empty( $this->mModifier ) and empty( $this->mModified ) ) 
        {
            if ( $raiseErrors )
            {
                $error = 'Please specify one of the options for date last modified.';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }
        else if ( ! empty( $this->mModifier ) and ! empty( $this->mModified ) ) 
        {
            if ( $raiseErrors )
            {
                $error = 'Please specify only one of the options for date last modified.';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        if ( ! empty( $this->mModifier ) and ! is_null( $this->mModifierDatatype ) and 
             ( $this->mModifierDatatype != TYPE_DATETIME and 
               $this->mModifierDatatype != TYPE_TEXT ) )
        {
            if ( $raiseErrors )
            {
                $error = 'Unsupported datatype for date last modified field ('.
                         $this->mModifierDatatype .'). It must be either '.TYPE_DATETIME.
                         ' or '.TYPE_TEXT.'.';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        // Validate modified
        $pattern = '[\-]?\d{4}\-\d{2}\-\d{2}T\d{2}:\d{2}:\d{2}([Z|(+|\-)]\d{2}\:\d{2})?';

        if ( strlen( $this->mModified ) and 
             ! preg_match("/^$pattern\$/i", $this->mModified ) )
        {
            if ( $raiseErrors )
            {
                $error = 'Fixed value for "Date last modified" does not match the '.
                         'xsd:dateTime format: [-]CCYY-MM-DDThh:mm:ss[Z|(+|-)hh:mm].';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        // Minimum requirements for the search operation
        if ( empty( $this->mSearchTemplates ) and 
             empty( $this->mOutputModels ) and
             ! $this->mCustomOutputModels )
        {
            if ( $raiseErrors )
            {
                $error = 'In order to support search operations, you must declare at '.
                         'least one search template or one output model or accept '.
                         'custom output models.';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        return $ret_val;

    } // end of member function Validate

    function GetConfigXml( ) 
    {
        $xml = "\t<settings>\n";

        $xml .= "\t\t<dateLastModified";

        if ( ! empty( $this->mModifier ) )
        {
            $xml .= ' fromField="'.$this->mModifier.'"';

            if ( ! is_null( $this->mModifierDatatype ) )
            {
                $xml .= ' datatype="'.$this->mModifierDatatype.'"';
            }
        }
        else
        {
            $xml .= ' fixedValue="'.$this->mModified.'"';
        }

        $xml .= "/>\n";

        $xml .= "\t</settings>\n";

        return $xml;

    } // end of member function GetConfigXml

    function SetMaxElementRepetitions( $max )
    {
        $this->mMaxElementRepetitions = $max;

    } // end of member function SetMaxElementRepetitions

    function GetMaxElementRepetitions( )
    {
        return $this->mMaxElementRepetitions;

    } // end of member function GetMaxElementRepetitions

    function GetMaxElementLevels( )
    {
        return $this->mMaxElementLevels;

    } // end of member function GetMaxElementLevels

    function GetLogOnly( )
    {
        return $this->mLogOnly;

    } // end of member function GetLogOnly

    function GetCaseSensitiveInEquals( )
    {
        return $this->mCaseSensitiveInEquals;

    } // end of member function GetCaseSensitiveInEquals

    function GetCaseSensitiveInLike( )
    {
        return $this->mCaseSensitiveInLike;

    } // end of member function GetCaseSensitiveInLike

    function GetModifier( ) 
    {
        return $this->mModifier;

    } // end of member function GetModifier

    function GetModifierDatatype( ) 
    {
        return $this->mModifierDatatype;

    } // end of member function GetModifierDatatype

    function GetModified( ) 
    {
        return $this->mModified;

    } // end of member function GetModified

    function SetModified( $modified ) 
    {
        $this->mModified = $modified;

    } // end of member function SetModified

    function GetCustomOutputModelsAcceptance( ) 
    {
        return $this->mCustomOutputModels;

    } // end of member function GetCustomOutputModelsAcceptance

    function SetCustomOutputModelsAcceptance( $accept ) 
    {
        $this->mCustomOutputModels = $accept;

    } // end of member function SetCustomOutputModelsAcceptance

    function AddInventoryTemplate( $alias, $location ) 
    {
        if ( strlen( $alias ) == 0 or strlen( $location ) == 0 )
        {
            return false;
        }

        if ( ! TpUtils::IsUrl( $location ) )
        {
            $error = 'Inventory template location ('.$location.') is not a URL';
            TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );

            return false;
        }

        $this->mInventoryTemplates[$alias] = $location;

        return true;

    } // end of member function AddInventoryTemplate

    function AddSearchTemplate( $alias, $location ) 
    {
        if ( strlen( $alias ) == 0 or strlen( $location ) == 0 )
        {
            return false;
        }

        if ( ! TpUtils::IsUrl( $location ) )
        {
            $error = 'Search template location ('.$location.') is not a URL';
            TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );

            return false;
        }

        $this->mSearchTemplates[$alias] = $location;

        return true;

    } // end of member function AddSearchTemplate

    function AddOutputModel( $alias, $location ) 
    {
        if ( strlen( $alias ) == 0 or strlen( $location ) == 0 )
        {
            return false;
        }

        if ( ! TpUtils::IsUrl( $location ) )
        {
            $error = 'Output model location ('.$location.') is not a URL';
            TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );

            return false;
        }

        $this->mOutputModels[$alias] = $location;

        return true;

    } // end of member function AddOutputModel

    function GetInventoryTemplate( $alias ) 
    {
        if ( isset( $this->mInventoryTemplates[$alias] ) )
        {
            return $this->mInventoryTemplates[$alias];
        }

        return null;

    } // end of member function GetInventoryTemplate

    function GetSearchTemplate( $alias ) 
    {
        if ( isset( $this->mSearchTemplates[$alias] ) )
        {
            return $this->mSearchTemplates[$alias];
        }

        return null;

    } // end of member function GetSearchTemplate

    function GetOutputModel( $alias ) 
    {
        if ( isset( $this->mOutputModels[$alias] ) )
        {
            return $this->mOutputModels[$alias];
        }

        return null;

    } // end of member function GetOutputModel

    function GetInventoryTemplates( ) 
    {
        return $this->mInventoryTemplates;

    } // end of member function GetInventoryTemplates

    function GetSearchTemplates( ) 
    {
        return $this->mSearchTemplates;

    } // end of member function GetSearchTemplates

    function GetOutputModels( ) 
    {
        return $this->mOutputModels;

    } // end of member function GetOutputModels

    function SupportOutputModels( ) 
    {
        return $this->mCustomOutputModels or ! empty( $this->mOutputModels );

    } // end of member function SupportOutputModels

    function GetInventoryTemplatesXml( ) 
    {
        $xml = ( count( $this->mInventoryTemplates ) ) ? "<templates>\n" : "\n";

        foreach ( $this->mInventoryTemplates as $alias => $location )
        {
            $alias = TpUtils::EscapeXmlSpecialChars( $alias );
            $location = TpUtils::EscapeXmlSpecialChars( $location );

            $xml .= "\t\t\t\t\t".'<template location="'.$location.'" alias="'.$alias.'"/>'."\n";
        }

        $xml .= ( count( $this->mInventoryTemplates ) ) ? "\t\t\t\t</templates>\n" : '';

        return $xml;

    } // end of member function GetInventoryTemplatesXml

    function GetSearchTemplatesXml( ) 
    {
        $xml = ( count( $this->mSearchTemplates ) ) ? "<templates>\n" : "\n";

        foreach ( $this->mSearchTemplates as $alias => $location )
        {
            $alias = TpUtils::EscapeXmlSpecialChars( $alias );
            $location = TpUtils::EscapeXmlSpecialChars( $location );

            $xml .= "\t\t\t\t".'<template location="'.$location.'" alias="'.$alias.'"/>'."\n";
        }

        $xml .= ( count( $this->mSearchTemplates ) ) ? "\t\t\t</templates>\n" : '';

        return $xml;

    } // end of member function GetSearchTemplatesXml

    function GetOutputModelsXml( ) 
    {
        if ( empty( $this->mOutputModels ) and ! $this->mCustomOutputModels )
        {
            return "\n";
        }

        $xml = '<outputModels>'."\n";

        // Declared output models
        if ( count( $this->mOutputModels ) )
        {
            $xml .= "\t\t\t\t<knownOutputModels>\n";

            foreach ( $this->mOutputModels as $alias => $location )
            {
                $alias = TpUtils::EscapeXmlSpecialChars( $alias );
                $location = TpUtils::EscapeXmlSpecialChars( $location );

                $xml .= "\t\t\t\t\t".'<outputModel location="'.$location.'" alias="'.$alias.'"/>'."\n";
            }

            $xml .= "\t\t\t\t</knownOutputModels>\n";
        }
        else
        {
            $xml .= "\n";
        }

        // Custom output models
        if ( $this->mCustomOutputModels )
        {
                $xml .= "\t\t\t\t".'<anyOutputModels>'."\n";
                $xml .= "\t\t\t\t\t".'<responseStructure>'."\n";
                $xml .= "\t\t\t\t\t\t".'<basicSchemaLanguage/>'."\n";
                $xml .= "\t\t\t\t\t".'</responseStructure>'."\n";
                $xml .= "\t\t\t\t".'</anyOutputModels>'."\n";
        }

        $xml .= "\t\t\t".'</outputModels>'."\n";

        return $xml;

    } // end of member function GetOutputModelsXml

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
      $this->ResetConnection();

      return array( 'mMaxElementRepetitions', 'mMaxElementLevels', 'mLogOnly',
                    'mCaseSensitiveInEquals', 'mCaseSensitiveInLike', 'mModifier',
                    'mModified', 'mIsLoaded', 'mInventoryTemplates', 'mSearchTemplates',
                    'mOutputModels', 'mCustomOutputModels' );

    } // end of member function __sleep

} // end of TpSettings
?>