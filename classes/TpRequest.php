<?php
/**
 * $Id: TpRequest.php 637 2008-04-15 12:38:44Z rdg $
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
 * @author Dave Vieglais (Biodiversity Research Center, University of Kansas)
 * 
 */

require_once('TpDiagnostics.php');
require_once('TpResources.php');
require_once('TpUtils.php');
require_once('phpxsd/XsNamespaceManager.php');

// These are needed for the unserialize method call
require_once('TpSearchParameters.php');
require_once('TpInventoryParameters.php');

class TpRequest 
{
    var $mResourceCode;
    var $mrResource;
    var $mInTags = array();    // element stack (names) during XML parsing
    var $mSequence = array();  // element sequence per level during XML parsing
    var $mClientAccesspoint;
    var $mOperation;
    var $mXslt;
    var $mXsltApply;
    var $mLogOnly = false;
    var $mCount = false;       // only for search and inventory
    var $mStart = 0;           // only for search and inventory
    var $mLimit = null;        // only for search and inventory
    var $mEnvelope = true;        // only for search
    var $mOmitNamespaces = false; // only for search
    var $mOperationParameters;
    var $mTemplate;
    var $mLoadedTemplateFromCache = false;
    var $mRequestEncoding = 'xml';

    function TpRequest()
    {

    } // end of member function TpRequest

    function ExtractResourceCode( $uri=null )
    {
        if ( $uri == null and isset( $_SERVER['REQUEST_URI'] ) )
        {
            $uri = $_SERVER['REQUEST_URI'];
        }

        $matches = array();

        if ( ! preg_match( '/^.*\/tapir.php\/([^\/\?]+)[\/\?]?.*$/i', $uri, $matches ) )
        {
            if ( isset( $_REQUEST['dsa'] ) )
            {
                $this->mResourceCode = $_REQUEST['dsa'];

                return true;
            }

            return false;
        }

        if ( ! isset( $matches[1] ) )
        {
            if ( isset( $_REQUEST['dsa'] ) )
            {
                $this->mResourceCode = $_REQUEST['dsa'];

                return true;
            }

            return false;
        }

        $this->mResourceCode = $matches[1];

        return true;

    } // end of member function ExtractResourceCode

    /**
     * Initialize the parameter array.  The parameter array is used to control the 
     * operation and response of the DiGIR provider. The array is populated by 
     * loading key+value pairs from the URL, from POSTed values, or XML
     * request document.
     */
    function InitializeParameters()
    {
        // Load resource object
        if ( $this->mResourceCode == null and ! $this->ExtractResourceCode() )
        {
            $error = 'Resource not specified in request URL.';
            TpDiagnostics::Append( DC_INVALID_REQUEST, $error, DIAG_FATAL );
            return false;
        }

        $r_resources =& TpResources::GetInstance();

        $raise_errors = false;
        $this->mrResource =& $r_resources->GetResource( $this->mResourceCode, 
                                                        $raise_errors );

        if ( is_null( $this->mrResource ) )
        {
            $error = 'Resource "'.$this->mResourceCode.'" not found.';
            TpDiagnostics::Append( DC_RESOURCE_NOT_FOUND, $error, DIAG_FATAL );
            return false;
        }

        if ( $this->mrResource->GetStatus() != 'active' )
        {
            $error = 'Resource "'.$this->mResourceCode.'" is not active.';
            TpDiagnostics::Append( DC_RESOURCE_NOT_FOUND, $error, DIAG_FATAL );
            return false;
        }

        // If there is a 'request' parameter (either with GET or POST)
        // then load it as an XML or as an URL pointing to an XML
        if ( isset( $_REQUEST['request'] ) )
        {
            $request = urldecode( trim( stripcslashes( $_REQUEST['request'] ) ) );

            // request can be an URL encoded XML or a URL pointing to 
            // a remote XML file
            return $this->LoadXmlParameters( $request );
        }

        // If method is GET, or if method is POST with parameters
        if ( $_SERVER['REQUEST_METHOD'] == 'GET' or count( $_POST ) )
        {
            // Load KVP parameters
            return $this->LoadKvpParameters();
        }

        // Here method is POST without parameters, so try loading
        // the XML from raw POST data
        return $this->LoadXmlParameters( 'php://input' );

    } // end of member function InitializeParameters

    function LoadKvpParameters()
    {
        global $g_dlog;

        $this->mRequestEncoding = 'kvp';

        // Stash request to a file

        if ( TP_STASH_REQUEST )
        {
            $fnam = TP_DEBUG_DIR.'/'.TP_STASH_FILE;

            $fp = fopen( $fnam, 'w' );

            if ( $fp )
            {
                fwrite( $fp, sprintf( "REMOTE ADDRESS: %s\n", $_SERVER['REMOTE_ADDR'] ) );
                fwrite( $fp, sprintf( "REQUEST URI: %s\n", $_SERVER['REQUEST_URI'] ) );
                fwrite( $fp, sprintf( "AGENT: %s\n", $_SERVER['HTTP_USER_AGENT'] ) );
                fwrite( $fp, sprintf( "VARS: %s\n", TpUtils::DumpArray( $_REQUEST ) ) );
                fflush( $fp );
                fclose( $fp );
            }
            else
            {
                 $msg = "Could not stash request in $fnam";
                 TpDiagnostics::Append( DC_IO_ERROR, $msg, DIAG_WARN );
            }
        }

        // Load params

        $this->mClientAccesspoint = TpUtils::GetVar( 'source-ip', $_SERVER['REMOTE_ADDR'] );

        $this->mOperation = strtolower( TpUtils::GetVar( 'op', 'metadata' ) );

        // Operations can be abbreviated - in this case, standardise
        switch ( $this->mOperation )
        {
            case 'p':
                $this->mOperation = 'ping';
                break;
            case 'm':
                $this->mOperation = 'metadata';
                break;
            case 'c':
                $this->mOperation = 'capabilities';
                break;
            case 's':
                $this->mOperation = 'search';
                break;
            case 'i':
                $this->mOperation = 'inventory';
                break;
        }

        $log_only = TpUtils::GetVar( 'log-only', false );

        if ( $log_only == 'true' or (int)$log_only == 1 )
        {
            $g_dlog->debug( 'Detected log-only request' );
            $this->mLogOnly = true;
        }

        $this->mXslt = TpUtils::GetVar( 'xslt', null );
        $this->mXsltApply = TpUtils::GetVar( 'xslt-apply', false );

        if ( $this->mOperation == 'search' or $this->mOperation == 'inventory' )
        {
            // Global Parameters

            // Count
            if ( isset( $_REQUEST['count'] ) )
            {
                $count = $_REQUEST['count'];
            }
            else if ( isset( $_REQUEST['cnt'] ) )
            {
                $count = $_REQUEST['cnt'];
            }

            if ( isset( $count ) and ( $count == 'true' or (int)$count == 1 ) )
            {
                $this->mCount = true;
            }
            else
            {
                $this->mCount = false;
            }

            // Start
            if ( isset( $_REQUEST['start'] )  and 
                 (int)$_REQUEST['start'] >= 1 )
            {
                $this->mStart = (int)$_REQUEST['start'];
            }
            else if ( isset( $_REQUEST['s'] )  and 
                 (int)$_REQUEST['s'] >= 1 )
            {
                $this->mStart = (int)$_REQUEST['s'];
            }

            // Limit
            if ( isset( $_REQUEST['limit'] )  and 
                 (int)$_REQUEST['limit'] >= 0 )
            {
                $this->mLimit = (int)$_REQUEST['limit'];
            }
            else if ( isset( $_REQUEST['l'] )  and 
                 (int)$_REQUEST['l'] >= 0 )
            {
                $this->mLimit = (int)$_REQUEST['l'];
            }

            if ( $this->mOperation == 'search' )
            {
                // Envelope
                if ( isset( $_REQUEST['envelope'] ) )
                {
                    $envelope = $_REQUEST['envelope'];
                }
                else if ( isset( $_REQUEST['e'] ) )
                {
                    $envelope = $_REQUEST['e'];
                }

                if ( isset( $envelope ) and 
                     ( $envelope == 'false' or (int)$envelope == 0 ) )
                {
                    $this->mEnvelope = false;
                }

                // Omit namespaces
                $omit_ns = TpUtils::GetVar( 'omit-ns', false );

                if ( $omit_ns == 'true' or (int)$omit_ns == 1 )
                {
                    $this->mOmitNamespaces = true;
                }
            }

            // Template
            if ( isset( $_REQUEST['template'] ) )
            {
                $this->mTemplate = $_REQUEST['template'];
            }
            else if ( isset( $_REQUEST['t'] ) )
            {
                $this->mTemplate = $_REQUEST['t'];
            }

            if ( ! empty( $this->mTemplate ) )
            {
                return $this->LoadTemplate();
            }
            else
            {
                // No template provided - delegate further parameter loading
                if ( $this->mOperation == 'search' )
                {
                    require_once('TpSearchParameters.php');

                    $this->mOperationParameters = new TpSearchParameters();
                }
                else
                {
                    require_once('TpInventoryParameters.php');

                    $this->mOperationParameters = new TpInventoryParameters();
                }

                return $this->mOperationParameters->LoadKvpParameters();
            }
	}

        return true;

    } // end of member function LoadKvpParameters

    function LoadXmlParameters( $request )
    {
        // Stash request to a file

        if ( TP_STASH_REQUEST )
        {
            $fnam = TP_DEBUG_DIR.'/'.TP_STASH_FILE;

            $fp_stash = fopen( $fnam, 'w' );

            if ( $fp_stash )
            {
                fwrite( $fp_stash, sprintf( "REMOTE ADDRESS: %s\n", $_SERVER['REMOTE_ADDR'] ) );
                fwrite( $fp_stash, sprintf( "REQUEST URI: %s\n", $_SERVER['REQUEST_URI'] ) );
                fwrite( $fp_stash, sprintf( "AGENT: %s\n", $_SERVER['HTTP_USER_AGENT'] ) );               
                fwrite( $fp_stash, sprintf( "XML SOURCE: %s\n", $request ) );
                fwrite( $fp_stash, "XML CONTENT: \n" );
            }
            else
            {
                $msg = "Could not stash request in $fnam";
                TpDiagnostics::Append( DC_IO_ERROR, $msg, DIAG_WARN );
            }
        }

        $parser = xml_parser_create_ns();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_start_namespace_decl_handler( $parser, 'DeclareNamespace' );
        xml_set_element_handler( $parser, 'StartElement', 'EndElement' );
        xml_set_character_data_handler( $parser, 'CharacterData' );

        if ( TpUtils::IsUrl( $request ) )
        {
            if ( !( $fp = fopen( $request, 'r' ) ) ) 
            {
                $error = "Could not open request file: $request";
                TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_FATAL );

                return false;
            }
      
            while ( $data = fread( $fp, 4096 ) ) 
            {
                if ( TP_STASH_REQUEST and $fp_stash )
                {
                    fwrite( $fp_stash, $data );
                }

                if ( ! xml_parse( $parser, $data, feof( $fp ) ) ) 
                {
                    $code = xml_get_error_code( $parser );

                    $error = sprintf( "Error (%s) parsing request at line %d: %s",
                                      $code,
                                      xml_get_current_line_number( $parser ),
                                      xml_error_string( $code ) );

                    TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_FATAL );
                    return false;
                }
            }

            fclose( $fp );
        }
        else
        {
            if ( $_SERVER['REQUEST_METHOD'] == 'GET' )
            {
                $request = urldecode( $request );
            }

            if ( ! xml_parse( $parser, $request ) )
            {
                $error = sprintf( "Error parsing request: %s at line %d",
                                  xml_error_string( xml_get_error_code( $parser ) ),
                                  xml_get_current_line_number( $parser ) );
                TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_FATAL );
                return false;
            }
        }

        xml_parser_free( $parser );

        if ( TP_STASH_REQUEST and $fp_stash )
        {
            fflush( $fp_stash );
            fclose( $fp_stash );
        }

        return true;

    } // end of member function LoadXmlParameters

    function StartElement( $parser, $qualified_name, $attrs ) 
    {
        global $g_dlog;

        $name = TpUtils::GetUnqualifiedName( $qualified_name );

        array_push( $this->mInTags, strtolower( $name ) );

        $depth = count( $this->mInTags );

        if ( $depth > count( $this->mSequence ) )
        {
            array_push( $this->mSequence, 1 );
        }
        else
        {
            $this->mSequence[$depth-1] = $this->mSequence[$depth-1] +1;
        }

        $path = implode( '/', $this->mInTags );

        $sequence = $this->mSequence[$depth-1];

        // First <source> inside <header>
        if ( strcasecmp( $path, 'request/header/source' ) == 0 and $sequence == 1 )
        {
            // Note: accesspoint is mandatory for all <source> except the last
            if ( isset( $attrs['accesspoint'] ) )
            {
                // Should only fall here when there's more than one <source>
                $this->mClientAccesspoint = $attrs['accesspoint'];
            }
            else
            {
                // Only one source (first == last)
                $this->mClientAccesspoint = $_SERVER['REMOTE_ADDR'];
            }
        }
        // Second element (after <header>) in the second level
        else if ( $depth == 2 and $sequence == 2 )
        {
            $this->mOperation = strtolower( $name ); // no need to perform check here

            $log_only = TpUtils::GetInArray( $attrs, 'log-only', false );

            if ( strcmp( $log_only, 'true' ) == 0 or $log_only == '1' )
            {
                $this->mLogOnly = true;
            }

            $this->mXslt = TpUtils::GetInArray( $attrs, 'xslt', null );
            $this->mXsltApply = TpUtils::GetInArray( $attrs, 'xslt-apply', false );

            if ( $this->mOperation == 'search' or $this->mOperation == 'inventory' )
            {
                $count = TpUtils::GetInArray( $attrs, 'count', false );

                if ( strcmp( $count, 'true' ) == 0 or $count == '1' )
                {
                    $this->mCount = true;
                }
                else
                {
                    $this->mCount = false;
                }

                $this->mStart = (int)TpUtils::GetInArray( $attrs, 'start', 0 );

                if ( $this->mStart < 0 )
                {
                    $this->mStart = 0;
                }

                $this->mLimit = (int)TpUtils::GetInArray( $attrs, 'limit', -1 );

                if ( $this->mLimit < 0 )
                {
                    $this->mLimit = null;
                }

                if ( $this->mOperation == 'search' )
                {
                    $envelope = TpUtils::GetInArray( $attrs, 'envelope', true );

                    if ( strcmp( $envelope, 'false' ) == 0 or $envelope == '0' )
                    {
                        $this->mEnvelope = false;
                    }

                    $omit_ns = TpUtils::GetInArray( $attrs, 'omit-ns', false );

                    if ( strcmp( $omit_ns, 'true' ) == 0 or $omit_ns == '1' )
                    {
                        $this->mOmitNamespaces = true;
                    }
                }
	    }
        }
        else if ( $this->mOperation == 'search' or $this->mOperation == 'inventory' )
        {
            // First subelement of the operation
            if ( $depth == 3 and $sequence == 1 )
            {
                if ( strcasecmp( $name, 'template' ) == 0 )
                {
                    if ( isset( $attrs['location'] ) )
                    {
                        // Remove possible namespaces that were flagged as being
                        // part of the output model element
                        if ( $this->mOperation == 'search' )
                        {
                            $g_dlog->debug( 'Removing possible namespaces that were '.
                                            'flagged as being part of the output '.
                                            'model element (detected <template>)' );

                            $r_namespace_manager =& XsNamespaceManager::GetInstance();

                            $r_namespace_manager->RemoveFlag( $parser, 'm' );
                        }

                        $this->mTemplate = $attrs['location'];

                        $this->LoadTemplate();
                    }
                    else
                    {
                        $error = 'Missing attribute "location" in <template> element';
                        TpDiagnostics::Append( DC_INVALID_REQUEST, $msg, DIAG_ERROR );
                    }
                }
                else 
                {
                    // Just instantiate a new parameters object and let the
                    // rest of the parsing take care of loading parameters
                    if ( $this->mOperation == 'search' )
                    {
                        // Remove possible namespaces that were flagged as being
                        // part of the output model element
                        if ( strcasecmp( $name, 'searchtemplate' ) == 0 )
                        {
                            $g_dlog->debug( 'Removing possible namespaces that were '.
                                            'flagged as being part of the output model '.
                                            'element (detected <searchTemplate>)' );

                            $r_namespace_manager =& XsNamespaceManager::GetInstance();

                            $r_namespace_manager->RemoveFlag( $parser, 'm' );
                        }

                        require_once('TpSearchParameters.php');

                        $this->mOperationParameters = new TpSearchParameters();
                    }
                    else
                    {
                        require_once('TpInventoryParameters.php');

                        $this->mOperationParameters = new TpInventoryParameters();
                    }
		}
            }

            if ( empty( $this->mTemplate ) and 
                 ! TpDiagnostics::Count( array( DIAG_ERROR, DIAG_FATAL ) ) )
            {
                // Delegate parsing
                $this->mOperationParameters->StartElement( $parser, $qualified_name, $attrs );
            }
        }

    } // end of member function StartElement

    function EndElement( $parser, $qualified_name ) 
    {
        $name = TpUtils::GetUnqualifiedName( $qualified_name );

        $depth = count( $this->mInTags );

        array_pop( $this->mInTags );

        if ( $depth < count( $this->mSequence ) )
        {
            $this->mSequence[$depth] = 0;
        }

        if ( $this->mOperation == 'search' or $this->mOperation == 'inventory' )
        {
            if ( is_object( $this->mOperationParameters ) and empty( $this->mTemplate ) )
            {
                $this->mOperationParameters->EndElement( $parser, $qualified_name );
            }
        }

    } // end of member function EndElement

    function CharacterData( $parser, $data ) 
    {
        $depth = count( $this->mInTags );

        if ( $this->mOperation == 'search' or $this->mOperation == 'inventory' )
        {
            if ( is_object( $this->mOperationParameters ) and empty( $this->mTemplate ) )
            {
                $this->mOperationParameters->CharacterData( $parser, $data );
            }
	}

    } // end of member function CharacterData

    function DeclareNamespace( $parser, $prefix, $uri ) 
    {
        $path = implode( '/', $this->mInTags );

        $flag = null;

        if ( strcasecmp( $path, 'request/search' ) == 0 )
        {
            // Namespaces declared in output model or query template.
            // This flag indicates a potential namespace declared in
            // an output model element.
            $flag = 'm';
        }

        $r_namespace_manager =& XsNamespaceManager::GetInstance();

        $r_namespace_manager->AddNamespace( $parser, $prefix, $uri, $flag );

    } // end of member function DeclareNamespace

    function LoadTemplate()
    {
        global $g_dlog;

        $g_dlog->debug( '[Query template]' );

        // Templates must be specified as URLs
        // (it's important to check this also for security reasons since
        // "fopen" is used to read templates!)
        if ( ! TpUtils::IsUrl( $this->mTemplate ) )
        {
            // Check if template is a known alias
            $this->mrResource->LoadConfig();

            $r_settings =& $this->mrResource->GetSettings();

            if ( $this->mOperation == 'inventory' )
            {
                $location = $r_settings->GetInventoryTemplate( $this->mTemplate );
            }
            else if ( $this->mOperation == 'search' )
            {
                $location = $r_settings->GetSearchTemplate( $this->mTemplate );
            }

            if ( $location )
            {
                $g_dlog->debug( 'Template parameter is a known alias: '.$this->mTemplate );
                $this->mTemplate = $location;
            }
            else
            {
                $error = 'Template is neither a URL nor a known alias.';
                TpDiagnostics::Append( DC_INVALID_REQUEST, $error, DIAG_FATAL );

                return false;
            }
        }

        $g_dlog->debug( 'Location '. $this->mTemplate );

        $loaded_from_cache = false;

        // If cache is enabled
        if ( TP_USE_CACHE and TP_TEMPLATE_CACHE_LIFE_SECS )
        {
            $cache_dir = TP_CACHE_DIR . '/' . $this->mResourceCode;

            $cache_options = array( 'cache_dir' => $cache_dir );

            $subdir = 'templates';

            $cache = new Cache( 'file', $cache_options );
            $cache_id = $cache->generateID( $this->mTemplate );
            $cached_data = $cache->get( $cache_id, $subdir );

            if ( $cached_data and ! $cache->isExpired( $cache_id, $subdir ) )
            {
                $g_dlog->debug( 'Unserializing query template from cache' );

                // Check if serialized object has the mRevision property.
                // If not, this means that the cached object was based on 
                // the old Tp*Parameters class definition, so we need 
                // to discard it.
                if ( strpos( $cached_data, ':"mRevision"' ) === false )
                {
                    $g_dlog->debug( 'Detected obsolete serialized query template' );

                    if ( ! $cache->remove( $cache_id, $subdir ) )
                    {
                        $g_dlog->debug( 'Could not remove query template from cache' );
                    }
                    else
                    {
                        $g_dlog->debug( 'Removed query template from cache' );
                    }
                }
                else
                {
                    $this->mOperationParameters = unserialize( $cached_data );

                    if ( ! $this->mOperationParameters )
                    {
                        $g_dlog->debug( 'Could not unserialize query template from cache' );

                        if ( ! $cache->remove( $cache_id, $subdir ) )
                        {
                            $g_dlog->debug( 'Could not remove query template from cache' );
                        } 
                        else
                        {
                            $g_dlog->debug( 'Removed query template from cache' );
                        } 
                    }
                    else
                    {
                        // Check if unserialized object has correct version.
                        // Should always pass this condition. It is here in case
                        // there is any change in Tp*Parameters that may
                        // affect caching in the future.
                        $revision = $this->mOperationParameters->GetRevision();

                        // NOTE: You may want to distinguish between search 
                        // and inventory.

                        if ( $revision > 557 )
                        {
                            // IMPORTANT: In the future it may be necessary to 
                            // check the output model and/or response structure 
                            // revisions here too!

                            $g_dlog->debug( 'Loaded query template from cache' );

                            $this->mLoadedTemplateFromCache = true;
                        }
                        else
                        {
                            $g_dlog->debug( 'Incorrect serialized query template revision ('.$revision.')' );

                            if ( ! $cache->remove( $cache_id, $subdir ) )
                            {
                                $g_dlog->debug( 'Could not remove query template from cache' );
                            }
                            else
                            {
                                $g_dlog->debug( 'Removed query template from cache' );
                            }
                        }
                    }
                }
            }
        }

        if ( ! $this->mLoadedTemplateFromCache )
        {
            $g_dlog->debug( 'Retrieving and parsing template file' );

            if ( $this->mOperation == 'search' )
            {
                require_once('TpSearchParameters.php');

                $this->mOperationParameters = new TpSearchParameters();
            }
            else
            {
                require_once('TpInventoryParameters.php');

                $this->mOperationParameters = new TpInventoryParameters();
            }

            return $this->mOperationParameters->ParseTemplate( $this->mTemplate );
        }
 
        return true;

    } // end of member function LoadTemplate

    function GetOperation()
    {
        return strtolower( $this->mOperation );

    } // end of member function GetOperation

    function GetResourceCode()
    {
        return $this->mResourceCode;

    } // end of member function GetResourceCode

    function GetResourceAccessPoint()
    {
        $accesspoint = '?';

        if ( $this->mrResource != null )
        {
            $accesspoint = $this->mrResource->GetAccesspoint();
        }

        return $accesspoint;

    } // end of member function GetResourceAccesspoint

    function &GetResource()
    {
        return $this->mrResource;

    } // end of member function GetResource

    function GetClientAccesspoint()
    {
        return $this->mClientAccesspoint;

    } // end of member function GetClientAccesspoint

    function GetLogOnly()
    {
        return $this->mLogOnly;

    } // end of member function GetLogOnly

    function GetXslt()
    {
        return $this->mXslt;

    } // end of member function GetXslt

    function GetXsltApply()
    {
        return $this->mXsltApply;

    } // end of member function GetXsltApply

    function GetCount()
    {
        return $this->mCount;

    } // end of member function GetCount

    function GetStart()
    {
        return $this->mStart;

    } // end of member function GetStart

    function GetLimit()
    {
        return $this->mLimit;

    } // end of member function GetLimit

    function GetEnvelope( )
    {
        return $this->mEnvelope;

    } // end of member function GetEnvelope

    function GetOmitNamespaces( )
    {
        return $this->mOmitNamespaces;

    } // end of member function GetOmitNamespaces

    function GetOperationParameters()
    {
        return $this->mOperationParameters;

    } // end of member function GetOperationParameters

    function GetRequestEncoding()
    {
        return $this->mRequestEncoding;

    } // end of member function GetRequestEncoding

    function GetTemplate()
    {
        return $this->mTemplate;

    } // end of member function GetTemplate

    function LoadedTemplateFromCache()
    {
        return $this->mLoadedTemplateFromCache;

    } // end of member function LoadedTemplateFromCache

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	$basic_parameters = array( 'mResourceCode', 'mOperation', 'mXslt', 
                                   'mXsltApply', 'mLogOnly' );

        if ( $this->mOperation == 'ping' or $this->mOperation == 'metadata' or 
             $this->mOperation == 'capabilities' )
        {
            return $basic_parameters;
        }

        $query_parameters = array( 'mCount', 'mStart', 'mLimit', 'mEnvelope', 
                                   'mOperationParameters', 'mTemplate', 
                                   'mLoadedTemplateFromCache' );

        return array_merge( $basic_parameters, $query_parameters );

    } // end of member function __sleep

} // end of TpRequest
?>
