<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * AICode module admin settings and defaults
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // Executor service URL.
    $settings->add(new admin_setting_configtext('aicode/executor_url',
        get_string('executorurl', 'aicode'),
        get_string('executorurl_desc', 'aicode'),
        'http://127.0.0.1:3001',
        PARAM_URL));

    // Confidence threshold for AI feedback.
    $settings->add(new admin_setting_configtext('aicode/confidence_threshold',
        get_string('confidencethreshold', 'aicode'),
        get_string('confidencethreshold_desc', 'aicode'),
        '0.6',
        PARAM_FLOAT));

    // Cache TTL (seconds).
    $settings->add(new admin_setting_configtext('aicode/cache_ttl',
        get_string('cachettl', 'aicode'),
        get_string('cachettl_desc', 'aicode'),
        '3600',
        PARAM_INT));

    // Max AI calls per student per day.
    $settings->add(new admin_setting_configtext('aicode/max_calls_per_day',
        get_string('maxcallsperday', 'aicode'),
        get_string('maxcallsperday_desc', 'aicode'),
        '50',
        PARAM_INT));

    // Execution timeout (seconds).
    $settings->add(new admin_setting_configtext('aicode/execution_timeout',
        get_string('executiontimeout', 'aicode'),
        get_string('executiontimeout_desc', 'aicode'),
        '2',
        PARAM_INT));
}

