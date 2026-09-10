<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

$feedbacks = $DB->get_records('acmls_motivation_feedback', ['courseid' => 22]);
echo "Feedbacks in Course 22: " . count($feedbacks) . "\n";
foreach ($feedbacks as $f) {
    echo "ID: {$f->id} | User: {$f->userid} | Category: {$f->category} | Source: {$f->source} | e1: {$f->e1} | Time: " . date('Y-m-d H:i:s', $f->timecreated) . "\n";
}

// Reset any test feedbacks for user 3
$deleted = $DB->delete_records('acmls_motivation_feedback', ['courseid' => 22, 'userid' => 3]);
echo "Deleted test feedback count for user 3: " . $deleted . "\n";
