<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;
$sql = "SELECT cm.id, cm.section, cs.section AS secnum, m.name as modname, cm.instance, cm.completion, cm.completiongradeitemnumber
          FROM {course_modules} cm
          JOIN {modules} m ON m.id = cm.module
          JOIN {course_sections} cs ON cs.id = cm.section
         WHERE cm.course = 22 AND cs.section IN (1, 2)
      ORDER BY cs.section, cm.id";
$cms = $DB->get_records_sql($sql);
foreach ($cms as $cm) {
    // get instance name
    $inst = $DB->get_record($cm->modname, ['id' => $cm->instance], 'name');
    $name = $inst ? $inst->name : 'Unknown';
    echo "Section {$cm->secnum} (id {$cm->section}) | cmid {$cm->id} | Mod: {$cm->modname} | Name: {$name} | Completion: {$cm->completion}\n";
}
