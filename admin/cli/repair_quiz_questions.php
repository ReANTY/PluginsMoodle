<?php
/**
 * Repair quiz questions missing question_references (Moodle 5+).
 *
 * Usage:
 *   php admin/cli/repair_quiz_questions.php [shortname]
 *   php admin/cli/repair_quiz_questions.php --all
 *
 * --all : Perbaiki semua kuis di semua course yang slot-nya tidak punya question_references.
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/admin/course_builder_helpers.php');

/**
 * Rebuild slots for one quiz when references are missing.
 *
 * @return int Number of questions re-linked (0 if skipped).
 */
function admin_cli_repair_one_quiz(stdClass $quiz, stdClass $course): int {
    global $DB;

    $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id);
    $quiz->cmid = $cm->id;
    $context = context_module::instance($cm->id);

    $slots = $DB->get_records('quiz_slots', ['quizid' => $quiz->id], 'slot ASC');
    if (empty($slots)) {
        return 0;
    }

    $needsrebuild = false;
    foreach ($slots as $slot) {
        $ref = $DB->get_record('question_references', [
            'component' => 'mod_quiz',
            'questionarea' => 'slot',
            'itemid' => $slot->id,
        ]);
        if (!$ref) {
            $needsrebuild = true;
            break;
        }
    }

    if (!$needsrebuild) {
        return 0;
    }

    mtrace('Rebuilding: ' . $quiz->name . ' (course ' . $course->shortname . ')');

    $category = $DB->get_record('question_categories', [
        'contextid' => $context->id,
        'name' => $quiz->name . ' Questions',
    ]);

    $questionids = [];
    if ($category) {
        $sql = "SELECT q.id
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id AND qv.status = 'ready'
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                 WHERE qbe.questioncategoryid = ?
              ORDER BY q.id ASC";
        $questionids = $DB->get_fieldset_sql($sql, [$category->id]);
    }

    $oldattempts = $DB->get_records('quiz_attempts', ['quiz' => $quiz->id]);
    foreach ($oldattempts as $attempt) {
        quiz_delete_attempt($attempt, $quiz);
    }
    if ($oldattempts) {
        mtrace('  Deleted ' . count($oldattempts) . ' stale attempt(s)');
    }

    foreach ($slots as $slot) {
        $DB->delete_records('question_references', [
            'component' => 'mod_quiz',
            'questionarea' => 'slot',
            'itemid' => $slot->id,
        ]);
        $DB->delete_records('quiz_slots', ['id' => $slot->id]);
    }

    if (empty($questionids)) {
        mtrace('  WARNING: No questions found in category — skip');
        return 0;
    }

    $page = 1;
    $linked = 0;
    foreach ($questionids as $qid) {
        quiz_add_quiz_question($qid, $quiz, $page, 1.0);
        $page++;
        $linked++;
    }

    $sumgrades = $DB->get_field_sql('SELECT SUM(maxmark) FROM {quiz_slots} WHERE quizid = ?', [$quiz->id]);
    $DB->set_field('quiz', 'sumgrades', $sumgrades ?: count($questionids), ['id' => $quiz->id]);

    return $linked;
}

$modeall = !empty($argv[1]) && ($argv[1] === '--all' || $argv[1] === 'all');
$fixed = 0;
$rebuilt = 0;
$coursesdone = [];

if ($modeall) {
    $sql = "SELECT DISTINCT qs.quizid
              FROM {quiz_slots} qs
              LEFT JOIN {question_references} qr
                ON qr.itemid = qs.id AND qr.component = 'mod_quiz' AND qr.questionarea = 'slot'
             WHERE qr.id IS NULL";
    $quizids = $DB->get_fieldset_sql($sql);
    mtrace('Found ' . count($quizids) . ' quiz(es) with missing question_references (--all)');

    foreach ($quizids as $quizid) {
        $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', IGNORE_MISSING);
        if (!$quiz) {
            continue;
        }
        $course = $DB->get_record('course', ['id' => $quiz->course], '*', IGNORE_MISSING);
        if (!$course || $course->id == SITEID) {
            continue;
        }
        $n = admin_cli_repair_one_quiz($quiz, $course);
        if ($n > 0) {
            $fixed += $n;
            $rebuilt++;
            $coursesdone[$course->id] = true;
        }
    }

    foreach (array_keys($coursesdone) as $courseid) {
        rebuild_course_cache($courseid, true);
    }

    mtrace("Done (--all). Rebuilt {$rebuilt} quizzes, linked {$fixed} questions.");
    mtrace('Run: php admin/cli/fix_quiz_attempts.php --all   (to clear broken preview attempts)');
    exit(0);
}

$shortname = $argv[1] ?? 'JS-FUND-2026';
$course = $DB->get_record('course', ['shortname' => $shortname]);
if (!$course) {
    mtrace('Course not found: ' . $shortname);
    mtrace('Tip: use php admin/cli/repair_quiz_questions.php --all  to fix every course.');
    exit(1);
}

$quizzes = $DB->get_records('quiz', ['course' => $course->id]);
mtrace('Repairing ' . count($quizzes) . ' quizzes in ' . $shortname);

foreach ($quizzes as $quiz) {
    $n = admin_cli_repair_one_quiz($quiz, $course);
    if ($n > 0) {
        $fixed += $n;
        $rebuilt++;
    }
}

rebuild_course_cache($course->id, true);
mtrace("Done. Rebuilt {$rebuilt} quizzes, linked {$fixed} questions.");
