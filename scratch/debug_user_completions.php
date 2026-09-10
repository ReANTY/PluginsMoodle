<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

// Cari user yang baru aktif
$recent_users = $DB->get_records_sql("SELECT id, username, firstname, lastname, lastaccess, currentlogin FROM {user} WHERE lastaccess > ? ORDER BY lastaccess DESC", [time() - 7200]);
echo "Recent users:\n";
foreach ($recent_users as $u) {
    echo "User ID {$u->id}: {$u->username} ({$u->firstname} {$u->lastname}) | Last access: " . date('Y-m-d H:i:s', $u->lastaccess) . "\n";
}

$user = reset($recent_users);
$userid = $user ? $user->id : 3;
echo "\nInspecting for User ID: {$userid}\n";

// Cek attempt kuis untuk user ini di course 22
$attempts = $DB->get_records_sql("SELECT qa.id, qa.quiz, q.name, qa.state, qa.sumgrades, qa.timefinish 
                                     FROM {quiz_attempts} qa 
                                     JOIN {quiz} q ON q.id = qa.quiz 
                                    WHERE qa.userid = ? AND q.course = 22", [$userid]);
echo "\nQuiz attempts for user {$userid}:\n";
foreach ($attempts as $att) {
    echo "Attempt ID {$att->id} | Quiz ID {$att->quiz}: {$att->name} | State: {$att->state} | Grade: {$att->sumgrades} | Finish: " . date('Y-m-d H:i:s', $att->timefinish) . "\n";
}

// Cek completion records untuk user ini di course 22
$completions = $DB->get_records_sql("SELECT cmc.id, cmc.coursemoduleid, cm.module, m.name as modname, cmc.completionstate, cmc.viewed, cmc.timemodified
                                       FROM {course_modules_completion} cmc
                                       JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                                       JOIN {modules} m ON m.id = cm.module
                                      WHERE cmc.userid = ? AND cm.course = 22
                                   ORDER BY cmc.coursemoduleid", [$userid]);
echo "\nCompletion records for user {$userid}:\n";
foreach ($completions as $c) {
    $inst = $DB->get_record($c->modname, ['id' => $DB->get_field('course_modules', 'instance', ['id' => $c->coursemoduleid])], 'name');
    $name = $inst ? $inst->name : '';
    echo "CMID {$c->coursemoduleid} ({$c->modname} - {$name}): state = {$c->completionstate}\n";
}
