<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
global $DB;
foreach ([32, 33, 34] as $id) {
    $r = $DB->get_record('aicode', ['id' => $id]);
    if (!$r) continue;
    echo "=== ID {$id}: {$r->name} (Course {$r->course}) ===\n";
    echo "Description:\n{$r->description}\n";
    echo "Startercode:\n{$r->startercode}\n";
    echo "--------------------------------------------------------\n";
}
