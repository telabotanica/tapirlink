<?php
/**
 * $Id: help.php 9 2007-01-06 15:50:46Z rdg $
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

$name = ( isset( $_REQUEST['name'] ) ) ? nl2br( urldecode( $_REQUEST['name'] ) ) : '';

$doc = ( isset( $_REQUEST['doc'] ) ) ? urldecode( $_REQUEST['doc'] ) : '';

if ( get_magic_quotes_gpc() )
{
  $name = stripslashes( $name );
  $doc = stripslashes( $doc );
}

include( '../templates/help.tmpl.php' );

?>