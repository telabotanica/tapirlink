<?php
/**
 * $Id: TpTable.php 6 2007-01-06 01:38:13Z rdg $
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
 * @author Dave Vieglais (Biodiversity Research Center, University of Kansas)
 * 
 */

require_once('TpDiagnostics.php');

class TpTable
{
    var $mName;    // table name
    var $mKey;     // primary key name
    var $mJoin;    // foreign key name in parent table
    var $mrParent; // reference to parent table
    var $mChildren = array(); // table name => TpTable object

    function TpTable( )
    {

    } // end of member function TpTable

    function SetName( $name ) 
    {
        $this->mName = $name;

    } // end of member function SetName

    function GetName( ) 
    {
        return $this->mName;

    } // end of member function GetName

    function SetKey( $key ) 
    {
        $this->mKey = $key;

    } // end of member function SetKey

    function GetKey( )
    {
        return $this->mKey;

    } // end of member function GetKey

    function SetJoin( $key ) 
    {
        $this->mJoin = $key;

    } // end of member function SetParentKey

    function GetJoin( )
    {
        return $this->mJoin;

    } // end of member function GetJoin

    function SetParent( &$rTable ) 
    {
        $this->mrParent =& $rTable;

    } // end of member function SetParent

    function &GetParent( ) 
    {
        return $this->mrParent;

    } // end of member function GetParent

    function GetParentName( )
    {
        if ( is_object( $this->mrParent ) )
        {
            return $this->mrParent->GetName();
        }

        return null;

    } // end of member function GetParentName

    function AddChild( &$rTable ) 
    {
        $rTable->SetParent( $this );

        $this->mChildren[$rTable->GetName()] =& $rTable;

    } // end of member function AddChild

    function RemoveChild( $tableName ) 
    {
        if ( isset( $this->mChildren[$tableName] ) )
        {
            unset( $this->mChildren[$tableName] );

            return true;
        }
        else
        {
            $msg = 'Could not find relationship between "'.$this->mName.'" and "'.
                   $tableName.'". Failed to remove it.';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $msg, DIAG_ERROR );

            return false;
        }

    } // end of member function AddChild

    function &GetChildren( ) 
    {
        return $this->mChildren;

    } // end of member function GetChildren

    function &GetChild( $tableName ) 
    {
        if ( isset( $this->mChildren[$tableName] ) )
        {
            return $this->mChildren[$tableName];
        }

        $msg = 'Could not find relationship between "'.
               $tableName.'" and "'.$this->mName.'"';
        TpDiagnostics::Append( CFG_INTERNAL_ERROR, $msg, DIAG_ERROR );

        $ref = null;

        return $ref;

    } // end of member function GetChild

    function GetLevel( ) 
    {
        if ( is_object( $this->mrParent ) )
        {
            return $this->mrParent->GetLevel() + 1;
        }

        return 1;

    } // end of member function GetLevel

    function GetPath( ) 
    {
        if ( is_object( $this->mrParent ) )
        {
            return $this->mrParent->GetPath().'/'.$this->mName;
        }

        return $this->mName;

    } // end of member function GetPath

    function GetAllTables( ) 
    {
        $tables = array( $this->mName );

        foreach ( $this->mChildren as $name => $table )
        {
            $tables = array_merge( $tables, $table->GetAllTables() );
        }

        return $tables;

    } // end of member function GetPath

    function &Find( $tableName ) 
    {
        if ( $tableName == $this->mName )
        {
            return $this;
        }

        $ref = null;

        foreach ( $this->mChildren as $name => $table )
        {
            $ref =& $this->mChildren[$name]->Find( $tableName );

            if ( $ref != null )
            {
                break;
            }
        }

        return $ref;

    } // end of member function GetPath

    function GetXml( ) 
    {
        $join = '';

        if ( $this->mJoin != null )
        {
            $join = ' join="'.$this->mJoin.'"';
        }

        $xml = '<table name="'.$this->mName.'" key="'.$this->mKey.'"'.$join.'>';

        foreach ( $this->mChildren as $name => $table )
        {
            $xml .= $table->GetXml();
        }

        $xml .= '</table>';

        return $xml;

    } // end of member function GetXml

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
      return array( 'mName', 'mKey', 'mJoin', 'mrParent', 'mChildren' );

    } // end of member function __sleep

} // end of TpTable
?>