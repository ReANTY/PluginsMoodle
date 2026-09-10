<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

$userid = 3;
$courseid = 22;
$section_num = 1;

echo "========================================================\n";
echo "PENGUJIAN URUTAN TERBALIK (TUGAS DULU, KUIS BELAKANGAN)\n";
echo "========================================================\n";

// Bersihkan data
$DB->delete_records('quiz_attempts', ['quiz' => 312, 'userid' => $userid]);
$DB->delete_records('aicode_attempts', ['problemid' => 330, 'userid' => $userid]);
$DB->delete_records('course_modules_completion', ['coursemoduleid' => 969, 'userid' => $userid]);
$DB->delete_records('course_modules_completion', ['coursemoduleid' => 970, 'userid' => $userid]);
$DB->delete_records('acmls_learner_record', ['userid' => $userid, 'courseid' => $courseid]);
$DB->delete_records('acmls_motivation_feedback', ['userid' => $userid, 'courseid' => $courseid]);

echo "\n--- TAHAP 1: SISWA KIRIM TUGAS DULU (Kuis belum) ---\n";
$ai_att = new stdClass();
$ai_att->problemid = 330;
$ai_att->userid = $userid;
$ai_att->code_hash = 'testhash2';
$ai_att->is_anonymous = 0;
$ai_att->teacher_review_requested = 1;
$ai_att->result_json = json_encode(['teacher_review_requested' => true]);
$ai_att->timecreated = time();
$ai_att_id = $DB->insert_record('aicode_attempts', $ai_att);

$cm_assign = $DB->get_record('course_modules', ['id' => 970]);
\block_attendanceleaderboard\event\observer::assignment_submitted_for_motivation($cm_assign, $userid);

$status1 = \block_attendanceleaderboard\event\observer::check_section_completion_status($userid, $courseid, $section_num);
echo "Quiz completed: " . ($status1['quiz_completed'] ? 'YES' : 'NO') . "\n";
echo "Assignment completed: " . ($status1['assignment_completed'] ? 'YES' : 'NO') . "\n";
echo "Section fully completed: " . ($status1['completed'] ? 'YES' : 'NO') . "\n";

$pending1 = $DB->get_record('acmls_learner_record', ['userid' => $userid, 'courseid' => $courseid, 'record_type' => 'pending_quiz_motivation']);
echo "Pending motivation created prematurely: " . ($pending1 ? 'YES (WRONG)' : 'NO (CORRECT, WAITING FOR QUIZ)') . "\n";

echo "\n--- TAHAP 2: SISWA MENYELESAIKAN KUIS ---\n";
$att = new stdClass();
$att->quiz = 312;
$att->userid = $userid;
$att->attempt = 1;
$att->uniqueid = 999998;
$att->layout = '1,0';
$att->currentpage = 0;
$att->preview = 0;
$att->sumgrades = 9.0;
$att->timestart = time() - 200;
$att->timefinish = time();
$att->timemodified = time();
$att->state = 'finished';
$att_id = $DB->insert_record('quiz_attempts', $att);

// Trigger observer kuis
$quiz = $DB->get_record('quiz', ['id' => 312]);
$cm_quiz = $DB->get_record('course_modules', ['id' => 969]);
$event = \mod_quiz\event\attempt_submitted::create([
    'objectid' => $att_id,
    'relateduserid' => $userid,
    'context' => context_module::instance(969),
    'other' => [
        'quizid' => 312,
        'attemptid' => $att_id,
        'submitterid' => $userid,
    ],
]);
\block_attendanceleaderboard\event\observer::quiz_attempt_submitted($event);

$status2 = \block_attendanceleaderboard\event\observer::check_section_completion_status($userid, $courseid, $section_num);
echo "Quiz completed: " . ($status2['quiz_completed'] ? 'YES' : 'NO') . "\n";
echo "Assignment completed: " . ($status2['assignment_completed'] ? 'YES' : 'NO') . "\n";
echo "Section fully completed: " . ($status2['completed'] ? 'YES' : 'NO') . "\n";

$pending2 = $DB->get_record('acmls_learner_record', ['userid' => $userid, 'courseid' => $courseid, 'record_type' => 'pending_quiz_motivation']);
echo "Pending motivation created upon quiz submit: " . ($pending2 ? 'YES (CORRECT!)' : 'NO (ERROR)') . "\n";

echo "\n--- PEMBERSIHAN DATA PENGUJIAN ---\n";
$DB->delete_records('quiz_attempts', ['id' => $att_id]);
$DB->delete_records('aicode_attempts', ['id' => $ai_att_id]);
$DB->delete_records('acmls_learner_record', ['userid' => $userid, 'courseid' => $courseid]);
echo "Cleaned up.\n";
