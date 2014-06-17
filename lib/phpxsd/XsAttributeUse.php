<?php
/**
 * $Id: XsAttributeUse.php 646 2008-04-23 17:05:36Z rdg $
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

class XsAttributeUse
{
    var $mAttributeDecl;
    var $mUse;
    var $mFixedValue;
    var $mDefaultValue;

    function XsAttributeUse( $xsAttributeDecl ) 
    {
        $this->mAttributeDecl = $xsAttributeDecl;

    } // end of member function XsAttributeUse

    function GetTargetNamespace( ) 
    {
        return $this->mAttributeDecl->GetTargetNamespace();
        
    } // end of member function GetTargetNamespace

    function SetType( $simpleType ) 
    {
        $this->mAttributeDecl->SetType( $simpleType );
        
    } // end of member function SetType

    function GetType( ) 
    {
        return $this->mAttributeDecl->GetType();
        
    } // end of member function GetType

    function SetUse( $use ) 
    {
        $this->mUse = $use;
        
    } // end of member function SetUse

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
        if ( isset( $this->mDefaultValue ) )
        {
            return true;
        }

        return $this->mAttributeDecl->HasDefaultValue();
        
    } // end of member function HasDefaultValue

    function GetDefaultValue( ) 
    {
        if ( isset( $this->mDefaultValue ) )
        {
            return $this->mDefaultValue;
        }

        if ( $this->mAttributeDecl->HasDefaultValue() )
        {
            return $this->mAttributeDecl->GetDefaultValue();
        }

        return null;
        
    } // end of member function GetDefaultValue

    function HasFixedValue( ) 
    {
        if ( isset( $this->mFixedValue ) )
        {
            return true;
        }

        return $this->mAttributeDecl->HasFixedValue();
        
    } // end of member function HasFixedValue

    function GetFixedValue( ) 
    {
        if ( isset( $this->mFixedValue ) )
        {
            return $this->mFixedValue;
        }

        if ( $this->mAttributeDecl->HasFixedValue() )
        {
            return $this->mAttributeDecl->GetFixedValue();
        }

        return null;

    } // end of member function GetFixedValue

    function &GetDecl( ) 
    {
        return $this->mAttributeDecl;
        
    } // end of member function GetDecl

    function IsRequired( ) 
    {
        if ( $this->mUse == 'required' )
        {
            return true;
        }

        return false;
        
    } // end of member function IsRequired

    function SetProperties( $attrs ) 
    {
        if ( isset( $attrs['ref'] ) )
        {
            $this->mAttributeDecl->SetRef( $attrs['ref'] );
        }

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

        if ( isset( $attrs['use'] ) )
        {
            $this->SetUse( $attrs['use'] );
        }

    } // end of member function SetProperties

    function Accept( &$visitor, $path ) 
    {
        return $visitor->VisitAttributeUse( $this, $path );
        
    } // end of member function Accept

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array( 'mAttributeDecl', 'mUse', 'mFixedValue', 'mDefaultValue' );

    } // end of member function __sleep

} // end of XsAttributeUse
?>