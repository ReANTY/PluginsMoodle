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
    // Gemini API key.
    $settings->add(new admin_setting_configpasswordunmask('aicode/gemini_api_key',
        get_string('geminiapikey', 'aicode'),
        get_string('geminiapikey_desc', 'aicode'),
        ''));

    // Gemini model.
    $settings->add(new admin_setting_configtext('aicode/gemini_model',
        get_string('geminimodel', 'aicode'),
        get_string('geminimodel_desc', 'aicode'),
        'gemini-2.0-flash',
        PARAM_TEXT));

    // Executor service URL.
    $settings->add(new admin_setting_configtext('aicode/executor_url',
        get_string('executorurl', 'aicode'),
        get_string('executorurl_desc', 'aicode'),
        'http://127.0.0.1:3001',
        PARAM_URL));

    // Default AI feedback prompt template.
    $settings->add(new admin_setting_configtextarea('aicode/ai_feedback_prompt_template',
        get_string('aifeedbackprompttemplate', 'aicode'),
        get_string('aifeedbackprompttemplate_desc', 'aicode'),
        ' ',
        PARAM_RAW));

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

    // -------------------------------------------------------------------------
    // Security Check Module
    // -------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('aicode/security_heading',
        get_string('securityheading', 'aicode'),
        get_string('securityheading_desc', 'aicode')));

    // Enable / disable server-side security check.
    $settings->add(new admin_setting_configcheckbox('aicode/security_check_enabled',
        get_string('securitycheckenabled', 'aicode'),
        get_string('securitycheckenabled_desc', 'aicode'),
        '1'));

    // Minimum risk level that causes code to be blocked.
    $blockleveloptions = [
        'low'      => get_string('securitylevel_low', 'aicode'),
        'medium'   => get_string('securitylevel_medium', 'aicode'),
        'high'     => get_string('securitylevel_high', 'aicode'),
        'critical' => get_string('securitylevel_critical', 'aicode'),
    ];
    $settings->add(new admin_setting_configselect('aicode/security_block_level',
        get_string('securityblocklevel', 'aicode'),
        get_string('securityblocklevel_desc', 'aicode'),
        'high',
        $blockleveloptions));

    // Metadata activity log (research export — site admins only; no source code stored).
    $logurl = new moodle_url('/mod/aicode/activity_log_manage.php');
    $loglink = html_writer::link($logurl, get_string('activitylog_manage_link', 'aicode'));
    $settings->add(new admin_setting_description('aicode/activity_log_heading',
        get_string('activitylog_manage_heading', 'aicode'),
        get_string('activitylog_manage_heading_desc', 'aicode') . '<br /><br />' . $loglink));
}

