<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $USER, $COURSE, $PAGE;

$USER = $DB->get_record('user', ['id' => 3]);
$COURSE = $DB->get_record('course', ['id' => 22]);

echo "isloggedin: " . (isloggedin() ? 'YES' : 'NO') . "\n";
echo "USER->id: " . ($USER->id ?? 0) . "\n";

$courseid = (int)$COURSE->id;
echo "is_student_user: " . (\local_llmmotivation\delivery\delivery_system::is_student_user(3, $courseid) ? 'YES' : 'NO') . "\n";

$delivery = new \local_llmmotivation\delivery\delivery_system();
$pending = $delivery->get_pending_quiz_motivation(3, $courseid);
echo "pending: " . ($pending ? 'YES' : 'NO') . "\n";

$state = $delivery->resolve_emotion_checkin_state(3, $courseid, $pending);
echo "state:\n";
print_r($state);
