<?php
/**
 * $Id: CnsSchemaHandler_v1.php 439 2007-10-08 05:42:11Z rdg $
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
 * @author Roger Hyam <roger [at] tdwg . org>
 */

require_once('TpConceptualSchemaHandler.php');
require_once('TpDiagnostics.php');
require_once('TpUtils.php');

class CnsSchemaHandler_v1 extends TpConceptualSchemaHandler
{
    var $mConceptualSchema;
    var $mXmlSchemaNs = 'http://www.w3.org/2001/XMLSchema';
    var $mNamespaces = array();
    var $mConcept;
    var $mMode; // the kind of data we are digesting
    var $mConceptSourceTarget;  // alias of concept source to load
    var $mCurrentConceptSource; // alias of current concept source when parsing
    var $mPreparedConcept = false; // flag to indicate if at least one concept was loaded
    var $mLastLabel = '';
    var $mLastNamespace = '';

    function CnsSchemaHandler_v1( ) 
    {

    } // end of member function DarwinSchemaHandler_v2

    function Load( &$conceptualSchema ) 
    {
        $this->mConceptualSchema =& $conceptualSchema;

        $file = $conceptualSchema->GetLocation();

        // If location follows http://host/path/file#somealias
        // then load only the concepts from the schema with alias "somealias",
        // otherwise load only concepts from the last schema in the file.
        $parts = explode( '#', $file );

        if ( count( $parts ) == 2 and strlen( $parts[1] ) )
        {
            $this->mConceptSourceTarget = $parts[1];

            $file = $parts[0];
        }

        // there is nothing here...

        $lines = $this->ReadFile( $file );
        
        foreach ( $lines as $line_number => $line )
        {
            // clear away white space
            $t_line = trim( $line );
            
            // ignore blank lines
            if ( strlen( $t_line ) == 0 ) continue;
            
            // ignore comment lines
            if ( strpos( $t_line, '#' ) === 0 ) continue;
            
            // we are changing mode if the line just contains something in
            // square brackets
            if ( ereg( "^\[.+]$", $t_line ) )
            {
                $this->mMode = $t_line;

                if ( ( ! is_null( $this->mConceptSourceTarget ) ) and
		       $this->mPreparedConcept )
                {
                    // no need to continue if concepts were already loaded
                    break;
                }

                if ( $t_line == '[concept_source]' )
                {
                    $this->mConceptualSchema->Reset();
                }

                continue;
            }
            
            // got to here so we are loading a KVP of a kind
            $kvp = explode( '=', $t_line );
            $key = trim( $kvp[0] );
            $value = trim( $kvp[1] );
            
            // fail if the line can't be cut in two
            if ( count($kvp) != 2 || strlen($key) == 0 || strlen($value) == 0 )
            {
                //$error = "Problems parsing line $line_number of $file. Could not split it about an = sign.";
                //TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );
                continue;
            }
            
            // now switching depending on the mode we are in
            
            // we are loading a new concept source
            if ( $this->mMode == '[concept_source]' )
            {
                if ( $key == 'alias' )
                {
                    $this->mCurrentConceptSource = $value;
                }
                else if ( $key == 'label' )
                {
                    $this->mLastLabel = $value;
                }
                else if ( $key == 'namespace' )
                {
                    $this->mLastNamespace = $value;
                }

                continue;
            }

            // we are working through aliases associated with the last concept source loaded
            if ( $this->mMode == '[aliases]' )
            {
                if ( is_null( $this->mConceptSourceTarget ) or
                     $this->mCurrentConceptSource == $this->mConceptSourceTarget )
                {
                    $this->mConceptualSchema->AddConcept( $this->PrepareConcept( $key, $value ) );
                }

                continue;
            }
            
            if ( $this->mPreparedConcept and 
                 $this->mCurrentConceptSource == $this->mConceptSourceTarget )
            {
                break; // no need to continue parsing, already loaded all we need
            }

            // if we have got this far then the line does not fall in a mode we understand
            //$error = "Ignoring line $line_number of $file. I do not understand mode $this->mMode.";
            //TpDiagnostics::Append( DC_GENERAL_ERROR, $error, DIAG_WARN );

        } // end foreach

        // Load additional schema properties
        if ( $this->mPreparedConcept )
        {
            if ( strlen( $this->mLastLabel ) )
            {
                $this->mConceptualSchema->SetAlias( $this->mLastLabel );
            }

            if ( strlen( $this->mLastNamespace ) )
            {
                $this->mConceptualSchema->SetNamespace( $this->mLastNamespace );
            }
        }

        return true;

    } // end of member function Load

    function PrepareConcept( $key, $value ) 
    {
        $this->mPreparedConcept = true;

        $doc_uri = '';

        $ns = $this->mConceptualSchema->GetNamespace();

        if ( strpos( $ns, 'http://rs.tdwg.org/ontology/voc/' ) === 0 )
        {
            // the convention is followed that the documentation is at the same
            // location as the CNS file but with a .html ending instead of a .txt ending
            // and theres is an anchor of the alias within that file
            $cns_location = $this->mConceptualSchema->GetLocation();
            $doc_file_location = eregi_replace( '\.txt$', '.html', $cns_location );
            $doc_uri = $doc_file_location . '#' . $key;
        }

        $concept = new TpConcept();
        $concept->SetId( $value ); // the concept id from the cns
        $concept->SetDocumentation( $doc_uri ); // derived above
        $concept->SetName( $key ); // the alias from the cns
        $concept->SetRequired( false ); // no info in the cns
        $concept->SetType( 'http://www.w3.org/2001/XMLSchema:string' ); // defaults to string as we don't have type info in cns

    	return $concept;

    } // end of member function PrepareConcept

    /**
    * This is needed to overcome the problem 
    * with allow_url_fopen being turned off by some 
    * ISPs
    *
    * Returns the file as an array of lines
    *
    */
    function ReadFile( $file )
    {
        $lines = array();
        
        // if we can transparently open URLs
        // or this is just a local file 
        if ( ini_get('allow_url_fopen') || ! TpUtils::IsUrl( $file ) )
        {
            if ( ! ( $lines = file( $file ) ) ) 
            {
                $error = "Could not open remote file: $file";
                TpDiagnostics::Append( DC_IO_ERROR, $error, DIAG_ERROR );

                return false;
            }
        }
        // this is a URL and we are not permitted to fopen urls
        // we must use cURL
        else
        {
            $fp = tmpfile();  // open a temporary file to write to
            $ch = curl_init( $file );  // open a curl session
            curl_setopt( $ch, CURLOPT_FILE, $fp ); // tell the curl session to write results to the temp file
            curl_exec( $ch ); // execute the curl session
            curl_close( $ch ); // close it

            // read the temp file into an array
            rewind( $fp );
            $lines = array();

            while ( ! feof( $fp ) )
            {
                $lines[] = fgets( $fp );
            }

            fclose( $fp ); // close the temp file
        }
        
        return $lines;
        
    } // end member function ReadFile
    
} // end of CnsSchemaHandler_v1
?>