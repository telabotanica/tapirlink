<?php
/**
 * $Id: TpResponse.php 1971 2009-02-04 12:10:35Z rdg $
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

require_once('TpUtils.php');
require_once('TpDiagnostics.php');
require_once('TpXmlNamespace.php');
require_once('pear/Cache/Function.php');

class TpResponse 
{
    var $mRequest;
    var $mXmlNamespaces = array(); // TpXmlNamespace objects
    var $mXslt;
    var $mDefaultXslt;
    var $mCacheable = true;
    var $mCacheLife = 86400; // once a day (in seconds)

    function TpResponse( $request )
    {
        $this->mRequest = $request;

        $schema_instance_ns = new TpXmlNamespace( XMLSCHEMAINST, TP_XSI_PREFIX, '' );
        $this->AddXmlNamespace( $schema_instance_ns );

        $xslt = $request->GetXslt();

        if ( $xslt )
        {
            $this->mXslt = $xslt;
        }
        else if ( $this->mDefaultXslt )
        {
            $accesspoint = $this->mRequest->GetResourceAccesspoint();

            $main_script_position = strpos( $accesspoint, 'tapir.php' );

            if ( $main_script_position !== false )
            {
                $base_url = substr( $accesspoint, 0, $main_script_position );

                $this->mXslt = $base_url . $this->mDefaultXslt;
            }
        }

    } // end of member function TpResponse

    function Process()
    {
        // In some cases it is necessary to replace headers (see "header" call 
        // inside TpXmlGenerator), so avoid echoing content straight away.
        ob_start();

        global $g_dlog;

        $log_data = array();

        if ( $this->mRequest->GetLogOnly() )
        {
            $r_resource =& $this->mRequest->GetResource();

            $config_file = $r_resource->GetConfigFile();
            $capabilities_file = $r_resource->GetCapabilitiesFile();

            $r_settings =& $r_resource->GetSettings();

            $r_settings->LoadFromXml( $config_file, $capabilities_file );

            if ( $r_settings->GetLogOnly() == 'denied' )
            {
                $this->ReturnError('Log-only requests are denied on this service');
            }

            $this->Header();

            $log_data = $this->_GetLogData();

            $this->Log( $log_data );

            echo "\n<logged />";

            $this->Footer();
        }
        else
        {
            // Header should be always dynamic, leave it out from cache
            $this->Header();

            if ( $this->mCacheable and TP_USE_CACHE and $this->mCacheLife )
            {
                $g_dlog->debug( 'Response cache is activated' );

                $params = $this->GetParams();

                $cache_dir = TP_CACHE_DIR . '/' . $this->mRequest->GetResourceCode();

                $cache_params = array( 'cache_dir' => $cache_dir,
                                       'filename_prefix' => 'req_' );

                $cache = new Cache_Function( 'file', $cache_params, $this->mCacheLife );

                // The object needs to be global to be accessible from 
                // inside Cache_Function
                global $r_thiscopy;

                // It also needs to be a full copy, not a reference (not sure why)
                $r_thiscopy = $this;

                $cache->call( 'r_thiscopy->CacheResponse', $params );

                $log_data = $this->_GetLogData();

                $this->Log( $log_data );
            }
            else
            {
                $this->Body();

                // Note: better to place logging after Body() so that the SQL can 
                //       also be logged. 
                $log_data = $this->_GetLogData();

                $this->Log( $log_data );

                $this->Footer();
            }
        }

        if ( TP_STATISTICS_TRACKING )
        {
            require_once('flatfile/flatfile.php');
            require_once('TpStatistics.php');
            require_once('TpStatisticsLogger.php');

            $current_month = date('m');
            $current_year  = date('Y');

            $stats_db = new Flatfile();
            $stats_db->datadir = TP_STATISTICS_DIR;

            $stats_file_name = TP_STATISTICS_DIR.'/'.$current_year.'_'.
                                                     $current_month.'.tbl';

            if ( ! file_exists( $stats_file_name ) )
            {
                $res = touch( $stats_file_name );
            }

            $stats_log =& Log::singleton( TP_LOG_TYPE,
                                          realpath( $stats_file_name ),
                                          $_SERVER['REMOTE_ADDR'],
                                          unserialize( TP_LOG_OPTIONS ),
                                          TP_LOG_LEVEL );

            TpStatistics::LogResourceInfo( $stats_log, $stats_db, $log_data, 
                                           $current_month, $current_year );
            TpStatistics::LogSchemaInfo( $stats_db, $log_data, 
                                         $current_month, $current_year );

            $LogStruct = new TpStatisticsLogger( $log_data );
            $LogStruct->Initialize( $log_data );

            $LogStruct->WriteRequestResult( $stats_log );
        }

        ob_end_flush();

    } // end of member function Process

    function Header()
    {
        $tapir_ns = new TpXmlNamespace( TP_NAMESPACE, '', TP_SCHEMA_LOCATION );
        $this->AddXmlNamespace( $tapir_ns );

        $this->XmlHeader();

        // Open response element adding the namespaces
        echo "\n<response";

        $this->NamespaceDeclarations();

        echo ">\n";

        // TAPIR header
        $send_time = TpUtils::TimestampToXsdDateTime( time() );

        $accesspoint = $this->mRequest->GetResourceAccesspoint();

        $destination = $this->mRequest->GetClientAccesspoint();

        $version = TP_VERSION.' (revision '.TP_REVISION.')';

        $h = '<header>';
        $h .= "\n".'<source accesspoint="'.$accesspoint.'" sendtime="'.$send_time.'">';
        $h .= "\n\t".'<software name="TapirLink" version="'.$version.'"/>';
        $h .= "\n".'</source>';
        $h .= "\n<destination>".$destination.'</destination>';

        if ( defined( 'TP_SKIN') )
        {
            $h .= "\n<custom>".
                  '<skin xmlns="http://rs.tdwg.org/tapir/1.0/skin">'.
                  TP_SKIN.
                  '</skin></custom>';
        }

        $h .= "\n</header>";

        echo $h;

    } // end of member function Header

    function NamespaceDeclarations()
    {
        $ns_declarations = '';

        function _CmpNamespaces( $ns1, $ns2 )
        {
            return strcmp( $ns1->GetPrefix(), $ns2->GetPrefix() );

        } // end of inline function _CmpNamespaces

        usort( $this->mXmlNamespaces, '_CmpNamespaces' );

        $locations = '';

        foreach ( $this->mXmlNamespaces as $xml_namespace )
        {
            $ns_declarations .= ' xmlns';

            $prefix = $xml_namespace->GetPrefix();

            if ( ! empty( $prefix ) )
            {
                $ns_declarations .= ':'.$prefix;
            }

            $uri = $xml_namespace->GetNamespace();

            $ns_declarations .= '="'.$uri.'"';

            $location = $xml_namespace->GetSchemaLocation();

            if ( ! empty( $location ) )
            {
                $locations .= ' '.$uri.' '.$location;
            }
        }

        echo $ns_declarations;

        if ( ! empty( $locations ) )
        {
            echo ' '.TP_XSI_PREFIX.':schemaLocation="'.trim( $locations ).'"';
        }

    } // end of member function NamespaceDeclarations

    function XmlHeader()
    {
        // Send the default XML content type header
        header( 'Content-type: text/xml; charset=utf-8' );

        // Start the response
        echo TpUtils::GetXmlHeader();

        if ( $this->mXslt )
        {
            if ( $this->mRequest->GetXsltApply() )
            {
                $msg = 'Parameter "xslt-apply" is not supported';
                TpDiagnostics::Append( DC_UNSUPPORTED_CAPABILITY, $msg, DIAG_WARN );
            }

            echo "\n";
            echo '<?xml-stylesheet type="text/xsl" href="'.$this->mXslt.'"?>';
        }

    } // end of member function XmlHeader

    function Footer()
    {
        echo TpDiagnostics::GetXml();

        // Close response tag
        echo "\n</response>";

    } // end of member function Footer

    function Error( $msg, $level='error' )
    {
        // Error tag
        echo '<error level="'.$level.'">'.TpUtils::EscapeXmlSpecialChars( $msg ).'</error>';

        global $g_dlog;

        if ( is_object( $g_dlog ) )
        {
            $g_dlog->debug( '>> Returned Error: '.$msg );
        }

    } // end of member function Error

    function Body()
    {
        $this->Error( 'Internal error: TpResponse "Body" method must be '.
                      'overwritten by the subclass related to this operation.' );

    } // end of member function Body

    function ReturnError( $msg )
    {
        $this->Header();

        $this->Error( $msg );

        $this->Footer();

        global $g_log;
        
        $g_log->log( $msg, PEAR_LOG_ERR );

        die();

    } // end of member function ReturnError

    function AddXmlNamespace( $xmlNamespace )
    {
        array_push( $this->mXmlNamespaces, $xmlNamespace );

    } // end of member function AddXmlNamespace

    function GetParams( )
    {
        // This function is used to return all parameters that determine
        // the contents of a response. This is usually represented by a
        // TpRequest object. But this method can also be overwritten by 
        // subclasses to include other things that can influence responses 
        // (like local settings).

        return $this->mRequest;

    } // end of member function GetParams

    function CacheResponse( $params )
    {
        // This method is just a wrapper for caching results. $params don't
        // need to be used here since they mainly come from mRequest, which
        // is used by methods Body() and Footer(). $params only serve to 
        // generate the cache id.

        $this->Body();

        $this->Footer();

    } // end of member function CacheResponse

    function Log( $logData )
    {
        global $g_log;
        
        $str = TpServiceUtils::GetLogString( $logData );

        $g_log->log( $str, PEAR_LOG_INFO );

    } // end of member function Log

    function _GetLogData( )
    {
        // Log data which is common to all operations

        $data = array();

        $data['resource']    = $this->mRequest->GetResourceCode();
        $data['operation']   = $this->mRequest->GetOperation();
        $data['encoding']    = $this->mRequest->GetRequestEncoding();
        $data['logonly']     = $this->mRequest->GetLogOnly();
        $data['xslt']        = $this->mRequest->GetXslt();
        $data['source_ip']   = $this->mRequest->GetClientAccesspoint();
        $data['source_host'] = null;

        $long = ip2long( $data['source_ip'] );

        if ( $long == -1 || $long === false )
        {
            // Invalid IP!
        }
        else
        {
            // Valid IP

            // Note: gethostbyaddr can be time expensive! Using function cache here.

            $cache_params = array( 'cache_dir' => TP_CACHE_DIR,
                                   'filename_prefix' => 'ip_' );

            $cache = new Cache_Function( 'file', $cache_params, 
                                         TP_GETHOST_CACHE_LIFE_SECS );

            $source_host = $cache->call( 'gethostbyaddr', $data['source_ip'] );

            if ( $source_host )
            {
                $data['source_host'] = $source_host;
            }
        }

        return $data;

    } // end of member function _GetLogData

} // end of TpResponse
?>