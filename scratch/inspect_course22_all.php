<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
global $DB;

$rows = $DB->get_records('aicode', ['course' => 22], 'id ASC');
echo "Course 22 AICode activities count: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "ID {$r->id}: {$r->name}\n";
    echo "  Startercode (first 100 chars):\n    " . str_replace("\n", "\n    ", substr($r->startercode ?? '', 0, 100)) . "\n";
}
