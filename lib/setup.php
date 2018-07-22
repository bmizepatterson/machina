<?php
/**
 * Various Machina set-up operations
 */
define('MACHINA_INTERNAL', true);

unset($CFG);
global $CFG;
$CFG = new stdClass();
$CFG->dbhost     = 'localhost';
$CFG->dbname     = 'machina';
$CFG->dbuser     = 'machina_user';
$CFG->dbpass     = 'vivatlingualatina';
$CFG->wwwroot    = 'https://machina-bmizepatterson.c9users.io';
$CFG->dirroot    = dirname(dirname(__FILE__));

// File permissions on created directories in the $CFG->dataroot
$CFG->dataroot  = '/home/ubuntu/machinadata';
$CFG->directorypermissions = 02777;
if (!isset($CFG->filepermissions)) {
    $CFG->filepermissions = ($CFG->directorypermissions & 0666); // strip execute flags
}
if (!isset($CFG->umaskpermissions)) {
    $CFG->umaskpermissions = (($CFG->directorypermissions & 0777) ^ 0777);
}
umask($CFG->umaskpermissions);
if (!is_writable($CFG->dataroot)) {
    if (isset($_SERVER['REMOTE_ADDR'])) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
    }
    echo('Fatal error: $CFG->dataroot is not writable, admin has to fix directory permissions! Exiting.'."\n");
    exit(1);
}

// Set httpswwwroot default value (this variable will replace $CFG->wwwroot
// inside some URLs used in pages that call $PAGE->https_required().
$CFG->httpswwwroot = $CFG->wwwroot;
$CFG->loginhttps = true;
$CFG->sslproxy = true; // Cloud9 appears to tunnel an http connection through their own TLS cert, and forces the internal connection to use HTTP
$CFG->libdir = $CFG->dirroot .'/lib';
$CFG->tempdir = "$CFG->dataroot/temp";
$CFG->cachedir = "$CFG->dataroot/cache";
$CFG->localcachedir = "$CFG->dataroot/localcache";

// sometimes default PHP settings are borked on shared hosting servers, I wonder why they have to do that??
ini_set('precision', 14);
ini_set('serialize_precision', 17); // Make float serialization consistent on all systems.

// Store settings from config.php in array in $CFG - we can use it later to detect problems and overrides.
if (!isset($CFG->config_php_settings)) {
    $CFG->config_php_settings = (array)$CFG;
}

// Debug settings
define('DEBUG_NONE', 0);                                            /** No warnings and errors at all */
define('DEBUG_MINIMAL', E_ERROR | E_PARSE);                         /** Fatal errors only */
define('DEBUG_NORMAL', E_ERROR | E_PARSE | E_WARNING | E_NOTICE);   /** Errors, warnings and notices */
define('DEBUG_ALL', E_ALL & ~E_STRICT);                             /** All problems except strict PHP warnings */
define('DEBUG_DEVELOPER', E_ALL | E_STRICT);                        /** DEBUG_ALL with all debug messages and strict warnings */
$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = true;
$CFG->debugdeveloper = (($CFG->debug & DEBUG_DEVELOPER) === DEBUG_DEVELOPER);
$CFG->debugvalidators = false;

// Macron settings
define('NO_MACRONS', 0);
define('USE_ENTITIES', 1);
define('USE_DIERESES', 2);
define('USE_CAPS', 3);
$CFG->macrons = USE_ENTITIES;

// Define globals
global $DB;
global $OUTPUT;
global $PAGE;
global $SESSION;
global $USER;
/**
 * Full script path including all params, slash arguments, scheme and host.
 *
 * Note: Do NOT use for getting of current page URL or detection of https,
 * instead use $PAGE->url or is_https().
 *
 * @global string $FULLME
 * @name $FULLME
 */
global $FULLME;
/**
 * Script path including query string and slash arguments without host.
 * @global string $ME
 * @name $ME
 */
global $ME;
/**
 * $FULLME without slasharguments and query string.
 * @global string $FULLSCRIPT
 * @name $FULLSCRIPT
 */
global $FULLSCRIPT;
/**
 * Relative script path '/course/view.php'
 * @global string $SCRIPT
 * @name $SCRIPT
 */
global $SCRIPT;

require_once($CFG->libdir.'/setuplib.php');

$OUTPUT = new early_renderer();

// set handler for uncaught exceptions
set_exception_handler('default_exception_handler');
set_error_handler('default_error_handler', E_ALL | E_STRICT);

// Initialize performance info
init_performance_info();

// Load libraries
require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->libdir.'/dblib.php');
require_once($CFG->libdir.'/lingualib.php');
require_once($CFG->libdir.'/machinalib.php');
require_once($CFG->libdir.'/macronlib.php');
require_once($CFG->libdir.'/morphologylib.php');
require_once($CFG->libdir.'/outputlib.php');
require_once($CFG->libdir.'/pagelib.php');
require_once($CFG->libdir.'/weblib.php');
require_once($CFG->libdir.'/classes/shutdown_manager.php');
require_once($CFG->libdir.'/classes/session/manager.php');

// Initialize database connection and the global $PAGE variable
$DB = new machina_database($CFG->dbhost, $CFG->dbname, $CFG->dbuser, $CFG->dbpass);
$PAGE = new machina_page();

// enable circular reference collector
gc_enable();

// initialise ME's - this must be done BEFORE starting of session!
initialise_fullme();

shutdown_manager::initialize();

// Prepare session
define('NO_MACHINA_COOKIES', false);    // Turn on cookies
$CFG->sessiontimeout = 7200;            // Timeout after 2 hours
\session\manager::start();