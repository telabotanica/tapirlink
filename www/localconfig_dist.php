<?php
/**
* This file contains elements that may need to be adjusted to work with your
* particular installation. Before making any changes copy localconfig_dist.php
* to a new file that MUST be called localconfig.php. All changes MUST be done
* in localconfig.php.
*
* Default values that *should* work for an unmodified installation are
* set in tapir_globals.php.
*
* To change a value, uncomment the line (i.e. remove the // from the start
* of the line, and set the value accordingly. Note that this is a PHP
* script file, so edits you make must be valid PHP. Also make sure you do
* not accidentally insert any trailing spaces or lines at the end of the
* document after the closing ">"
*/

/**
 * Default timezone setting. Check http://www.php.net/manual/en/timezones.php
 * for the available options */
//date_default_timezone_set('America/Sao_Paulo');

/**
* Location of the provider configuration files. You can change this to a
* location anywhere on your system but remember that it must be readable
* and writable by the php application. It is ok, and actually preferable to
* use forward slashes here if you are on a windows system. If you feel you
* must use back slashes, remember that you must use "\\" to specify a single
* backslash. e.g. "c:\\inetpub\\wwwroot\\tapirlink\\"
*
* Security Note: You should really move this configuration directory to a
* location that is not accessible by a web browser. Otherwsie anyone
* could connect to your config directory and discern the necessary
* details for connecting directly to your database.
* A more sendible location for the config files would be something like
* c:/tapirlink/config
*/
//define('TP_CONFIG_DIR','c:/tapirlink/config');

/**
* Location of the log files.
*/
//define('TP_LOG_DIR','c:/tapirlink/log');

/**
* The name of the file that log information will be recorded to.
*/
//define('TP_LOG_NAME','history.txt');

/**
* Log level.  Valid values for this are (use the numeric form only)
* 0 = PEAR_LOG_EMERG = minimal logging information
* 1 = PEAR_LOG_ALERT
* 2 = PEAR_LOG_CRIT
* 3 = PEAR_LOG_ERR
* 4 = PEAR_LOG_WARNING
* 5 = PEAR_LOG_NOTICE
* 6 = PEAR_LOG_INFO (recommended for stable installations and
*                    required for statistics stracking)
* 7 = PEAR_LOG_DEBUG
*/
//define('TP_LOG_LEVEL', PEAR_LOG_INFO);

/*
* Indicate the minimum level of diagnostic messages to appear in responses.
* Options are: 0 (DEBUG), 1 (INFO), 2 (WARN), 3 (ERROR)
* Setting 1 (INFO) will avoid debug diagnostics, setting 2 (WARN) will avoid
* info and debug diagnostics, and so on. Deafult is 1 (INFO).
*/
//define( 'TP_DIAG_LEVEL', 3 );

/**
* Set this true to allow incoming debug requests
* (not recommended for stable installations).
*/
//define('TP_ALLOW_DEBUG', true);

/**
* Set this true to enable debug anyway. This will set error_reporting to E_ALL and
* will output debug diagnostics in the response XML.
* (not recommended for stable installations).
*/
//define('_DEBUG', true);

/**
* Set to true if you want to store detailed debugging information in a separate file
*/
//define('TP_LOG_DEBUG', true);

/**
* Directory to store debug information.
*/
//define('TP_DEBUG_DIR', TP_LOG_DIR);

/**
* Name of the debug file
*/
//define('TP_DEBUG_LOGFILE', 'debug.txt');

/*
* Set to true if you want to stash the last incoming request in TP_DEBUG_DIR
*/
//define('TP_STASH_REQUEST', true);

/*
* Name of the file with the request
*/
//define('TP_STASH_FILE', 'req.txt');

/*
* Set to true if you want to track statistics
*/
//define('TP_STATISTICS_TRACKING', true);

/**
* Location of the statistics files.
*/
//define('TP_STATISTICS_DIR','c:/tapirlink/statistics');

/**
* Set to true if you want to use cache
*/
//define('TP_USE_CACHE', false);

/**
* The location of a folder that is to contain the cached responses.
* it is ok to delete files from this folder - the provider will simply rebuild the
* files if necessary. There is already a default directory for this purpose, so you
* don't need to uncomment the following line unless you really want a different setting.
*/
//define('TP_CACHE_DIR', 'c:/tapirlink/cache');

/**
* Number of seconds that the php function gethostbyaddr will be used before
* forcing an update. Default is two days.
*/
//define('TP_GETHOST_CACHE_LIFE_SECS' , 172800);

/**
* Number of seconds that cached metadata will be used before forcing an update.
* Default is once a day.
*/
//define('TP_METADATA_CACHE_LIFE_SECS', 86400);

/**
* Number of seconds that cached capabilities will be used before forcing an update.
* Default is once a day.
*/
//define('TP_CAPABILITIES_CACHE_LIFE_SECS', 86400);

/**
* Indicates the time in seconds that an inventory response will remain in the cache.
* Default duration is one hour.
*/
//define('TP_INVENTORY_CACHE_LIFE_SECS', 3600);

/**
* Indicates the time in seconds that a search response will remain in the cache.
* Default duration is one hour.
*/
//define('TP_SEARCH_CACHE_LIFE_SECS', 3600);

/**
* Number of seconds that cached templates will be used before forcing an update.
* Default is once a year.
*/
//define('TP_TEMPLATE_CACHE_LIFE_SECS', 31536000);

/**
* Number of seconds that cached output models will be used before forcing an update.
* Default is once a year.
*/
//define('TP_OUTPUT_MODEL_CACHE_LIFE_SECS', 31536000);

/**
* Number of seconds that cached response structures will be used before
* forcing an update. Default is once a year.
*/
//define('TP_RESP_STRUCTURE_CACHE_LIFE_SECS', 31536000);

/**
* Number of seconds that cached SQL counts will be used before forcing another
* hit in the database. Default is once a week. Set to zero to disable this cache.
*/
//define( 'TP_SQL_COUNT_CACHE_LIFE_SECS', 604800 );

/**
* Skin identifier (subdirectory name inside "www/skins" directory) indicating
* a set of XSLT and CSS files to be used on top of responses.
*/
//define('TP_SKIN', 'default');

/**
* Set this to true to delimit table/column names in the SQL statement with double quotes.
* This may be necessary for database schemas that use case sensitive names or names that
* coincide with reserved words.
*/
//define( 'TP_SQL_DELIMIT_NAMES', true );

/**
* Set this to true to use column references (the order of the column in
* SELECT {columns}) instead of the column names in GROUP BY or ORDER BY
* clauses. You should do this for PostgreSQL databases.
*/
//define( 'TP_SQL_USE_COLUMN_REF', true );

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
//define( 'TP_SQL_USE_COUNT', true );

/**
* The following settings can be used as default values by the configurator
* during UDDI registration process.
*/
//define('TP_UDDI_TMODEL_NAME'  , 'TAPIR 1.0');
//define('TP_UDDI_OPERATOR_NAME', 'GBIFS');
//define('TP_UDDI_INQUIRY_URL'  , 'http://registry.gbif.net/uddi/inquiry');
//define('TP_UDDI_INQUIRY_PORT' , 80);
//define('TP_UDDI_PUBLISH_URL'  , 'http://registry.gbif.net/uddi/publish');
//define('TP_UDDI_PUBLISH_PORT' , 80);

/**
* Languages displayed in the metadata form of the web configurator.
* To customize, uncomment the lines below and change the $langs
* array accordingly.
*
* NOTE: Make sure you follow the IETF RFC 3066 standard for the codes:
* http://www.ietf.org/rfc/rfc3066.txt
*/
//$langs = array('en' => 'English',
//               'fr' => 'French',
//               'de' => 'German',
//               'it' => 'Italian',
//               'pt' => 'Portuguese',
//               'es' => 'Spanish');
//
//define( 'TP_LANG_OPTIONS', serialize( $langs ) );

/**
* File containing an index of files that have mapping references
* for a particular conceptual schema (to help on automapping).
*/
//define('TP_INDEX_OF_MAPPING_REFERENCES', 'http://somehost/somefile.xml');

/**
* Alternative location for files that need to be opened by the service.
* This can be especially useful for providers that are behind a proxy or
* for providers that only want to load local files.
*/
//define('TP_LOCAL_REPOSITORY', 'http://localhost/somepath/' or 'file:///somepath' or '/somelocalpath');

/**
* Indicates how file retrieval should happen: prefer original files (usually remote
* and always the most up-to-date), prefer local copies (manually stored in
* TP_LOCAL_REPOSITORY) or use only local copies. Note that this has nothing to do
* with the other caching settings.
*/
//define('TP_FILE_RETRIEVAL_BEHAVIOUR', 'prefer_original' or 'prefer_local' or 'only_local');

/**
* List of accepted domains for remote resource retrieval. Use an empty array for no
* restrictions, but beware that this is not recommended in production environments
* for security reasons! If you don't define this setting, there's an internal
* default value which restricts domains to tdwg.org, gbif.org, cria.org.br,
* dublincore.org, www.w3.org, darwincore.googlecode.com and the local values
* $_SERVER['HTTP_HOST'], 127.0.0.1 and localhost
*/
//$domains = array( 'www.tela-botanica.org', 'tdwg.org', 'gbif.org', 'cria.org.br', 'dublincore.org', 'www.w3.org', 'darwincore.googlecode.com', $_SERVER['HTTP_HOST'], '127.0.0.1', 'localhost' );
//
//define( 'TP_ACCEPTED_DOMAINS', serialize( $domains ) );

/**
* When using automatic updates, you can specify an alternative link to
* indicate the latest stable revision number instead of the default link
* below. Note: changing this setting is only recommended for those who are
* closely monitoring TapirLink development.
*/
//define( 'TP_CHECK_UPDATE_URL', 'http://rs.tdwg.org/tapir/software/tlink.xml' );

/**
* Note that it is also possible to change some of your default PHP operational
* parameters here. For example, you could change the include_path setting
* to work with your system if you do not have access to the PHP.ini file.
* Example for Linux systems to add ".." to the path. Note that ":" is used for
* separating entries in that OS.
*/

/*
$tmp = ini_get('include_path');
ini_set('include_path', $tmp.':..');
*/

/**
* Important: Make sure that there are no extra lines or spaces after the >
*/
?>