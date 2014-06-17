<?php
/**
 * $Id: TpConceptualSchemaHandler.php 6 2007-01-06 01:38:13Z rdg $
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

class TpConceptualSchemaHandler
{

    function TpConceptualSchemaHandler( ) 
    {

    } // end of member function TpConceptualSchemaHandler

    function Load( &$conceptualSchema ) 
    {
        // Must be overloaded by subclasses! 

        return false;

    } // end of member function Load

} // end of TpConceptualSchemaHandler
?>