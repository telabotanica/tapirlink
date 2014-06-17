<?php
/**
 * $Id: TpLangString.php 6 2007-01-06 01:38:13Z rdg $
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

class TpLangString 
{
    var $mValue;
    var $mLang;

    function TpLangString( $value, $lang ) 
    {
        $this->mValue = $value;
        $this->mLang  = $lang;

    } // end of member function TpLangString

    function GetValue( ) 
    {
        return $this->mValue;
        
    } // end of member function GetValue

    function GetLang( ) 
    {
        return $this->mLang;
        
    } // end of member function GetLang

} // end of TpLangString
?>
