<?php
/**
 * $Id: TpConfigUtils.php 643 2008-04-22 19:23:46Z rdg $
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

require_once('TpDiagnostics.php');
require_once(TP_XPATH_LIBRARY);
require_once('TpLangString.php');


class TpConfigUtils // only class methods
{
    /**
     * Derived from PHP XPath library code.
     * Writes a string into a file.
     *
     * @param $content Content
     * @param $file File name (including path)
     * @return True fr success, false otherwise
     */
    static function WriteToFile( $content, $file )
    {
        $status = false;

        do
        { // try-block

            // Did we open the file ok?
            if ( ! ( $h_file = fopen( $file, "wb" ) ) )
            {
                $err_str = "Failed to open file: '$file'.";
                TpDiagnostics::Append( DC_IO_ERROR, $err_str, DIAG_ERROR );
                break; // try-block
            }

            if ( ! ( PHP_OS == 'Windows_95' || PHP_OS == 'Windows_98' ) )
            {
                // Lock the file
                if ( ! flock( $h_file, LOCK_EX + LOCK_NB ) )
                {
                    $err_str = "Couldn't get an exclusive lock on file: '$file'.";
                    TpDiagnostics::Append( DC_IO_ERROR, $err_str, DIAG_ERROR );
                    break; // try-block
                }
            }

            $bytes_written = fwrite( $h_file, $content );

            if ( $bytes_written != strlen( $content ) )
            {
                $err_str = "Error when writting file: '$file'.";
                TpDiagnostics::Append( DC_IO_ERROR, $err_str, DIAG_ERROR );
                break; // try-block
            }

            // Flush and unlock the file
            @fflush( $h_file );
            $status = true;

        } while( FALSE );

        @flock( $h_file, LOCK_UN );
        @fclose( $h_file );

        // Sanity check the produced file.
        clearstatcache();

        if ( $status && ( filesize( $file ) < strlen( $content ) ) )
        {
            $err_str = "Error when writting file: '$file'.";
            TpDiagnostics::Append( DC_IO_ERROR, $err_str, DIAG_ERROR );
            $status = false;
        }

        return $status;

    } // end of WriteToFile

    /**
     * Writes a piece of XML string (related to a single element) into a
     * specific place in a file. Requires a previous sibling element.
     *
     * @param $content Piece of XML content (single element, simple or complex)
     * @param $currentXpath XPath to be element that will be substituted
     * @param $prevSiblingXpath XPath of the previous sibling element
     * @param $file XML file name (including path)
     * @return True on success, false otherwise
     */
    static function WriteXmlPiece( $content, $currentXpath, $prevXpath, $file )
    {
        $xparser = new XPath();
        $xparser->setVerbose( 0 );
        $xparser->setXmlOption( XML_OPTION_CASE_FOLDING, false );
        $xparser->setXmlOption( XML_OPTION_SKIP_WHITE, true );

        if ( ! $xparser->importFromFile( $file ) )
        {
            $xml_error = $xparser->getLastError();
            $error = 'Could not load the XML file '.$file.'.' . 'Error is ' . $xparser->getLastError();
            TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );
            return false;
        }

        // Remove tag if there's one
        if ( $xparser->match( $currentXpath ) )
        {
            if ( ! $xparser->removeChild( $currentXpath ) )
            {
                $error = sprintf( 'Could not prepare XML file (%s) to be updated: %s',
                                  $file, $xparser->getLastError() );
                TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );
                return false;
            }
        }

        $same_level = false;

        if ( substr_count( $currentXpath, "/" ) == substr_count( $prevXpath, "/" ) )
        {
            $same_level = true;
        }

        // Add new tag

        if ( $same_level )
        {
            if ( ! $xparser->insertChild( $prevXpath, $content, false) )
            {
                $error = sprintf( 'Could not update XML content in "%s": %s',
                                  $file, $xparser->getLastError() );
                TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );
                return false;
            }
        }
        else
        {
            if ( ! $xparser->appendChild( $prevXpath, $content, false) )
            {
                $error = sprintf( 'Could not update XML content in "%s": %s',
                                  $file, $xparser->getLastError() );
                TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );
                return false;
            }
        }

        // Save
        if ( ! $xparser->exportToFile( $file ) )
        {
            $error = sprintf( 'Could not update XML content in "%s": %s',
                              $file, $xparser->getLastError() );
            TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );
            return false;
        }

        return true;

    } // end of WriteXmlPiece

     static function ValidateLangSection( $sectionName, $langStrings, $raiseErrors, $mandatoryField=true, $defaultLang=null )
    {
        $errors = array();

        $langs = array();

        $numStrings = count( $langStrings );

        foreach ( $langStrings as $lang_string )
        {
            // No empty value if it's a mandatory field
            if ( $mandatoryField and mb_strlen( $lang_string->GetValue() ) == 0 )
            {
                array_push( $errors, 'at least one value is empty' );
            }

            $lang = $lang_string->GetLang();

            // No empty lang if there's no default lang specified
            if ( $defaultLang == null and strlen( $lang ) == 0 )
            {
                array_push( $errors, 'the language must be specified when '."\n".
                                     'there is no default language for the '.
                                     'entire metadata' );
            }

            // No multiple values with more than one empty lang
            if ( $numStrings > 1 and strlen( $lang ) == 0 )
            {
                array_push( $errors, 'the language must be specified when '.
                                     'there are multiple values' );
            }

            // No duplicate langs
            if ( in_array( $lang, $langs ) )
            {
                array_push( $errors, 'each value must be associated with a '.
                                     'distinct language' );
            }

            array_push( $langs, $lang );
        }

        $errors = array_unique( $errors );

        if ( count( $errors ) > 0 )
        {
            if ( $raiseErrors )
            {
                $error = 'Section "'.$sectionName.'" is incorrect: '.
                         implode( ',', $errors ).'.';
                TpDiagnostics::Append( CFG_DATA_VALIDATION_ERROR, $error, DIAG_ERROR );
            }
            return false;
        }

        return true;

    } // end of ValidateLangSection

    static function GetFieldType( $adodbField )
    {
        require_once(TP_ADODB_LIBRARY);
        require_once('TpConceptMapping.php');

        static $adodb_record_set;

        if ( ! is_object( $adodb_record_set ) )
        {
            $adodb_record_set = new ADORecordSet(0);
        }

        $meta_type = $adodb_record_set->MetaType( $adodbField );

        if ( $meta_type == 'C' or $meta_type == 'X' )
        {
            return TYPE_TEXT;
        }
        else if ( $meta_type == 'N' or $meta_type == 'I' )
        {
            return TYPE_NUMERIC;
        }
        else if ( $meta_type == 'D' )
        {
            return TYPE_DATE;
        }
        else if ( $meta_type == 'T' )
        {
            return TYPE_DATETIME;
        }

        return '?';

    } // end of GetFieldType

    /**
     * Returns a unique identifier for the service. This function does not
     * return the URL of the service! It's just a way to generate an identifier
     * that can distinguish between different instances of TapirLink.
     * Note: the service id is used as a session name, so it may not contain
     * characters such as dots.
     */
    static function GetServiceId()
    {
        $domain = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME']:'localhost';
        $port   = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT']:'80';
        $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME']:'/tapir.php';
        $protocol = ( isset($_SERVER['HTTPS']) and
                      ! empty($_SERVER['HTTPS']) ) ? 'https':'http';

        $s = $protocol.'://'.$domain.':'.$port.$script;

        return strtr( $s, '.', '_' );

    } // end of GetServiceId

    /**
     * Returns the primitive XSD type given a pair typename/namespace, or
     * a full type string (namespace concatenated with typename. Only
     * works for types that are already under the XSD namespace.
     * Null is returned if the primitive type could not be determined.
     */
    static function GetPrimitiveXsdType( $typeStr, $ns=null )
    {
        $xsd_namespace = 'http://www.w3.org/2001/XMLSchema';

        // If type contains namespace
        if ( is_null( $ns ) )
        {
            if ( strlen( $typeStr ) < 32 ) // size of xsd_namespace
            {
                return null;
            }

            $ns = substr( $typeStr, 0, 32 );

            $type_name = substr( $typeStr, 33 ); // skip separator
        }
        else
        {
            $type_name = $typeStr;
        }

        if ( $ns == $xsd_namespace )
        {
            switch ( $type_name )
            {
                case 'anyURI':
                case 'boolean':
                case 'base64Binary':
                case 'date':
                case 'dateTime':
                case 'decimal':
                case 'double':
                case 'duration':
                case 'float':
                case 'gDay':
                case 'gMonth':
                case 'gMonthDay':
                case 'gYear':
                case 'gYearMonth':
                case 'hexBinary':
                case 'NOTATION':
                case 'QName':
                case 'string':
                case 'time':
                    return $xsd_namespace.'#'.$type_name;
                case 'normalizedString':
                case 'token':
                case 'language':
                case 'Name':
                case 'NMTOKEN':
                case 'NMTOKENS':
                case 'NCName':
                case 'ID':
                case 'IDREF':
                case 'IDREFS':
                case 'ENTITY':
                case 'ENTITIES':
                    return $xsd_namespace.'#string';
                case 'integer':
                case 'nonPositiveInteger':
                case 'negativeInteger':
                case 'long':
                case 'int':
                case 'short':
                case 'byte':
                case 'nonNegativeInteger':
                case 'unsignedLong':
                case 'unsignedInt':
                case 'unsignedShort':
                case 'unsignedByte':
                case 'positiveInteger':
                    return $xsd_namespace.'#decimal';
            }
        }

        return null;

    } // end of member function GetPrimitiveXsdType

} // end of TpConfigUtils
?>