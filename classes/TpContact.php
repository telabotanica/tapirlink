<?php
/**
 * $Id: TpContact.php 104 2007-01-14 16:05:32Z rdg $
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
require_once('TpLangString.php');
require_once('TpUtils.php');
require_once('TpConfigUtils.php');
require_once('TpDiagnostics.php');

class TpContact extends TpBusinessObject
{
    var $mFullName;
    var $mTitles;
    var $mTelephone;
    var $mEmail;

    function TpContact( ) 
    {
        $this->TpBusinessObject();
        $this->mTitles = array();

    } // end of member function TpContact

    function LoadDefaults( ) 
    {
        $this->AddTitle( '', '' );

    } // end of member function LoadDefaults

    function LoadFromSession( $prefix ) 
    {
        $this->mFullName = TpUtils::GetVar( $prefix.'_fullname', '' );

        $this->LoadLangElementFromSession( $prefix.'_title', $this->mTitles );

        $this->mTelephone = TpUtils::GetVar( $prefix.'_telephone', '' );

        $this->mEmail = TpUtils::GetVar( $prefix.'_email', '' );

    } // end of member function LoadFromSession

    function AddTitle( $title, $lang ) 
    {
        array_push( $this->mTitles, new TpLangString( $title, $lang ) );

    } // end of member function AddTitle

    function GetFullName( ) 
    {
        return $this->mFullName;

    } // end of member function GetFullName

    function SetFullName( $name ) 
    {
        $this->mFullName = $name;

    } // end of member function SetFullName

    function GetTelephone( ) 
    {
        return $this->mTelephone;

    } // end of member function GetTelephone

    function SetTelephone( $tel ) 
    {
        $this->mTelephone = $tel;

    } // end of member function SetTelephone

    function GetEmail( ) 
    {
        return $this->mEmail;

    } // end of member function GetEmail

    function SetEmail( $email ) 
    {
        $this->mEmail = $email;

    } // end of member function SetEmail

    function GetTitles( ) 
    {
        return $this->mTitles;

    } // end of member function GetTitles

    function Validate( $raiseErrors=true, $defaultLang=null ) 
    {
        $ret_val = true;

        // Validate full name
        if ( strlen( $this->mFullName ) == 0 ) 
        {
            if ( $raiseErrors )
            {
                $error = 'At least one of the contacts has no name!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        // Validate email
        if ( strlen( $this->mEmail ) == 0 ) 
        {
            if ( $raiseErrors )
            {
                $error = 'At least one of the contacts has no e-mail!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        // Validate titles
        if ( ! TpConfigUtils::ValidateLangSection( 'Contact Title', 
                                                   $this->mTitles,
                                                   $raiseErrors, false,
                                                   $defaultLang ) ) 
        {
            $ret_val = false;
        }

        return $ret_val;

    } // end of member function validate

    function GetXml( $offset='', $indentWith='') 
    {
        $indent = $offset.$indentWith;

        $xml = TpUtils::OpenTag( TP_VCARD_PREFIX, 'VCARD', $offset );

        $xml .= TpUtils::MakeTag( TP_VCARD_PREFIX, 'FN', $this->mFullName, $indent );

        foreach ( $this->mTitles as $lang_string ) 
        {
            $xml .= TpUtils::MakeLangTag( TP_VCARD_PREFIX, 'TITLE', 
                                          $lang_string->GetValue(), 
                                          $lang_string->GetLang(), $indent );
        }

        if ( ! empty( $this->mTelephone ) )
        {
            $xml .= TpUtils::MakeTag( TP_VCARD_PREFIX, 'TEL', $this->mTelephone, $indent );
        }

        $xml .= TpUtils::MakeTag( TP_VCARD_PREFIX, 'EMAIL', $this->mEmail, $indent );

        $xml .= TpUtils::CloseTag( TP_VCARD_PREFIX, 'VCARD', $offset );

        return $xml;
        
    } // end of member function GetXml

} // end of TpContact
?>