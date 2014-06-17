<?php
/**
 * $Id: TpXmlNamespace.php 6 2007-01-06 01:38:13Z rdg $
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

class TpXmlNamespace 
{
    var $mNamespace;
    var $mPrefix;
    var $mSchemaLocation;

    function TpXmlNamespace( $namespace, $prefix, $schemaLocation ) 
    {
        $this->mNamespace      = $namespace;
        $this->mPrefix         = $prefix;
        $this->mSchemaLocation = $schemaLocation;

    } // end of member function TpXmlNamespace

    function GetNamespace( ) 
    {
        return $this->mNamespace;
        
    } // end of member function GetNamespace

    function GetPrefix( ) 
    {
        return $this->mPrefix;
        
    } // end of member function GetPrefix

    function GetSchemaLocation( ) 
    {
        return $this->mSchemaLocation;
        
    } // end of member function GetSchemaLocation

} // end of TpXmlNamespace
?>
