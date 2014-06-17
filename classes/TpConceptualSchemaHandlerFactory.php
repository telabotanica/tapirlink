<?php
/**
 * $Id: TpConceptualSchemaHandlerFactory.php 569 2008-03-27 15:27:57Z rdg $
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

require_once('DarwinSchemaHandler_v1.php');
require_once('DarwinSchemaHandler_v2.php');
require_once('CnsSchemaHandler_v1.php');
require_once('CnsSchemaHandler_v2.php');

class TpConceptualSchemaHandlerFactory
{

    static function GetInstance( $id ) 
    {
        if ( $id == 'DarwinSchemaHandler_v1') 
        {
            return new DarwinSchemaHandler_v1();
        }
        else if ( $id == 'DarwinSchemaHandler_v2') 
        {
            return new DarwinSchemaHandler_v2();
        }
        else if ( $id == 'CnsSchemaHandler_v1') 
        {
            return new CnsSchemaHandler_v1();
        }
        else if ( $id == 'CnsSchemaHandler_v2') 
        {
            return new CnsSchemaHandler_v2();
        }
        return null;

    } // end of member function GetInstance

} // end of TpConceptualSchemaHandlerFactory
?>