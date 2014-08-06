<?php
/**
 * $Id: TpNestedList.php 447 2007-10-26 03:16:29Z rdg $
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
 * This class has been deliberately translated from the
 * PyWrapper data provider software (http://www.pywrapper.org/)
 * with generous permission from its author. Many thanks to Markus
 * Döring!
 */

class TpNestedList
{
    var $mList;
    var $mrParent;

    function TpNestedList( $list=array() )
    {
        $this->mList = $list;

    } // end of member function TpNestedList

    function ToString( )
    {
        $pre = "\n" . str_repeat( '  ', $this->GetDepth() );

        $tree = array( sprintf( "%s+--", $pre ) );

        foreach ( $this->mList as $element )
        {
            if ( is_object( $element ) and
                 strtolower( get_class( $element ) ) == 'tpnestedlist' )
            {
                array_push( $tree, $element->ToString() );
            }
            else
            {
                if ( is_object( $element ) )
                {
                    array_push( $tree, sprintf( "%s  %s", $pre, $element->ToString() ) );
                }
                else
                {
                    array_push( $tree, sprintf( "%s  [%s]", $pre, $element ) );
                }
            }
        }

        return implode( '', $tree );

    } // end of member function ToString

    function Append( $element )
    {
        if ( is_array( $element ) )
        {
            $element = new TpNestedList( $element );
        }

        if ( is_object( $element ) and
             strtolower( get_class( $element ) ) == 'tpnestedlist' )
        {
            $element->SetParent( $this );
        }

        $this->mList[] = $element;

    } // end of member function GetParent

    function SetParent( &$rParent )
    {
        $this->mrParent =& $rParent;

    } // end of member function SetParent

    function &GetParent( )
    {
        return $this->mrParent;

    } // end of member function GetParent

    function &GetElement( $index )
    {
        if ( $index == -1 )
        {
            $index = count( $this->mList ) - 1;
        }

        return $this->mList[$index];

    } // end of member function GetElement

    function GetElements( )
    {
        return $this->mList;

    } // end of member function GetElments

    function AddString( $index, $string )
    {
        if ( $index == -1 )
        {
            $index = count( $this->mList ) - 1;
        }

        $this->mList[$index] .= $string;

    } // end of member function AddString

    function GetDepth( )
    {
        if ( $this->mrParent == null )
        {
            return 0;
        }

        return $this->mrParent->GetDepth()+ 1;

    } // end of member function GetDepth

    function GetSize( )
    {
        return count( $this->mList );

    } // end of member function GetSize

} // end of TpNestedList
?>