<?php
/*
 * Viewing/editing the verbs in the database
 */

require_once('../../lib/setup.php');
require_once('classes/verb_table_renderable.php');

$edit = optional_param('edit', null, PARAM_INT);
$delete = optional_param('delete', null, PARAM_INT);

$url = new url('/admin/verba/editverbs.php');
if ($edit) {
	$url->param('edit', $edit);
}
if ($delete) {
	$url->param('delete', $delete);
}
$PAGE->set_url($url);
$PAGE->set_title('Edit Verbs');

// Process submitted data
// Deleting a verb
if ($delete) {
	$deletedverb = $DB->get_record("SELECT pp1 FROM verb WHERE id = ?", array($delete), MUST_EXIST);
	$verbname = cite_latin(macron::convert_upper($deletedverb->pp1));
	
	if ($DB->delete_record("DELETE FROM verb WHERE id = ?", array($delete))) {
		$PAGE->notify("The verb $verbname has been deleted.", \output\notification::NOTIFY_SUCCESS);
	}

} elseif ($edit) {
	$editingverb = $DB->get_record("SELECT * FROM verb WHERE id = ?", array($edit), MUST_EXIST);

} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
	$newverb = $_POST;
	// Scrub the data
	foreach ($newverb as $key => &$value) {
		$value = clean_text($value);		
	}

	if (isset($_POST['insert'])) {
		// Adding a new verb	
		// Check for duplicates
		
		if ($DB->get_records("SELECT pp1 FROM verb WHERE pp1 = ?", array($newverb['pp1']))) {
			$PAGE->notify('The verb ' . cite_latin(macron::convert_upper($newverb['pp1'])) . ' already exists.', \output\notification::NOTIFY_WARNING);
		} else {			
			$sql = "INSERT INTO verb (pp1, pp2, pp3, pp4, verbgroup, transitive, deponent)
					VALUES (:pp1, :pp2, :pp3, :pp4, :verbgroup, :transitive, :deponent)";
			$insertverb = array(':pp1' 			=> $newverb['pp1'],
								':pp2' 			=> $newverb['pp2'],
								':pp3' 			=> $newverb['pp3'],
								':pp4' 			=> $newverb['pp4'],
								':verbgroup'	=> $newverb['verbgroup'],
								':transitive'	=> $newverb['transitive'],
								':deponent'		=> $newverb['deponent']);
			if ($DB->insert_record($sql, $insertverb)) {
				$PAGE->notify('The verb ' . cite_latin(macron::convert_upper($newverb['pp1'])) . ' has been added.', \output\notification::NOTIFY_SUCCESS);
			} else {
				$PAGE->notify('There was a problem adding the verb ' . cite_latin(macron::convert_upper($newverb['pp1'])) . '.', \output\notificaiton::NOTIFY_ERROR);
			}
		} 

	} elseif (isset($_POST['update'])) {
		// Updating a verb
		$sql = "UPDATE verb 
				SET pp1 = :pp1, pp2 = :pp2, pp3 = :pp3, pp4 = :pp4, verbgroup = :verbgroup, transitive = :transitive, deponent = :deponent
				WHERE id = :id";
		$updateverb = array(':pp1' 		  => $newverb['pp1'],
							':pp2' 		  => $newverb['pp2'],
							':pp3' 	      => $newverb['pp3'],
							':pp4' 		  => $newverb['pp4'],
							':verbgroup'  => $newverb['verbgroup'],
							':transitive' => $newverb['transitive'],
							':deponent'	  => $newverb['deponent'],
							':id'		  => $newverb['id']);
		if ($DB->update_record($sql, $updateverb)) {
			$PAGE->notify('The verb ' . cite_latin(macron::convert_upper($newverb['pp1'])) . ' has been updated.', \output\notification::NOTIFY_SUCCESS);
		} else {
			$PAGE->notify('There was a problem updating the verb ' . cite_latin(macron::convert_upper($newverb['pp1'])) . '.', \output\notification::NOTIFY_ERROR);
		}		
	}
}

echo $OUTPUT->header();

// Prepare values if editing a verb
if ($edit) {
	$header = 'Update ' . cite_latin(macron::convert_upper($editingverb->pp1));
	$editpp1 = ' value="' . $editingverb->pp1 . '"';
	$editpp2 = ' value="' . $editingverb->pp2 . '"';
	$editpp3 = ' value="' . $editingverb->pp3 . '"';
	$editpp4 = ' value="' . $editingverb->pp4 . '"';
	$transitiveyes = $editingverb->transitive ? ' checked' : '';
	$transitiveno = $editingverb->transitive ? '' : ' checked';
	$deponentyes = $editingverb->deponent ? ' checked' : '';
	$deponentno = $editingverb->deponent ? '' : ' checked';
	$editid = '<input type="hidden" name="id" value="'.$edit.'">';
	$submit = '<input class="w3-btn w3-black w3-block" type="submit" name="update" value="Update">';	
	
} else {
	$header = 'Add a verb';
	$editpp1 = ' autofocus';
	$editpp2 = '';
	$editpp3 = '';
	$editpp4 = '';
	$editid = '';
	$transitiveyes = ' checked';
	$transitiveno = '';
	$deponentyes = '';
	$deponentno = ' checked';
	$submit = '<input class="w3-btn w3-black w3-block" type="submit" name="insert" value="Add">';
}

$conjugations = '';
$verbgroups = verb::get_group_names();
foreach ($verbgroups as $id => $groupname) {
	
	if ($edit and $editingverb->verbgroup == $id) {
		$selected = ' selected';
	} else {
		$selected = '';
	}
	$conjugations .= '<option value="' . $id . '"' . $selected . '>' . $groupname . "</option>\n";
}

echo $OUTPUT->open('div', 'editingform', array('class' => 'w3-card-4 w3-section-extra'));

echo '
<div class="w3-container w3-black">
  <h2>' . $header . '</h2>
</div>
<form class="w3-container" method="post" action="'.$url->out_omit_querystring().'">
	<div class="w3-row-padding w3-section">
		<div class="w3-quarter">
			<input placeholder="1st" class="w3-input" type="text" name="pp1" required' . $editpp1 . '>
		</div>
		<div class="w3-quarter">
			<input placeholder="2nd" class="w3-input" type="text" name="pp2"' . $editpp2 . '>
		</div>
		<div class="w3-quarter">
			<input placeholder="3rd" class="w3-input" type="text" name="pp3"' . $editpp3 . '>
		</div>
		<div class="w3-quarter">
			<input placeholder="4th" class="w3-input" type="text" name="pp4"' . $editpp4 . '>
		</div>
	</div>
	<div class="w3-row-padding w3-section">
		<div class="w3-quarter w3-margin-top">
			<select class="w3-select" name="verbgroup">' . $conjugations . '</select>		
		</div>
		<div class="w3-quarter w3-margin-top">
			<label><input class="w3-radio" type="radio" name="transitive" value="1"' . $transitiveyes . '>
				Transitive</label><br />
			<label><input class="w3-radio" type="radio" name="transitive" value="0"' . $transitiveno . '>
				Intransitive</label>
		</div>
		<div class="w3-quarter w3-margin-top">
			<label><input class="w3-radio" type="radio" name="deponent" value="1"' . $deponentyes . '>
				Deponent</label><br />
			<label><input class="w3-radio" type="radio" name="deponent" value="0"' . $deponentno . '>
				Not deponent</label>
		</div>
		<div class="w3-quarter w3-margin-top" style="vertical-align:middle;">' . $submit . '</div>
	</div>' . 
	$editid .
'</form>';

echo $OUTPUT->close('editingform');

// Print a list of all the verbs
$verb_table = new verb_table_renderable('*', null, 'pp1 ASC', $url);
echo $OUTPUT->render($verb_table);


echo $OUTPUT->footer();