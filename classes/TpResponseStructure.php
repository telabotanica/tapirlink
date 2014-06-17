<?php
/**
 * $Id: TpResponseStructure.php 575 2008-03-28 20:52:13Z rdg $
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
 *
 */

require_once('TpResources.php');
require_once('phpxsd/XsSchemaParser.php');
require_once('pear/Cache.php');

class TpResponseStructure extends XsSchemaParser
{
    var $mRevision = '$Revision: 575 $';
    var $mLoadedFromCache = true;
    var $mRejectedPaths = array();
    var $mAcceptedPaths = array();

    function TpResponseStructure( )
    {
        // Call parent constructor
        parent::XsSchemaParser();

        // Constructor will only be called when the response structure  
        // is not loaded from cache
        $this->mLoadedFromCache = false;

    } // end of member function TpResponseStructure

    function GetRevision( )
    {
        $revision_regexp = '/^\$'.'Revision:\s(\d+)\s\$$/';

        if ( preg_match( $revision_regexp, $this->mRevision, $matches ) )
        {
            return (int)$matches[1];
        }

        return null;

    } // end of member function GetRevision

    function LoadedFromCache( ) 
    {
        return $this->mLoadedFromCache;

    } // end of member function LoadedFromCache

    function Cache( ) 
    {
        global $g_dlog;

        if ( TP_USE_CACHE and TP_RESP_STRUCTURE_CACHE_LIFE_SECS )
        {
            $r_resources =& TpResources::GetInstance();

            $cache_dir = TP_CACHE_DIR . '/' . $r_resources->GetCurrentResourceCode();

            $cache_options = array( 'cache_dir' => $cache_dir );

            $subdir = 'structures';

            $cache = new Cache( 'file', $cache_options );

            $location = $this->GetLocation();

            if ( empty( $location ) )
            {
                return;
            }

            $cache_id = $cache->generateID( $location );

            if ( ( ! $cache->isCached( $cache_id, $subdir ) ) or 
                 (  $cache->isExpired( $cache_id, $subdir ) ) )
            {
                $cache_expires = TP_RESP_STRUCTURE_CACHE_LIFE_SECS;
                $cached_data = serialize( $this );

                $cache->save( $cache_id, $cached_data, $cache_expires, $subdir );

                $g_dlog->debug( 'Caching response structure with id generated from "'.
                                $location.'"' );
            }
        }

    } // end of member function Cache

    function SetRejectedPaths( $rejectedPaths ) 
    {
        $this->mRejectedPaths = $rejectedPaths;

    } // end of member function SetRejectedPaths

    function SetAcceptedPaths( $acceptedPaths ) 
    {
        $this->mAcceptedPaths = $acceptedPaths;

    } // end of member function SetAcceptedPaths

    function GetRejectedPaths( ) 
    {
        return $this->mRejectedPaths;

    } // end of member function GetRejectedPaths

    function GetAcceptedPaths( ) 
    {
        return $this->mAcceptedPaths;

    } // end of member function GetAcceptedPaths

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array_merge( parent::__sleep(), 
                            array( 'mRevision', 'mRejectedPaths', 'mAcceptedPaths' ) );

    } // end of member function __sleep

} // end of TpResponseStructure
?>