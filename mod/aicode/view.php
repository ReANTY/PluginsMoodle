<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Prints a particular instance of aicode
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$a = optional_param('a', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('aicode', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $aicode = $DB->get_record('aicode', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $aicode = $DB->get_record('aicode', ['id' => $a], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $aicode->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('aicode', $aicode->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

\mod_aicode\local\activity_log::record(
    $context,
    (int) $aicode->id,
    (int) $USER->id,
    \mod_aicode\local\activity_log::ACTION_ACTIVITY_VIEW,
    [],
    $aicode
);

$event = \mod_aicode\event\course_module_viewed::create([
    'objectid' => $aicode->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('aicode', $aicode);
$event->trigger();

// Print the page header.
$PAGE->set_url('/mod/aicode/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($aicode->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Output starts here.
echo $OUTPUT->header();

// Render the activity.
$renderer = $PAGE->get_renderer('mod_aicode');
echo $renderer->render_problem_view($aicode, $cm, $context);

// Finish the page.
echo $OUTPUT->footer();

