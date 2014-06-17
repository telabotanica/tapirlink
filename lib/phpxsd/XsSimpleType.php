<?php
/**
 * $Id: XsSimpleType.php 566 2008-03-08 11:14:22Z rdg $
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
 * ACKNOWLEDGEMENTS
 * 
 * This class has been largely based on the API documentation of 
 * xsom (https://xsom.dev.java.net/) written by Kohsuke Kawaguchi.
 */

require_once(dirname(__FILE__).'/XsType.php');

class XsSimpleType extends XsType
{
    function XsSimpleType( $name, $targetNamespace, $isGlobal ) 
    {
        parent::XsType( $name, $targetNamespace, $isGlobal );

        $this->mIsSimple = true;

    } // end of member function XsSimpleType

    function GetUri( ) 
    {
        return $this->GetTargetNamespace() . '#' . $this->GetName();
        
    } // end of member function GetUri

} // end of XsSimpleType
?>