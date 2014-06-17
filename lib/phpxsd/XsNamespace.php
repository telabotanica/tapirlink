<?php
/**
 * $Id: XsNamespace.php 597 2008-04-04 23:37:02Z rdg $
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

require_once(dirname(__FILE__).'/XsSchema.php');

class XsNamespace
{
    var $mPrefix = '';
    var $mUri;
    var $mFlags = array(); // flags are used during XML parsing just to help detecting
                           // the element where the namespace has been declared
    var $mSchemas = array(); // location => TpXmlSchema obj
    var $mFirstSchemaLocation; // location of the first schema

    function XsNamespace( $prefix, $uri, $flag=null )
    {
        $this->mPrefix = $prefix;
        $this->mUri = $uri;

        if ( ! is_null( $flag ) )
        {
            $this->mFlags[0] = $flag;
        }

    } // end of member function XsNamespace

    function GetUri( )
    {
        return $this->mUri;

    } // end of member function GetUri

    function GetPrefix( )
    {
        return $this->mPrefix;

    } // end of member function GetPrefix

    function HasFlag( $flag )
    {
        return in_array( $flag, $this->mFlags );

    } // end of member function HasFlag

    function RemoveFlag( $flag )
    {
        unset( $this->mFlags[$flag] );

    } // end of member function RemoveFlag

    function PushSchema( $location, &$rParser )
    {
        if ( ! count( $this->mSchemas ) )
        {
            $this->mFirstLocation = $location;
        }

        if ( ! isset( $this->mSchemas[$location] ) )
        {
            $this->mSchemas[$location] = new XsSchema( $this->mUri, $location, $rParser );
        }

    } // end of member function PushSchema

    function AddSchema( &$rSchema )
    {
        $location = $rSchema->GetLocation();

        if ( ! count( $this->mSchemas ) )
        {
            $this->mFirstLocation = $location;
        }

        if ( ! isset( $this->mSchemas[$location] ) )
        {
            $this->mSchemas[$location] =& $rSchema;
        }

    } // end of member function AddSchema

    function GetFirstLocation( )
    {
        return $this->mFirstLocation;

    } // end of member function GetFirstLocation

    function GetLocations( )
    {
        return array_keys( $this->mSchemas );

    } // end of member function GetLocations

    function HasSchema( $location )
    {
        return isset( $this->mSchemas[$location] );

    } // end of member function HasSchema

    function &GetSchema( $location )
    {
        $r_schema = null;

        if ( isset( $this->mSchemas[$location] ) )
        {
            $r_schema =& $this->mSchemas[$location];
        }

        return $r_schema;

    } // end of member function GetSchema

    function AddElementDecl( $schema, &$rElementDecl )
    {
        $this->mSchemas[$schema]->AddElementDecl( $rElementDecl );

    } // end of member function AddElementDecl

    function AddAttributeDecl( $schema, &$rAttributeDecl )
    {
        $this->mSchemas[$schema]->AddAttributeDecl( $rAttributeDecl );

    } // end of member function AddAttributeDecl

    function AddType( $schema, &$rType )
    {
        $this->mSchemas[$schema]->AddType( $rType );

    } // end of member function AddType

    function GetElementDecls( ) 
    {
        $ret = array();

        foreach ( $this->mSchemas as $loc => $schema )
        {
            $ret = array_merge( $ret, $schema->GetElementDecls() );
        }

        return $ret;

    } // end of member function GetElementDecls

    function GetAttributeDecls( ) 
    {
        $ret = array();

        foreach ( $this->mSchemas as $loc => $schema )
        {
            $ret = array_merge( $ret, $schema->GetAttributeDecls() );
        }

        return $ret;

    } // end of member function GetAttributeDecls

    function &GetElementDecl( $name ) 
    {
        $null_object = null;

        foreach ( $this->mSchemas as $loc => $schema )
        {
            $r_el =& $schema->GetElementDecl( $name );

            if ( ! is_null( $r_el ) )
            {
                return $r_el;
            }
        }

        return $null_object;

    } // end of member function GetElementDecl

    function &GetAttributeDecl( $name ) 
    {
        $null_object = null;
        
        foreach ( $this->mSchemas as $loc => $schema )
        {
            $r_attr =& $schema->GetAttributeDecl( $name );

            if ( ! is_null( $r_attr ) )
            {
                return $r_attr;
            }
        }

        return $null_object;

    } // end of member function GetAttributeDecl

    function &GetType( $name ) 
    {
        $r_type = null;

        foreach ( $this->mSchemas as $loc => $schema )
        {
            $r_type =& $this->mSchemas[$loc]->GetType( $name );

            if ( ! is_null( $r_type ) )
            {
                return $r_type;
            }
        }

        return $r_type;

    } // end of member function GetType

} // end of XsNamespace
?>