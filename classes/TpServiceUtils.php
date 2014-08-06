<?php
/**
 * $Id: TpServiceUtils.php 1956 2009-01-06 00:20:47Z rdg $
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

require_once('TpUtils.php');

class TpServiceUtils
{
    /**
     * Converts an associative array into a string to be used in the log.
     *
     * @param $data array Data to be logged.
     * @return string Log formatted string.
     */
    function GetLogString( $data )
    {
        $spacer = "\t";

        $log_str = '';

        foreach ( $data as $key => $value )
        {
            if ( is_numeric( $value ) )
            {
                $log_value = "$value";
            }
            elseif ( is_bool( $value ) )
            {
                $log_value = ( $value == false ) ? 'false' : 'true';
            }
            elseif ( $value == null )
            {
                $log_value = 'NULL';
            }
            else
            {
                $log_value = str_replace( "\n", '', str_replace( "\t", '', $value ) );
            }

            $log_str .= "$spacer$key=".$log_value;
        }

        return $log_str;

    } // end of GetLogString

    /**
     * Check if the specified db charset encoding can be detected by the
     * mb_detect_encoding PHP function. In that case, set the global variable
     * $g_encoding_can_be_detected to true and add the $encoding to the beginning
     * of the default detect_order list.
     *
     * @param $encoding string Database charset encoding.
     */
    function AdjustDetectEncodingOrder( $encoding )
    {
        if ( ! function_exists( 'mb_detect_encoding' ) )
        {
            return;
	}

        $supported[] = 'UTF-8';
        $supported[] = 'UTF-7';
        $supported[] = 'ASCII';
        $supported[] = 'EUC-JP';
        $supported[] = 'SJIS';
        $supported[] = 'eucJP-win';
        $supported[] = 'SJIS-win';
        $supported[] = 'JIS';
        $supported[] = 'ISO-2022-JP';

        $supported[] = 'ISO-8859-1';
        $supported[] = 'ISO-8859-2';
        $supported[] = 'ISO-8859-3';
        $supported[] = 'ISO-8859-4';
        $supported[] = 'ISO-8859-5';
        $supported[] = 'ISO-8859-6';
        $supported[] = 'ISO-8859-7';
        $supported[] = 'ISO-8859-8';
        $supported[] = 'ISO-8859-9';
        $supported[] = 'ISO-8859-10';
        $supported[] = 'ISO-8859-13';
        $supported[] = 'ISO-8859-14';
        $supported[] = 'ISO-8859-15';

        //$unsupported[] = 'UTF-16';
        //$unsupported[] = 'UTF-32';
        //$unsupported[] = 'UCS2';
        //$unsupported[] = 'UCS4';

        if ( ! in_array( $encoding, $supported ) )
        {
            return;
        }

        global $g_encoding_can_be_detected;

        $g_encoding_can_be_detected = true;

        // Get default order
        $order = mb_detect_order();

        if ( ! in_array( $encoding, $order ) )
        {
            array_unshift( $order, $encoding );

            // Set new order
            mb_detect_order( $order );
        }

        // Trick: always try to prepend latin1, since it's very common
        if ( ! in_array( 'ISO-8859-1', $order ) )
        {
            array_unshift( $order, $encoding );

            // Set new order again
            mb_detect_order( $order );
        }

    } // end of AdjustDetectEncodingOrder

    /**
     * Converts a SQL statement to the current database charset encoding
     * (if different from UTF-8).
     *
     * @param $sql string SQL statement.
     * @param $encoding string Database charset encoding.
     * @return string SQL statement to be sent to database
     */
    function EncodeSql( $sql, $encoding )
    {
        if ( strcasecmp( $encoding, 'UTF-8' ) )
        {
            if ( function_exists( 'mb_convert_encoding' ) )
            {
                $sql = mb_convert_encoding( $sql, $encoding, 'UTF-8' );
            }
        }

        return $sql;

    } // end of EncodeSql

    /**
     * Encode a given string in the given encoding.
     * Note: AdjustDetectEncodingOrder must always be called before calling
     *       this function.
     *
     * @param $data string String to be encoded.
     * @param $encoding string Final charset encoding.
     * @return string Encoded string
     */
    function EncodeData( $data, $encoding )
    {
        if ( $data == null )
        {
            return null;
        }

        // If conversion function exists
        if ( function_exists( 'mb_convert_encoding' ) )
        {
            // If data encoding is different from UTF-8, convert values to UTF-8
            if ( strcasecmp( $encoding, 'UTF-8' ) )
            {
                // But first check if $data is encoded as expected

                // If encoding can be checked
                if ( function_exists( 'mb_check_encoding' ) )
                {
                    // If db encoding is corrrect
                    if ( mb_check_encoding( $data, $encoding ) )
                    {
                        $data = mb_convert_encoding( $data, 'UTF-8', $encoding );
                    }
                    // Db encoding seems to be wrong!
                    else
                    {
                        // Dynamically detect the string encoding before conversion
                        $data = mb_convert_encoding( $data, 'UTF-8', mb_detect_encoding( $data ) );
                    }
                }
                // Cannot check encoding
                else
                {
                    global $g_encoding_can_be_detected;

                    // If specified db encoding can be detected by PHP.
                    // Note: here we assume that it was already included in detect_order
                    if ( $g_encoding_can_be_detected )
                    {
                        // Use detection
                        $data = mb_convert_encoding( $data, 'UTF-8', mb_detect_encoding( $data ) );
                    }
                    // If specified db encoding cannot be detected by PHP
                    else
                    {
                        // Trust user
                        $data = mb_convert_encoding( $data, 'UTF-8', $encoding );
                    }
                }
            }
            // Encoding is UTF-8
            else
            {
                // Check if it's really UTF-8
                // Nous devons faire confiance au format dÃ©finit par le mapping.
                // En effet, la prÃ©sence des caractÃšres XML spÃ©cial renvoit une erreur "mb_convert_encoding : illegal character"
                // Il est donc nÃ©cessaire de les Ã©chapper avant. Mais ne connaissant pas l'encodage, cela est impossible
                /*
                if ( function_exists( 'mb_check_encoding' ) )
                {
                    // If db encoding is incorrrect
                    if ( ! mb_check_encoding( $data, $encoding ) )
                    {
                        // Try to detect encoding and force conversion
                        mb_substitute_character("entity");
                        $data = mb_convert_encoding( $data, 'UTF-8', mb_detect_encoding( $data ) );
                    }
                }*/
            }
        }

        return TpUtils::EscapeXmlSpecialChars( $data );

    } // end of member function EncodeData

    /**
     * Indicates is $path1 contains $path2 (offset = 0)
     * (note: not using substr_count because offset was only added in PHP5).
     *
     * @param $path1 string haystack.
     * @param $path2 string needle.
     * @return boolean True if haystack contains needle
     */
    function Contains( &$rPath1, &$rPath2 )
    {
        $size = strlen( $rPath2 );

        if ( strlen( $rPath1 ) >= $size )
        {
            if ( substr( $rPath1, 0, $size ) == $rPath2 )
            {
                return true;
            }
        }

        return false;

    } // end of member function Contains

} // end of TpServiceUtils
?>
