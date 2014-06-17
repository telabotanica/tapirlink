<?php
/**
 * $Id: XsModelGroup.php 559 2008-02-27 22:22:41Z rdg $
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
 * This class has been largely based on the API documentation of 
 * xsom (https://xsom.dev.java.net/) written by Kohsuke Kawaguchi.
 */

class XsModelGroup
{
    var $mCompositor;
    var $mParticles = array();
    var $mMinOccurs = 1;
    var $mMinRange;
    var $mMaxOccurs = 1;

    function XsModelGroup( $compositor ) 
    {
        $this->mCompositor = $compositor;

    } // end of member function XsModelGroup

    function GetCompositor( ) 
    {
        return $this->mCompositor;
        
    } // end of member function GetCompositor

    function AddParticle( &$rParticle ) 
    {
        $this->mParticles[count($this->mParticles)] =& $rParticle;
        
    } // end of member function AddParticle

    function &GetParticles( ) 
    {
        return $this->mParticles;
        
    } // end of member function GetParticles

    function SetMinOccurs( $minOccurs ) 
    {
        $this->mMinOccurs = $minOccurs;
        
    } // end of member function SetMinOccurs

    function SetMaxOccurs( $maxOccurs ) 
    {
        $this->mMaxOccurs = $maxOccurs;
        
    } // end of member function SetMaxOccurs

    function GetMinOccurs( ) 
    {
        return $this->mMinOccurs;
        
    } // end of member function GetMinOccurs

    function GetMaxOccurs( ) 
    {
        return $this->mMaxOccurs;
        
    } // end of member function GetMaxOccurs

    function GetMinRange( ) 
    {
        if ( ! is_null( $this->mMinRange ) )
        {
            return $this->mMinRange;
        }

        $min_range = $this->mMinOccurs;

        if ( $this->mCompositor == 'sequence' or $this->mCompositor == 'all' )
        {
            // From XML Schema spec:
            // "The product of the particle's {min occurs} and the sum of 
            // the {min occurs} of every wildcard or element declaration particle 
            // in the group's {particles} and the minimum part of the effective 
            // total range of each of the group particles in the group's {particles} 
            // (or 0 if there are no {particles}).

            if ( count( $this->mParticles ) == 0 )
            {
                $this->mMinRange = 0;

                return 0;
            }

            $min = 0;

            foreach ( $this->mParticles as $particle )
            {
                if ( strtolower( get_class( $particle ) ) == 'xsmodelgroup' )
                {
                    $min += $particle->GetMinRange();
                }
                else
                {
                    $min += $particle->GetMinOccurs();
                }
            }

            $min_range *= $min;
        }
        else if ( $this->mCompositor == 'choice' )
        {
            // From XML Schema spec:
            // "The product of the particle's {min occurs} and the minimum of 
            // the {min occurs} of every wildcard or element declaration particle 
            // in the group's {particles} and the minimum part of the effective 
            // total range of each of the group particles in the group's {particles} 
            // (or 0 if there are no {particles})."

            if ( count( $this->mParticles ) == 0 )
            {
                $this->mMinRange = 0;

                return 0;
            }

            $min = null;

            foreach ( $this->mParticles as $particle )
            {
                if ( strtolower( get_class( $particle ) ) == 'xsmodelgroup' )
                {
                    $particle_min = $particle->GetMinRange();
                }
                else
                {
                    $particle_min = $particle->GetMinOccurs();
                }

                if ( is_null( $min ) )
                {
                    $min = $particle_min;
                }
                else
                {
                    $min = min( $min, $particle_min );
                }
            }

            $min_range *= $min;
        }

        $this->mMinRange = $min_range;

        return $min_range;
        
    } // end of member function GetMinRange

    function Accept( &$visitor, $path )
    {
        return $visitor->VisitModelGroup( $this, $path );

    } // end of member function Accept

    /**
     * Internal method called before serialization
     *
     * @return array Properties that should be considered during serialization
     */
    function __sleep()
    {
	return array( 'mCompositor', 'mParticles', 'mMinOccurs', 'mMinRange',
                      'mMaxOccurs' );

    } // end of member function __sleep

} // end of XsModelGroup
?>