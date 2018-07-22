<?php
/**
 * User authentication functions
 */
defined('MACHINA_INTERNAL') || die();

/** Login attempt successful. */
define('AUTH_LOGIN_OK', 0);

/** Can not login because user does not exist. */
define('AUTH_LOGIN_NOUSER', 1);

/** Can not login because user is suspended. */
define('AUTH_LOGIN_SUSPENDED', 2);

/** Can not login, most probably password did not match. */
define('AUTH_LOGIN_FAILED', 3);

/** Can not login because user is locked out. */
define('AUTH_LOGIN_LOCKOUT', 4);

/** Can not login becauser user is not authorised. */
define('AUTH_LOGIN_UNAUTHORISED', 5);

/**
 * Verify if user is locked out.
 *
 * @param stdClass $user
 * @return bool true if user locked out
 */
function login_is_lockedout($user) {
    global $CFG;

    if (isguestuser($user)) {
        return false;
    }

    if (empty($CFG->lockoutthreshold)) {
        // Lockout not enabled.
        return false;
    }

    if (get_user_preferences('login_lockout_ignored', 0, $user)) {
        // This preference may be used for accounts that must not be locked out.
        return false;
    }

    $locked = get_user_preferences('login_lockout', 0, $user);
    if (!$locked) {
        return false;
    }

    if (empty($CFG->lockoutduration)) {
        // Locked out forever.
        return true;
    }

    if (time() - $locked < $CFG->lockoutduration) {
        return true;
    }

    login_unlock_account($user);

    return false;
}