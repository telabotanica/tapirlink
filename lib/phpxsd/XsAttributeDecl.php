<?php
/**
 * $Id: XsAttributeDecl.php 559 2008-02-27 22:22:41Z rdg $
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

class XsAttributeDecl extends XsDeclaration
{
    var $mFixedValue;
    var $mDefaultValue;
    var $mSimpleType;

    function XsAttributeDecl( $name, $targetNamespace, $isGlobal ) 
    {
        parent::XsDeclaration( $name, $targetNamespace, $isGlobal );

    } // end of member function XsAttributeDecl

    function SetType( $simpleType ) 
    {
        if ( is_object( $this->mrRefObj ) )
        {
            $this->mrRefObj->SetType( $simpleType );
        }

        $this->mSimpleType = $simpleType;
        
    } // end of member function SetType

    function SetDefaultValue( $defaultValue ) 
    {
        $this->mDefaultValue = $defaultValue;
        
    } // end of member function SetDefaultValue

    function SetFixedValue( $fixedValue ) 
    {
        $this->mFixedValue = $fixedValue;
        
    } // end of member function SetFixedValue

    function HasDefaultValue( ) 
    {
        if ( is_object( $this->mrRefObj ) )
        {
            return $this->mrRefObj->HasDefaultValue();
        }

        return isset( $this->mDefaultValue );
        
    } // end of member function HasDefaultValue

    function GetDefaultValue( ) 
    {
        if ( is_object( $this->mrRefObj ) )
        {
            return $this->mrRefObj->GetDefaultValue();
        }

        return $this->mDefaultValue;
        
    } // end of member function GetDefaultValue

    function HasFixedValue( ) 
    {
        if ( is_object( $this->mrRefObj ) )
        {
            return $this->mrRefObj->HasFixedValue();
        }

        return isset( $this->mFixedValue );
        
    } // end of member function HasFixedValue

    function GetFixedValue( ) 
    {
        if ( is_object( $this->mrRefObj ) )
        {
            return $this->mrRefObj->GetFixedValue();
        }

        return $this->mFixedValue;
        
    } // end of member function GetFixedValue

    function GetType( ) 
    {
        if ( is_object( $this->mrRefObj ) )
        {
            return $this->mrRefObj->GetType();
        }

        return $this->mSimpleType;
        
    } // end of member function GetType

    function SetProperties( $attrs ) 
    {
        if ( isset( $attrs['type'] ) )
        {
            $this->SetType( $attrs['type'] );
        }

        if ( isset( $attrs['fixed'] ) )
        {
            $this->SetFixedValue( $attrs['fixed'] );
        }
        else if ( isset( $attrs['default'] ) )
        {
            $this->SetDefaultValue( $attrs['default'] );
        }

    } // end of member function SetProperties

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array_merge( parent::__sleep(), 
                            array( 'mFixedValue', 'mDefaultValue', 'mSimpleType' ) );

    } // end of member function __sleep

} // end of XsAttributeDecl
?>