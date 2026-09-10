<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

$sql = "SELECT cm.id, cs.section AS secnum, m.name AS modname, cm.instance
          FROM {course_modules} cm
          JOIN {modules} m ON m.id = cm.module
          JOIN {course_sections} cs ON cs.id = cm.section
         WHERE cm.course = 22
      ORDER BY cs.section, cm.id";

$cms = $DB->get_records_sql($sql);
$weekly = [];

foreach ($cms as $cm) {
    $inst = $DB->get_record($cm->modname, ['id' => $cm->instance], 'name');
    $name = $inst ? $inst->name : '';
    if (stripos($name, 'Weekly Quiz') !== false) {
        $weekly[$cm->secnum]['quiz'] = ['cmid' => $cm->id, 'name' => $name, 'instance' => $cm->instance];
    }
    if (stripos($name, 'Weekly Assignment') !== false || stripos($name, 'Final Project') !== false) {
        $weekly[$cm->secnum]['assignment'] = ['cmid' => $cm->id, 'name' => $name, 'instance' => $cm->instance];
    }
}

ksort($weekly);
echo json_encode($weekly, JSON_PRETTY_PRINT);
