<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/local/llmmotivation/lib.php');

global $USER, $COURSE, $PAGE, $DB;

$USER = $DB->get_record('user', ['id' => 3]);
$COURSE = $DB->get_record('course', ['id' => 22]);
$PAGE->set_course($COURSE);
$PAGE->set_url('/mod/quiz/view.php', ['id' => 969]);

// Masukkan pending motivation test untuk Section 4
$rec = new stdClass();
$rec->userid = 3;
$rec->courseid = 22;
$rec->record_type = 'pending_quiz_motivation';
$rec->data_payload = json_encode([
    'quizgrade' => 90.0,
    'section' => 4,
    'content' => 'Selamat, kamu hebat di Minggu 4!',
    'suggestion' => 'Terus pertahankan!',
]);
$rec->timecreated = time();
$rec_id = $DB->insert_record('acmls_learner_record', $rec);

echo "=== TEST: BEFORE_FOOTER DI HALAMAN KUIS SETELAH SELESAI (TANPA BLOK) ===\n";
ob_start();
local_llmmotivation_before_footer();
$output = ob_get_clean();

echo "Output HTML length: " . strlen($output) . " bytes\n";
echo "Contains post-emotion popup: " . (strpos($output, 'Evaluasi Emosi Akhir — Minggu 4') !== false ? 'YES (SUCCESS!)' : 'NO') . "\n";
echo "Contains quiz motivation popup: " . (strpos($output, 'Selamat, kamu hebat di Minggu 4!') !== false ? 'YES (SUCCESS!)' : 'NO') . "\n";

// Bersihkan
$DB->delete_records('acmls_learner_record', ['id' => $rec_id]);
echo "Test completed cleanly.\n";
