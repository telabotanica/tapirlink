<?php
/**
 * $Id: tapir_globals.php 2027 2010-09-15 00:51:15Z rdg $
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
 *
 * NOTES
 *
 * Global constants for the TapirLink provider and associated services
 * This script also sets the include_path for this instance of PHP
 * so that the libaries can be located.
 *
 * Running this script without being included within another script
 * such as TapirLink entry point results in a number of tests being run to
 * check the values of configurable variables.
 *
 * The default installation assumes the following folde structure
 *
 * TapirLink
 *    config    		(configuration files- NOT web accessible)
 *    classes   		(classes- NOT web accessible)
 *    templates 		(templates- NOT web accessible)
 *    cache     		(location of temporary cache files- writable by web)
 *    log		(location of log files - writable by web)
 *    www		(root folder of DiGIR script - web accessible)
 *    lib		(folder for external libraries - NOT web accessible)
 *       pear		(PEAR libraries)
 *       xpath		(XPath processor support library)
 *       adodb		(SQL database abstraction library)
 *
 */

// Change all parameter names to lower case if the Web Service is running
if (  defined( 'TP_RUNNING_TAPIR' ) )
{
    $_REQUEST = array_change_key_case( $_REQUEST, CASE_LOWER );
}

// Load possible local configuration file, which can be used to override the default
// values for the defines in this file.
ini_set( 'include_path', '.' );

$tmp = @include_once( 'localconfig.php' );

/*
* Indicate the minimum level of diagnostic messages to appear in responses.
* Options are: 0 (DEBUG), 1 (INFO), 2 (WARN), 3 (ERROR)
* Setting 1 (INFO) will avoid debug diagnostics, setting 2 (WARN) will avoid
* info and debug diagnostics, and so on.
*/
if ( ! defined( 'TP_DIAG_LEVEL' ) )
{
    define( 'TP_DIAG_LEVEL', 1 );
}

// Set the root folder to the parent of the folder the script is being run from.

if ( ! defined( 'TP_WWW_DIR' ) )
{
    $root_dir = dirname(__FILE__);

    define( 'TP_WWW_DIR', $root_dir );
}

// The full path to the directory used to contain configuration.

if ( ! defined( 'TP_CONFIG_DIR' ) ) {

    define( 'TP_CONFIG_DIR', realpath(TP_WWW_DIR.'/../config') );
}

// The full path to the directory used to contain classes.

if ( ! defined( 'TP_CLASSES_DIR ') )
{
    define( 'TP_CLASSES_DIR', realpath(TP_WWW_DIR.'/../classes') );
}

// The full path to the directory used to contain templates.

if ( ! defined( 'TP_TEMPLATES_DIR' ) )
{
    define( 'TP_TEMPLATES_DIR', realpath(TP_WWW_DIR.'/../templates') );
}

// The full path to the directory used to store log files

if ( ! defined( 'TP_LOG_DIR' ) )
{
    define( 'TP_LOG_DIR', realpath(TP_WWW_DIR.'/../log') );
}

// The full path to the directory used to track statistics

if ( ! defined( 'TP_STATISTICS_DIR' ) )
{
    define( 'TP_STATISTICS_DIR', realpath(TP_WWW_DIR.'/../statistics/') );
}

/**
* The directory that will be used by the cache. This is a full path to a folder
* that has permissions for the php interpreter to create sub-folders and write
* content. The specified folder must exist.
*/
if ( ! defined( 'TP_CACHE_DIR' ) )
{
    define( 'TP_CACHE_DIR', realpath(TP_WWW_DIR.'/../cache') );
}

/**
* This folder is used to stash incoming requests when TP_STASH_REQUEST is true and
* to write detailed log for debugging when TP_LOG_DEBUG is true.
*/
if ( ! defined( 'TP_DEBUG_DIR' ) )
{
    define( 'TP_DEBUG_DIR', TP_LOG_DIR );
}

// Try and determine the appropriate path separator
// windows = ";", unix = ":"
// Note that the PHP constant PATH_SEPARATOR was only included in PHP 4.3.0

if ( ! defined( 'TP_PATH_SEP' ) )
{
    if ( strtoupper( substr( PHP_OS, 0, 3) ) == 'WIN' )
    {
        define( 'TP_PATH_SEP', ';' );
    }
    else
    {
        define( 'TP_PATH_SEP', ':' );
    }
}

// The include path to find libaries.

if ( ! defined( 'TP_INCLUDE_PATH' ) )
{
    $pth = '.';

    $pth .= TP_PATH_SEP.realpath( TP_WWW_DIR );
    $pth .= TP_PATH_SEP.realpath( TP_CLASSES_DIR );
    $pth .= TP_PATH_SEP.realpath( TP_WWW_DIR.'/../lib/pear' );
    $pth .= TP_PATH_SEP.realpath( TP_WWW_DIR.'/../lib' );
    $pth .= TP_PATH_SEP.realpath( TP_TEMPLATES_DIR );

    define( 'TP_INCLUDE_PATH', $pth );
}

$current_include_path = ini_get( 'include_path' );

ini_set( 'include_path', TP_INCLUDE_PATH .TP_PATH_SEP. $current_include_path );

// A mini-test of the include path

$tmp = @include_once( 'PEAR.php' );
$tmp = @include_once( 'Log.php' );

if ( ! $tmp )
{
    // This is a serious problem. Log.php is in the PEAR
    // libraries, and if it can not be loaded then we can not
    // continue, since it is likely that the PEAR libraries are in the
    // wrong place.
    $msg = "Fatal Error. This provider is not configured correctly. \n";
    $msg .= "The include_path does not resolve the location of the required libraries. \n";
    $msg .= "Include path = ".ini_get( 'include_path' );

    if ( headers_sent() )
    {
        echo "<error>".$msg."</error>";
    }
    else
    {
        echo "<pre>\n".$msg;
    }

    flush();
    die();
}

include_once('tapir_errors.php');

/*
* Indicates if the request must be stashed in TP_DEBUG_DIR
*/
if ( ! defined( 'TP_STASH_REQUEST' ) )
{
    define( 'TP_STASH_REQUEST', FALSE );
}

if ( ! defined( 'TP_STASH_FILE' ) )
{
    define( 'TP_STASH_FILE', 'req.txt' );
}

/*
* Indicates if details debugging must be stored in a separate log file in TP_DEBUG_DIR
*/
if ( ! defined( 'TP_LOG_DEBUG' ) )
{
    define( 'TP_LOG_DEBUG', FALSE );
}

if ( ! defined( 'TP_DEBUG_LOGFILE' ) )
{
    define( 'TP_DEBUG_LOGFILE', 'debug.txt' );
}

// This is the maximum time in seconds that the script can run.

if ( ! defined( 'TP_MAX_RUNTIME' ) ) {

    define( 'TP_MAX_RUNTIME', 120 );
}

/**
* The file name of the Resources xml file which contains a list of resource
* names and associated configuration information.  Must be located in the
* TP_CONFIG_DIR folder.
*/
if ( ! defined( 'TP_RESOURCES_FILE' ) )
{
    define ( 'TP_RESOURCES_FILE', 'resources.xml' );
}

/**
* The file name of the Schemas xml file which contains a list of conceptual
* schemas that can be used during provider configuration.  Must be located in the
* TP_CONFIG_DIR folder.
*/
if ( ! defined( 'TP_SCHEMAS_FILE') )
{
    define ( 'TP_SCHEMAS_FILE', 'schemas.xml' );
}

/**
* The file name of an XML file which contains an index of files that have
* mapping references for a particular conceptual schema (to help on automapping).
*/
if ( ! defined( 'TP_INDEX_OF_MAPPING_REFERENCES') )
{
    define ( 'TP_INDEX_OF_MAPPING_REFERENCES', 'http://rs.tdwg.org/tapir/cs/mappings/index.xml' );
}

/**
* Indicates whether the caching system should be used.  If you have a fast
* system and like to see the CPU meter pegged, then it's probably ok to
* turn caching off...
*/
if ( ! defined( 'TP_USE_CACHE' ) )
{
    define( 'TP_USE_CACHE', TRUE );
}

/**
* Number of seconds that the php function gethostbyaddr will be used before
* forcing an update. Default is two days.
*/
if ( ! defined( 'TP_GETHOST_CACHE_LIFE_SECS' ) )
{
    define( 'TP_GETHOST_CACHE_LIFE_SECS', 172800 );
}

/**
* Number of seconds that cached metadata will be used before forcing an update.
*/
if ( ! defined( 'TP_METADATA_CACHE_LIFE_SECS' ) )
{
    define( 'TP_METADATA_CACHE_LIFE_SECS', 0 );
}

/**
* Number of seconds that cached capabilities will be used before forcing an update.
*/
if ( ! defined( 'TP_CAPABILITIES_CACHE_LIFE_SECS' ) )
{
    define( 'TP_CAPABILITIES_CACHE_LIFE_SECS', 0 );
}

/**
* Indicates the time in seconds that an inventory response will remain in the cache.
*/
if ( ! defined( 'TP_INVENTORY_CACHE_LIFE_SECS' ) )
{
    define( 'TP_INVENTORY_CACHE_LIFE_SECS', 0 );
}

/**
* Indicates the time in seconds that a search response will remain in the cache.
*/
if ( ! defined( 'TP_SEARCH_CACHE_LIFE_SECS' ) )
{
    define( 'TP_SEARCH_CACHE_LIFE_SECS', 0 );
}

/**
* Number of seconds that cached templates will be used before forcing an update.
* Default is once a year.
*/
if ( ! defined( 'TP_TEMPLATE_CACHE_LIFE_SECS' ) )
{
    define( 'TP_TEMPLATE_CACHE_LIFE_SECS', 31536000 );
}

/**
* Number of seconds that cached output models will be used before forcing an update.
* Default is once a year.
*/
if ( ! defined( 'TP_OUTPUT_MODEL_CACHE_LIFE_SECS' ) )
{
    define( 'TP_OUTPUT_MODEL_CACHE_LIFE_SECS', 31536000 );
}

/**
* Number of seconds that cached response structures will be used before
* forcing an update. Default is once a year.
*/
if ( ! defined( 'TP_RESP_STRUCTURE_CACHE_LIFE_SECS' ) )
{
    define( 'TP_RESP_STRUCTURE_CACHE_LIFE_SECS', 31536000 );
}

/**
* Number of seconds that cached SQL counts can be used before
* forcing another hit in the dabatase. Default is once a week.
*/
if ( ! defined( 'TP_SQL_COUNT_CACHE_LIFE_SECS' ) )
{
    define( 'TP_SQL_COUNT_CACHE_LIFE_SECS', 604800 );
}

/**
* Indicates how file retrieval should happen. Possible values:
* prefer_original: Prefer original files directly specified in the request or
*                  inside other documents referenced by the request. These are
*                  usually remote files and always the most up-to-date).
* prefer_local: Prefer local copies that could be manually stored in
*               TP_LOCAL_REPOSITORY.
* only_local: Always use local copies that were manually stored in
*             TP_LOCAL_REPOSITORY.
*
* Please note that this setting has nothing to do with the other caching settings.
*/
if ( ! defined( 'TP_FILE_RETRIEVAL_BEHAVIOUR' ) )
{
    define( 'TP_FILE_RETRIEVAL_BEHAVIOUR', 'prefer_original' );
}

/*
* Accepted domains for external resource retrieval
*/
if ( ! defined( 'TP_ACCEPTED_DOMAINS' ) )
{
    $domains = array( 'tdwg.org', 'gbif.org', 'cria.org.br', 'dublincore.org', 'www.w3.org', 'darwincore.googlecode.com', $_SERVER['HTTP_HOST'], '127.0.0.1', 'localhost' );

    define( 'TP_ACCEPTED_DOMAINS', serialize( $domains ) );
}

/**
* This is the relative or absolute path to the xpath library
*/
if ( ! defined( 'TP_XPATH_LIBRARY' ) )
{
    define( 'TP_XPATH_LIBRARY', 'xpath/XPath.class.php' );
}

/**
* This is the relative or absolute path to the php ADODB library
*/
if ( ! defined( 'TP_ADODB_LIBRARY' ) ) {

    define( 'TP_ADODB_LIBRARY', 'adodb/adodb.inc.php' );
}

/**
* The enable or disable (FALSE) the use of the mb_string library. This library
* is required for translation between character encodings, and so if your
* data or metadata contain *any* characters outside of 7 bit ASCII, then you
* *must* enable use of the mbstring library.
*/
if ( ! defined( 'TP_USE_MBSTRING' ) )
{
    define( 'TP_USE_MBSTRING', TRUE );
}

/**
* Load the mbstring library if not already loaded.
* Note that dl() is only available when the PHP interpreter is running
* in CGI mode.
*/
if ( TP_USE_MBSTRING )
{
    // try and load it using dl()
    $res = TpUtils::LoadLibrary( 'mbstring' );

    if ( ! $res )
    {
        $msg = 'The "mbstring" library is not available for character conversions';
        TpDiagnostics::Append( DC_MISSING_LIBRARY, $msg, DIAG_WARN );
    }
}

/**
* For logging.
*/

if ( ! defined( 'TP_LOG_TYPE' ) )
{
    define( 'TP_LOG_TYPE', 'file' );
}

if ( ! defined( 'TP_LOG_NAME' ) )
{
    define( 'TP_LOG_NAME', 'history.txt' );
}

/**
* Log level.  Valid values for this are
* PEAR_LOG_EMERG
* PEAR_LOG_ALERT
* PEAR_LOG_CRIT
* PEAR_LOG_ERR
* PEAR_LOG_WARNING
* PEAR_LOG_NOTICE
* PEAR_LOG_INFO
* PEAR_LOG_DEBUG
* Table installations should set this to PEAR_LOG_INFO to record transactions
*/
if ( ! defined( 'TP_LOG_LEVEL' ) )
{
    define( 'TP_LOG_LEVEL', PEAR_LOG_INFO );
}

// note for statistics to work, the log file format MUST have these options
if ( ! defined( 'TP_LOG_OPTIONS' ) )
{
    define( 'TP_LOG_OPTIONS', serialize(
                              array( 'timeFormat' => '%b %d %Y	%H:%M:%S',
                                     'lineFormat' => "%1s	%2s	[%3s]	%4s" ) ) );
}

/*
* Set to true if you want to track statistics
*/
if ( ! defined( 'TP_STATISTICS_TRACKING' ) )
{
    define( 'TP_STATISTICS_TRACKING', true );
}

/**
* Other statistics settings
*/
if ( ! defined( 'TP_STATISTICS_RESOURCE_TABLE' ) )
{
    define( 'TP_STATISTICS_RESOURCE_TABLE', 'resources.tbl' );
}

if ( ! defined( 'TP_STATISTICS_SCHEMA_TABLE' ) )
{
    define( 'TP_STATISTICS_SCHEMA_TABLE', 'schema.tbl' );
}

/**
* Set this to true to allow incoming debug requests.
*/
if ( ! defined( 'TP_ALLOW_DEBUG' ) )
{
    define( 'TP_ALLOW_DEBUG', FALSE );
}

//////////////////////////////////////////////////////
// SQL definitions

if ( ! defined( 'TP_SQL_QUOTE' ) )
{
    define( 'TP_SQL_QUOTE', "'" );
}

if ( ! defined( 'TP_SQL_QUOTE_ESCAPE' ) )
{
    define( 'TP_SQL_QUOTE_ESCAPE', "''" );
}

if ( ! defined( 'TP_SQL_WILDCARD' ) )
{
    define( 'TP_SQL_WILDCARD', '%' );
}

/**
* Set this to true to delimit table/column names in the SQL statement with double quotes.
* This may be necessary for database schemas that use case sensitive names or names that
* coincide with reserved words.
*/
if ( ! defined( 'TP_SQL_DELIMIT_NAMES' ) )
{
    define( 'TP_SQL_DELIMIT_NAMES', false );
}

/**
* Set this to true to use column references (the order of the column in
* SELECT {columns}) instead of the column names in GROUP BY or ORDER BY
* clauses. You should do this for PostgreSQL databases.
*/
if ( ! defined( 'TP_SQL_USE_COLUMN_REF' ) )
{
    define( 'TP_SQL_USE_COLUMN_REF', false );
}

/**
* Set this to true to use SELECT COUNT(*) to count records. Default is the
* opposite (to execute the entire query and let PHP ADOdb calculate the number of
* records through the "RecordCount" method). However, this may be VERY memory
* intensive depending on the database and on the number of records, that's
* why this configuration option was created. Please note that for certain
* databases, COUNT(*) may return an approximate number, and perhaps it may
* not even support the alternative SQL construction being used:
* SELECT COUNT(*) FROM (SELECT ...) AS src
*/
if ( ! defined( 'TP_SQL_USE_COUNT' ) )
{
    define( 'TP_SQL_USE_COUNT', false );
}

//////////////////////////////////////////////////////
// AUTOMATIC UPDATES

if ( ! defined( 'TP_CHECK_UPDATE_URL' ) )
{
    define( 'TP_CHECK_UPDATE_URL', 'http://rs.tdwg.org/tapir/software/tlink.xml' );
}

//////////////////////////////////////////////////////
// UDDI

if ( ! defined( 'TP_UDDI_TMODEL_NAME' ) )
{
    define( 'TP_UDDI_TMODEL_NAME', 'TAPIR' );
}

if ( ! defined( 'TP_UDDI_OPERATOR_NAME' ) )
{
    define( 'TP_UDDI_OPERATOR_NAME', '' );
}

if ( ! defined( 'TP_UDDI_INQUIRY_URL' ) )
{
    define( 'TP_UDDI_INQUIRY_URL', '' );
}

if ( ! defined( 'TP_UDDI_INQUIRY_PORT' ) )
{
    define( 'TP_UDDI_INQUIRY_PORT', 80 );
}

if ( ! defined( 'TP_UDDI_PUBLISH_URL' ) )
{
    define( 'TP_UDDI_PUBLISH_URL', '' );
}

if ( ! defined( 'TP_UDDI_PUBLISH_PORT' ) )
{
    define( 'TP_UDDI_PUBLISH_PORT', 80 );
}

/////////////////////////////////////////////////////////////////////////////
// Nothing to change past here
/////////////////////////////////////////////////////////////////////////////

define( 'TP_MIN_PHP_VERSION', '4.2.3' );

define( 'TP_VERSION', '0.7.1' );

$revision = '$Revision: 2027 $.';

$revision_regexp = '/^\$'.'Revision:\s(\d+)\s\$\.$/';

if ( preg_match( $revision_regexp, $revision, $matches ) )
{
    $revision = $matches[1];
}

define( 'TP_REVISION', $revision );

define( 'TP_NAMESPACE','http://rs.tdwg.org/tapir/1.0' );
define( 'TP_SCHEMA_LOCATION','http://rs.tdwg.org/tapir/1.0/schema/tdwg_tapir.xsd' );
define( 'XMLSCHEMANS','http://www.w3.org/2001/XMLSchema' );
define( 'XMLSCHEMAINST','http://www.w3.org/2001/XMLSchema-instance' );

define( 'TP_DC_PREFIX'   , 'dc'    );
define( 'TP_DCT_PREFIX'  , 'dct'   );
define( 'TP_GEO_PREFIX'  , 'geo'   );
define( 'TP_VCARD_PREFIX', 'vcard' );
define( 'TP_XML_PREFIX'  , 'xml'   );
define( 'TP_XSI_PREFIX'  , 'xsi'   );

define('XML_HEADER', '<?xml version="1.0" encoding="utf-8" ?>');

?>