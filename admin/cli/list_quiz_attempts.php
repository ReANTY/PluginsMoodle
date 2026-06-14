<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');

$quizid = (int)($argv[1] ?? 183);
$attempts = $DB->get_records('quiz_attempts', ['quiz' => $quizid], 'id DESC', '*', 0, 15);
mtrace('Attempts for quiz ' . $quizid . ': ' . count($attempts));
foreach ($attempts as $x) {
    $c = $DB->count_records('question_attempts', ['questionusageid' => $x->uniqueid]);
    $layout = $x->layout ?? '(null)';
    mtrace("id={$x->id} preview={$x->preview} state={$x->state} qas={$c} layout={$layout}");
}
