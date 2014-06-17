<?php
/**
 * $Id: XsType.php 559 2008-02-27 22:22:41Z rdg $
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

require_once(dirname(__FILE__).'/XsDeclaration.php');

class XsType extends XsDeclaration
{
    var $mIsSimple;
    var $mDerivationMethod;
    var $mrBaseType;

    function XsType( $name, $targetNamespace, $isGlobal ) 
    {
        parent::XsDeclaration( $name, $targetNamespace, $isGlobal );

    } // end of member function XsType

    function IsComplexType( ) 
    {
        return ! $this->mIsSimple;
        
    } // end of member function IsComplexType

    function IsSimpleType( ) 
    {
        return $this->mIsSimple;
        
    } // end of member function IsSimpleType

    function SetBaseType( &$rBaseType ) 
    {
        $this->mrBaseType =& $rBaseType;
        
    } // end of member function SetBaseType

    function &GetBaseType( ) 
    {
        return $this->mrBaseType;
        
    } // end of member function GetBaseType

    function SetDerivationMethod( $derivationMethod ) 
    {
        $this->mDerivationMethod = $derivationMethod;
        
    } // end of member function SetDerivationMethod

    function GetDerivationMethod( ) 
    {
        return $this->mDerivationMethod;
        
    } // end of member function GetDerivationMethod

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array_merge( parent::__sleep(), 
                            array( 'mIsSimple', 'mrBaseType', 'mDerivationMethod' ) );

    } // end of member function __sleep

} // end of XsType
?>