<?php

require_once('TpUtils.php');
require_once('TpServiceUtils.php');

define('TBL_MONTH', 0);
define('TBL_YEAR' , 1);

//for table resources
define('TBL_RESOURCE' , 2);
define('TBL_DAYS'     , 3);
define('TBL_HITS'     , 4);
define('TBL_MATCHES'  , 5);
define('TBL_0_MATCHES', 6);

//for table schema
define('TBL_SCHEMA_RESOURCE', 2);
define('TBL_SCHEMA_SCHEMA'  , 3);
define('TBL_SCHEMA_HITS'    , 4);
define('TBL_SCHEMA_MATCHES' , 5);

//for table month
define('TBL_MONTH_DATE'        , 0);
define('TBL_MONTH_TIME'        , 1);
define('TBL_MONTH_PORTAL'      , 2);
define('TBL_MONTH_LEVEL'       , 3);
define('TBL_MONTH_TYPE'        , 4);
define('TBL_MONTH_STATUS'      , 5);
define('TBL_MONTH_METHOD'      , 6);
define('TBL_MONTH_RESOURCE'    , 7);
define('TBL_MONTH_FILTER'      , 8);
define('TBL_MONTH_STARTREC'    , 9);
define('TBL_MONTH_MAXRECS'     , 10);
define('TBL_MONTH_RETURNEDRECS', 11);
define('TBL_MONTH_SOURCE_IP'   , 12);
define('TBL_MONTH_SOURCE_HOST' , 13);

//For search requests
define('TBL_MONTH_SCHEMA',  14);
define('TBL_MONTH_WHERE' ,  15);

//For inventory requests
define('TBL_MONTH_WHERE_INV' , 14);
define('TBL_MONTH_COLUMN_INV', 15);


define('TBL_MONTH_REQUEST', 16);

class TpStatistics
{
    function LogSchemaInfo( &$rStatsDb, &$params, $currentMonth, $currentYear )
    {
        $operation = $params['operation'];
        
        // We only track certain operations
        if ( $operation == 'inventory' or $operation == 'search' or $operation == 'custom' )
        {
            $do_nothing_special = true;
        }
        else
        {
            return;
        }

    
        if ( $rStatsDb )
        {

            if ( array_key_exists( 'output_model', $params ) && TpUtils::IsUrl( $params['output_model'] ) )
            {
                $recstr = $params['output_model'];
            }
            elseif ( $params['operation'] == 'inventory' )
            {
                $recstr = 'inventory';
            }
            else
            {
                $recstr = 'custom';
            }
            $whereClause = new AndWhereClause();
            $whereClause->add( new SimpleWhereClause( TBL_MONTH, '=', $currentMonth ) );
            $whereClause->add( new SimpleWhereClause( TBL_YEAR, '=', $currentYear ) );
            $whereClause->add( new SimpleWhereClause( TBL_SCHEMA_SCHEMA, '=', $recstr ) );
            $whereClause->add( new SimpleWhereClause( TBL_SCHEMA_RESOURCE, '=', $params['resource']) );
            $row = $rStatsDb->selectWhere( TP_STATISTICS_SCHEMA_TABLE, $whereClause );

            if ( count( $row ) > 0 )
            {
                $newMatches = $row[0][ TBL_SCHEMA_MATCHES ] + $params['returned'];
                $newHits = $row[0][ TBL_SCHEMA_HITS ] + 1;
                $rStatsDb->updateSetWhere( TP_STATISTICS_SCHEMA_TABLE, array( TBL_SCHEMA_MATCHES => $newMatches, TBL_SCHEMA_HITS => $newHits ), $whereClause );
            }
            else
            {
                $row = array( TBL_MONTH => $currentMonth, TBL_YEAR => $currentYear, TBL_SCHEMA_RESOURCE => $params['resource'], TBL_SCHEMA_SCHEMA => $recstr, TBL_SCHEMA_HITS =>1, TBL_SCHEMA_MATCHES => $params['returned'] );
                $rStatsDb->insert( TP_STATISTICS_SCHEMA_TABLE, $row );
            }
        }

    } // end of LogSchemaInfo

    function LogResourceInfo( &$rStatsLog, &$rStatsDb, &$params, $currentMonth, $currentYear )
    {
        $operation = $params['operation'];
        
        // We only track certain operations
        if ( $operation == 'inventory' or $operation == 'search' or $operation == 'custom' )
        {
            $do_nothing_special = true;
        }
        else
        {
            return;
        }

        
        $log_str = TpServiceUtils::GetLogString( $params );

        $rStatsLog->log( $log_str, PEAR_LOG_INFO );

        if ( $rStatsDb )
        {
            $whereClause = new AndWhereClause();
            $whereClause->add( new SimpleWhereClause( TBL_MONTH, '=', $currentMonth ) );
            $whereClause->add( new SimpleWhereClause( TBL_YEAR, '=', $currentYear ) );
            $whereClause->add( new SimpleWhereClause( TBL_RESOURCE, '=', $params['resource']) );
            $row = $rStatsDb->selectWhere( TP_STATISTICS_RESOURCE_TABLE, $whereClause );

            if ( count( $row ) > 0)
            {
                $newMatches = $row[0][ TBL_MATCHES ] + $params['returned'];
                $newHits = $row[0][ TBL_HITS ] + 1;
                $newZeroMatches = $row[0][ TBL_0_MATCHES ];

                if ( $params['returned'] == 0 )
                {
                    $newZeroMatches++;
                }

                $rStatsDb->updateSetWhere( TP_STATISTICS_RESOURCE_TABLE, array( TBL_MATCHES => $newMatches, TBL_HITS => $newHits, TBL_0_MATCHES => $newZeroMatches), $whereClause );
            }
            else
            {
                $zeroMatches = 0;

                if ( $params['returned'] == 0 )
                {
                    $zeroMatches = 1;
                }

                $row = array( TBL_MONTH => $currentMonth, TBL_YEAR => $currentYear, TBL_RESOURCE => $params['resource'], TBL_DAYS => 0, TBL_HITS =>1, TBL_MATCHES => $params['returned'], TBL_0_MATCHES => $zeroMatches );

                $rStatsDb->insert( TP_STATISTICS_RESOURCE_TABLE, $row );
            }
        }

    } // end of LogResourceInfo

} // end of class TpStatistics
?>