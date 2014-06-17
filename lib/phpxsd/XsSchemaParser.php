<?php
/**
 * $Id: XsSchemaParser.php 1946 2008-10-26 11:02:14Z rdg $
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
 * Parts of this class have been based on the API documentation of 
 * xsom (https://xsom.dev.java.net/) written by Kohsuke Kawaguchi.
 */

define( 'PHPXSD_VERSION', '0.2.2' );

define( 'PHPXSD_LIB_DIR', dirname(__FILE__) );

require_once(PHPXSD_LIB_DIR.'/XsManager.php');
require_once(PHPXSD_LIB_DIR.'/XsSchema.php');
require_once(PHPXSD_LIB_DIR.'/XsNamespaceManager.php');
require_once(PHPXSD_LIB_DIR.'/XsElementDecl.php');
require_once(PHPXSD_LIB_DIR.'/XsAttributeDecl.php');
require_once(PHPXSD_LIB_DIR.'/XsAttributeUse.php');
require_once(PHPXSD_LIB_DIR.'/XsComplexType.php');
require_once(PHPXSD_LIB_DIR.'/XsSimpleType.php');
require_once(PHPXSD_LIB_DIR.'/XsModelGroup.php');

class XsSchemaParser
{
    // User defined properties
    var $mMaxNumberOfLoadedSchemas = 15;
    var $mTargetNamespaceMustBePresent = true;
    var $mFileOpenCallback; // Function used to open files

    // Internal properties
    var $mParser;
    var $mInTagsStack = array( array() ); // array of "intags" arrays (element stack  
                                          // during XML parsing) for each schema being 
                                          // parsed
    var $mCurrentLocationStack = array(); // stack of file URIs, one for each schema 
                                          // being parsed
    var $mIncludeStack = array(); // stack of boolan flags, one for each schema 
                                  // being parsed, to indicate if the current
                                  // schema was included or not (imported)
    var $mTargetNamespace;
    var $mTargetNamespaces = array(); // stack to keep track of current namespace
    var $mObjects = array();
    var $mIgnoreNode;
    var $mNumParsings = 0; // Control the maximum number of consecutive includes/imports
    var $mNamespaces = array(); // namespace stack to avoid circular references
                                // when parsing <import> statements and to store all
                                // global declarations
                                //
                                // namespace => XsNamespace obj
    var $mUnsupportedConstructs = array(); // construct => array of schema location

    /**
     * Constructor.
     */
    function XsSchemaParser( )
    {
        $this->mFileOpenCallback = array( $this, '_Fopen' );

    } // end of member function XsSchemaParser

    /**
     * Returns the unqualified name from a 'namespace:name' string.
     * @param fullName string
     */
    function _GetUnqualifiedName( $fullName ) {

        $last_colon = strrpos( $fullName, ':' );

        if ( $last_colon === false ) {

            return $fullName;
        }

        return substr( $fullName, $last_colon + 1 );

    } // end of _GetUnqualifiedName

    /**
     * Simple array dumper. For debugging stuff.
     * @param a Array.
     */
    function _DumpArray( $a )
    {
        if ( ! is_array( $a ) ) 
        {
            return $a;
        }

        $s = '';

        foreach ( $a as $key => $val ) 
        {
            $s .= "\n(".$key.')='.$val;
        }

        return $s;

    } // end of _DumpArray

    /**
     * Check if a URL is absolute.
     * @param $url String.
     */
    function _IsAbsoluteUrl( $url )
    {
        $parts = parse_url( $url );

        return ( $parts and isset( $parts['scheme'] ) and isset( $parts['host'] ) );

    } // end of _IsAbsoluteUrl

    /**
     * Sets a callback function for opening files. The function must be accessible
     * when "Parse" is called, and it must return a file handle.
     * @param $callback String Callback function in a suitable format for call_user_func.
     */
    function SetFileOpenCallback( $callback )
    {
        $this->mFileOpenCallback = $callback;

    } // end of SetFileOpenCallback

    /**
     * Try to open a file with fopen.
     * @param $location String.
     */
    function _Fopen( $location )
    {
        return fopen( $location, 'r' );

    } // end of _Fopen

    function Parse( $location, $wasIncluded=false )
    {
        $r_manager =& XsManager::GetInstance();

        $r_manager->Debug( 'Parsing schema '.$location );

        if ( $this->mNumParsings > $this->mMaxNumberOfLoadedSchemas )
        {
            $msg = $r_manager->GetMsg( 'Exceeded maximum number of parsed schemas. '.
                                       'Aborted parsing of "'.$location.'"' );

            trigger_error( $msg, E_USER_ERROR );
            return;
        }

        // Try to figure out the absolute URL if location is relative
        if ( ! $this->_IsAbsoluteUrl( $location ) // URI is not absolute already (or is wrong!)
             and
             count( $this->mCurrentLocationStack ) // this is not the first call to the parser
                                                   // and we have a base URI to work with
           )
        {
            //  RDH: 20070525
            //  relative location may start with dir name or
            //  have a series parent dirs like "../../mydir/myfile.xsd"
            //  changed to catch this.

            $including_location = end( $this->mCurrentLocationStack );
            
            $r_manager->Debug( 'Calculating absolute URI from '. $including_location . ' and '. $location);
            
            $base_parts = explode( '/', $including_location );

            array_pop( $base_parts ); // Remove file name from location
            
            $relative_parts = explode( '/', $location );
            
            // chop off parent directories
            while( $relative_parts[0] == '..' )
            {
                array_shift( $relative_parts );
                array_pop( $base_parts );
            }
            
            $base_location = implode( '/', $base_parts );
            $relative_location = implode( '/', $relative_parts );

            // Replace with absolute location
            $location = $base_location . '/' . $relative_location;
            
            $r_manager->Debug( 'New Location URI is '. $location );
        }

        // See if we have parsed the file before (location here should be always absolute)
        foreach ( $this->mNamespaces as $namespace => $ns_obj )
        {
            if ( $ns_obj->HasSchema( $location ) )
            {
                // Before skipping, see if it's necessary to add the corresponding
                // XsSchema object to the current namespace
                if ( $wasIncluded )
                {
                    $current_namespace = end( $this->mTargetNamespaces );

                    if ( ! $this->mNamespaces[$current_namespace]->HasSchema( $location ) )
                    {
                        $r_schema =& $ns_obj->GetSchema( $location ); 

                        $this->mNamespaces[$current_namespace]->AddSchema( $r_schema );
                    }
                }

                $r_manager->Debug( 'Skipping XML Schema that was already parsed ('.$location.')' );

                return;
            }
        }

        ++$this->mNumParsings;

        $parser = xml_parser_create_ns();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object( $parser, $this );
        xml_set_start_namespace_decl_handler( $parser, 'DeclareNamespace' );
        xml_set_element_handler( $parser, 'StartElement', 'EndElement' );
        xml_set_character_data_handler( $parser, 'CharacterData' );

        $fp = call_user_func( $this->mFileOpenCallback, $location );

        if ( ! is_resource( $fp ) )
        {
            $error = $r_manager->GetMsg( "Could not open XML Schema file: $location" );

            trigger_error( $error, E_USER_ERROR );

            return false;
        }

        array_push( $this->mInTagsStack, array() );
        array_push( $this->mCurrentLocationStack, $location );
        array_push( $this->mIncludeStack, $wasIncluded );

        while ( $data = fread( $fp, 4096 ) ) 
        {
            if ( ! xml_parse( $parser, $data, feof( $fp ) ) ) 
            {
                $error = $r_manager->GetMsg( sprintf( "Error parsing XML Schema: %s at line %d",
                                             xml_error_string( xml_get_error_code( $parser ) ),
                                             xml_get_current_line_number( $parser ) ) );

                trigger_error( $error, E_USER_ERROR );
                return false;
            }
        }

        fclose( $fp );

        xml_parser_free( $parser );

        array_pop( $this->mInTagsStack );
        array_pop( $this->mCurrentLocationStack );
        array_pop( $this->mIncludeStack );

        return true;

    } // end of member function Parse

    function StartElement( $parser, $qualified_name, $attrs ) 
    {
        $r_manager =& XsManager::GetInstance();

        $name = strtolower( $this->_GetUnqualifiedName( $qualified_name ) );

        $r_manager->Debug( 'Starting Element: '.$name.' '.$this->_DumpArray( $attrs ) );

        $namespace = '';

        if ( count( $this->mTargetNamespaces ) )
        {
            $namespace = end( $this->mTargetNamespaces );
        }

        $parse_step = count( $this->mInTagsStack );

        array_push( $this->mInTagsStack[$parse_step-1], strtolower( $name ) );

        $in_tags = $this->mInTagsStack[$parse_step-1];

        $node_path = implode( '/', $in_tags );

        $r_manager->Debug( 'Node path: '.$node_path );

        if ( $this->mIgnoreNode != null )
        {
            $r_manager->Debug( 'Ignoring start node' );

            return;
        }

        $depth = count( $in_tags );

        $num_objects = count( $this->mObjects );

        if ( $num_objects )
        {
            $r_current_object =& $this->mObjects[$num_objects-1];
        }

        $current_location = end( $this->mCurrentLocationStack );

        // SCHEMA
        if ( strcasecmp( $name, 'schema' ) == 0 )
        {
            $was_included = end( $this->mIncludeStack );

            if ( $was_included )
            {
                // No need to have target a namespace.
                // Things belong to the last namespace.

                $this->mNamespaces[$namespace]->PushSchema( $current_location, $parser );
            }
            else
            {
                if ( $this->mTargetNamespaceMustBePresent and 
                     ! isset( $attrs['targetNamespace'] ) )
                {
                    $this->mIgnoreNode = $node_path;

                    $error = $r_manager->GetMsg( 'Missing attribute "targetNamespace" '.
                                                 'in schema loaded from "'.
                                                 $current_location.'"' );

                    trigger_error( $error, E_USER_ERROR );
                    return;
                }

                array_push( $this->mTargetNamespaces, $attrs['targetNamespace'] );

                $ts = $attrs['targetNamespace'];

                if ( ! isset( $this->mNamespaces[$ts] ) )
                {
                    $prefix = ''; // not needed here

                    $ns_obj = new XsNamespace( $prefix, $ts );

                    $this->mNamespaces[$ts] = $ns_obj;
                }

                $this->mNamespaces[$ts]->PushSchema( $current_location, $parser );
            }

            if ( empty( $this->mTargetNamespace ) )
            {
                $this->mTargetNamespace = $attrs['targetNamespace'];
            }
            else
            {
                // inside <import> parsing. targetNamespace for the response
                // structure was already assigned.
            }

            if ( ! isset( $this->mParser ) )
            {
                // Assign parser property here since it might have been 
                // instantiated outside this class
                $this->mParser = $parser;
            }
            else
            {
                // inside <import> parsing. no need to assign anything.
            }
        }
        // IMPORT
        else if ( strcasecmp( $name, 'import' ) == 0 )
        {
            if ( isset( $attrs['namespace'] ) )
            {
                $imported_namespace = $attrs['namespace'];
            }
            else
            {
                $imported_namespace = $namespace;
            }

            if ( ! isset( $attrs['schemaLocation'] ) )
            {
                $this->mIgnoreNode = $node_path;

                $error = $r_manager->GetMsg( 'Only XML Schema "import" directives with a '.
                                             '"schemaLocation" attribute are supported. '.
                                             'Ignoring import declaration for "'.
                                             $imported_namespace.'" in "'.
                                             $current_location.'"' );

                trigger_error( $error, E_USER_WARNING );
                return;
            }

            $this->Parse( $attrs['schemaLocation'] );
        }
        // INCLUDE
        else if ( strcasecmp( $name, 'include' ) == 0 )
        {
            if ( ! isset( $attrs['schemaLocation'] ) )
            {
                $this->mIgnoreNode = $node_path;

                $error = $r_manager->GetMsg( 'Found XML Schema "include" construct without a '.
                                             '"schemaLocation" attribute. '.
                                             'Ignoring declaration in "'.
                                             $current_location.'"' );

                trigger_error( $error, E_USER_WARNING );
                return;
            }

            $is_include = true;

            $this->Parse( $attrs['schemaLocation'] , $is_include );
        }
        // ELEMENT
        else if ( strcasecmp( $name, 'element' ) == 0 )
        {
            $is_global = ( $in_tags[$depth-2] == 'schema' ) ? true : false;

            if ( isset( $attrs['substitutionGroup'] ) )
            {
                if ( $r_manager->InDebugMode() )
                {
                    $msg = '"substitutionGroup" not supported. '.
                           'Ignoring statement in "'.$current_location.'"';

                    if ( isset( $attrs['name'] ) )
                    {
                        $msg .= ' for "'.$attrs['name'].'"';
                    }

                    trigger_error( $r_manager->GetMsg( $msg ), E_USER_WARNING );
                }
                else
                {
                    $this->AddUnsupportedConstruct( $current_location, 'substitutionGroup' );
                }
            }

            if ( isset( $attrs['ref'] ) )
            {
                if ( $is_global )
                {
                    $this->mIgnoreNode = $node_path;

                    $error = $r_manager->GetMsg( 'Detected global element with "ref" '.
                                                 'attribute in "'.$current_location.'"' );

                    trigger_error( $error, E_USER_ERROR );
                    return;
                }

                $xs_element_declaration = new XsElementDecl( null, $namespace, false );
            }
            else
            {
                if ( ! isset( $attrs['name'] ) )
                {
                    $this->mIgnoreNode = $node_path;

                    $error = $r_manager->GetMsg( 'Detected element declaration without name '.
                                                 'attribute in "'.$current_location.'"' );

                    trigger_error( $error, E_USER_ERROR );
                    return;
                }

                $name = $attrs['name'];

                $xs_element_declaration = new XsElementDecl( $name, $namespace, $is_global );
            }

            $xs_element_declaration->SetProperties( $attrs );

            if ( $is_global )
            {
                // Global element
                $this->mNamespaces[$namespace]->AddElementDecl( $current_location, $xs_element_declaration );
            }
            else
            {
                $r_current_object->AddParticle( $xs_element_declaration );
            }

            $r_manager->Debug( 'Changing current object to an element' );

            $this->mObjects[$num_objects] =& $xs_element_declaration;
        }
        // COMPLEX TYPE
        else if ( strcasecmp( $name, 'complextype' ) == 0 )
        {
            $is_global = ( $in_tags[$depth-2] == 'schema' ) ? true : false;

            $name = null;

            if ( isset( $attrs['name'] ) )
            {
                $name = $attrs['name'];
            }

            $mixed = false;

            if ( isset( $attrs['mixed'] ) and $attrs['mixed'] == 'true' )
            {
                $mixed = true;
            }

            $xs_complex_type = new XsComplexType( $name, $namespace, $is_global, $mixed );

            if ( $is_global )
            {
                if ( $name == null )
                {
                    $error = $r_manager->GetMsg( 'Detected global complex type without a name '.
                                                 'attribute in response schema' );

                    trigger_error( $error, E_USER_ERROR );
                    return;
                }

                $this->mNamespaces[$namespace]->AddType( $current_location, $xs_complex_type );
            }
            else
            {
                $r_current_object->SetType( $xs_complex_type );

                $r_manager->Debug( 'Setting type of current object' );
            }

            $this->mObjects[$num_objects] =& $xs_complex_type;

            $r_manager->Debug( 'Changing current object to a complex type' );
        }
        // SIMPLE TYPE
        else if ( strcasecmp( $name, 'simpletype' ) == 0 )
        {
            $is_global = ( $in_tags[$depth-2] == 'schema' ) ? true : false;

            $name = null;

            if ( isset( $attrs['name'] ) )
            {
                $name = $attrs['name'];
            }

            $xs_simple_type = new XsSimpleType( $name, $namespace, $is_global );

            if ( $is_global )
            {
                if ( $name == null )
                {
                    $error = $r_manager->GetMsg( 'Detected global simple type without a name '.
                                                 'attribute in "'.$current_location.'"' );

                    trigger_error( $error, E_USER_ERROR );
                    return;
                }

                $this->mNamespaces[$namespace]->AddType( $current_location, $xs_simple_type );
            }
            else
            {
                $r_current_object->SetType( $xs_simple_type );

                $r_manager->Debug( 'Setting type of current object' );
            }

            $this->mObjects[$num_objects] =& $xs_simple_type;

            $r_manager->Debug( 'Changing current object to a simple type' );
        }
        // ATTRIBUTE
        else if ( strcasecmp( $name, 'attribute' ) == 0 )
        {
            $is_global = ( $in_tags[$depth-2] == 'schema' ) ? true : false;

            if ( isset( $attrs['ref'] ) )
            {
                if ( $is_global )
                {
                    $this->mIgnoreNode = $node_path;

                    $error = $r_manager->GetMsg( 'Detected global attribute declaration with '.
                                                 '"ref" attribute in "'.$current_location.'"' );

                    trigger_error( $error, E_USER_ERROR );
                    return;
                }

                $xs_attribute_declaration = new XsAttributeDecl( null, $namespace, false );
            }
            else
            {
                $name = null;

                if ( isset( $attrs['name'] ) )
                {
                    $name = $attrs['name'];

                    $r_manager->Debug( 'name = '.$name );
                }

                $xs_attribute_declaration = new XsAttributeDecl( $name, $namespace, $is_global );

                if ( $is_global )
                {
                    if ( $name == null )
                    {
                        $this->mIgnoreNode = $node_path;

                        $error = $r_manager->GetMsg( 'Detected global attribute declaration '.
                                                     'without name in "'.
                                                     $current_location.'"' );

                        trigger_error( $error, E_USER_ERROR );
                        return;
                    }
                }
            }

            if ( $is_global )
            {
                $xs_attribute_declaration->SetProperties( $attrs );

                $this->mNamespaces[$namespace]->AddAttributeDecl( $current_location, $xs_attribute_declaration );

                $this->mObjects[$num_objects] =& $xs_attribute_declaration;
            }
            else
            {
                $xs_attribute_use = new XsAttributeUse( $xs_attribute_declaration );

                $xs_attribute_use->SetProperties( $attrs );

                $r_current_object->AddDeclaredAttributeUse( $xs_attribute_use );

                $this->mObjects[$num_objects] =& $xs_attribute_use;
            }

            $r_manager->Debug( 'Changing current object to an attribute' );
        }
        // MODEL GROUP ( SEQUENCE, ALL, CHOICE )
        else if ( strcasecmp( $name, 'sequence' ) == 0 or 
                  strcasecmp( $name, 'all' ) == 0 or
                  strcasecmp( $name, 'choice' ) == 0 )
        {
            $xs_model_group = new XsModelGroup( strtolower( $name ) );

            if ( isset( $attrs['minOccurs'] ) )
            {
                $xs_model_group->SetMinOccurs( $attrs['minOccurs'] );
            }

            if ( isset( $attrs['maxOccurs'] ) )
            {
                $xs_model_group->SetMaxOccurs( $attrs['maxOccurs'] );
            }

            if ( $in_tags[$depth-2] == 'complextype' )
            {
                $r_manager->Debug( 'Adding content type "'.$name.'" to current object' );

                $r_current_object->AddContentType( $xs_model_group );
            }
            else if ( in_array( $in_tags[$depth-2], 
                                array( 'sequence', 'all', 'choice' ) ) )
            {
                $r_manager->Debug( 'Adding particle "'.$name.'" to current object' );

                $r_current_object->AddParticle( $xs_model_group );
            }
            else if ( $in_tags[$depth-2] == 'extension' )
            {
                $r_manager->Debug( 'Adding extended content type "'.$name.'" to current object' );

                $r_current_object->AddContentType( $xs_model_group );
            }
            else
            {
                $error = $r_manager->GetMsg( 'Container "'.$in_tags[$depth-2].'" does not '.
                                             'expect model group "'.$name.'" in schema "'.
                                             $current_location.'" ('.
                                             implode( '/', $in_tags ).')' );

                trigger_error( $error, E_USER_ERROR );
                return;
            }

            $this->mObjects[$num_objects] =& $xs_model_group;

            $r_manager->Debug( 'Changing current object to a model group' );
        }
        // ANNOTATION
        else if ( strcasecmp( $name, 'annotation' ) == 0 )
        {
            // ignore annotations
            $this->mIgnoreNode = $node_path;

            return;
        }
        // COMPLEX CONTENT
        else if ( strcasecmp( $name, 'complexcontent' ) == 0 )
        {
            if ( $in_tags[$depth-2] != 'complextype' )
            {
                $this->mIgnoreNode = $node_path;

                $error = $r_manager->GetMsg( 'XML Schema "complexContent" can only be used '.
                                             'by complex types. Ignoring wrong usage by "'.
                                             $in_tags[$depth-2].'"' );

                trigger_error( $error, E_USER_ERROR );
                return;
            }

            $r_current_object->SetSimpleTypeDerivation( false );
        }
        // SIMPLE CONTENT
        else if ( strcasecmp( $name, 'simplecontent' ) == 0 )
        {
            if ( $in_tags[$depth-2] != 'complextype' )
            {
                $this->mIgnoreNode = $node_path;

                $error = $r_manager->GetMsg( 'XML Schema "simpleContent" can only be used '.
                                             'by complex types. Ignoring wrong usage by "'.
                                             $in_tags[$depth-2].'"' );

                trigger_error( $error, E_USER_ERROR );
                return;
            }

            $r_current_object->SetSimpleTypeDerivation( true );
        }
        // RESTRICTION
        else if ( strcasecmp( $name, 'restriction' ) == 0 )
        {
            // Note: Only type inheritance is stored. Further XML Schema constructs
            // are not yet supported.
            $this->mIgnoreNode = $node_path;

            if ( $r_manager->InDebugMode() )
            {
                $msg = $r_manager->GetMsg( 'XML Schema construct "restriction" not supported. '.
                                           'Ignoring statement at node "'.$node_path.'" in "'.
                                           $current_location.'"' );

                trigger_error( $msg, E_USER_WARNING );
            }
            else
            {
                $this->AddUnsupportedConstruct( $current_location, 'restriction' );
            }

            if ( ! isset( $attrs['base'] ) )
            {
                $this->mIgnoreNode = $node_path;

                $error = $r_manager->GetMsg( 'Detected "restriction" without "base" attribute '.
                                             'in "'.$current_location.'"' );

                trigger_error( $error, E_USER_ERROR );
                return;
            }

            $r_current_object->SetBaseType( $attrs['base'] );
            $r_current_object->SetDerivationMethod( 'restriction' );
        }
        // EXTENSION
        else if ( strcasecmp( $name, 'extension' ) == 0 )
        {
            if ( in_array( $in_tags[$depth-2], 
                           array( 'complexContent', 'simpleContent' ) ) )
            {
                $this->mIgnoreNode = $node_path;

                $error = $r_manager->GetMsg( 'XML Schema "extension" can only be used inside '.
                                             '"simpleContent" or "complexContent". '.
                                             'Ignoring wrong usage by "'.
                                             $in_tags[$depth-2].'"' );

                trigger_error( $error, E_USER_ERROR );
                return;
            }

            if ( ! isset( $attrs['base'] ) )
            {
                $this->mIgnoreNode = $node_path;

                $error = $r_manager->GetMsg( 'Detected "extension" without "base" attribute '.
                                             'in "'.$current_location.'"' );

                trigger_error( $error, E_USER_ERROR );
                return;
            }

            $r_current_object->SetBaseType( $attrs['base'] );
            $r_current_object->SetDerivationMethod( 'extension' );
        }
        // UNION
        else if ( strcasecmp( $name, 'union' ) == 0 )
        {
            $this->mIgnoreNode = $node_path;

            // ignore union
            if ( $r_manager->InDebugMode() )
            {
                $msg = $r_manager->GetMsg( 'XML Schema construct "union" not supported. '.
                                           'Ignoring statement at node "'.$node_path.'" in "'.
                                           $current_location.'"' );

                trigger_error( $msg, E_USER_WARNING );
            }
            else
            {
                $this->AddUnsupportedConstruct( $current_location, 'union' );
            }

            return;
        }
        else
        {
            $this->mIgnoreNode = $node_path;

            if ( $r_manager->InDebugMode() )
            {
                $msg = $r_manager->GetMsg( 'Unknown schema component "'.$node_path.'" in "'.
                                           $current_location.'"' );

                trigger_error( $msg, E_USER_WARNING );
            }
            else
            {
                $this->AddUnsupportedConstruct( $current_location, $name );
            }

            return;
        }

    } // end of member function StartElement

    function EndElement( $parser, $qualified_name ) 
    {
        $r_manager =& XsManager::GetInstance();

        $name = strtolower( $this->_GetUnqualifiedName( $qualified_name ) );

        $parse_step = count( $this->mInTagsStack );

        $node_path = implode( '/', $this->mInTagsStack[$parse_step-1] );

        $r_manager->Debug( 'Ending element '.$node_path );

        if ( $this->mIgnoreNode != null )
        {
            $r_manager->Debug( 'Ignoring end node' );

            if ( $this->mIgnoreNode == $node_path  )
            {
                $this->mIgnoreNode = null;
            }

            array_pop( $this->mInTagsStack[$parse_step-1] );

            return;
        }

        if ( $name == 'element' or $name == 'attribute' or $name == 'complextype' or 
             $name == 'simpletype' or $name == 'sequence' or $name == 'all' or  
             $name == 'choice' )
        {
            $x = array_pop( $this->mObjects );

            $r_manager->Debug( 'Popping class '.get_class( $x ) );
        }
        else if ( $name == 'schema' )
        {
            $was_included = end( $this->mIncludeStack );

            if ( ! $was_included )
            {
                array_pop( $this->mTargetNamespaces );
            }
        }

        array_pop( $this->mInTagsStack[$parse_step-1] );

    } // end of member function EndElement

    function CharacterData( $parser, $data ) 
    {

    } // end of member function CharacterData

    function DeclareNamespace( $parser, $prefix, $uri ) 
    {
        $r_namespace_manager =& XsNamespaceManager::GetInstance();

        $r_namespace_manager->AddNamespace( $parser, $prefix, $uri );

    } // end of member function DeclareNamespace

    function AddUnsupportedConstruct( $schema, $construct ) 
    {
        if ( ! isset( $this->mUnsupportedConstructs[$construct] ) )
        {
            $this->mUnsupportedConstructs[$construct] = array();
        }

        if ( ! in_array( $schema, $this->mUnsupportedConstructs[$construct] ) )
        {
            array_push( $this->mUnsupportedConstructs[$construct], $schema );
        }

    } // end of member function AddUnsupportedConstruct

    function GetUnsupportedConstructs( ) 
    {
        return $this->mUnsupportedConstructs;

    } // end of member function GetUnsupportedConstructs

    function GetLocation( $ns=null ) 
    {
        if ( $ns == null )
        {
            $ns = $this->mTargetNamespace;
        }

        if ( isset( $this->mNamespaces[$ns] ) )
        {
            return $this->mNamespaces[$ns]->GetFirstLocation();
        }

        return null;

    } // end of member function GetLocation

    function GetTargetNamespace( ) 
    {
        return $this->mTargetNamespace;

    } // end of member function GetTargetNamespace

    function GetElementDecls( $ns=null ) 
    {
        if ( $ns == null )
        {
            $ns = $this->mTargetNamespace;
        }

        if ( isset( $this->mNamespaces[$ns] ) )
        {
            return $this->mNamespaces[$ns]->GetElementDecls();
        }

        return array();

    } // end of member function GetElementDecls

    function GetAttributeDecls( $ns=null ) 
    {
        if ( $ns == null )
        {
            $ns = $this->mTargetNamespace;
        }

        if ( isset( $this->mNamespaces[$ns] ) )
        {
            return $this->mNamespaces[$ns]->GetAttributeDecls();
        }

        return array();

    } // end of member function GetAttributeDecls

    function &GetElementDecl( $namespace, $name ) 
    {
        $null_object = null;
    
        if ( ! isset( $this->mNamespaces[$namespace] ) )
        {
            return $null_object;
        }

        $parsed_name = explode( ':', $name );

        if ( count( $parsed_name ) == 2 )
        {
            $prefix = $parsed_name[0];

            $local_name = $parsed_name[1];

            $schema_locations = $this->mNamespaces[$namespace]->GetLocations();

            // Return first element found in one of the associated schemas
            foreach ( $schema_locations as $location )
            {
                $r_schema =& $this->mNamespaces[$namespace]->GetSchema( $location );

                $r_parser =& $r_schema->GetParser();

                $r_namespace_manager =& XsNamespaceManager::GetInstance();

                $namespace = $r_namespace_manager->GetNamespace( $r_parser, $prefix );

                $r_el =& $this->mNamespaces[$namespace]->GetElementDecl( $local_name );

                if ( ! is_null( $r_el ) )
                {
                    return $r_el;
                }
            }
        }
        else
        {
            $local_name = $name;

            if ( $namespace == null )
            {
                $namespace = $this->mTargetNamespace;
            }

            return $this->mNamespaces[$namespace]->GetElementDecl( $local_name );
        }

        return $null_object;

    } // end of member function GetElementDecl

    function &GetAttributeDecl( $namespace, $name ) 
    {
        $null_object = null;
        
        if ( ! isset( $this->mNamespaces[$namespace] ) )
        {
            return $null_object;
        }

        $parsed_name = explode( ':', $name );

        if ( count( $parsed_name ) == 2 )
        {
            $prefix = $parsed_name[0];

            $local_name = $parsed_name[1];

            $schema_locations = $this->mNamespaces[$namespace]->GetLocations();

            // Return first attribute found in one of the associated schemas
            foreach ( $schema_locations as $location )
            {
                $r_schema =& $this->mNamespaces[$namespace]->GetSchema( $location );

                $r_parser =& $r_schema->GetParser();

                $r_namespace_manager =& XsNamespaceManager::GetInstance();

                $namespace = $r_namespace_manager->GetNamespace( $r_parser, $prefix );

                $r_attr =& $this->mNamespaces[$namespace]->GetAttributeDecl( $local_name );

                if ( ! is_null( $r_attr ) )
                {
                    return $r_attr;
                }
            }
        }
        else
        {
            $local_name = $name;

            if ( $namespace == null )
            {
                $namespace = $this->mTargetNamespace;
            }

            return $this->mNamespaces[$namespace]->GetAttributeDecl( $local_name );
        }

        return $null_object;

    } // end of member function GetAttributeDecl

    function &GetType( $namespace, $name ) 
    {
        $null_type = null;

        if ( ! isset( $this->mNamespaces[$namespace] ) )
        {
            return $null_type;
        }

        $parsed_name = explode( ':', $name );

        if ( count( $parsed_name ) == 2 )
        {
            // External reference

            $prefix = $parsed_name[0];

            $local_name = $parsed_name[1];

            $schema_locations = $this->mNamespaces[$namespace]->GetLocations();

            // Return first type found in one of the associated schemas
            foreach ( $schema_locations as $location )
            {
                $r_schema =& $this->mNamespaces[$namespace]->GetSchema( $location );

                $r_parser =& $r_schema->GetParser();

                $r_namespace_manager =& XsNamespaceManager::GetInstance();

                $uri = $r_namespace_manager->GetNamespace( $r_parser, $prefix );

                if ( is_null( $uri ) )
                {
                    return $null_type;
                }
                else if ( $uri == 'http://www.w3.org/2001/XMLSchema' )
                {
                    break;
                }

                $type = $this->mNamespaces[$uri]->GetType( $local_name );

                if ( ! is_null( $type ) )
                {
                    return $type;
                }
            }

            if ( $uri == 'http://www.w3.org/2001/XMLSchema' )
            {
                // XML Schema types

                $is_global = true;

                $xs_simple_type = new XsSimpleType( $local_name, $uri, $is_global );

                return $xs_simple_type;

                // TODO: create simple types for each one, with
                //       specific data validation
                //if ( $local_name == 'string' ) 
                //{
                    //return new XsStringType( $local_name, $uri, true );
                //}
                //else if ( $local_name == 'int' ) 
                //{
                    //return new XsIntType( $local_name, $uri, true );
                //}
            }
        }
        else
        {
            // Reference to global definition
            if ( $namespace == null )
            {
                $uri = $this->mTargetNamespace;
            }
            else
            {
                $uri = $namespace;
            }

            $local_name = $name;

            return $this->mNamespaces[$uri]->GetType( $local_name );
        }

        $r_manager =& XsManager::GetInstance();

        $error = $r_manager->GetMsg( 'Unknown schema type "'.$name.'"' );

        trigger_error( $error, E_USER_ERROR );

        return $null_type;

    } // end of member function GetType

    function GetPrimitiveXsdType( $typeObj )
    {
        if ( ! is_object( $typeObj ) )
        {
            return null;
        }

        $xsd_namespace = 'http://www.w3.org/2001/XMLSchema';

        $type_ns = $typeObj->GetTargetNamespace();

        $type_name = $typeObj->GetName();

        if ( $type_ns == $xsd_namespace )
        {
            switch ( $type_name )
            {
                case 'anyURI':
                case 'boolean':
                case 'base64Binary':
                case 'date':
                case 'dateTime':
                case 'decimal':
                case 'double':
                case 'duration':
                case 'float':
                case 'gDay':
                case 'gMonth':
                case 'gMonthDay':
                case 'gYear':
                case 'gYearMonth':
                case 'hexBinary':
                case 'NOTATION':
                case 'QName':
                case 'string':
                case 'time':
                    return $typeObj;
                case 'normalizedString':
                case 'token':
                case 'language':
                case 'Name':
                case 'NMTOKEN':
                case 'NMTOKENS':
                case 'NCName':
                case 'ID':
                case 'IDREF':
                case 'IDREFS':
                case 'ENTITY':
                case 'ENTITIES':
                    return new XsSimpleType( 'string', $xsd_namespace, false );
                case 'integer':
                case 'nonPositiveInteger':
                case 'negativeInteger':
                case 'long':
                case 'int':
                case 'short':
                case 'byte':
                case 'nonNegativeInteger':
                case 'unsignedLong':
                case 'unsignedInt':
                case 'unsignedShort':
                case 'unsignedByte':
                case 'positiveInteger':
                    return new XsSimpleType( 'decimal', $xsd_namespace, false );
            }
        }
        else
        {
            $base_type = $typeObj->GetBaseType();

            if ( is_object( $base_type ) )
            {
                return $this->GetPrimitiveXsdType( $base_type );
            }
            else if ( is_string( $base_type ) )
            {
                $base_type = $this->GetType( $type_ns, $base_type );

                if ( is_object( $base_type ) )
                {
                    return $this->GetPrimitiveXsdType( $base_type );
                }
            }
        }

        return null;

    } // end of member function GetPrimitiveXsdType

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array( 'mNamespaces', 'mTargetNamespace' );

    } // end of member function __sleep

} // end of XsSchemaParser
?>