<?php
/**
 * $Id: TpFilter.php 1966 2009-01-20 18:11:30Z rdg $
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
 *
 * ACKNOWLEDGEMENTS
 *
 * The URL filter parser (methods _ResolveLiterals, _ResolveConcepts,
 * _ResolveBrackets, _TokenizeData, _ResolveOperators and
 * _CreateOperator) has been deliberately translated from the
 * PyWrapper data provider software (http://www.pywrapper.org/)
 * with generous permission from its author. Many thanks to Markus
 * Döring!
 */

define( 'COP_TYPE', 0 );
define( 'LOP_TYPE', 1 );

define( 'COP_EQUALS'             , 0 );
define( 'COP_LESSTHAN'           , 1 );
define( 'COP_LESSTHANOREQUALS'   , 2 );
define( 'COP_GREATERTHAN'        , 3 );
define( 'COP_GREATERTHANOREQUALS', 4 );
define( 'COP_LIKE'               , 5 );
define( 'COP_ISNULL'             , 6 );
define( 'COP_IN'                 , 7 );

define( 'LOP_AND', 0 );
define( 'LOP_OR' , 1 );
define( 'LOP_NOT', 2 );

define( 'EXP_CONCEPT'  , 0 );
define( 'EXP_LITERAL'  , 1 );
define( 'EXP_PARAMETER', 2 );
define( 'EXP_COLUMN'   , 3 ); // for local filters
define( 'EXP_VARIABLE' , 4 );

require_once('TpComparisonOperator.php');
require_once('TpLogicalOperator.php');
require_once('TpDiagnostics.php');
require_once('TpExpression.php');
require_once('TpNestedList.php');
require_once('TpTransparentConcept.php'); // for local filters

class TpFilter
{
    var $mRootBooleanOperator; // Root COP or LOP
    var $mInTags = array();    // name element stack during XML parsing
    var $mIsValid = true;
    var $mOperatorsStack = array();
    var $mEscapeChar;
    var $mOperators = array( 'isnull'              => 100,
                             'like'                => 40,
                             'equals'              => 50,
                             'greaterthan'         => 60,
                             'lessthan'            => 70,
                             'greaterthanorequals' => 80,
                             'lessthanorequals'    => 90,
                             'in'                  => 120,
                             'and'                 => 20,
                             'or'                  => 10,
                             'not'                 => 30 ); // id => precedence
    var $mIsLocal = false;

    function TpFilter( $isLocal=false )
    {
        $this->mIsLocal = $isLocal;
        $this->mEscapeChar = chr(92);

    } // end of member function TpFilter

    function LoadFromKvp( $filterString )
    {
        global $g_dlog;

        $g_dlog->debug( '[KVP filter parsing]' );
        $g_dlog->debug( 'Filter string: ['.$filterString.']' );

        // Double quotes are always coming escaped!
        // TODO: how to deal with real escaped double quotes??
        $filterString = strtr( $filterString, array('\\"' => '"' ) );

        // Array of strings and TpExpression objects (literals)
        $list = $this->_ResolveLiterals( trim( $filterString ) );

        // Array of strings and TpExpression objects (literals or concepts)
        $list = $this->_ResolveConcepts( $list );

        // Nested array of strings and TpExpression objects (literals or concepts)
        $nested_list = $this->_ResolveBrackets( $list );

        // Nested array of strings and TpExpression objects (literals or concepts)
        $nested_list = $this->_TokenizeData( $nested_list );

        $root_boolean_operator = $this->_ResolveOperators( $nested_list );

        if ( is_object( $root_boolean_operator ) and
             is_subclass_of( $root_boolean_operator, 'TpBooleanOperator' ) )
        {
            $this->mRootBooleanOperator = $root_boolean_operator;
        }

    } // end of member function LoadFromKvp

    function _ResolveLiterals( $filterString )
    {
        // reads a string and returns a list of strings and literal
        // objects resolving quoted text into literal objects.
        $tokens = array();
        $inside_quote = false;
        $literal_content = '';
        $escaped = false;

        for ( $i = 0; $i < strlen( $filterString ); ++$i )
        {
            $char = $filterString[$i];

            if ( $char == '"' and ! $escaped )
            {
                if ( $inside_quote )
                {
                    $inside_quote = false;
                    array_push( $tokens,
                                new TpExpression( EXP_LITERAL, $literal_content ) );
                }
                else
                {
                    $inside_quote = true;
                    array_push( $tokens, trim( $literal_content ) );
                }

                $literal_content = '';
            }
            else if ( $char == $this->mEscapeChar )
            {
                if ( $escaped )
                {
                    $escaped = false;
                    $literal_content .= $char;
                }
                else
                {
                    $escaped = true;
                }
            }
            else
            {
                if ( $escaped )
                {
                    $literal_content .= $this->mEscapeChar;
                    $escaped = false;
                }

                $literal_content .= $char;
            }
        }

        if ( strlen( $literal_content ) > 0 )
        {
            array_push( $tokens, trim( $literal_content ) );
        }

        # test if all quotations are closed
        if ( $inside_quote )
        {
            $error = 'Incorrect number of double quotes in the filter';
            TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

            $this->mIsValid = false;
        }

        return $tokens;

    } // end of member function _ResolveLiterals

    function _ResolveConcepts( $list )
    {
        // Go through a list of strings and literals and replace concepts
        // found in the string with concept objects.

        $new_list = array();

        foreach ( $list as $part )
        {
            if ( is_string( $part ) )
            {
                $last_string = '';

                foreach ( split( ' ', $part ) as $token )
                {
                    $add_string = '';

                    // Separate possible leading brackets
                    $last_start_bracket = strrpos( $token, '(' );

                    if ( $last_start_bracket !== false )
                    {
                        $last_string .= ' '.str_repeat( '(', $last_start_bracket+1 );
                        $token = substr( $token, $last_start_bracket+1 );
                    }

                    // Separate possible ending brackets
                    $first_end_bracket = strpos( $token, ')' );

                    if ( $first_end_bracket !== false )
                    {
                        $add_string = str_repeat( ')', strlen( $token ) - $first_end_bracket );
                        $token = substr( $token, 0, $first_end_bracket );
                    }

                    if ( strlen( $token ) and $token != ',' and
                         ! in_array( strtolower( $token ),
                                     array_keys( $this->mOperators ) ) )
                    {
                        // it should be a concept (no literal, no operator).

                        // add previous string to result
                        if ( strlen( $last_string ) > 0 )
                        {
                            array_push( $new_list, $last_string );
                            $last_string = '';
                        }

                        // add concept
                        $concept = new TpExpression( EXP_CONCEPT, $token );

                        array_push( $new_list, $concept );
                    }
                    else
                    {
                        // this is no concept. remember string
                        if ( strlen( $last_string ) > 0 and
                             substr( $last_string, -1, 1 ) != '(' )
                        {
                            $last_string .= ' ';
                        }

                        $last_string .= $token;
                    }

                    $last_string .= $add_string;
                }

                if ( strlen( $last_string ) > 0 )
                {
                    array_push( $new_list, $last_string );
                }
            }
            else
            {
                array_push( $new_list, $part );
            }
        }

        return $new_list;

    } // end of member function _ResolveConcepts

    function _ResolveBrackets( $list )
    {
        // first take the string and create a tree for all brackets.

        $i = 0;

        $new_list = new TpNestedList( array('') );

        $escape = false;

        $r_current_list =& $new_list;

        foreach ( $list as $token )
        {
            if ( ! is_string( $token ) )
            {
                ++$i;

                $r_current_list->Append( $token );
                $r_current_list->Append( '' );
            }
            else
            {
                for ( $j = 0; $j < strlen( $token ); ++$j )
                {
                    $char = $token[$j];

                    ++$i;

                    if ( $char == $this->mEscapeChar and $escape )
                    {
                        $escape = false;
                        $r_current_list->AddString( -1, $this->mEscapeChar );
                    }
                    else
                    {
                        if ( $char == '(' and ! $escape )
                        {
                            $r_current_list->Append( new TpNestedList( array('') ) );
                            $r_current_list =& $r_current_list->GetElement( -1 );
                        }
                        else if ( $char == ')' and ! $escape )
                        {
                            $r_current_list =& $r_current_list->GetParent();
                            $r_current_list->Append( '' );
                        }
                        else
                        {
                            $r_current_list->AddString( -1, $char );
                        }

                        $escape = false;
                    }
                }
            }
        }

        return $new_list;

    } // end of member function _ResolveBrackets

    function _TokenizeData( $nestedList )
    {
        // prepare list by concatenating strings first
        $tmp = '';
        $new_list = array();

        foreach ( $nestedList->GetElements() as $element )
        {
            if ( is_string( $element ) )
            {
                $tmp .= $element;
            }
            else
            {
                if ( strlen( $tmp ) > 0 )
                {
                    array_push( $new_list, $tmp );
                    $tmp = '';
                }

                if ( is_object( $element ) and
                     strtolower( get_class( $element ) ) == 'tpnestedlist' )
                {
                    array_push( $new_list, $this->_TokenizeData( $element ) );
                }
                else
                {
                    array_push( $new_list, $element );
                }
            }
        }

        if ( strlen( $tmp ) > 0 )
        {
            array_push( $new_list, $tmp );
        }

        // replace old list with new one
        $tmp_nested_list = new TpNestedList();

        foreach( $new_list as $element )
        {
            $tmp_nested_list->Append( $element );
        }

        // now split the strings at whitespace!
        $new_list = array();

        foreach ( $tmp_nested_list->GetElements() as $element )
        {
            if ( is_string( $element ) )
            {
                // string data that needs to be tokenized
                foreach ( explode( ' ', $element ) as $token )
                {
                    if ( strlen( $token ) > 0 )
                    {
                        array_push( $new_list, $token );
                    }
                }
            }
            else
            {
                array_push( $new_list, $element );
            }
        }

        // replace old list with new one
        $nested_list = new TpNestedList();

        foreach ( $new_list as $element )
        {
            $nested_list->Append( $element );
        }

        // return changed list
        return $nested_list;

    } // end of member function _TokenizeData

    function _ResolveOperators( $nestedList )
    {
        // Takes a list of tokens (operatorString, literalObj, conceptObj, blockObj)
        // and returns a list of tokens with 3 items maximum.
        // It looks for the operator token with the smallest precedence and creates
        // a new Block node for all items before that token and after the smallest
        // token.

        // only process lists. return other objects
        if ( ! ( is_object( $nestedList ) and
                 strtolower( get_class( $nestedList ) ) == 'tpnestedlist' ) )
        {
            return $nestedList;
        }

        while ( $nestedList->GetSize() == 1 )
        {
            $first_element = $nestedList->GetElement(0);

            if ( is_object( $first_element ) and
                 strtolower( get_class( $first_element ) ) == 'tpnestedlist' )
            {
                $nestedList = $first_element;
            }
            else
            {
                return $first_element;
            }
        }

        # first resolve existing sub lists into proper objects
        $tokens = array();

        foreach( $nestedList->GetElements() as $element )
        {
            if ( is_object( $element ) and
                 strtolower( get_class( $element ) == 'tpnestedlist' ) )
            {
                array_push( $tokens, $this->_ResolveOperators( $element ) );
            }
            else if ( (! is_string( $element ) ) or $element != ',' )
            {
                // don't append the token "," which has no meaning,
                // eg in IN operator arg lists. Be aware that sublists still have commas
                array_push( $tokens, $element );
            }
        }

        // now reorganize list by operator precedence
        // scan children for lowest operator precedence.

        $has_string = false;
        $min_precedence = 1000;

        foreach ( $tokens as $token )
        {
            if ( is_string( $token ) )
            {
                $token = strtolower( $token );

                $has_string = true;

                if ( in_array( $token, array_keys( $this->mOperators  ) ) )
                {
                    $min_precedence = min( $min_precedence, $this->mOperators[$token] );
                }
                else
                {
                    $error = "Unknown filter element '".$token."'.";
                    TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

                    $this->mIsValid = false;

                    return null;
                }
            }
        }

        if ( ! $has_string )
        {
            // there is no string in this list!
            // return the original list if there is at least 1 item
            if ( $nestedList->GetSize() > 0 )
            {
                return $nestedList;
            }
            else
            {
                $error = 'Runtime error when parsing filter (tried to create '.
                         'operator object from empty list).';
                TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

                $this->mIsValid = false;

                return null;
            }
        }

        // find lowest operator token and create a new operator object
        $seq = -1;

        foreach ( $tokens as $token )
        {
            ++$seq;

            if ( is_string( $token ) and
                 $this->mOperators[strtolower($token)] == $min_precedence )
            {
                $op = strtolower( $token );
                break;
            }
        }

        return $this->_CreateOperator( $tokens, $seq, $op );

    } // end of member function _ResolveOperators

    function _CreateOperator( $tokens, $idx, $opClass )
    {
        if ( $opClass == 'and' or $opClass == 'or' or $opClass == 'not' )
        {
            if ( $opClass == 'not' )
            {
                if ( $idx > 0 )
                {
                    $error = "Invalid filter: 'not' operator seems to have left ".
                             'arguments. Wrong filter.';
                    TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

                    $this->mIsValid = false;

                    return null;
                }

                $op = new TpLogicalOperator( LOP_NOT );

                $arg = new TpNestedList( array_slice( $tokens, $idx+1 ) );

                $boolean_operator = $this->_ResolveOperators( $arg );

                if ( $boolean_operator != null )
                {
                    $op->AddBooleanOperator( $boolean_operator );
                }
            }
            else
            {
                if ( $opClass == 'and' )
                {
                    $op = new TpLogicalOperator( LOP_AND );
                }
                else
                {
                    $op = new TpLogicalOperator( LOP_OR );
                }

                $left_arg = new TpNestedList( array_slice( $tokens, 0, $idx ) );

                $left_boolean_operator = $this->_ResolveOperators( $left_arg );

                $right_arg = new TpNestedList( array_slice( $tokens, $idx+1 ) );

                $right_boolean_operator = $this->_ResolveOperators( $right_arg );

                if ( $left_boolean_operator != null and
                     $right_boolean_operator != null )
                {
                    $op->AddBooleanOperator( $left_boolean_operator );
                    $op->AddBooleanOperator( $right_boolean_operator );
                }
            }
        }
        else if ( $opClass == 'isnull' )
        {
            if ( count( $tokens ) != 2 )
            {
                $error = "Invalid filter: wrong number of arguments to 'isNull' operator";
                TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

                $this->mIsValid = false;

                return null;
            }

            $op = new TpComparisonOperator( COP_ISNULL );

            if ( is_object( $tokens[$idx+1] ) and
                 strtolower( get_class( $tokens[$idx+1] ) ) == 'tpexpression' )
            {
                $op->SetExpression( $tokens[$idx+1] );
            }
            else
            {
                $error = "Argument to 'isNull' operator is not an expression";
                TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

                $this->mIsValid = false;

                return null;
            }
        }
        else if ( $opClass == 'equals' or $opClass == 'like' or
                  $opClass == 'greaterthanorequals' or $opClass == 'greaterthan' or
                  $opClass == 'lessthanorequals' or $opClass == 'lessthan' )
        {
            if ( count( $tokens ) != 3 )
            {
                $error = "Invalid filter: wrong number of arguments to comparison ".
                         "operator '$opClass'";
                TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

                $this->mIsValid = false;

                return null;
            }

            if ( $opClass == 'equals' )
            {
                $op = new TpComparisonOperator( COP_EQUALS );
            }
            else if ( $opClass == 'lessthan' )
            {
                $op = new TpComparisonOperator( COP_LESSTHAN );
            }
            else if ( $opClass == 'lessthanorequals' )
            {
                $op = new TpComparisonOperator( COP_LESSTHANOREQUALS );
            }
            else if ( $opClass == 'greaterthan' )
            {
                $op = new TpComparisonOperator( COP_GREATERTHAN );
            }
            else if ( $opClass == 'greaterthanorequals' )
            {
                $op = new TpComparisonOperator( COP_GREATERTHANOREQUALS );
            }
            else if ( $opClass == 'like' )
            {
                $op = new TpComparisonOperator( COP_LIKE );
            }
            else
            {
                $error = "Invalid filter: Unknown comparison operator '$opClass'";
                TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

                $this->mIsValid = false;

                return null;
            }

            if ( is_object( $tokens[$idx-1] ) and
                 strtolower( get_class( $tokens[$idx-1] ) ) == 'tpexpression' and
                 $tokens[$idx-1]->GetType() == EXP_CONCEPT )
            {
                $op->SetExpression( $tokens[$idx-1] );

                $arg = $tokens[$idx+1];
            }
            else if ( is_object( $tokens[$idx+1] ) and
                      strtolower( get_class( $tokens[$idx+1] ) ) == 'tpexpression' and
                      $tokens[$idx+1]->GetType() == EXP_CONCEPT )
            {
                $arg = $tokens[$idx-1];

                $op->SetExpression( $tokens[$idx+1] );
            }
            else
            {
                // no need to raise error here - IsValid() can check that
            }

            if ( is_array( $arg ) )
            {
                foreach ( $arg as $el )
                {
                    if ( is_object( $el ) and
                         strtolower( get_class( $el ) ) == 'tpexpression' )
                    {
                        $op->SetExpression( $el );
                    }
                }
            }
            else if ( is_object( $arg ) and
                      strtolower( get_class( $arg ) ) == 'tpexpression' )
            {
                $op->SetExpression( $arg );
            }
        }
        else if ( $opClass == 'in' )
        {
            $num_tokens = count( $tokens );

            if ( $num_tokens < 3 )
            {
                $error = "Invalid filter: wrong number of arguments to comparison ".
                         "operator '$opClass'";
                TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

                $this->mIsValid = false;

                return null;
            }

            $op = new TpComparisonOperator( COP_IN );

            // Set base concept
            if ( is_object( $tokens[$idx-1] ) and
                 strtolower( get_class( $tokens[$idx-1] ) ) == 'tpexpression' and
                 $tokens[$idx-1]->GetType() == EXP_CONCEPT )
            {
                $op->SetExpression( $tokens[$idx-1] );
            }

            // Set arguments
            for ( $i = 2; $i < $num_tokens; ++$i )
            {
                $el = $tokens[$i];

                if ( is_object( $el ) and
                     strtolower( get_class( $el ) ) == 'tpexpression' )
                {
                    $op->SetExpression( $el );
                }
            }
        }

        return $op;

    } // end of member function _CreateOperator

    /**
     *  XML parsing methods are invoked from TpOperationParameters
     */
    function StartElement( $parser, $qualified_name, $attrs )
    {
        $name = TpUtils::GetUnqualifiedName( $qualified_name );

        if ( ! $this->mIsValid )
        {
            return;
        }

        array_push( $this->mInTags, strtolower( $name ) );

        $depth = count( $this->mInTags );

        $size = count( $this->mOperatorsStack );

        if ( $size > 0 )
        {
            $current_operator =& $this->mOperatorsStack[$size-1];
        }

        if ( strcasecmp( $name, 'filter' ) == 0 )
        {
            // nothing to do here
        }
        else if ( strcasecmp( $name, 'equals' ) == 0 )
        {
            $this->_AddOperator( new TpComparisonOperator( COP_EQUALS ) );
        }
        else if ( strcasecmp( $name, 'lessThan' ) == 0 )
        {
            $this->_AddOperator( new TpComparisonOperator( COP_LESSTHAN ) );
        }
        else if ( strcasecmp( $name, 'lessThanOrEquals' ) == 0 )
        {
            $this->_AddOperator( new TpComparisonOperator( COP_LESSTHANOREQUALS ) );
        }
        else if ( strcasecmp( $name, 'greaterThan' ) == 0 )
        {
            $this->_AddOperator( new TpComparisonOperator( COP_GREATERTHAN ) );
        }
        else if ( strcasecmp( $name, 'greaterThanOrEquals' ) == 0 )
        {
            $this->_AddOperator( new TpComparisonOperator( COP_GREATERTHANOREQUALS ) );
        }
        else if ( strcasecmp( $name, 'like' ) == 0 )
        {
            $this->_AddOperator( new TpComparisonOperator( COP_LIKE ) );
        }
        else if ( strcasecmp( $name, 'isNull' ) == 0 )
        {
            $this->_AddOperator( new TpComparisonOperator( COP_ISNULL ) );
        }
        else if ( strcasecmp( $name, 'in' ) == 0 )
        {
            $this->_AddOperator( new TpComparisonOperator( COP_IN ) );
        }
        else if ( strcasecmp( $name, 'concept' ) == 0 )
        {
            if ( isset( $current_operator ) and
                 $current_operator->GetBooleanType() == COP_TYPE and
                 isset( $attrs['id'] ) )
            {
                $current_operator->SetExpression( new TpExpression( EXP_CONCEPT,
                                                                    $attrs['id'] ) );
            }
        }
        else if ( strcasecmp( $name, 't_concept' ) == 0 )
        {
            if ( $this->mIsLocal )
            {
                if ( isset( $current_operator ) and
                     $current_operator->GetBooleanType() == COP_TYPE and
                     isset( $attrs['table'] ) )
                {
                    $concept = new TpTransparentConcept( $attrs['table'],
                                                         $attrs['field'],
                                                         $attrs['type'] );

                    $current_operator->SetExpression( new TpExpression( EXP_COLUMN,
                                                                        $concept ) );
                }
            }
            else
            {
                $error = "Unknown filter element '".$name."'.";
                TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

                $this->mIsValid = false;
            }
        }
        else if ( strcasecmp( $name, 'literal' ) == 0 )
        {
            if ( isset( $current_operator ) and
                 $current_operator->GetBooleanType() == COP_TYPE and
                 isset( $attrs['value'] ) )
            {
                $current_operator->SetExpression( new TpExpression( EXP_LITERAL,
                                                                    $attrs['value'] ) );
            }
        }
        else if ( strcasecmp( $name, 'parameter' ) == 0 )
        {
            if ( isset( $current_operator ) and
                 $current_operator->GetBooleanType() == COP_TYPE and
                 isset( $attrs['name'] ) )
            {
                $current_operator->SetExpression( new TpExpression( EXP_PARAMETER,
                                                                    $attrs['name'] ) );
            }
        }
        else if ( strcasecmp( $name, 'and' ) == 0 )
        {
            $this->_AddOperator( new TpLogicalOperator( LOP_AND ) );
        }
        else if ( strcasecmp( $name, 'or' ) == 0 )
        {
            $this->_AddOperator( new TpLogicalOperator( LOP_OR ) );
        }
        else if ( strcasecmp( $name, 'not' ) == 0 )
        {
            $this->_AddOperator( new TpLogicalOperator( LOP_NOT ) );
        }
        else if ( strcasecmp( $name, 'values' ) == 0 )
        {
            // nothing to do here ("values" is part of "in")
        }
        else
        {
            $error = "Unknown filter element '".$name."'.";
            TpDiagnostics::Append( DC_INVALID_FILTER, $error, DIAG_ERROR );

            $this->mIsValid = false;
        }

    } // end of member function StartElement

    function EndElement( $parser, $qualified_name )
    {
        if ( ! $this->mIsValid )
        {
            return;
        }

        $name = TpUtils::GetUnqualifiedName( $qualified_name );

        $depth = count( $this->mInTags );

        if ( strcasecmp( $name, 'equals' )              == 0 or
             strcasecmp( $name, 'lessThan' )            == 0 or
             strcasecmp( $name, 'lessThanOrEquals' )    == 0 or
             strcasecmp( $name, 'greaterThan' )         == 0 or
             strcasecmp( $name, 'greaterThanOrEquals' ) == 0 or
             strcasecmp( $name, 'like' )                == 0 or
             strcasecmp( $name, 'isNull' )              == 0 or
             strcasecmp( $name, 'in' )                  == 0 or
             strcasecmp( $name, 'and' )                 == 0 or
             strcasecmp( $name, 'or' )                  == 0 or
             strcasecmp( $name, 'not' )                 == 0 )
        {
            $size = count( $this->mOperatorsStack );

            $current_operator =& $this->mOperatorsStack[$size-1];

            if ( ! $current_operator->IsValid() )
            {
                $this->mIsValid = false;
            }

            array_pop( $this->mOperatorsStack );
        }

        array_pop( $this->mInTags );

    } // end of member function EndElement

    function CharacterData( $parser, $data )
    {
        if ( ! $this->mIsValid )
        {
            return;
        }

    } // end of member function CharacterData

    function _AddOperator( &$operator )
    {
        $size = count( $this->mOperatorsStack );

        if ( ! isset( $this->mRootBooleanOperator ) )
        {
            $this->mRootBooleanOperator =& $operator;
        }
        else
        {
            $current_operator =& $this->mOperatorsStack[$size-1];

            if ( $current_operator->GetBooleanType() == LOP_TYPE )
            {
                $current_operator->AddBooleanOperator( $operator );
            }
        }

        $this->mOperatorsStack[$size] =& $operator;

    } // end of member function _AddOperator

    function SetRootBooleanOperator( $rootBooleanOperator )
    {
        $this->mRootBooleanOperator = $rootBooleanOperator;

    } // end of member function SetRootBooleanOperator

    function &GetRootBooleanOperator( )
    {
        return $this->mRootBooleanOperator;

    } // end of member function GetRootBooleanOperator

    function Remove( $path )
    {
        if ( ! is_object( $this->mRootBooleanOperator ) )
        {
            return false;
        }

        if ( $path == '/0' )
        {
            if ( $this->mRootBooleanOperator->GetBooleanType() == LOP_TYPE and
                 $this->mRootBooleanOperator->GetLogicalType() == LOP_NOT )
            {
                // Replace the NOT condition by its sub conditions

                $new_ops = $this->mRootBooleanOperator->GetBooleanOperators();

                if ( count( $new_ops ) )
                {
                    // NOT operators have only one condition inside
                    $this->mRootBooleanOperator = $new_ops[0];
                }
                else
                {
                    $this->mRootBooleanOperator = null;
                }
            }
            else
            {
                $this->mRootBooleanOperator = null;
            }

            return true;
        }

        if ( $this->mRootBooleanOperator->GetBooleanType() == COP_TYPE )
        {
            $error = 'Cannot remove conditions from comparison operators';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );

            return false;
        }

        // Remove leading '/'
        $path = substr( $path, 1 );

        $exploded_path = explode( '/', $path );

        // Remove first element
        array_shift( $exploded_path );

        return $this->mRootBooleanOperator->Remove( $exploded_path );

    } // end of member function Remove

    function AddOperator( $path, $booleanType, $specificType )
    {
        $op = null;

        if ( $booleanType == LOP_TYPE )
        {
            $op = new TpLogicalOperator( $specificType );
        }
        else
        {
            $op = new TpComparisonOperator( $specificType );
        }

        if ( $path == 'root' )
        {
            $this->mRootBooleanOperator = $op;

            return true;
        }

        if ( ! is_object( $this->mRootBooleanOperator ) )
        {
            return false;
        }

        if ( $this->mRootBooleanOperator->GetBooleanType() == COP_TYPE )
        {
            $error = 'Cannot add conditions to comparison operators';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );

            return false;
        }

        // Remove leading '/'
        $path = substr( $path, 1 );

        $exploded_path = explode( '/', $path );

        // Remove first element
        array_shift( $exploded_path );

        return $this->mRootBooleanOperator->AddOperator( $exploded_path, $op );

    } // end of member function AddOperator

    function &Find( $path )
    {
        if ( $path == '/0' )
        {
            return $this->mRootBooleanOperator;
        }

        if ( ! is_object( $this->mRootBooleanOperator ) )
        {
            return null;
        }

        if ( $this->mRootBooleanOperator->GetBooleanType() == COP_TYPE )
        {
            $error = 'Cannot search on comparison operators';
            TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );

            return null;
        }

        // Remove leading '/'
        $path = substr( $path, 1 );

        $exploded_path = explode( '/', $path );

        // Remove first element
        array_shift( $exploded_path );

        return $this->mRootBooleanOperator->Find( $exploded_path );

    } // end of member function Find

    function GetSql( &$rResource )
    {
        if ( isset( $this->mRootBooleanOperator ) )
        {
            return $this->mRootBooleanOperator->GetSql( $rResource );
        }

        return '';

    } // end of member function GetSql

    function GetLogRepresentation( )
    {
        if ( isset( $this->mRootBooleanOperator ) )
        {
            return $this->mRootBooleanOperator->GetLogRepresentation();
        }

        return '';

    } // end of member function GetLogRepresentation

    function GetXml( )
    {
        if ( isset( $this->mRootBooleanOperator ) )
        {
            return $this->mRootBooleanOperator->GetXml();
        }

        return '';

    } // end of member function GetXml

    function IsValid( $force=false )
    {
        if ( $force and is_object( $this->mRootBooleanOperator ) )
        {
            return $this->mRootBooleanOperator->IsValid();
        }

        return $this->mIsValid;

    } // end of member function IsValid

    function IsEmpty( )
    {
        return ! is_object( $this->mRootBooleanOperator );

    } // end of member function IsEmpty

    function TestUrlFilterParser( $filterString )
    {
        $filterString = strtr( $filterString, array('\\"' => '"' ) );

        echo "<br/>String is: $filterString<br/>";

        $list = $this->_ResolveLiterals( trim( $filterString ) );

        echo "<br/><b>After ResolveLiterals</b><br/>";

        $i = 0;

        foreach ( $list as $el )
        {
            ++$i;

            echo "<br/><b>$i:</b>&nbsp;";

            if ( is_object( $el ) )
            {
                $class = get_class( $el );

                echo "class ($class): ".$el->GetReference();
            }
            else
            {
                echo "string: $el";
            }
        }

        $list = $this->_ResolveConcepts( $list );

        echo "<br/><br/><b>After ResolveConcepts</b><br/>";

        $i = 0;

        foreach ( $list as $el )
        {
            ++$i;

            echo "<br/><b>$i:</b>&nbsp;";

            if ( is_object( $el ) )
            {
                $class = get_class( $el );

                echo "class ($class): ".$el->GetReference();
            }
            else
            {
                echo "string: $el";
            }
        }

        $nested_list = $this->_ResolveBrackets( $list );

        echo "<br/><br/><b>After ResolveBrackets</b><br/>";
        echo '<pre>';
        echo $nested_list->ToString();
        echo '</pre>';

        $nested_list = $this->_TokenizeData( $nested_list );

        echo "<br/><br/><b>After TokenizeData</b><br/>";
        echo '<pre>';
        echo $nested_list->ToString();
        echo '</pre>';

        $root_boolean_operator = $this->_ResolveOperators( $nested_list );

        echo "<br/><br/><b>Log representation</b><br/>";
        echo $root_boolean_operator->GetLogRepresentation();

	exit();

    } // end of member function TestUrlFilterParser

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
        // Note: the other properties are being ommited since serialization
        //       is only used to generate an id for the request parameters.
        //       Unserialised objects will not work!

	return array( 'mRootBooleanOperator', 'mEscapeChar', 'mIsLocal' );

    } // end of member function __sleep

} // end of TpFilter
?>
