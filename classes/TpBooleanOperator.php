<?php
/**
 * $Id: TpBooleanOperator.php 264 2007-02-22 23:46:21Z rdg $
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
 */

require_once('TpFilter.php');

class TpBooleanOperator
{
    var $mBooleanType; // Type of boolean operator (see constants defined in TpFilter.php)

    function TpBooleanOperator( $type )
    {
        $this->mBooleanType = $type;

    } // end of member function TpBooleanOperator

    function GetBooleanType( )
    {
        return $this->mBooleanType;

    } // end of member function GetBooleanType

    function GetSql( &$rResource )
    {
        // Must be overwritten by subclasses!!

    } // end of member function GetSql

    function GetLogRepresentation( )
    {
        // Must be overwritten by subclasses!!

    } // end of member function GetLogRepresentation

    function GetXml( )
    {
        // Must be overwritten by subclasses!!

    } // end of member function GetXml

    function IsValid( )
    {
        // Must be overwritten by subclasses!!
        return true;

    } // end of member function IsValid

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array( 'mBooleanType' );

    } // end of member function __sleep

} // end of TpBooleanOperator
?>