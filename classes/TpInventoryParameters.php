<?php
/**
 * $Id: TpInventoryParameters.php 750 2008-08-22 22:59:27Z rdg $
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

require_once('TpOperationParameters.php');
require_once('TpFilter.php');

class TpInventoryParameters extends TpOperationParameters
{
    var $mConcepts = array(); // concept id => tag name

    function TpInventoryParameters( )
    {
        $this->TpOperationParameters();

        $this->mRevision = '$Revision: 750 $';

    } // end of member function TpInventoryParameters

    function LoadKvpParameters()
    {
        // Since there can be multiple parameters with the same name 
        // (concept and tagname) we need a custom HTTP query string parser
        $parameters = $this->_ParseQueryString();

        // Concepts
        if ( isset( $parameters['concept'] ) )
        {
            $concept = $parameters['concept'];
        }
        else if ( isset( $parameters['c'] ) )
        {
            $concept = $parameters['c'];
        }

        if ( isset( $parameters['tagname'] ) )
        {
            $tag_name = $parameters['tagname'];
        }
        else if ( isset( $parameters['n'] ) )
        {
            $tag_name = $parameters['n'];
        }

        if ( isset( $concept ) )
        {
            if ( is_array( $concept ) )
            {
                $i = 0;

                foreach ( $concept as $concept_id )
                {
                    $tag = 'value';

                    if ( isset( $tag_name ) and is_array( $tag_name ) and isset( $tag_name[$i] ) and ! empty( $tag_name[$i] ) )
                    {
                        $tag = $tag_name[$i];
                    }

                    $this->mConcepts[$concept_id] = $tag;

                    ++$i;
                }
            }
            else if ( is_string( $concept ) )
            {
                $tag = 'value';

                if ( isset( $tag_name ) and ( ! is_array( $tag_name ) ) and ! empty( $tag_name ) )
                {
                    $tag = $tag_name;
                }

                $this->mConcepts[$concept] = $tag;
            }
        }

        return parent::LoadKvpParameters();

    } // end of member function LoadKvpParameters

    function _ParseQueryString( )
    {
        $parameters = array();

        // See if there is any raw post
        $raw_post = '';

        if ( $fp = fopen( 'php://input', 'r' ) ) 
        {
            while ( $data = fread( $fp, 4096 ) ) 
            {
                $raw_post .= $data;
            }

            fclose( $fp );
        }
        else
        {
            // TODO: raise an error here!
        }

        // Concatenate with query string and raw post before spliting
        $raw_input_items = split( '&', $_SERVER['QUERY_STRING'] . '&' . $raw_post );

        foreach ( $raw_input_items as $input_item )
        {
            // split into name/value pair
            if ( $input_item != '' )
            {
                $item = split( '=', $input_item );

                $key = urldecode( $item[0] );
               
                $value = ( empty( $item[1] ) ) ? '' : urldecode( $item[1] );
            
                if ( ! isset( $parameters[$key] ) )
                {
                    $parameters[$key] = $value;
                }
                elseif ( ! is_array( $parameters[$key] ) )
                {
                    $first = $parameters[$key];
                    $parameters[$key] = array();
                    $parameters[$key][]= $first;
                    $parameters[$key][]= $value;
                }
                else
                {
                    $parameters[$key][]= $value;
                }
            }
        }

        return $parameters;

    } // end of member function _ParseQueryString

    function StartElement( $parser, $qualified_name, $attrs )
    {
        $name = TpUtils::GetUnqualifiedName( $qualified_name );

        parent::StartElement( $parser, $qualified_name, $attrs );

        $depth = count( $this->mInTags );

        // <concept> element whose parent is <concepts>
        if ( $depth > 1 and $this->mInTags[$depth-2] == 'concepts' and
             strcasecmp( $name, 'concept' ) == 0 and 
             isset( $attrs['id'] ) )
        {
            $tag = 'value';

            if ( isset( $attrs['tagName'] ) )
            {
                $tag = $attrs['tagName'];
            }

            $this->mConcepts[$attrs['id']] = $tag;
        }

    } // end of member function StartElement

    function EndElement( $parser, $qualified_name ) 
    {
        parent::EndElement( $parser, $qualified_name );

    } // end of member function EndElement

    function CharacterData( $parser, $data ) 
    {
        parent::CharacterData( $parser, $data );

    } // end of member function CharacterData

    function GetConcepts( )
    {
        return $this->mConcepts;

    } // end of member function GetConcepts

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array_merge( parent::__sleep(), array( 'mConcepts' ) );

    } // end of member function __sleep

} // end of TpInventoryParameters
?>