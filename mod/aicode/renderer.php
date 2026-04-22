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
        global $USER, $PAGE, $DB;

        $output = '';

        $isteacher = has_capability('mod/aicode:viewattempts', $context);
        $mode = $aicode->mode ?? 'training';

        // In exam mode, check if this student has already submitted.
        $hassubmitted = false;
        $submittedcode = '';
        if ($mode === 'exam' && !$isteacher) {
            $existingattempts = $DB->get_records_select(
                'aicode_attempts',
                'problemid = :pid AND userid = :uid',
                ['pid' => $aicode->id, 'uid' => $USER->id],
                'timecreated DESC',
                '*',
                0, 20
            );
            foreach ($existingattempts as $existingattempt) {
                $resultdata = json_decode($existingattempt->result_json, true);
                if (!empty($resultdata['teacher_review_requested'])) {
                    $hassubmitted = true;
                    $submittedcode = $resultdata['code'] ?? '';
                    break;
                }
            }
        }

        // Display problem description.
        $output .= html_writer::start_div('aicode-problem-description');
        $output .= html_writer::div(
            format_text($aicode->description ?? '', FORMAT_HTML, ['para' => false]),
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
        $output .= html_writer::tag('textarea', s($submittedcode), [
            'id' => 'aicode-submitted-code-raw',
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
            'data-hassubmitted' => $hassubmitted ? '1' : '0',
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
            'hasSubmitted' => $hassubmitted,
        ]);
        $PAGE->requires->js_init_code(<<<'JS'
(function() {
    // ── Reset button: restore starter code ──────────────────────────────────
    var resetBtn = document.getElementById('aicode-reset-btn');
    var editor   = document.getElementById('aicode-fallback-editor');
    var raw      = document.getElementById('aicode-starter-code-raw');
    if (resetBtn && editor && raw) {
        resetBtn.addEventListener('click', function() {
            var before = editor.value;
            window.setTimeout(function() {
                var after = editor.value;
                if (after === before) { return; }
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
    }

    // ── Toggle HTML/CSS template visibility ─────────────────────────────────
    var toggleBtn = document.getElementById('aicode-toggle-templates');
    var wrapper   = document.getElementById('aicode-templates-wrapper');
    if (toggleBtn && wrapper) {
        toggleBtn.addEventListener('click', function() {
            var isOpen = toggleBtn.getAttribute('data-open') === '1';
            if (isOpen) {
                wrapper.style.display = 'none';
                toggleBtn.setAttribute('data-open', '0');
                toggleBtn.setAttribute('aria-expanded', 'false');
                toggleBtn.textContent = 'Lihat HTML & CSS';
            } else {
                wrapper.style.display = 'block';
                toggleBtn.setAttribute('data-open', '1');
                toggleBtn.setAttribute('aria-expanded', 'true');
                toggleBtn.textContent = 'Sembunyikan HTML & CSS';
            }
        });
    }

    // ── Auto-preview on load when HTML/CSS templates are present ────────────
    function buildInitialPreview() {
        var frame   = document.getElementById('aicode-preview-iframe');
        var htmlRaw = document.getElementById('aicode-html-template-raw');
        var cssRaw  = document.getElementById('aicode-css-template-raw');
        var edEl    = document.getElementById('aicode-fallback-editor');
        if (!frame || (!htmlRaw && !cssRaw)) { return; }
        var html = (htmlRaw && htmlRaw.value) ? htmlRaw.value : '';
        var css  = (cssRaw  && cssRaw.value)  ? cssRaw.value  : '';
        var js   = (edEl    && edEl.value)    ? edEl.value    : '';
        if (!html.trim() && !css.trim()) { return; }
        // Replicate the same srcdoc structure used by the AMD module.
        var loopGuard = 'var __lpS=Date.now();window.__loopProtect=function(){'
            + 'if(Date.now()-__lpS>500){throw new Error("Infinite loop guard");}};';
        var safeJs = js.replace(/<\/script>/gi, '<\\/script>');
        var srcdoc = '<!doctype html><html><head><meta charset="utf-8">'
            + '<meta name="viewport" content="width=device-width,initial-scale=1">'
            + '<style>' + css + '</style></head><body>' + html
            + '<script>(function(){' + loopGuard + 'try{' + safeJs + '}catch(e){'
            + 'parent.postMessage({source:"aicode-preview",type:"error",'
            + 'payload:{message:e.message||String(e),stack:e.stack||""}}, "*");}'
            + '})();<\/script></body></html>';
        frame.srcdoc = srcdoc;
    }

    // Run after AMD module has initialised (it uses $(document).ready internally).
    if (document.readyState === 'complete') {
        setTimeout(buildInitialPreview, 150);
    } else {
        window.addEventListener('load', function() {
            setTimeout(buildInitialPreview, 150);
        });
    }
})();
JS
        );

        // Main layout.
        $output .= html_writer::start_div('aicode-layout');

        // Check if HTML/CSS templates exist.
        $hashtml = !empty(trim($aicode->htmltemplate ?? ''));
        $hascss  = !empty(trim($aicode->csstemplate ?? ''));
        $hastemplates = $hashtml || $hascss;

        // Info notice: HTML & CSS provided by teacher.
        if ($hastemplates) {
            $output .= html_writer::start_div('aicode-template-notice');
            $output .= html_writer::start_div('aicode-template-notice-body');
            $output .= '<div class="aicode-template-notice-icon" aria-hidden="true">'
                . '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="#d97706">'
                . '<path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17'
                . ' 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a'
                . '.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>'
                . '</svg>'
                . '</div>';
            $output .= html_writer::start_div('aicode-template-notice-text');
            $output .= html_writer::tag('strong', 'Kode HTML &amp; CSS sudah disediakan oleh soal');
            $output .= html_writer::tag(
                'span',
                'Kamu hanya perlu menulis kode <strong>JavaScript</strong> sesuai instruksi soal.'
                . ' Kode HTML dan CSS bersifat <em>read-only</em> dan tidak dapat diubah.',
                ['class' => 'aicode-template-notice-desc']
            );
            $output .= html_writer::end_div();
            $output .= html_writer::end_div();
            $output .= html_writer::tag('button', 'Lihat HTML &amp; CSS', [
                'id'          => 'aicode-toggle-templates',
                'class'       => 'btn btn-sm btn-outline-secondary aicode-toggle-btn',
                'type'        => 'button',
                'data-open'   => '0',
                'aria-expanded' => 'false',
            ]);
            $output .= html_writer::end_div();
        }

        // Templates row: only render when there is actual HTML or CSS content.
        if ($hastemplates) {
            $output .= '<div class="aicode-templates-wrapper" id="aicode-templates-wrapper" style="display:none;">';
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
            $output .= '</div>';
        }

        $output .= html_writer::start_div('aicode-panel aicode-js-panel');
        $output .= html_writer::tag('h4', 'JavaScript');
        $output .= html_writer::start_div('aicode-textarea-wrap');
        $output .= html_writer::div('', 'aicode-line-numbers', [
            'id' => 'aicode-line-numbers',
            'aria-hidden' => 'true',
        ]);
        $output .= html_writer::start_div('aicode-editor-stack');
        $output .= html_writer::start_div('aicode-highlight', ['id' => 'aicode-highlight']);
        $output .= html_writer::tag('div', '', [
            'id'    => 'aicode-active-line',
            'class' => 'aicode-active-line',
            'style' => 'display:none',
        ]);
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

        $previewpanelattrs = $hastemplates ? [] : ['style' => 'display:none'];
        $output .= html_writer::start_div('aicode-preview-panel', $previewpanelattrs);
        $output .= '<div class="aicode-output-header">';
        $output .= '<span class="aicode-output-title">PREVIEW</span>';
        $output .= '</div>';
        $output .= html_writer::tag('iframe', '', [
            'id' => 'aicode-preview-iframe',
            'class' => 'aicode-preview-iframe',
            'sandbox' => 'allow-scripts',
            'referrerpolicy' => 'no-referrer',
        ]);
        $output .= html_writer::end_div();

        // Output panel — console.log/info results, hidden until code is run.
        $output .= '<div class="aicode-output-panel" id="aicode-output-panel" style="display:none">';
        $output .= '<div class="aicode-output-header">';
        $output .= '<span class="aicode-output-title">OUTPUT</span>';
        $output .= '<span class="aicode-output-count" id="aicode-output-count"></span>';
        $output .= '</div>';
        $output .= '<div class="aicode-output-list" id="aicode-output-list"></div>';
        $output .= '</div>';

        // Compute JS filename from activity name (e.g. "Latihan 7: DOM" → "latihan_7_dom.js").
        $activityname = $aicode->name ?? 'student_code';
        $jsfilename = strtolower($activityname);
        $jsfilename = preg_replace('/[^a-z0-9]+/', '_', $jsfilename);
        $jsfilename = trim($jsfilename, '_');
        $jsfilename = substr($jsfilename, 0, 40);
        if (empty($jsfilename)) {
            $jsfilename = 'student_code';
        }
        $jsfilename .= '.js';

        // Problems panel (VS Code style).
        $output .= '<div class="aicode-problems-panel">';
        $output .= '<div class="aicode-problems-header">';
        $output .= '<span class="aicode-problems-title">PROBLEMS</span>';
        $output .= '<span class="aicode-problems-badges">';
        $output .= '<span class="aicode-problems-badge aicode-badge-error" id="aicode-badge-error" style="display:none"></span>';
        $output .= '<span class="aicode-problems-badge aicode-badge-warn"  id="aicode-badge-warn"  style="display:none"></span>';
        $output .= '<span class="aicode-problems-badge aicode-badge-info"  id="aicode-badge-info"  style="display:none"></span>';
        $output .= '</span>';
        $output .= '</div>';
        $output .= '<div class="aicode-problems-group" id="aicode-problems-group" style="display:none">';
        $output .= '<div class="aicode-problems-file-header">';
        $output .= '<svg width="10" height="10" viewBox="0 0 10 10" fill="none" class="aicode-file-chevron" id="aicode-file-chevron">';
        $output .= '<path d="M2 3l3 4 3-4" stroke="#858585" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>';
        $output .= '</svg>';
        $output .= '<svg width="14" height="14" viewBox="0 0 16 16" fill="none" style="flex-shrink:0">';
        $output .= '<rect x="2" y="1" width="9" height="14" rx="1" fill="#569cd6" opacity="0.15"/>';
        $output .= '<rect x="2" y="1" width="9" height="14" rx="1" stroke="#569cd6" stroke-width="1"/>';
        $output .= '<path d="M5 5h6M5 8h6M5 11h4" stroke="#569cd6" stroke-width="1" stroke-linecap="round"/>';
        $output .= '</svg>';
        $output .= '<span class="aicode-problems-filename">' . htmlspecialchars($jsfilename) . '</span>';
        $output .= '<span class="aicode-problems-file-count" id="aicode-problems-file-count"></span>';
        $output .= '</div>';
        $output .= '<div class="aicode-problems-list" id="aicode-errors"></div>';
        $output .= '</div>';
        $output .= '<div class="aicode-problems-empty is-visible" id="aicode-problems-empty">Tidak ada masalah terdeteksi.</div>';
        $output .= '</div>';

        $output .= '<div class="aicode-feedback-panel">';
        $output .= '<div class="aicode-output-header">';
        $output .= '<span class="aicode-output-title">FEEDBACK</span>';
        $output .= '</div>';
        $output .= html_writer::div('', 'feedback-box', ['id' => 'aicode-feedback']);
        $output .= '</div>';

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
            /* Template notice banner */
            .aicode-template-notice {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                background: #fffbeb;
                border: 1px solid #fde68a;
                border-radius: 0.5rem;
                padding: 10px 14px;
                flex-wrap: wrap;
            }
            .aicode-template-notice-body {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                flex: 1 1 auto;
                min-width: 0;
            }
            .aicode-template-notice-icon {
                flex-shrink: 0;
                display: flex;
                align-items: flex-start;
                margin-top: 1px;
            }
            .aicode-template-notice-text {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            .aicode-template-notice-text strong {
                font-size: 0.875rem;
                color: #92400e;
                font-weight: 600;
            }
            .aicode-template-notice-desc {
                font-size: 0.8125rem;
                color: #78350f;
                line-height: 1.5;
            }
            .aicode-toggle-btn {
                flex-shrink: 0;
                white-space: nowrap;
                border-color: #d97706;
                color: #92400e;
                font-size: 0.8rem;
                padding: 4px 12px;
                transition: background 0.15s, color 0.15s;
            }
            .aicode-toggle-btn:hover {
                background: #fde68a;
                border-color: #d97706;
                color: #78350f;
            }
            .aicode-templates-wrapper {
                overflow: hidden;
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
            .aicode-preview-panel {
                background: #1e1e1e;
                border: 1px solid #3e3e3e;
                border-radius: 0.375rem;
                overflow: hidden;
                margin-bottom: 8px;
            }
            .aicode-preview-panel .aicode-output-header {
                margin-bottom: 0;
            }
            .aicode-preview-iframe {
                width: 100%;
                height: 100%;
                min-height: 420px;
                border: none;
                border-top: 1px solid #3e3e3e;
                border-radius: 0;
                background: #ffffff;
                display: block;
            }
            /* Output panel (console.log / console.info) */
            .aicode-output-panel {
                background: #1e1e1e;
                border: 1px solid #3e3e3e;
                border-radius: 0.375rem;
                overflow: hidden;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                font-size: 0.8125rem;
                margin-bottom: 8px;
            }
            .aicode-output-header {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 5px 10px;
                background: #252526;
                border-bottom: 1px solid #3e3e3e;
                user-select: none;
            }
            .aicode-output-title {
                font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 0.68rem;
                font-weight: 700;
                color: #cccccc;
                letter-spacing: 0.07em;
                text-transform: uppercase;
            }
            .aicode-output-count {
                font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 0.72rem;
                color: #858585;
                font-weight: 400;
                margin-left: auto;
            }
            .aicode-output-list {
                padding: 4px 0;
                max-height: 200px;
                overflow-y: auto;
            }
            .aicode-output-line {
                display: block;
                padding: 3px 14px;
                color: #d4d4d4;
                border-bottom: 1px solid #2a2a2a;
                white-space: pre-wrap;
                word-break: break-all;
                line-height: 1.5;
            }
            .aicode-output-line:last-child {
                border-bottom: none;
            }
            .aicode-output-info {
                color: #4ec9b0;
            }
            /* VS Code Problems panel */
            .aicode-problems-panel {
                background: #1e1e1e;
                border: 1px solid #3e3e3e;
                border-radius: 0.375rem;
                overflow: hidden;
                min-height: 180px;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                font-size: 0.8125rem;
                margin-bottom: 8px;
            }
            .aicode-problems-header {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 5px 10px;
                background: #252526;
                border-bottom: 1px solid #3e3e3e;
                user-select: none;
            }
            .aicode-problems-title {
                font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 0.68rem;
                font-weight: 700;
                color: #cccccc;
                letter-spacing: 0.07em;
                text-transform: uppercase;
            }
            .aicode-problems-badges {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-left: auto;
            }
            .aicode-problems-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 0.78rem;
                font-weight: 500;
                line-height: 1;
            }
            .aicode-badge-error { color: #f14c4c; }
            .aicode-badge-warn  { color: #cca700; }
            .aicode-badge-info  { color: #3794ff; }
            .aicode-problems-file-header {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 5px 10px 5px 8px;
                color: #cccccc;
                font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 0.8rem;
                font-weight: 600;
                background: #252526;
                border-bottom: 1px solid #3e3e3e;
                cursor: default;
                user-select: none;
            }
            .aicode-file-chevron {
                flex-shrink: 0;
            }
            .aicode-problems-filename {
                color: #e0e0e0;
                flex: 1 1 auto;
            }
            .aicode-problems-file-count {
                font-size: 0.72rem;
                color: #858585;
                font-weight: 400;
            }
            .aicode-problems-list {
                padding: 2px 0;
            }
            .aicode-problem-item {
                display: flex;
                align-items: flex-start;
                gap: 8px;
                padding: 4px 12px 4px 22px;
                cursor: default;
                line-height: 1.5;
                border-left: 2px solid transparent;
                transition: background 0.08s;
            }
            .aicode-problem-item:hover {
                background: #2a2d2e;
            }
            .aicode-problem-item.aicode-problem-error  { border-left-color: transparent; }
            .aicode-problem-item.aicode-problem-warning { border-left-color: transparent; }
            .aicode-problem-icon {
                flex-shrink: 0;
                display: flex;
                align-items: center;
                padding-top: 2px;
            }
            .aicode-problem-message {
                flex: 1 1 auto;
                color: #d4d4d4;
                word-break: break-word;
            }
            .aicode-problem-location {
                flex-shrink: 0;
                color: #858585;
                font-size: 0.75rem;
                padding-left: 8px;
                white-space: nowrap;
                align-self: flex-start;
                padding-top: 3px;
            }
            .aicode-problems-empty {
                padding: 18px 20px;
                color: #6c6c6c;
                font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 0.8125rem;
                display: none;
            }
            .aicode-problems-empty.is-visible {
                display: block;
            }
            /* Problem item is clickable when it has a line reference */
            .aicode-problem-item[data-line] {
                cursor: pointer;
            }
            .aicode-problem-item[data-line]:hover .aicode-problem-message {
                text-decoration: underline;
                text-underline-offset: 2px;
            }
            .aicode-problem-item.is-active {
                background: #37373d;
            }
            /* Active-line highlight strip inside the editor */
            .aicode-active-line {
                position: absolute;
                left: 0;
                right: 0;
                background: rgba(255, 200, 50, 0.10);
                border-left: 3px solid #f14c4c;
                pointer-events: none;
                z-index: 1;
                transition: top 0.12s ease, opacity 0.15s;
            }
            /* Feedback panel */
            .aicode-feedback-panel {
                background: #1e1e1e;
                border: 1px solid #3e3e3e;
                border-radius: 0.375rem;
                overflow: hidden;
            }
            .aicode-feedback-panel .aicode-output-header {
                margin-bottom: 0;
            }
            .feedback-box {
                background: #e7f1ff;
                color: #084298;
                padding: 10px;
                border: none;
                border-top: 1px solid #3e3e3e;
                border-radius: 0;
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

