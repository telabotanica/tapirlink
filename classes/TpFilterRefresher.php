<?php
/**
 * $Id: TpFilterRefresher.php 573 2008-03-28 16:57:58Z rdg $
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

require_once('TpFilterVisitor.php');
require_once('TpUtils.php');
require_once('TpDiagnostics.php');
require_once('TpTransparentConcept.php');

class TpFilterRefresher extends TpFilterVisitor
{
    var $mTablesAndColumns = array();  // array (table_name => array(column obj) )

    function TpFilterRefresher( ) 
    {

    } // end of member function TpFilterRefresher

    function SetTablesAndColumns( $tablesAndColumns ) 
    {
        $this->mTablesAndColumns = $tablesAndColumns;

    } // end of member function SetTablesAndColumns

    function Refresh( &$rFilter )
    {
        $r_root_boolean_operator =& $rFilter->GetRootBooleanOperator();

        if ( is_object( $r_root_boolean_operator ) )
        {
            $args = array();

            $args['path'] = '/0';

            return $r_root_boolean_operator->Accept( $this, $args );
        }

        return true;

    } // end of member function Refresh

    function VisitLogicalOperator( $lop, $args )
    {
        $path = $args['path'];

        $lop_id = $path;

        // Only change type if necessary
        if ( substr( TpUtils::GetVar( 'refresh' ), -10 ) == '_lopchange' )
        {
            $refresh = explode( '_', TpUtils::GetVar( 'refresh' ) );

            $changed_op_id = $refresh[0];

            if ( $changed_op_id == $lop_id )
            {
                $cut_point = $refresh[1];

                $lop_connection_id = $lop_id . '_' . $cut_point;

                $env_type = (int)TpUtils::GetVar( $lop_connection_id, -1 );

                if ( $env_type != -1 )
                {
                    $lop->SetLogicalType( $env_type );
                }
            }
        }

        $r_boolean_operators =& $lop->GetBooleanOperators();

        for ( $i = 0; $i < count( $r_boolean_operators ); ++$i )
        {
            $args['path'] = $path . '/' . $i;
            $args['seq']  = $i;

            if ( ! $r_boolean_operators[$i]->Accept( $this, $args ) )
            {
                return false;
            }
        }

        return true;

    } // end of member function VisitLogicalOperatior

    function VisitComparisonOperator( $cop, $args )
    {
        $path = $args['path'];

        $cop_id = $path;

        $current_type = $cop->GetComparisonType();

        $env_type = (int)TpUtils::GetVar( $cop_id, -1 );

        if ( $env_type != -1 and $env_type != $current_type )
        {
            $cop->SetComparisonType( $env_type );
        }

        $column_id = $path . '@col';

        $column = TpUtils::GetVar( $column_id, '' );

        if ( empty( $column ) )
        {
            $cop->SetBaseConcept( null );
        }
        else
        {
            $parts = explode( '.', $column );

            if ( count( $parts ) == 2 )
            {
                $table = $parts[0];
                $field = $parts[1];

                $new_reference = $table.'.'.$field;

                $current_reference = '';

                $base_concept = $cop->GetBaseConcept();

                if ( is_object( $base_concept ) )
                {
                    $current_reference = $base_concept->GetReference();
                }

                if ( is_object( $base_concept ) or 
                     $current_reference != $new_reference )
                {
                    if ( isset( $this->mTablesAndColumns[$table][$field] ) )
                    {
                        $field_obj = $this->mTablesAndColumns[$table][$field];

                        $type = TpConfigUtils::GetFieldType( $field_obj );

                        $concept = new TpTransparentConcept( $table, $field, $type );

                        $cop->SetBaseConcept( new TpExpression( EXP_COLUMN, $concept ) );
                    }
                    else
                    {
                        $error = 'Could not find data type for field: "'.
                                 $table.'.'.$field.'"';
                        TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
                        return false;
                    }
                }
            }
            else
            {
                $error = 'Unexpected column format: "'.$column.'"';
                TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
                return false;
            }
        }

        $value_id  = $cop_id . '@val';

        $r_expressions =& $cop->GetExpressions();

        $env_value = TpUtils::GetVar( $value_id, '' );

        $cop->ResetExpressions();

        if ( $cop->GetComparisonType() == COP_IN )
        {
            $values = explode( ',', $env_value );

            for ( $i = 0; $i < count( $values ); ++$i )
            {
                $cop->SetExpression( new TpExpression( EXP_LITERAL, $values[$i] ) );
            }
        }
        else if ( $cop->GetComparisonType() == COP_ISNULL )
        {
            if ( ! empty( $env_value ) )
            {
                $error = '"isNull" conditions should have no associated value';
                TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );
                return false;
            }
        }
        else
        {
            $cop->SetExpression( new TpExpression( EXP_LITERAL, $env_value ) ); 
        }

        return true;
        
    } // end of member function VisitComparisonOperator

    function VisitExpression( $expression, $args )
    {
        // never called
        
    } // end of member function VisitExpression

} // end of TpFilterRefresher
?>