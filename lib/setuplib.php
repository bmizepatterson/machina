<?php
/**
 * MACHINA setup functions that must be loaded before everything else
 */
defined('MACHINA_INTERNAL') || die;

class machina_exception extends Exception {

    /**
     * @var string A short name for the error
     */
    public $errorcode;

    /**
     * @var strong Error description
     */
    public $description;

    /**
     * @var string Optional information to aid the debugging process
     */
    public $debuginfo;

    /**
     * Constructor
     * @param string $errorcode A short name for the error
     * @param string $description A description of the error
     * @param string $debuginfo optional debugging information
     */
    function __construct($errorcode, $description=null, $debuginfo=null) {

        $this->errorcode   = $errorcode;
        $this->description = $description;
        $this->debuginfo   = is_null($debuginfo) ? null : (string) $debuginfo;
        $message = $errorcode;
        
        if (isset($description)) {
        	$message = "$message: $description";
        }
        if (isset($debuginfo)) {
        	$message = "$message ($debuginfo)";
        }
        parent::__construct($message, 0);
    }
}

/**
 * Exception indicating programming error
 */
class coding_exception extends machina_exception {
    /**
     * Constructor
     * @param string $debuginfo detailed information how to fix problem
     */
    function __construct($debuginfo) {
        $description = 'Whoops. MACHINA seems to have tripped over herself. Please contact a programmer for help.';
        parent::__construct('Coding Error', $description, $debuginfo);
    }
}

/**
 * Exception indicating database read error
 */
class database_read_exception extends coding_exception {
    /**
     * Constructor
     * @param string $debuginfo detailed information how to fix problem
     */
    function __construct($debuginfo = null) {
        if (isset($debuginfo)) {
            $debuginfo = 'Error reading from database: ' . $debuginfo;
        } else {
            $debuginfo = 'Error reading from database';
        }
        parent::__construct($debuginfo);
    }
}

/**
 * Exception indicating database write error
 */
class database_create_exception extends coding_exception {
    /**
     * Constructor
     * @param string $debuginfo detailed information how to fix problem
     */
    function __construct($debuginfo = null) {
        if (isset($debuginfo)) {
            $debuginfo = 'Error inserting into database: ' . $debuginfo;
        } else {
            $debuginfo = 'Error inserting into database';
        }
        parent::__construct($debuginfo);
    }
}

/**
 * Exception indicating database update error
 */
class database_update_exception extends coding_exception {
    /**
     * Constructor
     * @param string $debuginfo detailed information how to fix problem
     */
    function __construct($debuginfo = null) {
        if (isset($debuginfo)) {
            $debuginfo = 'Error updating database: ' . $debuginfo;
        } else {
            $debuginfo = 'Error updating database';
        }
        parent::__construct($debuginfo);
    }
}

/**
 * Exception indicating database update error
 */
class database_delete_exception extends coding_exception {
    /**
     * Constructor
     * @param string $debuginfo detailed information how to fix problem
     */
    function __construct($debuginfo = null) {
        if (isset($debuginfo)) {
            $debuginfo = 'Error deleting from database: ' . $debuginfo;
        } else {
            $debuginfo = 'Error deleting from database';
        }
        parent::__construct($debuginfo);
    }
}

/**
 * Exception indicating a badly formed URL parameter
 */
class invalid_parameter_exception extends machina_exception {
    /**
     * Constructor
     * @param string $debuginfo detailed information how to fix problem
     */
    function __construct($debuginfo) {
        $description = 'Uh-oh. The URL you requested has given MACHINA some indigestion. Please contact a programmer for help.';
        parent::__construct('Invalid Parameter', $description, $debuginfo);
    }
}

/**
 * An exception that indicates incorrect permissions in $CFG->dataroot
 */
class invalid_dataroot_permissions extends machina_exception {
    /**
     * Constructor
     * @param string $debuginfo optional more detailed information
     */
    function __construct($debuginfo = NULL) {
        parent::__construct('Invalid Dataroot Permissions', 'Invalid permissions detected when trying to create a directory. Turn debugging on for further details.', $debuginfo);
    }
}

/**
 * Course/activity access exception.
 *
 * This exception is thrown from require_login()
 */
class require_login_exception extends machina_exception {
    /**
     * Constructor
     * @param string $debuginfo Information to aid the debugging process
     */
    function __construct($debuginfo) {
        parent::__construct('Login Required', 'You must log in to view this page.', $debuginfo);
    }
}

/**
 * Session timeout exception.
 *
 * This exception is thrown from require_login()
 */
class require_login_session_timeout_exception extends machina_exception {
    /**
     * Constructor
     */
    public function __construct() {
        moodle_exception::__construct('Session Timeout', 'Your session has timed out. Please log in again.', null);
    }
}

/**
 * Default exception handler.
 *
 * @param Exception $ex
 * @return void -does not return. Terminates execution!
 */
function default_exception_handler($ex) {
    global $CFG, $DB, $OUTPUT, $PAGE;

    $info = get_exception_info($ex);

    if (debugging('', DEBUG_MINIMAL)) {
        $logerrmsg = "Default exception handler: ".$info->errorcode.' Debug: '.$info->description."\n".format_backtrace($info->backtrace, true);
        error_log($logerrmsg);
    }
    
    try {
        echo $OUTPUT->fatal_error($info->errorcode, $info->description, $info->backtrace, $info->debuginfo);
    } catch (Throwable $e) {
        // Engine errors in PHP7 throw exceptions of type Throwable (this "catch" will be ignored in PHP5).
        $out_ex = $e;
    }

    if (isset($out_ex)) {
        // default exception handler MUST not throw any exceptions!!
        // the problem here is we do not know if page already started or not
        // so we just print at least something instead of "Exception thrown without a stack frame in Unknown on line 0":-(
        echo early_renderer::early_error_content($info->errorcode, $info->description, $info->backtrace, $info->debuginfo);
        $outinfo = get_exception_info($out_ex);
        echo early_renderer::early_error_content($outinfo->errorcode, $outinfo->description, $outinfo->backtrace, $outinfo->debuginfo);
    }
    exit; // General error code
}

/**
 * Default error handler, prevents some white screens.
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 * @param array $errcontext
 * @return bool false means use default error handler
 */
function default_error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
    if ($errno == 4096) {
        //fatal catchable error
        throw new machina_exception('PHP catchable fatal error', $errstr);
    }
    return false;
}


/**
 * Standard Debugging Function
 *
 * Returns true if the current site debugging settings are equal or above specified level.
 * If passed a parameter it will emit a debugging notice similar to trigger_error(). The
 * routing of notices is controlled by $CFG->debugdisplay
 * eg use like this:
 *
 * 1)  debugging('a normal debug notice');
 * 2)  debugging('something really picky', DEBUG_ALL);
 * 3)  debugging('annoying debug message only for developers', DEBUG_DEVELOPER);
 * 4)  if (debugging()) { perform extra debugging operations (do not use print or echo) }
 *
 * In code blocks controlled by debugging() (such as example 4)
 * any output should be routed via debugging() itself. Using echo or print will break XHTML
 * JS and HTTP headers.
 *
 * @param string $message a message to print
 * @param int $level the level at which this debugging statement should show
 * @param bool $backtrace Whether to include the backtrace
 * @param bool $print Whether to print the message or return it (Only applys if CFG->debugdisplay)
 * @return bool
 */
function debugging($message = '', $level = DEBUG_NORMAL, $backtrace = true, $print = true) {
    global $CFG;

    if (empty($CFG->debug) || ($CFG->debug != -1 and $CFG->debug < $level)) {
        return false;
    }

    if (!isset($CFG->debugdisplay)) {
        $CFG->debugdisplay = ini_get_bool('display_errors');
    }
    
    $from = '';
    if ($message) {
        if ($backtrace) {
            $backtrace = debug_backtrace();
            $from = '<div class="backtrace"><h4>Backtrace</h4>' . format_backtrace($backtrace) . '</div>';
        }

        if ($CFG->debugdisplay) {
            if (!defined('DEBUGGING_PRINTED')) {
                define('DEBUGGING_PRINTED', 1); // Indicates we have printed something.
            }
            $output = '<div class="debuggingmessage w3-border" data-rel="debugging">' .
                      '<div class="w3-panel">' . $message . '</div>' . $from . '</div>';
            if ($print) {
                echo $output;
            } else {
                return $output;
            }
        } else {
            trigger_error($message . $from, E_USER_NOTICE);
        }
    }
    return true;
}

/**
 * Formats a backtrace ready for output.
 *
 * @param array $callers backtrace array, as returned by debug_backtrace().
 * @param boolean $plaintext if false, generates HTML, if true generates plain text.
 * @return string formatted backtrace, ready for output.
 */
function format_backtrace($callers, $plaintext = false) {
    // do not use $CFG->dirroot because it might not be available in destructors
    $dirroot = dirname(dirname(__FILE__));

    if (empty($callers)) {
        return '';
    }

    $from = $plaintext ? '' : '<ul style="text-align: left" data-rel="backtrace">';
    foreach ($callers as $caller) {
        if (!isset($caller['line'])) {
            $caller['line'] = '?'; // probably call_user_func()
        }
        if (!isset($caller['file'])) {
            $caller['file'] = 'unknownfile'; // probably call_user_func()
        }
        $from .= $plaintext ? '* ' : '<li>';
        $from .= 'line ' . $caller['line'] . ' of ' . str_replace($dirroot, '', $caller['file']);
        if (isset($caller['function'])) {
            $from .= ': call to ';
            if (isset($caller['class'])) {
                $from .= $caller['class'] . $caller['type'];
            }
            $from .= $caller['function'] . '()';
        } else if (isset($caller['exception'])) {
            $from .= ': '.$caller['exception'].' thrown';
        }
        $from .= $plaintext ? "\n" : '</li>';
    }
    $from .= $plaintext ? '' : '</ul>';

    return $from;
}

/**
 * Returns detailed information about specified exception.
 * @param exception $ex
 * @return object
 */
function get_exception_info($ex) {

    if ($ex instanceof machina_exception) {
        $errorcode = $ex->errorcode;
        $description = $ex->description;
        $debuginfo = $ex->debuginfo;
    } else {
        $errorcode = 'Well, good grief.';
        $description = 'Something went wrong, but who knows what. Best contact the MACHINATOR for help. Maybe next time MACHINA will be more self-aware...';
        $debuginfo = $ex->getMessage();
    }

    $backtrace = $ex->getTrace();
    $place = array('file'=>$ex->getFile(), 'line'=>$ex->getLine(), 'exception'=>get_class($ex));
    array_unshift($backtrace, $place);
    
    $info = new stdClass();
    $info->errorcode   = $errorcode;
    $info->backtrace   = $backtrace;
    $info->description = $description;
    $info->debuginfo   = $debuginfo;

    return $info;
}
 
function init_performance_info() {
    global $PERF;
    $PERF = new stdClass();
    $PERF->logwrites = 0;
    $PERF->starttime = microtime();
    $PERF->startmemory = memory_get_usage();
    $PERF->startposixtimes = posix_times();
}


/**
 * Converts numbers like 10M into bytes.
 *
 * @param string $size The size to be converted
 * @return int
 */
function get_real_size($size = 0) {
    if (!$size) {
        return 0;
    }

    static $binaryprefixes = array(
        'K' => 1024,
        'k' => 1024,
        'M' => 1048576,
        'm' => 1048576,
        'G' => 1073741824,
        'g' => 1073741824,
        'T' => 1099511627776,
        't' => 1099511627776,
    );

    if (preg_match('/^([0-9]+)([KMGT])/i', $size, $matches)) {
        return $matches[1] * $binaryprefixes[$matches[2]];
    }

    return (int) $size;
}

/**
 * Create a directory and make sure it is writable.
 *
 * @private
 * @param string $dir  the full path of the directory to be created
 * @param bool $exceptiononerror throw exception if error encountered
 * @return string|false Returns full path to directory if successful, false if not; may throw exception
 */
function make_writable_directory($dir, $exceptiononerror = true) {
    global $CFG;

    if (file_exists($dir) and !is_dir($dir)) {
        if ($exceptiononerror) {
            throw new coding_exception($dir.' directory can not be created, file with the same name already exists.');
        } else {
            return false;
        }
    }

    umask($CFG->umaskpermissions);

    if (!file_exists($dir)) {
        if (!@mkdir($dir, $CFG->directorypermissions, true)) {
            clearstatcache();
            // There might be a race condition when creating directory.
            if (!is_dir($dir)) {
                if ($exceptiononerror) {
                    throw new invalid_dataroot_permissions($dir.' can not be created, check permissions.');
                } else {
                    debugging('Can not create directory: '.$dir, DEBUG_DEVELOPER);
                    return false;
                }
            }
        }
    }

    if (!is_writable($dir)) {
        if ($exceptiononerror) {
            throw new invalid_dataroot_permissions($dir.' is not writable, check permissions.');
        } else {
            return false;
        }
    }

    return $dir;
}

/**
 * Early renderer
 * Used when errors occur before the OUTPUT object is initialized
 */
class early_renderer {
    /**
     * Handles re-entrancy. Without this, errors or debugging output that occur
     * during the initialisation of $OUTPUT, cause infinite recursion.
     * @var boolean
     */
    protected $initialising = false;

    /**
     * Have we started output yet? Nope.
     */
    public function has_started() {
        return false;
    }
    
    public function __call($method, $arguments) {
        global $OUTPUT, $PAGE;

        // If lib/outputlib.php has been loaded, call it.
        if (!empty($PAGE)) {
            $OUTPUT = new base_renderer($PAGE);
            return call_user_func_array(array($OUTPUT, $method), $arguments);
        }

        $this->initialising = true;

        // Too soon to initialise $OUTPUT, provide a couple of key methods.
        if ($method == 'fatal_error') {
            return call_user_func_array(array('early_renderer', 'early_error'), $arguments);
        }

        throw new coding_exception('Attempt to start output before enough information is known to initialise it.');
    }
    
    /**
     * Returns nicely formatted error message in a div box.
     * @static
     * @param string $errorcode error title
     * @param string $description error message
     * @param array $backtrace
     * @param string $debuginfo
     * @return string
     */
    public static function early_error_content($errorcode, $description, $backtrace, $debuginfo = null) {
        global $CFG;

        $content = '<div style="margin-top: 100px; padding: 16px; background-color:black; font-family: Verdana, sans-serif; color: white;"><h3>' . $errorcode . '</h3>' .
                   '<p>' . $description . '</p>';
        // Check whether debug is set.
        $debug = (!empty($CFG->debug) && $CFG->debug >= DEBUG_DEVELOPER);
        // Also check if we have it set in the config.php file. This occurs if the method to read the config table from the
        // database fails. Reading from the config table is the first database interaction we have.
        $debug = $debug || (!empty($CFG->config_php_settings['debug'])  && $CFG->config_php_settings['debug'] >= DEBUG_DEVELOPER );
        if ($debug) {
            if (!empty($debuginfo)) {
                $debuginfo = s($debuginfo); // removes all nasty JS
                $debuginfo = str_replace("\n", '<br />', $debuginfo); // keep newlines
                $content .= '<p><strong>Debug info:</strong> ' . $debuginfo . '</p>';
            }
            if (!empty($backtrace)) {
                $content .= '<p><strong>Stack trace:</strong> ' . format_backtrace($backtrace, false) . '</p>';
            }
        }
        
        $content . '</div>';
        return $content;
    }
    
    /**
     * This function should only be called by this class, or from exception handlers
     * @static
     * @param string $errorcode error title
     * @param string $description error message
     * @param array $backtrace
     * @param string $debuginfo extra information for developers
     * @return string
     */
    public static function early_error($errorcode, $description, $backtrace, $debuginfo = null) {
        global $CFG;

        // In the name of protocol correctness, monitoring and performance
        // profiling, set the appropriate error headers for machine consumption.
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        @header($protocol . ' 503 Service Unavailable');

        // better disable any caching
        @header('Content-Type: text/html; charset=utf-8');
        @header('X-UA-Compatible: IE=edge');
        @header('Cache-Control: no-store, no-cache, must-revalidate');
        @header('Cache-Control: post-check=0, pre-check=0', false);
        @header('Pragma: no-cache');
        @header('Expires: Mon, 20 Aug 1969 09:23:00 GMT');
        @header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

        $content = self::early_error_content($errorcode, $description, $backtrace, $debuginfo);
        return self::plain_page('Error', $content);
    }
    
    /**
     * Output basic html page.
     * @static
     * @param string $title page title
     * @param string $content page content
     * @param string $meta meta tag
     * @return string html page
     */
    public static function plain_page($title, $content, $meta = '') {
        return '<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
'.$meta.'
<title>' . $title . '</title>
</head><body style="margin:0;">' . $content . '</body></html>';
    }
    
    /**
     * Page should redirect message.
     * @static
     * @param string $encodedurl redirect url
     * @return string
     */
    public static function plain_redirect_message($encodedurl) {
        $message = '<div style="margin-top: 3em; text-align:center;">This page will automatically redirect.<br /><a href="'.
                $encodedurl .'">Continue</a></div>';
        return self::plain_page('Redirect', $message);
    }
    
    /**
     * Early redirection page, used before full init of $PAGE global
     * @static
     * @param string $encodedurl redirect url
     * @param string $message redirect message
     * @param int $delay time in seconds
     * @return string redirect page
     */
    public static function early_redirect_message($encodedurl, $message, $delay) {
        $meta = '<meta http-equiv="refresh" content="'. $delay .'; url='. $encodedurl .'" />';
        $content = self::early_error_content($message, null, null, null);
        $content .= self::plain_redirect_message($encodedurl);

        return self::plain_page('Redirect', $content, $meta);
    }
}

/**
 * Initialises $FULLME and friends. Private function. Should only be called from
 * setup.php.
 */
function initialise_fullme() {
    global $CFG, $FULLME, $ME, $SCRIPT, $FULLSCRIPT;
    // Detect common config error.
    if (substr($CFG->wwwroot, -1) == '/') {
        throw new coding_exception('Detected an incorrect $CFG->wwwroot in setup.php. It must not contain a trailing slash.');
    }
    $rurl = setup_get_remote_url();
    $wwwroot = parse_url($CFG->wwwroot.'/');
    if (empty($rurl['host'])) {
        // missing host in request header, probably not a real browser, let's ignore them
    } else if (!empty($CFG->reverseproxy)) {
        // $CFG->reverseproxy specifies if reverse proxy server used
        // Used in load balancing scenarios.
        // Do not abuse this to try to solve lan/wan access problems!!!!!
    } else {
        if (($rurl['host'] !== $wwwroot['host']) or
                (!empty($wwwroot['port']) and $rurl['port'] != $wwwroot['port']) or
                (strpos($rurl['path'], $wwwroot['path']) !== 0)) {
            // Explain the problem and redirect them to the right URL
            if (!defined('NO_MOODLE_COOKIES')) {
                define('NO_MOODLE_COOKIES', true);
            }
            // The login/token.php script should call the correct url/port.
            if (defined('REQUIRE_CORRECT_ACCESS') && REQUIRE_CORRECT_ACCESS) {
                $wwwrootport = empty($wwwroot['port'])?'':$wwwroot['port'];
                $calledurl = $rurl['host'];
                if (!empty($rurl['port'])) {
                    $calledurl .=  ':'. $rurl['port'];
                }
                $correcturl = $wwwroot['host'];
                if (!empty($wwwrootport)) {
                    $correcturl .=  ':'. $wwwrootport;
                }
                throw new machina_exception('Invalid URL or port',
                    'You called ' . $calledurl .'; you should have called ' . $correcturl .'.');
            }
            redirect($CFG->wwwroot, 'Incorrect access detected. This server may be accessed only through the "' . $CFG->wwwroot . '" address, sorry.', 3);
        }
    }
    // Check that URL is under $CFG->wwwroot.
    if (strpos($rurl['path'], $wwwroot['path']) === 0) {
        $SCRIPT = substr($rurl['path'], strlen($wwwroot['path'])-1);
    } else {
        // Probably some weird external script
        $SCRIPT = $FULLSCRIPT = $FULLME = $ME = null;
        return;
    }
    // $CFG->sslproxy specifies if external SSL appliance is used
    // (That is, the Machina server uses http, with an external box translating everything to https).
    if (empty($CFG->sslproxy)) {
        if ($rurl['scheme'] === 'http' and $wwwroot['scheme'] === 'https') {
            if (defined('REQUIRE_CORRECT_ACCESS') && REQUIRE_CORRECT_ACCESS) {
                throw new machina_exception('HHTPS Connection Required', "For security reasons, only https connections are allowed.");
            } else {
                redirect($CFG->wwwroot, 'Incorrect access detected. This server may be accessed only through the "' . $CFG->wwwroot . '" address.', 3);
            }
        }
    } else {
        if ($wwwroot['scheme'] !== 'https') {
            throw new machina_exception('SSL Proxy Error', 'You must use https address in wwwroot when ssl proxy is enabled.');
        }
        $rurl['scheme'] = 'https'; // make moodle believe it runs on https, squid or something else it doing it
        $_SERVER['HTTPS'] = 'on'; // Override $_SERVER to help external libraries with their HTTPS detection.
        $_SERVER['SERVER_PORT'] = 443; // Assume default ssl port for the proxy.
    }
    if (!empty($CFG->reverseproxy) && $rurl['host'] === $wwwroot['host']) {
        throw new machina_exception('Direct Access Forbidden', 'With reverse proxy enabled, the server cannot be accessed directly.');
    }
    $hostandport = $rurl['scheme'] . '://' . $wwwroot['host'];
    if (!empty($wwwroot['port'])) {
        $hostandport .= ':'.$wwwroot['port'];
    }
    $FULLSCRIPT = $hostandport . $rurl['path'];
    $FULLME = $hostandport . $rurl['fullpath'];
    $ME = $rurl['fullpath'];
}

/**
 * Get the URL that PHP/the web server thinks it is serving.
 * @return array in the same format that parse_url returns, with the addition of
 *      a 'fullpath' element, which includes any slasharguments path.
 */
function setup_get_remote_url() {
    $rurl = array();
    if (isset($_SERVER['HTTP_HOST'])) {
        list($rurl['host']) = explode(':', $_SERVER['HTTP_HOST']);
    } else {
        $rurl['host'] = null;
    }
    $rurl['port'] = $_SERVER['SERVER_PORT'];
    $rurl['path'] = $_SERVER['SCRIPT_NAME']; // Script path without slash arguments
    $rurl['scheme'] = (empty($_SERVER['HTTPS']) or $_SERVER['HTTPS'] === 'off' or $_SERVER['HTTPS'] === 'Off' or $_SERVER['HTTPS'] === 'OFF') ? 'http' : 'https';
    if (stripos($_SERVER['SERVER_SOFTWARE'], 'apache') !== false) {
        //Apache server
        $rurl['fullpath'] = $_SERVER['REQUEST_URI'];
        // Fixing a known issue with:
        // - Apache versions lesser than 2.4.11
        // - PHP deployed in Apache as PHP-FPM via mod_proxy_fcgi
        // - PHP versions lesser than 5.6.3 and 5.5.18.
        if (isset($_SERVER['PATH_INFO']) && (php_sapi_name() === 'fpm-fcgi') && isset($_SERVER['SCRIPT_NAME'])) {
            $pathinfodec = rawurldecode($_SERVER['PATH_INFO']);
            $lenneedle = strlen($pathinfodec);
            // Checks whether SCRIPT_NAME ends with PATH_INFO, URL-decoded.
            if (substr($_SERVER['SCRIPT_NAME'], -$lenneedle) === $pathinfodec) {
                // This is the "Apache 2.4.10- running PHP-FPM via mod_proxy_fcgi" fingerprint,
                // at least on CentOS 7 (Apache/2.4.6 PHP/5.4.16) and Ubuntu 14.04 (Apache/2.4.7 PHP/5.5.9)
                // => SCRIPT_NAME contains 'slash arguments' data too, which is wrongly exposed via PATH_INFO as URL-encoded.
                // Fix both $_SERVER['PATH_INFO'] and $_SERVER['SCRIPT_NAME'].
                $lenhaystack = strlen($_SERVER['SCRIPT_NAME']);
                $pos = $lenhaystack - $lenneedle;
                // Here $pos is greater than 0 but let's double check it.
                if ($pos > 0) {
                    $_SERVER['PATH_INFO'] = $pathinfodec;
                    $_SERVER['SCRIPT_NAME'] = substr($_SERVER['SCRIPT_NAME'], 0, $pos);
                }
            }
        }
    } else if (stripos($_SERVER['SERVER_SOFTWARE'], 'iis') !== false) {
        //IIS - needs a lot of tweaking to make it work
        $rurl['fullpath'] = $_SERVER['SCRIPT_NAME'];
        // NOTE: we should ignore PATH_INFO because it is incorrectly encoded using 8bit filesystem legacy encoding in IIS.
        //       Since 2.0, we rely on IIS rewrite extensions like Helicon ISAPI_rewrite
        //         example rule: RewriteRule ^([^\?]+?\.php)(\/.+)$ $1\?file=$2 [QSA]
        //       OR
        //       we rely on a proper IIS 6.0+ configuration: the 'FastCGIUtf8ServerVariables' registry key.
        if (isset($_SERVER['PATH_INFO']) and $_SERVER['PATH_INFO'] !== '') {
            // Check that PATH_INFO works == must not contain the script name.
            if (strpos($_SERVER['PATH_INFO'], $_SERVER['SCRIPT_NAME']) === false) {
                $rurl['fullpath'] .= clean_param(urldecode($_SERVER['PATH_INFO']), PARAM_PATH);
            }
        }
        if (isset($_SERVER['QUERY_STRING']) and $_SERVER['QUERY_STRING'] !== '') {
            $rurl['fullpath'] .= '?'.$_SERVER['QUERY_STRING'];
        }
        $_SERVER['REQUEST_URI'] = $rurl['fullpath']; // extra IIS compatibility
/* NOTE: following servers are not fully tested! */
    } else if (stripos($_SERVER['SERVER_SOFTWARE'], 'lighttpd') !== false) {
        //lighttpd - not officially supported
        $rurl['fullpath'] = $_SERVER['REQUEST_URI']; // TODO: verify this is always properly encoded
    } else if (stripos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false) {
        //nginx - not officially supported
        if (!isset($_SERVER['SCRIPT_NAME'])) {
            die('Invalid server configuration detected, please try to add "fastcgi_param SCRIPT_NAME $fastcgi_script_name;" to the nginx server configuration.');
        }
        $rurl['fullpath'] = $_SERVER['REQUEST_URI']; // TODO: verify this is always properly encoded
     } else if (stripos($_SERVER['SERVER_SOFTWARE'], 'cherokee') !== false) {
         //cherokee - not officially supported
         $rurl['fullpath'] = $_SERVER['REQUEST_URI']; // TODO: verify this is always properly encoded
     } else if (stripos($_SERVER['SERVER_SOFTWARE'], 'zeus') !== false) {
         //zeus - not officially supported
         $rurl['fullpath'] = $_SERVER['REQUEST_URI']; // TODO: verify this is always properly encoded
    } else if (stripos($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed') !== false) {
        //LiteSpeed - not officially supported
        $rurl['fullpath'] = $_SERVER['REQUEST_URI']; // TODO: verify this is always properly encoded
    } else if ($_SERVER['SERVER_SOFTWARE'] === 'HTTPD') {
        //obscure name found on some servers - this is definitely not supported
        $rurl['fullpath'] = $_SERVER['REQUEST_URI']; // TODO: verify this is always properly encoded
    } else if (strpos($_SERVER['SERVER_SOFTWARE'], 'PHP') === 0) {
        // built-in PHP Development Server
        $rurl['fullpath'] = $_SERVER['REQUEST_URI'];
    } else {
        throw new coding_exception('Web server software (' . $_SERVER['SERVER_SOFTWARE'] . ') is not supported.');
    }
    // sanitize the url a bit more, the encoding style may be different in vars above
    $rurl['fullpath'] = str_replace('"', '%22', $rurl['fullpath']);
    $rurl['fullpath'] = str_replace('\'', '%27', $rurl['fullpath']);
    return $rurl;
}