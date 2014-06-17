<?php

if( !isset($mainPage) )
{
    $mainPage="index.php";
}

header("Pragma: no-cache"); 
header("Cache-Control: no-cache");
require_once( '../www/tapir_globals.php' );

//BUBU redundant?
$monthNames = array('null', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
//BUGBUG should use TapirLink global variables
include('filesorter.php'); //will sort any log files found in the log dir in chronological order

include('statistics_include.php'); //will sort any log files found in the log dir in chronological order

require_once( 'TpStatistics.php' );

$providerName = 'TapirLink';

if( !file_exists( TP_STATISTICS_DIR .'/'. TP_STATISTICS_RESOURCE_TABLE ) )
{
    touch( TP_STATISTICS_DIR .'/'. TP_STATISTICS_RESOURCE_TABLE);
}
if( !file_exists( TP_STATISTICS_DIR .'/'. TP_STATISTICS_SCHEMA_TABLE ) )
{
    touch( TP_STATISTICS_DIR .'/'. TP_STATISTICS_SCHEMA_TABLE );
}

$action=isset($_GET['action'])?$_GET['action']:'';

if( $action == 'summary' )
{
    $month=$_GET['month'];
    $year=$_GET['year'];

    $startMonth = $_GET['startmonth'];
    $endMonth   = $_GET['endmonth'];
    $startYear  = $_GET['startyear'];
    $endYear    = $_GET['endyear'];

    $resource=$_GET['resource'];
    $detailed=$_GET['detailed'];
    print( getResourceSummaryPage( $providerName, $month, $year, $resource, $detailed, $startMonth, $endMonth, $startYear, $endYear ) );
}
elseif( $action == 'monthdetail' )
{
    //set_time_limit(180);
    $month=$_GET['month'];
    $year=$_GET['year'];
    $resource=$_GET['resource'];
    $detailed=$_GET['detailed'];
    print( getResourceMonthDetailPage( $providerName, $month, $year, $resource, $detailed ) );  
}
elseif( $action == 'daydetail' )
{
    $month=$_GET['month'];
    $day= $_GET['day'];
    $year=$_GET['year'];
    $resource=$_GET['resource'];
    $ascsv= isset( $_GET['ascsv'] ) ? $_GET['ascsv'] : 0;
    print( getResourceDayDetailPage( $providerName, $month, $day, $year, $resource, $ascsv ) );
}
elseif( $action == 'custom' )
{  
    $startMonth = $_GET['startmonth'];
    $endMonth   = $_GET['endmonth'];
    $startYear  = $_GET['startyear'];
    $endYear    = $_GET['endyear'];

    $startDay  = $_GET['startday'];
    $endDay    = $_GET['endday'];

    $selectHost   = $_GET['selectHost'];
    $selectIP     = $_GET['selectIP'];
    $selectRecs   = $_GET['selectRecs'];
    $selectQuery  = $_GET['selectQuery'];
    $selectSchema = $_GET['selectSchema'];

    $textHost   = $_GET['textHost'];
    $textIP     = $_GET['textIP'];
    $textRecs   = $_GET['textRecs'];
    $textQuery  = $_GET['textQuery'];
    $textSchema = $_GET['textSchema'];

    $textBox    = $_GET['textbox'];
    $resources  = $_GET['resource'];

    $astab      = $_GET['download'];


    print( getCustomPage( $providerName,
                          $startDay, $endDay, $startMonth, $startYear, $endMonth, $endYear,
                          $selectHost, $textHost,
                          $selectIP, $textIP,
                          $selectRecs, $textRecs,
                          $selectQuery, $textQuery,
                          $selectSchema, $textSchema,
                          $resources,
                          $textBox,
                          $astab
                        )
         );
 
    if( !$astab )
    {
        print getjavaScript();
    }
}
else
{
    global $g_current_month, $g_current_year;

    $availableLogFileNames = array();
    $cachedMonthsFiles = array();
    getLogArray( $availableLogFileNames, $cachedMonthsFiles );
    $startMonth= ( isset( $_GET['startmonth'] ) ) ? $_GET['startmonth'] : $g_current_month;
    $endMonth= ( isset( $_GET['endmonth'] ) ) ? $_GET['endmonth'] : $g_current_month;
    $startYear= ( isset( $_GET['startyear'] ) ) ? $_GET['startyear'] : $g_current_year;
    $endYear= ( isset( $_GET['endyear'] ) ) ? $_GET['endyear'] : $g_current_year;
    print( getStatisticsPage( $providerName, $availableLogFileNames, $cachedMonthsFiles, $startMonth, $startYear, $endMonth, $endYear ) );
}
  
?>