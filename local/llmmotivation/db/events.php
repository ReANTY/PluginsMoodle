<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback'  => '\local_llmmotivation\event\observer::quiz_attempt_submitted',
    ],
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback'  => '\local_llmmotivation\event\observer::activity_completed',
    ],
];
