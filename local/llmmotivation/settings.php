<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_llmmotivation', get_string('pluginname', 'local_llmmotivation'));

    $settings->add(new admin_setting_heading(
        'local_llmmotivation_heading',
        get_string('settings_heading', 'local_llmmotivation'),
        ''
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_llmmotivation/gemini_apikey',
        get_string('settings_apikey', 'local_llmmotivation'),
        get_string('settings_apikey_desc', 'local_llmmotivation'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_llmmotivation/gemini_model',
        get_string('settings_model', 'local_llmmotivation'),
        get_string('settings_model_desc', 'local_llmmotivation'),
        'gemini-1.5-flash',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_llmmotivation/admin_preview',
        get_string('settings_admin_preview', 'local_llmmotivation'),
        get_string('settings_admin_preview_desc', 'local_llmmotivation'),
        0
    ));

    $ADMIN->add('localplugins', $settings);
}
