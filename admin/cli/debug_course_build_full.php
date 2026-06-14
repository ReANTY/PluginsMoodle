<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/admin/course_builder_helpers.php');

if (!customcert_plugin_available()) {
    echo "SKIP: mod_customcert not installed\n";
    exit(0);
}

$shortname = 'DEBUG-JS-FULL-' . time();
$coursedata = (object)[
    'fullname' => 'Debug Full Build',
    'shortname' => $shortname,
    'category' => 1,
    'summary' => 'x',
    'summaryformat' => FORMAT_HTML,
    'format' => 'topics',
    'numsections' => 8,
    'startdate' => time(),
    'visible' => 1,
    'enablecompletion' => COMPLETION_ENABLED,
    'showgrades' => 1,
];

$tx = $DB->start_delegated_transaction();
try {
    $course = create_course($coursedata);
    $courseid = $course->id;
    $unlock = null;
    $gb = ['micro_quiz' => [], 'weekly_quiz' => [], 'weekly_assignment' => [], 'final_project' => []];

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

    for ($w = 1; $w <= 7; $w++) {
        echo "Week $w... ";
        $data = ($w === 1) ? $week1 : require($CFG->dirroot . "/admin/js_week{$w}_content.php");
        $r = build_week_from_data($courseid, $w, $data, $unlock);
        $gb['micro_quiz'] = array_merge($gb['micro_quiz'], $r['micro_quiz_cmids']);
        $unlock = $r['weekly_assignment_cmid'];
        echo "OK\n";
    }

    $week8 = require($CFG->dirroot . '/admin/js_week8_content.php');
    $r8 = build_week_from_data($courseid, 8, $week8, $unlock);
    $gb['micro_quiz'] = array_merge($gb['micro_quiz'], $r8['micro_quiz_cmids']);
    $fp = $week8['final_project'];
    $finalcmid = create_aicode_activity($courseid, 8, [
        'name' => $fp['name'],
        'intro' => $fp['intro'],
        'description' => $fp['description'],
        'mode' => 'exam',
        'startercode' => $fp['startercode'],
        'testcases' => $fp['testcases'],
        'htmltemplate' => $fp['htmltemplate'] ?? '',
        'csstemplate' => $fp['csstemplate'] ?? '',
    ]);
    $gb['final_project'][] = $finalcmid;

    configure_gradebook($courseid, $gb);
    configure_course_completion($courseid, $finalcmid);

    $tx->allow_commit();
    echo "FULL BUILD SUCCESS: $shortname (id $courseid)\n";
} catch (Throwable $e) {
    $tx->rollback($e);
    echo "FAIL: " . $e->getMessage() . "\n";
    if ($e instanceof dml_exception && !empty($e->debuginfo)) {
        echo $e->debuginfo . "\n";
    }
    exit(1);
}
