<?php
/**
 * Tambahkan / perbarui Page kunci jawaban guru pada course yang sudah ada.
 *
 * php admin/cli/add_teacher_keys_page.php --shortname=JS-FUND-PPLG-XI
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/admin/course_builder_helpers.php');
require_once($CFG->dirroot . '/admin/js_course_teacher_keys.php');

$shortname = '';
if (!empty($argv)) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--shortname=') === 0) {
            $shortname = substr($arg, 12);
        }
    }
}

if ($shortname === '') {
    echo "Usage: php admin/cli/add_teacher_keys_page.php --shortname=COURSE_SHORTNAME\n";
    exit(1);
}

$courseid = get_course_id_by_shortname($shortname);
if (!$courseid) {
    echo "Course tidak ditemukan: $shortname\n";
    exit(1);
}

$teacherkeys = build_teacher_keys_page();
$pagetitle = $teacherkeys['title'];

$existing = $DB->get_records_sql(
    "SELECT cm.id AS cmid, p.id AS pageid
       FROM {course_modules} cm
       JOIN {modules} m ON m.id = cm.module AND m.name = 'page'
       JOIN {page} p ON p.id = cm.instance
      WHERE cm.course = ? AND p.name = ?",
    [$courseid, $pagetitle]
);

if ($existing) {
    $rec = reset($existing);
    $DB->set_field('page', 'content', $teacherkeys['content'], ['id' => $rec->pageid]);
    $DB->set_field('page', 'intro', $teacherkeys['intro'], ['id' => $rec->pageid]);
    $DB->set_field('page', 'timemodified', time(), ['id' => $rec->pageid]);
    $DB->set_field('course_modules', 'visible', 0, ['id' => $rec->cmid]);
    echo "Kunci jawaban diperbarui (cmid {$rec->cmid}) pada course $shortname\n";
} else {
    $cmid = create_teacher_only_page_resource(
        $courseid,
        0,
        $teacherkeys['title'],
        $teacherkeys['intro'],
        $teacherkeys['content']
    );
    echo "Kunci jawaban ditambahkan (cmid $cmid) pada course $shortname\n";
}

echo "Hanya guru/admin dengan izin viewhiddenactivities yang dapat melihat halaman ini.\n";
