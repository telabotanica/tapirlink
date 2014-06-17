<?php
/**
 * $Id: XsElementDecl.php 559 2008-02-27 22:22:41Z rdg $
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

class XsElementDecl extends XsDeclaration
{
    var $mMinOccurs = 1;
    var $mMaxOccurs = 1;
    var $mFixedValue;
    var $mDefaultValue;
    var $mAbstract = false;
    var $mNillable = false;
    var $mrType;

    function XsElementDecl( $name, $targetNamespace, $isGlobal ) 
    {
        parent::XsDeclaration( $name, $targetNamespace, $isGlobal );

    } // end of member function XsElementDecl

    function SetType( &$rType ) 
    {
        if ( is_object( $this->mrRefObj ) )
        {
            $this->mrRefObj->SetType( $rType );
        }

        $this->mrType =& $rType;
        
    } // end of member function SetType

    function &GetType( ) 
    {
        if ( is_object( $this->mrRefObj ) )
        {
            return $this->mrRefObj->GetType();
        }

        return $this->mrType;
        
    } // end of member function GetType

    function SetMinOccurs( $minOccurs ) 
    {
        $this->mMinOccurs = $minOccurs;
        
    } // end of member function SetMinOccurs

    function SetMaxOccurs( $maxOccurs ) 
    {
        $this->mMaxOccurs = $maxOccurs;
        
    } // end of member function SetMaxOccurs

    function GetMinOccurs( ) 
    {
        return $this->mMinOccurs;
        
    } // end of member function GetMinOccurs

    function GetMaxOccurs( ) 
    {
        return $this->mMaxOccurs;
        
    } // end of member function GetMaxOccurs

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

    function SetAbstract( $abstract ) 
    {
        if ( $abstract == 'true' or $abstract == '1' )
        {
            $this->mAbstract = true;
        }

    } // end of member function SetAbstract

    function SetNillable( $nillable ) 
    {
        if ( $nillable == 'true' or $nillable == '1' )
        {
            $this->mNillable = true;
        }

    } // end of member function SetNillable

    function IsAbstract( ) 
    {
        if ( is_object( $this->mrRefObj ) )
        {
            return $this->mrRefObj->IsAbstract();
        }

        return $this->mAbstract;

    } // end of member function IsAbstract

    function IsNillable( ) 
    {
        if ( is_object( $this->mrRefObj ) )
        {
            return $this->mrRefObj->IsNillable();
        }

        return $this->mNillable;

    } // end of member function IsNillable

    function SetProperties( $attrs ) 
    {
        if ( isset( $attrs['minOccurs'] ) )
        {
            $this->SetMinOccurs( $attrs['minOccurs'] );
        }

        if ( isset( $attrs['maxOccurs'] ) )
        {
            $this->SetMaxOccurs( $attrs['maxOccurs'] );
        }

        if ( isset( $attrs['ref'] ) )
        {
            $this->SetRef( $attrs['ref'] );
        }
        else
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

            if ( isset( $attrs['abstract'] ) )
            {
                $this->SetAbstract( $attrs['abstract'] );
            }

            if ( isset( $attrs['nillable'] ) )
            {
                $this->SetNillable( $attrs['nillable'] );
            }
        }

    } // end of member function SetProperties

    function Accept( &$visitor, $path )
    {
        return $visitor->VisitElementDecl( $this, $path );

    } // end of member function Accept

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array_merge( parent::__sleep(), 
                            array( 'mMinOccurs', 'mMaxOccurs', 'mFixedValue', 
                                   'mDefaultValue', 'mAbstract', 'mNillable', 
                                   'mrType' ) );

    } // end of member function __sleep

} // end of XsElementDecl
?>