<?php
/**
 * $Id: TpTransparentConcept.php 6 2007-01-06 01:38:13Z rdg $
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

require_once('TpConcept.php');
require_once('SingleColumnMapping.php');

// This class was created for local filters. The idea is to represent
// a table column directly, so ids are "table.column" and mapping is 
// always SingleColumnMapping.

class TpTransparentConcept extends TpConcept
{
    function TpTransparentConcept( $table, $field, $localType )
    {
        $this->mMapping = new SingleColumnMapping();

        $this->mMapping->SetTable( $table );
        $this->mMapping->SetField( $field );
        $this->mMapping->SetLocalType( $localType );

        $this->SetId( $table . '.' . $field );

    } // end of member function TpTransparentConcept

    function SetId( $id ) 
    {
        // ids in this case are "table.column"

        parent::SetId( $id );

        $parts = explode( '.', $id );

        if ( count( $parts ) == 2 )
        {
            $this->mMapping->SetTable( $parts[0] );
            $this->mMapping->SetField( $parts[1] );
        }

    } // end of member function SetId

} // end of TpTransparentConcept
?>