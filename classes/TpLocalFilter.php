<?php
/**
 * $Id: TpLocalFilter.php 264 2007-02-22 23:46:21Z rdg $
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

require_once('TpUtils.php');
require_once('TpDiagnostics.php');
require_once('TpFilter.php');
require_once('TpFilterToHtml.php');
require_once('TpFilterRefresher.php');

require_once( TP_XPATH_LIBRARY );

class TpLocalFilter
{
    var $mFilter;

    function TpLocalFilter( )
    {
        $is_local = true;

        $this->mFilter = new TpFilter( $is_local );

    } // end of member function TpLocalFilter

    function LoadDefaults( ) 
    {

    } // end of member function LoadDefaults

    function LoadFromSession( ) 
    {

    } // end of member function LoadFromSession

    function LoadFromXml( $file, $xpr=false )
    {
        if ( ! is_object( $xpr ) ) 
        {
            $xpr = new XPath();
            $xpr->setVerbose( 1 );
            $xpr->setXmlOption( XML_OPTION_CASE_FOLDING, false );
            $xpr->setXmlOption( XML_OPTION_SKIP_WHITE, true );

            if ( ! $xpr->importFromFile( $file ) )
            {
                $error = 'Could not import content from XML file: '.$xpr->getLastError();
                TpDiagnostics::Append( CFG_INTERNAL_ERROR, $error, DIAG_ERROR );
                return;
            }
        }

        $path_to_filter = '/configuration[1]/filter[1]';

        if ( $xpr->match( $path_to_filter ) )
        {
            // second parameter should be empty (no header)
            $xml_filter = $xpr->exportAsXml( $path_to_filter, '' );
        }
        else
        {
            $xml_filter = '<filter />';
        }

        $this->_LoadFilter( $xml_filter );

    } // end of member function LoadFromXml

    function SetFilter( $filter )
    {
        $this->mFilter = $filter;
        
    } // end of member function SetFilter

    function IsLoaded( )
    {
        return is_object( $this->mFilter );
        
    } // end of member function IsLoaded

    function GetXml( )
    {
        return $this->mFilter->GetXml();
        
    } // end of member function GetXml

    function GetHtml( $tablesAndColumns )
    {
        $filter_to_html = new TpFilterToHtml();

        $filter_to_html->SetTablesAndColumns( $tablesAndColumns );

        return $filter_to_html->GetHtml( $this->mFilter );
        
    } // end of member function GetHtml

    function Refresh( $tablesAndColumns )
    {
        $filter_refresher = new TpFilterRefresher();

        $filter_refresher->SetTablesAndColumns( $tablesAndColumns );

        return $filter_refresher->Refresh( $this->mFilter );
        
    } // end of member function Refresh

    function Remove( $path )
    {
        return $this->mFilter->Remove( $path );
        
    } // end of member function Remove

    function AddOperator( $path, $booleanType, $specificType )
    {
        return $this->mFilter->AddOperator( $path, $booleanType, $specificType );
        
    } // end of member function AddOperator

    function &Find( $path )
    {
        return $this->mFilter->Find( $path );
        
    } // end of member function Find

    function GetSql( &$rResource )
    {
        return $this->mFilter->GetSql( $rResource );
        
    } // end of member function GetSql

    function IsEmpty( )
    {
        return $this->mFilter->IsEmpty();
        
    } // end of member function IsEmpty

    function IsValid( $force )
    {
        return $this->mFilter->IsValid( $force );
        
    } // end of member function IsValid

    function _LoadFilter( $xml ) 
    {
        if ( ! $this->mFilter->IsEmpty() )
        {
            // Overwrite filter
            $is_local = true;

            $this->mFilter = new TpFilter( $is_local );
        }

        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this->mFilter );
        xml_set_element_handler( $parser, 'StartElement', 'EndElement' );
        xml_set_character_data_handler( $parser, 'CharacterData' );

        if ( ! xml_parse( $parser, $xml, true ) ) 
        {
            $error = sprintf( "XML error: %s at line %d",
                              xml_error_string( xml_get_error_code( $parser ) ),
                              xml_get_current_line_number( $parser ) );

            TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_ERROR );
            return false;
        }

        xml_parser_free( $parser );

        return true;

    } // end of member function _LoadFilter

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
      return array( 'mFilter' );

    } // end of member function __sleep

} // end of TpLocalFilter
?>