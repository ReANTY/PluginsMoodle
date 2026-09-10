<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

$recs = $DB->get_records('acmls_learner_record', ['userid' => 3, 'courseid' => 22]);
echo "Learner records:\n";
foreach ($recs as $r) {
    echo "ID: {$r->id} | Type: {$r->record_type} | Payload: {$r->data_payload}\n";
}

$fbs = $DB->get_records('acmls_motivation_feedback', ['userid' => 3, 'courseid' => 22]);
echo "\nFeedback records:\n";
foreach ($fbs as $f) {
    echo "ID: {$f->id} | Category: {$f->category} | Time: " . date('Y-m-d H:i:s', $f->timecreated) . "\n";
}
