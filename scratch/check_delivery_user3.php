<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

$userid = 3;
$courseid = 22;

$delivery = new \block_attendanceleaderboard\delivery\delivery_system();

$pending_mot = $delivery->get_pending_quiz_motivation($userid, $courseid);
echo "Pending motivation: " . ($pending_mot ? 'YES' : 'NO') . "\n";
if ($pending_mot) {
    echo "  Section: " . ($pending_mot['section'] ?? 'none') . "\n";
    echo "  Content: " . ($pending_mot['content'] ?? 'none') . "\n";
}

$state = $delivery->resolve_emotion_checkin_state($userid, $courseid, $pending_mot);
echo "Emotion checkin state:\n";
print_r($state);
