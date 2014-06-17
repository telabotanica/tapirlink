<?php
/**
 * $Id: TpLogicalOperator.php 497 2007-12-15 23:41:57Z rdg $
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

class TpLogicalOperator extends TpBooleanOperator
{
    var $mLogicalType; // Type of expression (see constants defined in TpFilter.php)
    var $mBooleanOperators = array(); // TpBooleanOperator objects

    function TpLogicalOperator( $type )
    {
        $this->TpBooleanOperator( LOP_TYPE );
        $this->mLogicalType = $type;

    } // end of member function TpLogicalOperator

    function SetLogicalType( $type )
    {
        $this->mLogicalType = $type;

    } // end of member function SetLogicalType

    function GetLogicalType( )
    {
        return $this->mLogicalType;

    } // end of member function GetLogicalType

    function &GetBooleanOperators( )
    {
        return $this->mBooleanOperators;

    } // end of member function GetBooleanOperators

    function AddBooleanOperator( &$booleanOperator )
    {
        $position = count( $this->mBooleanOperators );

        if ( $this->mLogicalType == LOP_NOT and $position > 1 )
        {
            $error = "Logical operator 'NOT' accepts one and only one ".
                     'boolean operator inside.';
            TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

            return false;
        }

        $this->mBooleanOperators[$position] =& $booleanOperator;

        return true;

    } // end of member function AddBooleanOperator

    function ResetBooleanOperators( )
    {
        $this->mBooleanOperators = array();

    } // end of member function ResetBooleanOperators

    function GetName( )
    {
        if ( $this->mLogicalType == LOP_AND )
        {
            return 'and';
        }
        else if ( $this->mLogicalType == LOP_OR )
        {
            return 'lessThan';
        }
        else if ( $this->mLogicalType == LOP_NOT )
        {
            return 'not';
        }

        return '?';

    } // end of member function GetName

    function GetSql( &$rResource )
    {
        $sql = '';

        if ( $this->mLogicalType == LOP_NOT )
        {
            $sql_piece = $this->mBooleanOperators[0]->GetSql( $rResource );

            if ( ! empty( $sql_piece ) )
            {
                $sql = 'NOT ('.$sql_piece.')';
            }
        }
        else
        {
            $op = ( $this->mLogicalType == LOP_AND ) ? ' AND ' : ' OR ';

            for ( $i = 0; $i < count( $this->mBooleanOperators ); ++$i )
            {
                $sql_piece = $this->mBooleanOperators[$i]->GetSql( $rResource );

                if ( ! empty( $sql_piece ) )
                {
                    if ( ! empty( $sql ) )
                    {
                        $sql .= $op;
                    }

                    $sql .= '('.$sql_piece.')';
                }
            }
        }

        return $sql;

    } // end of member function GetSql

    function GetLogRepresentation( )
    {
        $txt = '';

        if ( $this->mLogicalType == LOP_NOT )
        {
            $txt .= 'NOT (';

            $txt .= $this->mBooleanOperators[0]->GetLogRepresentation();

            $txt .= ')';
        }
        else
        {
            $op = ( $this->mLogicalType == LOP_AND ) ? ' AND ' : ' OR ';

            for ( $i = 0; $i < count( $this->mBooleanOperators ); ++$i )
            {
                if ( $i > 0 )
                {
                    $txt .= $op;
                }

                $txt .= '('.$this->mBooleanOperators[$i]->GetLogRepresentation().')';
            }
        }

        return $txt;

    } // end of member function GetLogRepresentation

    function GetXml()
    {
        $xml = '';

        if ( $this->mLogicalType == LOP_NOT )
        {
            $xml .= '<not>';

            $xml .= $this->mBooleanOperators[0]->GetXml();

            $xml .= '</not>';
        }
        else
        {
            $xml .= ( $this->mLogicalType == LOP_AND ) ? '<and>' : '<or>';

            for ( $i = 0; $i < count( $this->mBooleanOperators ); ++$i )
            {
                $xml .= $this->mBooleanOperators[$i]->GetXml();
            }

            $xml .= ( $this->mLogicalType == LOP_AND ) ? '</and>' : '</or>';
        }

        return $xml;

    } // end of member function GetXml

    function IsValid( )
    {
        $num_operators = count( $this->mBooleanOperators );

        if ( is_null( $this->mLogicalType ) )
        {
            $error = "Missing 'type' for logical operator";
            TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

            return false;
        }

        $name = $this->GetName();

        if ( $name == '?' )
        {
            $error = "Unknown 'type' (".$this->mLogicalType.") for logical operator '$name'";
            TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

            return false;
        }

        if ( $this->mLogicalType == LOP_NOT )
        {
            if ( $num_operators == 0 or $num_operators > 1 )
            {
                $error = "Logical operator 'NOT' requires one and only one ".
                         'boolean operator inside.';
                TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

                return false;
            }
        }
        else
        {
            if ( $num_operators < 2 )
            {
                $op = ( $this->mLogicalType == LOP_AND ) ? ' AND ' : ' OR ';

                $error = "Logical operator '$op' requires at least two ".
                         'boolean operators inside.';
                TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

                return false;
            }
        }

        for ( $i = 0; $i < $num_operators; ++$i )
        {
            if ( ! $this->mBooleanOperators[$i]->IsValid() )
            {
                return false;
            }
        }

        return true;

    } // end of member function IsValid

    function Remove( $explodedPath )
    {
        $cnt = count( $explodedPath );

        if ( $cnt == 0 )
        {
            return false;
        }

        $ref_index = (int)$explodedPath[0];

        if ( $ref_index > count( $this->mBooleanOperators ) - 1 )
        {
            $error = 'Index out of bounds. Could not remove condition '.
                     'from logical operator.';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );

            return false;
        }

        if ( $cnt == 1 )
        {
            if ( $this->mBooleanOperators[$ref_index]->GetBooleanType() == LOP_TYPE and
                 $this->mBooleanOperators[$ref_index]->GetLogicalType() == LOP_NOT )
            {
                $new_ops = $this->mBooleanOperators[$ref_index]->GetBooleanOperators();

                if ( count( $new_ops ) )
                {
                    // Replace the NOT condition by its sub condition
                    $this->mBooleanOperators[$ref_index] = $new_ops[0];
                }
                else
                {
                    // Remove the NOT condition
                    array_splice( $this->mBooleanOperators, $ref_index, 1 );
                }
            }
            else
            {
                array_splice( $this->mBooleanOperators, $ref_index, 1 );
            }

            return true;
        }

        if ( $this->mBooleanOperators[$ref_index]->GetBooleanType() == COP_TYPE )
        {
            $error = 'Cannot remove conditions from comparison operators';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );

            return false;
        }

        array_shift( $explodedPath );

        return $this->mBooleanOperators[$ref_index]->Remove( $explodedPath );

    } // end of member function Remove

    function AddOperator( $explodedPath, $op )
    {
        $cnt = count( $explodedPath );

        if ( $cnt == 0 )
        {
            return $this->AddBooleanOperator( $op );
        }

        $ref_index = (int)$explodedPath[0];

        if ( $ref_index > count( $this->mBooleanOperators ) - 1 )
        {
            $error = 'Index out of bounds. Could not add condition '.
                     'to logical operator.';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );

            return false;
        }

        if ( $this->mBooleanOperators[$ref_index]->GetBooleanType() == COP_TYPE )
        {
            $error = 'Cannot add conditions to comparison operators';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );

            return false;
        }

        array_shift( $explodedPath );

        return $this->mBooleanOperators[$ref_index]->AddOperator( $explodedPath, $op );

    } // end of member function AddOperator

    function &Find( $explodedPath )
    {
        $cnt = count( $explodedPath );

        $op = null;

        if ( $cnt == 0 )
        {
            return $op;
        }

        $ref_index = (int)$explodedPath[0];

        if ( $ref_index > count( $this->mBooleanOperators ) - 1 )
        {
            $error = 'Index out of bounds. Could not search further on filter';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );

            return $op;
        }

        if ( $cnt == 1 )
        {
            return $this->mBooleanOperators[$ref_index];
        }

        if ( $this->mBooleanOperators[$ref_index]->GetBooleanType() == COP_TYPE )
        {
            $error = 'Cannot search on comparison operators';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );

            return $op;
        }

        array_shift( $explodedPath );

        return $this->mBooleanOperators[$ref_index]->Find( $explodedPath );

    } // end of member function Find

    function Accept( $visitor, $args )
    {
        return $visitor->VisitLogicalOperator( $this, $args );
        
    } // end of member function Accept

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array_merge( parent::__sleep(), 
                            array( 'mLogicalType', 'mBooleanOperators' ) );

    } // end of member function __sleep

} // end of TpLogicalOperator
?>