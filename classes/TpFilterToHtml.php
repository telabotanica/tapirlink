<?php
/**
 * $Id: TpFilterToHtml.php 573 2008-03-28 16:57:58Z rdg $
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
require_once('TpHtmlUtils.php');

class TpFilterToHtml extends TpFilterVisitor
{
    // comparative operators
    var $mBinaryCops = array( COP_EQUALS => 'equals',
                              COP_LIKE => 'contains (* for wildcard)',
                              COP_LESSTHAN => 'less than',
                              COP_LESSTHANOREQUALS => 'less than or equal to',
                              COP_GREATERTHAN => 'greater than',
                              COP_GREATERTHANOREQUALS => 'greater than or equal to',
                              COP_IN => 'in list (comma-delimited)' );

    var $mUnaryCops = array( COP_ISNULL => 'isNull' );

    // logical operators
    var $mMultiLops = array( LOP_AND => 'and',
                             LOP_OR  => 'or' );

    var $mUnaryLops = array( LOP_NOT => 'not' );

    var $mTablesAndColumns = array();  // array (table_name => array(column obj) )

    function TpFilterToHtml( ) 
    {

    } // end of member function TpFilterToHtml

    function SetTablesAndColumns( $tablesAndColumns ) 
    {
        $this->mTablesAndColumns = $tablesAndColumns;

    } // end of member function SetTablesAndColumns

    function GetHtml( &$rFilter )
    {
        $root_boolean_operator = $rFilter->GetRootBooleanOperator();

        if ( is_object( $root_boolean_operator ) )
        {
            $args = array();

            $args['path'] = '/0';

            $html = $root_boolean_operator->Accept( $this, $args );

            if ( $root_boolean_operator->GetBooleanType() == COP_TYPE )
            {
                $html .= '<br/>';
            }

            return $html;
        }

        return $this->_GetAddButtons( 'root' ).'<br/>';

    } // end of member function GetHtml

    function VisitLogicalOperator( $lop, $args )
    {
        $html = '';

        $path = $args['path'];

        $lop_id = $path;

        $level = substr_count( $path, '/' ) - 1;

        $css_class = ( fmod( $level, 2 ) <> 0 ) ? 'box1' : 'box2';

	$lop_name = '?';

        $logical_type = $lop->GetLogicalType();

        if ( $logical_type == LOP_AND )
        {
            $lop_name = 'and';
        }
        else if ( $logical_type == LOP_OR )
        {
            $lop_name = 'or';
        }
        else if ( $logical_type == LOP_NOT )
        {
            $lop_name = 'not';
        }

        $html .= "\n".sprintf( '<div class="%s" nowrap="1">', $css_class )."\n";

        if ( $logical_type == LOP_NOT )
        {
            $html .= '<b>'.strtoupper( $lop_name ).'</b>&nbsp;&nbsp;';

            $html .= $this->_GetRemoveButton( $lop_id, 'remove negation' );
            $html .= '<br/>';
        }
        else
        {
            $html .= $this->_GetRemoveButton( $lop_id, 'remove logical operator box' );
            $html .= '<br/>';
        }

        $boolean_operators = $lop->GetBooleanOperators();

        $total = count( $boolean_operators );

        $add_line = false;

        for ( $i = 0; $i < $total; ++$i )
        {
            $args['path'] = $path . '/' . $i;

            if ( $boolean_operators[$i]->GetBooleanType() == COP_TYPE )
            {
                $add_line = true;

                $html .= '<br/>';
            }
            else
            {
                $add_line = false;
            }

            $html .= $boolean_operators[$i]->Accept( $this, $args );

            if ( $logical_type != LOP_NOT and $total > 1 and $i < $total - 1)
            {
                if ( $add_line )
                {
                    $html .= '<br/><br/>';
                }

                $lop_connection_id = $lop_id .'_'.$i;

                $js = sprintf("document.forms[1].refresh.value='%s';window.saveScroll();document.forms[1].submit();", $lop_connection_id.'_lopchange' );

                $html .= TpHtmlUtils::GetCombo( $lop_connection_id,
                                                $logical_type,
                                                $this->_GetOptions( 'lops' ),  
                                                false, false, $js );
                $html .= '<br/>';
            }
        }

        if ( $logical_type != LOP_NOT or $total == 0 )
        {
            if ( $add_line )
            {
                $html .= '<br/>';
            }

            $html .= '<br/>'.$this->_GetAddButtons( $lop_id );
        }

        $html .= '</div>'."\n";

        return $html;

    } // end of member function VisitLogicalOperatior

    function VisitComparisonOperator( $cop, $args )
    {
        $html = "\n";

        $path = $args['path'];

        $cop_id = $path;

	$base_concept = $cop->GetBaseConcept();

        if ( is_object( $base_concept ) )
        {
            $html .= $base_concept->Accept( $this, $args );
        }
        else
        {
            $column_id = $path . '@col';

            $html .= TpHtmlUtils::GetCombo( $column_id, 
                                            '',
                                            $this->_GetOptions( 'columns' ) );
        }

        $html .= '&nbsp;'.
                 TpHtmlUtils::GetCombo( $cop_id, 
                                        $cop->GetComparisonType(),
                                        $this->_GetOptions( 'cops') ).'&nbsp;';

        $value_id  = $cop_id . '@val';

        $expressions = $cop->GetExpressions();

        $value = '';

        for ( $i = 0; $i < count( $expressions ); ++$i )
        {
            if ( $i > 0 )
            {
                $value .= ',';
            }

            $value .= $expressions[$i]->GetReference();
        }

        $html .= '<input type="text" name="'.$value_id.'" '.
                        'value="'.$value.'" '.
                        'size="10">'. "\n" . '&nbsp;';


        $html .= $this->_GetRemoveButton( $cop_id );

        return $html;
        
    } // end of member function VisitComparisonOperator

    function VisitExpression( $expression, $args )
    {
        if ( $expression->GetType() == EXP_COLUMN )
        {
            $path = $args['path'];

            $column_id = $path . '@col';

            $concept = $expression->GetReference();

            $mapping = $concept->GetMapping();

            $table = $mapping->GetTable();

            $field = $mapping->GetField();

            $column = $table.'.'.$field;

            if ( ! isset( $this->mTablesAndColumns[$table] ) or 
                 ! is_array( $this->mTablesAndColumns[$table] ) )
            {
                $msg = 'Table "'.$table.'" is referenced by the current filter but '.
                       'does not exist in the database.';
                TpDiagnostics::Append( CFG_INTERNAL_ERROR, $msg, DIAG_ERROR );
            }
            else if ( ! isset( $this->mTablesAndColumns[$table][$field] ) )
            {
                $msg = 'Column "'.$field.'" is referenced by the current filter but '.
                       'does not exist in the database.';
                TpDiagnostics::Append( CFG_INTERNAL_ERROR, $msg, DIAG_ERROR );
            }

            return TpHtmlUtils::GetCombo( $column_id, 
                                          $column,
                                          $this->_GetOptions( 'columns' ) );
        }

        return $expression->GetReference();
        
    } // end of member function VisitExpression

    function _GetOptions( $id ) 
    {
        $options = array();

        if ( $id == 'cops')
        {
             // It's important to preserve keys, so don't use array_merge!
            $options = $this->mBinaryCops + $this->mUnaryCops;

            //array_unshift( $options, '-- comparator --' );
        }
        else if ( $id == 'lops')
        {
            $options = $this->mMultiLops;

            //array_unshift( $options, '-- operator --' );
        }
        else if ( $id == 'columns')
        {
            if ( is_array( $this->mTablesAndColumns ) )
            {
                foreach ( $this->mTablesAndColumns as $table => $columns )
                {
                    if ( is_array( $columns ) )
                    {
                        foreach ( $columns as $column )
                        {
                            array_push( $options, $table.'.'.$column->name );
                        }
                    }
                }

                $options = TpUtils::GetHash( $options );
                asort( $options );

                array_unshift( $options, '-- column --' );
            }
        }

        return $options;

    } // end of member function _GetOptions

    function _GetRemoveButton( $id, $label='remove' )
    {
        return '<input type="submit" name="remove" value="'.$label.'" '.
                      'onClick="document.wizard.refresh.value=\''.$id.'\';'.
                      'document.wizard.submit();"/>';

    } // end of member function _GetRemoveButton

    function _GetAddButtons( $id )
    {
        $add_cop_button = '<input type="submit" name="add_cop" value="add simple comparison" onClick="document.forms[1].refresh.value=\''.$id.'\';document.forms[1].submit();">';
        $add_multi_lop_button = '<input type="submit" name="add_multi_lop" value="add logical operator box" onClick="document.forms[1].refresh.value=\''.$id.'\';document.forms[1].submit();">';
        $add_not_lop_button = '<input type="submit" name="add_not_lop" value="add NOT condition" onClick="document.forms[1].refresh.value=\''.$id.'\';document.forms[1].submit();">';

        return "\n".$add_cop_button.'&nbsp;'.$add_multi_lop_button.'&nbsp;'.$add_not_lop_button;

    } // end of member function _GetAddButtons

} // end of TpFilterToHtml
?>