<?php
/**
 * Shutdown management class 
 * Allows other parts of MACHINA to register additional shutdown functions
 */
 
class shutdown_manager {
    /** @var array list of custom callbacks */
    protected static $callbacks = array();
    /** @var bool is this manager already registered? */
    protected static $registered = false;

    /**
     * Register self as main shutdown handler.
     *
     * @private to be called from lib/setup.php only!
     */
    public static function initialize() {
        if (self::$registered) {
            debugging('Shutdown manager is already initialised!');
        }
        self::$registered = true;
        register_shutdown_function(array('shutdown_manager', 'shutdown_handler'));
    }

    /**
     * Register custom shutdown function.
     *
     * @param callable $callback
     * @param array $params
     */
    public static function register_function($callback, array $params = null) {
        self::$callbacks[] = array($callback, $params);
    }

    /**
     * @private - do NOT call directly.
     */
    public static function shutdown_handler() {
        global $DB;

        // Custom stuff first.
        foreach (self::$callbacks as $data) {
            list($callback, $params) = $data;
            try {
                if (!is_callable($callback)) {
                    error_log('Invalid custom shutdown function detected: '.var_export($callback, true));
                    continue;
                }
                if ($params === null) {
                    call_user_func($callback);
                } else {
                    call_user_func_array($callback, $params);
                }
            } catch (Exception $e) {
                error_log('Exception ignored in shutdown function '.var_export($callback, true).':'.$e->getMessage());
            } catch (Throwable $e) {
                // Engine errors in PHP7 throw exceptions of type Throwable (this "catch" will be ignored in PHP5).
                error_log('Exception ignored in shutdown function '.var_export($callback, true).':'.$e->getMessage());
            }
        }

        // Close sessions
        \session\manager::write_close();

        // Other cleanup.
        self::request_shutdown();

        // NOTE: do not dispose $DB here, they might be used from legacy shutdown functions.
    }

    /**
     * Standard shutdown sequence.
     */
    protected static function request_shutdown() {
        global $CFG;

        // Help apache server if possible.
        $apachereleasemem = false;
        if (function_exists('apache_child_terminate') && function_exists('memory_get_usage') && ini_get_bool('child_terminate')) {
            $limit = (empty($CFG->apachemaxmem) ? 64*1024*1024 : $CFG->apachemaxmem); // 64MB default.
            if (memory_get_usage() > get_real_size($limit)) {
                $apachereleasemem = $limit;
                @apache_child_terminate();
            }
        }
    }
}