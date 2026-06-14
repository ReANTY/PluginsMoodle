<?php
/**
 * Add missing quiz_sections and fix empty attempt layouts.
 *
 * Usage:
 *   php admin/cli/repair_quiz_sections.php [shortname]
 *   php admin/cli/repair_quiz_sections.php --all
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/admin/course_builder_helpers.php');

$modeall = !empty($argv[1]) && ($argv[1] === '--all' || $argv[1] === 'all');
if ($modeall) {
    $courses = $DB->get_records_select('course', 'id > :site', ['site' => SITEID], 'id ASC');
    $totals = ['sections' => 0, 'deleted' => 0, 'fixedlayout' => 0];
    foreach ($courses as $course) {
        $r = repair_course_quiz_structure((int)$course->id);
        if ($r['sections'] || $r['deleted'] || $r['fixedlayout']) {
            mtrace("{$course->shortname}: sections +{$r['sections']}, deleted {$r['deleted']}, fixed {$r['fixedlayout']}");
        }
        $totals['sections'] += $r['sections'];
        $totals['deleted'] += $r['deleted'];
        $totals['fixedlayout'] += $r['fixedlayout'];
        rebuild_course_cache($course->id, true);
    }
    mtrace("Done (--all). Sections +{$totals['sections']}, deleted {$totals['deleted']}, fixed {$totals['fixedlayout']}.");
    exit(0);
}

$shortname = $argv[1] ?? 'JS-FUND-2026';
$course = $DB->get_record('course', ['shortname' => $shortname]);
if (!$course) {
    mtrace('Course not found: ' . $shortname);
    exit(1);
}

$r = repair_course_quiz_structure((int)$course->id);
rebuild_course_cache($course->id, true);
mtrace("Done. Sections added: {$r['sections']}, attempts removed: {$r['deleted']}, layouts fixed: {$r['fixedlayout']}.");
mtrace('Refresh the quiz page and start Preview again.');
