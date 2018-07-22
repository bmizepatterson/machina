<?php
/**
 * Object for rendering a table of verbs
 */

defined('MACHINA_INTERNAL') || die;

class verb_table_renderable implements renderable {
    /**
     * array of verbs to be displayed
     * @var array
     */
    protected $verbs;
    /**
     * DB fields to select
     * @var array
     */
    protected $fields;
    /**
     * sort expression
     * @var string
     */
    protected $sort;
    /**
     * Filters
     * @var array
     */
    protected $filters;
    /**
     * Return URL
     * @var url object
     */
    protected $returnurl;
    
    
    public function __construct($fields = array(), $filters = null, $sort = '', url $returnurl) {
        if (empty($fields) or $fields = '*') {
            $this->fields = '*';
        } else if (is_array($fields)) {
            $this->fields = implode($fields, ', ');
        } else {
            throw new coding_exception('$fields must be of type array');
        }
        
        if (isset($filters)) {
            if (is_array($filters)) {
                $this->filters = $filters;
            } else {
                throw new coding_exception('$filters must be of type array');
            }
        } else {
            $this->filters = array();
        }
        
        $this->sort = $sort;
        
        $this->returnurl = $returnurl;
    }
    
    public function render() {
        global $OUTPUT;
        
        $output = $OUTPUT->open('div', 'verblist', array('class' => 'w3-section-extra w3-row'));
        
        if (count($this->load_verbs())) {
            $output .= $this->render_large();
            $output .= $this->render_medium();
            $output .= $this->render_small();
        } else {
            $output .= '<p>No verbs found.</p>';
        }
        
        $output .= $OUTPUT->close('verblist');
        return $output;
    }
    
    protected function load_verbs() {
        global $DB;
        
        $queryparams = array();
        if (!empty($this->filters)) {
            $where = ' WHERE ';
            $i = 1;
            foreach ($this->filters as $param => $value) {
                $where .= "$param = :$param";
                $queryparams[":$param"] = $value;
                if ($i < count($filters)) {
                    $where .= ' AND ';
                }
            }
        } else {
            $where = '';
        }
        if (!empty($this->sort)) {
            $orderby = ' ORDER BY '. $this->sort;
        } else {
            $orderby = '';
        }
        $sql = 'SELECT ' . $this->fields . ' FROM verb' . $where . $orderby;
        
        $this->verbs = $DB->get_records($sql, $queryparams);
        return $this->verbs;
    }
    
    protected function render_large() {
        global $OUTPUT;
        
        // Print the full table of verbs for large screens only
        $output = $OUTPUT->open('div', 'verblistlarge', array('class' => 'w3-hide-small w3-hide-medium'));
    	$output .= '
<table class="w3-table-all w3-hoverable">
<tr><th>1st pp</th><th>2nd pp</th><th>3rd pp</th><th>4th pp</th><th>Conjugation</th><th>Transitive?</th><th>Deponent?</th><th>&nbsp;</th></tr>';

    	foreach ($this->verbs as $verb) {
    		$transitive = $verb->transitive ? 'Yes' : 'No';
    		$deponent = $verb->deponent ? 'Yes' : 'No';
    		$conjugateurl = new url('/conjugate.php', array('id' => $verb->id));
    		$conjugatelink = '<a href="' . $conjugateurl . '" target="_blank">' . macron::convert_upper($verb->pp1) . '</a>';
    		$deleteurl = new url($this->returnurl, array('delete' => $verb->id));
    		$deletelink = '<a href="' . $deleteurl . '">' . $OUTPUT->icon('delete_forever') . '</a>';
    		$editurl = new url($this->returnurl, array('edit' => $verb->id));
    		$editlink = '<a href="' . $editurl . '">' . $OUTPUT->icon('mode_edit') . '</a>';
    		
    		$output .=   '<tr><td>' .  $conjugatelink .
            			 '</td><td>' . macron::convert_upper($verb->pp2) .
            			 '</td><td>' . macron::convert_upper($verb->pp3) .
            			 '</td><td>' . macron::convert_upper($verb->pp4) .
            			 '</td><td>' . verb::get_group_names($verb->verbgroup, true) .
            			 '</td><td>' . $transitive .
            			 '</td><td>' . $deponent .
            			 "</td><td>$deletelink$editlink</td>" .
            			 '</td></tr>';
    	}
    	
    	$output .= '</table>';
    	$output .= $OUTPUT->close('verblistlarge');
    	return $output;
    }
    
    protected function render_medium() {
        global $OUTPUT;
        
        $output = $OUTPUT->open('div', 'verblistmedium', array('class' => 'w3-hide-small w3-hide-large'));
    	// Print a smaller table of verbs for medium screens
    	$output .= '
<table class="w3-table-all w3-hoverable">
<tr><th>1st pp</th><th>2nd pp</th><th>3rd pp</th><th>4th pp</th><th>&nbsp;</th></tr>';

    	foreach ($this->verbs as $verb) {
    		$conjugateurl = new url('/conjugate.php', array('id' => $verb->id));
    		$conjugatelink = '<a href="' . $conjugateurl . '" target="_blank">' . macron::convert_upper($verb->pp1) . '</a>';
    		$deleteurl = new url($this->returnurl, array('delete' => $verb->id));
    		$deletelink = '<a href="' . $deleteurl . '">' . $OUTPUT->icon('delete_forever') . '</a>';
    		$editurl = new url($this->returnurl, array('edit' => $verb->id));
    		$editlink = '<a href="' . $editurl . '">' . $OUTPUT->icon('mode_edit') . '</a>';
    		
    		$output .=   '<tr><td>' .  $conjugatelink .
            			 '</td><td>' . macron::convert_upper($verb->pp2) .
            			 '</td><td>' . macron::convert_upper($verb->pp3) .
            			 '</td><td>' . macron::convert_upper($verb->pp4) .
            			 "</td><td>$deletelink$editlink</td>" .
            			 '</td></tr>';
    	}
    	$output .= '</table>';
    	$output .= $OUTPUT->close('verblistmedium');
    	return $output;
        
    }
    
    protected function render_small() {
        global $OUTPUT;
        
    	$output = $OUTPUT->open('div', 'verblistsmall', array('class' => 'w3-hide-large w3-hide-medium'));
    	// Print an even smaller table of verbs for small screens
    	$output .= '
<table class="w3-table-all w3-hoverable">
<tr><th>1st pp</th><th>&nbsp;</th></tr>';

    	foreach ($this->verbs as $verb) {
    		$conjugateurl = new url('/conjugate.php', array('id' => $verb->id));
    		$conjugatelink = '<a href="' . $conjugateurl . '" target="_blank">' . macron::convert_upper($verb->pp1) . '</a>';
    		$deleteurl = new url($this->returnurl, array('delete' => $verb->id));
    		$deletelink = '<a href="' . $deleteurl . '">' . $OUTPUT->icon('delete_forever') . '</a>';
    		$editurl = new url($this->returnurl, array('edit' => $verb->id));
    		$editlink = '<a href="' . $editurl . '">' . $OUTPUT->icon('mode_edit') . '</a>';
    		
    		$output .=   '<tr><td>' .  $conjugatelink .
            			 "</td><td>$deletelink$editlink</td>" .
            			 '</td></tr>';
    	}
    	$output .= '</table>';
    	$output .= $OUTPUT->close('verblistsmall');
    	return $output;
    }
}