<?php
/*
 * WELCOME TO MACHINA
 */

require_once(dirname(__FILE__) . '/lib/setup.php');

$verbid = optional_param('id', 19, PARAM_INT);

$baseurl = '/conjugate.php';
$params = array();
if ($verbid) {
    $params['id'] = $verbid;
}
$PAGE->set_url($baseurl, $params);
$PAGE->set_title('Conjugate');

echo $OUTPUT->header();

if (!$verbid) {
    echo $OUTPUT->footer();
    exit;
}

// Pull a verb out of the database
// debugging(pr($verbid, '$verbid', true));
$verbentry = $DB->get_record('SELECT * FROM verb WHERE id = ?', array($verbid), MUST_EXIST);
$verb = new regular_verb($verbentry->pp1, $verbentry->pp2, $verbentry->pp3, $verbentry->pp4, $verbentry->verbgroup, $verbentry->transitive);
// debugging('<p>Verb Object:</p><pre>'.print_r($verb,true).'</pre>');
$stems = array('root' => $verb->root,
               'present' => $verb->present_stem,
               'perfect' => $verb->perfect_stem,
               'participial' => $verb->participial_stem);
// debugging('<p>Stems:</p><pre>'.print_r($stems,true).'</pre>');
               
$forms = $verb->conjugate();
// debugging('<p>Forms:</p><pre>'.print_r($forms,true).'</pre>');

echo '<h1 class="conjugationtableheader">' . macron::convert_upper($verb->get_principal_parts()) . '</h1>';

echo $OUTPUT->conjugation_table($forms);


echo $OUTPUT->footer();

