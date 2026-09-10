<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

$sql = "SELECT cm.id, cs.section AS secnum, m.name AS modname, cm.instance, cm.completion, cm.completionview, cm.completionpassgrade, cm.completiongradeitemnumber
          FROM {course_modules} cm
          JOIN {modules} m ON m.id = cm.module
          JOIN {course_sections} cs ON cs.id = cm.section
         WHERE cm.course = 22
      ORDER BY cs.section, cm.id";

$cms = $DB->get_records_sql($sql);
foreach ($cms as $cm) {
    $inst = $DB->get_record($cm->modname, ['id' => $cm->instance], 'name');
    $name = $inst ? $inst->name : '';
    if ($cm->completionpassgrade == 1 || $cm->completionview == 1) {
        echo "Sec {$cm->secnum} | CMID {$cm->id} | Mod: {$cm->modname} | Name: {$name} | completion: {$cm->completion} | view: {$cm->completionview} | passgrade: {$cm->completionpassgrade}\n";
    }
}
