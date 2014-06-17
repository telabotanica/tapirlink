<?php
/**
 * $Id: XsManager.php 560 2008-02-28 00:02:04Z rdg $
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

class XsManager
{
    var $mDebugMode = false;      // Debugging flag
    var $mLibPrefix = '[phpxsd]'; // Prefix for messages
    var $mrLogger;                // Optional PEAR logger object

    // No constructor - this class uses the singleton pattern
    // Use GetInstance instead

    function &GetInstance( )
    {
        static $instance;

        if ( ! isset( $instance ) ) 
        {
            $instance = new XsManager();
        }

        return $instance;

    } // end of member function GetInstance

    /**
     * Activates or deactivates debug mode.
     * @param bool Boolean
     */
    function SetDebugMode( $bool )
    {
        $this->mDebugMode = $bool;

    } // end of member function SetDebugMode

    /**
     * Indicates if debugging is activated.
     */
    function InDebugMode( )
    {
        return $this->mDebugMode;

    } // end of member function InDebugMode

    /**
     * Set a PEAR log object to store debugging.
     * @param rLogger Object Instance of a PEAR log object
     */
    function SetLogger( &$rLogger )
    {
        $this->mrLogger =& $rLogger;

    } // end of member function SetLogger

    /**
     * Send a debug message to the logger object.
     * @param msg String
     */
    function Debug( $msg )
    {
        if ( $this->mrLogger )
        {
            $this->mrLogger->debug( $this->mLibPrefix.' '.$msg );
        }

    } // end of member function Debug

    /**
     * Returns a formated message with the library prefix.
     * @param msg string
     */
    function GetMsg( $msg )
    {
        return $this->mLibPrefix.' '.$msg;

    } // end of member function GetMsg

} // end of XsManager
?>