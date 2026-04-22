<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Lists all instances of mod_aicode in a course
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // course id

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course);

$context = context_course::instance($course->id);

$event = \mod_aicode\event\course_module_instance_list_viewed::create([
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url('/mod/aicode/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->shortname) . ': ' . get_string('modulenameplural', 'aicode'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->navbar->add(get_string('modulenameplural', 'aicode'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'aicode'));

$aicodemods = get_all_instances_in_course('aicode', $course);

if (empty($aicodemods)) {
    notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'aicode')),
        new moodle_url('/course/view.php', ['id' => $course->id]));
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_' . $course->format);
    $table->head  = [$strsectionname, get_string('name'), get_string('mode', 'aicode')];
    $table->align = ['center', 'left', 'center'];
} else {
    $table->head  = [get_string('name'), get_string('mode', 'aicode')];
    $table->align = ['left', 'center'];
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';

foreach ($aicodemods as $aicode) {
    $cm = $modinfo->cms[$aicode->coursemodule];

    if ($usesections) {
        $printsection = '';
        if ($aicode->section !== $currentsection) {
            if ($aicode->section) {
                $printsection = get_section_name($course, $aicode->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $aicode->section;
        }
    }

    $class = $aicode->visible ? null : ['class' => 'dimmed'];

    $link = html_writer::link(
        new moodle_url('/mod/aicode/view.php', ['id' => $aicode->coursemodule]),
        format_string($aicode->name, true),
        $class ?? []
    );

    $mode = $aicode->mode ?? 'training';
    if ($mode === 'exam') {
        $modelabel = html_writer::span(
            get_string('mode_exam', 'aicode'),
            'badge bg-danger'
        );
    } else {
        $modelabel = html_writer::span(
            get_string('mode_training', 'aicode'),
            'badge bg-primary'
        );
    }

    if ($usesections) {
        $table->data[] = [$printsection, $link, $modelabel];
    } else {
        $table->data[] = [$link, $modelabel];
    }
}

echo html_writer::table($table);
echo $OUTPUT->footer();
