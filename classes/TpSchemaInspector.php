<?php
/**
 * $Id: TpSchemaInspector.php 1996 2009-08-26 21:51:14Z rdg $
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
require_once('TpServiceUtils.php');
require_once('TpFilter.php');

class TpSchemaInspector extends XsSchemaVisitor
{
    var $mrResource;
    var $mrOutputModel;
    var $mrResponseStructure;
    var $mIndexingElement;
    var $mrLocalMapping;
    var $mMaxLevels;
    var $mModelGroupVisits = 0;
    var $mPartialNodes;
    var $mAcceptedPaths = array(); // $path => min occurs
    var $mRejectedPaths = array(); // $paths
    var $mAbort = false;
    var $mFoundIndexingElement = false;
    var $mGreedy = true;

    function TpSchemaInspector( &$rResource, &$rOutputModel, &$rLocalMapping, $maxLevels, $partial )
    {
        $this->mrResource =& $rResource;
        $this->mrOutputModel =& $rOutputModel;
        $this->mrResponseStructure =& $rOutputModel->GetResponseStructure();
        $this->mIndexingElement = $rOutputModel->GetIndexingElement();
        $this->mrLocalMapping =& $rLocalMapping;
        $this->mMaxLevels = $maxLevels;
        $this->mPartialNodes = $partial;

        if ( count( $partial ) )
        {
            $this->mGreedy = false;
        }

    } // end of member function TpSchemaInspector

    function Inspect( )
    {
        global $g_dlog;

        $g_dlog->debug( '[Schema Inspector]' );

        if ( ! is_object( $this->mrResponseStructure ) )
        {
            $error = 'Could not find a response structure in the output model';
            TpDiagnostics::Append( DC_INVALID_REQUEST, $error, DIAG_ERROR );

            $this->_ActivateAbort();

            return;
        }

        $global_elements = $this->mrResponseStructure->GetElementDecls();

        $path = '';

        $num_globals = count( $global_elements );

        if ( $num_globals == 0 )
        {
            $error = 'No global element defined in response structure';
            TpDiagnostics::Append( DC_INVALID_REQUEST, $error, DIAG_ERROR );

            $this->_ActivateAbort();

            return;
        }
        else
        {
            $found_root = false;

            $lacks_concrete = true;

            $ns = $this->mrResponseStructure->GetTargetNamespace();

            $root_element = $this->mrOutputModel->GetRootElement();

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
                        if ( $xs_element_decl->IsAbstract() )
                        {
                            $error = 'Root element is abstract';
                            TpDiagnostics::Append( DC_INVALID_REQUEST, $error, DIAG_ERROR );

                            $this->_ActivateAbort();

                            return;
                        }

                        $found_root = true;
                        $lacks_concrete = false;

                        $r_xs_element_decl =& $this->mrResponseStructure->GetElementDecl( $ns, $el_name );
                        $r_xs_element_decl->Accept( $this, $path );

                        break;
                    }
                }
                else {

                    if ( ! $xs_element_decl->IsAbstract() )
                    {
                        $lacks_concrete = false;

                        // First "concrete" global element declaration
                        $r_xs_element_decl =& $this->mrResponseStructure->GetElementDecl( $ns, $el_name );
                        $r_xs_element_decl->Accept( $this, $path );

                        break;
                    }
                }
            }

            if ( ( ! empty( $root_element ) ) and ( ! $found_root ) )
            {
                $error = 'Could not find the root element';
                TpDiagnostics::Append( DC_INVALID_REQUEST, $error, DIAG_ERROR );

                $this->_ActivateAbort();

                return;
            }
            else
            {
                if ( $lacks_concrete )
                {
                    $error = 'No concrete (non abstract) global element defined in '.
                             'response structure';
                    TpDiagnostics::Append( DC_INVALID_REQUEST, $error, DIAG_ERROR );

                    $this->_ActivateAbort();

                    return;
                }
            }
        }

        if ( ! $this->mFoundIndexingElement )
        {
            $error = 'Final response structure has no indexingElement';
            TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $error, DIAG_WARN );
        }

    } // end of member function Inspect

    function VisitElementDecl( &$rXsElementDecl, $path )
    {
        global $g_dlog;

        $is_root = ( $path == '' ) ? true : false;

        $ns = $rXsElementDecl->GetTargetNamespace();

        if ( $rXsElementDecl->IsReference() )
        {
            $ref = $rXsElementDecl->GetRef();

            $g_dlog->debug( 'Found element reference '.$ref );

            $rReferencedElement =& $this->mrResponseStructure->GetElementDecl( $ns, $ref );

            if ( is_object( $rReferencedElement ) )
            {
                $rXsElementDecl->SetReferencedObj( $rReferencedElement );

                // Get namespace again (it will now use the referenced object!)
                $ns = $rXsElementDecl->GetTargetNamespace();
            }
            else
            {
                $error = 'Cannot find reference to element "'.$ref.'"';

                TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $error, DIAG_ERROR );

                $this->_ActivateAbort();

                return;
            }
        }

        $name = $rXsElementDecl->GetName();

        $prefix = $this->mrOutputModel->GetPrefix( $ns );
        
        if ( ! empty( $prefix ) )
        {
            $prefix .= ':';
        }
        $path .= '/' . $prefix . $name;
        $g_dlog->debug( 'Visiting '.$path );

        $min_occurs = (int)$rXsElementDecl->GetMinOccurs();

        if ( count( explode( '/', $path ) ) > $this->mMaxLevels )
        {
            if ( $min_occurs > 0 )
            {
                $error = 'Exceeded maximum number of element levels (element "'.
                         $path.'")';
                TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $error, DIAG_ERROR );

                $this->_ActivateAbort( $path );
            }
            else
            {
                $error = 'Optional Element "'.$path.'" exceeds the maximum number '.
                         'of element levels. It will be discarded.';
                TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $error, DIAG_WARN );
            }

            array_push( $this->mRejectedPaths, $path );

            return;
        }

        if ( ( ! $this->mGreedy ) and $min_occurs == 0 and
             ( ! $this->_BelongsToSomePartialAxis( $path ) ) )
        {
            $g_dlog->debug( 'Element '.$path.' will be ignored because it does not '.
                            'belong to any partial axis and it is optional in a '.
                            'non-greedy context' );

            array_push( $this->mRejectedPaths, $path );

            return;
        }

        // Set type object if necessary
        if ( ! $this->_PrepareType( $rXsElementDecl, $path, $min_occurs ) )
        {
            return;
        }

        $r_type =& $rXsElementDecl->GetType();

        $r_basetype =& $r_type->GetBaseType();

        if ( $r_type->IsComplexType() )
        {
            $g_dlog->debug( 'Is complex' );

            $derivation_method = $r_type->GetDerivationMethod();

            // Attributes

            $base_type_has_attributes = false;

            if ( $derivation_method == 'extension' and
                 is_object( $r_basetype ) and
                 $r_basetype->IsComplexType() )
            {
                // Base attributes, in case of extension
                $r_basetype_declared_attribute_uses =& $r_basetype->GetDeclaredAttributeUses();

                for ( $i = 0; $i < count( $r_basetype_declared_attribute_uses ); ++$i )
                {
                    $r_basetype_declared_attribute_uses[$i]->Accept( $this, $path );
                    $base_type_has_attributes = true;
                }
            }

            // Current content type (real extended attributes or attributes
            // from a not extended type)
            $r_declared_attribute_uses =& $r_type->GetDeclaredAttributeUses();

            $type_has_attributes = false;

            for ( $i = 0; $i < count( $r_declared_attribute_uses ); ++$i )
            {
                $r_declared_attribute_uses[$i]->Accept( $this, $path );
                $type_has_attributes = true;
            }

            // Content type

            if ( $derivation_method == 'extension' and ! is_null( $r_basetype ) )
            {
                if ( $r_basetype->IsComplexType() )
                {
                    $g_dlog->debug( 'Checking content of base type '.$r_basetype->GetName() );

                    // Base content type, in case of extension
                    $r_base_content_type =& $r_basetype->GetContentType();

                    $is_base_type = true;

                    $this->_CheckContentType( $r_base_content_type, $path, $is_base_type,
                                              $base_type_has_attributes );
                }
                else
                {
                    if ( ! $this->_CheckSimpleContent( $rXsElementDecl, $path, $min_occurs ) )
                    {
                        $this->_ActivateAbort( $path );
                    }
                }
            }

            // Current content type (real extended content or content
            // from a not extended type)
            $r_content_type =& $r_type->GetContentType();

            // Always check if it's not derived from a base type.
            // If it's derived from a base type, the current content can be null
            // (because it inherits from a base type)
            if ( empty( $derivation_method ) or
                 ( ( ! $this->mAbort ) and ! is_null( $r_content_type ) ) )
            {
                $g_dlog->debug( 'Checking content of type '.$r_type->GetName() );

                $is_base_type = false;

                $this->_CheckContentType( $r_content_type, $path, $is_base_type,
                                          $type_has_attributes );
            }

            // Mixed content
            if ( $r_type->IsMixed() )
            {
                $g_dlog->debug( 'Is mixed' );

                // This will force automapped concepts to be added
                $this->_CheckSimpleContent( $rXsElementDecl, $path, $min_occurs );
            }

            if ( $path == $this->mIndexingElement )
            {
                $this->mFoundIndexingElement = true;
            }

            if ( $this->mAbort )
            {
                if ( $min_occurs == 0 )
                {
                    $this->_RevertAbort( $path );
                }
                else
                {
                    if ( ! in_array( $path, $this->mRejectedPaths ) )
                    {
                        array_push( $this->mRejectedPaths, $path );
                    }
                }
            }
            else
            {
                if ( $r_type->IsMixed() )
                {
                    if ( ! in_array( $path, $this->mRejectedPaths ) )
                    {
                        $this->mAcceptedPaths[$path] = $min_occurs;
                    }
                }
                else
                {
                    if ( count( $this->mRejectedPaths ) == 0 or
                         $this->_HasAnAcceptedSubnode( $path ) )
                    {
                        $this->mAcceptedPaths[$path] = $min_occurs;
                    }
                }
            }
        }
        else
        {
            $g_dlog->debug( 'Is simple' );

            // Simple content
            if ( $this->_CheckSimpleContent( $rXsElementDecl, $path, $min_occurs ) )
            {
                $this->mAcceptedPaths[$path] = $min_occurs;

                if ( $path == $this->mIndexingElement )
                {
                    $this->mFoundIndexingElement = true;
                }
            }
        }

    } // end of member function VisitElementDecl

    function VisitAttributeUse( &$rXsAttributeUse, $path )
    {
        global $g_dlog;

        $r_attribute_decl =& $rXsAttributeUse->GetDecl();

        $ns = $r_attribute_decl->GetTargetNamespace();

        if ( $r_attribute_decl->IsReference() )
        {
            $ref = $r_attribute_decl->GetRef();

            $g_dlog->debug( 'Found attribute reference '.$ref.' in namespace '.$ns );

            $rReferencedAttribute =& $this->mrResponseStructure->GetAttributeDecl( $ns, $ref );

            if ( is_object( $rReferencedAttribute ) )
            {
                $r_attribute_decl->SetReferencedObj( $rReferencedAttribute );

                // Get namespace again (it will now be based on the referenced object!)
                $ns = $r_attribute_decl->GetTargetNamespace();
            }
            else
            {
                $error = 'Cannot find attribute reference to "'.$ref.'"';

                TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $error, DIAG_ERROR );

                $this->_ActivateAbort( $path );

                return;
            }
        }

        $name = $r_attribute_decl->GetName();

        $prefix = $this->mrOutputModel->GetPrefix( $ns );

        if ( ! empty( $prefix ) )
        {
            $prefix .= ':';
        }

        $path .= '/@' . $prefix . $name;

        $g_dlog->debug( 'Visiting '.$path );

        $min_occurs = 0;

        if ( $rXsAttributeUse->IsRequired() )
        {
            $min_occurs = 1;
        }

        if ( ! $this->_PrepareType( $rXsAttributeUse, $path, $min_occurs ) )
        {
            return;
        }

        if ( $this->_CheckSimpleContent( $rXsAttributeUse, $path, $min_occurs ) )
        {
            if ( ( ! $this->mGreedy ) and $min_occurs == 0 )
            {
                array_push( $this->mRejectedPaths, $path );

                $g_dlog->debug( 'Attribute '.$path.' will be ignored because '.
                                'it is optional in a non-greedy context' );

                return;
            }

            $this->mAcceptedPaths[$path] = $min_occurs;
        }

    } // end of member function VisitAttributeUse

    function VisitModelGroup( &$rXsModelGroup, $path )
    {
        global $g_dlog;

        $g_dlog->debug( 'Visiting model group '. $rXsModelGroup->GetCompositor() );

        ++$this->mModelGroupVisits;

        if ( $this->mModelGroupVisits > 20 )
        {
            $error = 'Exceed maximum number of model group nesting';

            TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $error, DIAG_ERROR );

            $this->_ActivateAbort( $path );

            return;
        }

        $r_particles =& $rXsModelGroup->GetParticles();

        $g_dlog->debug( 'Num particles: '.count( $r_particles ) );

        for ( $i = 0; $i < count( $r_particles ); ++$i )
        {
            $r_particles[$i]->Accept( $this, $path );
        }

        --$this->mModelGroupVisits;

    } // end of member function VisitModelGroup

    function _PrepareType( &$rTypedObj, $path, $minOccurs )
    {
        global $g_dlog;

        $r_type =& $rTypedObj->GetType();

        // Set type object if necessary
        if ( is_string( $r_type ) )
        {
            $g_dlog->debug( 'Has string type '.$r_type );

            $type_str = $r_type;

            $r_type =& $this->mrResponseStructure->GetType( $rTypedObj->GetTargetNamespace(), $r_type );

            if ( $r_type == null )
            {
                if ( $minOccurs > 0 )
                {
                    $error = 'Unknown type '.$type_str.' for mandatory node "'.$path.'"';

                    TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $error, DIAG_WARN );

                    $this->_ActivateAbort( $path );
                }
                else
                {
                    $error = 'Unknown type "'.$type_str.'" for node "'.$path.'". It '.
                             'will be discarded';

                    TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $error, DIAG_WARN );
                }

                array_push( $this->mRejectedPaths, $path );

                return false;
            }

            $g_dlog->debug( 'Setting type object' );

            $rTypedObj->SetType( $r_type );
        }
        else if ( ! is_object( $r_type ) )
        {
            if ( $minOccurs > 0 )
            {
                $error = 'Undefined type for mandatory node "'.$path.'"';
                TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $error, DIAG_WARN );

                $this->_ActivateAbort( $path );
            }
            else
            {
                $error = 'Undefined type for node "'.$path.'". It will be discarded';
                TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $error, DIAG_WARN );
            }

            array_push( $this->mRejectedPaths, $path );

            return false;
        }

        $r_basetype =& $r_type->GetBaseType();

        // Set base type object if necessary
        if ( ! is_null( $r_basetype ) )
        {
            $g_dlog->debug( 'Has base type' );

            if ( is_string( $r_basetype ) )
            {
                $g_dlog->debug( 'Base type is string '.$r_basetype );

                $basetype_str = $r_basetype;

                $r_basetype =& $this->mrResponseStructure->GetType( $rTypedObj->GetTargetNamespace(), $basetype_str );

                if ( $r_basetype == null )
                {
                    if ( $minOccurs > 0 )
                    {
                        $error = 'Unknown base type '.$basetype_str.' for mandatory node "'.$path.'"';

                        TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $error, DIAG_WARN );

                        $this->_ActivateAbort( $path );
                    }
                    else
                    {
                        $error = 'Unknown base type "'.$basetype_str.'" for node "'.$path.'". It '.
                                 'will be discarded';

                        TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $error, DIAG_WARN );
                    }

                    array_push( $this->mRejectedPaths, $path );

                    return false;
                }

                $g_dlog->debug( 'Setting base type object' );

                $r_type->SetBaseType( $r_basetype );
            }
        }

        return true;

    } // end of member function _PrepareType

    function _CheckContentType( &$rContentType, $path, $isBaseType, $hasAttributes )
    {
        global $g_dlog;

        $g_dlog->debug( 'Content type: '.get_class( $rContentType ) );
        $g_dlog->debug( 'Is object: '.is_object( $rContentType ) );
        $g_dlog->debug( 'Is null: '.is_null( $rContentType ) );

        if ( strtolower( get_class( $rContentType ) ) == 'xsmodelgroup' )
        {
            $rContentType->Accept( $this, $path );
        }
        else
        {
            // Note: Simple and complex content extensions or restrictions
            // will fall here when fully implemented in the future.

            // Note: Empty complex types (rContentType == null) must not be
            //       rejected if they have attributes. Empty base complex types
            //       must not be rejected even if they do not have any attributes
            //       (the extended type might include attribtues in this case).
            if ( is_null( $rContentType ) and ( !$hasAttributes ) and ( !$isBaseType ) )
            {
                $g_dlog->debug( 'Rejecting '.$path.' for having no content: '.
                                strtolower( get_class( $rContentType ) ) );

                $this->_ActivateAbort( $path );
            }
        }

    } // end of _CheckContentType

    function _CheckSimpleContent( $obj, $path, $isMandatory )
    {
        global $g_dlog;

        $add_if_missing = true;

        $mapping = $this->mrOutputModel->GetMappingForNode( $path, $add_if_missing );

        if ( ( ! $obj->HasFixedValue() ) and $mapping != null )
        {
            $num_mappings = count( $mapping );

            for ( $i = 0; $i < $num_mappings; ++$i )
            {
                $expression = $mapping[$i];

                $reference = $expression->GetReference();

                if ( $expression->GetType() == EXP_CONCEPT )
                {
                    if ( strtolower( get_class( $obj ) ) == 'xselementdecl' and
                         $obj->GetMinOccurs() > 1 and
                         TpServiceUtils::Contains( $path, $this->mIndexingElement ) )
                    {
                        $msg = 'Node "'.$path.'" has incompatible value for "minOccurs" '.
                               '(greater than one) for being inside the '.
                               'indexingElement and locally mapped to a concept';

                        TpDiagnostics::Append( DC_UNSUPPORTED_OUTPUT_MODEL, $msg,
                                               DIAG_ERROR );
                        $this->_ActivateAbort( $path );

                        array_push( $this->mRejectedPaths, $path );

                        return false;
                    }

                    $concept = $this->mrLocalMapping->GetConcept( $reference );

                    if ( is_object( $concept ) and $concept->IsMapped() )
                    {
                        $mapping_type = $concept->GetMappingType();

                        if ( strcasecmp( $mapping_type, 'SingleColumnMapping' ) == 0 and
                             ! TpServiceUtils::Contains( $path, $this->mIndexingElement ) )
                        {
                            $msg = 'Node "'.$path.'" is outside the indexing element and '.
                                   'was mapped to a column. This will only work if the '.
                                   'corresponding value is the same for the entire record '.
                                   'set returned!';

                            TpDiagnostics::Append( DC_CONFIG_ISSUE, $msg, DIAG_WARN );

                             // Restrictions removed to allow many-to-one
                             // relationships being used outside the indexing
                             // element scope

//                             if ( $expression->IsRequired() )
//                             {
//                                 TpDiagnostics::Append( DC_UNSUPPORTED_OUTPUT_MODEL, $msg,
//                                                        DIAG_ERROR );

//                                 $this->_ActivateAbort( $path );
//                             }
//                             else
//                             {
//                                 $msg .= ' It will be discarded.';

//                                 TpDiagnostics::Append( DC_TRUNCATED_RESPONSE, $msg,
//                                                        DIAG_WARN );
//                             }

//                             array_push( $this->mRejectedPaths, $path );

//                             return false;
                        }
                    }
                    else
                    {
                        $concept_id = $reference;

                        if ( is_object( $concept ) )
                        {
                            $concept_id = $concept->GetId();
                        }

                        $msg = 'Concept "'.$concept_id.'" was not mapped '.
                               'to the local database.';

                        // Only abort if expression is required
                        if ( $expression->IsRequired() )
                        {
                            $this->_ActivateAbort( $path );

                            $msg .= ' Aborting.';
                            TpDiagnostics::Append( DC_CONTENT_UNAVAILABLE, $msg, DIAG_WARN );
                        } else {
                            TpDiagnostics::Append( DC_CONTENT_UNAVAILABLE, $msg, DIAG_DEBUG );
                        }

                        // Don't reject if it's a concatenation ($num_mappings > 1)
                        if ( $num_mappings == 1 )
                        {
                            array_push( $this->mRejectedPaths, $path );

                            return false;
                        }
                    }
                }
                else if ( $expression->GetType() == EXP_VARIABLE )
                {
                    if ( ! $this->mrResource->HasVariable( $reference ) )
                    {
                        $msg = 'Variable "'.$reference.'" is not supported.';

                        // Only abort if expression is required
                        if ( $expression->IsRequired() )
                        {
                            $this->_ActivateAbort( $path );

                            $msg .= ' Aborting.';
                        }

                        TpDiagnostics::Append( DC_CONTENT_UNAVAILABLE, $msg, DIAG_WARN );

                        // Don't reject if it's a concatenation ($num_mappings > 1)
                        if ( $num_mappings == 1 )
                        {
                            array_push( $this->mRejectedPaths, $path );

                            return false;
                        }
                    }
                }
            }
        }

        return true;

    } // end of _CheckSimpleContent

    function _BelongsToSomePartialAxis( $path )
    {
        foreach ( $this->mPartialNodes as $partial_node )
        {
            if ( TpServiceUtils::Contains( $partial_node, $path ) )
            {
                return true;
            }
        }

        return false;

    } // end of member function _BelongsToSomePartialAxis

    function _ActivateAbort( $path='' )
    {
        $this->mAbort = true;

    } // end of member function _ActivateAbort

    function _RevertAbort( $path )
    {
        $this->mAbort = false;

    } // end of member function _ActivateAbort

    function MustAbort( )
    {
        return $this->mAbort;

    } // end of member function MustAbort

    function GetAcceptedPaths( )
    {
        return $this->mAcceptedPaths;

    } // end of member function GetAcceptedPaths

    function GetRejectedPaths( )
    {
        return $this->mRejectedPaths;

    } // end of member function GetRejectedPaths

    function _HasAnAcceptedSubnode( $path )
    {
	global $g_dlog;
        foreach ( $this->mAcceptedPaths as $accepted_path => $num )
        {
            if ( strpos( $accepted_path, $path ) === 0 )
            {
		$g_dlog->debug( "$path is in $accepted_path" );
                return true;
            }
        }

        return false;

    } // end of member function _HasAnAcceptedSubnode

} // end of TpSchemaInspector
?>