<?php
/**
 * CLI: Build JavaScript Fundamental course (JS-FUND-2026).
 *
 * @package    local
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/lib/gradelib.php');
require_once($CFG->dirroot . '/admin/course_builder_helpers.php');

raise_memory_limit(MEMORY_EXTRA);

if (!customcert_plugin_available()) {
    mtrace('ERROR: mod_customcert not found. Install plugin and run: php admin/cli/upgrade.php');
    exit(1);
}

$config = require($CFG->dirroot . '/admin/js_course_data.php');
$shortname = $config['course']['shortname'];

if ($existing = get_course_id_by_shortname($shortname)) {
    mtrace('Removing existing course ID ' . $existing . ' (' . $shortname . ')...');
    require_once($CFG->dirroot . '/course/lib.php');
    delete_course($DB->get_record('course', ['id' => $existing], '*', MUST_EXIST), false);
}

$coursecfg = $config['course'];
$coursedata = new stdClass();
$coursedata->fullname = $coursecfg['fullname'];
$coursedata->shortname = $shortname;
$coursedata->category = $coursecfg['category'];
$coursedata->summary = $coursecfg['summary'];
$coursedata->summaryformat = FORMAT_HTML;
$coursedata->format = $coursecfg['format'];
$coursedata->numsections = $coursecfg['numsections'];
$coursedata->startdate = $coursecfg['startdate'];
$coursedata->visible = $coursecfg['visible'];
$coursedata->enablecompletion = COMPLETION_ENABLED;
$coursedata->showgrades = 1;

$transaction = $DB->start_delegated_transaction();

try {
    $course = create_course($coursedata);
    $courseid = $course->id;
    mtrace('Course created: ' . $courseid);

    $generalpage = require($CFG->dirroot . '/admin/js_course_general_page.php');
    create_page_resource(
        $courseid,
        0,
        $generalpage['title'],
        $generalpage['intro'],
        $generalpage['content']
    );
    mtrace('General section welcome page created.');

    $gbmap = ['micro_quiz' => [], 'weekly_quiz' => [], 'weekly_assignment' => [], 'final_project' => []];
    $unlockcmid = null;

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

    mtrace('Building week 1...');
    $w1 = build_week_from_data($courseid, 1, $week1, $unlockcmid);
    $gbmap['micro_quiz'] = array_merge($gbmap['micro_quiz'], $w1['micro_quiz_cmids']);
    $unlockcmid = $w1['weekly_assignment_cmid'];

    for ($w = 2; $w <= 7; $w++) {
        mtrace('Building week ' . $w . '...');
        $weekdata = require($CFG->dirroot . '/admin/js_week' . $w . '_content.php');
        $result = build_week_from_data($courseid, $w, $weekdata, $unlockcmid);
        $gbmap['micro_quiz'] = array_merge($gbmap['micro_quiz'], $result['micro_quiz_cmids']);
        $unlockcmid = $result['weekly_assignment_cmid'];
    }

    mtrace('Building week 8...');
    $week8 = require($CFG->dirroot . '/admin/js_week8_content.php');
    $avail = $unlockcmid ? availability_require_cm_completion($unlockcmid) : null;
    $opts = $avail ? ['availability' => $avail] : [];
    update_section($courseid, 8, $week8['section_name'], $week8['section_summary']);
    foreach ($week8['pages'] as $page) {
        create_page_resource($courseid, 8, $page['title'], $page['intro'], $page['content'], $opts);
    }
    $fp = $week8['final_project'];
    $finalcmid = create_aicode_activity($courseid, 8, [
        'name' => $fp['name'],
        'intro' => $fp['intro'],
        'description' => $fp['description'],
        'mode' => 'exam',
        'startercode' => $fp['startercode'],
        'testcases' => $fp['testcases'],
    ], $opts);
    $gbmap['final_project'][] = $finalcmid;

    $modinfo = get_fast_modinfo($courseid);
    $gbmap['weekly_quiz'] = [];
    $gbmap['weekly_assignment'] = [];
    foreach ($modinfo->cms as $cm) {
        if ($cm->modname === 'quiz' && strpos($cm->name, 'Weekly Quiz') !== false) {
            $gbmap['weekly_quiz'][] = $cm->id;
        }
        if ($cm->modname === 'aicode' && strpos($cm->name, 'Weekly Assignment') !== false) {
            $gbmap['weekly_assignment'][] = $cm->id;
        }
    }

    mtrace('Configuring gradebook...');
    configure_gradebook($courseid, $gbmap);

    mtrace('Configuring completion...');
    configure_course_completion($courseid, $finalcmid);

    mtrace('Adding certificate...');
    create_customcert_activity($courseid, 8, 'Sertifikat JavaScript Fundamental',
        '<p>Sertifikat internal setelah lulus course (nilai minimal 70%).</p>', $opts);

    rebuild_course_cache($courseid, true);

    mtrace('Repairing quiz sections and attempt layouts...');
    $quizfix = repair_course_quiz_structure($courseid);
    if ($quizfix['sections'] || $quizfix['deleted'] || $quizfix['fixedlayout']) {
        mtrace('  Sections added: ' . $quizfix['sections'] .
            ', attempts removed: ' . $quizfix['deleted'] .
            ', layouts fixed: ' . $quizfix['fixedlayout']);
    }

    $transaction->allow_commit();

    mtrace('SUCCESS. Course ID: ' . $courseid . ' | URL: ' . $CFG->wwwroot . '/course/view.php?id=' . $courseid);
    exit(0);

} catch (Exception $e) {
    $transaction->rollback($e);
    mtrace('ERROR: ' . $e->getMessage());
    mtrace($e->getTraceAsString());
    exit(1);
}
