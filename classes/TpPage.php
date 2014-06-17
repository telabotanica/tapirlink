<?php
/**
 * $Id: TpPage.php 536 2008-02-20 12:02:08Z rdg $
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

require_once('TpUtils.php');

class TpPage
{
    var $mMessage;

    function TpPage( )
    {

    } // end of member function TpPage

    function SetMessage( $msg )
    {
        $this->mMessage = $msg;

    } // end of member function SetMessage

    function DisplayHtml( )
    {
        if ( $this->mMessage ) 
        {
            printf( "\n<center><span class=\"msg\">%s</span></center>", 
                    nl2br( $this->mMessage ) );
        }
        else
        {
            print('<!-- Abstract page with no message -->');
        }

    } // end of member function DisplayHtml

    function GetJavascript( )
    {
        // Can be overwritten by subclasses to include specific javascript code
        return '';

    } // end of member function GetJavascript

    function GetScroll( )
    {
        return TpUtils::GetVar( 'scroll', '');

    } // end of member function GetScroll

} // end of TpPage
?>