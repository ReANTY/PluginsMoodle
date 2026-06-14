<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/classes/question/bank/qbank_helper.php');

$quizid = (int)($argv[1] ?? 184);
$cm = get_coursemodule_from_instance('quiz', $quizid);
$context = context_module::instance($cm->id);
$structure = mod_quiz\question\bank\qbank_helper::get_question_structure($quizid, $context);
$ok = true;
foreach ($structure as $slot) {
    $status = ($slot->qtype === 'missingtype') ? 'MISSING' : $slot->qtype;
    mtrace('  slot ' . $slot->slot . ': ' . $status);
    if ($slot->qtype === 'missingtype') {
        $ok = false;
    }
}
mtrace($ok ? 'OK: all slots valid' : 'FAIL: invalid slots remain');
