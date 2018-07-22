<?php
/**
 * Object for rendering the list of expressions in LINGUA
 */
 
class expression_table_renderable implements renderable {
    protected $expressions;
    protected $returnurl;
    
    public function __construct(url $returnurl) {
        global $DB;
        
        $this->returnurl = $returnurl;
    }
    
    public function render() {
        global $DB, $OUTPUT;
        
        $output = $OUTPUT->open('div', 'expressionlist', array('class' => 'w3-section-extra w3-row'));
        if (count($this->load_expressions())) {
            $output .= '<ul class="w3-ul w3-hoverable w3-xlarge">';
        	foreach ($this->expressions as $ex) {
        		$exp_emotions = $DB->get_records("SELECT tag_id FROM lingua_expression_tag WHERE expression_id = ?", array($ex->id));
        		$taglist = '';
        		foreach ($exp_emotions as $emotion) {
        			$taginfo = lingua::get_emotion_info($emotion->tag_id);
        			$taglist .= '<span class="w3-tag w3-small w3-padding-small w3-margin-left ' . $taginfo->cssclass . '">' . $taginfo->adjective . '</span>';
        		}
        		$deleteurl = new url($this->returnurl, array('delete' => $ex->id));
        		$deletelink = '<a href="' . $deleteurl . '">' . $OUTPUT->icon('delete_forever') . '</a>';
        		$editurl = new url($this->returnurl, array('edit' => $ex->id));
        		$editlink = '<a href="' . $editurl . '">' . $OUTPUT->icon('mode_edit') . '</a>';
        		$output .= '<li>' . $ex->expression . $taglist . 
                				'<span class="w3-right">' . $deletelink . $editlink . '</span>' . 
                			 '</li>';
        	}
        	$output .= '</ul>';
        } else {
            $output .= '<p>No expressions found.</p>';
        }
        
        $output .= $OUTPUT->close('expressionlist');
    }
    
    protected function load_expressions() {
        global $DB;
        
        $this->expressions = $DB->get_all_records("SELECT id, expression FROM lingua_expression");
        return $ths->expressions;
    }
}