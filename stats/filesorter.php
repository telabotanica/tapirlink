<?php
    function getFileMonth( $line )
    {
        $returnValue = false;
        $month = substr( $line, 5, 2 );
        //$year = substr( $line, 0, 4 );
        if( preg_match( "/^[0-1][0-9]/", $month ) == 1 )
        {
            $returnValue = $month;
        }
        return $returnValue;
    }

    function getFileYear( $line )
    {
        $returnValue = false;
        $year = substr( $line, 0, 4 );
        if( preg_match( "/^[0-9]{4}/", $year ) == 1 )
        {
            $returnValue = $year;
        }
        return $returnValue;
    }
    
    function compareFileNames( $file1, $file2 )
    {
        //BUGBUG not perfect. assume file 1 is good
        //could be more effecient
        $returnValue = $file1;
        $year1 = getFileYear( $file1 );
        $year2 = getFileYear( $file2 );
        
        $month1 = getFileMonth( $file1 );
        $month2 = getFileMonth( $file2 );
        
        if( $month1 != false && $year1 != false && $month2 != false && $year2 !=false )
        {
            $yearResult = strcmp( $year1, $year2 );
            $monthResult = strcmp( $month1, $month2 );
            
            if( $yearResult < 0 )
            {
                $returnValue = -1;
            }
            elseif( $yearResult > 0)
            {
                $returnValue = 1;
            }
            else
            {
                if( $monthResult <= 0 )
                {
                    $returnValue = -1;
                }
                else
                {
                    $returnValue = 1;
                }
            }
        }
        return $returnValue;
    }
    
    function getAvailableYears( $logFileNames )
    {
        $availableYears = array();
        foreach ($logFileNames as $lfn )
        {
            // check to see if the filenames is yyyy_mm.tbl format
            if ( preg_match( "/^[0-9]{4}_[0-1][0-9]\.tbl$/", $lfn ) == 1 )
            {
                //make unique. array_unique is error prone so just keep restting value.
                $key = substr( $lfn, 0, 4 );
                $availableYears[ $key ] = $key;
            }
        }
        return $availableYears;
    }
    
    function getAvailableMonthsForYear( $logFileNames, $year )
    {
        $availableMonths = array();
        $count = 0;
        $regexString="/^" . $year . "_[0-1][0-9]\.tbl$/";
        foreach ($logFileNames as $lfn )
        {
            // check to see if the filenames is yyyy_mm.tbl format
            if ( preg_match( $regexString, $lfn ) == 1 )
            {
                $availableMonths[ $count ] = $lfn;
                $count++;
            }
        }
        array_unique( $availableMonths );
        return $availableMonths;
    }

    
    function getLogArray( &$logFiles, &$cachedMonthsFiles )
    {
        $i=0;
        if ( $logDir = opendir( TP_STATISTICS_DIR ) )
        {					
            // reading the log dir for all files
            while ( false !== ($file = readdir( $logDir ) ) )
            { 
                // CHECK NUMBER ONE
                if ($file != "." && $file != ".." )
                {
                    //if the file is not . or .. and is 11 chars long
                    // stick the filename in the array
                    $logNames[ $i ] =  $file;
                    $i++; 
                } 
            }
            closedir( $logDir ); 
        }
        // END OF CHECK ONE
        
        // CHECK NUMBER TWO
        $statsFiles = array();
        $j = 0;
        foreach ($logNames as $b )
        {
            // check to see if the filenames is yyyy_mm.tbl format
            if ( preg_match( "/^[0-9]{4}_[0-1][0-9]\.tbl$/", $b ) == 1 )
            {
                $statsFiles[ $j ] = $b;
                $j++;
            }
        }
        //END OF CHECK TWO
        //BUGBUG uneeded copy
        $logFiles = $statsFiles;
        //get the suckers into ascending order
        usort( $logFiles, "compareFileNames" );

        $statsFiles = array();
        $j = 0;
        foreach ($logNames as $b )
        {
            // check to see if the filenames is yyyy_mm.tbl format
            if ( preg_match( "/^[0-9]{4}_[0-1][0-9]\.html$/", $b ) == 1 )
            {
                $statsFiles[ $j ] = $b;
                $j++;
            }
        }
        //END OF CHECK TWO
        $cachedMonthsFiles = $statsFiles;
        //get the suckers into ascending order
        usort( $cachedMonthsFiles, "compareFileNames" );
    }


?>
