<!doctype html public "-//W3C//DTD HTML 4.0 //EN">
<html>
<head>
<title>TapirLink Installation/Configuration Checker</title>
<style type="text/css">
body {background-color: #ffffff; color: #000000;}
body, td, th, h1, h2 {font-family: sans-serif;}
pre {margin: 0px; font-family: monospace;}
a:link {color: #000099; text-decoration: none; background-color: #ffffff;}
a:hover {text-decoration: underline;}
table {border-collapse: collapse;}
.center {text-align: center;}
.center table { margin-left: auto; margin-right: auto; text-align: left;}
.center th { text-align: center !important; }
td, th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
h1 {font-size: 150%;}
h2 {font-size: 125%;}
.p {text-align: left;}
.e {background-color: #ccccff; font-weight: bold; color: #000000;}
.h {background-color: #9999cc; font-weight: bold; color: #000000;}
.v {background-color: #cccccc; color: #000000;}
.vr {background-color: #cccccc; text-align: right; color: #000000;}
img {float: right; border: 0px;}
hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
</style>
</head>
<body>
<h3><i>TapirLink Configuration Test</i></h3>
<?php

function includeErrorHandler ($errNo, $errMsg, $fileName, $lineNum, $vars)
{
    global $emsg;

    if ( $errNo == 2048 ) // ignore compatibility warnings
    {
        $emsg = "OK.<br />\n";
    }
    else
    {
        $emsg = "<pre>\nERROR (".$errNo.")\n";
        $emsg .= $errMsg."\n\n</pre>";
    }
}

function showHelp()
{
    $resources = TpResources::GetInstance();

    $ares = $resources->GetAllResources();

    if ( count( $ares ) > 0 )
    {
        echo '<p>Resources available:</p>';
        echo '<table border="0" cellpadding="3">';
        foreach ($ares as $res)
        {
            TpDiagnostics::Reset();

            $code = $res->GetCode();
            $status = $res->GetStatus();
            $configured_metadata = ( $res->ConfiguredMetadata() ) ? 'OK' : '<b>incomplete</b>';
            $configured_datasource = ( $res->ConfiguredDatasource() ) ? 'OK' : '<b>incomplete</b>';
            $configured_tables = ( $res->ConfiguredTables() ) ? 'OK' : '<b>incomplete</b>';
            $configured_localfilter = ( $res->ConfiguredLocalFilter() ) ? 'OK' : '<b>incomplete</b>';
            $configured_mapping = ( $res->ConfiguredMapping() ) ? 'OK' : '<b>incomplete</b>';
            $configured_settings = ( $res->ConfiguredSettings() ) ? 'OK' : '<b>incomplete</b>';
            echo '<tr><td class="e"><!-- a href="check.php?resource='.$code.'" -->'.
                 $code.'<!-- /a --></td><td class="v">status = '.$status.
                 '<br/>metadata: '.$configured_metadata.
                 '<br/>datasource: '.$configured_datasource.
                 '<br/>tables: '.$configured_tables.
                 '<br/>local filter: '.$configured_localfilter.
                 '<br/>mapping: '.$configured_mapping.
                 '<br/>settings: '.$configured_settings.
                 '</td></tr>';
        }
        echo '</table>';
    }
    else
    {
        echo '<p>No resources were found in your resources file ('
            .$resources->GetFile().').</p>';
        echo '<p>If you did not include any resource yet, you can use the '.
             '<a href="configurator.php">web configuration interface</a>. '.
             'Otherwise, check that the path to the file is correct and if '.
             'the web server user has permission to read it.';
    }
    echo '</body></html>';
    die();
}

//return 0=ADOdb field name, 1 = status code
function checkMetaColumns(&$cn,$c)
{
    $ares = array('','Table not found - check case of table name');
    $flds = $cn->MetaColumns($c[1]);
    if (count($flds) > 0)
    {
        $ares[1] = 'Field ('.$c[2].') not found.';
        foreach ($flds as $f)
        {
            if ($f->name == $c[2])
            {
                $ares[0] = $f->name;
                $ares[1] = "OK";
                return $ares;
            }
            elseif (strcasecmp($f->name,$c[2]) == 0)
            {
                $ares[0] = $f->name;
                $ares[1] = "Field found but case doesn't match";
                return $ares;
            }
        }
    }
    return $ares;
}

//0 = adodb field, 1 = concept name, 2 = field, 3= status
function checkColumn($fld,$tbl,&$Config)
{
    $ares = array($fld,'','','Field in table but not listed as concept.');
    foreach ($Config->m_concepts as $c)
    {
        if ((strcasecmp($tbl,$c[1]) == 0) && (strcasecmp($fld,$c[2]) == 0))
        {
            $ares[1] = $c[3];
            $ares[2] = $c[2];
            $ares[3] = "Check case of field name for this concept.";
            if ($c[2] == $fld)
                $ares[3] = 'OK';
            return $ares;
        }
    }
    return $ares;
}

function getTableList(&$Config)
{
    $ares = array();
    foreach ($Config->m_concepts as $c)
    {
        if (array_search($c[1],$ares) === FALSE)
        {
            array_push($ares,$c[1]);
        }
    }
    return $ares;
}




/////////////////////////////////////////////////
//Start here
/////////////////////////////////////////////////
// should be included first
require_once('../www/tapir_globals.php');
if ( ! defined( '_DEBUG' ) ) {
	define('_DEBUG', FALSE);
}
require_once('TpUtils.php');
require_once('TpResources.php');

$emsg = '';

//dv: update the include path to point to the digir code base
ini_set('include_path',ini_get('include_path').TP_PATH_SEP.realpath('../www/'));

echo "<h3>System</h3>\n";
echo "<table border=\"0\" cellpadding=\"3\">\n";
echo "<tr><td class=\"e\">PHP Version</td><td class=\"v\">".phpversion()."</td></tr>\n";
echo "<tr><td class=\"e\">Operating System</td><td class=\"v\">".php_uname()."</td></tr>\n";
echo "<tr><td class=\"e\">Web Server</td><td class=\"v\">".$_SERVER['SERVER_SOFTWARE']."</td></tr>\n";
echo "<tr><td class=\"e\">Interface Version</td><td class=\"v\">".$_SERVER['GATEWAY_INTERFACE']."</td></tr>\n";
echo "<tr><td class=\"e\">Protocol</td><td class=\"v\">".$_SERVER['SERVER_PROTOCOL']."</td></tr>\n";
echo "<tr><td class=\"e\">PHP Include Path</td><td class=\"v\">".implode('<br/>', explode(':', ini_get('include_path')))."</td></tr>\n";
echo "</table>\n";

echo '<br/><a href="info.php" target="_new">More info</a> about this PHP installation<br/>';

/////////////////////////////////////////////////
// Check PHP version

echo "\n<h3>Checking PHP version...</h3>\n";
flush();

$current_version = phpversion();

$ok = true;

if ( version_compare( $current_version, '5.0', '<' ) > 0 )
{
    if ( version_compare( $current_version, TP_MIN_PHP_VERSION, '<' ) > 0 )
    {
        echo "\n".'<br\><b>Warning:</b> PHP version '.TP_MIN_PHP_VERSION.
             ' or later required. Some features may not be available!<br />';
        $ok = false;
    }

    if ( version_compare( $current_version, '4.4.4', '<=' ) > 0 )
    {
        echo "\n".'<br\><b>Warning:</b> There are vulnerabilities in this PHP version '.
                  '(buffer overflows in functions htmlentities and htmlspecialchars). '.
                  'See <a href="http://www.hardened-php.net/advisory_132006.138.html" '.
                  'target="_new">announcement</a>. '.
                  'Please consider upgrading to a version greater than 4.4.4.<br />';
        $ok = false;
    }
}
else
{
    if ( version_compare( $current_version, '5.0.3', '<' ) > 0 )
    {
        echo "\n".'<br\><b>Error:</b> This version of PHP contains a bug in '.
             '"xml_set_start_namespace_decl_handler". <br \>If you want to use PHP5, you '.
             'should upgrade to at least version 5.0.3.<br />';
    }

    if ( version_compare( $current_version, '5.1.6', '<=' ) > 0 )
    {
        echo "\n".'<br\><b>Warning:</b> There are vulnerabilities in this PHP version '.
                  '(buffer overflows in functions htmlentities and htmlspecialchars) '.
                  'See <a href="http://www.hardened-php.net/advisory_132006.138.html" '.
                  'target="_new">announcement</a>. '.
                  'Please consider upgrading to a version greater than 5.1.6.<br />';
        $ok = false;
    }
}

if ( $ok )
{
    echo ' OK<br />';
}

/////////////////////////////////////////////////
// Check timezone setting

echo "\n<h3>Checking timezone configuration...</h3>\n";
flush();

$old_track = ini_set( 'track_errors', '1' );
$old_php_errormsg = $php_errormsg;
$tz = @date_default_timezone_get();

if ( $php_errormsg == $old_php_errormsg )
{
    echo 'OK<br />';
}
else
{
    echo '<b>Warning:</b> Timezone not properly defined. You should either set the \'date.timezone\' option in your PHP configuration file (php.ini) file, or enable the line with a call to the \'date_default_timezone_set\' function in your localconfig.php file inside the \'www\' directory (create the file as a copy of localconfig_dist.php if you don\'t have one yet).<br />';
}
ini_set( 'track_errors', $old_track );

/////////////////////////////////////////////////
// Check PHP configuration

echo "\n<h3>Checking PHP configuration...</h3>\n";
flush();

$memory_limit = ini_get( 'memory_limit' );

$val = trim( $memory_limit );

$last = strtolower( substr( $val, strlen($val)-1 ) );

switch ( $last )
{
    case 'g':
        $val *= 1024;
    case 'm':
        $val *= 1024;
    case 'k':
        $val *= 1024;
}

echo "\nmemory_limit... ".$memory_limit;

if ( $val >= 33554432 ) // 32M
{
    echo ' (OK)<br />';
}
else
{
    echo '<br /><b>Warning:</b> TapirLink can easily consume more than 10M when processing requests. It is advisable to set the memory_limit of your PHP installation (inside php.ini) to at least 32M.<br />';
}

$register_globals = ini_get('register_globals');

$result = ($register_globals) ? 'on' : 'off';

echo "<br/>\nregister_globals... ".$result;

if ( $register_globals )
{
    echo '<br /><b>Warning:</b> Activating "register_globals" usually makes PHP scripts '.
         'more vulnerable to security issues (see '.
         '<a href="http://www.php.net/manual/en/security.globals.php" target="_new">'.
         'http://www.php.net/manual/en/security.globals.php</a>). '.
         'It is strongly recommended to turn it off.<br />';
}
else
{
    echo ' (OK)<br />';
}

/////////////////////////////////////////////////
// Check libraries

$includes = array( 'XPath'         => TP_XPATH_LIBRARY,
                   'ADOdb'         => TP_ADODB_LIBRARY,
                   'HTTP_Request'  => 'HTTP/Request.php',
                   'functionCache' => 'Cache/Function.php');

echo "\n<h3>Checking installed libraries...</h3>\n";
$olderrorRep = error_reporting(0);
$olderrofunc = set_error_handler('includeErrorHandler');

// mbstring
echo "\nPHP mbstring extension...";

if ( TpUtils::LoadLibrary( 'mbstring' ) )
{
    echo "OK.<br/>\n";
}
else
{
    echo "<b>Failed:</b> Please check your PHP installation.<br/>\n";
}

// Internal libraries
foreach ($includes as $k=>$v)
{
    echo "\n".$k.'...';
    $emsg = "OK.<br/>\n";
    include_once($v);
    echo $emsg;
    flush();
}
restore_error_handler($olderrorfunc);
error_reporting($olderrorRep);

/////////////////////////////////////////////////
// Check Permissions

echo "\n<h3>Checking permissions...</h3>\n";
flush();

$dirs = array( 'Configuration directory' => array( TP_CONFIG_DIR   , 'rw' ),
               'Classes directory'       => array( TP_CLASSES_DIR  , 'r'  ),
               'Templates directory'     => array( TP_TEMPLATES_DIR, 'r'  ),
               'Log directory'           => array( TP_LOG_DIR      , 'rw' ),
               'Cache directory'         => array( TP_CACHE_DIR    , 'rw' ),
               'Debug directory'         => array( TP_DEBUG_DIR    , 'rw' ) );

foreach ( $dirs as $dir_name => $dir_data )
{
    $dir = $dir_data[0];
    $dir_permissions = $dir_data[1];

    echo "\n$dir_name [$dir]: ";

    if ( empty( $dir ) )
    {
        echo '<b>not defined!</b><br/>';
        continue;
    }
    else if ( ! file_exists( $dir ) )
    {
        echo '<b>does not exist!</b><br/>';
        continue;
    }
    else if ( ! is_readable( $dir ) )
    {
        echo '<b>not readable!</b><br/>';
        continue;
    }
    else if ( $dir_permissions == 'rw' and ! is_writable( $dir ) )
    {
        echo '<b>not writable!</b><br/>';
        continue;
    }

    echo 'OK<br/>';
}

echo '<br/>';

$res_file = TP_CONFIG_DIR.'/'.TP_RESOURCES_FILE;

echo "\nResources file [".$res_file.']: ';

$check_resources = true;

if ( empty( $res_file ) )
{
    echo '<b>not defined!</b><br/>';
    $check_resources = false;
}
else if ( ! file_exists( $res_file ) )
{
    echo '<b>does not exist</b>. Please run the <a href="configurator.php">web configuration interface</a> to create it and to include at least one resource.';
    $check_resources = false;
}
else if ( ! is_readable( $res_file ) )
{
    echo '<b>not readable!</b><br/>';
    $check_resources = false;
}
else if ( ! is_writable( $res_file ) )
{
    echo '<b>not writable!</b><br/>';
    $check_resources = false;
}

if ( ! $check_resources )
{
    echo '</body></html>';
    die();
}

/////////////////////////////////////////////////
//Start checking resource configuration
echo "<h3>Checking Resources...</h3>\n";
flush();

$resName = TpUtils::getVar('resource','');
if ($resName == '')
{
    showHelp();
}

// TODO: revise and reactivate code below
return;

/////////////////////////////////////////////////
//ok process a resource file.
//first extract the file name from the resources.xml file
echo '<p>Processing resource name: '.$resName.'</p>';
$resFile = getResourceConfigFile($resName,$res_file);
if (!$resFile)
{
    echo '<p><b>ERROR:</b> No configuration file is associated with resource name = ';
    echo $resName.'</p>';
    showHelp();
}
$resFile = realpath(TP_CONFIG_DIR.'/'.$resFile);

/////////////////////////////////////////////////
//Now load the Config information from the file...
echo '<p>Processing file: '.$resFile."</p>";
flush();
$cfgBuilder = new ConfigBuilder($resFile);
$Config = $cfgBuilder->Config;
echo "<h3>Your configuration file contents:</h3>";
$Config->Dump(TRUE);
flush();

echo "<hr /><h3>Checking Configuration Settings</h3>\n";

/////////////////////////////////////////////////
//Try connecting to the database using ADOdb
echo "<p>Testing connection to database...<br />\n";
flush();
$cn = &ADONewConnection($Config->connectionType);
if (!is_object($cn))
{
    echo " ERROR: could not create database connection object of type: "
        .$Config->connectionType."<br />";
    die('can not continue tests.');
}
echo "&nbsp;&nbsp;Created connection object OK.<br />\n";
flush();
$res = $cn->PConnect($Config->connectionString,
                     $Config->connectionUID,
                     $Config->connectionPWD,
                     $Config->connectionDB);
if (!$res)
{
    echo " ERROR: Could not open database connection.<br />\n";
    echo "ADODB:".$cn->errorMsg()."<br />\n";
    die('can not continue tests.');
}
echo "&nbsp;&nbsp;Connected to database OK.<br />\n";
flush();

/////////////////////////////////////////////////
//check list of tables - match names and case of names
echo "<h4>Checking for correct table names...</h4>\n";
$tables = $cn->MetaTables();
echo "<table border='1'>\n";
echo "<tr><th>Config Table Name</th><th>ADODB Table Name</th>
      <th>Message</th></tr>";
$warnCase = FALSE;
foreach ($Config->m_tables as $k=>$t)
{
    echo "<tr><td>".$t->m_name."</td>";
    $msg = '<td></td><td>Not found in database!</td>';
    foreach ($tables as $atab)
    {
        if (strcasecmp($t->m_name,$atab) === 0)
        {
            echo "<td>".$atab."</td>";
            $msg = '<td>Check case of name in config file</td>';
            if (strcmp($t->m_name,$atab) == 0)
            {
                $msg = '<td>OK</td>';
            }
            elseif (!$warnCase)
                $warnCase = TRUE;

        }
    }
    echo $msg."</tr>\n";
}
echo "</table>\n";
if ($warnCase)
{
    echo '<p>The case of the table names in one or more &lt;table&gt; elements
    of your configuration file does
    not match the case of the names reported by the ADOdb library.  For some
    platform + database combinations this is not important, but to minimize the
    chances of unexpected errors it is always a good idea to ensure that the
    case of the table names in the configuration file match those that are
    reported by the ADOdb library.  Note that the case of the names reported
    by ADOdb may not match the case of the names as they appear in your
    database management application!  The simplest solution is generally to
    update your configuration file and leave the database alone.</p>';
}
flush();

/////////////////////////////////////////////////
//check relationships between tables.
echo "<h4>Checking relationships between tables</h4>\n";

echo "<pre>";

print_r($Config->m_tables);

echo "</pre>";


/////////////////////////////////////////////////
//check concept field names and their data types
echo "<h4>Checking field names as known by database library...</h4>\n";

//open database connection
//Concepts = array:
    //[0] = type
    //[1] = table
    //[2] = field
    //[3] = name
    //[4] = zid
    //[5] = namespace
    //[6] = boolean searchable?
    //[7] = boolean returnable?
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$tableList = getTableList($Config);

foreach ($tableList as $t)
{
    echo "<h5>Table: ".$t."</h5>\n";
    $sql = "SELECT * FROM ".$t;
    $rs = $cn->SelectLimit($sql,1);
    if (!is_object($rs))
    {
        echo "<p>$sql produced no result set.<br />";
        echo "ADOdb reports: ".htmlEntities($cn->ErrorMsg())."<br />";
    }
    else
    {
        $arow = $rs->FetchRow();
        echo "<table border='1'>\n";
        echo "<tr><th>ADOdb Field</th><th>Name</th><th>Field</th><th>Status</th></tr>\n";
        foreach ($arow as $k=>$v)
        {
            $ares = checkColumn($k,$t,$Config);
            echo "<tr>";
            echo "<td>".$ares[0]."</td>";
            echo "<td>".$ares[1]."</td>";
            echo "<td>".$ares[2]."</td>";
            echo "<td>".$ares[3]."</td>";
            echo "</tr>\n";
        }
        echo "</table>";
        $rs->Close();
    }
}

/*
echo "<table border='1'>\n";
echo "<tr><th>Name</th><th>Table</th><th>Field</th><th>ADOdb Field</th><th>Status</th></tr>\n";
foreach ($Config->m_concepts as $k=>$c)
{
    echo "<tr>";
    echo "<td>".$c[3]."</td>";
    echo "<td>".$c[1]."</td>";
    echo "<td>".$c[2]."</td>";
    $ares = checkMetaColumns($cn,$c);
    echo "<td>".$ares[0]."</td>";
    echo "<td>".$ares[1]."</td>";
    echo "</tr>\n";
}
echo"</table>\n";

/////////////////////////////////////////////////
//check metatype of data
echo "<h4>Checking column metatypes...</h4>\n";

/////////////////////////////////////////////////
//check concept names by comparing with conceptual schema
*/

$cn->Close();

echo "<p>Completed.</p>"
?>
</body>
</html>
