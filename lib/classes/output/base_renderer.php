<?php
/**
 * Class for rendering HTML output 
 */
 
class base_renderer {
    /**
     * @var string Used by {@link base_renderer::redirect_message()} method to communicate
     * with {@link base_renderer::header()}.
     */
    protected $metarefreshtag = '';
    
    /**
     * @var xhtml_container_stack The xhtml_container_stack to use.
     */
    protected $opencontainers;
    
    protected $language = 'en-us';
    
    protected $_notifications_printed = false;
    /**
     * @var machina_page The Machina page the renderer has been created to assist with.
     */
    protected $page;
    
    
    /**
     * Constructor
     *
     * @param machina_page $page the page we are doing output for.
     */
    public function __construct(machina_page $page) {
        $this->opencontainers =& $page->opencontainers;
        $this->page = $page;
    }
    
    /**
     * Returns true is output has already started, and false if not.
     *
     * @return boolean true if the header has been printed.
     */
    public function has_started() {
        return $this->page->state >= machina_page::STATE_IN_BODY;
    }
    
    /**
     * Given an array or space-separated list of classes, prepares and returns the HTML class attribute value
     *
     * @param mixed $classes Space-separated string or array of classes
     * @return string HTML class attribute value
     */
    public static function prepare_classes($classes) {
        if (is_array($classes)) {
            return implode(' ', array_unique($classes));
        }
        return $classes;
    }
    
    /**
     * The standard tags (meta tags, links to stylesheets and JavaScript, etc.)
     * that should be included in the <head> tag.
     *
     * @return string HTML fragment.
     */
    public function standard_head_html() {
        global $CFG;
        
        $output = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $output .= "\n".'<meta name="keywords" content="machina, ' . $this->page->title . '" />';
        // This is only set by the {@link redirect()} method
        $output .= $this->metarefreshtag;
        // Check if a periodic refresh delay has been set and make sure we arn't
        // already meta refreshing
        if ($this->metarefreshtag=='' && $this->page->periodicrefreshdelay!==null) {
            $output .= "\n".'<meta http-equiv="refresh" content="'.$this->page->periodicrefreshdelay.';url='.$this->page->url.'" />';
        }
        
        // CSS
        $output .= "\n".'<link rel="stylesheet" type="text/css" href="https://www.w3schools.com/w3css/4/w3.css">';
        $output .= "\n".'<link rel="stylesheet" type="text/css" href="/machina/machina.css">';
        
        /// Material Icons
        $output .= "\n".'<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">';

        // JS
        $output .= "\n".'<script src="/machina/lib/js/machina.js"></script>';

        return $output."\n";
    }
    
    /**
     * The standard tags that should be output just inside the start of the <body> tag.
     *
     * @return string HTML fragment.
     */
    public function standard_top_of_body_html() {
        global $CFG;
        
        // Currently empty
        $output = '';
        return $output;
    }
    
    /**
     * The standard tags (typically performance information and validation links,
     * if we are in developer debug mode) that should be output in the footer area
     * of the page.
     *
     * @return string HTML fragment.
     */
    public function standard_footer_html() {
        global $CFG, $SCRIPT;
        $output = '';

        if (debugging(null, DEBUG_DEVELOPER)) {  // Only in developer mode
            // PURGE CACHES LINK
            // $purgeurl = new machina_url('/admin/purgecaches.php', array('confirm' => 1,
            //     'sesskey' => sesskey(), 'returnurl' => $this->page->url->out_as_local_url(false)));
            // $output .= '<div class="purgecaches">' .
            //         html_writer::link($purgeurl, get_string('purgecaches', 'admin')) . '</div>';
        }
        if (!empty($CFG->debugvalidators)) {
            $output .= '<div><ul>' .
             '<li><a href="http://validator.w3.org/check?verbose=1&amp;ss=1&amp;uri=' . urlencode($this->page->url) . '">Validate HTML</a></li>' .
             '<li><a href="http://www.contentquality.com/mynewtester/cynthia.exe?rptmode=-1&amp;url1=' . urlencode($this->page->url) . '">Section 508 Check</a></li>' .
             '<li><a href="http://www.contentquality.com/mynewtester/cynthia.exe?rptmode=0&amp;warnp2n3e=1&amp;url1=' . urlencode($this->page->url) . '">WCAG 1 (2,3) Check</a></li>' .
            '</ul></div>';
        }
        return $output;
    }
    
    /**
     * The standard tags (typically script tags that are not needed earlier) that
     * should be output after everything else.
     *
     * @return string HTML fragment.
     */
    public function standard_end_of_body_html() {
        global $CFG;
        
        // Currently empty
        $output = '';
        return $output;
    }
    
    /**
     * Check whether the current page is a login page.
     *
     * @return bool
     */
    protected function is_login_page() {
        return in_array(
            $this->page->url->out_as_local_url(false, array()),
            array(
                '/login/index.php',
                '/login/forgot_password.php',
            )
        );
    }
    
    /**
     * Redirects the user by any means possible given the current state
     *
     * @param string $encodedurl The URL to send to encoded if required
     * @param string $message The message to display to the user if any
     * @param int $delay The delay before redirecting a user, if $message has been
     *         set this is a requirement and defaults to 3, set to 0 no delay
     * @param boolean $debugdisableredirect this redirect has been disabled for
     *         debugging purposes. Display a message that explains, and don't
     *         trigger the redirect.
     * @param string $messagetype The type of notification to show the message in.
     * @return string The HTML to display to the user before dying, may contain
     *         meta refresh, javascript refresh, and may have set header redirects
     */
    public function redirect_message($encodedurl, $message, $delay, $debugdisableredirect,
                                     $messagetype = NOTIFICATION_INFO) {
        global $CFG;
        $url = str_replace('&amp;', '&', $encodedurl);
        switch ($this->page->state) {
            case machina_page::STATE_BEFORE_HEADER :
                // No output yet it is safe to use the full arsenal of redirect methods
                if (!$debugdisableredirect) {
                    // Don't use exactly the same time here, it can cause problems when both redirects fire at the same time.
                    $this->metarefreshtag = '<meta http-equiv="refresh" content="'. $delay .'; url='. $encodedurl .'" />'."\n";
                }
                $output = $this->header();
                break;
            case machina_page::STATE_PRINTING_HEADER :
                // We should hopefully never get here
                throw new coding_exception('You cannot redirect while printing the page header.');
                break;
            case machina_page::STATE_IN_BODY :
                // We really shouldn't be here either
                throw new coding_exception('You cannot redirect after output has been printed.');
                break;
            case machina_page::STATE_DONE :
                // Too late to be calling redirect now
                throw new coding_exception('You cannot redirect after the entire page has been generated.');
                break;
        }
        $output .= $this->notification($message, $messagetype);
        $output .= '<div>(<a href="'. $encodedurl .'">Continue</a>)</div>';
        if ($debugdisableredirect) {
            $output .= '<p><strong>Debug messages have been printed. Automatic redirect is disabled so you can read them.</strong></p>';
        }
        $output .= $this->footer();
        return $output;
    }
    
    /**
     * Start output by sending the HTTP headers, and printing the HTML <head>
     * and the start of the <body>.
     *
     * @return string HTML to output
     */
    public function header() {
        global $USER, $CFG, $SESSION;
        
        if ($this->page->state !== machina_page::STATE_BEFORE_HEADER) {
            throw new coding_exception('Attempt to start output after output has already begun.');
        }
        if (!$this->page->title) {
            throw new coding_exception('Page title must be set before output begins.');
        }
        if (!$this->page->url) {
            throw new coding_exception('Page URL must be set before output begins.');
        }

        $header = '';
        
        // TODO: COUNT LOGIN FAILURES
        /*
        if (isset($SESSION->justloggedin) && !empty($CFG->displayloginfailures)) {
            require_once($CFG->dirroot . '/user/lib.php');
            // Set second parameter to false as we do not want reset the counter, the same message appears on footer.
            if ($count = user_count_login_failures($USER, false)) {
                $this->page->add_body_class('loginfailures');
            }
        }
        */

        $this->page->set_state(machina_page::STATE_PRINTING_HEADER);
        
        @header('Content-Type: text/html; charset=utf-8');
        @header('Content-Script-Type: text/javascript');
        @header('Content-Style-Type: text/css');
        @header('X-UA-Compatible: IE=edge');
        if ($this->page->cacheable) {
            // Allow caching on "back" (but not on normal clicks).
            @header('Cache-Control: private, pre-check=0, post-check=0, max-age=0, no-transform');
            @header('Pragma: no-cache');
            @header('Expires: ');
        } else {
            // Do everything we can to always prevent clients and proxies caching.
            @header('Cache-Control: no-store, no-cache, must-revalidate');
            @header('Cache-Control: post-check=0, pre-check=0, no-transform', false);
            @header('Pragma: no-cache');
            @header('Expires: Mon, 20 Aug 1969 09:23:00 GMT');
            @header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        }
        @header('Accept-Ranges: none');
        @header('Content-Language: '.$this->language);

        $header .= "<!DOCTYPE html>\n";        
        $header .= '<html dir="ltr" lang="'.$this->language.'" xml:lang="'.$this->language.'">';
        $header .= $this->standard_head_html();
        
        $header .= $this->opencontainers->push('body', 'body');
        $this->page->set_state(machina_page::STATE_IN_BODY);
        $header .= $this->notifications();
        $header .= $this->navbar();
        $header .= $this->start_main_content();
        return $header;
    }
    
    protected function start_main_content() {
        return $this->opencontainers->push('div', 'main', array('id'=>'main', 'class'=>'w3-content w3-container'));
    }
    
    protected function navbar() {
		global $CFG;
		
		$html = $this->opencontainers->push('div', 'bar', array('class'=>'w3-bar w3-black'));
		
			$html .= '<a href="'.$CFG->wwwroot.'" class="w3-bar-item w3-button">MACHINA</a>';
			$html .= '<a href="'.$CFG->wwwroot.'/conjugate.php" class="w3-bar-item w3-button w3-right">Conjugate</a>';
			$html .= '<a href="'.$CFG->wwwroot.'/admin/lingua/editexpressions.php" class="w3-bar-item w3-button w3-right">Expressions</a>';
			$html .= '<a href="'.$CFG->wwwroot.'/admin/verba/editverbs.php" class="w3-bar-item w3-button w3-right">Verbs</a>';
		
		$html .= $this->opencontainers->pop('bar');
		
		return $html;
	}
    
    /**
     * Outputs the page's footer
     *
     * @return string HTML fragment
     */
    public function footer() {
        global $CFG, $DB, $PAGE, $SESSION, $USER;
        
        $output = $this->container_end_all();
        $output .= $this->opencontainers->push('div', 'footer', array('id'=>'footer'));
        
        if ($CFG->debugdeveloper) {
            // Print special developer debug information
			$output .= $this->opencontainers->push('div', 'debuginfo', array('class'=>'w3-small'));
			$output .= '<button onclick="expand_accordion('."'page_info'".')" class="w3-button w3-black w3-block w3-left-align">$PAGE Info</button>' .
					   '<div id="page_info" class="w3-container w3-hide"><pre>'.htmlentities(print_r($PAGE, true)).'</pre></div>';
					  
			$perf_info = get_performance_info();
			$output .= '<button onclick="expand_accordion('."'perf_info'".')" class="w3-button w3-black w3-block w3-left-align">Performance Info</button>' .
                       '<div id="perf_info" class="performanceinfo w3-container w3-hide">' . $perf_info['html'] . '</div>';
			
			// Print a link to phpmyadmin
			$output .= '<div>
						<a class="w3-button" href="/phpmyadmin/index.php" target="_blank">phpMyAdmin</a>
						<a class="w3-button" href="https://www.w3schools.com/w3css/default.asp" target="_blank">W3 CSS</a>
					  </div>';
			$output .= $this->opencontainers->pop('debuginfo');
		}
		
		$output .= $this->standard_footer_html();
		$output .= $this->opencontainers->pop('footer');
		$output .= $this->standard_end_of_body_html();
	    $output .= $this->opencontainers->pop('body');
	    $output .= '</html>';
        $this->page->set_state(machina_page::STATE_DONE);
        return $output;
    }
    
    /**
     * Close all but the last open container. This is useful in places like error
     * handling, where you want to close all the open containers (apart from <body>)
     * before outputting the error message.
     *
     * @param bool $shouldbenone assert that the stack should be already be empty anyway - causes a
     *      developer debug warning if it isn't.
     * @return string the HTML required to close any open containers inside <body>.
     */
    public function container_end_all($shouldbenone = false) {
        return $this->opencontainers->pop_all_but_last($shouldbenone);
    }
    
    /**
	 * Do not call this function directly. To terminate the current script 
	 * with a fatal error, throw an exception, which will then call this
	 * function to display the error, before terminating the execution.
	 *
	 * @param string $errorcode The error title to output
	 * @param string $description The error description
	 * @param array $backtrace The execution backtrace
	 * @param string $debuginfo Debugging information
	 * @return string the HTML to output.
	 */
	public function fatal_error($errorcode, $description, $backtrace, $debuginfo = null) {
	    global $CFG, $PAGE;
	    
	    $output = '';
	    $obbuffer = '';
	    if ($this->has_started()) {
	        $output .= $this->opencontainers->pop_all_but_last();
	    } else {
	    	// Output not yet started.
	        error_reporting(0); // disable notices from gzip compression, etc.
	        while (ob_get_level() > 0) {
	            $buff = ob_get_clean();
	            if ($buff === false) {
	                break;
	            }
	            $obbuffer .= $buff;
	        }
	        error_reporting($CFG->debug);
	        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
	        if (empty($_SERVER['HTTP_RANGE'])) {
	            @header($protocol . ' 404 Not Found');
	        } else {
	            // Must stop byteserving attempts somehow,
	            // this is weird but Chrome PDF viewer can be stopped only with 407!
	            @header($protocol . ' 407 Proxy Authentication Required');
	        }
	        $PAGE->set_url(new url('/')); // no url
	        $PAGE->set_title('Error');
	        $output .= $this->header();
	    }
	
	    $message = '<div class="w3-panel w3-black" data-rel="fatalerror"><h3>'.$errorcode.'</h3>'.
	    		   '<p>'.$description.'</p>';
	
	    if ($CFG->debugdeveloper) {
	        if (!empty($debuginfo)) {
	            $debuginfo = s($debuginfo); // removes all nasty JS
	            $debuginfo = str_replace("\n", '<br />', $debuginfo); // keep newlines
	            $message .= '<p><strong>Debug info:</strong> '.$debuginfo.'</p>';
	        }
	        if (!empty($backtrace)) {
	            $message .= '<p><strong>Stack trace:</strong> '.format_backtrace($backtrace).'</p>';
	        }
	        if ($obbuffer !== '' ) {
	            $message .= '<div class="ob"><p><strong>Output buffer:</strong></p>'.$obbuffer.'</div>';
	        }
	    }
	    
	    $output .= $message.'</div>';
	
	    $output .= $this->footer();
	
	    // Padding to encourage IE to display our error page, rather than its own.
	    $output .= str_repeat(' ', 512);
	
	    return $output;
	}
    
    public function render(renderable $renderable) {
        if (method_exists($renderable, 'render')) {
            return $renderable->render();
        } else {
            throw new coding_exception('Call to $OUTPUT->render on a non-renderable object.');
        }
    }
    
    public function open($tag, $name, $attributes = null) {
		return $this->opencontainers->push($tag, $name, $attributes);
	}
	
	public function close($name) {
		return $this->opencontainers->pop($name);
	}
	
	/**
	 * Returns transactional PAGE notifications (such as success/failure of an action)
	 * Notificaitons are set using $PAGE->notify().
	 */
	protected function notifications() {
	    global $PAGE;
	    $output = '';
	    foreach ($PAGE->notifications as $notification) {
	        $output .= $this->render($notification);
	    }
	    $this->_notifications_printed = true;
	    return $output;
	}
	
	public function icon($name, $tag = 'i', $extraattributes = array()) {
    	$extraclass = '';
    	$otherattributes = '';
    	
    	if (!empty($extraattributes)) {
    		if (array_key_exists('class', $extraattributes)) {
    			$extraclass = ' ' . $extraattributes['class'];
    			unset($extraattributes['class']);
    		} else {
    			foreach ($extraattributes as $attr => $value) {
    				$otherattributes = " $attr='$value'";
    			}
    		}
    	}
    	return "<$tag class='material-icons$extraclass'$otherattributes>$name</$tag>";
    }
    
    public function notifications_printed() {
        return $this->_notifications_printed;
    }
}