<!doctype html public "-//W3C//DTD HTML 4.0 //EN">
<html>
<head>
<title>TapirLink Content Encoding Checker</title>
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
<h3><i>TapirLink Content Encoding Checker</i></h3>
<?php

if ( ! function_exists( 'mb_check_encoding' ) )
{
    die('Function mb_check_encoding unavailable. Please make sure that you have the PHP mbstring module installed and that the PHP version is >= 4.4.3 (or PHP5 >= 5.1.3)');
}

if ( ! isset( $_REQUEST['res'] ) )
{
    die('Missing mandatory parameter "res" (resource code)');
}

// Encodings that can be detected
$enc[] = 'ASCII';
$enc[] = 'ISO-8859-1';
$enc[] = 'ISO-8859-2';
$enc[] = 'ISO-8859-3';
$enc[] = 'ISO-8859-4';
$enc[] = 'ISO-8859-5';
$enc[] = 'ISO-8859-6';
$enc[] = 'ISO-8859-7';
$enc[] = 'ISO-8859-8';
$enc[] = 'ISO-8859-9';
$enc[] = 'ISO-8859-10';
$enc[] = 'ISO-8859-13';
$enc[] = 'ISO-8859-14';
$enc[] = 'ISO-8859-15';
$enc[] = 'JIS';
$enc[] = 'UTF-8';
$enc[] = 'UTF-7';
$enc[] = 'EUC-JP';
$enc[] = 'SJIS';
$enc[] = 'eucJP-win';
$enc[] = 'SJIS-win';
$enc[] = 'ISO-2022-JP';

// should be included first
require_once('../www/tapir_globals.php');
require_once('TpUtils.php');
require_once('TpResources.php');
require_once('TpDataSource.php');
require_once('TpLocalMapping.php');
require_once('TpConceptualSchema.php');
require_once('TpConcept.php');
require_once('TpConceptMapping.php');

$r_resources =& TpResources::GetInstance();

$r_resource =& $r_resources->GetResource( $_REQUEST['res'] );

if ( ! $r_resource )
{
    die('Could not find resource with code '.$_REQUEST['res']);
}

if ( ! $r_resource->ConfiguredDatasource( ) )
{
    die('Resource "'.$_REQUEST['res'].'" does not contain complete data source configuration yet');

}
if ( ! $r_resource->ConfiguredMapping( ) )
{
    die('Resource "'.$_REQUEST['res'].'" does not contain complete mapping configuration yet');
}

$r_resource->LoadConfig();

$r_ds =& $r_resource->GetDataSource();

$encoding = $r_ds->getEncoding();

echo "\n<b>Charset Specified in Configuration: </b>$encoding";
flush();

$r_mapping =& $r_resource->GetLocalMapping();

$r_mapped_schemas =& $r_mapping->GetMappedSchemas();

$num_fields = 0;

$selected_fields = TpUtils::GetVar( 'fields', array() );

$cn = $r_ds->GetConnection();

$flds = array();

foreach ( $r_mapped_schemas as $ns => $schema )
{
    $r_concepts =& $schema->GetConcepts();

    foreach ( $r_concepts as $id => $concept )
    {
        if ( ! $concept->IsMapped() )
        {
            continue;
        }

        $c_mapping = $concept->GetMapping();

        if ( $c_mapping->GetMappingType() <> 'SingleColumnMapping' )
        {
            continue;
	}

        ++$num_fields;

        if ( $num_fields == 1 )
        {
            echo "<form method=\"POST\" action=\"check_encoding.php\">\n";
            echo "\n<br/><br/>Select the fields to be tested. Please note that this test is not complete. It retrieves the first <input type=\"text\" name=\"limit\" value=\"".TpUtils::GetVar( 'limit', 100 )."\" size=\"6\"/> distinct values from each selected field and checks if the content is compatible with the charset specified in the configuration. If not, it tries to detect the charset.<br/><br/>\n";
            echo "<table border=\"0\" cellpadding=\"3\">\n";
            flush();
        }

        $table = $c_mapping->GetTable();
        $field = $c_mapping->GetField();

        $tf = $table.'.'.$field;

        if ( in_array( $tf, $flds ) )
        {
            continue;
        }

        $flds[] = $tf;

        $checked = '';

        if ( ( ! isset( $_REQUEST['check'] ) ) or 
             in_array( $tf, TpUtils::GetVar( 'fields', array() ) ) )
        {
            $checked = ' checked="1"';
        }

        echo "\n<tr><td class=\"e\"><input type=\"checkbox\" name=\"fields[]\" value=\"".$tf."\"".$checked."/></td><td class=\"e\">".$tf."</td>";

        if ( isset( $_REQUEST['check'] ) )
        {
            $result = 'not tested';

            if ( in_array( $tf, $selected_fields ) )
            {
                $sql = 'SELECT DISTINCT '.$tf.' FROM '.$table;

                $rs =& $cn->SelectLimit( $sql, TpUtils::GetVar( 'limit', 100 ), 0 );

                if ( is_object( $rs ) )
                {
                    $result = 'passed';

                    while ( ( ! $rs->EOF ) )
                    {
                        $data = $rs->fields[0];

                        if ( ! mb_check_encoding( $data, $encoding ) )
                        {
                            $suggested = mb_detect_encoding( $data, $enc );

                            $result = 'Failed! Detected '.$suggested;

                            break;
                        }

                        $rs->MoveNext();
                    }

                    $rs->Close();

                }
                else
                {
                    $err = $cn->ErrorMsg();

                    $result = 'Failed to select records: '.$err;

                    $r_data_source->ResetConnection();
                }
            } 

            echo "<td class=\"v\">".$result."</td></tr>";
        }

        flush();
    }
}

$r_ds->ResetConnection();

if ( $num_fields )
{
   echo "</table>\n";
   echo "<br/><input type=\"hidden\" name=\"res\" value=\"".$_REQUEST['res']."\"/>\n";
   echo "<input type=\"submit\" name=\"check\" value=\"Check\"/>\n";
   echo "</form>\n";
}
else
{
    die('Resource "'.$_REQUEST['res'].'" does not contain any mapping to fields in the database');
}

echo "<br/><a href=\"configurator.php\">back to the configuration interface</a>\n";

?>
</body>
</html>
