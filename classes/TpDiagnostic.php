<?php
/**
 * $Id: TpDiagnostic.php 661 2008-05-01 17:02:41Z rdg $
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
 * @author Dave Vieglais (Biodiversity Research Center, University of Kansas)
 * 
 */

require_once(dirname(__FILE__).'/TpUtils.php');

/**
* Class TpDiagnostic
*
* Implements a diagnostic log.
* A diagnostic is identified by a string constant with further information
* provided by an optional description element. It has an associated level
* of severity that can be: debug, info, warn, error, fatal
*/
class TpDiagnostic
{
    var $mCode;
    var $mDescription;
    var $mSeverity;

    /*
     * Constructor for TpDiagnostic class
     * @param string $code
     * @param string $descr
     * @param string $severity
     */
    function TpDiagnostic( $code='E_OK', $descr='', $severity='info' )
    {
        $this->mCode        = $code;
        $this->mDescription = $descr;
        $this->mSeverity    = $severity;

    } // end of TpDiagnostic

    /*
     * Returns the severity.
     */
    function GetSeverity( )
    {
        return $this->mSeverity;

    } // end of GetSeverity

    /*
     * Returns the description.
     */
    function GetDescription( )
    {
        return $this->mDescription;

    } // end of GetDescription

    /*
     * Generates a string representation of the diagnostic object
     */
    function ToString( )
    {
        return $this->mSeverity."(".$this->mCode."):".$this->mDescription;

    } // end of ToString

    /*
     * Generates an XML representation of this object
     */
    function GetXml()
    {
        $s = "\n<diagnostic";

        $s .= ' code="'.$this->mCode.'" level="'.$this->mSeverity.'">';
        $s .= TpUtils::EscapeXMLSpecialChars( $this->mDescription );
        $s .= '</diagnostic>';

        return $s;

    } // end of GetXml

} // end of TpDiagnostic
?>
