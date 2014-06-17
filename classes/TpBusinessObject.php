<?php
/**
 * $Id: TpBusinessObject.php 6 2007-01-06 01:38:13Z rdg $
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

require_once('TpLangString.php');
require_once('TpUtils.php');

class TpBusinessObject 
{
    function TpBusinessObject( ) 
    {

    } // end of member function TpBusinessObject

    function LoadLangElementFromSession( $id, &$rProperty ) 
    {
        $cnt = 1;

        while ( isset( $_REQUEST[$id.'_'.$cnt] ) && $cnt < 31 ) 
        {
            $value = $_REQUEST[$id.'_'.$cnt];
            $lang = TpUtils::GetVar( $id.'_lang_'.$cnt, '' );

            if ( ! isset( $_REQUEST['del_'.$id.'_'.$cnt] ) ) 
            {
                array_push( $rProperty, new TpLangString( $value, $lang ) );
            }
            ++$cnt;
        }
        if ( isset( $_REQUEST['add_'.$id] ) ) 
        {
            array_push( $rProperty, new TpLangString( '', '' ) );
        }

    } // end of member function LoadLangElement

} // end of TpBusinessObject
?>
