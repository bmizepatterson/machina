<?php
/**
 * Main login page
 */
require_once('../lib/setup.php');

$cancel      = optional_param('cancel', 0, PARAM_BOOL);      // redirect to frontpage, needed for loginhttps
if ($cancel) {
    redirect('/');
}

// HTTPS is required in this page when $CFG->loginhttps enabled
$PAGE->https_required();
$PAGE->set_url($CFG->httpswwwroot.'/login/login.php');

// Initialize variables
$errormsg = '';
$errorcode = 0;

// Check for timed out sessions
if (!empty($SESSION->has_timed_out)) {
    $session_has_timed_out = true;
    unset($SESSION->has_timed_out);
} else {
    $session_has_timed_out = false;
}

// Get POST data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // convert to an object
    $frm = new array_to_object($_POST);
    debugging(pr($frm,'',true));
} else {
    $frm = false;
}

// Check that username has been entered
if ($frm and isset($frm->username)) {
    $frm->username = trim(strtolower($frm->username));
    

    // Check for a valid username
    if (!$DB->get_record('SELECT COUNT(*) FROM user WHERE username = ?', array($frm->username), ALLOW_MISSING)) {
        $errormsg = 'Username: Invalide username';
        $errorcode = 2;
        $user = null;
    }
    
    if (($frm->username == 'guest') and empty($CFG->guestloginbutton)) {
        $user = false;    // Can't log in as guest if guest button is disabled
        $frm = false;
    } else {
        if (empty($errormsg)) {
            $user = authenticate_user_login($frm->username, $frm->password, false, $errorcode);
        }
    }
    
    if ($user) {
        if (empty($user->confirmed)) {       // This account was never confirmed
            $PAGE->set_title("Confirm your account");
            echo $OUTPUT->header();
            echo '<h1>Your account has not been confirmed.</h1>';
            echo '<p>An email has been sent to ' . $user->email . '. Please click the link there to confirm your account.</p>';
            echo $OUTPUT->footer();
            die;
        }
        
        complete_user_login($user);
    }
// Check that the account email has been verified

// Check for concurrent logins

// Set username cookie

// Check if password has expired

// Is the user coming here b/c session has timed out?

}

if ($session_has_timed_out and !$frm) {
    $errormsg = 'Your session has timed out. Please log in again.';
    $errorcode = 4;
}

// Remember where the user was trying to go before they got here.

// make sure we really are on the https page when https login required
$PAGE->verify_https_required();

// Generate the login page
if (!isset($frm) or !is_object($frm)) {
    $frm = new stdClass();
}

if (empty($frm->username)) {
    $frm->username = '';
    $frm->password = '';
}

if (!empty($SESSION->loginerrormsg)) {
    // We had some errors before redirect, show them now.
    $errormsg = $SESSION->loginerrormsg;
    unset($SESSION->loginerrormsg);
} else if ($errormsg or !empty($frm->password)) {
    // We must redirect after every password submission.
    if ($errormsg) {
        $SESSION->loginerrormsg = $errormsg;
    }
    redirect(new url($CFG->httpswwwroot . '/login/index.php'));
}

$PAGE->set_title('Log in to MACHINA');
echo $OUTPUT->header();
if (isloggedin() and !isguestuser()) {
    // prevent logging when already logged in, we do not want them to relogin by accident because sesskey would be changed
    echo $OUTPUT->open('div', 'box');
    echo '<p>You are already logged in, silly!';
    echo $OUTPUT->close('box');
} else {
    echo '<p>You have reached the login page.</p>';
    $loginform = new \output\login('');
    $loginform->set_error($errormsg);
    echo $OUTPUT->render($loginform);
}
echo $OUTPUT->footer();