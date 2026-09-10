<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB;

$courseid = 22;
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cinfo = new completion_info($course);

echo "1. Mengupdate aturan completion kuis di Course 22...\n";
// Ambil semua CM kuis di course 22
$quiz_cms = $DB->get_records_sql("SELECT cm.id, cm.instance
                                     FROM {course_modules} cm
                                     JOIN {modules} m ON m.id = cm.module
                                    WHERE cm.course = ? AND m.name = 'quiz'", [$courseid]);

$count_quiz = 0;
foreach ($quiz_cms as $cm) {
    $DB->set_field('course_modules', 'completionpassgrade', 0, ['id' => $cm->id]);
    $DB->set_field('course_modules', 'completiongradeitemnumber', 0, ['id' => $cm->id]);
    $count_quiz++;
}
echo "   -> Diperbarui {$count_quiz} modul kuis (completionpassgrade = 0, completiongradeitemnumber = 0).\n";

echo "2. Menyinkronkan completion kuis untuk semua siswa yang sudah menyelesaikan attempt...\n";
$finished_attempts = $DB->get_records_sql("SELECT qa.id, qa.quiz, qa.userid, cm.id as cmid
                                             FROM {quiz_attempts} qa
                                             JOIN {course_modules} cm ON cm.instance = qa.quiz AND cm.course = ?
                                             JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
                                            WHERE qa.state = 'finished'", [$courseid]);

$synced_quiz = 0;
foreach ($finished_attempts as $fa) {
    $existing = $DB->get_record('course_modules_completion', ['coursemoduleid' => $fa->cmid, 'userid' => $fa->userid]);
    if ($existing) {
        if ((int)$existing->completionstate !== 1) {
            $existing->completionstate = 1;
            $existing->timemodified = time();
            $DB->update_record('course_modules_completion', $existing);
            $synced_quiz++;
        }
    } else {
        $c = new stdClass();
        $c->coursemoduleid = $fa->cmid;
        $c->userid = $fa->userid;
        $c->completionstate = 1;
        $c->viewed = 0;
        $c->timemodified = time();
        $DB->insert_record('course_modules_completion', $c);
        $synced_quiz++;
    }
}
echo "   -> Disinkronkan {$synced_quiz} record kuis selesai.\n";

echo "3. Menyinkronkan completion praktik AICode untuk siswa yang memiliki attempt...\n";
$aicode_attempts = $DB->get_records_sql("SELECT MIN(aa.id) as id, aa.problemid, aa.userid, cm.id as cmid
                                           FROM {aicode_attempts} aa
                                           JOIN {course_modules} cm ON cm.instance = aa.problemid AND cm.course = ?
                                           JOIN {modules} m ON m.id = cm.module AND m.name = 'aicode'
                                          WHERE aa.userid > 0
                                       GROUP BY aa.problemid, aa.userid, cm.id", [$courseid]);

$synced_aicode = 0;
foreach ($aicode_attempts as $aa) {
    $existing = $DB->get_record('course_modules_completion', ['coursemoduleid' => $aa->cmid, 'userid' => $aa->userid]);
    if ($existing) {
        if ((int)$existing->completionstate !== 1) {
            $existing->completionstate = 1;
            $existing->viewed = 1;
            $existing->timemodified = time();
            $DB->update_record('course_modules_completion', $existing);
            $synced_aicode++;
        }
    } else {
        $c = new stdClass();
        $c->coursemoduleid = $aa->cmid;
        $c->userid = $aa->userid;
        $c->completionstate = 1;
        $c->viewed = 1;
        $c->timemodified = time();
        $DB->insert_record('course_modules_completion', $c);
        $synced_aicode++;
    }
}
echo "   -> Disinkronkan {$synced_aicode} record praktik AICode.\n";

echo "4. Memastikan modul materi bacaan yang telah diakses ditandai selesai...\n";
// Ambil log view page di course 22
$page_views = $DB->get_records_sql("SELECT cm.id as cmid, l.userid
                                      FROM {logstore_standard_log} l
                                      JOIN {course_modules} cm ON cm.id = l.contextinstanceid
                                      JOIN {modules} m ON m.id = cm.module AND m.name = 'page'
                                     WHERE l.courseid = ? AND l.action = 'viewed'
                                  GROUP BY cm.id, l.userid", [$courseid]);

$synced_page = 0;
foreach ($page_views as $pv) {
    $existing = $DB->get_record('course_modules_completion', ['coursemoduleid' => $pv->cmid, 'userid' => $pv->userid]);
    if ($existing) {
        if ((int)$existing->completionstate !== 1) {
            $existing->completionstate = 1;
            $existing->viewed = 1;
            $existing->timemodified = time();
            $DB->update_record('course_modules_completion', $existing);
            $synced_page++;
        }
    } else {
        $c = new stdClass();
        $c->coursemoduleid = $pv->cmid;
        $c->userid = $pv->userid;
        $c->completionstate = 1;
        $c->viewed = 1;
        $c->timemodified = time();
        $DB->insert_record('course_modules_completion', $c);
        $synced_page++;
    }
}
echo "   -> Disinkronkan {$synced_page} record materi bacaan.\n";

rebuild_course_cache($courseid, true);
echo "5. Cache course berhasil diperbarui.\n";
