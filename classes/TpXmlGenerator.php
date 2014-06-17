<?php
/**
 * $Id: TpXmlGenerator.php 1985 2009-03-21 20:26:36Z rdg $
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

require_once('phpxsd/XsSchemaVisitor.php');
require_once('TpDiagnostics.php');
require_once('TpUtils.php');
require_once('TpServiceUtils.php');
require_once('TpFilter.php');

class TpXmlGenerator extends XsSchemaVisitor
{
    var $mrOutputModel;
    var $mResponseStructure;
    var $mIndexingElement;
    var $mSqlBuilder;
    var $mDbEncoding;
    var $mMaxRepetitions;
    var $mLimit;
    var $mrResultSet;
    var $mrResource;
    var $mRecordIndex = 0;
    var $mTotalReturned = 0;
    var $mAbort;
    var $mIgnorePaths;
    var $mNoContentSummary = array(); // path => number of times it was dropped
    var $mNamespaceStack = array();
    var $mOmitNamespaces;

    function TpXmlGenerator( &$rOutputModel, $ignorePaths, $sqlBuilder, $dbEncoding, $maxRepetitions, $limit, $omitNamespaces ) 
    {
        $this->mrOutputModel =& $rOutputModel;
        $this->mResponseStructure = $rOutputModel->GetResponseStructure();
        $this->mIndexingElement = $rOutputModel->GetIndexingElement();
        $this->mIgnorePaths = $ignorePaths;
        $this->mSqlBuilder = $sqlBuilder;
        $this->mDbEncoding = $dbEncoding;
        $this->mMaxRepetitions = $maxRepetitions;
        $this->mLimit = $limit;
        $this->mOmitNamespaces = $omitNamespaces;

    } // end of member function TpXmlGenerator

    function Render( &$rResultSet, &$rResource )
    {
        global $g_dlog;

        $g_dlog->debug( '[Generating XML]' );

        // Make sure you call $output_model->IsValid() before calling this method!

        $this->mrResultSet =& $rResultSet;

        $this->mrResource =& $rResource;

        $root_element = $this->mrOutputModel->GetRootElement();

        $global_elements = $this->mResponseStructure->GetElementDecls();

        $path = '';

        $matched = false;

        // Get the first non abstract global element declaration and render it
        foreach ( $global_elements as $el_name => $xs_element_decl )
        {
            if ( ! empty( $root_element ) )
            {
                $ref_name = $el_name;

                $prefix = $this->mrOutputModel->GetPrefix( $xs_element_decl->GetTargetNamespace() );

                if ( ! empty( $prefix ) )
                {
                    $ref_name = $prefix.':'.$ref_name;
                }

                if ( $root_element == $ref_name )
                {
                    $matched = true;
                }
            }
            else {

                if ( ! $xs_element_decl->IsAbstract() )
                {
                    $matched = true;
                }
            }

            if ( $matched ) {

                $content = $xs_element_decl->Accept( $this, $path );

                if ( count( $this->mNoContentSummary ) )
                {
                    foreach ( $this->mNoContentSummary as $t_path => $times )
                    {
                        $msg = 'Node "'.$t_path.'" was dropped '.$times.
                               ' times for having incomplete or no content';
                        TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $msg, DIAG_WARN );
                    }
                }

                return $content;
            }
        }

        return '';

    } // end of member function Render

    function VisitElementDecl( &$rXsElementDecl, $path )
    {
        global $g_dlog;

        $is_root = ( $path == '' ) ? true : false;

        $name = $rXsElementDecl->GetName();

        $element_namespace = $rXsElementDecl->GetTargetNamespace();

        $path_token_prefix = $this->mrOutputModel->GetPrefix( $element_namespace );

        if ( ! empty( $path_token_prefix ) )
        {
            $path_token_prefix .= ':';
        }

        $path .= '/' . $path_token_prefix . $name;

        $g_dlog->debug( 'Visiting element declaration at '.$path );

        if ( in_array( $path, $this->mIgnorePaths ) )
        {
            $g_dlog->debug( 'Ignoring element' );

            return '';
        }

        // Note: We are assuming that the if the element exceeds the maximum level
        //       then it will be in mIgnorePaths. This is checked previously 
        //       by TpSchemaInspector.

        $min_occurs = (int)$rXsElementDecl->GetMinOccurs();

        // Note: We are assuming that $type is always a valid object, since 
        //       there was a previous checking done by TpSchemaInspector.
        $r_type =& $rXsElementDecl->GetType();

        if ( ! is_object( $r_type ) )
        {
            $msg = 'Error processing element '.$path.' (no type)';
            TpDiagnostics::Append( DC_GENERAL_ERROR, $msg, DIAG_FATAL );

            $this->mAbort = true;

            return '';
        }

        // Element cardinality

        $limit = $min_occurs;

        $limit = max( $limit, 1 );

        if ( $path == $this->mIndexingElement )
        {
            $max_occurs = $rXsElementDecl->GetMaxOccurs();

            if ( $max_occurs == 'unbounded' )
            {
                if ( is_null( $this->mLimit ) )
                {
                    $limit = $this->mMaxRepetitions;
                }
                else
                {
                    $limit = min( $this->mLimit, $this->mMaxRepetitions );
                }
            }
            else
            {
                $limit = min( $this->mLimit, $this->mMaxRepetitions, (int)$max_occurs );
            }
        }

        $limit = min( $limit, $this->mMaxRepetitions );

        $num_instances = $num_attempts = 0;

        $xml = '';

        $is_complex = false;

        $g_dlog->debug( 'Element instances limit is '.$limit );

        // Namespaces & prefixes declaration

        $prefix = '';

        $xmlns = '';

        if ( ! $this->mOmitNamespaces )
        {
            $target_namespace = $this->mResponseStructure->GetTargetNamespace();

            $target_ns_prefix = $this->mrOutputModel->GetPrefix( $target_namespace );

            if ( $is_root )
            {
                if ( $target_namespace == 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' )
                {
                    // Overwrite content-type for RDF data
                    header( 'Content-type: application/rdf+xml; charset=utf-8' );
                }

                if ( empty( $target_ns_prefix ) )
                {
                    // Only declare a default namespace if there is no prefix
                    // associated with the target namespace
                    $xmlns = ' xmlns="'.$target_namespace.'"';
                }
                else
                {
                    $xmlns = ' xmlns:'.$target_ns_prefix.'="'.$target_namespace.'"';

                    $prefix = $target_ns_prefix.':';
                }

                $xmlns .= ' xmlns:'.TP_XSI_PREFIX.'="'.XMLSCHEMAINST.'"';

                $ns_to_declare = $this->mrOutputModel->GetDeclaredNamespaces();

                for ( $i = 0; $i < count( $ns_to_declare ); ++$i )
                {
                    $uri = $ns_to_declare[$i]->GetUri();

                    $pref = $ns_to_declare[$i]->GetPrefix();

                    if ( $uri <> $target_namespace and 
                         $pref <> TP_XSI_PREFIX and 
                         $pref <> 'default' and
                         $uri <> XMLSCHEMANS )
                    {
                        $xmlns .= ' xmlns:'.$pref.'="'.$uri.'"';
                    }
                }
            }
            else
            {
                if ( $element_namespace != $target_namespace )
                {
                    $prefix = $this->mrOutputModel->GetPrefix( $element_namespace );

                    if ( empty( $prefix ) )
                    {
                        $msg = 'Could not find prefix for namespace '.
                               $element_namespace;
                        TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $msg, DIAG_ERROR );

                        $this->mAbort = true;

                        return '';
                    }

                    $prefix .= ':';
                }
                else if ( ! empty( $target_ns_prefix ) )
                {
                    $prefix = $target_ns_prefix . ':';
                }
            }

            array_push( $this->mNamespaceStack, $element_namespace );
        }

        $r_basetype =& $r_type->GetBaseType();

        // Main loop for element instances

        while ( $num_attempts < $limit )
        {
            $this->mAbort = false;

            if ( $path == $this->mIndexingElement )
            {
                if ( $this->mrResultSet->EOF )
                {
                    break;
                }
            }

            $open_tag = '<' . $prefix . $name;

            if ( $is_root )
            {
                $open_tag .= $xmlns;
            }

            $attributes_xml = '';

            $base_subcontent_xml = '';
            $current_subcontent_xml = '';

            $content = '';

            if ( $r_type->IsComplexType() )
            {
                $g_dlog->debug( 'Has Complex type' );

                $is_complex = true;

                $derivation_method = $r_type->GetDerivationMethod();

                // Add attributes

                if ( $derivation_method == 'extension' and 
                     is_object( $r_basetype ) and 
                     $r_basetype->IsComplexType() )
                {
                    $g_dlog->debug( 'Base attributes' );

                    // Base attributes, in case of extension
                    $r_basetype_declared_attribute_uses =& $r_basetype->GetDeclaredAttributeUses();

                    $attributes_xml .= $this->_GetAttributesXml( $r_basetype_declared_attribute_uses, $path );
                }

                // Current attributes (real extended attributes or attributes from 
                // non extended types)
                $g_dlog->debug( 'Current attributes' );

                $r_declared_attribute_uses =& $r_type->GetDeclaredAttributeUses();

                $attributes_xml .= $this->_GetAttributesXml( $r_declared_attribute_uses, $path );

                if ( $this->mAbort )
                {
                    ++$num_attempts;

                    if ( $this->_CheckLoop( $path ) )
                    {
                        break;
                    }

                    continue;
                }

                // Add sub elements

                $open_tag .= $attributes_xml . '>';

                if ( $derivation_method == 'extension' and is_object( $r_basetype ) )
                {
                    // Base content

                    if ( $r_basetype->IsSimpleType() )
                    {
                        $value = $this->_GetSimpleContent( $rXsElementDecl, $path );

                        $base_subcontent_xml = ( is_null( $value ) ) ? '' : $value;

                        $content .= $base_subcontent_xml;
                    }
                    else
                    {
                        $r_base_content_type =& $r_basetype->GetContentType();

                        $g_dlog->debug( 'Base content type: '.get_class( $r_base_content_type ) );

                        if ( strtolower( get_class( $r_base_content_type ) ) == 'xsmodelgroup' )
                        {
                            $base_subcontent_xml = $r_base_content_type->Accept( $this, $path );

                            if ( $this->mAbort )
                            {
                                ++$num_attempts;

                                if ( $this->_CheckLoop( $path ) )
                                {
                                    break;
                                }

                                continue;
                            }

                            $content .= $base_subcontent_xml;
                        }
                        else
                        {
                            // Should not fall here now (schema inspector should have 
                            // rejected)
                        }
                    }
                }

                $r_content_type =& $r_type->GetContentType();

                if ( empty( $derivation_method ) or 
                     ( ( ! $this->mAbort ) and 
                       ( ! $r_type->HasSimpleContent() ) and 
                         ! is_null( $r_content_type ) ) )
                {
                    $g_dlog->debug( 'Current content type: '.get_class( $r_content_type ) );

                    // Extended content or content from non extended types
                    if ( strtolower( get_class( $r_content_type ) ) == 'xsmodelgroup' )
                    {
                        $current_subcontent_xml = $r_content_type->Accept( $this, $path );

                        if ( $this->mAbort )
                        {
                            ++$num_attempts;

                            if ( $this->_CheckLoop( $path ) )
                            {
                                break;
                            }

                            continue;
                        }

                        $content .= $current_subcontent_xml;
                    }
                    else
                    {
                        // Should not fall here now (schema inspector should have 
                        // rejected)
                    }
                }

                // Mixed content (treat as simple content with unknown type)

                if ( $r_type->IsMixed() and empty( $current_subcontent_xml ) )
                {
                    $g_dlog->debug( 'Is mixed' );

                    $value = $this->_GetSimpleContent( $rXsElementDecl, $path );

                    if ( ! is_null( $value ) )
                    {
                        $current_subcontent_xml = $value;

                        $content .= $current_subcontent_xml;
                    }
                }

                // Don't render complex elements without content
                if ( empty( $attributes_xml ) and 
                     empty( $base_subcontent_xml ) and 
                     empty( $current_subcontent_xml )  )
                {
                    ++$num_attempts;

                    if ( $this->_CheckLoop( $path ) )
                    {
                        break;
                    }

                    continue;
                }
            }
            else
            {
                // Simple type

                $g_dlog->debug( 'Has Simple type' );

                $value = $this->_GetSimpleContent( $rXsElementDecl, $path );

                if ( $value == null )
                {
                    if ( $rXsElementDecl->IsNillable() and ! $this->mOmitNamespaces )
                    {
                        $open_tag .= ' '.TP_XSI_PREFIX.':nil="true">';
                    }
                    else
                    {
                        if ( isset( $this->mNoContentSummary[$path] ) )
                        {
                            ++$this->mNoContentSummary[$path];
                        }
                        else
                        {
                            $this->mNoContentSummary[$path] = 1;
                        }

                        $g_dlog->debug( 'Element '.$path.' has no content (rec index='.$this->mRecordIndex.')' );

                        $this->mAbort = true;
                    }
                }
                else
                {
                    $open_tag .= '>';

                    $content = $value;
                }
            }

            if ( ! $this->mAbort )
            {
                ++$num_instances;

                $xml .= $open_tag . $content . '</'.$prefix.$name.'>';
            }

            if ( $this->_CheckLoop( $path ) )
            {
                break;
            }

            ++$num_attempts;

        } // end while

        if ( ! $this->mOmitNamespaces )
        {
            array_pop( $this->mNamespaceStack );
        }

        if ( $num_instances < $min_occurs )
        {
            if ( isset( $this->mNoContentSummary[$path] ) )
            {
                if ( $is_complex )
                {
                    ++$this->mNoContentSummary[$path];
                }
            }
            else
            {
                $this->mNoContentSummary[$path] = 1;
            }

            $g_dlog->debug( 'Element '.$path.' did not reach the minimum number '.
                            'of occurrences (rec index='.$this->mRecordIndex.')' );

            $this->mAbort = true;

            $xml = '';
        }
        else
        {
            $this->mAbort = false;

            if ( $path == $this->mIndexingElement )
            {
                $this->mTotalReturned = $num_instances;
            }
        }

        return $xml;
        
    } // end of member function VisitElementDecl

    function _CheckLoop( $path )
    {
        // Jump to next record if this is the indexing element and in
        // this case returns true if reached the end of the result set
        if ( $path == $this->mIndexingElement )
        {
            ++$this->mRecordIndex;

            $this->mrResultSet->MoveNext();

            if ( $this->mrResultSet->EOF )
            {
                return true;
            }
        }

        return false;

    } // end of member function _CheckLoop

    function VisitAttributeUse( $rXsAttributeUse, $path )
    {
        global $g_dlog;

        $attribute_decl = $rXsAttributeUse->GetDecl();

        $attribute_namespace = $attribute_decl->GetTargetNamespace();

        $prefix = '';

        if ( ! $this->mOmitNamespaces )
        {
            $ns_depth = count( $this->mNamespaceStack );

            if ( $ns_depth ) // Sanity checking
            {
                $contextual_namespace = $this->mNamespaceStack[$ns_depth-1];

                // Note: assuming attributeFormDefault = unqualified
                //       (when the default namespace of attributes is the namespace
                //        of the containing element).

                if ( $attribute_namespace != $contextual_namespace )
                {
                    $prefix = $this->mrOutputModel->GetPrefix( $attribute_namespace );

                    if ( empty( $prefix ) )
                    {
                        $msg = 'Could not find prefix for namespace '.
                               $element_namespace;
                        TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $msg, DIAG_ERROR );

                        $this->mAbort = true;

                        return '';
                    }

                    $prefix .= ':';
                }
            }
        }

        $name = $attribute_decl->GetName();

        $path_token_prefix = $this->mrOutputModel->GetPrefix( $attribute_namespace );

        if ( ! empty( $path_token_prefix ) )
        {
            $path_token_prefix .= ':';
        }

        $path .= '/@' . $path_token_prefix . $name;

        $g_dlog->debug( 'Visiting attribute declaration at '.$path );

        $value = $this->_GetSimpleContent( $rXsAttributeUse, $path );

        if ( $value != null )
        {
            return ' '.$prefix . $name.'="'.TpUtils::EscapeXmlSpecialChars( $value ).'"';
        }
        else
        {
            if ( $rXsAttributeUse->IsRequired() )
            {
                if ( isset( $this->mNoContentSummary[$path] ) )
                {
                    ++$this->mNoContentSummary[$path];
                }
                else
                {
                    $this->mNoContentSummary[$path] = 1;
                }

                $g_dlog->debug( 'Attribute '.$path.' has no content (rec index='.$this->mRecordIndex.')' );

                $this->mAbort = true;
            }
        }

        return '';
        
    } // end of member function VisitAttributeUse

    function VisitModelGroup( &$rXsModelGroup, $path )
    {
        global $g_dlog;

        // Note: no need to check depth since this was already checked by 
        //       the schema inspector.

        $compositor = $rXsModelGroup->GetCompositor();

        $xml = '';

        $r_particles =& $rXsModelGroup->GetParticles();

        for ( $i = 0; $i < count( $r_particles ); ++$i )
        {
            $g_dlog->debug( 'Calling particle '.get_class( $r_particles[$i] ) );

            $xml .= $r_particles[$i]->Accept( $this, $path );

            if ( $compositor == 'choice' )
            {
                if ( ! empty( $xml ) )
                {
                    break;
                }
            }
            else 
            {
                if ( $this->mAbort )
                {
                    break;
                }
            }
        }

        if ( empty( $xml ) and $compositor == 'choice' and 
             $rXsModelGroup->GetMinRange() > 0 )
                        {
            if ( isset( $this->mNoContentSummary[$path] ) )
            {
                ++$this->mNoContentSummary[$path];
            }
            else
            {
                $this->mNoContentSummary[$path] = 1;
            }

            $g_dlog->debug( 'No valid choice for node '.$path.
                            ' (rec index='.$this->mRecordIndex.')' );

            $this->mAbort = true;
        }

        return $xml;

    } // end of member function VisitModelGroup

    function _GetAttributesXml( &$rDeclaredAttributeUses, $path )
    {
        $xml = '';

        for ( $i = 0; $i < count( $rDeclaredAttributeUses ); ++$i )
        {
            $attribute_xml = $rDeclaredAttributeUses[$i]->Accept( $this, $path );

            if ( ! empty( $attribute_xml ) )
            {
                $xml .= $attribute_xml;
            }

            if ( $this->mAbort )
            {
                // No need to continue if a mandatory attribute was not rendered
                break;
            }
        }

        return $xml;

    } // end of member function _GetAttributesXml

    function _GetSimpleContent( $obj, $path )
    {
        global $g_dlog;

        $g_dlog->debug( 'Getting simple content' );

        $value = null;

        if ( $obj->HasFixedValue() )
        {
            $value = $obj->GetFixedValue();
        }
        else
        {
            $mapping = $this->mrOutputModel->GetMappingForNode( $path );

            if ( $mapping != null )
            {
                $primitive_type = null;

                $type = $obj->GetType();

                if ( is_object( $type ) )
                {
                    $p_obj = $this->mResponseStructure->GetPrimitiveXsdType( $type );

                    if ( is_object( $p_obj ) )
                    {
                        $primitive_type = $p_obj->GetUri();
                    }
                }

                $value = $this->_GetMappedData( $mapping, $path, $primitive_type );
            }
        }

        return $value;

    } // end of _GetValue

    function _GetMappedData( $mapping, $path, $primitiveType )
    {
        global $g_dlog;

        $g_dlog->debug( 'Getting mapped data');

        $data = '';

        if ( in_array( $path, $this->mIgnorePaths ) )
        {
            $g_dlog->debug( 'Ignoring' );

            return null;
        }

        for ( $i = 0; $i < count( $mapping ); ++$i )
        {
            $expression = $mapping[$i];

            $reference = $expression->GetReference();

            if ( $expression->GetType() == EXP_LITERAL )
            {
                $data .= TpUtils::EscapeXmlSpecialChars( $reference );
            }
            else if ( $expression->GetType() == EXP_VARIABLE )
            {
                if ( ! $this->mrResource->HasVariable( $reference ) )
                {
                    // It may fall here if the variable is mandatory but not
                    // supported by the provider

                    if ( $expression->IsRequired() )
                    {
                        // NULLs can later raise errors or drop nodes if the node
                        // is mandatory or not
                        return null;
                    }
                    else
                    {
                        $data .= '';
                    }
                }
                else
                {
                    $data .= TpUtils::EscapeXmlSpecialChars( $expression->GetValue( $this->mrResource, null, null, null )  );
                }
            }
            else if ( $expression->GetType() == EXP_CONCEPT )
            {
                // Note: Here we are assuming that if this node is outside the
                //       indexingElement, then it does not have a mapping type
                //       bound to the local database (eg. SingleColumnMapping).
                //       This is checked previously with the TpSchemaInspector

                $column_index = $this->mSqlBuilder->GetTargetIndex( $reference );

                if ( $column_index >= 0 and is_array( $this->mrResultSet->fields ) and 
                     array_key_exists( $column_index, $this->mrResultSet->fields ) )
                {
                    $column_data = $this->mrResultSet->fields[$column_index];

                    if ( $primitiveType === 'http://www.w3.org/2001/XMLSchema#dateTime' )
                    {
                        // Try to convert to xsd:dateTime
                        if ( preg_match( "'^(\d{4})\-(\d{2})\-(\d{2})\s(\d{2}):(\d{2}):(\d{2})((\+|\-)(\d{2})(:(\d{2}))?)?$'", $column_data, $matches ) )
                        {
                            $year  = $matches[1];
                            $month = $matches[2];
                            $day   = $matches[3];
                            $hr    = $matches[4];
                            $min   = $matches[5];
                            $secs  = $matches[6];

                            $column_data = "$year-$month-$day".'T'."$hr:$min:$secs";
                        }
                    }

                    $data .= TpServiceUtils::EncodeData( $column_data, $this->mDbEncoding );
                }
                else 
                {
                    // Note: column index will be -1 if the concept is not mapped

                    if ( $expression->IsRequired() )
                    {
                        // NULLs can later raise errors or drop nodes if the node
                        // is mandatory or not
                        return null;
                    }
                    else
                    {
                        $data .= '';
                    }
                }
            }
        }

        return $data;

    } // end of _GetMappedData

    function GetTotalReturned( )
    {
        return $this->mTotalReturned;

    } // end of GetTotalReturned

} // end of TpXmlGenerator
?>