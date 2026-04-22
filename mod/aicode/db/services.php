<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * AICode external functions and service definitions.
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_aicode_run_code' => [
        'classname'   => 'mod_aicode\external\run_code',
        'methodname'  => 'execute',
        'description' => 'Execute student code',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_aicode_analyze_code' => [
        'classname'   => 'mod_aicode\external\analyze_code',
        'methodname'  => 'execute',
        'description' => 'Analyze code with AI',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_aicode_record_hint' => [
        'classname'   => 'mod_aicode\external\record_hint',
        'methodname'  => 'execute',
        'description' => 'Record hint usage',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_aicode_send_to_teacher' => [
        'classname'   => 'mod_aicode\external\send_to_teacher',
        'methodname'  => 'execute',
        'description' => 'Send code to teacher',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_aicode_get_run_history' => [
        'classname'   => 'mod_aicode\external\get_run_history',
        'methodname'  => 'execute',
        'description' => 'Get student run history from database',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
    ],
];

