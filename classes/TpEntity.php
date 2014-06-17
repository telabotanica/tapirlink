<?php
/**
 * $Id: TpEntity.php 1986 2009-03-21 20:27:22Z rdg $
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
require_once('TpRelatedContact.php');
require_once('TpContact.php');
require_once('TpUtils.php');
require_once('TpConfigUtils.php');
require_once('TpDiagnostics.php');

class TpEntity extends TpBusinessObject
{
    var $mIdentifier = '';
    var $mType;
    var $mNames;
    var $mAcronym;
    var $mLogoUrl;
    var $mDescriptions;
    var $mRelatedInformation;
    var $mRelatedContacts;
    var $mLatitude;
    var $mLongitude;
    var $mAddress;
    var $mRegionCode;
    var $mCountryCode;
    var $mZipCode;

    function TpEntity( ) 
    {
        $this->TpBusinessObject();
        $this->mNames = array();
        $this->mDescriptions = array();
        $this->mRelatedContacts = array();

    } // end of member function TpEntity

    function LoadDefaults( )
    {
        $this->SetType( 'organization' );
        $this->AddName( '', '' );
        $this->AddDescription( '', '' );

        $related_contact = new TpRelatedContact();
        $contact = new TpContact();
        $contact->LoadDefaults();
        $related_contact->SetContact( $contact );
        $this->AddRelatedContact( $related_contact );

    } // end of member function LoadDefaults

    function LoadFromSession( $prefix ) 
    {
        $this->mIdentifier = TpUtils::GetVar( $prefix.'_id', '' );

        $this->mType = TpUtils::GetVar( $prefix.'_type', '' );

        $this->LoadLangElementFromSession( $prefix.'_name', $this->mNames );

        $this->mAcronym = TpUtils::GetVar( $prefix.'_acronym', '' );

        $this->LoadLangElementFromSession( $prefix.'_description', $this->mDescriptions );

        $this->mLogoUrl = urldecode( TpUtils::GetVar( $prefix.'_logoURL', '' ) );

        $this->mAddress = TpUtils::GetVar( $prefix.'_address', '' );

        $this->mRegionCode = TpUtils::GetVar( $prefix.'_regionCode', '' );

        $this->mCountryCode = TpUtils::GetVar( $prefix.'_countryCode', '' );

        $this->mZipCode = TpUtils::GetVar( $prefix.'_zipCode', '' );

        $this->mRelatedInformation = urldecode( TpUtils::GetVar( $prefix.'_relatedInformation', '' ) );

        $this->mLongitude = TpUtils::GetVar( $prefix.'_longitude', '' );

        $this->mLatitude = TpUtils::GetVar( $prefix.'_latitude', '' );

        $this->LoadRelatedContacts( $prefix );

    } // end of member function LoadFromSession

    function LoadRelatedContacts( $prefix ) 
    {
        $cnt = 1;

        while ( isset( $_REQUEST[$prefix.'_contact_'.$cnt] ) && $cnt < 6 ) 
        {
            $newprefix = $prefix.'_contact_'.$cnt;

            if ( ! isset( $_REQUEST['del_'.$newprefix] ) ) 
            {
                $roles = array();

                $cnt2 = 1;

                while ( $cnt2 < 10 ) // Max number of roles is hard coded!
                {
                    if ( isset( $_REQUEST[$newprefix.'_role_'.$cnt2] ) ) 
                    {
                        array_push( $roles, $_REQUEST[$newprefix.'_role_'.$cnt2 ] );
                    }

                    ++$cnt2;
                }

                $related_contact = new TpRelatedContact();
                $contact = new TpContact();
                $contact->LoadFromSession( $newprefix );
                $related_contact->SetContact( $contact );
                $related_contact->SetRoles( $roles );
                $this->AddRelatedContact( $related_contact );

            }
            ++$cnt;
        }
        if ( isset( $_REQUEST['add_'.$prefix.'_contact'] ) )
        {
            $related_contact = new TpRelatedContact();
            $contact = new TpContact();
            $contact->LoadDefaults();
            $related_contact->SetContact( $contact );
            $this->AddRelatedContact( $related_contact );
        }

    } // end of member function LoadRelatedContacts

    function AddRelatedContact( $relatedContact ) 
    {
        array_push( $this->mRelatedContacts, $relatedContact );

    } // end of member function AddRelatedContact

    function AddName( $name, $lang ) 
    {
        array_push( $this->mNames, new TpLangString( $name, $lang ) );

    } // end of member function AddName

    function AddDescription( $description, $lang ) 
    {
        array_push( $this->mDescriptions, new TpLangString( $description, $lang ) );

    } // end of member function AddDescription

    function GetIdentifier( ) 
    {
        return $this->mIdentifier;

    } // end of member function GetIdentifier

    function SetIdentifier( $id ) 
    {
        $this->mIdentifier = $id;

    } // end of member function SetIdentifier

    function GetType( ) 
    {
        return $this->mType;

    } // end of member function GetType

    function SetType( $type ) 
    {
        $this->mType = $type;

    } // end of member function SetType

    function GetNames( ) 
    {
        return $this->mNames;

    } // end of member function GetNames

    function GetDescriptions( ) 
    {
        return $this->mDescriptions;

    } // end of member function GetDescriptions

    function GetAcronym( ) 
    {
        return $this->mAcronym;

    } // end of member function GetAcronym

    function SetAcronym( $acronym ) 
    {
        $this->mAcronym = $acronym;

    } // end of member function SetAcronym

    function GetLogoUrl( ) 
    {
        return $this->mLogoUrl;

    } // end of member function GetLogoUrl

    function SetLogoUrl( $logo )
    {
        $this->mLogoUrl = $logo;

    } // end of member function SetLogoURL

    function GetAddress( ) 
    {
        return $this->mAddress;

    } // end of member function GetAddress

    function SetAddress( $address ) 
    {
        $this->mAddress = $address;

    } // end of member function SetAddress

    function GetRegionCode( ) 
    {
        return $this->mRegionCode;

    } // end of member function GetRegionCode

    function SetRegionCode( $regionCode ) 
    {
        $this->mRegionCode = $regionCode;

    } // end of member function SetRegionCode

    function GetCountryCode( ) 
    {
        return $this->mCountryCode;

    } // end of member function GetCountryCode

    function SetCountryCode( $countryCode ) 
    {
        $this->mCountryCode = $countryCode;

    } // end of member function SetCountryCode

    function GetZipCode( ) 
    {
        return $this->mZipCode;

    } // end of member function GetZipCode

    function SetZipCode( $zipCode ) 
    {
        $this->mZipCode = $zipCode;

    } // end of member function SetZipCode

    function GetRelatedInformation( ) 
    {
        return $this->mRelatedInformation;

    } // end of member function GetRelatedInformation

    function SetRelatedInformation( $info ) 
    {
        $this->mRelatedInformation = $info;

    } // end of member function SetRelatedInformation

    function GetLongitude( ) 
    {
        return $this->mLongitude;

    } // end of member function GetLongitude

    function SetLongitude( $long ) 
    {
        $this->mLongitude = $long;

    } // end of member function SetLongitude

    function GetLatitude( ) 
    {
        return $this->mLatitude;

    } // end of member function GetLatitude

    function SetLatitude( $lat ) 
    {
        $this->mLatitude = $lat;

    } // end of member function SetLatitude

    function GetRelatedContacts( ) 
    {
        return $this->mRelatedContacts;

    } // end of member function GetRelatedContacts

    function &GetLastRelatedContact( ) 
    {
        $cnt = count( $this->mRelatedContacts );

        return $this->mRelatedContacts[$cnt-1];

    } // end of member function GetLastRelatedContact

    function Validate( $raiseErrors=true, $defaultLang=null ) 
    {
        $ret_val = true;

        // Validate identifier
        if ( count( $this->mIdentifier ) == 0 ) 
        {
            if ( $raiseErrors )
            {
                $error = 'One of the entities has no identifier!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        // Validate names
        if ( ! TpConfigUtils::ValidateLangSection( 'Entity Name', 
                                                   $this->mNames,
                                                   $raiseErrors, true,
                                                   $defaultLang ) ) 
        {
            $ret_val = false;
        }

        // Validate acronym
        if ( strlen( $this->mAcronym ) == 0 ) 
        {
            if ( $raiseErrors )
            {
                $error = 'One of the entity acronyms was not specified!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        // Validate descriptions
        if ( ! TpConfigUtils::ValidateLangSection( 'Entity Description', 
                                                   $this->mDescriptions,
                                                   $raiseErrors, false,
                                                   $defaultLang ) ) 
        {
            $ret_val = false;
        }

        // Validate related contacts
        $cnt = 0;
        foreach ( $this->mRelatedContacts as $related_contact ) 
        {
            if ( ! $related_contact->Validate( $raiseErrors, $defaultLang ) ) 
            {
                $ret_val = false;
            }
            ++$cnt;
        }

        if ( $cnt == 0 )
        {
            if ( $raiseErrors )
            {
                $error = 'All entities must have at least one contact!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        return $ret_val;

    } // end of member function Validate

    function GetXml( $offset='', $indentWith='' ) 
    {
        $indent1 = $offset.$indentWith;
        $indent2 = $offset.$indentWith.$indentWith;

        $attrs = array();

        if ( ! empty( $this->mType ) )
        {
            $attrs['type'] = $this->mType;
        }

        $xml = TpUtils::OpenTag( '', 'entity', $offset, $attrs );

        $xml .= TpUtils::MakeTag( '', 'identifier', $this->mIdentifier, $indent1 );

        foreach ( $this->mNames as $lang_string ) 
        {
            $xml .= TpUtils::MakeLangTag( '', 'name', 
                                          $lang_string->GetValue(), 
                                          $lang_string->GetLang(), $indent1 );
        }

        $xml .= TpUtils::MakeTag( '', 'acronym', $this->mAcronym, $indent1 );

        if ( ! empty( $this->mLogoUrl ) )
        {
            $xml .= TpUtils::MakeTag( '', 'logoURL', $this->mLogoUrl, $indent1 );
        }

        foreach ( $this->mDescriptions as $lang_string ) 
        {
            $xml .= TpUtils::MakeLangTag( '', 'description', 
                                          $lang_string->GetValue(), 
                                          $lang_string->GetLang(), $indent1 );
        }

        $xml .= TpUtils::MakeTag( '', 'address', $this->mAddress, $indent1 );

        if ( ! empty( $this->mRegionCode ) )
        {
            $xml .= TpUtils::MakeTag( '', 'regionCode', $this->mRegionCode, $indent1 );
        }

        if ( ! empty( $this->mCountryCode ) )
        {
            $xml .= TpUtils::MakeTag( '', 'countryCode', $this->mCountryCode, $indent1 );
        }

        if ( ! empty( $this->mZipCode ) )
        {
            $xml .= TpUtils::MakeTag( '', 'zipCode', $this->mZipCode, $indent1 );
        }

        if ( ! empty( $this->mRelatedInformation ) )
        {
            $xml .= TpUtils::MakeTag( '', 'relatedInformation', 
                                      $this->mRelatedInformation, $indent1 );
        }

        foreach ( $this->mRelatedContacts as $related_contact ) 
        {
            $xml .= $related_contact->GetXml( $indent1, $indentWith );
        }

        // geo:Point
        if ( ! empty( $this->mLatitude ) and ! empty( $this->mLongitude ) )
        {
            $xml .= TpUtils::OpenTag( TP_GEO_PREFIX, 'Point', $indent1 );
            $xml .= TpUtils::MakeTag( TP_GEO_PREFIX, 'lat', $this->mLatitude, $indent2 );
            $xml .= TpUtils::MakeTag( TP_GEO_PREFIX, 'long', $this->mLongitude, $indent2 );
            $xml .= TpUtils::CloseTag( TP_GEO_PREFIX, 'Point', $indent1 );
        }

        $xml .= TpUtils::CloseTag( '', 'entity', $offset );

        return $xml;

    } // end of member function getXml

} // end of TpEntity
?>
