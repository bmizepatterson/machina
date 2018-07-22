<?php
/*
 * WELCOME TO MACHINA
 */

require_once(dirname(__FILE__) . '/lib/setup.php');

$PAGE->set_url(new url('/index.php'));
$PAGE->set_title('MACHINA');

echo $OUTPUT->header();

echo '
<h1>Machina</h1>
';


echo $OUTPUT->footer();

