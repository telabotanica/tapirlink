<?php
/**
 * $Id: TpRelatedContact.php 38 2007-01-10 00:23:09Z rdg $
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
require_once('TpUtils.php');
require_once('TpDiagnostics.php');

class TpRelatedContact extends TpBusinessObject
{
    var $mRoles;
    var $mContact;

    function TpRelatedContact()
    {
        $this->TpBusinessObject();
        $this->mRoles = array();

    } // end of member function TpRelatedContact

    function &GetContact( ) 
    {
        return $this->mContact;

    } // end of member function GetContact

    function SetContact( $contact ) 
    {
        $this->mContact = $contact;

    } // end of member function SetContact

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
            $error = 'At least one of the contacts was '.
                         'not associated with any role!';
            TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            $ret_val = false;
        }

        if ( $this->mContact == null ) 
        {
            $error = 'Entity has no contact specified!';
            TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            $ret_val = false;
        }
        else 
        {
            if ( ! $this->mContact->Validate( $raiseErrors, $defaultLang ) )
            {
                $ret_val = false;
            }
        }

        return $ret_val;

    } // end of member function Validate

    function getXml( $offset='', $indentWith='' ) 
    {
        $indent = $offset.$indentWith;

        $xml = TpUtils::OpenTag( '', 'hasContact', $offset );

        foreach ( $this->mRoles as $role ) 
        {
            $xml .= TpUtils::MakeTag( '', 'role', $role, $indent );
        }

        $xml .= $this->mContact->GetXml( $indent, $indentWith );

        $xml .= TpUtils::CloseTag( '', 'hasContact', $offset );

        return $xml;

    } // end of member function GetXml

} // end of TpRelatedContact
?>
