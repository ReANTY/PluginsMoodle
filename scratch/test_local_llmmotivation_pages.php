<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/local/llmmotivation/lib.php');

global $USER, $COURSE, $PAGE;

$USER = $DB->get_record('user', ['id' => 3]);
$COURSE = $DB->get_record('course', ['id' => 22]);
$PAGE->set_course($COURSE);
$PAGE->set_url('/course/view.php', ['id' => 22]);

echo "=== TEST 1: BEFORE_FOOTER DI HALAMAN KURSUS ===\n";
ob_start();
local_llmmotivation_before_footer();
$output1 = ob_get_clean();

echo "Output length: " . strlen($output1) . "\n";
echo "Contains emotion popup: " . (strpos($output1, 'acmls-emotion-popup') !== false ? 'YES' : 'NO') . "\n";
if (strpos($output1, 'acmls-emotion-popup') !== false) {
    preg_match('/<p class="acmls-motivation-popup__eyebrow">(.*?)<\/p>/', $output1, $m);
    echo "Popup title: " . ($m[1] ?? 'unknown') . "\n";
}

echo "\n=== TEST 2: BEFORE_FOOTER DI HALAMAN KUIS (mod_quiz, tanpa blok) ===\n";
$PAGE->set_url('/mod/quiz/view.php', ['id' => 969]);
ob_start();
local_llmmotivation_before_footer();
$output2 = ob_get_clean();
echo "Output length: " . strlen($output2) . "\n";
echo "Contains emotion popup: " . (strpos($output2, 'acmls-emotion-popup') !== false ? 'YES' : 'NO') . "\n";

echo "\n=== TEST 3: BEFORE_FOOTER DI HALAMAN AICODE (mod_aicode, tanpa blok) ===\n";
$PAGE->set_url('/mod/aicode/view.php', ['id' => 970]);
ob_start();
local_llmmotivation_before_footer();
$output3 = ob_get_clean();
echo "Output length: " . strlen($output3) . "\n";
echo "Contains emotion popup: " . (strpos($output3, 'acmls-emotion-popup') !== false ? 'YES' : 'NO') . "\n";
