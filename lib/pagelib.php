<?php
/*
 * Machina Page object
 */

defined('MACHINA_INTERNAL') || die;

/*
 * Generic page object containing information about the current page
 *
 * The page URL and TITLE must be set before OUTPUT!
 */
class machina_page {
	
	/** The state of the page before it has printed the header **/
    const STATE_BEFORE_HEADER = 0;
    
    /** The state the page is in temporarily while the header is being printed **/
    const STATE_PRINTING_HEADER = 1;
    
    /** The state the page is in while content is presumably being printed **/
    const STATE_IN_BODY = 2;
    
    /**
     * The state the page is in after all HTML has been printed
     */
    const STATE_DONE = 3;
    
    /**
     * @var array An array of CSS classes that should be added to the body tag in HTML.
     */
    protected $_bodyclasses = array();
    
    /**
     * @var bool Sets whether this page should be cached by the browser or not.
     * If it is set to true (default) the page is served with caching headers.
     */
    protected $_cacheable = true;
    
    /**
     * @var bool Used to determine if HTTPS should be required for login.
     */
    protected $_https_login_required = false;
    
    /**
     * @var array An array of notifications to display at the top of the page.
     */
	protected $_notifications = array();
	
	/**
     * @var int Sets the page to refresh after a given delay (in seconds) using
     * meta refresh in {@link standard_head_html()} in classes/base_renderer.php
     * If set to null(default) the page is not refreshed
     */
    protected $_periodicrefreshdelay = null;
	
    /**
     * @var int The current state of the page. The state a page is within
     * determines what actions are possible for it.
     */
    protected $_state = self::STATE_BEFORE_HEADER;
    
    /**
     * @var string The title for the page. Used within the title tag in the HTML head.
     */
	protected $_title = null;
	
	/**
     * @var string The URL for this page. This is mandatory and must be set
     * before output is started.
     */
	protected $_url = null;
	
	/**
     * @var xhtml_container_stack Tracks XHTML tags on this page that have been
     * opened but not closed.
     */
	protected $_opencontainers;
	
	
	// GET METHODS
	
	/**
     * PHP overloading magic to make the $PAGE->property syntax work by redirecting
     * it to the corresponding $PAGE->magic_get_course() method if there is one, and
     * throwing an exception if not.
     *
     * @param string $name property name
     * @return mixed
     * @throws coding_exception
     */
    public function __get($name) {
        $getmethod = 'magic_get_' . $name;
        if (method_exists($this, $getmethod)) {
            return $this->$getmethod();
        } else {
            throw new coding_exception('Unknown property "' . $name . '" of $PAGE.');
        }
    }
    
    protected function magic_get_cacheable() {
        return $this->_cacheable;
    }

	protected function magic_get_notifications() {
		return $this->_notifications;
	}
	
    protected function magic_get_opencontainers() {
        if (is_null($this->_opencontainers)) {
            $this->_opencontainers = new xhtml_container_stack();
        }
        return $this->_opencontainers;
    }
    
    protected function magic_get_periodicrefreshdelay() {
        return $this->_periodicrefreshdelay;
    }
    
    protected function magic_get_state() {
        return $this->_state;
    }

	protected function magic_get_title() {
		return $this->_title;
	}

	protected function magic_get_url() {
		return $this->_url;
	}
	
	
	// SET METHODS
	
	/**
     * Sets whether the browser should cache this page or not.
     *
     * @param bool $cacheable can this page be cached by the user's browser.
     */
    public function set_cacheable($cacheable) {
        $this->_cacheable = $cacheable;
    }
	
	/**
     * Set the state.
     *
     * The state must be one of that STATE_... constants, and the state is only allowed to advance one step at a time.
     *
     * @param int $state The new state.
     * @throws coding_exception
     */
    public function set_state($state) {
        if ($state != $this->_state + 1 || $state > self::STATE_DONE) {
            throw new coding_exception('Invalid state passed to machina_page::set_state. We are in state ' .
                    $this->_state . ' and state ' . $state . ' was requested.');
        }
        if ($state == self::STATE_PRINTING_HEADER) {
            $this->starting_output();
        }
        $this->_state = $state;
    }

	/*
	 * The PAGE url must be set for every page before OUTPUT begins.
	 *
	 * @param url | string $url relative to $CFG->wwwroot
	 */
	public function set_url($url, array $params = null) {
		global $CFG;

        if (is_string($url) && strpos($url, 'http') !== 0) {
            if (strpos($url, '/') === 0) {
                // We have to use httpswwwroot here, because of loginhttps pages.
                $url = $CFG->wwwroot . $url;
            } else {
                throw new machina_exception('Invalid parameter $url, has to be full url or in shortened form starting with /.');
            }
        }

        $this->_url = new url($url, $params);
        $fullurl = $this->_url->out_omit_querystring();
        if (strpos($fullurl, "$CFG->httpswwwroot/") !== 0) {
            debugging('Most probably incorrect set_page() url argument, it does not match the httpswwwroot!');
        }
        $shorturl = str_replace("$CFG->httpswwwroot/", '', $fullurl);
	}
	
	/*
	 * The PAGE title must be set for every page before OUTPUT begins.
	 */
	public function set_title($title) {
        $title = strip_tags($title);
        $title = str_replace('"', '&quot;', $title);
        $this->_title = $title;
	}
	
	/**
     * Adds a CSS class to the body tag of the page.
     *
     * @param string $class add this class name ot the class attribute on the body tag.
     * @throws coding_exception
     */
    public function add_body_class($class) {
        if ($this->_state > self::STATE_BEFORE_HEADER) {
            throw new coding_exception('Cannot call mmachian_page::add_body_class after output has been started.');
        }
        $this->_bodyclasses[$class] = 1;
    }
    /**
     * Adds an array of body classes to the body tag of this page.
     *
     * @param array $classes this utility method calls add_body_class for each array element.
     */
    public function add_body_classes($classes) {
        foreach ($classes as $class) {
            $this->add_body_class($class);
        }
    }
    
    /**
     * This function indicates that current page requires the https when $CFG->loginhttps enabled.
     *
     * By using this function properly, we can ensure 100% https-ized pages
     * at our entire discretion (login, forgot_password, change_password)
     *
     * @return void
     * @throws coding_exception
     */
    public function https_required() {
        global $CFG;
        if (!is_null($this->_url)) {
            throw new coding_exception('https_required() must be used before setting page url!');
        }
        $this->_https_login_required = true;
        if (!empty($CFG->loginhttps)) {
            $CFG->httpswwwroot = str_replace('http:', 'https:', $CFG->wwwroot);
        } else {
            $CFG->httpswwwroot = $CFG->wwwroot;
        }
    }
    
    /**
     * Makes sure that page previously marked with https_required() is really using https://, if not it redirects to https://
     *
     * @return void (may redirect to https://self)
     * @throws coding_exception
     */
    public function verify_https_required() {
        global $CFG, $FULLME;
        if (is_null($this->_url)) {
            throw new coding_exception('verify_https_required() must be called after setting page url!');
        }
        if (!$this->_https_login_required) {
            throw new coding_exception('verify_https_required() must be called only after https_required()!');
        }
        if (empty($CFG->loginhttps)) {
            // Https not required, so stop checking.
            return;
        }
        if (strpos($this->_url, 'https://')) {
            // Detect if incorrect PAGE->set_url() used, it is recommended to use root-relative paths there.
            throw new coding_exception('Invalid page url. It must start with https:// for pages that set https_required().');
        }
        if (!empty($CFG->sslproxy)) {
            // It does not make much sense to use sslproxy and loginhttps at the same time.
            return;
        }
        // Now the real test and redirect!
        // NOTE: do NOT use this test for detection of https on current page because this code is not compatible with SSL proxies,
        //       instead use is_https().
        if (strpos($FULLME, 'https:') !== 0) {
            // This may lead to infinite redirect on an incorrectly configured site.
            // In that case set $CFG->loginhttps=0 within /setup.php.
            redirect($this->_url);
        }
    }
	
	
	// INITIALIZATION METHODS
	// These set various things up in a default way.
	
    /**
     * This method is called when the page first moves out of the STATE_BEFORE_HEADER
     * state. This is our last change to initialise things.
     */
    protected function starting_output() {
        global $CFG;
        $this->initialise_standard_body_classes();
    }
    
    /**
     * Initialises the CSS classes that will be added to body tag of the page.
     */
    protected function initialise_standard_body_classes() {
        global $CFG, $USER;
        if (!isloggedin()) {
            $this->add_body_class('notloggedin');
        }
    }
    
    /**
     * Returns true if the page URL has beem set.
     *
     * @return bool
     */
    public function has_set_url() {
        return ($this->_url!==null);
    }

	/*
	 * Add notifications to be displayed at the top of the page, usually to report on the success/failure of an action.
	 * @param string $text The text of the notification
	 * @param int $type The type of notification
	 */
	public function notify($text, $type = NOTIFY_INFO) {
		global $OUTPUT;
		// If notifications have already been printed on the page, throw an exception
		if ($OUTPUT->notifications_printed()) {
			throw new coding_exception('Call to $PAGE->notify() after notifications have been printed.');
		}
		$this->_notifications[] = new \output\notification($text, $type);
	}
	
}