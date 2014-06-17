<?php
/**
 * $Id: TpStatisticLogger.php 6 2007-01-06 01:38:13Z csw $
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
 * @author Craig Wieczorek <csw [at] ponyshow . com>
 */


class TpStatisticsLogger
{
    var $mData;
    var $mLevel;
    var $mRequestParams;
    var $mType;

    function TpStaticticsLogger( $parameters )
    {
        $this->Initialize( $parameters );
    }

    function BaseStruct( $method, $request='', $level=PEAR_LOG_INFO )
    {
        $this->mLevel = $level;
        $this->mRequestParams = $request;
        $this->mData = array();
        $this->mData['method'] = $method;
        $this->mData['status'] = false;
        $this->mType = $method;
    }

    function Initialize( $parameters ) 
    {
        if( array_key_exists( 'operation', $parameters ) && array_search( $parameters['operation'], array( 'impossible_key', 'search','inventory', 'metadata', 'custom' ) ) )
        {

            //need to look for custom here
            $operation = $parameters[ 'operation' ];
            $resource = $parameters[ 'resource' ];
            $filter = $parameters[ 'filter' ];
            $start = $parameters[ 'start' ];
            $max = $parameters[ 'limit' ];
            $returned = $parameters[ 'returned' ];
            $rec_str = $parameters[ 'template' ];
            $source_ip = $parameters[ 'source_ip' ];
            //need to do a DNS lookup
            $source_host = gethostbyaddr( $source_ip ); 
            $sql = $parameters[ 'sql' ];
            //need to rip sql apart to find column
            $tmp_pos = strrpos( $sql, ' '  );
            $str_len = strlen( $sql );
            $column = substr( $sql, $tmp_pos, $str_len );
            switch( $operation )
            {
                case 'search'    : $this->InitializeSearch( $resource, $filter, $start, $max, $returned, $rec_str, $source_ip, $source_host, $sql ); break;
                //case 'custom'    : $this->InitializeCustom( $resource, $filter, $start, $max, $returned, $rec_str, $source_ip, $source_host, $sql ); break;
                case 'inventory' : $this->InitializeInventory( $resource, $filter, $start, $max, $returned, $column, $source_ip, $source_host, $sql ); break;
                case 'metadata'  : $this->InitializeMetadata( $source_ip, $source_host ); break;
            }
        }
        else
        {
            //FIXME
        }
    
    }

    function InitializeMetadata( $sourceIp, $sourceHost, $request='', $level=PEAR_LOG_INFO )
    {
        $this->BaseStruct('metadata', $request, $level );
        $this->mData['status'] = true;
        $this->mData['source_ip'] = $sourceIp;
        $this->mData['source_host'] = $sourceHost;
    }


    function InitializeSearch( $resource, $strFilter, $startRec, $maxRecs, $returnedRecs, $recStr, $sourceIp, $sourceHost, $whereClause, $request='', $level=PEAR_LOG_INFO )
    {
        $this->BaseStruct( 'search', $request, $level );
        $this->mData['resource'] = $resource;
        $this->mData['filter'] = $strFilter;
        $this->mData['startrec'] = $startRec;
        $this->mData['maxrecs'] = $maxRecs;
        $this->mData['returnedrecs'] = $returnedRecs;
        $this->mData['source_ip'] = $sourceIp;
        $this->mData['source_host'] = $sourceHost;

        if( TpUtils::isUrl( $recStr ) )
        {
            $this->mData['recstr'] = $recStr;
        }
        else
        {
            $this->mData['recstr'] = 'custom';
            $this->mType = 'custom';
        }
        $this->mData['whereclause'] = $whereClause;
    }

    function InitializeInventory( $resource, $strFilter, $startRec, $maxRecs, $returnedRecs, $column, $sourceIp, $sourceHost, $whereClause, $request='', $level=PEAR_LOG_INFO )
    {
        $this->BaseStruct('inventory', $request, $level );
        $this->mData['resource'] = $resource;
        $this->mData['filter'] = $strFilter;
        $this->mData['startrec'] = $startRec;
        $this->mData['maxrecs'] = $maxRecs;
        $this->mData['returnedrecs'] = $returnedRecs;
        $this->mData['source_ip'] = $sourceIp;
        $this->mData['source_host'] = $sourceHost;
        $this->mData['whereclause'] = $whereClause;
        $this->mData['column'] = $column;
        //$this->mData['request'] = $request;
    }

    function _makeValueLogFormat( $value )
    {
        $return_value = '';
        
        if( is_numeric( $value ) )
        {
            $return_value = "$value";
        }
        elseif( is_bool( $value ) )
        {
            if( $value == false )
            {
                $return_value = 'false' ;
            }                
            else                    
            {
                $return_value = 'true' ;
            }
        }
        elseif( $value == null )
        {
            $return_value = 'NULL';
        }
        else
        {
            $return_value = str_replace( "\n", '', str_replace( "\t", '', $value ) );
        }
        
        return $return_value;
    }
    
    function _makeLogKeyValue( $key, $value, $spacer="\t" )
    {
        return "$spacer$key=" . $this->_makeValueLogFormat( $value );
    }
    
    function _getExtraInfo()
    {
        $return_value='';

        if ( is_array( $this->mData ) )
        {
            $data_keys = array_keys( $this->mData );

            foreach ( $data_keys as $key )
            {
                if ( $key != 'method' && $key != 'status' )
                {
                    $return_value .= $this->_makeLogKeyValue( $key, $this->mData[$key] );
                }
            }
        }
        return $return_value;
    }
    
    function WriteLog( &$logger )
    {
        $logger->log('data='.str_replace("\n",' ', serialize($this->mData) ), $this->mLevel);
    }
    
    function WriteRequestResult( &$logger )
    {
       $extra_info = $this->_getExtraInfo();
       
       $var = $this->_makeLogKeyValue( 'type', $this->mType, '' ) . $this->_makeLogKeyValue( 'status', $this->mData['status'] ) . $this->_makeLogKeyValue( 'method', $this->mData['method'] ) . $extra_info . $this->_makeLogKeyValue( 'request', $this->mRequestParams );

       $logger->log( $var, $this->mLevel );
    }
}
?>
