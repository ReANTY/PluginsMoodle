<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

$delivery = new \local_llmmotivation\delivery\delivery_system();
$sec = $delivery->get_current_section_for_student(3, 22);
echo "Current section for user 3: {$sec}\n";

$fbs = $DB->get_records('acmls_motivation_feedback', ['userid' => 3, 'courseid' => 22]);
echo "Feedbacks for user 3:\n";
foreach ($fbs as $f) {
    echo "ID {$f->id} | Category: {$f->category} | Source: {$f->source}\n";
}
