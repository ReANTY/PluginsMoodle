<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
global $DB;

$count = $DB->count_records('aicode_attempts');
echo "Total attempts in mdl_aicode_attempts: " . $count . "\n";
$attempts = $DB->get_records('aicode_attempts', null, 'id DESC', '*', 0, 10);
foreach ($attempts as $a) {
    echo "ID: {$a->id}, Problem: {$a->problemid}, User: {$a->userid}, Created: " . date('Y-m-d H:i:s', $a->timecreated) . "\n";
    $res = json_decode($a->result_json, true);
    if (!empty($res['code'])) {
        echo "Code: " . substr($res['code'], 0, 50) . "...\n";
    }
}
