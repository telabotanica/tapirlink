<?php
/**
 * $Id: XsSchema.php 597 2008-04-04 23:37:02Z rdg $
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

require_once(dirname(__FILE__).'/XsElementDecl.php');
require_once(dirname(__FILE__).'/XsAttributeDecl.php');
require_once(dirname(__FILE__).'/XsAttributeUse.php');
require_once(dirname(__FILE__).'/XsComplexType.php');
require_once(dirname(__FILE__).'/XsSimpleType.php');
require_once(dirname(__FILE__).'/XsModelGroup.php');

class XsSchema
{
    var $mNamespace;
    var $mLocation;
    var $mrParser;
    var $mElementDecls   = array(); // name => XsElementDecl obj
    var $mAttributeDecls = array(); // name => XsAttributeDecl obj
    var $mTypes          = array(); // name => XsType obj

    function XsSchema( $namespace, $location, &$rParser ) 
    {
        $this->mNamespace =  $namespace;
        $this->mLocation  =  $location;
        $this->mrParser   =& $rParser;

    } // end of member function XsSchema

    function GetNamespace( ) 
    {
        return $this->mNamespace;
        
    } // end of member function GetNamespace

    function GetLocation( ) 
    {
        return $this->mLocation;
        
    } // end of member function GetLocation

    function GetPrefix( ) 
    {
        return $this->mPrefix;
        
    } // end of member function GetPrefix

    function &GetParser( ) 
    {
        return $this->mrParser;
        
    } // end of member function GetParser

    function AddElementDecl( &$rElementDecl )
    {
        $name = $rElementDecl->GetName();

        $this->mElementDecls[$name] =& $rElementDecl;

    } // end of member function AddElementDecl

    function AddAttributeDecl( &$rAttributeDecl )
    {
        $name = $rAttributeDecl->GetName();

        $this->mAttributeDecls[$name] =& $rAttributeDecl;

    } // end of member function AddAttributeDecl

    function AddType( &$rType )
    {
        $name = $rType->GetName();

        $this->mTypes[$name] =& $rType;

    } // end of member function AddType

    function &GetElementDecls( ) 
    {
        return $this->mElementDecls;

    } // end of member function GetElementDecls

    function &GetAttributeDecls( ) 
    {
        return $this->mAttributeDecls;

    } // end of member function GetAttributeDecls

    function &GetElementDecl( $name ) 
    {
        $null_object = null;
        
        if ( isset( $this->mElementDecls[$name] ) )
        {
            return $this->mElementDecls[$name];
        }

        return $null_object;

    } // end of member function GetElementDecl

    function &GetAttributeDecl( $name ) 
    {
        $null_object = null;
        
        if ( isset( $this->mAttributeDecls[$name] ) )
        {
            return $this->mAttributeDecls[$name];
        }

        return $null_object;

    } // end of member function GetAttributeDecl

    function &GetType( $name ) 
    {
        $r_type = null;

        if ( isset( $this->mTypes[$name] ) )
        {
            $r_type =& $this->mTypes[$name];
        }

        return $r_type;

    } // end of member function GetType

} // end of TpXmlSchema
?>
