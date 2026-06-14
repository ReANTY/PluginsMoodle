<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');

$quizid = (int)($argv[1] ?? 183);
$count = $DB->count_records('quiz_sections', ['quizid' => $quizid]);
mtrace("quiz_sections for quiz {$quizid}: {$count}");
foreach ($DB->get_records('quiz_sections', ['quizid' => $quizid]) as $s) {
    mtrace("  id={$s->id} firstslot={$s->firstslot} shuffle={$s->shufflequestions}");
}

$course = $DB->get_record('course', ['shortname' => $argv[2] ?? 'JS-FUND-2026']);
if ($course) {
    $missing = 0;
    $quizzes = $DB->get_records('quiz', ['course' => $course->id]);
    foreach ($quizzes as $quiz) {
        if (!$DB->record_exists('quiz_sections', ['quizid' => $quiz->id])) {
            mtrace("MISSING section: {$quiz->name} (id {$quiz->id})");
            $missing++;
        }
    }
    mtrace("Total missing quiz_sections: {$missing} / " . count($quizzes));
}
