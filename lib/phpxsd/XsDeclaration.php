<?php
/**
 * $Id: XsDeclaration.php 559 2008-02-27 22:22:41Z rdg $
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

class XsDeclaration
{
    var $mName;
    var $mTargetNamespace;
    var $mIsGlobal;
    var $mRef;
    var $mrRefObj;

    function XsDeclaration( $name, $targetNamespace, $isGlobal ) 
    {
        $this->mName = $name;
        $this->mTargetNamespace = $targetNamespace;
        $this->mIsGlobal = $isGlobal;

    } // end of member function XsDeclaration

    function GetName( ) 
    {
        if ( is_object( $this->mrRefObj ) )
        {
            return $this->mrRefObj->GetName();
        }

        return $this->mName;
        
    } // end of member function GetName

    function GetTargetNamespace( ) 
    {
        if ( is_object( $this->mrRefObj ) )
        {
            return $this->mrRefObj->GetTargetNamespace();
        }

        return $this->mTargetNamespace;
        
    } // end of member function GetTargetNamespace

    function IsGlobal( ) 
    {
        return $this->mIsGlobal;
        
    } // end of member function IsGlobal

    function IsLocal( ) 
    {
        return ! $this->mIsGlobal;
        
    } // end of member function IsLocal

    function SetRef( $ref ) 
    {
        $this->mRef = $ref;
        
    } // end of member function SetRef

    function GetRef( ) 
    {
        return $this->mRef;
        
    } // end of member function GetRef

    function IsReference( ) 
    {
        return ! empty( $this->mRef );
        
    } // end of member function IsReference

    function SetReferencedObj( &$rReference )
    {
        $this->mrRefObj =& $rReference;
        
    } // end of member function SetReferencedObj

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array( 'mName', 'mTargetNamespace', 'mIsGlobal', 'mRef', 'mrRefObj' );

    } // end of member function __sleep

} // end of XsDeclaration
?>