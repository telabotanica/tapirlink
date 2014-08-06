<?php
/**
 * $Id: TpMetadataForm.php 1986 2009-03-21 20:27:22Z rdg $
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

require_once('TpWizardForm.php');
require_once('TpConfigUtils.php');
require_once('TpHtmlUtils.php');
require_once('TpDiagnostics.php');
require_once('TpResources.php');
require_once('TpResource.php');

class TpMetadataForm extends TpWizardForm
{
    var $mStep = 1;
    var $mLabel = 'Metadata';

    function TpMetadataForm( )
    {

    } // end of member function TpMetadataForm

    function LoadDefaults( )
    {
        $r_metadata =& $this->mResource->GetMetadata();

        if ( $this->mResource->HasMetadata() )
        {
            $msg = "If you have just imported this resource, please complete at least the mandatory fields below\n(with a ".TP_MANDATORY_FIELD_FLAG."before the label) and revise the next forms to finish the configuration process.";

            $metadata_file = $this->mResource->GetMetadataFile();

            $r_metadata->LoadFromXml( $this->mResource->GetCode(), $metadata_file );

            $r_metadata->SetAccesspoint( $this->mResource->GetAccesspoint() );

            // Show possible errors
            $r_metadata->Validate();

            if ( TpDiagnostics::Count() )
            {
                $msg .= "\nThe errors below may also give you more information.";
            }

            $this->SetMessage( $msg );
        }
        else
        {
            $this->SetMessage( "Configuring new resources involves six steps.\nFirst you should fill in this form with metadata about the resource.\nYou can find more information about each field by clicking over the label.\nMandatory fields have ".TP_MANDATORY_FIELD_FLAG."before the label." );

            $r_metadata->LoadDefaults();
        }

    } // end of member function LoadDefaults

    function LoadFromSession( )
    {
        $r_metadata =& $this->mResource->GetMetadata();
        $r_metadata->LoadFromSession();

    } // end of member function LoadFromSession

    function LoadFromXml( )
    {
        $r_metadata =& $this->mResource->GetMetadata();

        if ( $this->mResource->HasMetadata() )
        {
            $metadata_file = $this->mResource->GetMetadataFile();

            $r_metadata->LoadFromXml( $this->mResource->GetCode(), $metadata_file );

            $accesspoint = $this->mResource->GetAccesspoint();

            if ( ! empty( $accesspoint ) )
            {
                $r_metadata->SetAccesspoint( $accesspoint );
            }

            // Call this method just to show possible errors
            $this->mResource->ConfiguredMetadata();
        }
        else
        {
            $err_str = 'There is no metadata XML configuration to be loaded!';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $err_str, DIAG_ERROR );
            return;
        }

    } // end of member function LoadFromXml

    function GetJavascript( )
    {
        return "function changedLocalId()\n".
               "  {\n".
               "    var currentAccesspoint = document.wizard.accesspoint.value;\n".
               "    var currentLocalId = document.wizard.id.value;\n".
               "    var parts = currentAccesspoint.split('/');\n".
               "    if ( parts.length > 2 && ( parts[parts.length-3] == 'tapir.php' || parts[parts.length-2] == 'tapir.php' ) )\n".
               "    {\n".
               "      var newAccessPoint = '';\n".
               "      for ( i = 0; i < parts.length; i++ )\n".
               "      {\n".
               "          if ( i > 0 )\n".
               "          {\n".
               "            newAccessPoint += '/';\n".
               "            if ( parts[i-1] == 'tapir.php' )\n".
               "            {\n".
               "              newAccessPoint += currentLocalId;\n".
               "            }\n".
               "            else\n".
               "            {\n".
               "              newAccessPoint += parts[i];\n".
               "            }\n".
               "          }\n".
               "          else\n".
               "          {\n".
               "            newAccessPoint += parts[i];\n".
               "          }\n".
               "      }\n".
               "      document.wizard.accesspoint.value = newAccessPoint;\n".
               "    }\n".
               "  }\n";

    } // end of member function GetJavascript

    function DisplayForm( )
    {
        $r_metadata =& $this->mResource->GetMetadata();

        include('TpMetadataForm.tmpl.php');

    } // end of member function DisplayForm

    function HandleEvents( )
    {
        // Clicked next or update
        if ( isset( $_REQUEST['next'] ) or isset( $_REQUEST['update'] ) )
        {
            $update_resources = false;

            $r_metadata =& $this->mResource->GetMetadata();

            if ( ! $r_metadata->Validate() )
            {
                return;
            }

            if ( isset( $_REQUEST['next'] ) )
            {
                // Set code so that GetMetadataFile (called next) can determine
                // the name of the file for new resources.
                $this->mResource->SetCode( $r_metadata->GetId() );
            }

            if ( ! $this->mResource->SaveMetadata() )
            {
                return;
            }

            if ( isset( $_REQUEST['update'] ) )
            {
                $this->SetMessage( 'Changes successfully saved!' );
            }
            else // Clicked Next
            {
                $r_resources =& TpResources::GetInstance();

                if ( $r_resources->GetResource( $r_metadata->GetId() ) == null )
                {
                    $r_resources->AddResource( $this->mResource );
                }

                // Saving here avoids the configurator to later try to find
                // the removed entry and therefore raise a warning
                if ( ! $r_resources->Save() )
                {
                    return;
                }
            }

            // Update resource code
            $_REQUEST['resource'] = $r_metadata->GetId();

            $this->mDone = true;
        }

    } // end of member function HandleEvents

    function GetHtmlLabel( $labelId, $required )
    {
        $label = '?';
        $doc = '';

        $css = ( $required ) ? 'label_required' : 'label';

        if ( $labelId == 'id')
        {
            $label = 'Local ID';
            $doc = 'A local identifier for the resource. This should be a short '.
                   'sequence of characters that can be used directly in URLs. It '.
                   'will be part of the default accesspoint.';
        }
        else if ( $labelId == 'accesspoint')
        {
            $label = 'Accesspoint';
            $doc = 'URL of the service. The default URL has this form:'.
                   "\nhttp://hostname/tapirlink/tapir.php/local_id/\n".
                   'Replace &quot;hostname&quot; with the correct value, '.
                   '&quot;local_id&quot; with the resource local id, and '.
                   '&quot;tapirlink&quot; with the corresponding '.
                   'path that points to the &quot;tapirlink/www&quot; directory. '.
                   'You may also consider creating a PURL (see: http://purl.org/) '.
                   'to wrap your accesspoint.';
        }
        else if ( $labelId == 'default_language')
        {
            $label = 'Default language for the metadata fields below';
            $doc = 'Default language to be considered for all '.
                   'language aware metadata fields in this form '.
                   '(when not already specified at the field level).';
        }
        else if ( $labelId == 'title')
        {
            $label = 'Title';
            $doc = 'A title (name) for the resource.';
        }
        else if ( $labelId == 'description')
        {
            $label = 'Description';
            $doc = 'Description may include but is not limited to: an abstract, table of contents, reference to a graphical representation of content or a free-text account of the content.';
        }
        else if ( $labelId == 'subjects')
        {
            $label = 'Subjects';
            $doc = 'Subject and Keywords. Typically, a Subject will be expressed as keywords, key phrases or classification codes that describe a topic	of the resource. Recommended best practice is to select a value from a controlled vocabulary or formal classification scheme.';
        }
        else if ( $labelId == 'bibliographicCitation')
        {
            $label = 'Citation';
            $doc = 'Recommended practice is to include sufficient bibliographic detail to identify the resource as unambiguously as possible, whether or not the citation is in a standard form.';
        }
        else if ( $labelId == 'rights')
        {
            $label = 'Rights';
            $doc = 'Information about who can access the resource or an indication of its security status.';
        }
        else if ( $labelId == 'relatedEntities')
        {
            $label = 'Related entities';
            $doc = 'Entities (companies, organisations, institutions) related to this service with their respective roles, e.g. publisher, data supplier.';
        }
        else if ( $labelId == 'entityId')
        {
            $label = 'Entity identifier';
            $doc = 'A global unique identifier for the entity. It allows '.
                   'the same entity to be recognized across different '.
                   'providers. This is necessary because at a global level '.
                   'different entities can have the same name or acronym. '.
                   'There is no particular format for identifiers. Communities '.
                   'and networks are encouraged to define a standard way '.
                   'of assigning global unique identifiers for entities. '.
                   'It can be for instance the uuid of the corresponding '.
                   'business entity in a UDDI registry, the domain of the '.
                   'organization on the web, an LSID, etc. In the absence '.
                   'of a standard, just put the name or acronym or create '.
                   'some identifier.';
        }
        else if ( $labelId == 'entityType')
        {
            $label = 'Type';
            $doc = 'Entity type (person or organization).';
        }
        else if ( $labelId == 'acronym')
        {
            $label = 'Acronym';
            $doc = 'An acronym (code or short word) for the entity.';
        }
        else if ( $labelId == 'entityName')
        {
            $label = 'Name';
            $doc = 'Entity name.';
        }
        else if ( $labelId == 'entityDescription')
        {
            $label = 'Description';
            $doc = 'Entity description.';
        }
        else if ( $labelId == 'logoURL')
        {
            $label = 'Logo (URL)';
            $doc = 'A URL to a small logo of the entity.';
        }
        else if ( $labelId == 'address')
        {
            $label = 'Address';
            $doc = 'Address where the entity is located. It can include street name, street number, district, city, county and other complements. Use the next fields to specify state/province, country and zip code.';
        }
        else if ( $labelId == 'regionCode')
        {
            $label = 'Region';
            $doc = 'Region (e.g., state or province) of the specified address. Use standard abbreviation accepted in the country.';
        }
        else if ( $labelId == 'countryCode')
        {
            $label = 'Country';
            $doc = 'Country of the specified address.';
        }
        else if ( $labelId == 'zipCode')
        {
            $label = 'Zip code';
            $doc = 'Zip or postal code of the specified address.';
        }
        else if ( $labelId == 'relatedInformation')
        {
            $label = 'Related information (URL)';
            $doc = 'A URL where more information about this entity can found.';
        }
        else if ( $labelId == 'longitude')
        {
            $label = 'Longitude';
            $doc = 'Longitude where the entity is located (in decimal degrees using datum WGS84).';
        }
        else if ( $labelId == 'latitude')
        {
            $label = 'Latitude';
            $doc = 'Latitude where the entity is located (in decimal degrees using datum WGS84).';
        }
        else if ( $labelId == 'fullName')
        {
            $label = 'Full name';
            $doc = 'Full name.';
        }
        else if ( $labelId == 'contactTitle')
        {
            $label = 'Job Title';
            $doc = 'Job title (Curator, Director, etc.).';
        }
        else if ( $labelId == 'telephone')
        {
            $label = 'Telephone';
            $doc = 'Telephone number.';
        }
        else if ( $labelId == 'email')
        {
            $label = 'E-mail';
            $doc = 'E-mail address.';
        }
        else if ( $labelId == 'language')
        {
            $label = 'Main language of the data provided by this resource';
            $doc = 'Main language of the content in the underlying database.';
        }
        else if ( $labelId == 'indexingPreferences')
        {
            $label = 'Indexing preferences';
            $doc = 'Preferences related to external indexing.';
        }
        else if ( $labelId == 'startTime')
        {
            $label = 'Start time';
            $doc = 'Prefered starting time for an external indexing procedure. '.
                   '(note: 12AM means midnight, 12PM means midday)';
        }
        else if ( $labelId == 'maxDuration')
        {
            $label = 'Max. duration';
            $doc = 'Maximum acceptable duration of an external indexing procedure.';
        }
        else if ( $labelId == 'frequency')
        {
            $label = 'Frequency';
            $doc = 'Maximum acceptable frequency for external indexing procedures.';
        }
        else if ( $labelId == 'entityRoles')
        {
            $label = 'Roles';
            $doc = 'Roles of the entity for this resource.';
        }
        else if ( $labelId == 'relatedContacts')
        {
            $label = 'Related contacts';
            $doc = 'Contacts (people) related to the entity.';
        }
        else if ( $labelId == 'contactRoles')
        {
            $label = 'Roles';
            $doc = 'Roles of the contact for this resource.';
        }

        $js = sprintf("onClick=\"javascript:window.open('help.php?name=%s&amp;doc=%s','help','width=400,height=250,menubar=no,toolbar=no,scrollbars=yes,resizable=yes,personalbar=no,locationbar=no,statusbar=no').focus(); return false;\" onMouseOver=\"javascript:window.status='%s'; return true;\" onMouseOut=\"window.status=''; return true;\"", $label, urlencode($doc), urlencode($doc));

        $form_label = $label;

        $note = ( $required ) ? TP_MANDATORY_FIELD_FLAG : '';

        $html = sprintf('<span class="%s">%s<a href="help.php?name=%s&amp;doc=%s" %s>%s:</a>&nbsp;</span>',
                        $css, $note, $label, urlencode($doc), $js, $form_label);

        return $html;

    } // end of member function GetHtmlLabel

    function GetOptions( $id )
    {
        $options = array();

        if ( $id == 'lang')
        {
            if ( ! defined( 'TP_LANG_OPTIONS' ) )
            {
                $options = array('en' => 'English',
                                 'fr' => 'French',
                                 'de' => 'German',
                                 'pt' => 'Portuguese',
                                 'es' => 'Spanish');
            }
            else
            {
                $options = unserialize( TP_LANG_OPTIONS );
            }

            $options = array_merge( array('' => '-- language --'), $options );
        }
        else if ( $id == 'content_lang')
        {
            $options = $this->GetOptions( 'lang' );

            $options['zxx'] = 'No linguistic content';
        }
        else if ( $id == 'entityRoles')
        {
            $options = array('data supplier'  => 'Data Supplier',
                             'technical host' => 'Technical Host');
        }
        else if ( $id == 'entityType')
        {
            $options = array('organization' => 'Organization',
                             'person'       => 'Person');
        }
        else if ( $id == 'contactRoles')
        {
            $options = array('data administrator'   => 'Data Administrator',
                             'system administrator' => 'System Administrator');
        }
        else if ( $id == 'hour')
        {
            $options = array(''   => '--',
                             '1'  => '1',
                             '2'  => '2',
                             '3'  => '3',
                             '4'  => '4',
                             '5'  => '5',
                             '6'  => '6',
                             '7'  => '7',
                             '8'  => '8',
                             '9'  => '9',
                             '10' => '10',
                             '11' => '11',
                             '12' => '12');
        }
        else if ( $id == 'ampm')
        {
            $options = array(''   => '--',
                             'AM' => 'AM',
                             'PM' => 'PM');
        }
        else if ( $id == 'timezone')
        {
            $options = array(''       => '---',
                             'GMT0'   => 'GMT0',
                             'GMT+1'  => 'GMT+1',
                             'GMT+2'  => 'GMT+2',
                             'GMT+3'  => 'GMT+3',
                             'GMT+4'  => 'GMT+4',
                             'GMT+5'  => 'GMT+5',
                             'GMT+6'  => 'GMT+6',
                             'GMT+7'  => 'GMT+7',
                             'GMT+8'  => 'GMT+8',
                             'GMT+9'  => 'GMT+9',
                             'GMT+10' => 'GMT+10',
                             'GMT+11' => 'GMT+11',
                             'GMT+12' => 'GMT+12',
                             'GMT-1'  => 'GMT-1',
                             'GMT-2'  => 'GMT-2',
                             'GMT-3'  => 'GMT-3',
                             'GMT-4'  => 'GMT-4',
                             'GMT-5'  => 'GMT-5',
                             'GMT-6'  => 'GMT-6',
                             'GMT-7'  => 'GMT-7',
                             'GMT-8'  => 'GMT-8',
                             'GMT-9'  => 'GMT-9',
                             'GMT-10' => 'GMT-10',
                             'GMT-11' => 'GMT-11',
                             'GMT-12' => 'GMT-12',
                             'GMT-13' => 'GMT-13',
                             'GMT-14' => 'GMT-14');
        }
        else if ( $id == 'frequency')
        {
            $options = array(''    => '----',
                             'P1D' => 'daily',
                             'P7D' => 'weekly',
                             'P1M' => 'monthly',
                             'P2M' => 'every 2 months',
                             'P3M' => 'every 3 months',
                             'P6M' => 'every 6 months');
        }
        else if ( $id == 'maxDuration')
        {
            $options = array(''      => '---',
                             'PT1H'  => '1 hour',
                             'PT2H'  => '2 hours',
                             'PT5H'  => '5 hours',
                             'PT10H' => '10 hours');
        }
        else if ( $id == 'countryCodes')
        {
            $options = array(''      => '---',
'AF' => 'AFGHANISTAN',
'AX' => 'ÅLAND ISLANDS',
'AL' => 'ALBANIA',
'DZ' => 'ALGERIA',
'AS' => 'AMERICAN SAMOA',
'AD' => 'ANDORRA',
'AO' => 'ANGOLA',
'AI' => 'ANGUILLA',
'AQ' => 'ANTARCTICA',
'AG' => 'ANTIGUA AND BARBUDA',
'AR' => 'ARGENTINA',
'AM' => 'ARMENIA',
'AW' => 'ARUBA',
'AU' => 'AUSTRALIA',
'AT' => 'AUSTRIA',
'AZ' => 'AZERBAIJAN',
'BS' => 'BAHAMAS',
'BH' => 'BAHRAIN',
'BD' => 'BANGLADESH',
'BB' => 'BARBADOS',
'BY' => 'BELARUS',
'BE' => 'BELGIUM',
'BZ' => 'BELIZE',
'BJ' => 'BENIN',
'BM' => 'BERMUDA',
'BT' => 'BHUTAN',
'BO' => 'BOLIVIA',
'BA' => 'BOSNIA AND HERZEGOVINA',
'BW' => 'BOTSWANA',
'BV' => 'BOUVET ISLAND',
'BR' => 'BRAZIL',
'IO' => 'BRITISH INDIAN OCEAN TERRITORY',
'BN' => 'BRUNEI DARUSSALAM',
'BG' => 'BULGARIA',
'BF' => 'BURKINA FASO',
'BI' => 'BURUNDI',
'KH' => 'CAMBODIA',
'CM' => 'CAMEROON',
'CA' => 'CANADA',
'CV' => 'CAPE VERDE',
'KY' => 'CAYMAN ISLANDS',
'CF' => 'CENTRAL AFRICAN REPUBLIC',
'TD' => 'CHAD',
'CL' => 'CHILE',
'CN' => 'CHINA',
'CX' => 'CHRISTMAS ISLAND',
'CC' => 'COCOS (KEELING) ISLANDS',
'CO' => 'COLOMBIA',
'KM' => 'COMOROS',
'CG' => 'CONGO',
'CD' => 'CONGO, THE DEMOCRATIC REPUBLIC OF THE',
'CK' => 'COOK ISLANDS',
'CR' => 'COSTA RICA',
'CI' => 'CÔTE D\'IVOIRE',
'HR' => 'CROATIA',
'CU' => 'CUBA',
'CY' => 'CYPRUS',
'CZ' => 'CZECH REPUBLIC',
'DK' => 'DENMARK',
'DJ' => 'DJIBOUTI',
'DM' => 'DOMINICA',
'DO' => 'DOMINICAN REPUBLIC',
'EC' => 'ECUADOR',
'EG' => 'EGYPT',
'SV' => 'EL SALVADOR',
'GQ' => 'EQUATORIAL GUINEA',
'ER' => 'ERITREA',
'EE' => 'ESTONIA',
'ET' => 'ETHIOPIA',
'FK' => 'FALKLAND ISLANDS (MALVINAS)',
'FO' => 'FAROE ISLANDS',
'FJ' => 'FIJI',
'FI' => 'FINLAND',
'FR' => 'FRANCE',
'GF' => 'FRENCH GUIANA',
'PF' => 'FRENCH POLYNESIA',
'TF' => 'FRENCH SOUTHERN TERRITORIES',
'GA' => 'GABON',
'GM' => 'GAMBIA',
'GE' => 'GEORGIA',
'DE' => 'GERMANY',
'GH' => 'GHANA',
'GI' => 'GIBRALTAR',
'GR' => 'GREECE',
'GL' => 'GREENLAND',
'GD' => 'GRENADA',
'GP' => 'GUADELOUPE',
'GU' => 'GUAM',
'GT' => 'GUATEMALA',
'GG' => 'GUERNSEY',
'GN' => 'GUINEA',
'GW' => 'GUINEA-BISSAU',
'GY' => 'GUYANA',
'HT' => 'HAITI',
'HM' => 'HEARD ISLAND AND MCDONALD ISLANDS',
'VA' => 'HOLY SEE (VATICAN CITY STATE)',
'HN' => 'HONDURAS',
'HK' => 'HONG KONG',
'HU' => 'HUNGARY',
'IS' => 'ICELAND',
'IN' => 'INDIA',
'ID' => 'INDONESIA',
'IR' => 'IRAN',
'IQ' => 'IRAQ',
'IE' => 'IRELAND',
'IM' => 'ISLE OF MAN',
'IL' => 'ISRAEL',
'IT' => 'ITALY',
'JM' => 'JAMAICA',
'JP' => 'JAPAN',
'JE' => 'JERSEY',
'JO' => 'JORDAN',
'KZ' => 'KAZAKHSTAN',
'KE' => 'KENYA',
'KI' => 'KIRIBATI',
'KP' => 'KOREA, DEMOCRATIC PEOPLE\'S REPUBLIC OF',
'KR' => 'KOREA, REPUBLIC OF',
'KW' => 'KUWAIT',
'KG' => 'KYRGYZSTAN',
'LA' => 'LAO',
'LV' => 'LATVIA',
'LB' => 'LEBANON',
'LS' => 'LESOTHO',
'LR' => 'LIBERIA',
'LY' => 'LIBYAN ARAB JAMAHIRIYA',
'LI' => 'LIECHTENSTEIN',
'LT' => 'LITHUANIA',
'LU' => 'LUXEMBOURG',
'MO' => 'MACAO',
'MK' => 'MACEDONIA',
'MG' => 'MADAGASCAR',
'MW' => 'MALAWI',
'MY' => 'MALAYSIA',
'MV' => 'MALDIVES',
'ML' => 'MALI',
'MT' => 'MALTA',
'MH' => 'MARSHALL ISLANDS',
'MQ' => 'MARTINIQUE',
'MR' => 'MAURITANIA',
'MU' => 'MAURITIUS',
'YT' => 'MAYOTTE',
'MX' => 'MEXICO',
'FM' => 'MICRONESIA',
'MD' => 'MOLDOVA',
'MC' => 'MONACO',
'MN' => 'MONGOLIA',
'ME' => 'MONTENEGRO',
'MS' => 'MONTSERRAT',
'MA' => 'MOROCCO',
'MZ' => 'MOZAMBIQUE',
'MM' => 'MYANMAR',
'NA' => 'NAMIBIA',
'NR' => 'NAURU',
'NP' => 'NEPAL',
'NL' => 'NETHERLANDS',
'AN' => 'NETHERLANDS ANTILLES',
'NC' => 'NEW CALEDONIA',
'NZ' => 'NEW ZEALAND',
'NI' => 'NICARAGUA',
'NE' => 'NIGER',
'NG' => 'NIGERIA',
'NU' => 'NIUE',
'NF' => 'NORFOLK ISLAND',
'MP' => 'NORTHERN MARIANA ISLANDS',
'NO' => 'NORWAY',
'OM' => 'OMAN',
'PK' => 'PAKISTAN',
'PW' => 'PALAU',
'PS' => 'PALESTINIAN TERRITORY, OCCUPIED',
'PA' => 'PANAMA',
'PG' => 'PAPUA NEW GUINEA',
'PY' => 'PARAGUAY',
'PE' => 'PERU',
'PH' => 'PHILIPPINES',
'PN' => 'PITCAIRN',
'PL' => 'POLAND',
'PT' => 'PORTUGAL',
'PR' => 'PUERTO RICO',
'QA' => 'QATAR',
'RE' => 'RÉUNION',
'RO' => 'ROMANIA',
'RU' => 'RUSSIAN FEDERATION',
'RW' => 'RWANDA',
'BL' => 'SAINT BARTHÉLEMY',
'SH' => 'SAINT HELENA',
'KN' => 'SAINT KITTS AND NEVIS',
'LC' => 'SAINT LUCIA',
'MF' => 'SAINT MARTIN',
'PM' => 'SAINT PIERRE AND MIQUELON',
'VC' => 'SAINT VINCENT AND THE GRENADINES',
'WS' => 'SAMOA',
'SM' => 'SAN MARINO',
'ST' => 'SAO TOME AND PRINCIPE',
'SA' => 'SAUDI ARABIA',
'SN' => 'SENEGAL',
'RS' => 'SERBIA',
'SC' => 'SEYCHELLES',
'SL' => 'SIERRA LEONE',
'SG' => 'SINGAPORE',
'SK' => 'SLOVAKIA',
'SI' => 'SLOVENIA',
'SB' => 'SOLOMON ISLANDS',
'SO' => 'SOMALIA',
'ZA' => 'SOUTH AFRICA',
'GS' => 'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS',
'ES' => 'SPAIN',
'LK' => 'SRI LANKA',
'SD' => 'SUDAN',
'SR' => 'SURINAME',
'SJ' => 'SVALBARD AND JAN MAYEN',
'SZ' => 'SWAZILAND',
'SE' => 'SWEDEN',
'CH' => 'SWITZERLAND',
'SY' => 'SYRIAN ARAB REPUBLIC',
'TW' => 'TAIWAN, PROVINCE OF CHINA',
'TJ' => 'TAJIKISTAN',
'TZ' => 'TANZANIA',
'TH' => 'THAILAND',
'TL' => 'TIMOR-LESTE',
'TG' => 'TOGO',
'TK' => 'TOKELAU',
'TO' => 'TONGA',
'TT' => 'TRINIDAD AND TOBAGO',
'TN' => 'TUNISIA',
'TR' => 'TURKEY',
'TM' => 'TURKMENISTAN',
'TC' => 'TURKS AND CAICOS ISLANDS',
'TV' => 'TUVALU',
'UG' => 'UGANDA',
'UA' => 'UKRAINE',
'AE' => 'UNITED ARAB EMIRATES',
'GB' => 'UNITED KINGDOM',
'US' => 'UNITED STATES',
'UM' => 'UNITED STATES MINOR OUTLYING ISLANDS',
'UY' => 'URUGUAY',
'UZ' => 'UZBEKISTAN',
'VU' => 'VANUATU',
'VE' => 'VENEZUELA',
'VN' => 'VIET NAM',
'VG' => 'VIRGIN ISLANDS, BRITISH',
'VI' => 'VIRGIN ISLANDS, U.S.',
'WF' => 'WALLIS AND FUTUNA',
'EH' => 'WESTERN SAHARA',
'YE' => 'YEMEN',
'ZM' => 'ZAMBIA',
'ZW' => 'ZIMBABWE');
        }

        return $options;

    } // end of member function GetOptions

} // end of TpMetadataForm
?>