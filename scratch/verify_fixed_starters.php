<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
global $DB;

foreach ([352, 362, 32, 33] as $id) {
    $r = $DB->get_record('aicode', ['id' => $id]);
    if (!$r) continue;
    echo "=== ID {$id}: {$r->name} ===\n";
    echo $r->startercode . "\n";
    echo "--------------------------------------------------------\n";
}
