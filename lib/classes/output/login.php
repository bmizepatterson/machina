<?php
/**
 * Login form renderable class
 */
namespace output;
defined('MACHINA_INTERNAL') || die();

use renderable;
use url;
use stdClass;

class login implements renderable {
    /** @var bool Whether to auto focus the form fields. */
    public $autofocusform;
    /** @var bool Whether we can login as guest. */
    public $canloginasguest;
    /** @var bool Whether we can login by e-mail. */
    public $canloginbyemail;
    /** @var bool Whether we can sign-up. */
    public $cansignup;
    /** @var help_icon The cookies help icon. */
    public $cookieshelpicon;
    /** @var string The error message, if any. */
    public $error;
    /** @var url Forgot password URL. */
    public $forgotpasswordurl;
    /** @var string Login instructions, if any. */
    public $instructions;
    /** @var url The form action login URL. */
    public $loginurl;
    /** @var bool Whether the username should be remembered. */
    public $rememberusername;
    /** @var url The sign-up URL. */
    public $signupurl;
    /** @var string The user name to pre-fill the form with. */
    public $username;
    
    /**
     * Constructor.
     *
     * @param string $username The username to display.
     */
    public function __construct($username = '') {
        global $CFG, $SESSION;
        $this->username = $username;
        $this->canloginasguest = $CFG->guestloginbutton and !isguestuser();
        $this->canloginbyemail = !empty($CFG->authloginviaemail);
        $this->cansignup = $CFG->registerauth == 'email' || !empty($CFG->registerauth);
        // $this->cookieshelpicon = new help_icon('cookiesenabled', 'core');
        $this->autofocusform = !empty($CFG->loginpageautofocus);
        $this->rememberusername = isset($CFG->rememberusername) and $CFG->rememberusername == 2;
        $this->forgotpasswordurl = new url($CFG->httpswwwroot . '/login/forgot_password.php');
        $this->loginurl = new url($CFG->httpswwwroot . '/login/index.php');
        $this->signupurl = new url('/login/signup.php');
        // Authentication instructions.
        $this->instructions = 'Create an account.';
    }
    
    /**
     * Set the error message.
     *
     * @param string $error The error message.
     */
    public function set_error($error) {
        $this->error = $error;
    }
    
    public function render() {
        return;
    }
}