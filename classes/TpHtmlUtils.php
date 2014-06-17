<?php
/**
 * $Id: TpHtmlUtils.php 1997 2009-09-07 22:45:02Z rdg $
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

class TpHtmlUtils // only class methods
{
    static function GetCombo( $name, $value, $options, $multiple=false, $size=false, $onChange='' )
    {
        $str_size = (gettype( $size ) == 'integer') ? ' size="'.$size.'"' : '' ;

        $str_on_change = ($onChange) ? sprintf( ' onchange="%s"', $onChange ) : '';

        $str_multiple = ($multiple) ? ' multiple="1"' : '';

        $html = sprintf( '<select name="%s"%s%s%s>', 
                         $name,
                         $str_multiple,
                         $str_size,
                         $str_on_change );

        foreach ( $options as $option_id => $option_value ) 
        {
            $selected = ($value == $option_id) ? 'selected="selected"' : '';

            $html .= sprintf( '<option value="%s" %s>%s', $option_id, $selected, $option_value );
        }

        $html .= '</select>';

        return $html;

    } // end of member function GetCombo

    static function GetCheckboxes( $prefix, $values, $options )
    {
        $html = '';

        $cnt = 1;

        foreach ( $options as $option_id => $option_value )
        {
            $checked = ( in_array( $option_id, $values ) ) ? ' checked' : '';

            $name = $prefix . '_' . $cnt;

            $html .= sprintf( '&nbsp;<input type="checkbox" class="checkbox" name="%s" value="%s"%s>&nbsp;<span class="label">%s</span>', $name, $option_id, $checked, $option_value );

            ++$cnt;
        }

        return $html;

    } // end of member function GetCheckboxes

    static function GetRadio( $name, $value, $options )
    {
        $html = '';

        foreach ( $options as $option_id => $option_value )
        {
            $selected = ($value == $option_id) ? ' checked' : '';

            $html .= sprintf( '&nbsp;<input type="radio" class="checkbox" name="%s" value="%s"%s>&nbsp;<span class="label">%s</span>', $name, $option_id, $selected, $option_value );
        }

        return $html;

    } // end of member function GetRadio

} // end of TpHtmlUtils
?>