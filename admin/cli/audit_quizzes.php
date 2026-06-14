<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/classes/question/bank/qbank_helper.php');

$shortname = $argv[1] ?? 'JS-FUND-2026';
$course = $DB->get_record('course', ['shortname' => $shortname]);
if (!$course) {
    mtrace('Course not found: ' . $shortname);
    exit(1);
}

$quizzes = $DB->get_records('quiz', ['course' => $course->id]);
$issues = 0;
mtrace('Auditing ' . count($quizzes) . ' quizzes in ' . $shortname);

foreach ($quizzes as $quiz) {
    $slots = $DB->count_records('quiz_slots', ['quizid' => $quiz->id]);
    $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, IGNORE_MISSING);
    if (!$cm) {
        mtrace("FAIL {$quiz->name}: no course module");
        $issues++;
        continue;
    }
    $ctx = context_module::instance($cm->id);
    try {
        $struct = mod_quiz\question\bank\qbank_helper::get_question_structure($quiz->id, $ctx);
        $missing = 0;
        foreach ($struct as $s) {
            if ($s->qtype === 'missingtype') {
                $missing++;
            }
        }
        if ($slots == 0) {
            mtrace("FAIL {$quiz->name}: 0 slots");
            $issues++;
        } else if ($missing) {
            mtrace("FAIL {$quiz->name}: {$missing} missingtype slot(s)");
            $issues++;
        } else if (count($struct) != $slots) {
            mtrace("FAIL {$quiz->name}: structure " . count($struct) . " vs slots {$slots}");
            $issues++;
        }
    } catch (Throwable $e) {
        mtrace("FAIL {$quiz->name}: " . $e->getMessage());
        $issues++;
    }

    $attempts = $DB->get_records('quiz_attempts', ['quiz' => $quiz->id]);
    foreach ($attempts as $a) {
        $sc = $DB->count_records('question_attempts', ['questionusageid' => $a->uniqueid]);
        $layoutempty = ($a->layout === '' || $a->layout === null);
        if ($layoutempty || $sc == 0) {
            mtrace("  BROKEN attempt {$a->id} on {$quiz->name}");
            $issues++;
        }
    }
}

mtrace($issues ? "Done with {$issues} issue(s)." : 'OK: all quizzes and attempts look valid.');
