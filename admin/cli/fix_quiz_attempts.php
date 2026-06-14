<?php
/**
 * Delete stale quiz attempts (empty layout) after quiz repair.
 * Users must start a new preview/attempt.
 *
 * Usage:
 *   php admin/cli/fix_quiz_attempts.php [shortname]
 *   php admin/cli/fix_quiz_attempts.php --all
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/admin/course_builder_helpers.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

$modeall = !empty($argv[1]) && ($argv[1] === '--all' || $argv[1] === 'all');
if ($modeall) {
    $courses = $DB->get_records_select('course', 'id > :site', ['site' => SITEID], 'id ASC');
    $td = 0;
    $tf = 0;
    foreach ($courses as $course) {
        $r = cleanup_broken_quiz_attempts((int)$course->id);
        if ($r['deleted'] || $r['fixedlayout']) {
            mtrace("Course {$course->shortname} ({$course->id}): deleted {$r['deleted']}, fixed layout {$r['fixedlayout']}");
        }
        $td += $r['deleted'];
        $tf += $r['fixedlayout'];
        rebuild_course_cache($course->id, true);
    }
    mtrace("Done (--all). Total deleted {$td}, fixed layout {$tf}.");
    exit(0);
}

$shortname = $argv[1] ?? 'JS-FUND-2026';
$course = $DB->get_record('course', ['shortname' => $shortname]);
if (!$course) {
    mtrace('Course not found: ' . $shortname);
    exit(1);
}

$r = cleanup_broken_quiz_attempts((int)$course->id);
rebuild_course_cache($course->id, true);
mtrace("Done. Deleted {$r['deleted']} attempts, fixed layout on {$r['fixedlayout']}.");
