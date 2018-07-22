<?php
/*
 * Viewing/editing lingua expressions and tags
 */

require_once('../../lib/setup.php');

$edit = optional_param('edit', null, PARAM_INT);
$delete = optional_param('delete', null, PARAM_INT);

$baseurl = '/admin/lingua/editexpressions.php';
$params = array();
if ($edit) {
	$params['edit'] = $edit;
}
if ($delete) {
	$params['delete'] = $delete;
}
$PAGE->set_url($baseurl, $params);
$PAGE->set_title('Edit Expressions');

// Process submitted data
// Deleting an expression
if ($delete) {
	// Check that it exists
	if ($DB->get_record("SELECT id FROM lingua_expression WHERE id = ?", array($delete), MUST_EXIST)) {
		// Delete the expression from the DB
		$exp_success = $DB->delete_record("DELETE FROM lingua_expression WHERE id = ?", array($delete));
		// Delete the tag associations from the DB
		$tag_success = $DB->delete_record("DELETE FROM lingua_expression_tag WHERE expression_id = ?", array($delete));
	
		if ($exp_success and $tag_success) {
			$PAGE->notify("The expression has been deleted.", \output\notification::NOTIFY_SUCCESS);
		} else {
			$PAGE->notify("There was a problem deleting the expression.", \output\notification::NOTIFY_ERROR);
		}
	}

} elseif ($edit) {
	$editingexp = $DB->get_record("SELECT le.expression FROM lingua_expression le WHERE id = ?", array($edit), MUST_EXIST);
	$tagsql = "SELECT tag_id as id
			   FROM lingua_expression_tag
			   WHERE expression_id = ?";
	$tagrecords = $DB->get_records($tagsql, array($edit));
	$editingexptags = array();
	foreach ($tagrecords as $tag) {
		$editingexptags[] = $tag->id;
	}

} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
	$newexp = $_POST;
	if (!isset($newexp['tags'])) {
		$newexp['tags'] = array();
	}
	// Scrub the data
	foreach ($newexp as $key => &$value) {
		// Skip tags; they're ok.
		if ($key == 'tags') {
			continue;
		}
		// Save expressions in lowercase and remove any final punctuation
		$value = preg_replace('/\W$/', '', strtolower(clean_text($value)));		
	}
	
	if (isset($_POST['insert'])) {
		// Adding a new expression	
		// Check for duplicates
		
		if ($DB->get_records("SELECT expression FROM lingua_expression WHERE expression = ?", array($newexp['expression']))) {
			$PAGE->notify('The expression "' . $newexp['expression'] . '" already exists.', \output\notification::NOTIFY_WARNING);
		} else {			
			$sql = "INSERT INTO lingua_expression (expression) VALUES (:expression)";
			$insertexp = array(':expression' => $newexp['expression']);
			$newexpid = $DB->insert_record($sql, $insertexp);
			$tag_success = lingua::update_tags($newexpid, $newexp['tags']);
			if ($newexpid and $tag_success) {
				$PAGE->notify('The expression "' . $newexp['expression'] . '" has been added.', \output\notification::NOTIFY_SUCCESS);
			} else {
				$PAGE->notify('There was a problem adding the expression "' . $newexp['expression'] . '".', \output\notification::NOTIFY_ERROR);
			}
		} 

	} elseif (isset($_POST['update'])) {
		// Updating an expression
		$exp_success = $DB->update_record("UPDATE lingua_expression SET expression = ? WHERE id = ?", array($newexp['expression'], $newexp['id']));
		$tag_success = lingua::update_tags($newexp['id'], $newexp['tags']);
		
		if ($exp_success and $tag_success) {
			$PAGE->notify('The expression "' . $newexp['expression'] . '" has been updated.', \output\notification::NOTIFY_SUCCESS);
		} else {
			$PAGE->notify('There was a problem updating the expression "' . $newexp['expression'] . '".', \output\notification::NOTIFY_ERROR);
		}
	}
}

echo $OUTPUT->header();

// Prepare values if editing an expression
if ($edit) {
	$header = 'Edit an expression';
	$editexpression = ' value="' . $editingexp->expression . '"';
	$submit = '<button class="w3-btn w3-black w3-block" type="submit" name="update">' . $OUTPUT->icon('mode_edit', 'i', array('class' => 'w3-xxlarge')) . '</button>';
	$editid = '<input type="hidden" name="id" value="'.$edit.'">';
} else {
	$header = 'Add an expression';
	$editexpression = 'autofocus';
	$submit = '<button class="w3-btn w3-black w3-block" type="submit" name="insert">' . $OUTPUT->icon('playlist_add', 'i', array('class' => 'w3-xxlarge')) . '</button>';
	$editid = '';
}

// Generate the tag list
$tagoptions = '';
$tags = lingua::get_emotion_info();
foreach ($tags as $id => $tag) {
	$selected = '';
	if ($edit and in_array($id, $editingexptags)) {
		$selected = ' checked';
	}
	$tagoptions .= '<div class="w3-container w3-cell">
						<label class="w3-padding ' . $tag->cssclass . '"><input class="w3-check" type="checkbox" name="tags[]" value="' . $id . '"' . $selected . '>
						' . ucfirst($tag->adjective) . "</label>
					</div>\n";
}

echo $OUTPUT->open('div', 'editexpform', array('class' => 'w3-card-4 w3-section-extra'));
echo '
<div class="w3-container w3-black">
  <h2>' . $header . '</h2>
</div>
<form class="w3-container" method="post" action="' . $baseurl . '">
	<div class="w3-row-padding w3-section">
		<div class="w3-col s8 m10">
			<input placeholder="Expression" class="w3-input s8 m11 w3-xlarge" type="text" name="expression" required' . $editexpression . '>
		</div>
		<div class="w3-col s4 m2">' . $submit . '</div>
	</div>
	<div class="w3-section">
		<div class="w3-panel w3-cell-row">' . $tagoptions . 
		'</div>
	</div>' .
	$editid .
'</form>';
echo $OUTPUT->close('editexpform');



echo $OUTPUT->open('div', 'expressionlist', array('class' => 'w3-section-extra w3-row'));
// Load all expressions
$expressions = $DB->get_all_records("SELECT id, expression FROM lingua_expression");
if (count($expressions)) {
	// Print the table of expressions
	echo '<ul class="w3-ul w3-hoverable w3-xlarge">';
	foreach ($expressions as $ex) {
		$exp_emotions = $DB->get_records("SELECT tag_id FROM lingua_expression_tag WHERE expression_id = ?", array($ex->id));
		$taglist = '';
		foreach ($exp_emotions as $emotion) {
			$taginfo = lingua::get_emotion_info($emotion->tag_id);
			$taglist .= '<span class="w3-tag w3-small w3-padding-small w3-margin-left ' . $taginfo->cssclass . '">' . $taginfo->adjective . '</span>';
		}
		$deletelink = '<a href="' . $baseurl . '?delete=' . $ex->id . '">' . $OUTPUT->icon('delete_forever') . '</a>';
		$editlink = '<a href="' . $baseurl . '?edit='.$ex->id . '">' . $OUTPUT->icon('mode_edit') . '</a>';
		echo '<li>' . $ex->expression . $taglist . 
				'<span class="w3-right">' . $deletelink . $editlink . '</span>' . 
			 '</li>';
	}
	echo '</ul>';
} else {
	echo '<p>No expressions found.</p>';
}
echo $OUTPUT->close('expressionlist');

echo $OUTPUT->footer();