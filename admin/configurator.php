<?php
/**
 * $Id: configurator.php 2000 2010-03-03 18:22:03Z rdg $
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

//apd_set_pprof_trace();

header('Content-Type: text/html; charset="UTF-8"');
header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Cache-Control: no-cache, no-store, post-check=0, pre-check=0');
header('Pragma: no-cache');

// This one should be included first
require_once('../www/tapir_globals.php');

// Overwrite error reporting to E_ALL ("admin" is supposed to be run only by 
// owners of the service, so better help them identify possible issues)
error_reporting( E_ALL );

// Everything should be on the path now
require_once('TpConfigManager.php');
require_once('TpUtils.php');
require_once('TpDiagnostics.php');

# Magic quotes (automatic string escaping) is deprecated since version 5.3
if ( version_compare( phpversion(), '5.3', '<' ) > 0 )
{
  # No magic quotes
  set_magic_quotes_runtime(0);
}

if ( get_magic_quotes_gpc() ) {

  TpUtils::StripMagicSlashes( $_POST );
  TpUtils::StripMagicSlashes( $_GET );
  TpUtils::StripMagicSlashes( $_COOKIES );
  TpUtils::StripMagicSlashes( $_REQUEST );
}

# Clear php cache for file function calls
clearstatcache();

// Debugging
if ( ! defined( '_DEBUG' ) )
{
    define( '_DEBUG', false );
}

# Prepare global variable for debugging
TpUtils::InitializeDebugLog();

global $g_dlog;

$g_dlog->debug('Running configurator.php');

# Instantiate a manager for configuration
$config_manager = new TpConfigManager();

$config_manager->CheckEnvironment();

if ( TpDiagnostics::Count() ) {

    # Die showing errors in a simple list
    die( "<ul>\n<li>".implode("\n<li>", TpDiagnostics::GetMessages() )."\n</ul>" );
}

define( 'TP_MANDATORY_FIELD_FLAG', '(*) ' );

$config_manager->HandleEvents();

$g_dlog->debug('Finished configurator.php');

?>