<?php
/**
 * $Id: TpExpression.php 648 2008-04-23 18:51:54Z rdg $
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

require_once('tapir_globals.php');
require_once('TpFilter.php');
require_once('TpConceptMapping.php');
require_once('TpDiagnostics.php');

class TpExpression
{
    var $mType;      // Type of Expression (see constants defined in TpFilter.php)
    var $mReference; // mixed: a value (for literals) or a parameter name or a concept id
                     //        or a TpTransparentConcept object (base concept of local 
                     //        filters)
    var $mHasWildcard = false;
    var $mRequired; // Used only for output model mapping!

    function TpExpression( $type, $reference, $required=false )
    {
        $this->mType = $type;

        $this->SetReference( $reference );

        $this->mRequired = $required;

    } // end of member function TpExpression

    function GetType( )
    {
        return $this->mType;

    } // end of member function GetType

    function SetReference( $ref )
    {
        $this->mReference = $ref;

    } // end of member function SetReference

    function GetReference( )
    {
        return $this->mReference;

    } // end of member function GetReference

    function GetValue( &$rResource, $localType, $caseSensitive, $isLike, $conceptDatatype=null )
    {
        $value = '';

        if ( $this->mType == EXP_LITERAL )
        {
            $value = $this->_PrepareValue( $this->mReference, $rResource, $localType, $caseSensitive, $isLike, $conceptDatatype );

            if ( is_null( $value ) )
            {
                return $value;
            }
        }
        else if ( $this->mType == EXP_PARAMETER )
        {
            if ( ! isset( $_REQUEST[$this->mReference] ) )
            {
                $msg = 'Parameter "'.$this->mReference.'" is missing';

                TpDiagnostics::Append( DC_MISSING_PARAMETER, $msg, DIAG_WARN );
                return null;
            }

            $value = $this->_PrepareValue( $_REQUEST[$this->mReference], $rResource, $localType, $caseSensitive, $isLike, $conceptDatatype );

            if ( is_null( $value ) )
            {
                return $value;
            }
        }
        else if ( $this->mType == EXP_CONCEPT )
        {
            $r_local_mapping =& $rResource->GetLocalMapping();

            $concept = $r_local_mapping->GetConcept( $this->mReference );

            if ( $concept == null or ! $concept->IsMapped() )
            {
                $msg = 'Concept "'.$this->mReference.'" is not mapped';

                TpDiagnostics::Append( DC_UNMAPPED_CONCEPT, $msg, DIAG_WARN );
                return false;
            }

            if ( ! $concept->IsSearchable() )
            {
                $msg = 'Concept "'.$this->mReference.'" is not searchable';

                TpDiagnostics::Append( DC_UNSEARCHABLE_CONCEPT, $msg, DIAG_WARN );
                return false;
            }

            $mapping = $concept->GetMapping();

            $r_data_source =& $rResource->GetDataSource();

            $r_adodb_connection =& $r_data_source->GetConnection();

            $in_where_clause = true;

            $value = $mapping->GetSqlTarget( $r_adodb_connection, $in_where_clause );

            if ( $localType == TYPE_TEXT and ! $caseSensitive )
            {
                $value = $r_adodb_connection->upperCase.'('.$value.')';
            }
        }
        else if ( $this->mType == EXP_COLUMN ) // only for local filters
        {
            $value = $this->mReference; // table.column
        }
        else if ( $this->mType == EXP_VARIABLE )
        {
            if ( ! $rResource->HasVariable( $this->mReference ) )
            {
                $msg = 'Unknown variable "'.$this->mReference.'"';

                TpDiagnostics::Append( DC_UNKNOWN_VARIABLE, $msg, DIAG_WARN );
                return false;
            }

            $value = $rResource->GetVariable( $this->mReference );
        }

        if ( $this->mHasWildcard )
        {
            $value .= " ESCAPE '&'"; // SQL92
        }

        return $value;

    } // end of member function GetValue

    function GetLogRepresentation( )
    {
        $txt = '';

        if ( $this->mType == EXP_LITERAL )
        {
            $txt = '"' . str_replace( '"', '\"', $this->mReference ) . '"';
        }
        else if ( $this->mType == EXP_PARAMETER )
        {
            $value = '';

            if ( isset( $_REQUEST[$this->mReference] ) )
            {
                $value = $_REQUEST[$this->mReference];
            }

            $txt = 'Parameter('.$this->mReference.'=>'.$value.')';
        }
        else if ( $this->mType == EXP_CONCEPT )
        {
            $txt = $this->mReference;
        }
        else if ( $this->mType == EXP_VARIABLE )
        {
            $txt = 'Variable('.$this->mReference.')';
        }

        return $txt;

    } // end of member function GetLogRepresentation

    function _UpperCase( $value )
    {
        if ( version_compare( phpversion(), '4.3.0', '>=' ) > 0 )
        {
            if ( function_exists('mb_strtoupper') )
            {
                $value = mb_strtoupper( $value, 'UTF-8' );
            }
            else
            {
                $value = strtoupper( $value );
            }
        }
        else
        {
            $flat_value = $value;

            if ( function_exists('mb_convert_encoding') )
            {
                $flat_value = mb_convert_encoding( $value, 'ASCII', 'UTF-8' );
            }

            if ( count( unpack( 'C*', $value) ) <> count( unpack( 'C*', $flat_value ) ) )
            {
                // the test above is supposed to check if there are any 
                // diacriticals inside the term

                $error = 'The value ['.$value.'] contains unsupported characters to '.
                         'be used in case insensitive searches with PHP versions less '.
                         'than 4.3.0.';

                TpDiagnostics::Append( DC_INVALID_FILTER_TERM, $error, DIAG_ERROR );

                return '';
            }
            else
            {
                $value = strtoupper( $value );
            }
        }

        return $value;

    } // end of member function _UpperCase

    function _GetLikeTerm( $value )
    {
        $no_wildcard = false;

        // Check if there's any "*" not preceeded by "_" (escape character)
        if ( function_exists('mb_strpos') and function_exists('mb_substr_count') )
        {
            if ( mb_strpos( $value, '*' ) === false or 
                 ( mb_substr_count( $value, '*' ) == mb_substr_count( $value, '_*' ) ) )
            {
                $no_wildcard = true;
            }
        }
        else
        {
            if (  strpos( $value, '*' ) === false or 
                  ( substr_count( $value, '*' ) == substr_count( $value, '_*' ) ) )
            {
                $no_wildcard = true;
            }
        }

        if ( $no_wildcard )
        {
            // No wildcard means adding one in the beginning and 
            // another in the end
            $value = '*'.$value.'*';
        }

        // Escape DB wildcard character in term
        if ( function_exists( 'mb_strpos' ) and function_exists( 'mb_ereg_replace' ) )
        {
            if ( mb_strpos( $value, TP_SQL_WILDCARD ) !== false )
            {
                $this->mHasWildcard = true;

                $value = mb_ereg_replace( TP_SQL_WILDCARD, '&'.TP_SQL_WILDCARD, $value );
            }
        }
        else
        {
            if ( strpos( TP_SQL_WILDCARD, $value ) !== false )
            {
                $this->mHasWildcard = true;

                $value = str_replace( TP_SQL_WILDCARD, '&'.TP_SQL_WILDCARD, $value );
            }
        }

        // Replace wildcards if DB uses a different character
        // Note: don't replace escaped wildcards!!
        if ( TP_SQL_WILDCARD != '*' )
        {
            if ( function_exists( 'mb_split' ) and function_exists( 'mb_strlen' ) and 
                 function_exists( 'mb_substr' ) )
            {
                $parts = mb_split( '\*', $value );

                if ( count( $parts ) > 1 )
                {
                    $value = '';

                    for ( $i = 0; $i < count( $parts ); ++$i )
                    {
                        if ( $i > 0 )
                        {
                            // If last character of last piece is "_"
                            if ( mb_strlen( $parts[$i-1] ) > 0 and 
                                 mb_substr( $parts[$i-1], mb_strlen( $parts[$i-1] )-1, 1 ) == '_' )
                            {
                                // Remove the "_" and don't translate the wildcard
                                $value =  mb_substr( $value, 0, mb_strlen($value)-1 ) . '*';
                            }
                            else
                            {
                                $value .= TP_SQL_WILDCARD;
                            }
                        }

                        $value .= $parts[$i];
                    }
                }
            }
            else
            {
                $parts = explode( '*', $value );

                if ( count( $parts ) > 1 )
                {
                    $value = '';

                    for ( $i = 0; $i < count( $parts ); ++$i )
                    {
                        if ( $i > 0 )
                        {
                            // If last character of last piece is "_"
                            if ( strlen( $parts[$i-1] ) > 0 and 
                                 substr( $parts[$i-1], strlen( $parts[$i-1] )-1, 1 ) == '_' )
                            {
                                $value =  substr( $value, 0, strlen($value)-1 ) . '*';
                            }
                            else
                            {
                                $value .= TP_SQL_WILDCARD;
                            }
                        }

                        $value .= $parts[$i];
                    }
                }
            }
        }

        return $value;

    } // end of member function _GetLikeTerm

    function _PrepareValue( $value, &$rResource, $localType, $caseSensitive, $isLike, $conceptDatatype )
    {
        $add_delimiter = true;

        // TODO: add specific blocks for the other concept types

        if ( $conceptDatatype === 'http://www.w3.org/2001/XMLSchema#dateTime' and 
                  ! $isLike )
        {
            if ( preg_match( "'^([\-]?\d{4})\-(\d{2})\-(\d{2})T(\d{2}):(\d{2}):(\d{2})(\.\d+)?(Z|(\+|\-)(\d{2}):(\d{2}))?$'", $value, $matches ) and 
                 (int)$matches[2] >  0 && (int)$matches[2] < 13 && 
                 (int)$matches[3] >  0 && (int)$matches[3] < 32 && 
                 (int)$matches[4] >= 0 && (int)$matches[4] < 24 && 
                 (int)$matches[5] >= 0 && (int)$matches[5] < 61 && 
                 (int)$matches[6] >= 0 && (int)$matches[6] < 61 )
            {
                $year  = $matches[1];
                $month = $matches[2];
                $day   = $matches[3];
                $hr    = $matches[4];
                $min   = $matches[5];
                $secs  = $matches[6];

                // Note: second decimals and time zone are being ignored

                $r_data_source =& $rResource->GetDataSource();

                $r_adodb_connection =& $r_data_source->GetConnection();

                if ( $localType == TYPE_DATETIME )
                {
                   $value = $r_adodb_connection->DBTimeStamp( "$year-$month-$day $hr:$min:$secs" );
                   $add_delimiter = false;
                }
                else if ( $localType == TYPE_DATE )
                {
                   $value = $r_adodb_connection->DBDate( "$year-$month-$day" );

                   $add_delimiter = false;
                }
                else if ( $localType == TYPE_TEXT )
                {
                    // Assume that the text follows the same pattern?
                }
                else if ( $localType == TYPE_NUMERIC )
                {
                    $msg = 'Expression '.$this->ToString().' has a local datatype (numeric) incompatible with the corresponding concept datatype (xsd:dateTime)';

                    TpDiagnostics::Append( DC_INVALID_FILTER, $msg, DIAG_WARN );

                    return null;
                }
            }
            else
            {
                $msg = 'Value "'.$value.'" does not match the expected xsd:dateTime pattern';

                TpDiagnostics::Append( DC_INVALID_FILTER, $msg, DIAG_WARN );
            }
        }

        if ( $localType != TYPE_NUMERIC )
        {
            if ( $localType == TYPE_TEXT and ! $caseSensitive )
            {
                $value = $this->_UpperCase( $value );
            }

            if ( $add_delimiter )
            {
                $value = str_replace( TP_SQL_QUOTE, TP_SQL_QUOTE_ESCAPE, $value );
            }

            if ( $isLike )
            {
                $value = $this->_GetLikeTerm( $value );
            }

            if ( $add_delimiter )
            {
                $value = TP_SQL_QUOTE . $value . TP_SQL_QUOTE;
            }
        }

        return $value;

    } // end of member function _PrepareValue

    function ToString( )
    {
        $ret = '';

        if ( $this->mType == EXP_LITERAL )
        {
            $ret .= 'literal';
        }
        else if ( $this->mType == EXP_CONCEPT )
        {
            $ret .= 'concept';
        }
        else if ( $this->mType == EXP_PARAMETER )
        {
            $ret .= 'parameter';
        }
        else if ( $this->mType == EXP_VARIABLE )
        {
            $ret .= 'variable';
        }
        else
        {
            $ret .= 'expression?';
        }

        return $ret .'['.$this->mReference.']';

    } // end of member function ToString

    function GetXml()
    {
        $xml = '';

        if ( $this->mType == EXP_LITERAL )
        {
            $xml = '<literal value="'.$this->mReference.'"/>';
        }
        else if ( $this->mType == EXP_PARAMETER )
        {
            $xml = '<parameter name="'.$this->mReference.'"/>';
        }
        else if ( $this->mType == EXP_CONCEPT )
        {
            $xml = '<concept id="'.$this->mReference.'"/>';
        }
        else if ( $this->mType == EXP_COLUMN ) // only for local filters
        {
            // local filters
            $concept = $this->mBaseConcept;

            $mapping = $concept->GetMapping();

            $xml = '<t_concept table="'.$mapping->GetTable().'" '.
                              'field="'.$mapping->GetField().'" '.
                              'type="'.$mapping->GetLocalType().'"/>';
        }
        else if ( $this->mType == EXP_VARIABLE )
        {
            $xml = '<variable name="'.$this->mReference.'"/>';
        }

        return $xml;

    } // end of member function GetXml

    function IsRequired( )
    {
        return $this->mRequired;
        
    } // end of member function IsRequired

    function Accept( $visitor, $args )
    {
        return $visitor->VisitExpression( $this, $args );
        
    } // end of member function Accept

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array( 'mType', 'mReference', 'mRequired' );

    } // end of member function __sleep

} // end of TpExpression
?>