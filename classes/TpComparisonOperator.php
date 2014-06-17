<?php
/**
 * $Id: TpComparisonOperator.php 648 2008-04-23 18:51:54Z rdg $
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

require_once('TpFilter.php');
require_once('TpBooleanOperator.php');
require_once('TpDiagnostics.php');
require_once('TpExpression.php');
require_once('TpConceptMapping.php');

class TpComparisonOperator extends TpBooleanOperator
{
    var $mComparisonType;        // Type of COP (see constants defined in TpFilter.php)
    var $mBaseConcept;           // Base TpExpression representing a concept or column 
    var $mExpressions = array(); // Other TpExpression objects

    function TpComparisonOperator( $type )
    {
        $this->TpBooleanOperator( COP_TYPE );
        $this->mComparisonType = $type;

    } // end of member function TpComparisonOperator

    function SetComparisonType( $type )
    {
        $this->mComparisonType = $type;

    } // end of member function SetComparisonType

    function GetComparisonType( )
    {
        return $this->mComparisonType;

    } // end of member function GetComparisonType

    function SetExpression( $expression )
    {
        $size = count( $this->mExpressions );

        if ( $size == 0 and ( $expression->GetType() == EXP_CONCEPT or
                              $expression->GetType() == EXP_COLUMN ) )
        {
            $this->mBaseConcept =& $expression;
        }
        else
        {
            $this->mExpressions[$size] =& $expression;
        }

    } // end of member function SetConcept

    function SetBaseConcept( $expression )
    {
        $this->mBaseConcept = $expression;

    } // end of member function SetBaseConcept

    function &GetBaseConcept( )
    {
        return $this->mBaseConcept;

    } // end of member function GetBaseConcept

    function &GetExpressions( )
    {
        return $this->mExpressions;

    } // end of member function GetExpressions

    function ResetExpressions( )
    {
        $this->mExpressions = array();

    } // end of member function ResetExpressions

    function GetSql( &$rResource )
    {
        $concept = null;

        $concept_error = false;

        $concept_datatype = null;

        if ( $this->mBaseConcept->GetType() == EXP_CONCEPT )
        {
            $concept_id = $this->mBaseConcept->GetReference();

            $r_local_mapping =& $rResource->GetLocalMapping();

            $concept = $r_local_mapping->GetConcept( $concept_id );

            if ( $concept == null or ! $concept->IsMapped() )
            {
                // Don't raise error here. If the expression is a missing
                // parameter then the comparison should be simply discarded

                $msg = 'Concept "'.$concept_id.'" is not mapped';

                $concept_error = array( DC_UNMAPPED_CONCEPT, $msg, DIAG_WARN );
            }
            else if ( ! $concept->IsSearchable() )
            {
                // Don't raise error here. If the expression is a missing
                // parameter then the comparison should be simply discarded

                $msg = 'Concept "'.$concept_id.'" is not searchable';

                $concept_error = array( DC_UNSEARCHABLE_CONCEPT, $msg, DIAG_WARN );
            }

            $concept_datatype = $concept->GetType();
        }
        else
        {
            $concept = $this->mBaseConcept->GetReference(); // local filters
        }

        if ( $concept_error )
        {
            // There's an error in the base concept

            if ( $this->mComparisonType == COP_ISNULL )
            {
                // When dealing with ISNULL, we should raise the error
                TpDiagnostics::Append( $concept_error[0], $concept_error[1], $concept_error[2] );

                return 'FALSE';
            }

            // Otherwise, let's pretend we have some values for the following variables.
            // Later, if there's no missing parameter then we will raise the error.
            // Missing parameters have preference to drop the comparison.

            $target = '?'; // will be ignored later

            $local_type = TYPE_TEXT; // will be ignored later
        }
        else
        {
            $mapping = $concept->GetMapping();

            $r_data_source =& $rResource->GetDataSource();

            $r_adodb_connection =& $r_data_source->GetConnection();

            $in_where_clause = true;

            $target = $mapping->GetSqlTarget( $r_adodb_connection, $in_where_clause );

            if ( $this->mComparisonType == COP_ISNULL )
            {
                return $target . ' IS NULL';
            }

            $local_type = $mapping->GetLocalType();
        }

        $sql = $target;

        $case_sensitive = true;

        if ( $this->mComparisonType == COP_EQUALS )
        {
            $r_settings =& $rResource->GetSettings();

            if ( $local_type == TYPE_TEXT and ! $r_settings->GetCaseSensitiveInEquals() )
            {
                $r_data_source =& $rResource->GetDataSource();

                $r_adodb_connection =& $r_data_source->GetConnection();

                $case_sensitive = false;

                $sql = $r_adodb_connection->upperCase.'('.$target.')';
            }

            $sql .= ' = ';
        }
        else if ( $this->mComparisonType == COP_LESSTHAN )
        {
            $sql .= ' < ';
        }
        else if ( $this->mComparisonType == COP_LESSTHANOREQUALS )
        {
            $sql .= ' <= ';
        }
        else if ( $this->mComparisonType == COP_GREATERTHAN )
        {
            $sql .= ' > ';
        }
        else if ( $this->mComparisonType == COP_GREATERTHANOREQUALS )
        {
            $sql .= ' >= ';
        }
        else if ( $this->mComparisonType == COP_LIKE )
        {
            $r_settings =& $rResource->GetSettings();

            if ( ! $r_settings->GetCaseSensitiveInLike() )
            {
                $r_data_source =& $rResource->GetDataSource();

                $r_adodb_connection =& $r_data_source->GetConnection();

                $case_sensitive = false;

                $sql = $r_adodb_connection->upperCase.'('.$target.')';
            }

            $sql .= ' LIKE ';
        }
        else if ( $this->mComparisonType == COP_IN )
        {
            $r_settings =& $rResource->GetSettings();

            if ( ! $r_settings->GetCaseSensitiveInEquals() )
            {
                $r_data_source =& $rResource->GetDataSource();

                $r_adodb_connection =& $r_data_source->GetConnection();

                $case_sensitive = false;

                $sql = $r_adodb_connection->upperCase.'('.$target.')';
            }

            $sql .= ' IN ( ';
        }

        for ( $i = 0; $i < count( $this->mExpressions ); ++$i )
        {
            if ( $i > 0 )
            {
                $sql .= ', ';
            }

            $is_like = false;

            if ( $this->mComparisonType == COP_LIKE )
            {
                $is_like = true;

                if ( $local_type != TYPE_TEXT )
                {
                    $msg = 'Concept used in "Like" comparison is mapped to a '.
                           'non-textual content';
                    TpDiagnostics::Append( DC_INVALID_FILTER, $msg, DIAG_WARN );
                    return 'FALSE';
                }
            }

            $term = $this->mExpressions[$i]->GetValue( $rResource, $local_type, 
                                                       $case_sensitive, $is_like,
                                                       $concept_datatype );

            if ( is_null( $term ) )
            {
                return '';
            }
            else if ( $term === false )
            {
                return 'FALSE';
            }

            $sql .= $term;
        }

        if ( $this->mComparisonType == COP_IN )
        {
            $sql .= ')';
        }

        if ( $concept_error )
        {
            TpDiagnostics::Append( $concept_error[0], $concept_error[1], $concept_error[2] );

            return 'FALSE';
        }

        return $sql;

    } // end of member function GetSql

    function GetLogRepresentation()
    {
        $txt = '';

        if ( $this->mBaseConcept->GetType() == EXP_CONCEPT )
        {
            $txt = $this->mBaseConcept->GetReference();
        }
        else
        {
            // Should never fall here - local filters are not logged

            $concept = $this->mBaseConcept->GetReference();

            $txt = $concept->GetId();
        }

        if ( $this->mComparisonType == COP_ISNULL )
        {
            return $txt . ' IS NULL';
        }

        if ( $this->mComparisonType == COP_EQUALS )
        {
            $txt .= ' = ';
        }
        else if ( $this->mComparisonType == COP_LESSTHAN )
        {
            $txt .= ' < ';
        }
        else if ( $this->mComparisonType == COP_LESSTHANOREQUALS )
        {
            $txt .= ' <= ';
        }
        else if ( $this->mComparisonType == COP_GREATERTHAN )
        {
            $txt .= ' > ';
        }
        else if ( $this->mComparisonType == COP_GREATERTHANOREQUALS )
        {
            $txt .= ' >= ';
        }
        else if ( $this->mComparisonType == COP_LIKE )
        {
            $txt .= ' LIKE ';
        }
        else if ( $this->mComparisonType == COP_IN )
        {
            $txt .= ' IN ( ';
        }

        for ( $i = 0; $i < count( $this->mExpressions ); ++$i )
        {
            if ( $i > 0 )
            {
                $txt .= ', ';
            }

            $term = $this->mExpressions[$i]->GetLogRepresentation();

            $txt .= $term;
        }

        if ( $this->mComparisonType == COP_IN )
        {
            $txt .= ')';
        }

        return $txt;

    } // end of member function GetLogRepresentation

    function GetXml( )
    {
        $xml = '';

        if ( $this->mComparisonType == COP_ISNULL )
        {
            $start_tag = '<isNull>';
            $end_tag   = '</isNull>';
        }
        else if ( $this->mComparisonType == COP_EQUALS )
        {
            $start_tag = '<equals>';
            $end_tag   = '</equals>';
        }
        else if ( $this->mComparisonType == COP_LESSTHAN )
        {
            $start_tag = '<lessThan>';
            $end_tag   = '</lessThan>';
        }
        else if ( $this->mComparisonType == COP_LESSTHANOREQUALS )
        {
            $start_tag = '<lessThanOrEquals>';
            $end_tag   = '</lessThanOrEquals>';
        }
        else if ( $this->mComparisonType == COP_GREATERTHAN )
        {
            $start_tag = '<greaterThan>';
            $end_tag   = '</greaterThan>';
        }
        else if ( $this->mComparisonType == COP_GREATERTHANOREQUALS )
        {
            $start_tag = '<greaterThanOrEquals>';
            $end_tag   = '</greaterThanOrEquals>';
        }
        else if ( $this->mComparisonType == COP_LIKE )
        {
            $start_tag = '<like>';
            $end_tag   = '</like>';
        }
        else if ( $this->mComparisonType == COP_IN )
        {
            $start_tag = '<in>';
            $end_tag   = '</in>';
        }
        else
        {
            $start_tag = '<unknown type="'.$this->mComparisonType.'">';
            $end_tag   = '</unknown>';
        }

        $xml .= $start_tag;

        if ( $this->mBaseConcept->GetType() == EXP_CONCEPT )
        {
            $xml .= '<concept id="'.$this->mBaseConcept->GetReference().'"/>';
        }
        else
        {
            // local filters
            $concept = $this->mBaseConcept->GetReference();

            $mapping = $concept->GetMapping();

            $xml .= '<t_concept table="'.$mapping->GetTable().'" '.
                               'field="'.$mapping->GetField().'" '.
                               'type="'.$mapping->GetLocalType().'"/>';
        }

        if ( $this->mComparisonType == COP_IN )
        {
            $xml .= '<values>';
        }

        for ( $i = 0; $i < count( $this->mExpressions ); ++$i )
        {
            $xml .= $this->mExpressions[$i]->GetXml();
        }

        if ( $this->mComparisonType == COP_IN )
        {
            $xml .= '</values>';
        }

        $xml .= $end_tag;

        return $xml;

    } // end of member function GetXml

    function GetName( )
    {
        if ( $this->mComparisonType == COP_EQUALS )
        {
            return 'equals';
        }
        else if ( $this->mComparisonType == COP_LESSTHAN )
        {
            return 'lessThan';
        }
        else if ( $this->mComparisonType == COP_LESSTHANOREQUALS )
        {
            return 'lessThanOrEquals';
        }
        else if ( $this->mComparisonType == COP_GREATERTHAN )
        {
            return 'greaterThan';
        }
        else if ( $this->mComparisonType == COP_GREATERTHANOREQUALS )
        {
            return 'greaterThanOrEquals';
        }
        else if ( $this->mComparisonType == COP_LIKE )
        {
            return 'like';
        }
        else if ( $this->mComparisonType == COP_ISNULL )
        {
            return 'isNull';
        }
        else if ( $this->mComparisonType == COP_IN )
        {
            return 'in';
        }

        return '?';

    } // end of member function GetName

    function IsValid( )
    {
        if ( is_null( $this->mComparisonType ) )
        {
            $error = "Missing 'type' for comparison operator";
            TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

            return false;
        }

        $name = $this->GetName();

        if ( ! isset( $this->mBaseConcept ) )
        {
            $error = "Missing 'concept' term for comparison operator '$name'";
            TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

            return false;
        }

        if ( $name == '?' )
        {
            $error = "Unknown 'type' (".$this->mComparisonType.") for comparison operator '$name'";
            TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

            return false;
        }

        $num_expressions = count( $this->mExpressions );

        if ( $this->mComparisonType == COP_ISNULL )
        {
            if ( $num_expressions > 0 )
            {
                $error = "Operator '$name' requires no additional terms ".
                         'besides a concept';
                TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

                return false;
            }
        }
        else if ( $this->mComparisonType == COP_IN )
        {
            if ( $num_expressions == 0 )
            {
                $error = "Operator '$name' requires at least one term ".
                         'besides a concept';
                TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

                return false;
            }
        }
        else
        {
            if ( $num_expressions != 1 )
            {
                $error = "Operator '$name' requires one and only one term ".
                         'besides a concept';
                TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

                return false;
            }
        }

        return true;

    } // end of member function IsValid

    function Accept( $visitor, $args )
    {
        return $visitor->VisitComparisonOperator( $this, $args );
        
    } // end of member function Accept

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array_merge( parent::__sleep(), 
                            array( 'mComparisonType', 'mBaseConcept', 'mExpressions' ) );

    } // end of member function __sleep

} // end of TpComparisonOperator
?>