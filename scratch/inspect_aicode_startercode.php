<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
global $DB;

$rows = $DB->get_records('aicode', null, 'id ASC');
echo "Total aicode records: " . count($rows) . PHP_EOL;
foreach ($rows as $r) {
    echo "ID: " . $r->id . " | Name: " . $r->name . " | Course: " . $r->course . PHP_EOL;
    echo "Description: " . mb_substr(strip_tags($r->description), 0, 100) . "..." . PHP_EOL;
    echo "STARTERCODE:\n" . $r->startercode . PHP_EOL;
    echo "--------------------------------------------------------" . PHP_EOL;
}
