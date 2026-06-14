<?php
/**
 * Debug course builder — full week 1 test.
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/admin/course_builder_helpers.php');

$shortname = 'DEBUG-JS-BUILD-' . time();
$coursedata = new stdClass();
$coursedata->fullname = 'Debug JS Build';
$coursedata->shortname = $shortname;
$coursedata->category = 1;
$coursedata->summary = 'debug';
$coursedata->summaryformat = FORMAT_HTML;
$coursedata->format = 'topics';
$coursedata->numsections = 8;
$coursedata->startdate = time();
$coursedata->visible = 1;
$coursedata->enablecompletion = COMPLETION_ENABLED;
$coursedata->showgrades = 1;

$transaction = $DB->start_delegated_transaction();

try {
    $course = create_course($coursedata);
    $courseid = $course->id;
    echo "Course ID: $courseid ($shortname)\n";

    $week1 = require($CFG->dirroot . '/admin/js_week1_content.php');
    $w1extra = require($CFG->dirroot . '/admin/js_week1_enrichment.php');
    foreach ($week1['micro_lessons'] as $i => &$lesson) {
        if (isset($w1extra['lesson_enrichment'][$i])) {
            $lesson['practice'] = $w1extra['lesson_enrichment'][$i]['practice'];
            $lesson['micro_quiz'] = $w1extra['lesson_enrichment'][$i]['micro_quiz'];
        }
    }
    unset($lesson);
    $week1['weekly_quiz'] = $w1extra['weekly_quiz'];
    $week1['weekly_assignment'] = $w1extra['weekly_assignment'];

    $result = build_week_from_data($courseid, 1, $week1, null);
    echo "Week 1 OK — micro quizzes: " . count($result['micro_quiz_cmids']) . "\n";

    $transaction->allow_commit();
    echo "SUCCESS\n";
} catch (Throwable $e) {
    $transaction->rollback($e);
    echo "FAIL: " . $e->getMessage() . "\n";
    if ($e instanceof dml_exception && !empty($e->debuginfo)) {
        echo $e->debuginfo . "\n";
    }
    exit(1);
}
