<?php
/**
 * Library of language strings for adding zazz to the UX
 */
defined('MACHINA_INTERNAL') || die;

class lingua {
	const HAPPY   = 1;
	const SAD	  = 2;
	const ANGRY   = 3;
	const NEUTRAL = 4;
	
	static function get_emotion_info($emotion = null) {
		$anger = new emotion(self::ANGRY, 'angry', 'anger', 'w3-red');
		$happiness = new emotion(self::HAPPY, 'happy', 'happiness', 'w3-green');
		$neutral = new emotion(self::NEUTRAL, 'neutral', 'neutral', 'w3-brown');
		$sadness = new emotion(self::SAD, 'sad', 'sadness', 'w3-indigo');
		
		$em_info = array();
		$em_info[$anger->id] = $anger;
		$em_info[$happiness->id] = $happiness;
		$em_info[$neutral->id] = $neutral;
		$em_info[$sadness->id] = $sadness;
		
		if (isset($emotion)) {
			if (array_key_exists($emotion, $em_info)) {
				return $em_info[$emotion];
			} else {
				throw new coding_exception('Invalid $emotion value');
			}
		} else {
			return $em_info;
		}
	}
	
	static function express($emotion, $upper = true) {
		global $DB;
		
		$em_info = self::get_emotion_info($emotion);
		
		// Get all the expressions of that emotion
		$set = $DB->get_records("SELECT le.expression FROM lingua_expression le
								 JOIN lingua_expression_tag let ON let.expression_id = le.id
								 WHERE let.tag_id = ?", array($em_info->id));
								 
		// Pick one
		if (empty($set)) {
			return;
		} else {
			$seed = rand(0, count($set)-1);
			$raw = $set[$seed];
			$raw = $raw->expression;
		}
		
		// Capitalize if required
		if ($upper) {
			return ucfirst($raw);
		} else {
			return $raw;
		}
	}
	
	static function update_tags($expression, $tags = array()) {
		global $DB;
		
		// First, delete all current tags associated with this expression
		$DB->delete_record("DELETE FROM lingua_expression_tag WHERE expression_id = ?", array($expression));
		
		// Now add in the tags, if any
		if (!empty($tags)) {
			// Insert tag associations
			foreach ($tags as $tag) {
				$tagsql = "INSERT INTO lingua_expression_tag (expression_id, tag_id) VALUES (:expression, :tag)";
				$DB->insert_record($tagsql, array(':expression' => $expression, ':tag' => $tag));
			}
			return true;
		} else {
			return true;
		}
	}
}

class emotion extends lingua {
	public $id;
	public $adjective;
	public $noun;
	public $cssclass;
	
	function __construct($id, $adjective, $noun, $cssclass) {
		$this->id = $id;
		$this->adjective = $adjective;
		$this->noun = $noun;
		$this->cssclass = $cssclass;
	}
}