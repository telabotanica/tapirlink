<?php
/**
 * $Id: TpOutputModel.php 1985 2009-03-21 20:26:36Z rdg $
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

require_once('TpDiagnostics.php');
require_once('TpUtils.php');
require_once('TpExpression.php');
require_once('TpResponseStructure.php');
require_once('TpResources.php');
require_once('phpxsd/XsNamespaceManager.php');
require_once('pear/Cache.php');

class TpOutputModel
{
    var $mRevision = '$Revision: 1985 $';
    var $mInTags = array(); // name element stack during XML parsing
    var $mLabel;
    var $mDocumentation;
    var $mLocation;
    var $mRootElement;
    var $mIndexingElement;
    var $mMapping = array(); // node path => array of TpExpression
    var $mAutomapping = false;
    var $mCurrrentMappingPath;
    var $mResponseStructure;
    var $mNamespaces = array(); // Possible namespaces declared in the output model element
    var $mLoadedFromCache = true;

    function TpOutputModel( )
    {
        // Constructor will only be called when the output model  
        // is not loaded from cache
        $this->mLoadedFromCache = false;

    } // end of member function TpOutputModel

    function GetRevision( )
    {
        $revision_regexp = '/^\$'.'Revision:\s(\d+)\s\$$/';

        if ( preg_match( $revision_regexp, $this->mRevision, $matches ) )
        {
            return (int)$matches[1];
        }

        return null;

    } // end of member function GetRevision

    function Parse( $location )
    {
        // This is a workaround because when browsing the provider 
        // through XSLT, some parameter values are not being escaped.
        // In the future this should be solved in the xsl files.
        $location = strtr( $location, ' ', '+' );

        $this->mLocation = $location;

        $parser = xml_parser_create_ns();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_element_handler( $parser, 'StartElement', 'EndElement' );
        xml_set_character_data_handler( $parser, 'CharacterData' );
        xml_set_start_namespace_decl_handler( $parser, 'DeclareNamespace' );

        $fp = TpUtils::GetFileHandle( $location );

        if ( ! is_resource( $fp ) )
        {
            $error = 'Could not open output model file: '.$location;
            TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );
            return false;
        }
      
        while ( $data = fread( $fp, 4096 ) ) 
        {
            if ( ! xml_parse( $parser, $data, feof( $fp ) ) ) 
            {
                $error = sprintf( "Error parsing output model: %s at line %d",
                                  xml_error_string( xml_get_error_code( $parser ) ),
                                  xml_get_current_line_number( $parser ) );

                TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_FATAL );
                return false;
            }
        }

        fclose( $fp );

        xml_parser_free( $parser );

        return true;

    } // end of member function Parse

    function StartElement( $parser, $qualified_name, $attrs )
    {
        global $g_dlog;

        $name = TpUtils::GetUnqualifiedName( $qualified_name );

        array_push( $this->mInTags, strtolower( $name ) );

        $depth = count( $this->mInTags );

        // <outputModel>
        if ( strcasecmp( $name, 'outputmodel' ) == 0 )
        {
            // Get possible prefix declarations
            $r_namespace_manager =& XsNamespaceManager::GetInstance();

            $this->mNamespaces = $r_namespace_manager->GetFlaggedNamespaces( $parser, 'm' );
        }
        // <structure>
        else if ( strcasecmp( $name, 'structure' ) == 0 )
        {
            $g_dlog->debug( '[Response Structure]' );
        }
        // <rootElement>
        else if ( strcasecmp( $name, 'rootElement' ) == 0 )
        {
            if ( isset( $attrs['name'] )  )
            {
                $this->mRootElement = $attrs['name'];
            }
            else
            {
                $error = 'Missing attribute "name" in <rootElement>';
                TpDiagnostics::Append( DC_INVALID_REQUEST, $error, DIAG_ERROR );
            }
        }
        // <indexingElement>
        else if ( strcasecmp( $name, 'indexingElement' ) == 0 )
        {
            if ( isset( $attrs['path'] )  )
            {
                $this->mIndexingElement = $attrs['path'];
            }
            else
            {
                $error = 'Missing attribute "path" in <indexingElement>';
                TpDiagnostics::Append( DC_INVALID_REQUEST, $error, DIAG_ERROR );
            }
        }
        // <mapping>
        else if ( strcasecmp( $name, 'mapping' ) == 0 )
        {
            $automapping = TpUtils::GetInArray( $attrs, 'automapping', false );

            if ( $automapping == 'true' or (int)$automapping == 1 )
            {
                $automapping = true;
            }
            else
            {
                $automapping = false;
            }

            $this->mAutomapping = $automapping;
        }
        else if ( $depth > 1 )
        {
            // <node> element whose parent is <mapping>
            if ( $this->mInTags[$depth-2] == 'mapping' and
                 strcasecmp( $name, 'node' ) == 0 )
            {
                if ( isset( $attrs['path'] )  )
                {
                    $this->mMapping[$attrs['path']] = array();
                    $this->mCurrentMappingPath = $attrs['path'];
                }
                else
                {
                    $error = 'Missing attribute "path" in <node> element';
                    TpDiagnostics::Append( DC_INVALID_REQUEST, $error, DIAG_ERROR );
                }
            }
            // parent is <node>
            else if ( $this->mInTags[$depth-2] == 'node' )
            {
                if ( strcasecmp( $name, 'concept' ) == 0 )
                {
                    if ( isset( $this->mCurrentMappingPath ) and isset( $attrs['id'] ) )
                    {
                        $required = false;

                        if ( isset( $attrs['required'] ) and 
                             ( strcmp( $attrs['required'], 'true' ) == 0 or 
                               $attrs['required'] == '1' ) )
                        {
                            $required = true;
                        }

                        array_push( $this->mMapping[$this->mCurrentMappingPath],
                                    new TpExpression( EXP_CONCEPT, $attrs['id'], $required ) );
                    }
                }
                else if ( strcasecmp( $name, 'literal' ) == 0 )
                {
                    if ( isset( $this->mCurrentMappingPath ) and 
                         isset( $attrs['value'] ) )
                    {
                        array_push( $this->mMapping[$this->mCurrentMappingPath],
                                    new TpExpression( EXP_LITERAL, $attrs['value'] ) );
                    }
                }
                else if ( strcasecmp( $name, 'variable' ) == 0 )
                {
                    if ( isset( $this->mCurrentMappingPath ) and 
                         isset( $attrs['name'] ) )
                    {
                        $required = false;

                        if ( isset( $attrs['required'] ) and 
                             ( strcmp( $attrs['required'], 'true' ) == 0 or 
                               $attrs['required'] == '1' ) )
                        {
                            $required = true;
                        }

                        array_push( $this->mMapping[$this->mCurrentMappingPath],
                                    new TpExpression( EXP_VARIABLE, $attrs['name'], $required ) );
                    }
                }
            }
            // inside <structure>
            else if ( in_array( 'structure', $this->mInTags ) )
            {
                if ( $this->mInTags[$depth-2] == 'structure' and 
                     strcasecmp( $name, 'schema' ) == 0 and 
                     isset( $attrs['location'] ) )
                {
                    $g_dlog->debug( 'Location '.$attrs['location'] );

                    $this->LoadResponseStructure( $attrs['location'] );
                }
                else
                {
                    // Delegate to response structure parser
                    if ( ! is_object( $this->mResponseStructure ) )
                    {
                        $g_dlog->debug( 'Delegating parsing events' );

                        $this->mResponseStructure = new TpResponseStructure();
                    }

                    $this->mResponseStructure->StartElement( $parser, $qualified_name, $attrs );
                }
            }
        }

    } // end of member function StartElement

    function EndElement( $parser, $qualified_name ) 
    {
        $name = TpUtils::GetUnqualifiedName( $qualified_name );

        $depth = count( $this->mInTags );

        // inside <schema>
        if ( in_array( 'schema', $this->mInTags ) and 
             strcasecmp( $name, 'schema' ) != 0 and 
             is_object( $this->mResponseStructure ) )
        {
            $this->mResponseStructure->EndElement( $parser, $qualified_name );
        }

        array_pop( $this->mInTags );

    } // end of member function EndElement

    function CharacterData( $parser, $data ) 
    {

    } // end of member function CharacterData

    function DeclareNamespace( $parser, $prefix, $uri ) 
    {
        $path = implode( '/', $this->mInTags );

        $flag = null;

        if ( $path == '' )
        {
            // Namespaces declared in output model.
            $flag = 'm';
        }

        $r_namespace_manager =& XsNamespaceManager::GetInstance();

        $r_namespace_manager->AddNamespace( $parser, $prefix, $uri, $flag );

    } // end of member function DeclareNamespace

    function LoadResponseStructure( $location )
    {
        global $g_dlog;

        // Here response structure must be specified as an URL
        // (it's important to check this also for security reasons since
        // "fopen" is used to read templates!)
        if ( ! TpUtils::IsUrl( $location ) )
        {
            $error = 'Response schema is not a URL.';
            TpDiagnostics::Append( DC_INVALID_REQUEST, $error, DIAG_FATAL );

            return false;
        }

        $loaded_from_cache = false;

        // If cache is enabled
        if ( TP_USE_CACHE and TP_RESP_STRUCTURE_CACHE_LIFE_SECS )
        {
            $r_resources =& TpResources::GetInstance();

            $cache_dir = TP_CACHE_DIR . '/' . $r_resources->GetCurrentResourceCode();

            $cache_options = array( 'cache_dir' => $cache_dir );

            $subdir = 'structures';

            $cache = new Cache( 'file', $cache_options );
            $cache_id = $cache->generateID( $location );
            $cached_data = $cache->get( $cache_id, $subdir );

            if ( $cached_data and ! $cache->isExpired( $cache_id, $subdir ) )
            {
                $g_dlog->debug( 'Unserializing response structure from cache' );

                // Check if serialized object has the mRevision property.
                // If not, this means that the cached object was based on 
                // the old TpResponseStructure class definition, so we need 
                // to discard it.
                if ( strpos( $cached_data, ':"mRevision"' ) === false )
                {
                    $g_dlog->debug( 'Detected obsolete serialized response structure' );

                    if ( ! $cache->remove( $cache_id, $subdir ) )
                    {
                        $g_dlog->debug( 'Could not remove response structure from cache' );
                    }
                    else
                    {
                        $g_dlog->debug( 'Removed response structure from cache' );
                    }
                }
                else
                {
                    $this->mResponseStructure = unserialize( $cached_data );

                    if ( ! $this->mResponseStructure )
                    {
                        $g_dlog->debug( 'Could not unserialize response structure from cache' );

                        if ( ! $cache->remove( $cache_id, $subdir ) )
                        {
                            $g_dlog->debug( 'Could not remove response structure from cache' );
                        } 
                        else
                        {
                            $g_dlog->debug( 'Removed response structure from cache' );
                        } 
                    }
                    else
                    {
                        // Check if unserialized object has correct version.
                        // Should always pass this condition. It is here in case
                        // there is any change in TpResponseStructure that may
                        // affect caching in the future.
                        $revision = $this->mResponseStructure->GetRevision();

                        if ( $revision > 557 )
                        {
                            $g_dlog->debug( 'Loaded response structure from cache' );

                            $loaded_from_cache = true;
                        }
                        else
                        {
                            $g_dlog->debug( 'Incorrect serialized response structure revision ('.$revision.')' );

                            if ( ! $cache->remove( $cache_id, $subdir ) )
                            {
                                $g_dlog->debug( 'Could not remove response structure from cache' );
                            }
                            else
                            {
                                $g_dlog->debug( 'Removed response structure from cache' );
                            }
                        }
                    }
                }
            }
        }

        if ( ! $loaded_from_cache )
        {
            $g_dlog->debug( 'Retrieving and parsing response structure' );

            $this->mResponseStructure = new TpResponseStructure();

            $this->mResponseStructure->SetFileOpenCallback( array( 'TpUtils', 'GetFileHandle' ) );

            if ( ! $this->mResponseStructure->Parse( $location ) )
            {
                return false;
            }
        }
 
        return true;

    } // end of member function LoadResponseStructure

    function GetLocation( )
    {
        return $this->mLocation;

    } // end of member function GetLocation

    function GetLabel( )
    {
        return $this->mLabel;

    } // end of member function GetLabel

    function GetDocumentation( )
    {
        return $this->mDocumentation;

    } // end of member function GetDocumentation

    function GetRootElement( )
    {
        return $this->mRootElement;

    } // end of member function GetRootElement

    function GetIndexingElement( )
    {
        return $this->mIndexingElement;

    } // end of member function GetIndexingElement

    function GetMapping( )
    {
        return $this->mMapping;

    } // end of member function GetMapping

    function GetMappingForNode( $path, $addIfMissing=false )
    {
        if ( count( $this->mMapping ) and isset( $this->mMapping[$path] ) )
        {
            return $this->mMapping[$path];
        }

        if ( $this->mAutomapping and $addIfMissing )
        {
            $ns = $this->mResponseStructure->GetTargetNamespace();

            $path_without_prefixes = ereg_replace( '/[^/]*:', '/', $path );

            $concept_id = $ns . $path_without_prefixes;

            $expression = new TpExpression( EXP_CONCEPT, $concept_id, false );

            $this->mMapping[$path] = array( $expression );

            return array( $expression );
        }

        return null;

    } // end of member function GetMappingForNode

    function GetAutomapping( )
    {
        return $this->mAutomapping;

    } // end of member function GetAutomapping

    function &GetResponseStructure( )
    {
        return $this->mResponseStructure;

    } // end of member function GetResponseStructure

    function GetPrefix( $ns )
    {
        for ( $i = 0; $i < count( $this->mNamespaces ); ++$i )
        {
            if ( $this->mNamespaces[$i]->GetUri() == $ns )
            {
                return $this->mNamespaces[$i]->GetPrefix();
            }
        }

        return '';

    } // end of member function GetPrefix

    function GetDeclaredNamespaces( )
    {
        return $this->mNamespaces;

    } // end of member function GetDeclaredNamespaces

    function IsValid( )
    {
        if ( ! isset( $this->mIndexingElement ) )
        {
            $error = 'No indexing element defined in response structure';
            TpDiagnostics::Append( DC_INVALID_REQUEST, $error, DIAG_ERROR );
 
            return false;
        }

        // Note: the checking that the indexing element actually points to an 
        //       element in the structure is performed by TpSchemaInspector

        if ( ( ! count( $this->mMapping ) ) and ! $this->mAutomapping )
        {
            $error = 'No mapping section defined in response structure';
            TpDiagnostics::Append( DC_INVALID_REQUEST, $error, DIAG_ERROR );
 
            return false;
        }

        // Note: TapirLink does not check if mapped nodes actually point to 
        //       nodes in the structure. It's not its core business to spend
        //       time with these things.


        // Note: TpSchemaInspector already checks if there is at least one 
        //       concrete global element in the response structure.

        // Note: TapirLink will ignore errors in the partial parameter (meaning
        //       that it will not check that the partial path coresponds to an 
        //       actual path in the structure). In that case the path will be ignored.

        return true;

    } // end of member function IsValid

    function LoadedFromCache( ) 
    {
        return $this->mLoadedFromCache;

    } // end of member function LoadedFromCache

    function Cache( ) 
    {
        global $g_dlog;

        if ( TP_USE_CACHE and TP_OUTPUT_MODEL_CACHE_LIFE_SECS )
        {
            $r_resources =& TpResources::GetInstance();

            $cache_dir = TP_CACHE_DIR . '/' . $r_resources->GetCurrentResourceCode();

            $cache_options = array( 'cache_dir' => $cache_dir );

            $subdir = 'models';

            $cache = new Cache( 'file', $cache_options );

            $location = $this->GetLocation();

            if ( empty( $location ) )
            {
                return;
            }

            $cache_id = $cache->generateID( $location );

            if ( ( ! $cache->isCached( $cache_id, $subdir ) ) or 
                 (  $cache->isExpired( $cache_id, $subdir ) ) )
            {
                $cache_expires = TP_OUTPUT_MODEL_CACHE_LIFE_SECS;
                $cached_data = serialize( $this );

                $cache->save( $cache_id, $cached_data, $cache_expires, $subdir );

                $g_dlog->debug( 'Caching output model with id generated from "'.
                                $location.'"' );
            }
        }

    } // end of member function Cache

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array( 'mRevision', 'mLocation', 'mRootElement', 'mIndexingElement', 
                      'mMapping', 'mAutomapping', 'mResponseStructure', 'mNamespaces' );

    } // end of member function __sleep

} // end of TpOutputModel
?>