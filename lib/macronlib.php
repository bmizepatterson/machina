<?php
/**
 * Machina macron library
 */
defined('MACHINA_INTERNAL') || die;
 
class macron {
     
    /**
     * Searches a string and replaces upper-case vowels with lower-case macrons
     * @param string $search The string to search
     * @param int $method The method of replacement, usually determined by $CFG->macrons
     * @return string
     */
    public static function convert_upper($search, $method = null) {
        global $CFG;
        
        if (empty($search)) {
            return;
        }
        
        if (!is_string($search)) {
            throw new coding_exception('The $search parameter must be a string.');
        }
        
        if (!isset($method)) {
            $method = isset($CFG->macrons) ? $CFG->macrons : NO_MACRONS;
        }
        
        $patterns = array('/A/', '/E/', '/I/', '/O/', '/U/', '/Y/');
        
        switch ($method) {
            case USE_ENTITIES:
                // No HTML entity for a y with a macron
                $replacements = array('&amacr;', '&emacr;', '&imacr;', '&omacr;', '&umacr;', 'y');
                break;
            case USE_DIERESES:
                $replacements = array('&auml;', '&euml;', '&iuml;', '&ouml;', '&uuml;', '&yuml;');
                break;
            case USE_CAPS:
                return $search;
            case NO_MACRONS:
            default:
                $replacements = array('a', 'e', 'i', 'o', 'u', 'y');
                break;
        }
        
        return preg_replace($patterns, $replacements, $search);
    }
}
