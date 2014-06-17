<?php
/**
 * $Id: TpResourceMetadata.php 1986 2009-03-21 20:27:22Z rdg $
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
require_once('TpRelatedEntity.php');
require_once('TpEntity.php');
require_once('TpIndexingPreferences.php');
require_once('TpUtils.php');
require_once('TpConfigUtils.php');
require_once('TpDiagnostics.php');
require_once('TpResources.php');

class TpResourceMetadata extends TpBusinessObject
{
    var $mId;
    var $mDefaultLanguage;
    var $mTitles = array();
    var $mType;
    var $mAccessPoint;
    var $mDescriptions = array();
    var $mLanguage;
    var $mSubjects = array();
    var $mBibliographicCitations = array();
    var $mRights = array();
    var $mCreated;
    var $mRelatedEntities = array();
    var $mIndexingPreferences;
    var $mInTags = array();
    var $mAttrs = array();
    var $mIsLoaded = false;
    var $mCharData = '';

    function TpResourceMetadata( ) 
    {
        $this->TpBusinessObject();
        
    } // end of member function TpResourceMetadata

    function IsLoaded( ) 
    {
        $this->mIsLoaded;
        
    } // end of member function IsLoaded

    function LoadDefaults( ) 
    {
        $this->mId = '';
        $this->mType = 'http://purl.org/dc/dcmitype/Service';

        $path_to_www_dir = '/PATH_TO_WWW_DIR';

        $request_uri = $_SERVER['REQUEST_URI'];

        $pos_admin = strpos( $request_uri, '/admin/' );

        if ( $pos_admin !== false and $pos_admin > 0 )
        {
            $path_to_www_dir = substr( $request_uri, 0, $pos_admin );
        }
        
        $this->mAccessPoint = 'http://'.$_SERVER['HTTP_HOST'].
                              $path_to_www_dir.'/tapir.php/LOCAL_ID';
        
        $this->mDefaultLanguage = '';
        $this->AddTitle( '', '' );
        $this->AddDescription( '', '' );
        $this->AddSubjects( '', '' );
        $this->AddBibliographicCitation( '', '' );
        $this->AddRights( '', '' );

        $this->mCreated = TpUtils::TimestampToXsdDateTime( TpUtils::MicrotimeFloat() );

        $related_entity = new TpRelatedEntity();
        $entity = new TpEntity();
        $entity->LoadDefaults();
        $related_entity->SetEntity( $entity );
        $this->AddRelatedEntity( $related_entity );

        $this->mIndexingPreferences = new TpIndexingPreferences();
        $this->mIndexingPreferences->LoadDefaults();

        $this->mIsLoaded = true;

    } // end of member function LoadDefaults

    function LoadFromSession( ) 
    {
        $this->mId = TpUtils::GetVar( 'id', '' );

        $this->mDefaultLanguage = TpUtils::GetVar( 'default_language', '' );

        $this->LoadLangElementFromSession( 'title', $this->mTitles );

        $this->mType = urldecode( TpUtils::GetVar( 'type', '' ) );

        $this->mAccessPoint = urldecode( TpUtils::GetVar( 'accesspoint', '' ) );

        $this->LoadLangElementFromSession( 'description', $this->mDescriptions );

        $this->mLanguage = TpUtils::GetVar( 'language', '' );

        $this->LoadLangElementFromSession( 'subjects', $this->mSubjects );

        $this->LoadLangElementFromSession( 'bibliographicCitation', $this->mBibliographicCitations );
        $this->LoadLangElementFromSession( 'rights', $this->mRights );

        $this->LoadRelatedEntitiesFromSession();

        $this->mCreated = urldecode( TpUtils::GetVar( 'created', '' ) );

        $this->mIndexingPreferences = new TpIndexingPreferences();
        $this->mIndexingPreferences->LoadFromSession();

        $this->mIsLoaded = true;

    } // end of member function LoadFromSession

    function LoadRelatedEntitiesFromSession( ) 
    {
        $cnt = 1;

        while ( isset( $_REQUEST['entity_'.$cnt] ) && $cnt < 6 ) 
        {
            $prefix = 'entity_'.$cnt;

            if ( ! isset( $_REQUEST['del_'.$prefix] ) ) 
            {
                $roles = array();

                $cnt2 = 1;

                while ( $cnt2 < 10 ) // Max number of roles is hard coded!
                {
                    if ( isset( $_REQUEST[$prefix.'_role_'.$cnt2] ) ) 
                    {
                        array_push( $roles, $_REQUEST[$prefix.'_role_'.$cnt2] );
                    }

                    ++$cnt2;
                }

                $related_entity = new TpRelatedEntity();
                $entity = new TpEntity();
                $entity->LoadFromSession( $prefix );
                $related_entity->SetEntity( $entity );
                $related_entity->SetRoles( $roles );
                $this->AddRelatedEntity( $related_entity );
            }

            ++$cnt;
        }
        if ( isset( $_REQUEST['add_entity'] ) ) 
        {
            $related_entity = new TpRelatedEntity();
            $entity = new TpEntity();
            $entity->LoadDefaults();
            $related_entity->SetEntity( $entity );
            $this->AddRelatedEntity( $related_entity );
        }

    } // end of member function LoadRelatedEntitiesFromSession

    function LoadFromXml( $localId, $file ) 
    {
        $this->mId = $localId;

        $parser = xml_parser_create('UTF-8');
        xml_parser_set_option ( $parser, XML_OPTION_TARGET_ENCODING, 'UTF-8' );
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_element_handler( $parser, 'StartElement', 'EndElement' );
        xml_set_character_data_handler( $parser, 'CharacterData' );

        if ( !( $fp = fopen( $file, 'r' ) ) ) 
        {
            $error = "Could not open file: $file";
            TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );

            return false;
        }

        while ( $data = fread( $fp, 4096 ) ) 
        {
            if ( ! xml_parse( $parser, $data, feof($fp) ) ) 
            {
                $error = sprintf( "XML error: %s at line %d",
                                  xml_error_string( xml_get_error_code( $parser ) ),
                                  xml_get_current_line_number( $parser ) );

                TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_ERROR );
                return false;
            }
        }

        xml_parser_free( $parser );
        fclose( $fp );

        if ( strlen( $this->mAccessPoint ) == 0 )
        {
            $path_to_www_dir = '/PATH_TO_WWW_DIR';

            $request_uri = $_SERVER['REQUEST_URI'];

            $pos_admin = strpos( $request_uri, '/admin/' );

            if ( $pos_admin !== false and $pos_admin > 0 )
            {
                $path_to_www_dir = substr( $request_uri, 0, $pos_admin );
            }
        
            $this->mAccessPoint = 'http://'.$_SERVER['HTTP_HOST'].
                                  $path_to_www_dir.'/tapir.php/'.$this->mId;
        }

        $this->mIsLoaded = true;

        return true;

    } // end of member function LoadFromXml

    function StartElement( $parser, $name, $attrs ) 
    {
        array_push( $this->mInTags, $name );

        $this->mAttrs = $attrs;

        $depth = count( $this->mInTags );

        $lastTag = '';

        if ( $depth >= 2 )
        { 
            $lastTag = $this->mInTags[$depth-2];
        }

        if ( strcasecmp( $name, 'metadata' ) == 0 ) 
        {
            if ( isset( $attrs[TP_XML_PREFIX.':lang'] ) )
            {
                $this->mDefaultLanguage = $attrs[TP_XML_PREFIX.':lang'];
            }
        }
        else if ( strcasecmp( $name, 'indexingPreferences' ) == 0 ) 
        {
            $this->mIndexingPreferences = new TpIndexingPreferences();

            $this->mIndexingPreferences->SetStartTime( $attrs['startTime'] );
            $this->mIndexingPreferences->SetMaxDuration( $attrs['maxDuration'] );
            $this->mIndexingPreferences->SetFrequency( $attrs['frequency'] );
        }
        else if ( strcasecmp( $name, 'relatedEntity' ) == 0 ) 
        {
            $this->AddRelatedEntity( new TpRelatedEntity() );
        }
        else if ( strcasecmp( $name, 'entity' ) == 0 ) 
        {
            $r_related_entity =& $this->GetLastRelatedEntity();

            $entity = new TpEntity();

            if ( isset( $attrs['type'] ) )
            {
                $entity->SetType( $attrs['type'] );
            }

            $r_related_entity->SetEntity( $entity );
        }
        else if ( strcasecmp( $name, 'hasContact' ) == 0 ) 
        {
            $r_related_entity =& $this->GetLastRelatedEntity();
            $r_entity =& $r_related_entity->GetEntity();
            $r_entity->AddRelatedContact( new TpRelatedContact() );
        }
        else if ( strcasecmp( $name, TP_VCARD_PREFIX.':VCARD' ) == 0 ) 
        {
            $r_related_entity =& $this->GetLastRelatedEntity();
            $r_entity =& $r_related_entity->GetEntity();
            $r_related_contact =& $r_entity->GetLastRelatedContact();
            $r_related_contact->SetContact( new TpContact() );
        }

    } // end of member function StartElement

    function EndElement( $parser, $name ) 
    {
        if ( strlen( trim( $this->mCharData ) ) ) 
        {
            $depth = count( $this->mInTags );
            $in_tag = $name;
            $last_tag = $this->mInTags[$depth-2];

            $reset_char_data = true;

            $lang = null;

            if ( isset( $this->mAttrs[TP_XML_PREFIX.':lang'] ) )
            {
                $lang = $this->mAttrs[TP_XML_PREFIX.':lang'];
            }

            if ( strcasecmp( $last_tag, 'metadata' ) == 0 ) 
            {
                if ( strcasecmp( $in_tag, TP_DC_PREFIX.':title' ) == 0 ) 
                {
                    $this->AddTitle( trim( $this->mCharData ), $lang );
                }
                else if ( strcasecmp( $in_tag, TP_DC_PREFIX.':type' ) == 0 ) 
                {
                    $this->mType = trim( $this->mCharData );
                }
                else if ( strcasecmp( $in_tag, TP_DC_PREFIX.':description' ) == 0 ) 
                {
                    $this->AddDescription( trim( $this->mCharData ), $lang );
                }
                else if ( strcasecmp( $in_tag, TP_DC_PREFIX.':language' ) == 0 ) 
                {
                    $this->mLanguage = trim( $this->mCharData );
                }
                else if ( strcasecmp( $in_tag, TP_DC_PREFIX.':subject' ) == 0 ) 
                {
                    $this->AddSubjects( trim( $this->mCharData ), $lang );
                }
                else if ( strcasecmp( $in_tag, TP_DCT_PREFIX.':bibliographicCitation' ) == 0 ) 
                {
                    $this->AddBibliographicCitation( trim( $this->mCharData ), $lang );
                }
                else if ( strcasecmp( $in_tag, TP_DC_PREFIX.':rights' ) == 0 ) 
                {
                    $this->AddRights( trim( $this->mCharData ), $lang );
                }
                else if ( strcasecmp( $in_tag, TP_DCT_PREFIX.':created' ) == 0 ) 
                {
                    $this->mCreated = trim( $this->mCharData );
                }
                else
                {
                    $reset_char_data = false;
                }
            }
            else if ( in_array( 'relatedEntity', $this->mInTags ) ) 
            {
                $r_related_entity =& $this->GetLastRelatedEntity();

                if ( strcasecmp( $last_tag, 'relatedEntity' ) == 0 ) 
                {
                    if ( strcasecmp( $in_tag, 'role' ) == 0 ) 
                    {
                        $r_related_entity->AddRole( trim( $this->mCharData ) );
                    }
                    else
                    {
                        $reset_char_data = false;
                    }
                }
                else if ( in_array( 'entity', $this->mInTags ) ) 
                {
                    $r_entity =& $r_related_entity->GetEntity();

                    if ( strcasecmp( $last_tag, 'entity' ) == 0 ) 
                    {
                        if ( strcasecmp( $in_tag, 'identifier' ) == 0 ) 
                        {
                            $r_entity->SetIdentifier( trim( $this->mCharData ) );
                        }
                        else if ( strcasecmp( $in_tag, 'name' ) == 0 ) 
                        {
                            $r_entity->AddName( trim( $this->mCharData ), $lang );
                        }
                        else if ( strcasecmp( $in_tag, 'description' ) == 0 ) 
                        {
                            $r_entity->AddDescription( trim( $this->mCharData ), $lang );
                        }
                        else if ( strcasecmp( $in_tag, 'acronym' ) == 0 ) 
                        {
                            $r_entity->SetAcronym( trim( $this->mCharData ) );
                        }
                        else if ( strcasecmp( $in_tag, 'address' ) == 0 ) 
                        {
                            $r_entity->SetAddress( trim( $this->mCharData ) );
                        }
                        else if ( strcasecmp( $in_tag, 'regionCode' ) == 0 ) 
                        {
                            $r_entity->SetRegionCode( trim( $this->mCharData ) );
                        }
                        else if ( strcasecmp( $in_tag, 'countryCode' ) == 0 ) 
                        {
                            $r_entity->SetCountryCode( trim( $this->mCharData ) );
                        }
                        else if ( strcasecmp( $in_tag, 'zipCode' ) == 0 ) 
                        {
                            $r_entity->SetZipCode( trim( $this->mCharData ) );
                        }
                        else if ( strcasecmp( $in_tag, 'logoURL' ) == 0 ) 
                        {
                            $r_entity->SetLogoUrl( trim( $this->mCharData ) );
                        }
                        else if ( strcasecmp( $in_tag, 'relatedInformation' ) == 0 ) 
                        {
                            $r_entity->SetRelatedInformation( trim( $this->mCharData ) );
                        }
                        else
                        {
                            $reset_char_data = false;
                        }
                    }
                    else if ( strcasecmp( $last_tag, TP_GEO_PREFIX.':Point' ) == 0 ) 
                    {
                        if ( strcasecmp( $in_tag, TP_GEO_PREFIX.':long' ) == 0 ) 
                        {
                            $r_entity->SetLongitude( trim( $this->mCharData ) );
                        }
                        else if ( strcasecmp( $in_tag, TP_GEO_PREFIX.':lat' ) == 0 ) 
                        {
                            $r_entity->SetLatitude( trim( $this->mCharData ) );
                        }
                        else
                        {
                            $reset_char_data = false;
                        }
                    }
                    else if ( in_array( 'hasContact', $this->mInTags ) ) 
                    {
                        $r_related_contact =& $r_entity->GetLastRelatedContact();

                        if ( strcasecmp( $last_tag, 'hasContact' ) == 0 ) 
                        {
                            if ( strcasecmp( $in_tag, 'role' ) == 0 ) 
                            {
                                $r_related_contact->AddRole( trim( $this->mCharData ) );
                            }
                            else
                            {
                                $reset_char_data = false;
                            }
                        }
                        else if ( in_array( TP_VCARD_PREFIX.':VCARD', $this->mInTags ) )
                        {
                            $r_contact =& $r_related_contact->GetContact();

                            if ( strcasecmp( $in_tag, TP_VCARD_PREFIX.':FN' ) == 0 ) 
                            {
                                $r_contact->SetFullName( trim( $this->mCharData ) );
                            }
                            else if ( strcasecmp( $in_tag, TP_VCARD_PREFIX.':TITLE' ) == 0 ) 
                            {
                                $r_contact->AddTitle( trim( $this->mCharData ), $lang );
                            }
                            else if ( strcasecmp( $in_tag, TP_VCARD_PREFIX.':EMAIL' ) == 0 ) 
                            {
                                $r_contact->SetEmail( trim( $this->mCharData ) );
                            }
                            else if ( strcasecmp( $in_tag, TP_VCARD_PREFIX.':TEL' ) == 0 ) 
                            {
                                $r_contact->SetTelephone( trim( $this->mCharData ) );
                            }
                            else
                            {
                                $reset_char_data = false;
                            }
                        }
                        else
                        {
                            $reset_char_data = false;
                        }
                    }
                    else
                    {
                        $reset_char_data = false;
                    }
                }
                else
                {
                    $reset_char_data = false;
                }
            }
            else
            {
                 $reset_char_data = false;
            }

            if ( $reset_char_data )
            {
                $this->mCharData = '';
            }
        }

        array_pop( $this->mInTags );

    } // end of member function EndElement

    function CharacterData( $parser, $data ) 
    {
        $this->mCharData .= $data;

    } // end of member function CharacterData

    function AddTitle( $title, $lang ) 
    {
        array_push( $this->mTitles, new TpLangString( $title, $lang ) );

    } // end of member function AddTitle

    function AddDescription( $description, $lang ) 
    {
        array_push( $this->mDescriptions, new TpLangString( $description, $lang ) );

    } // end of member function AddDescription

    function AddSubjects( $subjects, $lang ) 
    {
        array_push( $this->mSubjects, new TpLangString( $subjects, $lang ) );

    } // end of member function AddSubjects

    function AddBibliographicCitation( $citation, $lang ) 
    {
        array_push( $this->mBibliographicCitations, new TpLangString( $citation, $lang ) );

    } // end of member function AddBibliographicCitation

    function AddRights( $rights, $lang ) 
    {
        array_push( $this->mRights, new TpLangString( $rights, $lang ) );

    } // end of member function AddRights

    function AddRelatedEntity( $relatedEntity ) 
    {
        array_push( $this->mRelatedEntities, $relatedEntity );

    } // end of member function AddRelatedEntity

    function SetType( $type )
    {
        $this->mType = $type;

    } // end of member function SetType

    function SetId( $localId ) 
    {
        $this->mId = $localId;

    } // end of member function SetId

    function GetId( ) 
    {
        return $this->mId;

    } // end of member function GetId

    function GetDefaultLanguage( ) 
    {
        return $this->mDefaultLanguage;

    } // end of member function GetDefaultLanguage

    function GetType( ) 
    {
        return $this->mType;

    } // end of member function GetType

    function GetTitles( ) 
    {
        return $this->mTitles;

    } // end of member function GetTitles

    function GetDescriptions( ) 
    {
        return $this->mDescriptions;

    } // end of member function GetDescriptions

    function GetSubjects( ) 
    {
        return $this->mSubjects;

    } // end of member function GetDescriptions

    function GetBibliographicCitations( ) 
    {
        return $this->mBibliographicCitations;

    } // end of member function GetBibliographicCitations

    function GetRights( ) 
    {
        return $this->mRights;

    } // end of member function GetRights

    function GetRelatedEntities( ) 
    {
        return $this->mRelatedEntities;

    } // end of member function GetRelatedEntities

    function &GetLastRelatedEntity( ) 
    {
        $cnt = count( $this->mRelatedEntities );

        return $this->mRelatedEntities[$cnt-1];

    } // end of member function GetLastRelatedEntity

    function GetIndexingPreferences( ) 
    {
        if ( ! is_object( $this->mIndexingPreferences ) )
        {
            $this->mIndexingPreferences = new TpIndexingPreferences();
            $this->mIndexingPreferences->LoadDefaults();
        }

        return $this->mIndexingPreferences;

    } // end of member function GetIndexingPreferences

    function GetCreated( ) 
    {
        return $this->mCreated;

    } // end of member function GetCreated

    function SetCreated( $created ) 
    {
        $this->mCreated = $created;

    } // end of member function SetCreated

    function GetAccesspoint( )
    {
        return $this->mAccessPoint;

    } // end of member function GetAccesspoint

    function SetAccesspoint( $accesspoint )
    {
        $this->mAccessPoint = $accesspoint;

    } // end of member function SetAccesspoint

    function GetLanguage( ) 
    {
        return $this->mLanguage;

    } // end of member function GetLanguage

    function Validate( $raiseErrors=true ) 
    {
        $ret_val = true;

        $default_lang = $this->mDefaultLanguage;

        // id is mandatory
        if ( strlen( $this->mId ) == 0 ) 
        {
            if ( $raiseErrors )
            {
                $error = 'Local identifier is empty!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }
        else 
        {
            // Check that id does not contain special chars for URL
            if ( $this->mId <> urlencode( $this->mId ) ) 
            {
                if ( $raiseErrors )
                {
                    $error = 'Local identifier must not contain URL special characters!';
                    TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
                }
                $ret_val = false;
            }

            // Check that id is unique
            $resources =& TpResources::GetInstance();

            $raise_error = false;

            $resource_param = TpUtils::GetVar( 'resource', false );

            if ( $resource_param ) // only check when adding/editing a resource
            {
                if ( $this->mId != $resource_param and 
                     $resources->GetResource( $this->mId, $raise_error ) != null )
                {
                    if ( $raiseErrors )
                    {
                        $error = 'Local identifier already exists! Please choose another one.';
                        TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
                    }
                    $ret_val = false;
                }
            }
        }

        // Validate type
        if ( strlen( $this->mType ) == 0 ) 
        {
            if ( $raiseErrors )
            {
                $error = 'Resource type must be specified! '.
                         '(this message is intended for developers '.
                         'because the type should be automatically '.
                         'defined internally).';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        // Validate accesspoint
        if ( strlen( $this->mAccessPoint ) == 0 ) 
        {
            if ( $raiseErrors )
            {
                $error = 'Resource accesspoint must be specified!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }
        else 
        {
            if ( ! TpUtils::IsUrl( $this->mAccessPoint ) ) 
            {
                if ( $raiseErrors )
                {
                    $error = 'Resource accesspoint is not a URL!';
                    TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
                }
                $ret_val = false;
            }
        }

        // Validate created
        if ( strlen( $this->mCreated ) == 0 ) 
        {
            if ( $raiseErrors )
            {
                $error = '"Creation date" must be specified! '.
                         '(this message is intended for developers '.
                         'because dct:created should be automatically '.
                         'defined internally).';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        // At least one title
        if ( count( $this->mTitles ) == 0 ) 
        {
            if ( $raiseErrors )
            {
                $error = 'Resource metadata must have at least one title!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }
        else 
        {
            // Validate titles
            if ( ! TpConfigUtils::ValidateLangSection( 'Resource title', 
                                                       $this->mTitles,
                                                       $raiseErrors, true, 
                                                       $default_lang ) )
            {
                $ret_val = false;
            }
        }

        // At least one description
        if ( count( $this->mDescriptions ) == 0 ) 
        {
            if ( $raiseErrors )
            {
                $error = 'Resource metadata must have at least one description!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }
        else 
        {
            // Validate descriptions
            if ( ! TpConfigUtils::ValidateLangSection( 'Resource description', 
                                                       $this->mDescriptions,
                                                       $raiseErrors, true,
                                                       $default_lang ) ) 
            {
                $ret_val = false;
            }
        }

        // Language
        if ( strlen( $this->mLanguage ) == 0 ) 
        {
            if ( $raiseErrors )
            {
                $error = 'Content language is empty!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        // Validate subjects
        if ( ! TpConfigUtils::ValidateLangSection( 'Resource subjects', 
                                                   $this->mSubjects,
                                                   $raiseErrors, false,
                                                   $default_lang ) ) 
        {
            $ret_val = false;
        }

        // Validate bibliographic citations
        if ( ! TpConfigUtils::ValidateLangSection( 'Bibliographic citation', 
                                                   $this->mBibliographicCitations,
                                                   $raiseErrors, false,
                                                   $default_lang ) ) 
        {
            $ret_val = false;
        }

        // Validate rights
        if ( ! TpConfigUtils::ValidateLangSection( 'Resource rights', 
                                                   $this->mRights,
                                                   $raiseErrors, false,
                                                   $default_lang ) ) 
        {
            $ret_val = false;
        }

        // Validate related entities
        $cnt = 0;
        foreach ( $this->mRelatedEntities as $related_entity ) 
        {
            if ( ! $related_entity->Validate( $raiseErrors, $default_lang ) )
            {
                $ret_val = false;
            }

            ++$cnt;
        }

        if ( $cnt == 0 ) 
        {
            if ( $raiseErrors )
            {
                $error = 'There must be at least one related entity!';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            $ret_val = false;
        }

        return $ret_val;

    } // end of member function Validate

    function GetXml( $offset='', $indentWith='' )
    {
        $attrs = array();

        if ( $this->mDefaultLanguage != null )
        {
            $attrs[TP_XML_PREFIX.':lang'] = $this->mDefaultLanguage;
        }

        $xml = TpUtils::OpenTag( '', 'metadata', $offset, $attrs );

        $indent = $offset . $indentWith;

        foreach ( $this->mTitles as $lang_string ) 
        {
            $xml .= TpUtils::MakeLangTag( TP_DC_PREFIX, 'title', 
                                          $lang_string->GetValue(), 
                                          $lang_string->GetLang(), $indent );
        }

        $xml .= TpUtils::MakeTag( TP_DC_PREFIX, 'type', $this->mType, $indent );

        $xml .= $indent.'<accesspoint>'.
                '<?php if (is_object($resource)): ?>'.
                '<?php print($resource->GetAccesspoint()); ?>'.
                '<?php else: ?>'.
                '<?php print(\'http://\'.$_SERVER[\'SERVER_NAME\'].\':\'.$_SERVER[\'SERVER_PORT\'].$_SERVER[\'REQUEST_URI\']); ?>'.
                '<?php endif; ?>'.
                "</accesspoint>\n";

        foreach ( $this->mDescriptions as $lang_string ) 
        {
            $xml .= TpUtils::MakeLangTag( TP_DC_PREFIX, 'description', 
                                          $lang_string->GetValue(), 
                                          $lang_string->GetLang(), $indent );
        }

        $xml .= TpUtils::MakeTag( TP_DC_PREFIX, 'language', $this->mLanguage, $indent );

        foreach ( $this->mSubjects as $lang_string ) 
        {
            if ( strlen( $lang_string->GetValue() ) > 0 )
            {
                $xml .= TpUtils::MakeLangTag( TP_DC_PREFIX, 'subject', 
                                              $lang_string->GetValue(), 
                                              $lang_string->GetLang(), $indent );
            }
        }

        foreach ( $this->mBibliographicCitations as $lang_string ) 
        {
            if ( strlen( $lang_string->GetValue() ) > 0 )
            {
                $xml .= TpUtils::MakeLangTag( TP_DCT_PREFIX, 'bibliographicCitation', 
                                              $lang_string->GetValue(), 
                                              $lang_string->GetLang(), $indent );
            }
        }

        foreach ( $this->mRights as $lang_string ) 
        {
            if ( strlen( $lang_string->GetValue() ) > 0 )
            {
                $xml .= TpUtils::MakeLangTag( TP_DC_PREFIX, 'rights', 
                                              $lang_string->GetValue(), 
                                              $lang_string->GetLang(), $indent );
            }
        }

        $xml .= $indent.'<'.TP_DCT_PREFIX.':modified>'.
                '<?php if ($date_last_modified): ?>'.
                '<?php print($date_last_modified); ?>'.
                '<?php endif; ?>'.
                '</'.TP_DCT_PREFIX.':modified>'."\n";

        $xml .= TpUtils::MakeTag( TP_DCT_PREFIX, 'created', $this->mCreated, $indent );

        // Indexing preferences
        if ( is_object( $this->mIndexingPreferences ) )
        {
            $xml .= $this->mIndexingPreferences->GetXml( $indent, $indentWith );
        }

        foreach ( $this->mRelatedEntities as $related_entity ) 
        {
            $xml .= $related_entity->GetXml( $indent, $indentWith );
        }

        $xml .= TpUtils::CloseTag( '', 'metadata', $offset );

        return $xml;

    } // end of member function GetXml

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
      return array( 'mId', 'mDefaultLanguage', 'mTitles', 'mType', '$mAccessPoint', 
                    'mDescriptions', 'mLanguage', 'mSubjects', 
                    'mBibliographicCitations', 'mRights', 'mCreated', 
                    'mRelatedEntities', 'mIndexingPreferences', 'mIsLoaded' );

    } // end of member function __sleep

} // end of TpResourceMetadata
?>
