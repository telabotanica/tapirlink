<?php
/**
 * $Id: TpUtils.php 2005 2010-06-11 23:00:46Z rdg $
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

require_once(dirname(__FILE__).'/TpDiagnostics.php');

class TpUtils 
{
    /** Format the output of debug_backtrace in a human friendly fashion.
     *  Render as Plain text for as HTML, and an option to print it directly.
     *
     */
    static function DumpPrettyStack( $renderAsHtml=true, $printInline=false )
    {
        // debug_backtrace is only available since PHP 4.3.0
        if ( version_compare( phpversion(), '4.3.0', '<' ) > 0 )
        {
            $msg = 'debug_backtrace not available in this PHP version';
            TpDiagnostics::Append( DC_UNSUPPORTED_CAPABILITY, $msg, DIAG_WARN );

            return '';
        }
    
        $return_value = '';
        
        $stack_array = debug_backtrace();

        $td   = '';
        $untd = '';
        $tr   = '';
        $untr = '';
        
        if ( $renderAsHtml )
        {
            $td   = '<TD>';
            $untd = '</TD>';
            $tr   = '<TR>';
            $untr = '</TR>';
        }
        
        if ( $renderAsHtml )
        {
            $return_value = '<TABLE><TR><TD></TD><TD>File</TD><TD>Line</TD><TD>Function</TD></TR>';
        }

        $count = 0;
        
        //we don't want this function call reported in the stack, so shift it off the array
        array_shift( $stack_array );

        foreach( $stack_array as $element )
        {
            $file     = false;
            $line     = false;
            $class    = false;
            $object   = false;
            $function = false;
            $args     = false;
            
            if ( array_key_exists( 'file', $element ) )
            {
                $file = $element['file'];
            }
            
            if ( array_key_exists( 'line', $element ) )
            {
                $line = $element['line'];
            }

            if ( array_key_exists( 'class', $element ) )
            {
                $class = $element['class'];
            }

            if ( array_key_exists( 'object', $element ) )
            {
                $object = $element['object'];
            }

            if ( array_key_exists( 'function', $element ) )
            {
                $function = $element['function'];
            }
            
            if ( array_key_exists( 'args', $element ) )
            {
                $args = $element[ 'args' ];
                $arg_total = count( $args );
                $arg_count = 1;
                $func_args = '(';

                foreach( $args as $arg )
                {
                    if ( is_array( $arg ) )
                    {
                        $func_args .= ' {Array}';
                    }
                    else if ( is_object( $arg ) )
                    {
                        $func_args .= ' {Object}';
                    }
                    else if ( is_bool( $arg ) )
                    {
                        if ( $arg )
                        {
                            $func_args .= ' true';
                        }
                        else
                        {
                            $func_args .= ' false';
                        }
                    }
                    else if ( is_float( $arg ) or is_int( $arg ) )
                    {
                       $func_args .= " $arg";
                    }
                    else if ( is_string( $arg ) )
                    {
                       $func_args .= " \"$arg\"";
                    }
                    else if ( is_resource( $arg ) )
                    {
                       $func_args .= " {resource $arg}";
                    }
                    else if ( is_null( $arg ) )
                    {
                        $func_args .= ' NULL';
                    }
                    
                    if ( $arg_count < $arg_total )
                    {
                        $func_args .= ',';
                    }

                    $arg_count++;
                }

                $func_args .= ')';
            }

            $return_value .= "$tr$td #$count $untd$td";

            if ( $file )
            {
                $return_value .= "$element[file] ";
            }

            $return_value .= "$untd$td";

            if ( $line )
            {
                $return_value .= "$element[line] ";
            }

            $return_value .= "$untd$td";

            if ( $class )
            {
                $return_value .= "$element[class]::";
            }

            if ( $function )
            {
                $return_value .= "$element[function]$func_args";
            }


            $return_value .= " $untd$untr\n";
            
            $count++;
        }
        
        if ( $renderAsHtml )
        {
            $return_value .= "</TABLE>\n";
        }

        if ( $printInline )
        {
            print( "$return_value" );
        }
        
        return $return_value;
    }
    
    /** Instantiate two log objects: the main one called g_log and another one
     *  just for detailed debugging called g_dlog. 
     *
     */
    static function InitializeLogs( ) 
    {
        // Main log
        $log_file_name = TP_LOG_DIR.'/'.TP_LOG_NAME;

        if ( ! file_exists( $log_file_name ) )
        {
            touch( $log_file_name );
        }

        // Create the log singleton
        // log entries have ID = client (portal) IP address
        $GLOBALS['g_log'] =& Log::singleton( TP_LOG_TYPE,
                                             $log_file_name,
                                             $_SERVER['REMOTE_ADDR'],
                                             unserialize( TP_LOG_OPTIONS ),
                                             TP_LOG_LEVEL );
        global $g_log;

        if ( PEAR::isError( $g_log ) ) 
        {
            $msg = 'The main log file could not be opened';
            TpDiagnostics::Append( DC_LOG_ERROR, $msg, DC_WARN );
        }

        // Separate log for detailed debugging
        TpUtils::InitializeDebugLog();

    } // end of InitializeLogs

    /** Instantiate a log object for detailed debugging called g_dlog. 
     *
     */
    static function InitializeDebugLog( ) 
    {
        $debug_file_name = TP_DEBUG_DIR.'/'.TP_DEBUG_LOGFILE;

        $debug_logtype = 'null';

        if ( TP_LOG_DEBUG )
        {
            if ( touch( $debug_file_name ) )
            {
                $debug_logtype = 'file';
            }
        }

        $GLOBALS['g_dlog'] = &Log::singleton( $debug_logtype, $debug_file_name, 'debug', 
                                              array( 'append'     => false,
                                                     'timeFormat' => '', 
                                                     'lineFormat' => '%4$s' ), 
                                              PEAR_LOG_DEBUG );
        global $g_dlog;

        if ( PEAR::isError( $g_dlog ) ) 
        {
            $msg = 'The debug log file could not be opened';
            TpDiagnostics::Append( DC_LOG_ERROR, $msg, DC_WARN );
        }

    } // end of InitializeDebugLog

    /** Get value from post/get environment variables or return a default 
     *
     * @param string name Parameter name
     * @param mixed defaultVal Default value to be used if parameter was not passed
     *
     * @return mixed Parameter value
     */
    static function GetVar( $name, $defaultVal=false ) 
    {
        // Note: If tapir_globals.php is included, then all $_REQUEST keys
        //       are changed to lower case!

        return ( isset( $_REQUEST[$name] ) ? $_REQUEST[$name] : $defaultVal );

    } // end of GetVar

    /** Get value from array using case insensitive comparison with a given key
     *  
     * @param array targetArray Array to search for key
     * @param string searchKey Key to be searched
     * @param mixed defaultVal Default value to be used if key is not present
     *
     * @return mixed Key value (if found) or default value
     */
    static function GetInArray( $targetArray, $searchKey, $defaultVal=false )
    {
        foreach ( $targetArray as $key => $value )
        {
            if ( strcasecmp( $searchKey, $key ) == 0 )
            {
                return $value;
            }
        }

        return $defaultVal;
    }

    /**
     * Returns the unqualified name from a 'namespace:name' string.
     */
    static function GetUnqualifiedName( $fullName ) {

        $last_colon = strrpos( $fullName, ':' );

        if ( $last_colon === false ) {

            return $fullName;
        }

        return substr( $fullName, $last_colon + 1 );

    } // end of GetUnqualifiedName

    /**
     * Simple check to determine if the supplied string is a URL
     *
     * @param string tst String to test
     *
     * returns TRUE or FALSE
     */
    static function IsUrl( $tst ) {

        //simple check to see if a string is a URL.
        //just looks at the first few characters to see if the scheme is http or ftp
        //add a single char so we don't get confused with a false answer
        //being the same as a zero
        $tst = ' ' . substr( $tst, 0, 8 );

        $tst = strtolower( $tst );

        if ( strpos( $tst, 'http://' ) == 1 )
        {
            return true;
        }
	else if ( strpos( $tst, 'ftp://' ) == 1 )
        {
            return true;
        }
	else if ( strpos( $tst, 'php://' ) == 1 )
        {
            return true;
        }

        return false;

    } // end of IsUrl

    /** Strip slashes from strings and array elements
     *  
     * @param string or array reference
     */
    static function StripMagicSlashes( &$rVar ) 
    {
        if ( is_string( $rVar ) )
        {
            $rVar = stripslashes( $rVar );
        }
        elseif ( is_array( $rVar ) )
        {
            foreach( $rVar as $key => $value ) 
            {
                TpUtils::StripMagicSlashes( $rVar[$key] );
            }
        }

    } // end of StripMagicSlashes

    /**
     * Escapes XML special chars in a string
     *
     * @param $s string String to be escaped (assumed to be in utf-8)
     * @return string Escaped string
     */
    static function EscapeXmlSpecialChars( $s )
    {
        // Since "htmlspecialchars" does not work with utf-8 in versions
        // prior than 4.3.0 (stable!), we need to use mb_ereg_replace as an alternative
        if ( version_compare( phpversion(), '4.3.0', '>=' ) > 0 )
        {
            $s = htmlspecialchars( $s, ENT_COMPAT, 'UTF-8' );
        }
        else
        {
            if ( function_exists( 'mb_regex_encoding' ) )
            {
                mb_regex_encoding('UTF-8');
                $s = mb_ereg_replace('&', '&amp;' , $s);
                $s = mb_ereg_replace('>', '&gt;'  , $s);
                $s = mb_ereg_replace('<', '&lt;'  , $s);
                $s = mb_ereg_replace('"', '&quot;', $s);
            }
            else
            { 
                // TODO: If $s contains any xml special char, we should raise an error here!
                $s = htmlspecialchars( $s );
            }
        }

        return $s;

    } // end of EscapeXmlSpecialChars

    /**
     * Returns current time measured in the number of seconds since the Unix 
     * Epoch (0:00:00 January 1, 1970 GMT) including microseconds.
     *
     * @return float number of seconds with microseconds since the Unix Epoch
     */
    static function MicrotimeFloat()
    {
        list( $usec, $sec ) = explode( ' ', microtime() );

        return ( (float)$usec + (float)$sec );

    } // end of MicrotimeFloat

    /**
     * Returns an xsd:dateTime value from a timestamp.
     * xsd:dateTime values follow this rule: [-]CCYY-MM-DDThh:mm:ss[Z|(+|-)hh:mm]
     *
     * @return string xsd:dateTime
     */
    static function TimestampToXsdDateTime( $timestamp )
    {
        $date = strftime( '%Y-%m-%d', $timestamp );

        if ( strtoupper( substr( PHP_OS, 0, 3) ) == 'WIN' )
        {
            $time = strftime( '%X', $timestamp );
        }
        else
        {
            $time = strftime( '%T', $timestamp );
        }

        $time_zone = strftime( '%z', $timestamp );

        if ( preg_match("/^[+-]{1}\d{4}$/", $time_zone ) )
        {
            $time_zone = substr( $time_zone, 0, 3 ) . ':'. substr( $time_zone, 3 );
        }
        else
        {
            $time_zone = '';
        }

        return $date.'T'.$time.$time_zone;

    } // end of TimestampToXsdDateTime

    /** Simple function that returns a hash out of a simple array 
     *  using each array value as both the key and the value of the hash.
     *  
     * @param array elements
     *
     * @return hash
     */
    static function GetHash( $elements ) 
    {
        $ret_array = array();
  
        foreach ( $elements as $value )
        {
            $ret_array[$value] = $value;
        }

        return $ret_array;

    } // end of GetHash

    /**
     * Returns an opening XML tag for the specified element.
     *
     * @param string $nsPrefix Namespace prefix.
     * @param string $elementName Element name.
     * @param string $indent Optional indentation characters.
     * @param array $attrs Optional array with key value pairs to be added as attributes.
     * @return string An opening tag for the specified element
     */
    static function OpenTag( $nsPrefix, $elementName, $indent='', $attrs=array() )
    {
        $ns_sep = ( $nsPrefix ) ? ':' : '';

        $xml_attrs = '';

        if ( count( $attrs ) )
        {
            foreach ( $attrs as $attr_key => $attr_value )
            {
                $xml_attrs .= ' ' . $attr_key .'="'. $attr_value .'"';
            }
        }

        return sprintf( "%s<%s%s%s%s>\n", $indent, $nsPrefix, $ns_sep, 
                                          $elementName, $xml_attrs );

    } // end of OpenTag

    /**
     * Returns a closing XML tag for the specified element.
     *
     * @param string $nsPrefix Namespace prefix.
     * @param string $elementName Element name.
     * @param string $indent Optional indentation characters.
     * @return string A closing tag for the specified element
     */
    static function CloseTag( $nsPrefix, $elementName, $indent='' )
    {
        $ns_sep = ( $nsPrefix ) ? ':' : '';

        return sprintf( "%s</%s%s%s>\n", $indent, $nsPrefix, $ns_sep, $elementName );

    } // end of CloseTag

    /**
     * Returns an XML tag enclosing the specified value.
     *
     * @param string $nsPrefix Namespace prefix.
     * @param string $elementName Element name.
     * @param string $value Element value.
     * @param string $indent Optional indentation characters.
     * @param array $attrs Optional array with key value pairs to be added as attributes.
     * @return string Value (with XML characters escaped) enclosed by the element
     */
    static function MakeTag( $nsPrefix, $elementName, $value, $indent='', $attrs=array() )
    {
        $ns_sep = ( $nsPrefix ) ? ':' : '';

        $xml_attrs = '';

        if ( count( $attrs ) )
        {
            foreach ( $attrs as $attr_key => $attr_value )
            {
                $xml_attrs = ' ' . $attr_key .'="'. TpUtils::EscapeXmlSpecialChars( $attr_value ) .'"';
            }
        }

        $s = sprintf( '%s<%s%s%s%s>', $indent, $nsPrefix, $ns_sep, 
                                      $elementName, $xml_attrs );
        $s .= TpUtils::EscapeXmlSpecialChars( $value );
        $s .= sprintf( "</%s%s%s>\n", $nsPrefix, $ns_sep, $elementName );

        return $s;

    } // end of MakeTag

    /**
     * Returns an XML tag enclosing the specified value and having an xml:lang attribute.
     *
     * @param string $nsPrefix Namespace prefix.
     * @param string $elementName Element name.
     * @param string $value Element value.
     * @param string $lang Language code.
     * @param string $indent Optional indentation characters.
     * @return string Value (with XML characters escaped) enclosed by the specified 
     *                element with a lang attribute
     */
    static function MakeLangTag( $nsPrefix, $elementName, $value, $lang, $indent='' )
    {
        $ns_sep = ( $nsPrefix ) ? ':' : '';

        if ( $lang )
        {
            $s = sprintf( '%s<%s%s%s %s:lang="%s">', $indent, $nsPrefix, $ns_sep, 
                          $elementName, TP_XML_PREFIX, $lang );
        }
        else 
        {
            $s = sprintf( '%s<%s%s%s>', $indent, $nsPrefix, $ns_sep, $elementName );
        }
        
        $s .= TpUtils::EscapeXmlSpecialChars( $value );
        $s .= sprintf( "</%s%s%s>\n", $nsPrefix, $ns_sep, $elementName );

        return $s;

    } // end of MakeLangTag

    /** Returns a default XML header 
     *
     * @return Default XML header
     */
    static function GetXmlHeader( ) 
    {
        return '<?xml version="1.0" encoding="utf-8" ?>';

    } // end of GetXmlHeader

    /**
     * Loads the specified library if not already, trying to be platform independent.
     * Note that dl() is only available when the PHP interpreter is running
     * in CGI mode.  
     *
     * @param $libName string Name of the library to load
     */
    static function LoadLibrary( $libName )
    {
        $res = true;

        if ( ! extension_loaded( $libName ) )
        {
    	    if ( strtoupper( substr( PHP_OS, 0, 3 ) ) == 'WIN' )
            {
    	        $res = @dl( 'php_'.$libName.'.dll' );
            }
    	    elseif ( PHP_OS == 'HP-UX' )
            {
    	        $res = @dl( $libName.'.sl' );
            }
    	    elseif ( PHP_OS == 'AIX' )
            {
    	        $res = @dl( $libName.'.a' );
            }
    	    else
            {
    	        $res = @dl( $libName.'.so' );
            }
        }

        return $res;

    } // end of LoadLibrary

    /**
     * Simple array dumper. For debugging stuff.
     */
    static function DumpArray( $a )
    {
        if ( ! is_array( $a ) ) 
        {
            return;
        }

        $s = '';

        foreach ( $a as $key => $val ) 
        {
            $s .= "\n(".$key.')=';
            $s .= (is_object( $val )) ? 'obj' : $val;
        }

        return $s;

    } // end of DumpArray

    /**
     * Returns the alternative file name given a file location.
     */
    static function GetAlternativeFileName( $location )
    {
        global $g_dlog;

        if ( ! defined( 'TP_LOCAL_REPOSITORY' ) )
        {
            $g_dlog->debug( 'TP_LOCAL_REPOSITORY not defined. Cannot try alternative location.' );
            return null;
        }

        $parsed_url = parse_url( $location );

        if ( $parsed_url and isset( $parsed_url['path'] ) )
        {
            $url_explode = explode( '/', $parsed_url['path'] );
            $file = array_pop( $url_explode );

            $file = TP_LOCAL_REPOSITORY.'/'.$file;

            return $file;
        }
        else
        {
            $g_dlog->debug( 'Could not parse location URL to find alternative name' );
            return null;
        }

    } // end of GetAlternativeFileName

    /**
     * Try to open a file from its original location. Upon failure, try 
     * alternative location if TP_LOCAL_REPOSITORY is defined.
     * NOTE: Remember to close the file handle.
     * @param $location string File location
     * @return file handle or null
     */
    static function GetFileHandle( $location )
    {
        global $g_dlog;

        $attempt = array();

        $local_copy = TpUtils::GetAlternativeFileName( $location );

        if ( TP_FILE_RETRIEVAL_BEHAVIOUR == 'prefer_local' )
        {
            if ( is_null( $local_copy ) )
            {
                $attempt[] = $location; // no alternative, use original
            }
            else
            {
                $attempt[] = $local_copy; // prefer local copy
                $attempt[] = $location;   // otherwise try original
            }
        }
        if ( TP_FILE_RETRIEVAL_BEHAVIOUR == 'only_local' )
        {
            if ( is_null( $local_copy ) )
            {
                return null; // no way
            }

            $attempt[] = $local_copy; // there may be a local copy, so try to use it
        }
        else
        {
            $attempt[] = $location; // prefer original

            if ( ! is_null( $local_copy ) )
            {
                $attempt[] = $local_copy; // otherwise try a possible local copy
            }
        }

        foreach ( $attempt as $file_location )
        {
            $g_dlog->debug( 'Trying to open: '.$file_location );

            $fp = TpUtils::OpenFile( $file_location );

            if ( is_resource( $fp ) )
            {
                return $fp;
            }
        }

        return null;

    } // end of member function GetFileHandle

    /**
     * Try to open a file with fopen if allow_url_fopen permits, otherwise
     * use curl to open. NOTE: Remember to close the file handle.
     * @param $location string File location
     * @return file handle or null
     */
    static function OpenFile( $location )
    {
        global $g_dlog;

        if ( ! TpUtils::IsUrl( $location ) )
        {
            if ( !( $fp = fopen( $location, 'r' ) ) )
            {
                $g_dlog->debug( 'fopen error' );

                // Remove PHP warning
                TpDiagnostics::PopDiagnostic();

                return null;
            }
        }
        else if ( ini_get( 'allow_url_fopen' ) )
        {
            // This is a URL and we are permitted to fopen urls.

            if ( ! TpUtils::CheckUrl( $location ) )
            {
                $error = 'Unauthorized URL domain for: '.$location;
                TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );

                $g_dlog->debug( 'forbidden resource domain' );
                return null;
            }

            if ( !( $fp = fopen( $location, 'r' ) ) )
            {
                $g_dlog->debug( 'fopen error' );

                // Remove PHP warning
                TpDiagnostics::PopDiagnostic();

                return null;
            }
        }
        else
        {
            // This is a URL and we are not permitted to fopen urls, so use cURL.
            // Open a temporary file to write curl session results to.

            if ( ! TpUtils::CheckUrl( $location ) )
            {
                $error = 'Unauthorized URL domain for: '.$location;
                TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );

                $g_dlog->debug( 'forbidden resource domain' );
                return null;
            }

            $g_dlog->debug( 'Using curl to retrieve file' );

            $fp = tmpfile();
            $ch = curl_init( $location );
            curl_setopt( $ch, CURLOPT_FILE, $fp );
            curl_exec( $ch );

            $error = curl_error( $ch );

            if ( ! empty( $error )  )
            {
                $g_dlog->debug( 'curl error: '.$error );

                // Remove PHP warning
                TpDiagnostics::PopDiagnostic();

                return null;
            }

            curl_close( $ch );
            rewind( $fp );
        }

        return $fp;

    } // end of member function OpenFile

    /**
     * Check if a URL can be opened.
     * @param $location string URL
     * @return boolean
     */
    static function CheckUrl( $location )
    {
        if ( ! defined( 'TP_ACCEPTED_DOMAINS' ) )
        {
            return true;
	}

        $accepted_domains = unserialize( TP_ACCEPTED_DOMAINS );

        if ( ( ! is_array( $accepted_domains ) ) || empty( $accepted_domains ) )
        { 
            return true;
	}
            
        // get host name from URL
        preg_match( '@^(?:http://)?([^/:]+)@i', $location, $matches );
        $host = $matches[1];

        foreach( $accepted_domains as $accepted_domain )
        {
            $len1 = strlen( $host );
            $len2 = strlen( $accepted_domain );

            if ( strpos( $host, $accepted_domain ) == $len1-$len2 )
            {
                return true;
            }
	}

        return false;
    }

   /** Simple function that receives an ADODB meta columns array that  
     *  frequently contains column names in upper case as keys. This method
     *  returns the same array but using the original column names as keys.
     *  
     * @param array ADODB meta columns array
     *
     * @return array with original column names pointing to column objects
     */
    static function FixAdodbColumnsArray( $columns ) 
    {
        $ret_array = array();
  
        foreach ( $columns as $key => $object )
        {
            $ret_array[$object->name] = $object;
        }

        return $ret_array;

    } // end of FixAdodbColumnsArray

} // end of TpUtils
?>