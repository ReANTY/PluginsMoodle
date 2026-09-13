<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
global $DB;

$rows = $DB->get_records_select('aicode', 'id BETWEEN 326 AND 346', null, 'id ASC');
foreach ($rows as $r) {
    echo "=== ID: {$r->id} | {$r->name} ===\n";
    echo "STARTERCODE IN DB:\n" . $r->startercode . "\n";
}
