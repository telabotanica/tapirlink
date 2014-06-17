<?php
/**
 * $Id: XsNamespaceManager.php 720 2008-06-17 02:16:10Z rdg $
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

require_once(dirname(__FILE__).'/XsManager.php');
require_once(dirname(__FILE__).'/XsNamespace.php');

class XsNamespaceManager
{
    var $mData = array();  // parser obj => array( sequence => XsNamespace obj )

    // No constructor - this class uses the singleton pattern
    // Use GetInstance instead

    function &GetInstance( )
    {
        static $instance;

        if ( ! isset( $instance ) )
        {
            $instance = new XsNamespaceManager();
        }

        return $instance;

    } // end of member function GetInstance

    function AddNamespace( $parser, $prefix, $uri, $flag=null )
    {
        $r_manager =& XsManager::GetInstance();

        $r_manager->GetMsg( 'Adding namespace declaration '.$uri.' for prefix '.$prefix.' ('.$parser.') with flag: '.$flag );

        if ( empty( $prefix ) )
        {
            $prefix = 'default';
        }

        $namespace = new XsNamespace( $prefix, $uri, $flag );

        // Add namespace declaration
        // Note: namespaces that are redeclared will be appended on the list

        $this->mData[$parser][] = $namespace;

    } // end of member function AddNamespace

    function GetNamespace( $parser, $prefix )
    {
        $r_manager =& XsManager::GetInstance();

        if ( ! isset( $this->mData[$parser] ) )
        {
            $error = $r_manager->GetMsg( 'Could not find the expected parser object in '.
                                         'namespace manager ('.$parser.')' );

            trigger_error( $error, E_USER_ERROR );
            return null;
        }

        // Note: this function will always retrieve the last declaration for the prefix

        $parser_namespaces = $this->mData[$parser];

        // Search backwards
        for ( $i = count( $parser_namespaces ) - 1; $i >= 0; --$i )
        {
            $ns = $parser_namespaces[$i];

            if ( $ns->GetPrefix() == $prefix )
            {
                return $ns->GetUri();
            }
        }

        $error = $r_manager->GetMsg( 'Could not find namespace declaration for prefix "'.
                                     $prefix.'" ('.$parser.')' );

        trigger_error( $error, E_USER_ERROR );
        return null;

    } // end of member function GetNamespace

    function GetPrefix( $parser, $namespace )
    {
        // Note: this function will always retrieve the last declaration for the namespace

        $parser_namespaces = $this->mData[$parser];

        // Search backwards
        for ( $i = count( $parser_namespaces ) - 1; $i >= 0; --$i )
        {
            $ns = $parser_namespaces[$i];

            if ( $ns->GetUri() == $namespace )
            {
                return $ns->GetPrefix();
            }
        }

        return null;

    } // end of member function GetPrefix

    function GetFlaggedNamespaces( $parser, $flag )
    {
        $r_manager =& XsManager::GetInstance();

        $r_manager->Debug( 'Getting flagged namespaces ('.$parser.')' );

        if ( ! isset( $this->mData[$parser] ) )
        {
            return array();
        }

        $parser_namespaces = $this->mData[$parser];

        $namespaces_to_return = array();

        for ( $i = 0; $i < count( $parser_namespaces ); ++$i )
        {
            $ns = $parser_namespaces[$i];

            if ( $ns->HasFlag( $flag ) )
            {
                array_push( $namespaces_to_return, $ns );
            }
        }

        return $namespaces_to_return;

    } // end of member function GetFlaggedNamespaces

    function RemoveFlag( $parser, $flag )
    {
        $r_parser_namespaces =& $this->mData[$parser];

        for ( $i = 0; $i < count( $r_parser_namespaces ); ++$i )
        {
            $r_parser_namespaces[$i]->RemoveFlag( 'm' );
        }

    } // end of member function RemoveFlag

    function GetAllNamespaces( )
    {
        $namespaces = array();

        foreach ( $this->mData as $parser => $parser_namespaces )
        {
            foreach ( $parser_namespaces as $seq => $ns_obj )
            {
                $namespaces[$ns_obj->GetUri()] = $ns_obj->GetPrefix();
            }
        }

        return $namespaces;

    } // end of member function GetAllNamespaces

} // end of XsNamespaceManager
?>