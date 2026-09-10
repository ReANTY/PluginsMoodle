<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

$cmids = [969, 970, 983, 984, 997, 998, 1011, 1012, 1025, 1026, 1039, 1040, 1053, 1054, 1067, 1069];
foreach ($cmids as $cmid) {
    $cm = $DB->get_record('course_modules', ['id' => $cmid], 'id, module, completion, completionview, completionexpected');
    echo "CMID {$cmid}: completion = {$cm->completion}\n";
}
