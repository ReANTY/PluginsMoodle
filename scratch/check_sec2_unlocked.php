<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB;

$course = $DB->get_record('course', ['id' => 22]);
$modinfo = get_fast_modinfo($course, 3);
$sections = $modinfo->get_section_info_all();

foreach ($sections as $sec) {
    if ($sec->section <= 3) {
        echo "Section {$sec->section} ({$sec->name}): uservisible = " . ($sec->uservisible ? 'YES (UNLOCKED!)' : 'NO (LOCKED)') . ", available = " . ($sec->available ? 'YES' : 'NO') . "\n";
    }
}
