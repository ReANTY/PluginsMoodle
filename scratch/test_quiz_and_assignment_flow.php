<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

$userid = 3;
$courseid = 22;
$section_num = 1;

echo "========================================================\n";
echo "PENGUJIAN SIKLUS PENYELESAIAN MINGGU 1 (KUIS + TUGAS)\n";
echo "========================================================\n";

// Pastikan bersih dari data test sebelumnya
$DB->delete_records('quiz_attempts', ['quiz' => 312, 'userid' => $userid]);
$DB->delete_records('aicode_attempts', ['problemid' => 330, 'userid' => $userid]);
$DB->delete_records('course_modules_completion', ['coursemoduleid' => 969, 'userid' => $userid]);
$DB->delete_records('course_modules_completion', ['coursemoduleid' => 970, 'userid' => $userid]);
$DB->delete_records('acmls_learner_record', ['userid' => $userid, 'courseid' => $courseid]);
$DB->delete_records('acmls_motivation_feedback', ['userid' => $userid, 'courseid' => $courseid]);

echo "\n--- KONDISI AWAL (Kuis: BELUM, Tugas: BELUM) ---\n";
$status0 = \block_attendanceleaderboard\event\observer::check_section_completion_status($userid, $courseid, $section_num);
echo "Quiz completed: " . ($status0['quiz_completed'] ? 'YES' : 'NO') . "\n";
echo "Assignment completed: " . ($status0['assignment_completed'] ? 'YES' : 'NO') . "\n";
echo "Section fully completed: " . ($status0['completed'] ? 'YES' : 'NO') . "\n";

echo "\n--- TAHAP 1: SISWA MENYELESAIKAN KUIS DULU (Tugas belum) ---\n";
// Masukkan attempt kuis selesai
$att = new stdClass();
$att->quiz = 312;
$att->userid = $userid;
$att->attempt = 1;
$att->uniqueid = 999999;
$att->layout = '1,0';
$att->currentpage = 0;
$att->preview = 0;
$att->sumgrades = 8.5;
$att->timestart = time() - 300;
$att->timefinish = time();
$att->timemodified = time();
$att->state = 'finished';
$att_id = $DB->insert_record('quiz_attempts', $att);

// Mark completion kuis
$comp_quiz = new stdClass();
$comp_quiz->coursemoduleid = 969;
$comp_quiz->userid = $userid;
$comp_quiz->completionstate = 1;
$comp_quiz->timemodified = time();
$DB->insert_record('course_modules_completion', $comp_quiz);

$status1 = \block_attendanceleaderboard\event\observer::check_section_completion_status($userid, $courseid, $section_num);
echo "Quiz completed: " . ($status1['quiz_completed'] ? 'YES' : 'NO') . " (Grade: " . round($status1['quiz_grade'], 1) . "%)\n";
echo "Assignment completed: " . ($status1['assignment_completed'] ? 'YES' : 'NO') . "\n";
echo "Section fully completed: " . ($status1['completed'] ? 'YES' : 'NO') . "\n";

$pending1 = $DB->get_record('acmls_learner_record', ['userid' => $userid, 'courseid' => $courseid, 'record_type' => 'pending_quiz_motivation']);
echo "Pending motivation created prematurely: " . ($pending1 ? 'YES (WRONG)' : 'NO (CORRECT, WAITING FOR ASSIGNMENT)') . "\n";

echo "\n--- TAHAP 2: SISWA MENGIRIM TUGAS AICODE (Kirim ke Guru) ---\n";
// Simulasikan siswa klik send_to_teacher
$ai_att = new stdClass();
$ai_att->problemid = 330;
$ai_att->userid = $userid;
$ai_att->code_hash = 'testhash';
$ai_att->is_anonymous = 0;
$ai_att->teacher_review_requested = 1;
$ai_att->result_json = json_encode(['teacher_review_requested' => true, 'code' => 'console.log("hello");']);
$ai_att->timecreated = time();
$ai_att_id = $DB->insert_record('aicode_attempts', $ai_att);

$comp_assign = new stdClass();
$comp_assign->coursemoduleid = 970;
$comp_assign->userid = $userid;
$comp_assign->completionstate = 1;
$comp_assign->timemodified = time();
$DB->insert_record('course_modules_completion', $comp_assign);

$cm_assign = $DB->get_record('course_modules', ['id' => 970]);
\block_attendanceleaderboard\event\observer::assignment_submitted_for_motivation($cm_assign, $userid);

$status2 = \block_attendanceleaderboard\event\observer::check_section_completion_status($userid, $courseid, $section_num);
echo "Quiz completed: " . ($status2['quiz_completed'] ? 'YES' : 'NO') . "\n";
echo "Assignment completed: " . ($status2['assignment_completed'] ? 'YES' : 'NO') . "\n";
echo "Section fully completed: " . ($status2['completed'] ? 'YES' : 'NO') . "\n";

$pending2 = $DB->get_record('acmls_learner_record', ['userid' => $userid, 'courseid' => $courseid, 'record_type' => 'pending_quiz_motivation']);
echo "Pending motivation created upon assignment submit: " . ($pending2 ? 'YES (CORRECT!)' : 'NO (ERROR)') . "\n";
if ($pending2) {
    $payload = json_decode($pending2->data_payload, true);
    echo "  Payload Section: " . ($payload['section'] ?? 'none') . "\n";
    echo "  Payload Content: " . ($payload['content'] ?? 'none') . "\n";
    echo "  Payload Suggestion: " . ($payload['suggestion'] ?? 'none') . "\n";
}

// Cek status delivery_system post-emotion
$delivery = new \block_attendanceleaderboard\delivery\delivery_system();
$pending_mot = $delivery->get_pending_quiz_motivation($userid, $courseid);
$emotion_state = $delivery->resolve_emotion_checkin_state($userid, $courseid, $pending_mot);
echo "Post-emotion checkin triggered: " . ($emotion_state['show'] && $emotion_state['stage'] === 'post' ? 'YES (CORRECT!)' : 'NO') . "\n";
echo "Post-emotion title: " . ($emotion_state['title'] ?? 'none') . "\n";

echo "\n--- PEMBERSIHAN DATA PENGUJIAN ---\n";
$DB->delete_records('quiz_attempts', ['id' => $att_id]);
$DB->delete_records('aicode_attempts', ['id' => $ai_att_id]);
$DB->delete_records('course_modules_completion', ['coursemoduleid' => 969, 'userid' => $userid]);
$DB->delete_records('course_modules_completion', ['coursemoduleid' => 970, 'userid' => $userid]);
$DB->delete_records('acmls_learner_record', ['userid' => $userid, 'courseid' => $courseid]);
echo "Test records cleared.\n";
