<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/gradelib.php');

global $DB;

$course = $DB->get_record('course', ['id' => 22]);
$cinfo = new completion_info($course);

// Ubah cm 969 completionpassgrade = 0, completiongradeitemnumber = 0
$DB->set_field('course_modules', 'completionpassgrade', 0, ['id' => 969]);
$DB->set_field('course_modules', 'completiongradeitemnumber', 0, ['id' => 969]);

// Rebuild course cache
rebuild_course_cache(22, true);

// Update completion state untuk user 3 pada cm 969
$cm969 = get_coursemodule_from_id('quiz', 969, 22, false, MUST_EXIST);
$cinfo->update_state($cm969, COMPLETION_UNKNOWN, 3);

$data = $cinfo->get_data($cm969, false, 3);
echo "CMID 969 completionstate after update: {$data->completionstate}\n";
