<?php
/**
 * $Id: XsSchemaNavigator.php 720 2008-06-17 02:16:10Z rdg $
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

require_once(dirname(__FILE__).'/XsSchemaVisitor.php');

class XsSchemaNavigator extends XsSchemaVisitor
{
    var $mXsd;
    var $mNamespace; // default namespace
    var $mAllNamespaces;  // array: namespace => prefix
    var $mNamespacesUsed; // array: namespace => prefix

    var $mStartElementCallback    = null;
    var $mEndElementCallback      = null;
    var $mStartAttributesCallback = null;
    var $mEndAttributesCallback   = null;
    var $mAttributeDataCallback   = null;
    var $mStartGroupCallback      = null;
    var $mEndGroupCallback        = null;

    var $mAcceptedPaths = array();
    var $mRejectedPaths = array();
    var $mAbort = false;

    function XsSchemaNavigator( $xsd )
    {
        $this->mXsd = $xsd;

        $this->mNamespace = $xsd->GetTargetNamespace();

    } // end of member function XsSchemaNavigator

    function SetNamespacePrefixes( $namespaces )
    {
        if ( is_array( $namespaces ) )
        {
            $this->mAllNamespaces = $namespaces;
        }
        
    } // end of member function SetNamespacePrefixes

    function GetNamespacePrefixes( )
    {
        return $this->mNamespacesUsed;
        
    } // end of member function GetNamespacePrefixes

    function _GetPrefix( $ns )
    {
        $prefix = '';

        if ( $ns != $this->mNamespace )
        {
            if ( isset( $this->mNamespacesUsed[$ns] ) )
            {
                $prefix = $this->mNamespacesUsed[$ns];
            }
            else if ( isset( $this->mAllNamespaces[$ns] ) )
            {
                $prefix = $this->mAllNamespaces[$ns];

                $this->mNamespacesUsed[$ns] = $prefix;
            }
            else
            {
                $cnt = 1;

                $prefix = 'sn'.$cnt;

                while ( isset( $this->mNamespacesUsed[$prefix] ) )
                {
                    ++$cnt;
                    $prefix = 'sn'.$cnt;
                }

                $this->mNamespacesUsed[$ns] = $prefix;
            }
        }

        return $prefix;
        
    } // end of member function _GetPrefix

    function SetElementCallbacks( $startElementCallback, $endElementCallback )
    {
        $this->mStartElementCallback = $startElementCallback;
        $this->mEndElementCallback   = $endElementCallback;
        
    } // end of member function SetElementCallbacks

    function SetAttributeCallbacks( $startAttributesCallback, $endAttributesCallback, $attributeDataCallback )
    {
        $this->mStartAttributesCallback = $startAttributesCallback;
        $this->mEndAttributesCallback   = $endAttributesCallback;
        $this->mAttributeDataCallback   = $attributeDataCallback;
        
    } // end of member function SetAttributesCallbacks

    function SetGroupCallbacks( $startGroupCallback, $endGroupCallback )
    {
        $this->mStartGroupCallback = $startGroupCallback;
        $this->mEndGroupCallback   = $endGroupCallback;
        
    } // end of member function SetGroupCallbacks

    function Navigate( )
    {
        // Choose namespace prefixes if they were not provided
        if ( ! is_array( $this->mAllNamespaces ) )
        {
            $ns_manager =& XsNamespaceManager::GetInstance();

            $this->mAllNamespaces = $ns_manager->GetAllNamespaces();
        }

        // Get all global elements
        $global_elements = $this->mXsd->GetElementDecls();

        $path = '';

        $num_globals = count( $global_elements );

        if ( $num_globals == 0 )
        {
            $error = 'No global element defined in schema';
            trigger_error( $error, E_USER_ERROR );
            return;
        }
        else
        {
            $lacks_concrete = true;

            foreach ( $global_elements as $el_name => $xs_element_decl )
            {
                if ( ! $xs_element_decl->IsAbstract() )
                {
                    $lacks_concrete = false;

                    // First "concrete" global element declaration
                    $xs_element_decl->Accept( $this, $path );

                    break;
                }
            }

            if ( $lacks_concrete )
            {
                $error = 'No concrete (non abstract) global element defined in schema';
                trigger_error( $error, E_USER_ERROR );
                return;
            }
        }

    } // end of member function Navigate

    function VisitElementDecl( &$rXsElementDecl, $path )
    {
        $is_root = ( $path == '' ) ? true : false;

        $ns = $rXsElementDecl->GetTargetNamespace();

        if ( $rXsElementDecl->IsReference() )
        {
            $ref = $rXsElementDecl->GetRef();

            $rReferencedElement =& $this->mXsd->GetElementDecl( $ns, $ref );

            if ( is_object( $rReferencedElement ) )
            {
                $rXsElementDecl->SetReferencedObj( $rReferencedElement );

                // Get namespace again (it will now use the referenced object!)
                $ns = $rXsElementDecl->GetTargetNamespace();
            }
            else
            {
                $error = 'Cannot find reference to element "'.$ref.'"';
                //trigger_error( $error, E_USER_ERROR );

                $this->mAbort = true;

                return;
            }
        }

        $name = $rXsElementDecl->GetName();

        $path_token_prefix = $this->_GetPrefix( $ns );

        if ( ! empty( $path_token_prefix ) )
        {
            $path_token_prefix .= ':';
        }

        $path .= '/' . $path_token_prefix . $name;

        $min_occurs = (int)$rXsElementDecl->GetMinOccurs();

        $r_type =& $rXsElementDecl->GetType();

        // Set type object if necessary
        if ( is_string( $r_type ) )
        {
            $type_str = $r_type;

            $r_type =& $this->mXsd->GetType( $rXsElementDecl->GetTargetNamespace(), $r_type );

            if ( $r_type == null )
            {
                $error = 'Unknown type '.$type_str.' for element "'.$path.'"';
                //trigger_error( $error, E_USER_NOTICE );

                $this->mAbort = true;

                $this->mRejectedPaths[] = $path;

                return;
            }

            $rXsElementDecl->SetType( $r_type );
        }
        else if ( ! is_object( $r_type ) )
        {
            $error = 'Unknown type "'.$r_type.'" for element "'.$path.'"';
            //trigger_error( $error, E_USER_NOTICE );

            $this->mAbort = true;

            $this->mRejectedPaths[] = $path;

            return;
        }

        $r_basetype =& $r_type->GetBaseType();

        // Set base type object if necessary
        if ( ! is_null( $r_basetype ) )
        {
            if ( is_string( $r_basetype ) )
            {
                $basetype_str = $r_basetype;

                $r_basetype =& $this->mXsd->GetType( $rXsElementDecl->GetTargetNamespace(), $basetype_str );

                if ( $r_basetype == null )
                {
                    $error = 'Unknown base type '.$basetype_str.' for element "'.$path.'"';
                    //trigger_error( $error, E_USER_NOTICE );

                    $this->mAbort = true;

                    $this->mRejectedPaths[] = $path;

                    return;
                }

                $r_type->SetBaseType( $r_basetype );
            }
        }

        // START ELEMENT

        if ( ! is_null( $this->mStartElementCallback ) )
        {
            call_user_func( $this->mStartElementCallback, $rXsElementDecl, $path );
        }

        if ( $r_type->IsComplexType() )
        {
            $derivation_method = $r_type->GetDerivationMethod();

            // Attributes

            $base_type_has_attributes = false;

            if ( $derivation_method == 'extension' and 
                 is_object( $r_basetype ) and 
                 $r_basetype->IsComplexType() )
            {
                // Base attributes, in case of extension
                $r_basetype_declared_attribute_uses =& $r_basetype->GetDeclaredAttributeUses();

                $num_attributes = count( $r_basetype_declared_attribute_uses );

                if ( $num_attributes )
                {
                    // START ATTRIBUTES (way 1)

                    if ( ! is_null( $this->mStartAttributesCallback ) )
                    {
                        call_user_func( $this->mStartAttributesCallback );
                    }

                    $base_type_has_attributes = true;

                    for ( $i = 0; $i < $num_attributes; ++$i )
                    {
                        $r_basetype_declared_attribute_uses[$i]->Accept( $this, $path );
                    }
                }
            }

            // Current content type (real extended attributes or attributes
            // from a not extended type)
            $r_declared_attribute_uses =& $r_type->GetDeclaredAttributeUses();

            $type_has_attributes = false;

            $num_attributes = count( $r_declared_attribute_uses );

            if ( $num_attributes )
            {
                if ( ! $base_type_has_attributes )
                {
                    // START ATTRIBUTES (way 2)

                    if ( ! is_null( $this->mStartAttributesCallback ) )
                    {
                        call_user_func( $this->mStartAttributesCallback );
                    }
                }

                $type_has_attributes = true;

                for ( $i = 0; $i < $num_attributes; ++$i )
                {
                    $r_declared_attribute_uses[$i]->Accept( $this, $path );
                }
            }

            if ( $base_type_has_attributes or $type_has_attributes )
            {
                // END ATTRIBUTES

                if ( ! is_null( $this->mEndAttributesCallback ) )
                {
                    call_user_func( $this->mEndAttributesCallback );
                }
            }

            // Content type

            if ( $derivation_method == 'extension' and ! is_null( $r_basetype ) )
            {
                if ( $r_basetype->IsComplexType() )
                {
                    // Base content type, in case of extension
                    $r_base_content_type =& $r_basetype->GetContentType();
                    
                    $is_base_type = true;

                    $this->_CheckContentType( $r_base_content_type, $path, $is_base_type, 
                                              $base_type_has_attributes );
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
                $is_base_type = false;
                
                $this->_CheckContentType( $r_content_type, $path, $is_base_type, 
                                          $type_has_attributes );
            }

            if ( $this->mAbort )
            {
                if ( ! in_array( $path, $this->mRejectedPaths ) )
                {
                    $this->mRejectedPaths[] = $path;
                }
            }
            else
            {
                $this->mAcceptedPaths[] = $path;
            }
        }
        else
        {
            // Simple content

            $this->mAcceptedPaths[] = $path;
        }

        // END ELEMENT

        if ( ! is_null( $this->mEndElementCallback ) )
        {
            call_user_func( $this->mEndElementCallback, $rXsElementDecl, $path );
        }

    } // end of member function VisitElementDecl

    function VisitAttributeUse( &$rXsAttributeUse, $path )
    {
        $r_attribute_decl =& $rXsAttributeUse->GetDecl();

        $ns = $r_attribute_decl->GetTargetNamespace();

        if ( $r_attribute_decl->IsReference() )
        {
            $ref = $r_attribute_decl->GetRef();

            $rReferencedAttribute =& $this->mXsd->GetAttributeDecl( $ns, $ref );

            if ( is_object( $rReferencedAttribute ) )
            {
                $r_attribute_decl->SetReferencedObj( $rReferencedAttribute );

                // Get namespace again (it will now be based on the referenced object!)
                $ns = $r_attribute_decl->GetTargetNamespace();
            }
            else
            {
                $error = 'Cannot find attribute reference to "'.$ref.'"';
                //trigger_error( $error, E_USER_NOTICE );

                $this->mAbort = true;

                return;
            }
        }

        $name = $r_attribute_decl->GetName();

        $path_token_prefix = $this->_GetPrefix( $ns );

        if ( ! empty( $path_token_prefix ) )
        {
            $path_token_prefix .= ':';
        }

        $path .= '/@' . $path_token_prefix . $name;

        $type = $r_attribute_decl->GetType();

        if ( is_string( $type ) )
        {
            $type = $this->mXsd->GetType( $ns, $type );

            $r_attribute_decl->SetType( $type );
        }

        $this->mAcceptedPaths[] = $path;

        // ATTRIBUTE DATA

        if ( ! is_null( $this->mAttributeDataCallback ) )
        {
            call_user_func( $this->mAttributeDataCallback, $r_attribute_decl, $path );
        }
        
    } // end of member function VisitAttributeUse

    function VisitModelGroup( &$rXsModelGroup, $path )
    {
        $r_particles =& $rXsModelGroup->GetParticles();

        $num_particles = count( $r_particles );

        if ( $num_particles )
        {
            // START GROUP

            if ( ! is_null( $this->mStartGroupCallback ) )
            {
                call_user_func( $this->mStartGroupCallback, $rXsModelGroup );
            }

            for ( $i = 0; $i < $num_particles; ++$i )
            {
                $r_particles[$i]->Accept( $this, $path );
            }

            // END GROUP

            if ( ! is_null( $this->mEndGroupCallback ) )
            {
                call_user_func( $this->mEndGroupCallback, $rXsModelGroup );
            }
        }
        
    } // end of member function VisitModelGroup

    function _CheckContentType( &$rContentType, $path, $isBaseType, $hasAttributes )
    {
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
                $this->mAbort = true;
            }
        }

    } // end of _CheckContentType

    function GetAcceptedPaths( )
    {
        return $this->mAcceptedPaths;
        
    } // end of member function GetAcceptedPaths

    function GetRejectedPaths( )
    {
        return $this->mRejectedPaths;
        
    } // end of member function GetRejectedPaths

} // end of XsSchemaNavigator
?>