<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/classes/question/bank/qbank_helper.php');

$cmid = (int)($argv[1] ?? 587);
$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cmid);

mtrace("Quiz: {$quiz->id} - {$quiz->name}");
mtrace("sumgrades: {$quiz->sumgrades}");

$slots = $DB->get_records('quiz_slots', ['quizid' => $quiz->id], 'slot ASC');
mtrace('Slots in DB: ' . count($slots));

foreach ($slots as $s) {
    $ref = $DB->get_record('question_references', [
        'component' => 'mod_quiz',
        'questionarea' => 'slot',
        'itemid' => $s->id,
        'usingcontextid' => $context->id,
    ]);
    mtrace("  slot {$s->slot} (id={$s->id}): ref=" . ($ref ? "qbe={$ref->questionbankentryid}" : 'NONE'));
}

try {
    $structure = mod_quiz\question\bank\qbank_helper::get_question_structure($quiz->id, $context);
    mtrace('Structure count: ' . count($structure));
    foreach ($structure as $slot) {
        mtrace("  structure slot {$slot->slot}: qtype={$slot->qtype}, qid={$slot->questionid}");
    }
} catch (Throwable $e) {
    mtrace('Structure error: ' . $e->getMessage());
}

$cat = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => $quiz->name . ' Questions']);
if ($cat) {
    $sql = "SELECT q.id, q.name, q.qtype
              FROM {question} q
              JOIN {question_versions} qv ON qv.questionid = q.id
              JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
             WHERE qbe.questioncategoryid = ?
          ORDER BY q.id";
    $qs = $DB->get_records_sql($sql, [$cat->id]);
    mtrace('Questions in category "' . $cat->name . '": ' . count($qs));
    foreach ($qs as $q) {
        mtrace("  q{$q->id}: [{$q->qtype}] " . substr($q->name, 0, 60));
    }
}
