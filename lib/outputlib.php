<?php
/**
 * Machina output library
 */

defined('MACHINA_INTERNAL') || die;

require_once($CFG->libdir.'/classes/xhtml_container_stack.php');
require_once($CFG->libdir.'/classes/output/notification.php');
require_once($CFG->libdir.'/classes/output/base_renderer.php');
require_once($CFG->libdir.'/classes/output/login.php');


interface renderable {
	public function render();
}

/* GENERIC OUTPUT FUNCTIONS */

/**
 * Cite a Latin phrase
 * @param $text The Latin phrase
 * @param $tag The HTML tag to use
 */
function cite_latin($text, $tag = 'span') {
    return "<$tag class='citelatin'>$text</$tag>";
}

function format_stem($stem, $tag = 'span') {
	return "<$tag class='stem'>$stem</$tag>";
}

function format_ending($ending, $tag = 'span') {
	return "<$tag class='ending'>$ending</$tag>";
}

/* OUTPUT OBJECT CLASS */

/**
 * General output class for returning HTML
 * Relies on a renderer class
 */
class machina_output {
	
	/**
	 * Returns the HTML for a conjugation table
	 * @param array $forms array of verb forms from verb::conjugate();
	 */
	public function conjugation_table($forms) {
		static $uniqueid = 1;	// unique id in case more than one conjugation table is output
		
		$tableid = 'conjugationtable'.$uniqueid;
		$singular = verb::get_number_names(verb::SINGULAR);
		$plural = verb::get_number_names(verb::PLURAL);
		
		$output = $this->_opencontainers->push('div', $tableid, array('id' => $tableid, 'class'=>'conjugationtable w3-panel w3-row'));;
		foreach ($forms as $voicename => $voicegroup) {
			// Tense Header
		    $output .= '<div class="w3-container w3-half">
		    			<div class="w3-container w3-black">
		    			<h2>' . verb::get_voice_names($voicename) . '</h2>
		    			</div>';
		
			foreach ($voicegroup as $tensename => $tensegroup) {
				// Tense Header
			    $output .= '<div class="w3-container w3-black">
			    			<h2>' . verb::get_tense_names($tensename) . '</h2>
			    			</div>
			    			<div class="w3-container w3-row">';
			    // Singular forms
			    $output .= "<div class='w3-half'>
			    			<h3>$singular</h3>
			    			<ul class='w3-ul'>";
			    
			    foreach ($tensegroup as $person) {
			        foreach ($person as $number => $form) {
			        	if ($number == verb::SINGULAR) {
			        		// debugging(pr($form));
			            	$output .= '<li>' . format_stem(macron::convert_upper($form['stem'])) . format_ending(macron::convert_upper($form['ending'])) . '</li>';
			        	}
			        }
			    }
			    $output .= '</ul></div>';	// End of singular forms
			    
			    // Plural forms
			    $output .= "<div class='w3-half'>
			    			<h3>$plural</h3>
			    			<ul class='w3-ul'>";
			    
			    foreach ($tensegroup as $person) {
			        foreach ($person as $number => $form) {
			        	if ($number == verb::PLURAL) {
			        		// debugging(pr($form));
			            	$output .= '<li>' . format_stem(macron::convert_upper($form['stem'])) . format_ending(macron::convert_upper($form['ending'])) . '</li>';
			        	}
			        }
			    }
			    $output .= '</ul></div>';	// End of plural forms
			    
			    $output .= '</div>';	// End of tense
			}
			$output .= '</div></div>';		// End of voice
		}
		
		$output .= $this->_opencontainers->pop($tableid);
		$uniqueid++;
		return $output;
	}
	
}