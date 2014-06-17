<?php
/**
 * $Id: tapir_errors.php 1972 2009-02-04 12:11:56Z rdg $
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
 * 
 * NOTES
 *
 * Implements the Errors module for the TapirLink provider.
 */

require_once('TpDiagnostics.php');

// set the error reporting level

if ( defined( '_DEBUG' ) )
{
    error_reporting (E_ALL);
}
else
{
    error_reporting( E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE );
}

// Custom error handler function
function TapirErrorHandler ( $errNo, $errStr, $errFile, $errLine )
{
    switch ( $errNo )
    {
        case DIAG_FATAL:

	    // The error is causing termination of the script. Try and bail
            // by pushing the existing diagnostics and terminating the xml stream.

            TpDiagnostics::Append( "Fatal Error [$errNo]", 
                                   "$errStr ($errFile:$errLine)",
                                    DIAG_FATAL );
            exit;
            break;

        case DIAG_ERROR:

            TpDiagnostics::Append( "Error [$errNo]", $errStr, DIAG_ERROR );
            break;

        case DIAG_WARN:

            TpDiagnostics::Append( "Warning [$errNo]", $errStr, DIAG_WARN );
            break;

        default:
        {
            if ( $errNo <> 2048 ) // ignore compatibility warnings
            {
                $error_type = array (
                                      E_ERROR             => 'Error',
                                      E_WARNING           => 'Warning',
                                      E_PARSE             => 'Parsing Error',
                                      E_NOTICE            => 'Notice',
                                      E_CORE_ERROR        => 'Core Error',
                                      E_CORE_WARNING      => 'Core Warning',
                                      E_COMPILE_ERROR     => 'Compile Error',
                                      E_COMPILE_WARNING   => 'Compile Warning',
                                      E_USER_ERROR        => 'User Error',
                                      E_USER_WARNING      => 'User Warning',
                                      E_USER_NOTICE       => 'User Notice'
                                     );

                $label = 'PHP ';

                if ( isset( $error_type[$errNo]) )
                {
                    $label .= $error_type[$errNo];
                }
                else
                {
                    $label .= 'Unknown error['.$errNo.']';
                }

                TpDiagnostics::Append( $label,
                                       "$errStr ($errFile:$errLine)",
                                       DIAG_WARN );
            }

            break;
        }
    }
}
?>