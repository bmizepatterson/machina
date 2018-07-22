<?php
/**
 * User access functions
 */

/**
 * Determines if a user is currently logged in
 *
 * @category   access
 *
 * @return bool
 */
function isloggedin() {
    global $USER;

    return (!empty($USER->id));
}

/**
 * Check that a user is logged in. If not, either redirect or throw exception
 * @param bool $preventredirect whether to redirect or throw exception
 */
function require_login($preventredirect = false) {
    global $CFG, $SESSION;
    
    // Must not redirect when byteserving already started.
    if (!empty($_SERVER['HTTP_RANGE'])) {
        $preventredirect = true;
    }
    
    // Redirect to the login page if session has expired
    if ((!isloggedin() or isguestuser()) && !empty($SESSION->has_timed_out)) {
        if ($preventredirect) {
            throw new require_login_session_timeout_exception();
        } else {
            redirect(get_login_url());
        }
    }
}

/**
 * Determines if a user is logged in as real guest user with username 'guest'.
 *
 * @param int|object $user mixed user object or id, $USER if not specified
 * @return bool true if user is the real guest user, false if not logged in or other user
 */
function isguestuser($user = null) {
    global $USER, $DB, $CFG;

    // make sure we have the user id cached in config table, because we are going to use it a lot
    if (empty($CFG->siteguest)) {
        if (!$guestid = $DB->get_field('user', 'id', array('username'=>'guest'))) {
            // guest does not exist yet, weird
            return false;
        }
        set_config('siteguest', $guestid);
    }
    if ($user === null) {
        $user = $USER;
    }

    if ($user === null) {
        // happens when setting the $USER
        return false;

    } else if (is_numeric($user)) {
        return ($CFG->siteguest == $user);

    } else if (is_object($user)) {
        if (empty($user->id)) {
            return false; // not logged in means not the guest
        } else {
            return ($CFG->siteguest == $user->id);
        }

    } else {
        throw new coding_exception('Invalid user parameter supplied for isguestuser() function!');
    }
}