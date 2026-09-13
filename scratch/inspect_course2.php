<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
global $DB;

$rows = $DB->get_records_select('aicode', 'course = 2', null, 'id ASC');
foreach ($rows as $r) {
    echo "ID {$r->id}: {$r->name}\n";
    echo "Starter: " . str_replace("\n", " ", substr($r->startercode ?? '', 0, 100)) . "\n";
}
