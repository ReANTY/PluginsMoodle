<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/completionlib.php');

global $DB;

$course = $DB->get_record('course', ['id' => 22]);
$cinfo = new completion_info($course);
$user = $DB->get_record('user', ['id' => 3]);

$cms = $DB->get_records_sql("SELECT cm.id, cm.instance, m.name as modname, cm.completion, cm.completionview, cm.completionpassgrade, cm.completiongradeitemnumber
                               FROM {course_modules} cm
                               JOIN {modules} m ON m.id = cm.module
                               JOIN {course_sections} cs ON cs.id = cm.section
                              WHERE cm.course = 22 AND cs.section = 1
                           ORDER BY cm.id");

echo "Checking section 1 completion details for User 3:\n";
foreach ($cms as $cm_rec) {
    $cm = get_coursemodule_from_id($cm_rec->modname, $cm_rec->id, 22, false, MUST_EXIST);
    $data = $cinfo->get_data($cm, false, 3);
    $inst = $DB->get_record($cm_rec->modname, ['id' => $cm_rec->instance], 'name');
    $name = $inst ? $inst->name : '';
    echo "CMID {$cm->id} ({$name}):\n";
    echo "  completionstate: {$data->completionstate} (0=Incomplete, 1=Complete, 2=Complete Pass, 3=Complete Fail)\n";
    echo "  viewed: {$data->viewed}\n";
    echo "  passgrade: {$data->passgrade}\n";
    echo "  cm settings: completion={$cm->completion}, view={$cm->completionview}, passgrade={$cm->completionpassgrade}\n";
}
