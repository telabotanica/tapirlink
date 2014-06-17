<?php
/**
 * $Id: TpConceptMappingFactory.php 399 2007-06-25 02:59:07Z rdg $
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

require_once('SingleColumnMapping.php');
require_once('FixedValueMapping.php');
require_once('EnvironmentVariableMapping.php');

class TpConceptMappingFactory
{
    function TpConceptMappingFactory( ) 
    {

    } // end of member function TpConceptMappingFactory

    static function GetInstance( $id ) 
    {
        if ( $id == 'SingleColumnMapping') 
        {
            return new SingleColumnMapping();
        }
        else if ( $id == 'FixedValueMapping') 
        {
            return new FixedValueMapping();
        }
        else if ( $id == 'EnvironmentVariableMapping') 
        {
            return new EnvironmentVariableMapping();
        }

        return null;

    } // end of member function GetInstance

    static function GetOptions( ) 
    {
        return array( 'unmapped' => '-- unmapped --',
                      'SingleColumnMapping'=>'single column', 
                      'FixedValueMapping'=>'fixed value',
                      'EnvironmentVariableMapping'=>'environment variable' );

    } // end of member function GetOptions

} // end of TpConceptMappingFactory
?>