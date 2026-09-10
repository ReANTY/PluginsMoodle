<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_llmmotivation_submit_feedback' => [
        'classname'     => 'local_llmmotivation\external\submit_feedback',
        'methodname'    => 'execute',
        'description'   => 'Submits learner feedback for emotional readiness and motivational messages.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'local_llmmotivation_record_interaction' => [
        'classname'     => 'local_llmmotivation\external\record_interaction',
        'methodname'    => 'execute',
        'description'   => 'Records student interaction with motivation popups (e.g. dismissal).',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];
