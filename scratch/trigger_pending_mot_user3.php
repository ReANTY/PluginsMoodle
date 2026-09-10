<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

$userid = 3;
$courseid = 22;
$section_num = 1;

$status = \block_attendanceleaderboard\event\observer::check_section_completion_status($userid, $courseid, $section_num);
echo "Status check: completed = " . ($status['completed'] ? 'YES' : 'NO') . "\n";
print_r($status);

// Panggil trigger section completion motivation
$reflectionMethod = new ReflectionMethod('\block_attendanceleaderboard\event\observer', 'trigger_section_completion_motivation');
$reflectionMethod->setAccessible(true);
$reflectionMethod->invoke(null, $userid, $courseid, $section_num, $status, null);

$delivery = new \block_attendanceleaderboard\delivery\delivery_system();
$pending_mot = $delivery->get_pending_quiz_motivation($userid, $courseid);
echo "\nPending motivation after trigger:\n";
print_r($pending_mot);

$emotion_state = $delivery->resolve_emotion_checkin_state($userid, $courseid, $pending_mot);
echo "\nEmotion state after trigger:\n";
print_r($emotion_state);
