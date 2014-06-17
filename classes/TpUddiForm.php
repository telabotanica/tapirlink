<?php
/**
 * $Id: TpUddiForm.php 134 2007-01-16 01:21:30Z rdg $
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

require_once('TpPage.php');
require_once('TpResources.php');
require_once('TpUtils.php');
require_once('TpDiagnostics.php');
require_once('UDDI.php');

class TpUddiForm extends TpPage
{
    var $mInteractions = array();  // UDDI operation=>array('req'=>string,'resp'=>string)
    var $mBusinessCache = array(); // business name => business key

    function TpUddiForm( )
    {

    } // end of member function TpUddiForm

    function DisplayHtml( )
    {
        $r_resources =& TpResources::GetInstance();

        $active_resources = $r_resources->GetActiveResources();

        include('TpUddiForm.tmpl.php');

    } // end of member function DisplayHtml

    function EchoMessage( $msg )
    {
        printf( "\n<br/><span class=\"msg\">%s</span>", nl2br( $msg ) );

        flush();

    } // end of member function EchoMessage

    function EchoErrors( )
    {
        $errors = TpDiagnostics::GetMessages();

        if ( count( $errors ) )
        {
            printf( "\n<br/><span class=\"error\">%s</span>", 
                    nl2br( implode( '<br/>', $errors ) ) );

            flush();

	    TpDiagnostics::Reset();

            return true;
        }

        return false;

    } // end of member function EchoErrors

    function Process( ) 
    {
        flush();

        $r_resources =& TpResources::GetInstance();

        $active_resources = $r_resources->GetActiveResources();

        if ( ! $this->EchoErrors() )
        {
            if ( count( $active_resources ) == 0 )
            {
                $this->EchoMessage( 'There are no active resources' );
            }
            else if ( isset( $_REQUEST['first'] ) )
            {
                $this->EchoMessage( 'Please note that only UDDI v2 is supported.' );
            }
        }

        if ( isset( $_REQUEST['register'] ) )
        {
            $uddi_name = trim( TpUtils::GetVar( 'uddi_name' ) );

            $tmodel_name = trim( TpUtils::GetVar( 'tmodel_name' ) );

            $inquiry_url = trim( TpUtils::GetVar( 'inquiry_url' ) );

            $inquiry_port = trim( TpUtils::GetVar( 'inquiry_port' ) );

            $publish_url = trim( TpUtils::GetVar( 'publish_url' ) );

            $publish_port = trim( TpUtils::GetVar( 'publish_port' ) );

            if ( empty( $uddi_name ) )
            {
                $msg = 'No UDDI name (operator) specified';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $msg, DIAG_ERROR );
                return;
            }
            if ( empty( $tmodel_name ) )
            {
                $msg = 'No Tmodel name specified';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $msg, DIAG_ERROR );
                return;
            }
            if ( empty( $inquiry_url ) or empty( $inquiry_port ) or 
                 empty( $publish_url ) or empty( $publish_port ) )
            {
                $msg = 'Please specify all URLs and ports';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $msg, DIAG_ERROR );
                return;
            }

            $inquiry_port = (int)$inquiry_port;
            $publish_port = (int)$publish_port;

            $selected_resources = TpUtils::GetVar( 'resources', array() );

            if ( count( $selected_resources ) == 0 )
            {
                $msg = 'No resources selected';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $msg, DIAG_ERROR );
                return;
            }

            // Initialize UDDI object

            $uddi = new UDDI();

            if ( _DEBUG )
            {
                $uddi->_debug = true;
            }

            $uddi->_api = 'Inquiry';
            $uddi->_uddiversion = 2;
            $uddi->_regarray = array( $uddi_name =>
                                      array( 'Inquiry' =>
                                             array( 'url'  => $inquiry_url,
                                                    'port' => $inquiry_port),
                                             'Publish' =>
                                             array( 'url'  => $publish_url,
                                                    'port' => $publish_port ) ) );

            // Trick to make this code work under PHP4 since the API provide
            // no means of setting the above parameters through methods
            $uddi->UDDI( $uddi_name, 2 );

            // Find tModel key

            $params = array( 'name' => $tmodel_name, 'maxRows' => 2 );

            ob_start();

            $result = $uddi->find_tModel( $params );

            if ( _DEBUG )
            {
                $this->mInteractions['find_tModel']['req'] = ob_get_contents();
                $this->mInteractions['find_tModel']['resp'] = htmlspecialchars($result);
            }

            ob_end_clean();

            if ( $this->_NotOk( $result ) )
            {
                return;
            }

            $tmodel_keys = $this->_ExtractTmodelKeys( $result );

            if ( count( $tmodel_keys ) == 0 )
            {
                $msg = 'Registration failed: ';
                $msg .= 'no tModels found for "'.$tmodel_name.'"';
                TpDiagnostics::Append( CFG_UDDI_ERROR, $msg, DIAG_ERROR );
                return;
            }
            else if ( count( $tmodel_keys ) > 1 )
            {
                $msg = 'Registration failed: ';
                $msg .= 'found more than one tModel found for "'.$tmodel_name.'"';
                TpDiagnostics::Append( CFG_UDDI_ERROR, $msg, DIAG_ERROR );
                return;
            }
 
            $tmodel_key = substr( $tmodel_keys[0], 5 ); // substr removes leading uuid:

            $i = 0;

            foreach ( $selected_resources as $resource_code )
            {
                $this->EchoErrors();

                ++$i;

                $r_resource =& $r_resources->GetResource( $resource_code );

                if ( $this->_Register( $uddi, $r_resource, $tmodel_key ) )
                {
                    $this->EchoMessage( "\n".'Resource "'.$resource_code.'" '.
                                        'successfully registered' );
                }
            }
        }

    } // end of member function Process

    function _Register( $uddi, &$rResource, $tmodelKey )
    {
        // Find business with the same name

        $business_key = $this->_GetBusinessKey( $uddi, $rResource );

        if ( ! $business_key )
        {
            return false;
        }

        // Find service with the same title

        $service_key = $this->_GetServiceKey( $uddi, $rResource, $business_key );

        if ( ! $service_key )
        {
            return false;
        }

        // Register binding

        if ( ! $this->_RegisterBinding( $uddi, $rResource, $service_key, $tmodelKey ) )
        {
            return false;
        }
        
        return true;

    } // end of member function _Register

    function _GetBusinessKey( $uddi, &$rResource )
    {
        $business_key = false;

        $resource_code = $rResource->GetCode();

        $r_metadata =& $this->_GetResourceMetadata( $rResource );

        $default_language = $r_metadata->GetDefaultLanguage();

        $entity = $this->_GetMainEntity( $r_metadata->GetRelatedEntities() );

        if ( $entity == null )
        {
            $msg = 'Resource "'.$resource_code.'" has no business entity';
            TpDiagnostics::Append( DC_CONFIG_FAILURE, $msg, DIAG_ERROR );
            return false;
        }

        $entity_name = $this->_GetMainLangString( $entity->GetNames(), $default_language );

        $business_name = $entity_name->GetValue();

        if ( isset( $this->mBusinessCache[$business_name] ) )
        {
            $business_key = $this->mBusinessCache[$business_name];
        }
        else
        {
            $params = array( 'name' => $business_name, 
                             'findQualifiers' => 'exactNameMatch' );

            ob_start();

            $uddi->_api = 'Inquiry';

            $result = $uddi->find_business( $params );

            if ( _DEBUG )
            {
                $label = 'find_business ('.$resource_code.')';

                $this->mInteractions[$label]['req'] = ob_get_contents();
                $this->mInteractions[$label]['resp'] = htmlspecialchars( $result );
            }

            ob_end_clean();

            if ( $this->_NotOk( $result, $resource_code ) )
            {
                return false;
            }

            $business_keys = $this->_ExtractBusinessKeys( $result );

            if ( count( $business_keys ) == 0 )
            {
                // Register new business

                $business_entity = array();

                $name_array = array();

                $name = array();
                $name['content'] = $business_name;

                $lang = $entity_name->GetLang();

                if ( empty( $lang ) )
                {
                    $lang = $default_language;
                }

                $name['lang'] = $lang;

                array_push( $name_array, $name );

                $business_entity['name'] = $name_array;
                $business_entity['businessKey'] = '';

                $descriptions = $entity->GetDescriptions();

                if ( count( $descriptions ) )
                {
                    $entity_description = $this->_GetMainLangString( $descriptions,
                                          $r_metadata->GetDefaultLanguage() );

                    $description_array = array();

                    $description_array['content'] = $entity_description->GetValue();

                    $lang = $entity_description->GetLang();

                    if ( empty( $lang ) )
                    {
                        $lang = $default_language;
                    }

                    $description_array['lang'] = $lang;

                    $business_entity['description'] = $description_array;
                }

                $contacts = array();

                $covered_roles = array();

                foreach ( $entity->GetRelatedContacts() as $related_contact )
                {
                    $contact = $related_contact->GetContact();

                    $contact_array = array();

                    // One contact for each role!
                    foreach ( $related_contact->GetRoles() as $role )
                    {
                        if ( ! in_array( $role, $covered_roles ) )
                        {
                            array_push( $covered_roles, $role );

                            $contact_array['useType'] = $role;
                            $contact_array['personName'] = $contact->GetFullName();
                            $contact_array['email'] = $contact->GetEmail();

                            $phone = $contact->GetTelephone();

                            if ( ! empty( $phone ) )
                            {
                                $contact['phone'] = $phone;
                            }

                            array_push( $contacts, $contact_array );
                        }
                    }
                }

                $business_entity['contacts'] = $contacts;


                $params = array( 'authInfo'       => null,
                                 'businessEntity' => $business_entity );

                ob_start();

                $uddi->_api = 'Publish';

                $result = $uddi->save_business( $params );

                if ( _DEBUG )
                {
                    $label = 'save_business ('.$resource_code.')';

                    $this->mInteractions[$label]['req'] = ob_get_contents();
                    $this->mInteractions[$label]['resp'] = htmlspecialchars( $result );
                }

                ob_end_clean();

                if ( $this->_NotOk( $result, $resource_code ) )
                {
                   return false;
                }

                $business_keys = $this->_ExtractBusinessKeys( $result );

                if ( count( $business_keys ) != 1 )
                {
                    $msg = 'Registration failed for resource "'.$resource_code.'": '.
                           'more than one business key returned for business "'.
                           $business_name.'"';
                    TpDiagnostics::Append( CFG_UDDI_ERROR, $msg, DIAG_ERROR );
                    return false;
                }
            }
            else if ( count( $business_keys ) > 1 )
            {
                $msg = 'Registration failed for resource "'.$resource_code.'": '.
                       'found more than one business for "'.$business_name.'"';
                TpDiagnostics::Append( CFG_UDDI_ERROR, $msg, DIAG_ERROR );
                return false;
            }

            $business_key = $business_keys[0];

            $this->mBusinessCache[$business_name] = $business_key;
        }

        return $business_key;

    } // end of member function _GetBusinessKey

    function _GetServiceKey( $uddi, &$rResource, $businessKey )
    {
        $service_key = null;

        $resource_code = $rResource->GetCode();

        $r_metadata =& $this->_GetResourceMetadata( $rResource );

        $default_language = $r_metadata->GetDefaultLanguage();

        $service_lang_string = $this->GetServiceMainTitle( $rResource );

        $service_name = $service_lang_string->GetValue();

        $params = array( 'name' => $service_name, 
                         'findQualifiers' => 'exactNameMatch' );

        ob_start();

        $uddi->_api = 'Inquiry';

        $result = $uddi->find_service( $params );

        if ( _DEBUG )
        {
            $label = 'find_service ('.$resource_code.')';

            $this->mInteractions[$label]['req'] = ob_get_contents();
            $this->mInteractions[$label]['resp'] = htmlspecialchars( $result );
        }

        ob_end_clean();

        if ( $this->_NotOk( $result, $resource_code ) )
        {
            return false;
        }

        $service_infos = $this->_ExtractServiceBusinessPairs( $result );

        $service_key = null;

        foreach ( $service_infos as $service_info )
        {
            if ( $service_info['business_key'] == $businessKey )
            {
                if ( is_null( $service_key ) )
                {
                    $service_key = $service_info['service_key'];
                }
                else
                {
                    $msg = 'Registration failed for resource "'.$resource_code.'": '.
                           'found more than one service associated with the same '.
                           'business and registered as "'.$service_name.'"';
                    TpDiagnostics::Append( CFG_UDDI_ERROR, $msg, DIAG_ERROR );
                    return false;
                }
            }
        }

        if ( is_null( $service_key ) )
        {
            // Register new service

            $name_array = array();

            $name = array();
            $name['content'] = $service_name;

            $lang = $service_lang_string->GetLang();

            if ( empty( $lang ) )
            {
                $lang = $default_language;
            }

            $name['lang'] = $lang;

            array_push( $name_array, $name );

            $params = array( 'businessKey' => $businessKey,
                             'name' => $name_array );

            ob_start();

            $uddi->_api = 'Publish';

            $result = $uddi->save_service( $params );

            if ( _DEBUG )
            {
                $label = 'save_service ('.$resource_code.')';

                $this->mInteractions[$label]['req'] = ob_get_contents();
                $this->mInteractions[$label]['resp'] = htmlspecialchars( $result );
            }

            ob_end_clean();

            if ( $this->_NotOk( $result, $resource_code ) )
            {
                return false;
            }

            $services = $this->_ExtractServiceBusinessPairs( $result );

            foreach ( $services as $service )
            {
                if ( $service['business_key'] == $businessKey )
                {
                    if ( is_null( $service_key ) )
                    {
                        $service_key = $service['service_key'];
                    }
                    else
                    {
                        $msg = 'Registration failed for resource "'.$resource_code.'": '.
                               'more than one service key returned for the same '.
                               'business after trying to save the service';
                        TpDiagnostics::Append( CFG_UDDI_ERROR, $msg, DIAG_ERROR );
                        return false;
                    }
                }
            }

            if ( $service_key == null )
            {
                $msg = 'Registration failed for resource "'.$resource_code.'": '.
                       'no service key returned after attempt to save the service';
                TpDiagnostics::Append( CFG_UDDI_ERROR, $msg, DIAG_ERROR );
                return false;
            }
        }

        return $service_key;

    } // end of member function _GetServiceKey

    function _RegisterBinding( $uddi, &$rResource, $serviceKey, $tmodelKey )
    {
        $resource_code = $rResource->GetCode();

        $params = array( 'serviceKey' => $serviceKey, 
                         'tModelBag'  => $tmodelKey );

        ob_start();

        $uddi->_api = 'Inquiry';

        $result = $uddi->find_binding( $params );

        if ( _DEBUG )
        {
            $label = 'find_binding ('.$resource_code.')';

            $this->mInteractions[$label]['req'] = ob_get_contents();
            $this->mInteractions[$label]['resp'] = htmlspecialchars( $result );
        }

        ob_end_clean();

        if ( $this->_NotOk( $result, $resource_code ) )
        {
            return false;
        }

        $accesspoints = $this->_ExtractBindingAccessPoints( $result );

        $accesspoint = $rResource->GetAccessPoint();

        if ( in_array( $accesspoint, $accesspoints ) )
        {
            $msg = 'Resource "'.$resource_code.'" is already registered with ';
                   'the same accesspoint. No need to re-register.'.
            TpDiagnostics::Append( CFG_UDDI_ERROR, $msg, DIAG_ERROR );
            return false;
        }

        // Save new binding

        $parsed_url = parse_url( $accesspoint );

        $tmodel_instance_infos = array();

        $tmodel_instance_info = array();

        $tmodel_instance_info['tModelKey'] = $tmodelKey;

        array_push( $tmodel_instance_infos, $tmodel_instance_info );

        $params = array( 'authInfo'           => null,
                         'serviceKey'         => $serviceKey, 
                         'accessPoint'        => $accesspoint,
                         'URLType'            => $parsed_url['scheme'],
                         'tModelInstanceInfo' => $tmodel_instance_infos );

        ob_start();

        $uddi->_api = 'Publish';

        $result = $uddi->save_binding( $params );

        if ( _DEBUG )
        {
            $label = 'save_binding ('.$resource_code.')';

            $this->mInteractions[$label]['req'] = ob_get_contents();
            $this->mInteractions[$label]['resp'] = htmlspecialchars( $result );
        }

        ob_end_clean();

        if ( $this->_NotOk( $result, $resource_code ) )
        {
            return false;
        }


        return true;

    } // end of member function _RegisterBinding

    function _NotOk( $soapResponse, $resourceCode=false )
    {
        if ( ! preg_match( '/^HTTP\/[\S]+\s200\sOK/', $soapResponse ) )
        {
            $msg = 'Error communicating with UDDI server';

            if ( $resourceCode )
            {
                $msg .= ' when processing resource "'.$resourceCode.'"';
            }

            TpDiagnostics::Append( CFG_UDDI_ERROR, $msg, DIAG_ERROR );

            return true;
        }

        return false;

    } // end of member function _NotOk

    function &_GetResourceMetadata( &$rResource )
    {
        $r_metadata =& $rResource->GetMetadata();

        if ( ! $r_metadata->IsLoaded() )
        {
            $r_metadata->LoadFromXml( $rResource->GetCode(), 
                                      $rResource->GetMetadataFile() );
        }

        return $r_metadata;

    } // end of member function _GetResourceMetadata

    function GetServiceMainTitle( &$rResource )
    {
        $r_metadata =& $this->_GetResourceMetadata( $rResource );

        return $this->_GetMainLangString( $r_metadata->GetTitles(), $r_metadata->GetDefaultLanguage() );

    } // end of member function GetServiceMainTitle

    function GetNameOfMainBusiness( &$rResource )
    {
        $r_metadata =& $this->_GetResourceMetadata( $rResource );

        $entity = $this->_GetMainEntity( $r_metadata->GetRelatedEntities() );

        if ( $entity == null )
        {
            // What happened??
            return new TpLangString( '?', '' );
        }

        return $this->_GetMainLangString( $entity->GetNames(), $r_metadata->GetDefaultLanguage() );

    } // end of member function GetServiceMainTitle

    /**
     * Returns the "main" TpLangString object from an array of TpLangString objects.
     * Criteria: Name in english or first existing name.
     *
     * @param $options array Array of TpLangString objects
     * @return object Main TpLangString object
     */
    function _GetMainLangString( $options, $default_lang=null )
    {
        $num_options = count( $options );

        if ( $num_options == 0 )
        {
            // No options??
            $main_lang_string = new TpLangString( '?', '' );
        }
        else if ( $num_options == 1 )
        {
            $main_lang_string = $options[0];
        }
        else
        {
            for ( $i = 0; $i < $num_options; ++$i )
            {
                $current_lang = $options[$i]->GetLang();

                $lang = ( empty($current_lang) ) ? $default_lang : $current_lang;

                // Default to first english option
                if ( substr( $lang, 0, 2 ) == 'en' )
                {
                    $main_lang_string = $options[$i];
                    break;
                }
                // Otherwise it will default to the first option
                else if ( $i == 0 )
                {
                    $main_lang_string = $options[$i];
                }
            }
        }

        return $main_lang_string;

    } // end of member function _GetMainLangString

    function _ExtractTmodelKeys( $soapResponse )
    {
        $tmodel_keys = array();

        $matches = array();

        preg_match_all( "/<tModelInfo tModelKey=\"([\w:-]+)\">/", 
                        $soapResponse, $matches, PREG_SET_ORDER );

        foreach ( $matches as $val ) 
        {
            array_push( $tmodel_keys, $val[1] );
        }

        return $tmodel_keys;

    } // end of member function _ExtractTmodelKeys

    function _ExtractBusinessKeys( $soapResponse )
    {
        $business_keys = array();

        $matches = array();

        preg_match_all( "/<[\w]+ businessKey=\"([\w:-]+)\">/", 
                        $soapResponse, $matches, PREG_SET_ORDER );

        foreach ( $matches as $val ) 
        {
            array_push( $business_keys, $val[1] );
        }

        return $business_keys;

    } // end of member function _ExtractBusinessKeys

    function _ExtractServiceBusinessPairs( $soapResponse )
    {
        $service_infos = array();

        $matches = array();

        preg_match_all( "/<[\w]+ serviceKey=\"([\w:-]+)\" businessKey=\"([\w:-]+)\">/", 
                        $soapResponse, $matches, PREG_SET_ORDER );

        foreach ( $matches as $val ) 
        {
            $pair = array();

            $pair['business_key'] = $val[2];
            $pair['service_key']  = $val[1];

            array_push( $service_infos, $pair );
        }

        return $service_infos;

    } // end of member function _ExtractServiceBusinessPairs

    function _ExtractBindingAccessPoints( $soapResponse )
    {
        $accesspoints = array();

        $matches = array();

        preg_match_all( "/<accessPoint URLType=\"([\w]+)\">([^<]+)<\/accessPoint>/", 
                        $soapResponse, $matches, PREG_SET_ORDER );

        foreach ( $matches as $val ) 
        {
            array_push( $accesspoints, $val[2] );
        }

        return $accesspoints;

    } // end of member function _ExtractBindingAccessPoints

    function _GetMainEntity( $relatedEntities )
    {
        $entity = null;

        $num_entities = count( $relatedEntities );

        if ( $num_entities == 0 )
        {
            // No entities??
        }
        else if ( $num_entities == 1 )
        {
            $entity = $relatedEntities[0]->GetEntity();
        }
        else
        {
            for ( $i = 0; $i < $num_entities; ++$i )
            {
                $roles = $relatedEntities[$i]->GetRoles();

                // Default to technical host
                if ( in_array( 'technical host', $roles ) )
                {
                    $entity = $relatedEntities[$i]->GetEntity();
                    break;
                }
                // Otherwise it will default to the first entity
                else if ( $i == 0 )
                {
                    $entity = $relatedEntities[$i]->GetEntity();
                }
            }
        }

        return $entity;

    } // end of member function _GetMainEntity

} // end of TpUddiForm
?>
