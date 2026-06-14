<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
$shortname = $argv[1] ?? 'JS-FUND-2026';
$course = $DB->get_record('course', ['shortname' => $shortname]);
if (!$course) {
    mtrace('Course not found');
    exit(1);
}
$sql = 'SELECT m.name, COUNT(*) AS cnt FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module
        WHERE cm.course = ? AND cm.deletioninprogress = 0
        GROUP BY m.name ORDER BY m.name';
$rows = $DB->get_records_sql($sql, [$course->id]);
mtrace('Course ' . $course->id . ' (' . $shortname . ')');
$total = 0;
foreach ($rows as $r) {
    mtrace('  ' . $r->name . ': ' . $r->cnt);
    $total += $r->cnt;
}
mtrace('Total activities: ' . $total);
