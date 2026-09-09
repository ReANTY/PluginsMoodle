<?php
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Heading: AI Configuration (OpenRouter & Google Gemini)
    $settings->add(new admin_setting_heading(
        'block_adaptive_learning_ai/ai_heading',
        get_string('setting_ai_heading', 'block_adaptive_learning_ai'),
        get_string('setting_ai_heading_desc', 'block_adaptive_learning_ai')
    ));

    // Gemini / OpenRouter API Key
    $settings->add(new admin_setting_configtext(
        'block_adaptive_learning_ai/gemini_apikey',
        get_string('setting_gemini_apikey', 'block_adaptive_learning_ai'),
        get_string('setting_gemini_apikey_desc', 'block_adaptive_learning_ai'),
        '',
        PARAM_TEXT
    ));

    // Model Selection (Supports OpenRouter & Google AI Studio)
    $settings->add(new admin_setting_configselect(
        'block_adaptive_learning_ai/gemini_model',
        get_string('setting_gemini_model', 'block_adaptive_learning_ai'),
        get_string('setting_gemini_model_desc', 'block_adaptive_learning_ai'),
        'google/gemini-2.5-flash',
        [
            'google/gemini-2.5-flash'     => 'OpenRouter: Google Gemini 2.5 Flash (Recommended)',
            'google/gemini-2.0-flash-001' => 'OpenRouter: Google Gemini 2.0 Flash',
            'google/gemini-flash-1.5'     => 'OpenRouter: Google Gemini 1.5 Flash',
            'gemini-2.5-flash'            => 'Google AI Studio: Gemini 2.5 Flash',
            'gemini-2.0-flash'            => 'Google AI Studio: Gemini 2.0 Flash',
            'gemini-1.5-flash'            => 'Google AI Studio: Gemini 1.5 Flash',
        ]
    ));

    // Heading: Thresholds
    $settings->add(new admin_setting_heading(
        'block_adaptive_learning_ai/threshold_heading',
        get_string('setting_threshold_heading', 'block_adaptive_learning_ai'),
        ''
    ));

    // Remedial Threshold (Score < this is remedial)
    $settings->add(new admin_setting_configtext(
        'block_adaptive_learning_ai/remedial_threshold',
        get_string('setting_remedial_threshold', 'block_adaptive_learning_ai'),
        get_string('setting_remedial_threshold_desc', 'block_adaptive_learning_ai'),
        70,
        PARAM_INT
    ));

    // Advanced Threshold (Score >= this is advanced)
    $settings->add(new admin_setting_configtext(
        'block_adaptive_learning_ai/advanced_threshold',
        get_string('setting_advanced_threshold', 'block_adaptive_learning_ai'),
        get_string('setting_advanced_threshold_desc', 'block_adaptive_learning_ai'),
        90,
        PARAM_INT
    ));
}
