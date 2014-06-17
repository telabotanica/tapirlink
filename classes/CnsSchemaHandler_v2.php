<?php
/**
 * $Id: CnsSchemaHandler_v2.php 2022 2010-09-02 20:30:25Z rdg $
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

require_once('TpConceptualSchemaHandler.php');
require_once('TpUtils.php');
require_once('TpDiagnostics.php');
require_once('TpConceptualSchema.php');
require_once('TpConcept.php');

// This class loads concepts from the new CNS file format in XML, such as:
// http://rs.tdwg.org/tapir/cns/alias.xml
class CnsSchemaHandler_v2 extends TpConceptualSchemaHandler
{
    var $mDesiredSchemaAlias;
    var $mAddConcepts = null; // flag to indicate if concepts should be added
                              // (if user specified a schema alias, only concepts from that
                              // schema should be added).
    var $mConceptHasAlias = false; // flag to indicate if concept has an alias
    var $mrConceptualSchema;
    var $mCurrentSchemaNamespace;
    var $mCurrentSchemaAlias;
    var $mCurrentSchemaLocation;
    var $mCurrentSchemaLabel;
    var $mCurrentConcept;
    var $mInTags = array();
    var $mPath;
    var $mIgnore = null; // Handler name when parsing should be ignored

    function CnsSchemaHandler_v2( ) 
    {
        if ( version_compare( phpversion(), '5.2.10', '<' ) > 0  )
        {
            $this->mIgnore = '_Ignore';
        }

    } // end of member function CnsSchemaHandler_v2

    function Load( &$conceptualSchema ) 
    {
        $this->mrConceptualSchema =& $conceptualSchema;

        $file = $conceptualSchema->GetSource();

        // If location follows http://host/path/file#somealias
        // then load only the concepts from the schema with alias "somealias",
        // otherwise load the first schema in the file.
        $parts = explode( '#', $file );

        if ( count( $parts ) == 2 and strlen( $parts[1] ) )
        {
            $this->mDesiredSchemaAlias = $parts[1];

            $file = $parts[0];
        }

        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_element_handler( $parser, '_StartElement', '_EndElement' );
        xml_set_character_data_handler( $parser, '_CharacterData' );

        $fp = TpUtils::GetFileHandle( $file );

        if ( ! is_resource( $fp ) )
        {
            $error = 'Could not open file: '.$file;
            TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );
            return false;
        }

        while ( $data = fread( $fp, 4096 ) ) 
        {
            if ( ! xml_parse( $parser, $data, feof( $fp ) ) ) 
            {
                $error = sprintf( "XML error: %s at line %d",
                                  xml_error_string( xml_get_error_code( $parser ) ),
                                  xml_get_current_line_number( $parser ) );

                TpDiagnostics::Append( DC_XML_PARSE_ERROR, $error, DIAG_ERROR );
                return false;
            }
        }

        xml_parser_free( $parser );
        fclose( $fp );

        if ( ! count( $this->mrConceptualSchema->GetConcepts() ) )
        {
            $error = 'Could not load any concepts from the specified schema';
            TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );
            return false;
        }

        return true;

    } // end of member function Load

    function _Ignore( $parser, $data )
    {
        return;
    } // end of member function _Ignore

    function _StartElement( $parser, $name, $attrs ) 
    {
        array_push( $this->mInTags, $name );

        $this->mPath = implode( '/', $this->mInTags );

        // Schema element
        if ( $name == 'schema' and isset( $attrs['namespace'] ) )
        {
            $this->mCurrentSchemaNamespace = $attrs['namespace'];
        }
        // Concepts element
        else if ( $name == 'concepts' )
        {
           if ( $this->mCurrentSchemaAlias == $this->mDesiredSchemaAlias )
            {
		$this->mrConceptualSchema->SetAlias( $this->mDesiredSchemaAlias );
                $this->mrConceptualSchema->SetNamespace( $this->mCurrentSchemaNamespace );
                $this->mrConceptualSchema->SetLocation( $this->mCurrentSchemaLocation );

                $this->mAddConcepts = true;
            }
            else if ( empty( $this->mDesiredSchemaAlias ) and is_null( $this->mAddConcepts ) )
            {
		$this->mrConceptualSchema->SetAlias( $this->mCurrentSchemaAlias );
                $this->mrConceptualSchema->SetNamespace( $this->mCurrentSchemaNamespace );
                $this->mrConceptualSchema->SetLocation( $this->mCurrentSchemaLocation );

                $this->mAddConcepts = true;
            }
	    else
            {
                // Ignore start and character events until the next </schema>
                xml_set_element_handler( $parser, $this->mIgnore, '_EndElement' );
                xml_set_character_data_handler( $parser, $this->mIgnore );
            }
        }
        // Concept element
        else if ( $name == 'concept' )
        {
            $this->mConceptHasAlias = false;

            if ( $this->mAddConcepts and isset( $attrs['id'] ) )
            {
                $this->mCurrentConcept = new TpConcept();
                $this->mCurrentConcept->SetRequired( false );
                $this->mCurrentConcept->SetId( $attrs['id'] );

                if ( isset( $attrs['required'] ) and 
                     ( $attrs['required'] == '1' or 
                       strtolower( $attrs['required'] ) == 'true' ) )
                {
                    $this->mCurrentConcept->SetRequired( true );
                }
            }
            else
            {
                $this->mCurrentConcept = null;
            }
        }

    } // end of member function _StartElement

    function _EndElement( $parser, $name ) 
    {
        // Concept element
        if ( $name == 'concept' )
        {
            if ( $this->mAddConcepts and 
                 is_object( $this->mCurrentConcept ) and 
                 $this->mConceptHasAlias )
            {
                $this->mrConceptualSchema->AddConcept( $this->mCurrentConcept );

                $this->mCurrentConcept = null;
            }
        }
        // Schema element
        else if ( $name == 'schema' )
        {
            if ( $this->mCurrentSchemaAlias == $this->mDesiredSchemaAlias or 
                 empty( $this->mDesiredSchemaAlias ) )
            {
                // Interrupt all parsing
                xml_set_element_handler( $parser, $this->mIgnore, $this->mIgnore );
                xml_set_character_data_handler( $parser, $this->mIgnore );
            }
            else
            {
                // Restart character and start events
                xml_set_element_handler( $parser, '_StartElement', '_EndElement' );
                xml_set_character_data_handler( $parser, '_CharacterData' );

                $this->mInTags = array( 'cns', 'schema' );
            }
        }

        // When character and start element handlers are deactivated, the
        // following lines will assign wrong values to the corresponding properties.
        // This should not be a problem because only the character handler depends
        // on them and it will get fixed as soon as the handlers are reactivated.
        // (see mInTags assignment in the previoud lines)
        array_pop( $this->mInTags );

        $this->mPath = implode( '/', $this->mInTags );

    } // end of member function _EndElement

    function _CharacterData( $parser, $data ) 
    {
        $data = trim( $data );

        if ( ! strlen( $data ) )
        {
            return;
        }

        // Schema alias
        if ( $this->mPath == 'cns/schema/alias' )
        {
            $this->mCurrentSchemaAlias = $data;
        }
        // Schema location
        else if ( $this->mPath == 'cns/schema/location' )
        {
            $this->mCurrentSchemaLocation = $data;
        }
        // Schema label
        else if ( $this->mPath == 'cns/schema/label' )
        {
            $this->mCurrentSchemaLabel = $data;
        }
        // Concept alias
        else if ( $this->mPath == 'cns/schema/concepts/concept/alias' )
        {
            if ( is_object( $this->mCurrentConcept ) )
            {
                $this->mConceptHasAlias = true;

                $this->mCurrentConcept->SetName( $data );
            }
        }
        // Concept datatype
        else if ( $this->mPath == 'cns/schema/concepts/concept/datatype' )
        {
            if ( is_object( $this->mCurrentConcept ) )
            {
                $this->mCurrentConcept->SetType( $data );
            }
        }
        // Concept documentation
        else if ( $this->mPath == 'cns/schema/concepts/concept/doc' )
        {
            if ( is_object( $this->mCurrentConcept ) )
            {
                $this->mCurrentConcept->SetDocumentation( $data );
            }
        }

    } // end of member function _CharacterData

} // end of CnsSchemaHandler_v2
?>