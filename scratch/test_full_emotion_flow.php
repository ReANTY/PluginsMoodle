<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

$userid = 3;
$courseid = 22;

$delivery = new \block_attendanceleaderboard\delivery\delivery_system();

echo "=== TAHAP 1: KONDISI AWAL SISWA DI MINGGU 1 ===\n";
$state = $delivery->resolve_emotion_checkin_state($userid, $courseid);
echo "Show: " . ($state['show'] ? 'YES' : 'NO') . "\n";
echo "Section: " . ($state['section'] ?? 'none') . "\n";
echo "Stage: " . ($state['stage'] ?? 'none') . "\n";
echo "Category: " . ($state['category'] ?? 'none') . "\n";
echo "Title: " . ($state['title'] ?? 'none') . "\n";
echo "Submit: " . ($state['submit'] ?? 'none') . "\n";

$html = $delivery->display_emotion_checkin($userid, $courseid, $state);
echo "HTML contains data-stage=\"pre\": " . (strpos($html, 'data-stage="pre"') !== false ? 'YES' : 'NO') . "\n";
echo "HTML contains data-section=\"1\": " . (strpos($html, 'data-section="1"') !== false ? 'YES' : 'NO') . "\n";

echo "\n=== TAHAP 2: SISWA MENGISI FORM EMOSI AWAL MINGGU 1 ===\n";
$f1 = new stdClass();
$f1->userid = $userid;
$f1->courseid = $courseid;
$f1->sentenceid = 0;
$f1->category = 'checkin_section_1';
$f1->source = 'section_pre';
$f1->message_content = 'Check-in Kesiapan Emosi Awal Minggu 1';
$f1->e1 = 4;
$f1->e2 = 5;
$f1->e3 = 4;
$f1->reflection_note = 'Semangat belajar';
$f1->timecreated = time();
$f1_id = $DB->insert_record('acmls_motivation_feedback', $f1);

$state2 = $delivery->resolve_emotion_checkin_state($userid, $courseid);
echo "Show after Pre-Checkin submitted: " . ($state2['show'] ? 'YES' : 'NO') . "\n";

echo "\n=== TAHAP 3: SISWA MENYELESAIKAN KUIS MINGGU 1 ===\n";
// Simulasikan record pending_quiz_motivation
$rec = new stdClass();
$rec->userid = $userid;
$rec->courseid = $courseid;
$rec->record_type = 'pending_quiz_motivation';
$rec->data_payload = json_encode([
    'quizgrade' => 85.0,
    'quizname' => 'Kuis Minggu 1',
    'category' => 'achievement',
    'source' => 'gemini',
    'section' => 1,
    'content' => 'Hebat! Kamu telah menyelesaikan Minggu 1 dengan gemilang!',
    'suggestion' => 'Pertahankan ritme belajarmu!',
]);
$rec->timecreated = time();
$rec_id = $DB->insert_record('acmls_learner_record', $rec);

$pending = $delivery->get_pending_quiz_motivation($userid, $courseid);
echo "Pending quiz motivation found: " . ($pending ? 'YES' : 'NO') . " (Section: " . ($pending['section'] ?? 'none') . ")\n";

$state3 = $delivery->resolve_emotion_checkin_state($userid, $courseid, $pending);
echo "Show Post-Emotion Checkin: " . ($state3['show'] ? 'YES' : 'NO') . "\n";
echo "Stage: " . ($state3['stage'] ?? 'none') . "\n";
echo "Section: " . ($state3['section'] ?? 'none') . "\n";
echo "Category: " . ($state3['category'] ?? 'none') . "\n";
echo "Title: " . ($state3['title'] ?? 'none') . "\n";

$post_html = $delivery->display_emotion_checkin($userid, $courseid, $state3);
echo "HTML contains data-stage=\"post\": " . (strpos($post_html, 'data-stage="post"') !== false ? 'YES' : 'NO') . "\n";

$quiz_html = $delivery->display_quiz_motivation($userid, $courseid, $pending, true);
echo "Quiz modal has 'is-visible' while wait_for_emotion=true: " . (strpos($quiz_html, 'is-visible') !== false ? 'YES (WRONG)' : 'NO (CORRECT, HIDDEN)') . "\n";

echo "\n=== TAHAP 4: SISWA MENGISI FORM EMOSI AKHIR MINGGU 1 ===\n";
$f2 = new stdClass();
$f2->userid = $userid;
$f2->courseid = $courseid;
$f2->sentenceid = 0;
$f2->category = 'post_section_1';
$f2->source = 'section_post';
$f2->message_content = 'Evaluasi Emosi Akhir Minggu 1';
$f2->e1 = 5;
$f2->e2 = 5;
$f2->e3 = 5;
$f2->reflection_note = 'Sangat puas dengan kuis minggu 1';
$f2->timecreated = time();
$f2_id = $DB->insert_record('acmls_motivation_feedback', $f2);

$state4 = $delivery->resolve_emotion_checkin_state($userid, $courseid, $pending);
echo "Show Post-Emotion after submitted: " . ($state4['show'] ? 'YES' : 'NO') . "\n";

echo "\n=== CLEANUP SIMULASI DATA TESTING ===\n";
$DB->delete_records('acmls_motivation_feedback', ['id' => $f1_id]);
$DB->delete_records('acmls_motivation_feedback', ['id' => $f2_id]);
$DB->delete_records('acmls_learner_record', ['id' => $rec_id]);
echo "Test records deleted cleanly.\n";
