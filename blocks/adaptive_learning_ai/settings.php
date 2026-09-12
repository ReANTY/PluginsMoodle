<?php
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $huburl = (new moodle_url('/admin/settings.php', ['section' => 'ai_central_settings']))->out();
    $settings->add(new admin_setting_description(
        'block_adaptive_learning_ai/central_hub_banner',
        '',
        '<div class="alert alert-info d-flex align-items-center mb-3" style="border-left: 4px solid #0f6cbf; border-radius: 8px;">
            <div style="font-size: 1.5rem; margin-right: 12px;">💡</div>
            <div>
                <strong>Pusat Pengaturan AI Terpadu:</strong> Anda kini dapat mengelola seluruh konfigurasi 
                <em>Adaptive Learning AI</em>, <em>AICode</em>, <em>LLM Motivation</em>, dan kontrol status <em>Microservice Executor</em> 
                dalam satu halaman di <a href="' . $huburl . '" class="alert-link font-weight-bold" style="text-decoration: underline;">Pusat Pengaturan AI</a>.
            </div>
        </div>'
    ));

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

    // Heading: Cognitive Level Thresholds (Adaptive Learning Path)
    $settings->add(new admin_setting_heading(
        'block_adaptive_learning_ai/threshold_heading',
        get_string('setting_threshold_heading', 'block_adaptive_learning_ai'),
        get_string('setting_threshold_heading_desc', 'block_adaptive_learning_ai')
    ));

    // Primary Threshold (Score < this is Primary / Dasar)
    $settings->add(new admin_setting_configtext(
        'block_adaptive_learning_ai/primary_threshold',
        get_string('setting_primary_threshold', 'block_adaptive_learning_ai'),
        get_string('setting_primary_threshold_desc', 'block_adaptive_learning_ai'),
        70,
        PARAM_INT
    ));

    // Expert Threshold (Score >= this is Expert / Mahir)
    $settings->add(new admin_setting_configtext(
        'block_adaptive_learning_ai/expert_threshold',
        get_string('setting_expert_threshold', 'block_adaptive_learning_ai'),
        get_string('setting_expert_threshold_desc', 'block_adaptive_learning_ai'),
        85,
        PARAM_INT
    ));
}
