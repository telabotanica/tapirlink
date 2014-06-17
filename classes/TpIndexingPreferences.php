<?php
/**
 * $Id: TpIndexingPreferences.php 470 2007-11-22 12:36:23Z rdg $
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

require_once('TpUtils.php');

class TpIndexingPreferences 
{
    var $mStartTime;
    var $mMaxDuration;
    var $mFrequency;

    function IndexingPreferences( ) 
    {
        
    } // end of member function IndexingPreferences

    function LoadDefaults( ) 
    {
        $this->mStartTime   = '';
        $this->mMaxDuration = '';
        $this->mFrequency   = '';

    } // end of member function LoadDefaults

    function LoadFromSession( ) 
    {
        $hour = TpUtils::GetVar( 'hour' );
        $ampm = TpUtils::GetVar( 'ampm' );

        if ( empty( $hour ) or empty( $ampm ) )
        {
            $this->mStartTime = '';
        }
        else
        {
            if ( $ampm == 'AM' )
            {
                if ( (int)$hour < 10 )
                {
                    $hour = '0'.$hour;
                }
                else if ( (int)$hour == 12 )
                {
                    $hour = '24';
                }
            }
            else if ( $ampm == 'PM' )
            {
                if ( (int)$hour < 12 )
                {
                    $hour += 12;
                }
            }

            // Remove leading GMT
            $timezone = substr( TpUtils::GetVar( 'timezone' ), 3 );

            if ( substr( $timezone, 0, 1 ) == '0' )
            {
                $timezone_sign = '+';

                $timezone_hour = '00';
            }
            else
            {
                $timezone_sign = substr( $timezone, 0, 1 );

                $timezone_hour = substr( $timezone, 1 );

                if ( strlen( $timezone_hour ) == 1 )
                {
                    $timezone_hour = '0'.$timezone_hour;
                }
            }

            $this->mStartTime = $hour.':00:00'.$timezone_sign.$timezone_hour.':00';
        }

        $this->mMaxDuration = TpUtils::GetVar( 'maxDuration' );
        $this->mFrequency   = TpUtils::GetVar( 'frequency' );

    } // end of member function LoadFromSession

    function GetStartTime( ) 
    {
        return $this->mStartTime;

    } // end of member function GetStartTime

    function SetStartTime( $startTime ) 
    {
        $this->mStartTime = $startTime;

    } // end of member function SetStartTime

    function ParseTime( ) 
    {
        $matches = array();

        if ( strpos( $this->mStartTime, 'GMT' ) === false )
        {
            preg_match("/^(\d{2}):\d{2}:\d{2}([\+\-]{1}\d{2}):\d{2}\$/i", $this->mStartTime, $matches );
        }
        else
        {
            // Backwards compatibility (previous versions were storing start time
            // in a wrong format)
            preg_match("/^(\d{2}):\d{2}:\d{2}(GMT[\+\-]?\d{1,2})\$/i", $this->mStartTime, $matches );
        }

        return $matches;

    } // end of member function ParseTime

    function GetHour( ) 
    {
        $matches = $this->ParseTime();

        $hour = '';

        if ( count( $matches ) ) 
        {
            $hour = $matches[1];

            // First character
            $first_char = substr( $hour, 0, 1 );

            // Remove sign if it exists
            if ( $first_char == '+' or $first_char == '-' )
            {
                $hour = substr( $hour, 1 );
            }

            if ( substr( $hour, 0, 1 ) == '0' ) 
            {
                $hour = substr( $hour, 1, 2 );
            }
            else 
            {
                $hour = substr( $hour, 0, 2 );
            }
            
            if ( (int)$hour > 12 )
            {
                $hour = (string)((int)$hour - 12);
            }
        }

        return $hour; 

    } // end of member function GetHour

    function GetAmPm( ) 
    {
        $matches = $this->ParseTime();

        if ( count( $matches ) ) 
        {
            if ( $matches[1] >= 12 and $matches[1] < 24 ) 
            {
                return 'PM';
            }
            else 
            {
                return 'AM';
            }
        }

        return ''; 

    } // end of member function GetAmPm

    function GetTimezone( ) 
    {
        $matches = $this->ParseTime();

        if ( count( $matches ) ) 
        {
            // Backwards compatibility
            if ( substr( $matches[2], 0, 3 ) == 'GMT' )
            {
                return $matches[2];
            }

            if ( $matches[2] == '+00' )
            {
                return 'GMT0';
            }

            if ( substr( $matches[2], 1, 1 ) == '0' )
            {
                $sign = substr( $matches[2], 0, 1 );
                $hour = substr( $matches[2], 2, 1 );

                return 'GMT'.$sign.$hour;
            }

            return 'GMT'.$matches[2];
        }

        return ''; 

    } // end of member function GetTimezone

    function GetMaxDuration( ) 
    {
        return $this->mMaxDuration;

    } // end of member function GetMaxDuration

    function SetMaxDuration( $maxDuration ) 
    {
        $this->mMaxDuration = $maxDuration;

    } // end of member function SetMaxDuration

    function GetFrequency( ) 
    {
        return $this->mFrequency;

    } // end of member function GetFrequency

    function SetFrequency( $frequency ) 
    {
        $this->mFrequency = $frequency;

    } // end of member function SetFrequency

    function GetXml( $offset='', $indentWith='' ) 
    {
        $xml = '';

        $start_time   = $this->GetStartTime();
        $max_duration = $this->GetMaxDuration();
        $frequency    = $this->GetFrequency();

         if ( strlen( $start_time . $max_duration . $frequency ) )
        {
            $xml .= sprintf( '%s<indexingPreferences', $offset );

            if ( strlen( $start_time ) )
            {
                $xml .= sprintf( ' startTime="%s"', TpUtils::EscapeXmlSpecialChars( $start_time  ) );
            }

            if ( strlen( $max_duration ) )
            {
                $xml .= sprintf( ' maxDuration="%s"', TpUtils::EscapeXmlSpecialChars( $max_duration ) );
            }

            if ( strlen( $frequency ) )
            {
                $xml .= sprintf( ' frequency="%s"', TpUtils::EscapeXmlSpecialChars( $frequency ) );
            }

            $xml .= "/>\n";
        }

        return $xml;

    } // end of member function GetXml

} // end of TpIndexingPreferences
?>
