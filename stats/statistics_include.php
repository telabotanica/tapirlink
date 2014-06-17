<?php


function removeDebug( $name, $value, $vardump = 0 )
{
    if( !$vardump )
    {
        print "$name = :$value:";
    }
    else
    {
        print "$name = ";
        var_dump( $value );
    }
}

function getAllMonthsAsText()
{
    return array( '01' => "Jan", '02' => "Feb", '03' => "Mar", '04' => "Apr", '05' => "May", '06' => "Jun", '07' => "Jul", '08' => "Aug", '09' => "Sep", '10' => "Oct", '11' => "Nov", '12' => "Dec" );
}

function getAllMonths()
{
    return array( '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12' );
}


function generateMonthsHeader( )
{
    return " <TD colspan=\"12\" align=\"center\" >Months</TD>";
}

function generateAvailableDataTableHeader( )
{

    return "
                            <TABLE border>
                                <TR bgcolor=DFE5FA> <TD>Year</TD><TD></TD> " . generateMonthsHeader() . "</TR>\n";
}

function generateAvailableDataTableCloser( )
{
    return "
                            </TABLE>
";
}

function monthNumberToText( $monthAsNumber )
{
    $monthAsText = "";
    switch( $monthAsNumber )
    {
        case "01": $monthAsText = "Jan";
                    break;
        case "02": $monthAsText = "Feb";
                    break;
        case "03": $monthAsText = "Mar";
                    break;
        case "04": $monthAsText = "Apr";
                    break;
        case "05": $monthAsText = "May";
                    break;
        case "06": $monthAsText = "Jun";
                    break;
        case "07": $monthAsText = "Jul";
                    break;
        case "08": $monthAsText = "Aug";
                    break;
        case "09": $monthAsText = "Sep";
                    break;
        case "10": $monthAsText = "Oct";
                    break;
        case "11": $monthAsText = "Nov";
                    break;
        case "12": $monthAsText = "Dec";
                    break;
    
    }
    return $monthAsText;
}

//BUGBUG could be made more effecient with filtering
function generateMonthLink( $targetMonth, $targetYear, $availableLogFileNames )
{
    global $mainPage;
    $monthAsText = monthNumberToText( $targetMonth );
    $returnValue = "
                                    <TD>$monthAsText</TD>
";

    $targetFileName = $targetYear . "_" . $targetMonth .  ".tbl";
    foreach( $availableLogFileNames as $availableDate )
    {
        if( strcmp( $availableDate , $targetFileName ) == 0 )
        {
            $returnValue = "                                  <TD><A HREF=\"$mainPage?startmonth=$targetMonth&startyear=$targetYear&endmonth=$targetMonth&endyear=$targetYear\">$monthAsText</A></TD>\n";
            break;
        }
    }
    
    return $returnValue;
}


function getCustomPageCenter( $providerName,
                              $startDay, $endDay, $startMonth, $startYear, $endMonth, $endYear,
                              $selectHost, $textHost,
                              $selectIP, $textIP,
                              $selectRecs, $textRecs,
                              $selectQuery, $textQuery,
                              $selectSchema, $textSchema,
                              $resources,
                              $textbox,
                              $astab
                            )
{

    require_once('flatfile/flatfile.php');
    $db_connection = new flatfile();
    $db_connection->datadir = TP_STATISTICS_DIR;

    $fieldClauses = new AndWhereClause();
    $strClause    = "";
    $strClauseAnd = ""; 
    $newClause    = NULL;
    
    global $mainPage;

    if( get_magic_quotes_runtime() || get_magic_quotes_gpc() )
    {
        $textHost=stripslashes( $textHost );
        $textIP=stripslashes( $textIP );
        $textQuery=stripslashes( $textQuery );
    }
    
    switch( $selectHost )
    {
        case "starts with"  : $newClause = new LikeWhereClause( TBL_MONTH_SOURCE_HOST, "source_host=$textHost%" ); $strClause .= "Host name starts with $textHost"; break;
        case "ends with"    : $newClause = new LikeWhereClause( TBL_MONTH_SOURCE_HOST, "source_host=%$textHost" ); $strClause .= "Host name ends with $textHost"; break;
        case "com"          : $newClause = new LikeWhereClause( TBL_MONTH_SOURCE_HOST, "source_host=%.com" ); $strClause .= "Host name is from .com"; break;
        case "edu"          : $newClause = new LikeWhereClause( TBL_MONTH_SOURCE_HOST, "source_host=%.edu" ); $strClause .= "Host name is from .edu"; break;
        case "gov"          : $newClause = new LikeWhereClause( TBL_MONTH_SOURCE_HOST, "source_host=%.gov" ); $strClause .= "Host name is from .gov"; break;
        case "regex"        : $newClause = new RegexWhereClause( TBL_MONTH_SOURCE_HOST, $textHost ); $strClause .= "Host name matches regular expression $textHost"; break;
        case "contains"     : $newClause = new LikeWhereClause( TBL_MONTH_SOURCE_HOST ,"source_host=%$textHost%" ); $strClause .= "Host name contains \"$textHost\""; break;
        case "equals"       : $newClause = new SimpleWhereClause( TBL_MONTH_SOURCE_HOST, '=', "source_host=$textHost", STRING_COMPARISON );$strClause .= "Host name is $textHost"; break;
        case "localhost"    : $newClause = new SimpleWhereClause( TBL_MONTH_SOURCE_HOST, '=', "source_host=localhost", STRING_COMPARISON );$strClause .= "Host name is localhost"; break;
        case "is an ip"     : $ipv4Regex='(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])'; $newClause = new RegexWhereClause( TBL_MONTH_SOURCE_HOST, "/^source_host.*$ipv4Regex\\.$ipv4Regex\\.$ipv4Regex\\.$ipv4Regex$/" ); $strClause .= "Host name did not resolve"; break;
    }
    
    if( $newClause )
    {
        $fieldClauses->add( $newClause );
        $strClauseAnd = " and "; 
    }

    $newClause = NULL;

    switch( $selectIP )
    {
        case "starts with"  : $newClause = new LikeWhereClause( TBL_MONTH_SOURCE_IP, "source_ip=$textIP%" ); $strClause .= $strClauseAnd . "IP Address starts with $textIP"; break;
        case "ends with"    : $newClause = new LikeWhereClause( TBL_MONTH_SOURCE_IP, "source_ip=%$textIP" );  $strClause .= $strClauseAnd . "IP Address ends with $textIP"; break;
        case "regex"        : $newClause = new RegexWhereClause( TBL_MONTH_SOURCE_IP, $textIP ); $strClause .= $strClauseAnd . "IP Address matches regular expression $textIP"; break;
        case "contains"     : $newClause = new LikeWhereClause( TBL_MONTH_SOURCE_IP ,"source_ip=%$textIP%" ); $strClause .= $strClauseAnd . "IP Address contains \"$textIP\""; break;
        case "equals"       : $newClause = new SimpleWhereClause( TBL_MONTH_SOURCE_IP, '=', "source_ip=$textIP", STRING_COMPARISON ); $strClause .= $strClauseAnd . "IP Address is $textIP"; break;
        case "localhost"    : $newClause = new SimpleWhereClause( TBL_MONTH_SOURCE_IP, '=', "source_ip=127.0.0.1", STRING_COMPARISON );$strClause .= $strClauseAnd . "IP Address is localhost"; break;
    }
    if( $newClause )
    {
        $fieldClauses->add( $newClause );
        $strClauseAnd = " and "; 
    }

    $newClause = NULL;

    switch( $selectRecs )
    {
        case "less than"      : $newClause = new SimpleWhereClause( TBL_MONTH_RETURNEDRECS, '<', "returnedrecs=$textRecs", STRING_COMPARISON ); $strClause .= $strClauseAnd . "Records returned is less than $textRecs"; break;
        case "greater than"   : $newClause = new SimpleWhereClause( TBL_MONTH_RETURNEDRECS, '>', "returnedrecs=$textRecs", STRING_COMPARISON ); $strClause .= $strClauseAnd . "Records returned is greater than $textRecs"; break;
        case "equals"         : $newClause = new SimpleWhereClause( TBL_MONTH_RETURNEDRECS, '=', "returnedrecs=$textRecs", STRING_COMPARISON ); $strClause .= $strClauseAnd . "Records returned is $textRecs"; break;
    }
    
    if( $newClause )
    {
        $fieldClauses->add( $newClause );
        $strClauseAnd = " and "; 
    }

    $newClause = NULL;

    switch( $selectQuery )
    {
        //BUGBUG the leading white space for "whereclause=<space>"could cause problems
        case "regex"          : $newClause = new RegexWhereClause( TBL_MONTH_SOURCE_HOST, stripslashes($textQuery) ); $strClause .= $strClauseAnd . "search clause matches regular expression ".stripslashes($textQuery) ; break;
        case "contains"       : $newClause = new LikeWhereClause( TBL_MONTH_WHERE, "whereclause= %$textQuery%" ); $strClause .= $strClauseAnd . "search clause contains \"$textQuery\""; break;
        case "equals"         : $newClause = new SimpleWhereClause( TBL_MONTH_WHERE, '=', "whereclause= $textQuery", STRING_COMPARISON ); $strClause .= $strClauseAnd . "search clause equals $textQuery"; break;
    }

    if( $newClause )
    {
        $fieldClauses->add( $newClause );
        $strClauseAnd = " and "; 
    }

    $newClause = NULL;
        
    switch( $selectSchema )
    {        
        case "any"       : break;
        case "inventory"      : $newClause = new SimpleWhereClause( TBL_MONTH_TYPE, '=', "type=inventory", STRING_COMPARISON ); $strClause .= $strClauseAnd . "Schema is inventory"; break;
        case "contains"       : $newClause = new LikeWhereClause( TBL_MONTH_SCHEMA, "recstr=%$textSchema%" ); $strClause .= $strClauseAnd . "Schema contains $textSchema"; break;
        case "equals"         : $newClause = new SimpleWhereClause( TBL_MONTH_SCHEMA, '=', "recstr=$textSchema", STRING_COMPARISON ); $strClause .= $strClauseAnd . "Schema is $textSchema"; break;
        default               : $newClause = new SimpleWhereClause( TBL_MONTH_SCHEMA, '=', "recstr=$selectSchema", STRING_COMPARISON ); $strClause .= $strClauseAnd . "Schema is $selectSchema"; break;
    }
    
    if( $newClause )
    {
        $fieldClauses->add( $newClause );
        $strClauseAnd = " and "; 
    }

    $newClause = NULL;
    $resourceArray = array();
    
    $dlResourceArgs = "";
    $dlResourceCount = 0;
    
    $resourceClause = "";
    
    foreach( $resources as $resource )
    {
        if( $resource == "all" )
        {
            $resourceArray = array();
            $dlResourceArgs = "&resource[0]=all";
            $resourceClause = "For all resources ";
            break;
        }
        else
        {
            array_push( $resourceArray,"resource=$resource" );
            $dlResourceArgs .="&resource[$dlResourceCount]=$resource";
            $dlResourceCount++;
        }
    }

    $resCount = count( $resourceArray );
    if( $resCount > 0 )
    {
        $newClause = new ListWhereClause( TBL_MONTH_RESOURCE, $resourceArray ); 
        $fieldClauses->add( $newClause );
        
        if( $resCount > 1 )
        {
            $resourceClause = "For Resources ";
        }
        else
        {
            $resourceClause .= "For Resource ";
        }
        
        $currentRes = 0;
        
        foreach( $resources as $resource )
        {
            $currentRes++;
            $resourceClause .= " $resource";
            if( $currentRes != $resCount )
            {
                $resourceClause .= ", ";
            }
        }
    }

    $strClause = "Report equals: $resourceClause where " . $strClause;
    //BUGBUG security flaw, a person could hand craft get params to cause near inifinite query
    //try to reduce it here
    //end security flaw

    //normalize years in case person didn't set them properly
    if( $endYear < $startYear )
    {
        $endYear = $startYear;
        $endMonth = $startMonth;
    }
    
    if(  $startYear == $endYear && $endMonth < $startMonth )
    {
        $endMonth = $startMonth;
    }
    
    $currentMonth = $startMonth;
    $currentYear = $startYear;

    $numMatchesFound = array();
    $numRecsReturned = array();
    
    $rows = array();

    
    while( $currentYear <= $endYear )
    {
        if( $currentYear != $endYear )
        {
            $end = 12;
        }
        else
        {
            $end = $endMonth;
        }
        
        while( $currentMonth <= $end )
        {

            $filename = getLogFileName( $currentMonth, $currentYear );
            $newRows = $db_connection->selectWhere( $filename, $fieldClauses );
            
            $doStartDayCheck = 0;
            $doEndDayCheck   = 0;
            
            if( $startMonth == $currentMonth && $currentYear == $startYear)
            {
                $doStartDayCheck = 1;
            }

            if( $endMonth == $currentMonth && $currentYear == $endYear )
            {
                $doEndDayCheck = 1;
            }

            $index = 0;
            foreach( $newRows as $row )
            {
                $removed = 0;
                if( $doStartDayCheck || $doEndDayCheck )
                {
                    $day = substr( $row[ TBL_MONTH_DATE ], 4, 2 );
                    
                    if( $doStartDayCheck  && !$doEndDayCheck )
                    {
                        if( $day < $startDay )
                        {
                          array_splice( $newRows, $index, 1 );
                          $removed = 1;
                          $index--;
                        }
                    }
                    else if( !$doStartDayCheck  && $doEndDayCheck )
                    {
                        if( $day > $endDay ) 
                        {
                          array_splice( $newRows, $index, 1 );
                          $removed = 1;
                          $index--;
                        }                    
                    }
                    else
                    {
                        if( !($day <= $endDay  && $day >= $startDay) )
                        {
                          array_splice( $newRows, $index, 1 );
                          $removed = 1;
                          $index--;
                        }
                    }
                }
                if( !$removed )
                {
                    $numRecsReturned[ $row[ TBL_MONTH_METHOD ] ] = $numRecsReturned[ $row[ TBL_MONTH_METHOD ] ] + substr( strrchr( $row[ TBL_MONTH_RETURNEDRECS ], "=" ), 1 );
                    $numMatchesFound[ $row[ TBL_MONTH_METHOD ] ] = $numMatchesFound[ $row[ TBL_MONTH_METHOD ] ] + 1;
                }
                $index++;
            }
            $rows = array_merge( $rows, $newRows );
            $currentMonth = $currentMonth + 1;
        }
        
        $currentYear = $currentYear + 1;        
        $currentMonth = 1;
    }

    $numSearchMatchesFound = $numMatchesFound[ "op=search" ];
    $numInventoryMatchesFound = $numMatchesFound[ "op=inventory" ];
    $numSearchRecordsFound = $numRecsReturned[ "op=search" ];
    $numInventoryRecordsFound = $numRecsReturned[ "op=inventory" ];

$dlLink = "<A HREF=\"$mainPage?action=custom&selectHost=$selectHost&textHost=$textHost&selectIP=$selectIP&textIP=$textIP&selectRecs=$selectRecs&textRecs=$textRecs&selectQuery=$selectQuery&textQuery=$textQuery&selectSchema=$selectSchema&textSchema=$textSchema&resource%5B3%5D=MVZMaNISDwC2&startmonth=$startMonth&startyear=$startYear&startday=$startDay&endmonth=$endMonth&endyear=$endYear&endday=$endDay$dlResourceArgs&download=1\">Download</A>";

//&generate=    &textbox%5Btextbox%5D=Display+in+a+text+box";



    if( $astab )
    {
        header( "Content-type: application/vnd.ms-excel" );
        header( "Content-disposition: attachment; filename=" . $startMonth . $startYear  ."-" . $endMonth . $endYear . ".xls" );
        $returnValue .="Date\tTime\tResource\tSource\tRecords Returned\tType\tSearch Clause\tResults Schema\r\n";
    }
    else
    {
        $totalRecords=0;
        foreach( $numRecsReturned as $type )
        {
            $totalRecords=$totalRecords + $type;            
        }
        $returnValue = "<TABLE border=1>\r\n";
        $returnValue .= "<TR><TD colspan=8>Queries: $numSearchMatchesFound<BR>Records returned: $numSearchRecordsFound<BR>Inventories: $numInventoryMatchesFound<BR>Records returned: $numInventoryRecordsFound<BR>Total records: $totalRecords<br>$strClause from $startMonth $startDay $startYear to $endMonth $endDay $endYear<BR>$dlLink</TD></TR>\r\n";
        if( !$textbox )
        {
            $returnValue .= "<TR><TD>Date</TD><TD>Time</TD><TD>Resource</TD><TD>Source</TD><TD>Records Returned</TD><TD>Type</TD><TD>Search Clause</TD><TD>Results Schema</TD></TR>\r\n";
        }
    }
    
    if( $textbox && !$astab )
    {
        $textrows=count( $rows ) + 1;
        if( $textrows < 4)
        {
            $textrows=4;
        }
        $returnValue .= "<TR>
                            <TD COLSPAN=\"8\">
                                <FORM name=\"textarea\">
                                <TEXTAREA COLS=60 ROWS=$textrows WRAP=OFF name=\"textresults\">
Date\tTime\tResource\tSource\tRecords Returned\tType\tSearch Clause\tResults Schema\r\n";
    }
    

    $TBL_WHERE_OFFSET = TBL_MONTH_WHERE;
    $TBL_SCHEMA_OFFSET = TBL_MONTH_SCHEMA;
    
    //Inventory records are different than search records
    
    foreach ( $rows as $row )
    {
        if( !$textbox && !$astab )
        {
            $returnValue .= "<TR>";
        }
        
        $date = $row[ TBL_MONTH_DATE ];
        $time = $row[ TBL_MONTH_TIME ];
    
        $resource = substr( strrchr( $row[ TBL_MONTH_RESOURCE ], "=" ),1);
        $host     = substr( strrchr( $row[ TBL_MONTH_SOURCE_HOST ], "=" ),1);
        $recs     = substr( strrchr( $row[ TBL_MONTH_RETURNEDRECS ], "=" ),1);
        $type     = substr( strrchr( $row[ TBL_MONTH_METHOD ], "=" ),1);

        if( $type == "inventory" )
        {
            $TBL_WHERE_OFFSET = TBL_MONTH_WHERE_INV;
            $TBL_SCHEMA_OFFSET = TBL_MONTH_COLUMN_INV;
        }

        $query    = substr( $row[ $TBL_WHERE_OFFSET ], strpos( $row[ $TBL_WHERE_OFFSET ], "=" )+1);
        $schema   = substr( strrchr( $row[ $TBL_SCHEMA_OFFSET ], "=" ),1);



//          $query    = substr( $row[ TBL_MONTH_WHERE ], strpos( $row[ TBL_MONTH_WHERE ], "=" )+1);
//          $schema   = substr( strrchr( $row[ TBL_MONTH_SCHEMA ], "=" ),1);
        
        if( $type == "inventory" )
        {
            $query = "DISTINCT $schema";
        }
        
        if( $textbox || $astab )
        {
            $returnValue .= "$date\t$time\t$resource\t$host\t$recs\t$type\t$query\t$schema\r\n";
        }
        else
        {
            $returnValue .= "<TD>$date</TD>";
            $returnValue .= "<TD>$time</TD>";
            $returnValue .= "<TD>$resource</TD>";
            $returnValue .= "<TD>$host</TD>";
            $returnValue .= "<TD>$recs</TD>";
            $returnValue .= "<TD>$type</TD>";            
            $returnValue .= "<TD>$query</TD>";
            $returnValue .= "<TD>$schema</TD>";
            $returnValue .= "</TR>\r\n";
        }
    }
    
    if( $astab )
    {
        ;
    }
    else if( $textbox )
    {
        $returnValue .= "</TEXTAREA></FORM></TD></TR>";    
    }
    
    if( !$astab ) 
    {
        $returnValue .= "</TABLE>";
    }

    return $returnValue;
}

//BUGBUG NEEDS to do it's own thing
//function getCustomPageHeader( $title, $title, $month, $day, $year, $resource )
//{
//    return getResourcePageHeader( $title, $title, $month, $day, $year, $resource );
//}

function getCustomPageFooter( $month, $year, $resource, $columncount )
{
    return getResourcePageFooter( $month, $year, $resource, $columncount );
}

function getCustomPage( $providerName,  $startDay, $endDay, $startMonth, $startYear, $endMonth, $endYear,
                                        $selectHost, $textHost,
                                        $selectIP, $textIP,                                        
                                        $selectRecs, $textRecs,
                                        $selectQuery, $textQuery,
                                        $selectSchema, $textSchema,
                                        $resources,
                                        $textbox,
                                        $astab
                                        )
{
    if( $astab )
    {
        ;
    }
    else
    {
        $returnValue  = getResourcePageHeader( "Custom Report", "Custom Report", "", "", "", "" );
    }
    
    $returnValue .= getCustomPageCenter( $providerName,
                                      $startDay, $endDay, $startMonth, $startYear, $endMonth, $endYear,
                                      $selectHost, $textHost,
                                      $selectIP, $textIP,                                        
                                      $selectRecs, $textRecs,
                                      $selectQuery, $textQuery,
                                      $selectSchema, $textSchema,
                                      $resources,
                                      $textbox,
                                      $astab
                                    );
    if( $astab )
    {
        ;
    }
    else
    {
        $returnValue .= getCustomPageFooter( $month, $year, $resource, 5 );
    }
    
    return $returnValue;
}

function generateAvailableMonthsForYear( $availableLogFileNames, $targetYear )
{
    $availableMonths =  getAllMonths();
    $returnValue ="";
        
    foreach( $availableMonths as $month )
    {
        //$year = substr( $year, 0, 4 );
        //if( $year == $targetYear )
        //{
            $returnValue .= generateMonthLink( $month, $targetYear, $availableLogFileNames );
        //}
    }
    return $returnValue;
}
 
function generateAvailableDataTableRows( $availableLogFileNames )
{
    global $mainPage;
    $returnValue='';
    $availableYears = array( date('Y') );
    {
        $availableYears = getAvailableYears( $availableLogFileNames );
    }
    
    foreach( $availableYears as $year )
    {
        $returnValue .= "
                                <TR>
                                    <TD>
                                        <A HREF=\"$mainPage?startmonth=01&startyear=$year&endmonth=12&endyear=$year\">
                                            $year
                                        </A>
                                    <TD>
                                    </TD>
                                    </TD>" .
                                        generateAvailableMonthsForYear( $availableLogFileNames, $year ) .   
"                               </TR>";
    }
    return $returnValue;
}


function getStatisticsMenu( )
{
//                <TD class=\"border\" width=\"15%\" valign=\"top\">.

    global $mainPage;

    return "
                    <!------------------ begin SIDE MENU ------------------>
                    
                    <A href=\"logfile_admin.php\" class=\"side_bar\">
                        Log File Administration
                    </A>
                    <BR /><BR />
                    <A href=\"$mainPage\" class=\"side_bar\">
                        Log File Statistics
                    </A>
                    <BR />
                    <!------------------ end SIDE MENU ------------------>
    ";
//                </TD>

}

function getStatisticsCloser( )
{
//                    </DIV>
//                </TD>
return "
            </TR>
        </TABLE>
    </BODY>
</HTML>\n";
}

function generateAvailableDataTable( $availableLogFileNames )
{
    
    return '' .
    generateAvailableDataTableHeader() .
    //generateAvailableDataTable() .
    generateAvailableDataTableRows( $availableLogFileNames ) .
    generateAvailableDataTableCloser() ;
    //. 
    //"                 </TD>\n";
}

function generateSelectTextPair( $selectName, $textName, $title, $selectData, $and )
{
$select = generateOpenSelect( $selectName, '' ) .
          generateDataForSelectBox( $selectData, 'any' ) .
          generateCloseSelect();
			//<TD>
              //                      $and
                //                </TD>
			
  $return_val = "
                            <TR>
                                <TD>
                                    $title
                                </TD>
                                <TD>
                                    $select
                                </TD>
                                <TD>
                                    <INPUT TYPE=TEXT NAME=\"$textName\">
                                </TD>                                                                
                            </TR>
";
return $return_val;
}


function generateCheckbox( $name, $index, $text,  $javascript = "", $checked = NULL )
{
    if( $checked )
    {
        $checked="CHECKED";
    }

    $name = "$name" . "[$index]"; 
    $returnValue = "<INPUT TYPE=CHECKBOX NAME=\"$name\" VALUE=\"$text\" $checked onClick=\"$javascript\">$text<br>";

    return $returnValue;
}

//BUGBUG might be better to pull from the xml file, or combine with results from XML file
//resources with no hits won't show
function getResources()
{
    require_once( 'flatfile/flatfile.php' );
    require_once( 'TpStatistics.php' );

    $returnValue="";
    $db_connection = new flatfile();
    $db_connection->datadir = TP_STATISTICS_DIR;
    $rows = $db_connection->selectAll( TP_STATISTICS_RESOURCE_TABLE );

    $resources = array();
    
    foreach( $rows as $row )
    {
        array_push($resources, $row[ TBL_RESOURCE ] );
    }
    
    $resources = array_unique( $resources );

    sort( $resources );
    return $resources;
}


//BUGBUG might be better to pull from the xml file, or combine with results from XML file
//resources with no hits won't show
function getSchemas()
{
    require_once( 'flatfile/flatfile.php' );
    require_once( 'TpStatistics.php' );

    $returnValue = array();
    $db_connection = new flatfile();
    $db_connection->datadir = TP_STATISTICS_DIR;
    $rows = $db_connection->selectAll( TP_STATISTICS_SCHEMA_TABLE );

    $schemas = array();
    
    foreach( $rows as $row )
    {
        array_push($schemas, $row[ TBL_SCHEMA_SCHEMA ] );
    }
    
    $schemas = array_unique( $schemas );

    sort( $schemas );
    return $schemas;
}


function generateResources()
{
    $returnValue = generateCheckbox("resource", 0, "all", "",1 );
    $resources = getResources(); 
    $count = 0;
    foreach( $resources as $resource )
    {
        $count = $count + 1;
        $returnValue .= generateCheckbox( "resource", $count, $resource, "" );
        
    }
    return $returnValue;
}

function getDaysArray( )
{
    return array(             "01" =>  1, "02" =>  2, "03" =>  3, "04" =>  4, "05" =>  5, "06" =>  6, "07" =>  7, "08" =>  8, "09" =>  9,
                         "10" => 10, "11" => 11, "12" => 12, "13" => 13, "14" => 14, "15" => 15, "16" => 16, "17" => 17, "18" => 18, "19" => 19, 
                         "20" => 20, "21" => 21, "22" => 22, "23" => 23, "24" => 24, "25" => 25, "26" => 26, "27" => 27, "28" => 28, "29" => 29,
                         "30" => 30, "31" => 31 ); 
    return $returnValue;
}

function generateDaysSelectBox( $name, $day )
{
    
    $returnValue =
        generateOpenSelect( $name, '' ) .
        generateDataForSelectBox( getDaysArray(  ), $day ) .
        generateCloseSelect( $name );
    
    return $returnValue;
}

function compareURLs( $urlA, $urlB )
{
    if( $urlA == "custom" )
    {
        return 0;
    }
    else if( $urlA == "inventory" )
    {
        if( $urlB != "custom" )
        {
            return 0;
        }
        return 1;
    }
    else
    {
        $A = substr( strrchr( $urlA, "/" ), 1 );
        $B = substr( strrchr( $urlB, "/" ), 1 );
        return strcmp( $A, $B );
    }
}

function generateCustomQueryForm( $availableLogFileNames )
{

    global $mainPage;
    $hostSelect     = array( "any" => "any", "starts with" => "starts with",    "ends with"    => "ends with",    "contains" =>"contains", "equals" =>"equals", "regex" =>"regex (start with /^source_host=)", "is an ip" =>"is an ip", "localhost" =>"is localhost", "edu" =>"ends with .edu", "gov" =>"ends with .gov", "com" =>"ends with .com" );
//    $hostSelect     = array( "any" => "any", "starts with" => "starts with",    "ends with"    => "ends with",    "contains" =>"contains", "equals" =>"equals", "is an ip" =>"is an ip", "localhost" =>"localhost", "edu" =>".edu", "gov" =>".gov", "com" =>".com" );

    $ipSelect       = array( "any" => "any", "starts with" => "starts with",    "ends with"    => "ends with",    "contains" =>"contains", "equals" =>"equals", "regex" =>"regex (start with /^source_ip=)", "localhost" =>"is localhost" );
//    $ipSelect       = array( "any" => "any", "starts with" => "starts with",    "ends with"    => "ends with",    "contains" =>"contains", "equals" =>"equals", "localhost" =>"localhost" );

    $recsSelect     = array( "any" => "any", "less than"   => "less than",      "greater than" => "greater than", "equals"   => "equals" );

    $querySelect    = array( "any" => "any", "contains"    => "contains",       "regex"        => "regex (start with /^whereclause=\s+)", "equals" =>"equals" );
//    $querySelect    = array( "any" => "any", "contains"    => "contains",       "equals" =>"equals" );
    
    $basicSelect = array( "any" => "any", "contains"    => "contains",       "equals"       => "equals" );

    //$schemaSelect   = array( "any" => "any", "contains"    => "TEXT: contains", "equals"       => "TEXT: equals", "custom" => "custom" , "inventory" => "inventory" );
    
    //BUGBUG ineffecient temp variable usage
    $tempArray = getSchemas();
    
    sort( $tempArray );
    
    $schemaSelect = array();

    foreach( $tempArray as $row )
    {
        $schemaSelect[$row] = $row;
    }
    $schemaSelect = array_unique( $schemaSelect );
    
    usort( $schemaSelect, "compareURLs" );
    
    $tempArray = array();
    foreach( $schemaSelect as $ss )
    {
        $tempArray[ $ss ] = "is $ss";
    }
    
    
    $schemaSelect = array_merge( $basicSelect, $tempArray );
    
    
    $day = date("d");
    $month = date("m");
    $year = date("Y");
    
    $return_val	 = "
                            <TABLE>
                                <TR>
                                    <TD>
".
                                    	generateFormOpen( "$mainPage", "custom", "custom", "GET" ) .

"                                       <TABLE>
                                            <TR>
                                                <TD>
                                                <TABLE>
".
    generateSelectTextPair( "selectHost", "textHost", "Host Name", $hostSelect, ""  ) .
    generateSelectTextPair( "selectIP", "textIP", "IP Address", $ipSelect, ""  ) .
    generateSelectTextPair( "selectRecs", "textRecs", "Num Records", $recsSelect, ""  ) .
    generateSelectTextPair( "selectQuery", "textQuery", "Query String", $querySelect, ""  ) .
    "                  	                        </TABLE> " .
"                                               <TABLE>
".
    generateSelectTextPair( "selectSchema", "textSchema", "Results Schema", $schemaSelect, ""  ) .
"                   	                        </TABLE>" .
	generateResources() .
	"<BR>In Date Range<BR>" . 
    generateMonthsSelectBox( "startmonth", $month ) .
    generateYearsSelectBox( "startyear", $availableLogFileNames, $year ) .
    generateDaysSelectBox( "startday", "01" ) .
    generateMonthsSelectBox( "endmonth", $month ) .
    generateYearsSelectBox( "endyear", $availableLogFileNames, $year ) .
    generateDaysSelectBox( "endday", $day ) .
	generateSubmitButton( "generate", "View Report" ) .
	generateSubmitButton( "download", "Download as Excel (Tab)" ) .
	generateRestButton( "", "Clear Form" ) .
"                                               </TD>
                                            </TR>
                                        </TABLE>
" . generateAsTextBox() .	generateFormClose() .
 "                                  </TD>
                                </TR>
                            </TABLE>
";


    return $return_val;
}

function generateOrderBy()
{
//	"<BR>Order By<BR>" . 
//	generateOrderBy() .

// return "NOT IMPLEMENTED";
}

function generateShowFields()
{
//	"<BR>Returning Fields<BR>" . 
//	generateShowFields() .

// return "NOT IMPLEMENTED";
}


function generatePeriodForm( $availableLogFileNames )
{
    global $mainPage;
    $month = date( "m" );
    $year = date( "Y" );
    $return	 = "
" .
"                           <TABLE>
                                <TR>
                                    <TD>
" .
	generateFormOpen( "$mainPage", "period", "period", "GET" ) .
    generateMonthsSelectBox( "startmonth", $month ) .
    generateYearsSelectBox( "startyear", $availableLogFileNames, $year ) .
    generateMonthsSelectBox( "endmonth", $month ) .
    generateYearsSelectBox( "endyear", $availableLogFileNames, $year ) .
	generateSubmitButton( "view", "View Statistics" ) .
	generateFormClose() .
"                                   </TD>
                               </TR>
                            </TABLE>
";
	
return $return;
}

function generateDataForSelectBox( $availableData, $selectedKey )
{
    $returnValue="";
    foreach ( array_keys( $availableData ) as $key )
    {
            $selected="";
            if( $key == $selectedKey )
            {
                $selected="SELECTED";
            }
            $returnValue .= "                               <OPTION value=\"$key\" $selected>" . $availableData[ $key ] . "\n";
    }

    return $returnValue;
}


function generateAvailableMonthsForYearForSelectBox( $availableLogFileNames, $targetYear )
{
    $availableMonths =  getAllMonths();
	foreach( $availableMonths as $month )
    {
      $returnValue .= generateMonthExists( $month, $targetYear, $availableLogFileNames );
	}
    return $returnValue;
}
	

function generateMonthExists( $targetMonth, $targetYear, $availableLogFileNames )
{
    $monthAsText = monthNumberToText( $targetMonth );
    $returnValue = "";
    $targetFileName = $targetYear . "_" . $targetMonth .  ".tbl";
    foreach( $availableLogFileNames as $availableDate )
    {
        if( strcmp( $availableDate , $targetFileName ) == 0 )
        {
            $returnValue = "                                <OPTION value=\"$monthAsText$targetYear\">" . $monthAsText . " " . $targetYear . "\n";
			break;
        }
    }
    
    return $returnValue;
}		

function generateOpenSelect( $name, $optionNull )
{
	$returnValue = "                            <SELECT name=\"$name\">\n";
//						<option value=\"0\">$optionNull\n";
	return $returnValue;
}
function generateCloseSelect()
{
	$returnValue = "                            </SELECT>\n";
	return $returnValue;
}

function generateFormOpen( $file, $action, $name, $method = "GET" )
{
	$returnValue = "                            <FORM action=\"$file\" method=\"$method\" name=\"$name\">
                                                    <INPUT TYPE=hidden name=action VALUE=\"$action\">\n";
	return $returnValue;
}

function generateAsTextBox()
{
    return generateCheckbox( "textbox", "textbox", "Display in a text box" );
}

/*
//BUGBUG depreacated
function generateAsTab()
{
	$returnValue = "                            <INPUT TYPE=submit name=\"a stab\" value=\"astab\" >\n";
	return $returnValue;
}
*/

function generateFormClose()
{
	$returnValue = "
                                </FORM>";
	return $returnValue;
}

//BUGBUG could be combined into a genric BUTTON maker
function generateSubmitButton( $name, $value )
{
	$returnValue = "                            <INPUT type=\"submit\" name=\"$name\" value=\"$value\">";
	return $returnValue;
}

function generateRestButton( $name, $value )
{
	$returnValue = "                            <INPUT type=\"reset\" name=\"$name\" value=\"$value\">";
	return $returnValue;
}


function generatePeriodSelectBoxes( $startingPeriod, $Start_Month, $availableLogFileNames ) {
	return 	generateOpenSelect( $startingPeriod, $Start_Month ) .
			generateAvailableDataSelectBox( $availableLogFileNames ) . 
			generateCloseSelect();
}


function generateYearsSelectBox( $name, $availableLogFileNames, $year )
{
	return 	generateOpenSelect( $name, "" ) .
			generateDataForSelectBox( getAvailableYears( $availableLogFileNames ), $year ) .
			generateCloseSelect();
} 

function generateMonthsSelectBox( $name, $month )
{
    
	return 	generateOpenSelect( $name, "" ) .
			generateDataForSelectBox( getAllMonthsAsText(), $month ) .
			generateCloseSelect();
}



function getResourcePageHeader( $title, $header, $month, $day, $year, $resource )
{
    global $mainPage;
    $action=( isset( $_GET['action'] ) ) ? $_GET['action'] : '';

    $link="<A HREF=\"$mainPage\">Database Query Statistics</A>";

    $monthStr=monthNumberToText( $month );
    if( $action == 'summary' )
    {
        $link .= " > $resource Queries";
    }
    elseif( $action=='monthdetail' )
    {
    
        //$link .= " > <A HREF=\"$mainPage?action=summary&month=$month&year=$year&resource=$resource\">$resource Queries</a>";
        $link .= " > $monthStr $year";
    }
    elseif( $action=='daydetail' )
    {
        ///$link .= " > <A HREF=\"$mainPage?action=summary&month=$month&year=$year&resource=$resource\">$resource Queries</a>";
        $link .= " > <A HREF=\"$mainPage?action=monthdetail&month=$month&year=$year&day=$day&resource=$resource\">$monthStr $year</a>";
        $link .= " > $monthStr $day, $year";
    }
    elseif( $action=='custom' )
    {
        $link .= " > Custom Report";    
    }

    return "
<HTML><HEAD>
<TITLE>$title</TITLE>
</HEAD>
<BODY BGCOLOR=#FFFFFF>
<table align=center width=90% border=0 cellspacing=0 cellpadding=0>
    <tr>
        <td width=5% bgcolor=DFE5FA>
            <br>
        </td>
        <td align=left valign=bottom>
            <table border=0 cellpadding=5>
                <tr>
                    <td align=left valign=bottom>
                        <font face=\"Helvetica,Arial,Verdana\" color=23238E>
                            <big><big>$header</big></big>
                        </font>&nbsp;&nbsp;
                    </td>
            </table>
        </td>
    </tr>
    <tr>
        <td width=100% colspan=2 bgcolor=23238E>
            <br>
        </td>
    </tr>
    <tr>
        <td width=5% bgcolor=DFE5FA>
        </td>
    </tr>
    <tr>
        <td width=5% bgcolor=DFE5FA>
        </td>
        <td>
            <center><i><small>$link</small></i></center><p>
        </td>
    </tr>
    <tr>
        <td width=5% bgcolor=DFE5FA>
        </td>
    </tr>
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
        <td>    
";
}

function getSchemaTDs( $schemaCount, &$schemas, &$db_connection, $month, $year, $resource, $totalMatches, $totalHits )
{

    for( $i = 0; $i < $schemaCount; $i++ )
    {
        $whereClause = new AndWhereClause( );
        $whereClause->add( new SimpleWhereClause( TBL_MONTH, '=', $month ) );
        $whereClause->add( new SimpleWhereClause( TBL_YEAR, '=', $year ) );
        $whereClause->add( new SimpleWhereClause( TBL_SCHEMA_SCHEMA, '=', $schemas[ $i ] ) );
        $whereClause->add( new SimpleWhereClause( TBL_SCHEMA_RESOURCE, '=', $resource ) );
        
        $rows = $db_connection->selectWhere( TP_STATISTICS_SCHEMA_TABLE, $whereClause );
        $percentHits = 0;
        $hits = 0;
        $percentMatches = 0;
        $matches = 0;


        if( count( $rows ) )
        {
            $hits = $rows[0][ TBL_SCHEMA_HITS ];
            $percentHits = round( ($hits * 100 ) / $totalHits );
            
            $matches = $rows[0][ TBL_SCHEMA_MATCHES ];
            $percentMatches = round( ( $matches * 100 ) /  $totalMatches);
        }
        
        $returnValue .="
            <TD align=right>
                $hits
            </TD>
            <TD align=right >
                <font color=009900>$percentHits %</font>
            </TD>
            <TD align=right>
                $matches
            </TD>
            <TD align=right >
                <font color=009900>$percentMatches %</font>
            </TD>
";
    }
    
    return $returnValue;
}





function getResourceSummaryRow( &$row, $schemaCount, &$schemas, &$db_connection, $month, $year, $doSchemas )
{
    global $mainPage;
    
    $month = $row[ TBL_MONTH ];
    
    $year = $row[ TBL_YEAR ];
    $totalQueries = $row[ TBL_HITS ];
    $totalMatches = $row[ TBL_MATCHES ];
    $totalZeroMatches = $row[ TBL_0_MATCHES ];
    
    $days=daysInMonth( $month, $year );
    
    $averageQueriesDay = round( $totalQueries / $days  );
    $averageMatchesDay = round( $totalMatches / $days );
    $zeroMatchesPercent = round( ( $totalZeroMatches *100 ) / $totalQueries );
    $averageMatchesPerQuery = round( $totalMatches / $totalQueries );
    $resource = $row[ TBL_RESOURCE ];
    
    if( $doSchemas )
    {
        $schemaTDs = getSchemaTDs( $schemaCount, $schemas, $db_connection, $month, $year, $resource , $totalMatches,$totalQueries );
    }
    $monthStr = monthNumberToText( $month );

    $returnValue ="
        <TR>
            <TD>
                <A HREF=\"$mainPage?action=monthdetail&resource=$resource&month=$month&year=$year\">$monthStr $year</a>
            </TD>
            <TD align=right bgcolor=EEEEEE>
                $days
            </TD>
            <TD align=right bgcolor=EEEEEE>
                $totalQueries
            </TD>
            <TD align=right bgcolor=EEEEEE>
                $averageQueriesDay
            </TD>
            <TD align=right bgcolor=EEEEEE>
                $totalMatches
            </TD>
            <TD align=right bgcolor=EEEEEE>
                $averageMatchesPerQuery
            </TD>
            <TD align=right bgcolor=EEEEEE>
                $totalZeroMatches
            </TD>
            <TD align=right bgcolor=EEEEEE>
                <font color=009900>$zeroMatchesPercent %</font>
            </TD>
            $schemaTDs
        </TR>
";
   return $returnValue;
}



function getResourceSummaryTableHeader( $schemaCount, $schemas, $doSchemas )
{
    
    $returnValue ="
    <TABLE border>
        <TR>
            <TH>
                Month
            </TH>
            <TH bgcolor=DDDDDD>
                Days
            </TH>
            <TH bgcolor=DDDDDD>
                Total<BR>Queries
            </TH>
            <TH bgcolor=DDDDDD>
                Queries<BR>
                per Day
            </TH>
            <TH bgcolor=DDDDDD>
                Total<br>Records Returned
            </TH>
            <TH bgcolor=DDDDDD>
                Records Returned<BR>
                per Query
            </TH>
            <TH colspan=2 bgcolor=DDDDDD>
                Zero Records Returned<br>
                Queries | %
            </TH>
";
    
    if( $doSchemas )
    {
        for( $i = 0; $i < $schemaCount; $i++ )
        {
            $schema = $schemas[ $i ];
            
            
            $hrefBegin="<A HREF=$schema>";
            $hrefEnd="</A>";
    
            if( $schema == "custom" )
            {
                $schemaName = "custom";
                $hrefBegin="";
                $hrefEnd="";
            }
            elseif( $schema == "inventory" )
            {
                $schemaName = "inventory";
                $hrefBegin="";
                $hrefEnd="";
            }
            else
            {
                $schemaName =  substr(strrchr($schema, "/"), 1);        
            }
            
            $returnValue .="
                <TH colspan=4>
                    $hrefBegin$schemaName$hrefEnd<br>
                    Queries | %<br>
                    Records Returned | %<br>
                </TH>
    ";
        }
    }
    
    $returnValue .="
        </TR>
";
   return $returnValue;
}


function getAvailableSchemas( &$schemaCount, &$schemas, &$db_connection, $resource )
{
    $orderBy = array( new OrderBy( TBL_YEAR, DESCENDING, INTEGER_COMPARISON), new OrderBy( TBL_MONTH, DESCENDING, STRING_COMPARISON) );
    $rows = $db_connection->selectWhere( TP_STATISTICS_SCHEMA_TABLE, new SimpleWhereClause( TBL_RESOURCE, '=', $resource ), -1, $orderBy );
    
    
    $schemas = array();
    $count = 0;
    
    foreach( $rows as $row )
    {
        $schemas[ $count ] = $row[ TBL_SCHEMA_SCHEMA ];
        $count++;
    }
    
    $schemas = array_unique( $schemas );
    sort( $schemas );
    usort( $schemas, "compareURLs" );
    $schemaCount = count( $schemas );
}

//BUGBUG this function has no protection from bad dates
function getAvailableSchemasForDateRange( &$schemaCount, &$schemas, &$db_connection, $resource, $startMonth, $endMonth, $endMonth, $endYear )
{
    $orderBy = array( new OrderBy( TBL_YEAR, DESCENDING, INTEGER_COMPARISON), new OrderBy( TBL_MONTH, DESCENDING, STRING_COMPARISON) );
    $rows = $db_connection->selectWhere( TP_STATISTICS_SCHEMA_TABLE, new SimpleWhereClause( TBL_RESOURCE, '=', $resource ), -1, $orderBy );

    $beginStartMonth = $StartMonth;
    $beginEndMonth = 12;
    
    $allRows = array();
    for( $currentYear = $startYear; $currentYear <= $endYear; $currentYear++ )
    {
        if( $currentYear == $endYear )
        {
            $beginEndMonth = $endMonth;
        }
        
        for( $currentMonth = $beginStartMonth; $currentMonth <= $beginEndMonth; $currentMonth++ )
        {
            $rows = $db_connection->selectWhere( TP_STATISTICS_SCHEMA_TABLE, new SimpleWhereClause( TBL_RESOURCE, '=', $resource ), -1, $orderBy );
            $allRows = array_merge( $allRows, $rows );
        }
        $beginMonth = 1;
    }
    
    $schemas = array();
    $count = 0;
    
    foreach( $allRows as $row )
    {
        $schemas[ $count ] = $row[ TBL_SCHEMA_SCHEMA ];
        $count++;
    }
    
    $schemas = array_unique( $schemas );
    
    usort( $schemas );
    
    $schemaCount = count( $schemas );
}


function getResourceSummaryCenter( $month, $year, $resource, &$schemaCount, $doSchemas, $startMonth, $endMonth, $startYear, $endYear )
{
    require_once( 'flatfile/flatfile.php' );
    require_once( TP_WWW_DIR .'/'. 'tapir_statistics.php' );

    $db_connection = new flatfile();
    $db_connection->datadir = TP_STATISTICS_DIR;
    
    

    getAvailableSchemas( $schemaCount, $schemas, $db_connection, $resource );
    
//    $rows = $db_connection->selectAll( TP_STATISTICS_RESOURCE_TABLE );
    $rows = $db_connection->selectWhere( TP_STATISTICS_RESOURCE_TABLE, new SimpleWhereClause( TBL_RESOURCE, '=', $resource ) );
    $rows = array_reverse( $rows );

    $returnValue = getResourceSummaryTableHeader( $schemaCount, $schemas, $doSchemas );
    foreach( $rows as $row )
    {
        $currentMonth = $row[ 0 ];
        $currentYear = $row[ 1 ];
        
        $inRange = 1;
        
        if( $currentYear == $startYear )
        {
            if( $currentMonth < $startMonth )
            {
                $inRange = 0;
            }
        }

        if( $currentYear == $endYear )
        {
            if( $currentMonth > $endMonth )
            {
                $inRange = 0;
            }
        }

        if( $currentYear < $startYear || $currentYear > $endYear )
        {
            $inRange = 0;
        }
        
        if( $inRange )
        {
            $returnValue .= getResourceSummaryRow( $row, $schemaCount, $schemas, $db_connection, $month, $year, $doSchemas );
        }
    }
    
    $returnValue .="</table></td></tr>";
    return $returnValue;
}


function getResourcePageFooter( $month, $year, $resource, $columnCount )
{
return "
</table>
</body></html>
";
}

function getLogFileName( $month, $year )
{
    if( strlen( $month ) < 2 )
    {
        $month = "0$month";
    }
    return $year . "_" . $month . ".tbl";
}

function daysInMonth( $month, $year )
{
    return date('t', strtotime("$year-$month-01")  );  
}

function convertRowToLine( $row  )
{
    $returnValue = "";
    foreach( $row as $element )
    {
        $returnValue .= "$element	";
    }
    return $returnValue;
}

function getResourceMonthDetailRow( $month, $monthStr, $day, $year, $queries, $matches, $zeroMatches, $schemaCount, &$schemaHits,$resource, $dolink=1, $doSchemas = 0  )
{
    global $mainPage;
    $nbsp="";
    $hrefBegin = "";
    $hrefEnd = "";
    
    if( $day < 10 )
    {
        $space=" ";
        $nbsp="&nbsp;&nbsp;";
    }
    $zeroMatchesPercent = 0;
    if(  $queries > 0)
    {
        $zeroMatchesPercent = round( ( $zeroMatches * 100 ) / $queries, 0  );
    }
    
    if($dolink)
    {
        $hrefBegin="<A HREF=\"$mainPage?action=daydetail&month=$month&day=$day&year=$year&resource=$resource\">";
        $hrefEnd="</a>";
    }

    $returnValue ="
        <TR>
            <TD align=center>
                $hrefBegin$monthStr $nbsp$day$hrefEnd
            </TD>
            <TD align=right bgcolor=EEEEEE>
                $queries
            </TD>
            <TD align=right bgcolor=EEEEEE>
                $matches
            </TD>
            <TD align=right bgcolor=EEEEEE>
                $zeroMatches
            </TD>
            <TD align=right bgcolor=EEEEEE>
                <font color=009900>$zeroMatchesPercent %</font>
            </TD>
";

    if( $doSchemas)
    {
        $schemaMatches = 0;
        for( $i = 0; $i < $schemaCount; $i++ )
        {
            if ( ! isset( $schemaHits[ $i ] ) )
            {
                $schemaHits[ $i ] = array( 'queries' => 0, 'matches' => 0 );
            }

            $schemaQueries = $schemaHits[ $i ]['queries'];
            $schemaPercentQueries = 0;
            if( $queries > 0 )
            {
                $schemaPercentQueries = round(($schemaHits[ $i ]['queries'] * 100 )/$queries,0);
            }
            
            $schemaPercentMatches = 0;
            $schemaMatches = $schemaHits[ $i ]['matches'];
            
            if( $matches > 0 )
            {
                $schemaPercentMatches = round(($schemaHits[ $i ]['matches'] * 100 )/$matches,0);
            }
            
            if( $schemaQueries === NULL)
            {
                $schemaQueries="0";
            }
            
            if( $schemaPercentQueries === NULL)
            {
                $schemaPercentQueries="0";
            }
            if( $schemaMatches === NULL)
            {
                $schemaMatches="0";
            }
            if( $schemaPercentMatches === NULL)
            {
                $schemaPercentMatches="0";
            }
            
            $returnValue .="
                <TD align=right>
                    $schemaQueries
                </TD>
                <TD align=right>
                    <font color=009000>$schemaPercentQueries %</font>
                </TD>
                <TD align=right>
                    $schemaMatches
                </TD>
                <TD align=right>
                    <font color=009000>$schemaPercentMatches %</font>
                </TD>
    ";
        }
    }
    return $returnValue;
}

function getResourceMonthTableHeader( $schemaCount, $schemas, $doSchemas )
{
    $returnValue ="
    <TABLE border>
        <TR>
            <TH>
                Day
            </TH>
            <TH bgcolor=DDDDDD>
                Queries
            </TH>
            <TH bgcolor=DDDDDD>
                Records<br>Returned
            </TH>
            <TH colspan=2 bgcolor=DDDDDD>
                Zero Records Returned<br>
                Queries | %
            </TH>
";
    
    if( $doSchemas )
    {
        for( $i = 0; $i < $schemaCount; $i++ )
        {
            $schema = $schemas[ $i ];
            
            $hrefBegin="<A HREF=$schema>";
            $hrefEnd="</A>";
    
            if( $schema == "custom" )
            {
                $schemaName = "custom";
                $hrefBegin="";
                $hrefEnd="";
            }
            elseif( $schema == "inventory" )
            {
                $schemaName = "inventory";
                $hrefBegin="";
                $hrefEnd="";
            }
            else
            {
                $schemaName =  substr(strrchr($schema, "/"), 1);
            }
            
            $returnValue .="
                <TH colspan=4>
                    $hrefBegin$schemaName$hrefEnd<br>
                    Queries | %<br>
                    Records Returned | %<br>
                </TH>
    ";
        }
    }
    $returnValue .="
        </TR>
";
   return $returnValue;
    
}

function getResourceMonthDetailCenter( $month, $year, $resource, $schemaCount, &$schemas, &$db_connection, $doSchemas )
{


    $returnValue = getResourceMonthTableHeader( $schemaCount, $schemas, $doSchemas );
    
    $filename = getLogFileName( $month, $year );
    
    $daysInMonth = daysInMonth( $month, $year );
    
    if( $month == date('m', time()) && $year == date('Y', time()))
    {
        $daysInMonth = date('j', time());
    }
    
    $monthStr = monthNumberToText( $month );

    $totalMonthMatches=0;
    $totalMonthQueries=0;
    $totalMonthZeroMatches=0;
    $totalMonthSchemaHits = array();
    
    
    for( $i = 1; $i <= $daysInMonth; $i++ )
    {
        $space = '';
        if( $i < 10 )
        {
            $space = '0';
        }
       
        $whereClause = new AndWhereClause();
        
        $whereClause->add( new SimpleWhereClause( TBL_MONTH_RESOURCE, '=', 'resource=' . $resource ) );
        $whereClause->add( new SimpleWhereClause( TBL_MONTH_DATE, '=', "$monthStr $space$i $year") );
        $rows = $db_connection->selectWhere( $filename, $whereClause );
        
        $queries = 0;
        $matches = 0;
        $zeroMatches = 0;
        $schemaHits = array();
        if( count( $rows ) )
        {
            $infoArray = array( );
            foreach( $rows as $row )
            {
                parseDataLine( $infoArray, convertRowToLine( $row  ) );
                if( $infoArray['wellformed'] == true )
                {
                    $queries++;
                    $matches = $matches + $infoArray[ 'returnedrecs' ];
                    if( $infoArray[ 'returnedrecs' ] == 0 )
                    {
                        $zeroMatches++;
                    }
                    //BUGBUG bad, defaults to first index.
                    $index=0;
                    
                    //BUGBUG this could be made much more effcient by associative arrays
                    if( $infoArray[ 'type' ] == 'inventory' )
                    {                    
                        for( $j = 0; $j < $schemaCount; $j++ )
                        {
                            if( $schemas[ $j ] == 'inventory' )
                            {
                                $index = $j;
                                $j = $schemaCount;
                            }
                        }
                        $schemaHits[ $index ][ 'matches' ] = $schemaHits[ $index ][ 'matches' ] + $infoArray[ 'returnedrecs' ];
                        $schemaHits[ $index ][ 'queries' ] = $schemaHits[ $index ][ 'queries' ] + 1;
                        
                    }
                    else
                    {
                        //BUGBUG could be made much faster using associative array
                        for( $j = 0; $j < $schemaCount; $j++ )
                        {
                            if( $schemas[ $j ] == $infoArray[ 'recstr' ] )
                            {
                                $index = $j;
                                $j = $schemaCount;
                            }
                        }

                        if( ! isset( $schemaHits[ $index ] ) )
                        {
                            $schemaHits[ $index ] = array( 'matches' => 0, 'queries' => 0 );
                        }

                        $schemaHits[ $index ][ 'matches' ] += $infoArray[ 'returnedrecs' ];
                        $schemaHits[ $index ][ 'queries' ] += 1;
                    }
                }
            }
        }
        

        $returnValue .= getResourceMonthDetailRow( $month, $monthStr, $i, $year, $queries, $matches, $zeroMatches, $schemaCount, $schemaHits, $resource, 1, $doSchemas );
        
        $totalMonthMatches = $totalMonthMatches + $matches;
        $totalMonthQueries = $totalMonthQueries + $queries;
        $totalMonthZeroMatches = $totalMonthZeroMatches + $zeroMatches;
        foreach( array_keys( $schemaHits ) as $key  )
        {
            if( ! isset( $totalMonthSchemaHits[ $key ] ) )
            {
                $totalMonthSchemaHits[ $key ] = array( 'queries' => 0, 'matches' => 0);
            }

            $totalMonthSchemaHits[ $key ]['queries'] += $schemaHits[ $key ]['queries'];
            $totalMonthSchemaHits[ $key ]['matches'] += $schemaHits[ $key ]['matches'];
        }
    }
    
    $returnValue .= getResourceMonthDetailRow( "Total", "Total",   "", $year, $totalMonthQueries, $totalMonthMatches, $totalMonthZeroMatches, $schemaCount, $totalMonthSchemaHits, $resource, 0, $doSchemas );
        
$returnValue .="
</table></td></tr>
";
       
 
    return $returnValue;
}

function getResourceSummaryPage( $providerName, $month, $year, $resource, $doSchemas, $startMonth, $endMonth, $startYear, $endYear )
{
    $returnValue ="";
    $schemaCount = 0;
    $schemas=0;
    require_once('flatfile/flatfile.php');

    $db_connection = new flatfile();
    $db_connection->datadir = TP_STATISTICS_DIR;
    getAvailableSchemas( $schemaCount, $schemas, $db_connection, $resource );


//, $startMonth, $endMonth, $startYear, $endYear
    $returnValue .= getResourcePageHeader( "$providerName: $resource Resource", $resource, $month, "1", $year, $resource );    
    $returnValue .= getResourceSummaryCenter( $month, $year, $resource, $schemaCount, $doSchemas, $startMonth, $endMonth, $startYear, $endYear );
    $returnValue .= getResourcePageFooter( $month, $year, $resource, ( $schemaCount * 4 ) + 8 );

    return $returnValue;
}


function getResourceMonthDetailPage( $providerName, $month, $year, $resource, $doSchemas )
{
    require_once('flatfile/flatfile.php');

    $day = "";
    
    $db_connection = new flatfile();
    $db_connection->datadir = TP_STATISTICS_DIR;
    $monthStr=monthNumberToText( $month);
    $title = "$providerName: $resource $monthStr $year";
    getAvailableSchemas( $schemaCount, $schemas, $db_connection, $resource );
    $returnValue ="";
    $returnValue .= getResourcePageHeader( $title, $title, $month, $day, $year, $resource, $doSchemas );
    $returnValue .= getResourceMonthDetailCenter( $month, $year, $resource, $schemaCount, $schemas, $db_connection, $doSchemas );
    $returnValue .= getResourcePageFooter( $month, $year, $resource, ( $schemaCount * 4 ) + 8 );

    return $returnValue;
}


function getStatisticsPage( $providerName, $availableLogFileNames, $cachedMonthsFiles, $startMonth, $startYear, $endMonth, $endYear )
{
    $count = 0;
    $month = date( 'm' );
    $year = date( 'Y' );
    
    $monthStr = monthNumberToText( $month );

    
    if( $startMonth == false || $endMonth == false || $startYear == false || $endYear == false )
    {
        $seperator ="";
        $period=monthNumberToText( date('m') ) . " " .date('Y');
    }
    elseif( $startYear == $endYear && $startMonth == $endMonth  )
    {
        $period=monthNumberToText( $startMonth )   . " " . $startYear;
    }
    else 
    {
        $period=monthNumberToText( $startMonth ) ." $startYear - ". monthNumberToText( $endMonth ) ." $endYear";
    }

    $title = "$providerName: Database Query Statistics for $period";
    $returnValue = getResourcePageHeader( $title, $title, $month, "01", $year, "" );
    

/*
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
        <td>
*/




    $returnValue .="
            </tr>
    </TD>
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
        <td>
            <big><B>Query statistics for all resources for $period</B></big>
        </TD>
    </TR>
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
        <td>
";


    $returnValue .= getResultsTables( $availableLogFileNames,$cachedMonthsFiles );


    $returnValue .= "
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
        <td>
        <br>
        <br>
        <TD>
    </TR>
";

    
    $returnValue .= "
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
        <td>
            <big><B>Select another reporting period</b></big>
        <TD>
    </TR>
";
    $returnValue .= "
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
        <td>
";


    $returnValue .= generateAvailableDataTable( $availableLogFileNames, $cachedMonthsFiles );
    
    $returnValue .= "
       </TD>
    </TR>
";




    $returnValue .= "
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
        <td>
            <BR>
";

    $returnValue .= generatePeriodForm( $availableLogFileNames );

    $returnValue .= "
        </TD>
    </TR>
    <TR>
        <td width=100% colspan=2 bgcolor=23238E>
            <br>
        </td>
    </TR>        
";

$returnValue .=getCustomQueriesTable( $availableLogFileNames );


$returnValue .="
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
                            <TD>
                            <br>
                            </TD>
    </tr>
";
    $resource = ''; // rdg: included this line because it must be defined somewhere (?)

    $returnValue .= getResourcePageFooter( $month, $year, $resource,  4 );
    //$returnValue .= "
//        </TD>
  //  </TR>
//";

    return $returnValue;
}

function findCachedFile( $startMonth, $startYear, $cachedMonthsFiles )
{
  return 0;
  $returnValue = 0;
  $length = count( $cachedMonthsFiles );
  $targetFile = $startYear . "_" . $startMonth . ".html";
  for( $i = 0; $i < $length; $i++ )
  {
    $temp = $cachedMonthsFiles[ $i ];
    if( $cachedMonthsFiles[ $i ] == $targetFile )
    {
        $returnValue = $cachedMonthsFiles[ $i ];
        $i = $length;
    }
  }
  return $returnValue;
}

function getResultsTables( $availableLogFileNames, $cachedMonthsFiles )
{
    $current_month = date('m');
    $current_year  = date('Y');

    $returnValue = FALSE;

    $startMonth= ( isset( $_GET['startmonth'] ) ) ? $_GET['startmonth'] : $current_month;
    $endMonth= ( isset( $_GET['endmonth'] ) ) ? $_GET['endmonth'] : $current_month;
    $startYear= ( isset( $_GET['startyear'] ) ) ? $_GET['startyear'] : $current_year;
    $endYear= ( isset( $_GET['endyear'] ) ) ? $_GET['endyear'] : $current_year;
    
    $cacheFile = findCachedFile( $startMonth, $startYear, $cachedMonthsFiles );
    if( ( $startMonth != $endMonth ) || ( $startYear != $endYear )                //if its a range
        || ( ( $startMonth == $current_month ) && ( $startYear == $current_year ) ) //if the start month and year are the current month/year
        || $cacheFile == 0 ) // cache file doesn't exist
    {
        $textOutput='';
        $returnValue = getResultsTableHeader( $startMonth, $startYear, $endMonth, $endYear );
        $returnValue .= getResultsTableCenter( $startMonth, $startYear, $endMonth, $endYear, $availableLogFileNames, $textOutput );
        $returnValue .= getResultsTableFooter( $textOutput );
        /*
        if( $startMonth == $endMonth && $startYear == $endYear && ( $startMonth != $current_month || $startYear != $current_year )   )
        {
            $cacheFile = TP_STATISTICS_DIR . $startYear . "_" . $startMonth . ".html";
            $resource = fopen( $cacheFile, "w+"  );
            if( $resource ) 
            {
                fwrite( $resource, $returnValue );
                fclose( $resrouce );
            }           
        }
        */
    }
    else
    {
        $cacheFile = $startYear . "_" . $startMonth . ".html";
        //$returnValue = file_get_contents( TP_STATISTICS_DIR . $cacheFile );
        $resource = fopen( TP_STATISTICS_DIR . $cacheFile, "r"  );
        $tf = TP_STATISTICS_DIR . $cacheFile;
        if( $resource ) 
        {
            $returnValue = fread( $resource, filesize(TP_STATISTICS_DIR . $cacheFile) );
            fclose( $resource );
        }
    }

    return $returnValue;
}

function getCustomQueriesTable( $availableLogFileNames )
{
 $returnVal = "
 
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
        <TD>
            <BIG><B>Custom Report</B></big>
        </TD>
    </tr>
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
        <td>
            <TABLE border>
                <TR >
                    <TD>
";

 $returnVal .= generateCustomQueryForm( $availableLogFileNames );
 $returnVal .= "
                    </TD>
                </TR>
            </TABLE>
        </TD>
    </TR>
    
    
    ";
   return $returnVal;
}

    //getLogArray( $availableLogFileNames );
    
function getResultsTableHeader( $startMonth, $startYear, $endMonth, $endYear )
{
    $stringStartMonth = monthNumberToText( $startMonth );
    $stringEndMonth = monthNumberToText( $endMonth );

/*
                                <TR>
                                    <TD colspan=\"1\">Start: $stringStartMonth $startYear</TD>
                                    <TD colspan=\"3\">End: $stringEndMonth $endYear</TD>
                                </TR>
*/



return "
                            <TABLE border>
                                <TR bgcolor=DFE5FA>
                                    <TD colspan=2>
                                    </TD>
                                    <TD align=\"right\">
                                    </TD>
                                    <TD colspan=3 align=\"center\">
                                        Search
                                    </TD>
                                    <TD colspan=3 align=\"center\">
                                        Inventory
                                    </TD>
                                    <TD colspan=2 align=\"center\">
                                        Total
                                    </TD>
                                    <TD colspan=2 align=\"center\">
                                        Records Returned
                                    </TD>
                                </TR>
                                <TR bgcolor=DFE5FA>
                                    <TD colspan=2 align=\"center\">
                                        Month
                                    </TD>
                                    <TD align=\"center\">
                                        Days
                                    </TD>
                                    <TD align=\"center\">
                                        Queries
                                    </TD>
                                    <TD align=\"center\">
                                        Records Returned
                                    </TD>
                                    <TD align=\"center\">
                                        % of Total
                                    </TD>
                                    <TD align=\"center\">
                                        Queries
                                    </TD>
                                    <TD align=\"center\">
                                        Records Returned
                                    </TD>
                                    <TD align=\"center\">
                                        % of Total
                                    </TD>
                                    <TD align=\"center\">
                                        Queries
                                    </TD>
                                    <TD align=\"center\">
                                        Records Returned
                                    </TD>
                                    <TD align=\"center\">
                                        per Day
                                    </TD>
                                    <TD align=\"center\">
                                        per Query
                                    </TD>
                                </TR>

";
}
    

function addMonthTodDate( $currentDate )
{
    $currentDate++;
    if( substr( strval( $currentDate ),4,2 )==13 )
    {
        $currentDate = $currentDate + 100 - 12;
    }
    return $currentDate;
}


function parseDataLine( &$dataArray, $dataLine )
{
    $infoArray = explode( "\t", $dataLine );
    $dataArray = array();
    $dataArray[ 'wellformed' ] = false;

    if( isset( $infoArray[ 4 ] ) and substr( $infoArray[ 4 ], 0, 4  ) == 'type' )
    {
        $elementCount = count( $infoArray );
        $dataArray[ 'wellformed' ] = true;
        $dataArray[ 'time' ] = $infoArray[ 1 ];
        $dataArray[ 'destination_ip' ] = $infoArray[ 2 ];
        $dataArray[ 'date' ] = $infoArray[ 0 ];
        for( $i = 4; $i < $elementCount; $i++ )
        {
            $element = explode( '=', $infoArray[ $i ] );
            if( $element[0] == 'filter' )
            {
                $dataArray[ 'filter' ] = substr($infoArray[ $i ],7);
            }
            elseif( $element[0] == 'request' )
            {
                $dataArray[ 'request' ] = $infoArray[ $i ];
            }
            elseif( $element[0] == 'whereclause' )
            {
                $dataArray[ 'whereclause' ] = substr($infoArray[ $i ],12);
            }
            else
            {
                $new_value = ( isset( $element[ 1 ] ) ) ? $element[ 1 ] : '?';

                $dataArray[ $element[0] ] = $new_value;
            }
        }
    }
}

function compareSchemaArray( $recordA, $recordB )
{
    return strcmp( $recordA[ 0 ], $recordB[ 0 ] );
}

function getResultsTableCenter( $startMonth, $startYear, $endMonth, $endYear, $availableLogFileNames, &$textOutput )
{
    global $mainPage;
    
    $period=monthNumberToText( $startMonth ) ." $startYear - ". monthNumberToText( $endMonth ) ." $endYear";
    if( $startMonth == false || $endMonth == false || $startYear == false || $endYear == false || 
      ( $startYear == $endYear && $startMonth == $endMonth ) )
    {
        $seperator ="";
        $period=monthNumberToText( $startMonth ) . " $startYear";
    }

    //BUGBUG always 1
    $doMonthDetailColumn = 0;
    $monthDetailHeader = "";
    
    $columnHeader="Resource";
    
    if( $startMonth == $endMonth && $startYear == $endYear )
    {
        $doMonthDetailColumn = 1;
        $columnHeader = "Month";
    }

        $monthDetailHeader =
"                                                <TD>
                                                    $columnHeader Detail
                                                </TD>
";
    
    $textOutput = "";

    $returnValue="";
    
    $colorBackground1="#DDDDFF";
    $colorBackground2="#FFFFFF";

    $total_inventory_hits = 0;
    $total_search_hits = 0;
    $total_custom_hits = 0;
    
    $currentPosition = 0;
    $notFound = TRUE;

    $totalFiles = count( $availableLogFileNames );
    
    $textOutput = "Date     \tTotal\tInv\tSearch\tCustom\n----------------------------------------------------------";
    $tableCenter = "";

    $endDate = intval($endYear . $endMonth );

    $currentDate = intval($startYear . $startMonth );
    $startDate = intval($startYear . $startMonth );
    
    $continue = TRUE;

    $destinationAddresses = array();
    $sourceAddresses = array();
    $sourceHosts = array();
    $resources = array();
    $returnedRecs = array();
    $recordStructs = array();


    $daysCounted = array();
    $resourceDaysCounted = array();
    $totalDaysCounted=0;
    
    while( $currentDate <= $endDate  )
    {
        
        $inventory_hits = 0;
        $search_hits = 0;
        $custom_hits = 0;

        $inventory_matches = 0;
        $search_matches = 0;
        $custom_matches = 0;

        $currentMonth = substr( strval( $currentDate ),4, 2 );
        $currentYear = substr( strval( $currentDate ),0, 4 );

        $file = TP_STATISTICS_DIR . '/' . $currentYear. '_' . $currentMonth . '.tbl';

        if ( file_exists( $file ) )
        {
            $data = fopen( $file, 'r' );
        
            if( $data )
            {
                $daysCounted=array();
                while( !feof( $data ) )
                {
                
                    parseDataLine( $infoArray, fgets( $data, 8192 ) );

                    if( $infoArray[ 'wellformed' ] == true )
                    {
                        $day = substr( $infoArray[ 'date' ], 4, 2 );
                        if( isset( $daysCounted[ $day ] ) )
                        {
                            $daysCounted[ $day ]++;
                        }
                        else
                        {
                            $daysCounted[ $day ] = 1;
                        }
                    
                        if( $infoArray['type'] == "search" || $infoArray['type'] == "custom" )
                        {
                            $search_hits++;
                            $search_matches = $search_matches + $infoArray[ 'returnedrecs' ];                        
                        }
                        elseif( $infoArray['type'] == "inventory" )
                        {
                            $inventory_hits++;
                            $inventory_matches = $inventory_matches + $infoArray[ 'returnedrecs' ];
                        }
                        /*
                        elseif( $infoArray['type'] == "custom" )
                        {
                            $custom_hits++;
                            $custom_matches = $custom_matches + $infoArray[ 'returnedrecs' ];
                        }
                        */
                        else
                        {
                            $infoArray[ 'wellformed' ] = false;
                        }
                    
                        if( $infoArray[ 'wellformed' ] == true )
                        {
                            if( isset( $destinationAddresses[ $infoArray[ 'destination_ip' ] ] ) )
                            {
                                $destinationAddresses[ $infoArray[ 'destination_ip' ] ]++;
                            }
                            else
                            {
                                $destinationAddresses[ $infoArray[ 'destination_ip' ] ] = 1;
                            }
                            if( isset( $sourceAddresses[ $infoArray[ 'source_ip' ] ] ) )
                            {
                                $sourceAddresses[ $infoArray[ 'source_ip' ] ]++;
                            }
                            else
                            {
                                $sourceAddresses[ $infoArray[ 'source_ip' ] ] = 1;
                            }
                            if( isset( $sourceHosts[ $infoArray[ 'source_host' ] ] ) )
                            {
                                $sourceHosts[ $infoArray[ 'source_host' ] ]++;
                            }
                            else
                            {
                                $sourceHosts[ $infoArray[ 'source_host' ] ] = 1;
                            }
                            if( isset( $resources[ $infoArray[ 'resource' ] ] ) )
                            {
                                $resources[ $infoArray[ 'resource' ] ]++;
                            }
                            else
                            {
                                $resources[ $infoArray[ 'resource' ] ] = 1;
                            }
                        
                            $resourceDaysCounted[  $infoArray[ 'resource' ]  ][ "$currentMonth $currentYear $day" ] = 1;

                            if( isset( $returnedRecs[ $infoArray[ 'resource' ] ] ) )
                            {
                                $returnedRecs[ $infoArray[ 'resource' ] ] += $infoArray[ 'returnedrecs' ];
                            }
                            else 
                            {
                                $returnedRecs[ $infoArray[ 'resource' ] ] = $infoArray[ 'returnedrecs' ]; 
                            }
                        
                            if( $infoArray['type'] == 'search' || $infoArray['type'] == 'ustom' )
                            {
                                if( isset( $returnedRecs[ 'search' ] ) )
                                {
                                    $returnedRecs[ 'search' ] += $infoArray[ 'returnedrecs' ];
                                }
                                else
                                {
                                    $returnedRecs[ 'search' ] = $infoArray[ 'returnedrecs' ];
                                }
                            }
                            else
                            {
                                $returnedRecs[ $infoArray[ 'type' ] ] = $returnedRecs[ $infoArray[ 'type' ] ] + $infoArray[ 'returnedrecs' ];
                            }

                            //BUGBUG is a resource is named total, this will cause problems
                            if( isset( $returnedRecs[ 'total' ] ) )
                            {
                                $returnedRecs[ 'total' ] += $infoArray[ 'returnedrecs' ];
                            }
                            else
                            {
                                $returnedRecs[ 'total' ] = $infoArray[ 'returnedrecs' ];
                            }

                            if( ! isset( $recordStructs[ $infoArray[ 'recstr' ] ]  ) )
                            {
                                $recordStructs[ $infoArray[ 'recstr' ] ] = array('hits' => 0, 'total' => 0 );
                            }
                            $recordStructs[ $infoArray[ 'recstr' ] ]['hits']++;


                            $recordStructs[ $infoArray[ 'recstr' ] ]['total'] += $infoArray[ 'returnedrecs' ];   
                        }
                    }
                }
            }    
        }
        else
        {
            $inventory_hits = 'No Data';
            $search_hits = 'No Data';
            $custom_hits = 'No Data';
        }

        
        if( $currentDate % 2 == 0 )
        {
            $currentColor = $colorBackground1;
        }
        else
        {
            $currentColor = $colorBackground2;
        }
        $daysCounted = count( $daysCounted );
        $totalDaysCounted = $totalDaysCounted +$daysCounted;
        
        $currentMonth =  monthNumberToText( $currentMonth );
        $monthHits = $inventory_hits + $search_hits + $custom_hits;
        $monthMatches = $inventory_matches + $search_matches + $custom_matches;

        if ( $monthHits > 0 )
        {
            $averageMatchesPerQuery = round(  $monthMatches  / $monthHits, 2 );
            $inventoryPercentage = round( ( $inventory_hits * 100 ) /  $monthHits, 2 );
            $searchPercentage = round( ( $search_hits * 100 ) / $monthHits, 2 );
            $customPercentage = round( ( $custom_hits * 100 ) / $monthHits, 2 );
        }
        else
        {
            $averageMatchesPerQuery = 0;
            $inventoryPercentage = 0;
            $searchPercentage = 0;
            $customPercentage = 0;
        }

        if ( $daysCounted > 0 )
        {
            $averageMatchesPerDay = round( $monthMatches  / $daysCounted, 2 );
        }
        else
        {
            $averageMatchesPerDay = 0;
        }
        /*bgcolor=\"$currentColor\"*/
        $tableCenter .= "
                                <TR >
                                    <TD colspan=2> $currentMonth $currentYear </TD>
                                    <TD align=\"right\"> $daysCounted </TD>
                                    <TD align=\"right\"> $search_hits </TD><TD align=\"right\"> $search_matches </TD><TD align=\"right\"><font color=009900>$searchPercentage % </font></TD>
                                    <TD align=\"right\"> $inventory_hits </TD><TD align=\"right\"> $inventory_matches </TD><TD align=\"right\"><font color=009900>$inventoryPercentage % </font></TD>
                                    <!--<TD align=\"right\">$custom_hits / $custom_hits</TD>
                                    <TD align=\"right\">$customPercentage</TD >-->
                                    <TD align=\"right\"> $monthHits </td><TD align=\"right\"> $monthMatches </TD>
                                    <TD align=\"right\"> $averageMatchesPerDay </TD >
                                    <TD align=\"right\"> $averageMatchesPerQuery </TD >
                                </TR>
";
		
        $textOutput .= "\n" . $currentMonth . " " . $currentYear . "\t" . $monthHits . "\t" . $inventory_hits . "\t" . $search_hits . "\t" . $custom_hits;
        $total_inventory_hits = $total_inventory_hits + $inventory_hits;
        $total_search_hits = $total_search_hits + $search_hits;
        $total_custom_hits = $total_custom_hits + $custom_hits;
        $currentDate = addMonthTodDate( $currentDate );
    }

    $totalHits = $total_inventory_hits + $total_search_hits + $total_custom_hits;

    $totalMatches = ( isset( $returnedRecs[ 'total' ] ) ) ? $returnedRecs[ 'total' ] : 0;

    if ( $totalHits > 0 )
    {
        $totalInventoryPercentage = round( ( $total_inventory_hits * 100 ) / $totalHits ,2 );
        $totalSearchPercentage = round( ( $total_search_hits * 100 ) / $totalHits, 2 );
        $totalCustomPercentage = round( ( $total_custom_hits * 100 ) / $totalHits, 2 );
        $averageMatchesPerQuery = round( $totalMatches  / $totalHits, 2 );
    }
    else
    {
        $totalInventoryPercentage = 0;
        $totalSearchPercentage = 0;
        $totalCustomPercentage = 0;
        $averageMatchesPerQuery = 0;
    }

    $totalInventoryHits = ( isset( $returnedRecs[ 'inventory' ] ) ) ? $returnedRecs[ 'inventory' ] : 0;
    $totalSearchHits = ( isset( $returnedRecs[ 'search' ] ) ) ? $returnedRecs[ 'search' ] : 0;
    $totalCustomHits = ( isset( $returnedRecs[ 'custom' ] ) ) ? $returnedRecs[ 'custom' ] : 0;

    if ( $totalDaysCounted > 0 )
    {
        $averageMatchesPerDay = round( $totalMatches  / $totalDaysCounted, 2 );
    }
    else
    {
        $averageMatchesPerDay = 0;
    }

    if( $totalSearchHits == NULL)
    {
        $totalSearchHits =0;
    }
    if( $totalInventoryHits == NULL)
    {
        $totalInventoryHits =0;
    }
    
    $tableCenter .="
                                <TR>
                                    <TD colspan=2>Totals:</TD><TD align=right>$totalDaysCounted</TD>
                                    <TD align=\"right\">$total_search_hits</TD><TD align=\"right\">$totalSearchHits</TD><TD align=\"right\"><font color=009900>$totalSearchPercentage %</font</TD>
                                    <TD align=\"right\">$total_inventory_hits</TD><TD align=\"right\">$totalInventoryHits</TD><TD align=\"right\"><font color=009900>$totalInventoryPercentage %</font></TD>
                                    <!--<TD align=\"right\">$total_custom_hits</TD><TD align=\"right\">$totalCustomHits</TD><TD align=\"right\">$totalCustomPercentage</TD>-->
                                    <TD align=\"right\">$totalHits</TD><TD align=\"right\"> $totalMatches</TD>
                                    <TD align=\"right\">$averageMatchesPerDay</TD>
                                    <TD align=\"right\">$averageMatchesPerQuery</TD>
                                </TR>
                            </TABLE>
                            </TD>
                        </TR>
";


$tableCenter .="
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
                            <TD>
                                <BR>
                                <BR>
                            </TD>
    </tr>

    
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
        <td>
            <big><B>Statistics by resource for $period</b></big>
        </TD>
    </TR>


    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
        <td>
        

                                        <TABLE border>
                                            <TR bgcolor=DFE5FA>
                                                <TD>
                                                    Resource Name
                                                </TD>
$monthDetailHeader
                                                <TD>
                                                    Days
                                                </TD>
                                                <TD >
                                                    Searches
                                                </TD>
                                                <TD>
                                                    Records Returned
                                                </TD>
                                                <TD>
                                                    Queries per Day
                                                </TD>
                                                <TD>
                                                    Records Returned per Day
                                                </TD>
                                                <TD>
                                                    Records Returned per Query
                                                </TD>
                                            </TR>
                            ";

    $count=0;
    asort( $resources, SORT_STRING );
    
    foreach( array_keys( $resources ) as $resource )
    {
        
        $currentColor = $colorBackground1;
        if( $count % 2)
        {
            $currentColor = $colorBackground2;
        }
        $monthLinks="";
        if( $doMonthDetailColumn )        
        {
            $monthLinks=
"                                                <TD align=\"right\"> 
                                                    <A href=\"$mainPage?action=monthdetail&resource=$resource&month=$startMonth&year=$startYear&detailed=0\">simple</A>                                                     <A href=\"$mainPage?action=monthdetail&resource=$resource&month=$startMonth&year=$startYear&detailed=1\">detailed</A>
                                                </TD>                                                
";
        }
        else
        {
            $monthLinks=
"                                                <TD align=\"right\"> 
                                                    <A href=\"$mainPage?action=summary&resource=$resource&startmonth=$startMonth&startyear=$startYear&endmonth=$endMonth&endyear=$endYear&detailed=0\">simple</A> <A href=\"$mainPage?action=summary&resource=$resource&startmonth=$startMonth&startyear=$startYear&endmonth=$endMonth&endyear=$endYear&detailed=1\">detailed</A>
                                                </TD>                                                
";
        }
        
/*bgcolor=\"$currentColor\"*/
    $tableCenter .="
                                            <TR >
                                                <TD>
                                                    $resource
                                                </TD>
$monthLinks
                                                <TD align=\"right\"> 
                                                    " . count( $resourceDaysCounted[ $resource  ]  ) .  "
                                                </TD>
                                                <TD align=\"right\">
                                                    ". $resources[ $resource ] .
"                                               </TD>
                                                <TD align=\"right\">
                                                    ". $returnedRecs[ $resource ] .
"                                               </TD>
                                                <TD align=\"right\">
                                                    ". round( $resources[ $resource ] / $daysCounted ) .
"                                               </TD>
                                                <TD align=\"right\">
                                                    ". round( $returnedRecs[ $resource ] / $daysCounted ) .
"                                               </TD>
                                                <TD align=\"right\">
                                                    ". round( $returnedRecs[ $resource ] / $resources[ $resource ] ) .
"                                               </TD>
                                            </TR>
";
        $count++;
    }
    
    $tableCenter .="
                                        </TABLE>
                                    </TD>
                                </TR>
";


$tableCenter .="
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
                            <TD>
                            <br>
                            <br>
                            </TD>
    </tr>
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
                            <TD>
                                <BIG><B>Statistics by Results Schema for $period</B></big>
                            </TD>
    </tr>
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
        <td>
                                        <TABLE border>
                                            <TR bgcolor=DFE5FA>
                                                <TD>
                                                    Results Schema
                                                </TD>
                                                <TD>
                                                    Queries
                                                </TD>
                                                <TD>
                                                    Returned Records
                                                </TD>
                                                <TD>
                                                    URL
                                                </TD>
                                            </TR>
";

    
    $count=0;
    
    $recsArray = array();
    $customArray = array();
    $inventoryArray = array();
    $count = 0;
    
    
    foreach( array_Keys( $recordStructs ) as $key )
    {
        if( strlen( $key ) == 0 )
        {
            $name = "inventory";
            $inventoryArray[0] = $name;
            $inventoryArray[1] = "inventory";
            $inventoryArray[2] = $recordStructs[ "" ][ "hits" ];
            $inventoryArray[3] = $recordStructs[ "" ][ "total" ];
            $inventoryArray[4] = "&nbsp;";
        }
        else if( $key == "custom" )
        {
            $customArray[0] = $name;
            $customArray[1] = "custom";
            $customArray[2] = $recordStructs[ "custom" ][ "hits" ];
            $customArray[3] = $recordStructs[ "custom" ][ "total" ];
            $customArray[4] = "&nbsp;";
        }
        else
        {
            $hrefBegin="<A HREF=\"$key\">";
            $hrefEnd="</A>";

            $name =  substr(strrchr($key, "/"), 1);
            $recsArray[ $count ][0] = $name;
            $recsArray[ $count ][1] = $name;
            $recsArray[ $count ][2] = $recordStructs[ $key ][ "hits" ];
            $recsArray[ $count ][3] = $recordStructs[ $key ][ "total" ];
            $recsArray[ $count ][4] = "$hrefBegin$key$hrefEnd";
            
            $count++;
        }
    }
    
    usort( $recsArray, "compareSchemaArray" );
    
    if( count( $inventoryArray ) )
    {
        array_unshift( $recsArray, $inventoryArray );
    }
    
    if( count( $customArray ) )
    {
        array_unshift( $recsArray, $customArray );
    }
    
    foreach( $recsArray as $recstr )
    {
        $link = $recstr[ 1 ];
        $hits = $recstr[ 2 ];
        $total = $recstr[ 3 ];
        $url = $recstr[ 4 ];
        
        $currentColor = $colorBackground1;
        if( $count % 2)
            $currentColor = $colorBackground2;
  
    /*bgcolor=\"$currentColor\"*/
    $tableCenter .="
                                            <TR >
                                                <TD align=\"left\">
                                                    $link
                                                </TD>
                                                <TD align=\"right\">
                                                    ". $hits .
"                                               </TD>
                                                <TD align=\"right\">
                                                    ". $total .
"                                               </TD>
                                                <TD align=\"left\">
                                                    ". $url .
"                                               </TD>
                                            </TR>
";
        $count++;
    }
    
    $tableCenter .="
                                        </TABLE>
                                    </TD>
                                </TR>
";



    $textOutput = $textOutput . "\n" . "----------------------------------------------------------\nTotals:     \t" . $totalHits . "\t" . $total_inventory_hits .  "\t" . $total_search_hits . "\t" .$total_custom_hits;
    return $returnValue . $tableCenter;
}


function getResultsTableFooter( $textOutput )
{
return "";
/*
    return "
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
                            <TD>
                            <br>
                            </TD>
    </tr>
    <tr>
        <td width=5% bgcolor=DFE5FA align=center valign=top>&nbsp;<p>
            <font face=\"Helvetica,Arial,Verdana\"><p></font>
        </td>
                            <TD colspan=\"3\">
                                <FORM name=\"textarea\">
                                <TEXTAREA cols=\"80\" rows=\"20\" readonly>
$textOutput
                                </TEXTAREA>
                                </FORM>
                            </TD>
    </TR>
    ";
    */
}    




function getResourceDayDetailPage( $providerName, $month, $day, $year, $resource, $textbox )
{
    require_once('flatfile/flatfile.php');
    $db_connection = new flatfile();
    $db_connection->datadir = TP_STATISTICS_DIR;
    $monthStr = monthNumberToText( $month );
    //$day = date('d');
    $title = "$providerName: $resource Resource Queries for $monthStr $day, $year";
    getAvailableSchemas( $schemaCount, $schemas, $db_connection, $resource );
    $returnValue = "";
    $returnValue .= getResourcePageHeader( $title, $title, $month, $day, $year, $resource );
    $returnValue .= getResourceDayDetailCenter( $month, $day, $year, $resource, $db_connection, $textbox );
    $returnValue .= getResourcePageFooter( $month, $year, $resource, 5 );

    return $returnValue;
}


function getResourceDayDetailCenter( $month, $day, $year, $resource, &$db_connection, $textbox )
{

    $returnValue = getResourceDayDetailTableHeader( $month,$day,$year );
    
  
    $filename = getLogFileName( $month, $year );
    
    $monthStr = monthNumberToText( $month );
    
    $space = "";
    
    if( $day < 10 )
    {
        $space = "0";
    }
       
    $whereClause = new AndWhereClause();
    $whereClause->add( new SimpleWhereClause( TBL_MONTH_RESOURCE, '=', "resource=" . $resource ) );
    $whereClause->add( new SimpleWhereClause( TBL_MONTH_DATE, "=", "$monthStr $space$day $year") );
    $rows = $db_connection->selectWhere( $filename, $whereClause );


    if( $textbox )
    {
        $textrows=count( $rows ) + 1;
        if( $textrows < 4)
        {
            $textrows=4;
        }

        $returnValue .="<tr><td colspan=5><FORM name=\"textarea\"><TEXTAREA COLS=60 ROWS=$textrows WRAP=OFF name=\"textresults\">";
    }


    if( count( $rows ) )
    {
        foreach( $rows as $row )
        {
            parseDataLine( $infoArray, convertRowToLine( $row  ) );
            if( $infoArray['wellformed'] == true )
            {
                $time = $infoArray[ 'time' ];
                $matches =  $infoArray[ 'returnedrecs' ];
                $whereclause=htmlspecialchars($infoArray[ 'whereclause' ]);
                $host=$infoArray[ 'source_host' ];
                $schema = $infoArray[ 'recstr' ];
                $dolink = 1;
                if( $infoArray[ 'type' ] == "inventory" )
                {
                    $dolink = 0;
                    $schema = $infoArray[ 'column' ];
                    //$whereclause="DISTINCT " . htmlspecialchars($infoArray[ 'column' ]) . " " . htmlspecialchars($infoArray[ 'whereclause' ]) ;
                    $whereclause = htmlspecialchars($infoArray[ 'whereclause' ]) ;
                }
                else if( $infoArray[ 'type' ] == "custom" )
                {
                    $dolink = 0;
                }
                
                $returnValue .= getResourceDayDetailRow( $time, $host, $matches, $whereclause, $schema, $textbox, $dolink );
            }
        }
    }
    if( $textbox )
    {
        $returnValue .="</TEXTAREA></FORM></td></tr>";
    }
                        
        
        
$returnValue .="
</table></td></tr>
";
    return $returnValue;
}

function getjavaScript()
{

return
"<SCRIPT LANGUAGE=\"JavaScript\">
<!--


function windowSize( direction )
{
    var myWidth = 0, myHeight = 0;
    if( typeof( window.innerWidth ) == 'number' )
    {
        //Non-IE
        myWidth = window.innerWidth;
        myHeight = window.innerHeight;
    }
    else if( document.documentElement && 
                ( document.documentElement.clientWidth ||
                  document.documentElement.clientHeight )
           )
    {
        //IE 6+ in 'standards compliant mode'
        myWidth = document.documentElement.clientWidth;
        myHeight = document.documentElement.clientHeight;
    }
    else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) )
    {
        //IE 4 compatible
        myWidth = document.body.clientWidth;
        myHeight = document.body.clientHeight;
    }
    
    if( direction )
    {
        return myWidth;
    }
     
    return myHeight;
}


function setTextAreaSize() {
    var the_form = document.forms[0];
    
    for ( var x in the_form )
    {
        if ( ! the_form[x] )
        {
            continue;
        }
        
        if( typeof the_form[x].rows != \"number\" )
        {
            continue;
        }
        the_form[x].cols = windowSize( 1 ) / 14;
    }
    
    setTimeout(\"setTextAreaSize();\", 300);
}

window.onload = setTextAreaSize;
-->
</SCRIPT>";
}

function getResourceDayDetailTableHeader( $month,$day,$year )
{
    $tz = date('T',mktime(0,0,0,$month,$day,$year));
    $returnValue ="
    <TABLE border>
        <TR>
            <TH>
                $tz
            </TH>
            <TH>
                Remote Host
            </TH>
            <TH>
                Records Returned
            </TH>
            <TH>
                Query
            </TH>
            <TH>
               Results Schema
            </TH>
        </TR>
";
   return $returnValue;
    
}



         
function getResourceDayDetailRow( $time, $host, $matches, $filter, $schema, $textbox, $dolink  )
{

    $hrefBegin="<A HREF=$schema>";
    $hrefEnd="</A>";

    if( !$dolink )
    {
        $schemaName = $schema;
        $hrefBegin="";
        $hrefEnd="";
    }
    else
    {
        $schemaName =  substr(strrchr($schema, "/"), 1);
    }

    if( $textbox )
    {
        $returnValue = "$time\t$host\t$matches\t$filter\t$schema\r";
    }
    else
    {
        $returnValue ="
        <TR>
            <TD align=right>
                $time
            </TD>
            <TD align=right>
                $host
            </TD>
            <TD align=right>
                $matches
            </TD>
            <TD align=right>
                $filter
            </TD>
            <TD align=right>
                $hrefBegin$schemaName$hrefEnd
            </TD>
        </TR>
";
    }
    
    return $returnValue;
}

?>

