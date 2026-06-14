<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

$attemptid = (int)($argv[1] ?? 108);
$attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $attempt->quiz], '*', MUST_EXIST);

mtrace("Attempt {$attempt->id} quiz={$quiz->name} state={$attempt->state}");
mtrace("layout: [" . $attempt->layout . "]");
mtrace("uniqueid (question usage): {$attempt->uniqueid}");
mtrace("preview: {$attempt->preview}");

$qacount = $DB->count_records('question_attempts', ['questionusageid' => $attempt->uniqueid]);
mtrace("question_attempts in usage: {$qacount}");

$slots = $DB->get_records('quiz_slots', ['quizid' => $quiz->id], 'slot ASC');
mtrace('quiz_slots: ' . count($slots));

$cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course);
$quizobj = mod_quiz\quiz_settings::create($quiz->id, $attempt->userid);
$attemptobj = $quizobj->create_attempt_object($attempt);

try {
    $pageslots = $attemptobj->get_slots(1);
    mtrace('get_slots(1) count: ' . count($pageslots));
} catch (Throwable $e) {
    mtrace('get_slots error: ' . $e->getMessage());
}

if ($qacount) {
    $qas = $DB->get_records('question_attempts', ['questionusageid' => $attempt->uniqueid], 'slot ASC');
    foreach ($qas as $qa) {
        mtrace("  qa slot {$qa->slot} questionid={$qa->questionid}");
    }
}
