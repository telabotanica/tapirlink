<?php
/**
 * $Id: TpRelatedEntity.php 38 2007-01-10 00:23:09Z rdg $
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

require_once('TpBusinessObject.php');
require_once('TpEntity.php');
require_once('TpUtils.php');
require_once('TpDiagnostics.php');

class TpRelatedEntity extends TpBusinessObject
{
    var $mRoles;
    var $mEntity;

    function TpRelatedEntity( ) 
    {
        $this->mRoles = array();

    } // end of member function TpRelatedEntity

    function &GetEntity( ) 
    {
        return $this->mEntity;

    } // end of member function GetEntity

    function SetEntity( $entity ) 
    {
        $this->mEntity = $entity;

    } // end of member function SetEntity

    function GetRoles( ) 
    {
        return $this->mRoles;

    } // end of member function GetRoles

    function SetRoles( $roles ) 
    {
        $this->mRoles = $roles;

    } // end of member function SetRoles

    function AddRole( $role ) 
    {
        array_push( $this->mRoles, $role );

    } // end of member function AddRole

    function Validate( $raiseErrors=true, $defaultLang=null ) 
    {
        $ret_val = true;

        // At least one role
        if ( count( $this->mRoles ) == 0 ) 
        {
            if ( $raiseErrors )
            {
                $error = 'At least one of the related entities was '.
                             'not associated with any role!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        if ( $this->mEntity == null ) 
        {
            if ( $raiseErrors )
            {
                $error = 'Resource has no entity specified!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }
        else 
        {
            if ( ! $this->mEntity->Validate( $raiseErrors, $defaultLang ) ) 
            {
                $ret_val = false;
            }
        }

        return $ret_val;

    } // end of member function Validate

    function GetXml( $offset='', $indentWith='' ) 
    {
        $indent = $offset.$indentWith;

        $xml = TpUtils::OpenTag( '', 'relatedEntity', $offset );

        foreach ( $this->mRoles as $role ) 
        {
            $xml .= TpUtils::MakeTag( '', 'role', $role, $indent );
        }

        $xml .= $this->mEntity->GetXml( $indent, $indentWith );

        $xml .= TpUtils::CloseTag( '', 'relatedEntity', $offset );

        return $xml;

    } // end of member function GetXml

} // end of TpRelatedEntity
?>
