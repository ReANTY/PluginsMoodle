<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
global $DB;

$courses = $DB->get_records('course', null, 'id ASC', 'id, fullname, shortname');
foreach ($courses as $c) {
    $aicode_count = $DB->count_records('aicode', ['course' => $c->id]);
    echo "Course ID: {$c->id} | Shortname: {$c->shortname} | Fullname: {$c->fullname} | AICode count: {$aicode_count}\n";
}
