<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Renderer for aicode module
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * AICode module renderer class
 */
class mod_aicode_renderer extends plugin_renderer_base {

    /**
     * Render the main problem view
     *
     * @param stdClass $aicode The aicode problem instance
     * @param stdClass $cm Course module
     * @param context $context Module context
     * @return string HTML output
     */
    public function render_problem_view($aicode, $cm, $context) {
        global $USER, $PAGE;

        $output = '';

        $isteacher = has_capability('mod/aicode:viewattempts', $context);
        $mode = $aicode->mode ?? 'training';

        // Display problem description.
        $output .= html_writer::start_div('aicode-problem-description');
        $output .= html_writer::div(
            format_text($aicode->description ?? '', FORMAT_PLAIN, ['para' => false]),
            'description'
        );
        $output .= html_writer::end_div();

        // Hidden metadata for fallback JS config.
        $output .= html_writer::tag('input', '', [
            'type' => 'hidden',
            'id' => 'aicode-problemid',
            'value' => $aicode->id,
        ]);
        $output .= html_writer::tag('input', '', [
            'type' => 'hidden',
            'id' => 'aicode-language',
            'value' => $aicode->language ?? 'javascript',
        ]);
        $output .= html_writer::tag('input', '', [
            'type' => 'hidden',
            'id' => 'aicode-sesskey',
            'value' => sesskey(),
        ]);
        $startercode = $aicode->startercode ?? '';
        if (!is_string($startercode)) {
            $startercode = (string) $startercode;
        }
        $startertrim = trim($startercode);
        if ($startertrim === '' || strtolower($startertrim) === 'undefined' || strtolower($startertrim) === 'null') {
            $startercode = '';
        }

        $output .= html_writer::tag('textarea', s($aicode->htmltemplate ?? ''), [
            'id' => 'aicode-html-template-raw',
            'style' => 'display:none;',
            'readonly' => 'readonly',
        ]);
        $output .= html_writer::tag('textarea', s($aicode->csstemplate ?? ''), [
            'id' => 'aicode-css-template-raw',
            'style' => 'display:none;',
            'readonly' => 'readonly',
        ]);
        $output .= html_writer::tag('textarea', s($startercode), [
            'id' => 'aicode-starter-code-raw',
            'style' => 'display:none;',
            'readonly' => 'readonly',
        ]);
        $output .= html_writer::tag('div', '', [
            'id' => 'aicode-data',
            'data-problemid' => $aicode->id,
            'data-language' => $aicode->language ?? 'javascript',
            'data-sesskey' => sesskey(),
            'data-mode' => $mode,
            'data-isteacher' => $isteacher ? '1' : '0',
        ]);

        // Load  editor AMD module.
        $PAGE->requires->js_call_amd('mod_aicode/editor', 'init', [
            'problemId' => $aicode->id,
            'cmId' => $cm->id,
            'language' => $aicode->language,
            'starterCode' => $startercode,
            'htmlTemplate' => $aicode->htmltemplate ?? '',
            'cssTemplate' => $aicode->csstemplate ?? '',
            'sesskey' => sesskey(),
            'mode' => $mode,
            'isTeacher' => $isteacher,
        ]);
        $PAGE->requires->js_init_code(<<<'JS'
(function() {
    var resetBtn = document.getElementById('aicode-reset-btn');
    var editor = document.getElementById('aicode-fallback-editor');
    var raw = document.getElementById('aicode-starter-code-raw');
    if (!resetBtn || !editor || !raw) {
        return;
    }
    resetBtn.addEventListener('click', function() {
        var before = editor.value;
        window.setTimeout(function() {
            var after = editor.value;
            if (after === before) {
                return;
            }
            if (after === '' || after === 'undefined') {
                editor.value = raw.value || '';
                var evt;
                if (typeof Event === 'function') {
                    evt = new Event('input', {bubbles: true});
                } else {
                    evt = document.createEvent('Event');
                    evt.initEvent('input', true, true);
                }
                editor.dispatchEvent(evt);
            }
        }, 0);
    });
})();
JS
        );

        // Main layout.
        $output .= html_writer::start_div('aicode-layout');

        // Templates row: HTML + CSS side by side.
        $output .= html_writer::start_div('aicode-templates');

        $output .= html_writer::start_div('aicode-panel aicode-readonly-panel');
        $output .= html_writer::tag('h4', get_string('htmlreadonly', 'aicode'));
        $output .= html_writer::tag('pre', s($aicode->htmltemplate ?? ''), [
            'id' => 'aicode-html-template',
            'class' => 'aicode-readonly-code',
        ]);
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('aicode-panel aicode-readonly-panel');
        $output .= html_writer::tag('h4', get_string('cssreadonly', 'aicode'));
        $output .= html_writer::tag('pre', s($aicode->csstemplate ?? ''), [
            'id' => 'aicode-css-template',
            'class' => 'aicode-readonly-code',
        ]);
        $output .= html_writer::end_div();

        $output .= html_writer::end_div();

        $output .= html_writer::start_div('aicode-panel aicode-js-panel');
        $output .= html_writer::tag('h4', 'JavaScript');
        $output .= html_writer::start_div('aicode-textarea-wrap');
        $output .= html_writer::div('', 'aicode-line-numbers', [
            'id' => 'aicode-line-numbers',
            'aria-hidden' => 'true',
        ]);
        $output .= html_writer::start_div('aicode-editor-stack');
        $output .= html_writer::start_div('aicode-highlight', ['id' => 'aicode-highlight']);
        $output .= html_writer::tag('pre', '', [
            'id' => 'aicode-highlight-code',
            'class' => 'aicode-highlight-code',
        ]);
        $output .= html_writer::end_div();
        $output .= html_writer::tag('textarea', s($startercode), [
            'id' => 'aicode-fallback-editor',
            'class' => 'aicode-fallback-editor',
            'spellcheck' => 'false',
            'wrap' => 'off',
        ]);
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::div('', 'monaco-editor', ['id' => 'aicode-monaco-editor']);
        $output .= html_writer::end_div();

        // Control buttons below JS editor.
        $output .= html_writer::start_div('aicode-controls');
        $output .= html_writer::tag('button', get_string('run', 'aicode'), [
            'id' => 'aicode-run-btn',
            'class' => 'btn btn-primary',
            'type' => 'button',
        ]);
        $output .= html_writer::tag('button', get_string('history', 'aicode'), [
            'id' => 'aicode-history-btn',
            'class' => 'btn btn-secondary',
            'type' => 'button',
        ]);
        $output .= html_writer::tag('button', get_string('hint', 'aicode'), [
            'id' => 'aicode-hint-btn',
            'class' => 'btn btn-secondary',
            'type' => 'button',
        ]);
        $output .= html_writer::tag('button', get_string('reset', 'aicode'), [
            'id' => 'aicode-reset-btn',
            'class' => 'btn btn-warning',
            'type' => 'button',
        ]);
        $output .= html_writer::tag('button', get_string('submit', 'aicode'), [
            'id' => 'aicode-submit-btn',
            'class' => 'btn btn-info',
            'type' => 'button',
        ]);
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('aicode-panel aicode-preview-panel');
        $output .= html_writer::tag('h4', get_string('preview', 'aicode'));
        $output .= html_writer::tag('iframe', '', [
            'id' => 'aicode-preview-iframe',
            'class' => 'aicode-preview-iframe',
            'sandbox' => 'allow-scripts',
            'referrerpolicy' => 'no-referrer',
        ]);
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('aicode-panel aicode-error-feedback-panel');
        $output .= html_writer::tag('h4', get_string('erroraifeedback', 'aicode'));
        $output .= html_writer::tag('pre', '', ['id' => 'aicode-errors', 'class' => 'error-box']);
        $output .= html_writer::div('', 'feedback-box', ['id' => 'aicode-feedback']);
        $output .= html_writer::end_div();

        $output .= html_writer::end_div();

        // History drawer (session-only).
        $output .= html_writer::div('', 'aicode-history-backdrop', ['id' => 'aicode-history-backdrop']);
        $output .= html_writer::start_div('aicode-history-drawer', ['id' => 'aicode-history-drawer']);
        $output .= html_writer::start_div('aicode-history-header');
        $output .= html_writer::tag('span', get_string('history', 'aicode'));
        $output .= html_writer::tag('button', '×', [
            'id' => 'aicode-history-close',
            'class' => 'aicode-history-close',
            'type' => 'button',
        ]);
        $output .= html_writer::end_div();
        $output .= html_writer::div('', 'aicode-history-list', ['id' => 'aicode-history-list']);
        $output .= html_writer::end_div();

        // Add CSS.
        $output .= html_writer::tag('style', '
            .aicode-problem-description {
                background: #ffffff;
                border: 1px solid #dee2e6;
                border-radius: 0.5rem;
                padding: 1rem 1.25rem;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            }
            .aicode-problem-description::before {
                content: "Deskripsi soal";
                display: inline-block;
                font-size: 0.75rem;
                font-weight: 600;
                color: #0d6efd;
                background: #e7f1ff;
                border: 1px solid #b6d4fe;
                border-radius: 999px;
                padding: 2px 8px;
                margin-bottom: 8px;
            }
            .aicode-problem-description h3 {
                margin: 0 0 0.5rem;
                font-size: 1.1rem;
                font-weight: 600;
                color: #343a40;
            }
            .aicode-problem-description .description {
                color: #495057;
                line-height: 1.6;
            }
            .aicode-problem-description .description p {
                margin: 0 0 8px;
            }
            .aicode-problem-description .description ul,
            .aicode-problem-description .description ol {
                margin: 6px 0 8px 18px;
            }
            .aicode-problem-description .description code {
                background: #f1f3f5;
                border: 1px solid #e9ecef;
                border-radius: 0.25rem;
                padding: 2px 6px;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                font-size: 0.9em;
            }
            .aicode-layout {
                display: flex;
                flex-direction: column;
                gap: 16px;
                margin-top: 8px;
            }
            .aicode-templates {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }
            .aicode-panel {
                border: 1px solid #dee2e6;
                background: #ffffff;
                border-radius: 0.5rem;
                padding: 1rem;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            }
            .aicode-panel h4 {
                margin: 0 0 0.75rem;
                font-size: 1rem;
                font-weight: 600;
                color: #343a40;
            }
            .aicode-readonly-panel h4::after {
                content: "read-only";
                display: inline-block;
                margin-left: 6px;
                font-size: 0.7rem;
                font-weight: 600;
                color: #6c757d;
                background: #f1f3f5;
                border: 1px solid #e9ecef;
                border-radius: 999px;
                padding: 1px 6px;
                text-transform: uppercase;
                letter-spacing: 0.02em;
            }
            .aicode-js-panel {
                border-color: #b6d4fe;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04), inset 4px 0 0 #0d6efd;
            }
            .aicode-js-panel h4::after {
                content: "Jawaban siswa";
                display: block;
                margin-top: 0.2rem;
                font-size: 0.85rem;
                font-weight: 400;
                color: #6c757d;
            }
            .aicode-readonly-code {
                background: #f8f9fa;
                border: 1px solid #e9ecef;
                border-radius: 0.375rem;
                padding: 10px;
                min-height: 160px;
                max-height: 300px;
                overflow: auto;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                font-size: 0.875rem;
                white-space: pre-wrap;
                color: #212529;
            }
            .aicode-controls {
                margin: 0.5rem 0 0;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .aicode-controls button {
                margin-right: 0;
                margin-bottom: 0;
            }
            .monaco-editor {
                height: 460px;
                border: 1px solid #dee2e6;
                background: #ffffff;
                border-radius: 0.5rem;
                display: none;
            }
            .aicode-js-panel .monaco-editor {
                border-color: #b6d4fe;
                box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.1);
            }
            .aicode-textarea-wrap {
                --editor-bg: #f8f9fa;
                --editor-fg: #212529;
                --editor-comment: #6c757d;
                --editor-keyword: #0d6efd;
                --editor-string: #198754;
                --editor-number: #d63384;
                --editor-boolean: #d63384;
                --editor-null: #d63384;
                --editor-builtin: #6f42c1;
                --editor-function: #6f42c1;
                --editor-operator: #dc3545;
                --editor-line-number: #868e96;
                --editor-border: #dee2e6;
                display: flex;
                width: 100%;
                height: 460px;
                border: 1px solid var(--editor-border);
                border-radius: 0.5rem;
                background: var(--editor-bg);
            }
            .aicode-js-panel .aicode-textarea-wrap {
                border-color: #b6d4fe;
                box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.1);
            }
            .aicode-line-numbers {
                width: 48px;
                padding: 10px 8px 10px 10px;
                text-align: right;
                background: #f1f3f5;
                color: var(--editor-line-number);
                border-right: 1px solid var(--editor-border);
                box-sizing: border-box;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                font-size: 0.9rem;
                line-height: 1.4;
                overflow: hidden;
                white-space: pre;
                user-select: none;
            }
            .aicode-editor-stack {
                position: relative;
                flex: 1 1 auto;
                min-width: 0;
            }
            .aicode-highlight {
                position: absolute;
                inset: 0;
                overflow: auto;
                pointer-events: none;
                scrollbar-width: none;
            }
            .aicode-highlight::-webkit-scrollbar {
                width: 0;
                height: 0;
            }
            .aicode-highlight-code {
                margin: 0;
                padding: 10px;
                min-height: 100%;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                font-size: 0.9rem;
                line-height: 1.4;
                white-space: pre;
                tab-size: 2;
                color: var(--editor-fg);
            }
            .aicode-highlight .tok-comment {
                color: var(--editor-comment);
            }
            .aicode-highlight .tok-keyword {
                color: var(--editor-keyword);
            }
            .aicode-highlight .tok-string {
                color: var(--editor-string);
            }
            .aicode-highlight .tok-number {
                color: var(--editor-number);
            }
            .aicode-highlight .tok-boolean {
                color: var(--editor-boolean);
            }
            .aicode-highlight .tok-null {
                color: var(--editor-null);
            }
            .aicode-highlight .tok-builtin {
                color: var(--editor-builtin);
            }
            .aicode-highlight .tok-function {
                color: var(--editor-function);
            }
            .aicode-fallback-editor {
                position: relative;
                width: 100%;
                height: 100%;
                border: 0;
                padding: 10px;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                font-size: 0.9rem;
                line-height: 1.4;
                background: transparent;
                color: transparent;
                caret-color: var(--editor-fg);
                resize: none;
                display: block;
                outline: none;
                overflow: auto;
                white-space: pre;
                tab-size: 2;
            }
            .aicode-fallback-editor::selection {
                background: rgba(13, 110, 253, 0.2);
            }
            .aicode-preview-iframe {
                width: 100%;
                height: 100%;
                min-height: 420px;
                border: 1px solid #dee2e6;
                border-radius: 0.5rem;
                background: #ffffff;
            }
            .error-box {
                background: #f8d7da;
                color: #842029;
                padding: 10px;
                border: 1px solid #f5c2c7;
                border-radius: 0.5rem;
                min-height: 200px;
                font-family: monospace;
                white-space: pre-wrap;
            }
            .feedback-box {
                background: #e7f1ff;
                color: #084298;
                padding: 10px;
                border: 1px solid #b6d4fe;
                border-radius: 0.5rem;
                min-height: 220px;
            }
            .aicode-history-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-weight: 600;
                padding: 12px 16px;
                border-bottom: 1px solid #dee2e6;
                background: #ffffff;
            }
            .aicode-history-close {
                border: none;
                background: transparent;
                font-size: 1.1rem;
                cursor: pointer;
                line-height: 1;
                color: #6c757d;
            }
            .aicode-history-list {
                flex: 1 1 auto;
                overflow: auto;
                display: flex;
                flex-direction: column;
                gap: 10px;
                padding: 12px 16px 16px;
            }
            .aicode-history-item {
                border: 1px solid #dee2e6;
                border-radius: 0.5rem;
                padding: 10px 12px;
                background: #ffffff;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                font-size: 0.8rem;
                white-space: pre-wrap;
                cursor: pointer;
                color: #212529;
            }
            .aicode-history-item.is-expanded {
                border-color: #b6d4fe;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04), inset 4px 0 0 #0d6efd;
            }
            .aicode-history-meta {
                display: flex;
                align-items: center;
                gap: 8px;
                font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                margin-bottom: 8px;
            }
            .aicode-history-index {
                min-width: 22px;
                height: 22px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.75rem;
                font-weight: 600;
                color: #0d6efd;
                background: #e7f1ff;
                border: 1px solid #b6d4fe;
                border-radius: 999px;
                padding: 0 6px;
            }
            .aicode-history-label {
                font-size: 0.8rem;
                font-weight: 600;
                color: #495057;
            }
            .aicode-history-code {
                margin: 0;
                padding: 8px 10px;
                background: #f8f9fa;
                border: 1px solid #e9ecef;
                border-radius: 0.375rem;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                font-size: 0.78rem;
                line-height: 1.4;
                white-space: pre-wrap;
            }
            .aicode-history-empty {
                color: #6c757d;
                font-size: 0.85rem;
                font-style: italic;
            }
            .aicode-history-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.35);
                display: none;
                z-index: 1050;
            }
            .aicode-history-backdrop.is-visible {
                display: block;
            }
            .aicode-history-drawer {
                position: fixed;
                top: var(--aicode-drawer-top, 56px);
                right: 0;
                height: calc(100vh - var(--aicode-drawer-top, 56px));
                width: 320px;
                background: #f8f9fa;
                border-left: 1px solid #dee2e6;
                box-shadow: -2px 0 4px rgba(0, 0, 0, 0.12);
                padding: 0;
                display: flex;
                flex-direction: column;
                gap: 0;
                transform: translateX(100%);
                transition: transform 0.2s ease;
                z-index: 1060;
            }
            .aicode-history-drawer.is-open {
                transform: translateX(0);
            }
            @media (max-width: 980px) {
                .aicode-templates {
                    grid-template-columns: 1fr;
                }
                .monaco-editor {
                    height: 360px;
                }
                .aicode-fallback-editor {
                    height: 360px;
                }
                .aicode-history-drawer {
                    top: 0;
                    height: 100vh;
                    width: 92vw;
                }
            }
        ');

        return $output;
    }
}

