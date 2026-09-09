<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Teacher report page for AICode module
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/gradelib.php');

$id     = required_param('id', PARAM_INT);       // course-module id
$userid = optional_param('userid', 0, PARAM_INT); // student detail view
$action = optional_param('action', '', PARAM_ALPHA);
$filter = optional_param('filter', 'all', PARAM_ALPHA);

$cm     = get_coursemodule_from_id('aicode', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$aicode = $DB->get_record('aicode', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/aicode:viewattempts', $context);

$canoverride = has_capability('mod/aicode:overridefeedback', $context);

// ── Handle form actions ────────────────────────────────────────────────────

if ($action === 'savegrade' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $gradeduserid = required_param('gradeduserid', PARAM_INT);
    $rawgradestr  = optional_param('rawgrade', '', PARAM_RAW);

    $aicodeforgrade = aicode_enrich_for_gradebook(clone $aicode);
    $gradeparams = aicode_build_grade_item_params($aicodeforgrade);
    $grademax = 100.0;
    if (!empty($gradeparams['grademax'])) {
        $grademax = (float) $gradeparams['grademax'];
    }

    if ($rawgradestr !== '') {
        $rawgrade = max(0.0, min($grademax, (float)$rawgradestr));
    } else {
        $rawgrade = null;
    }

    $result = aicode_set_user_grade($aicodeforgrade, $gradeduserid, $rawgrade);

    $redir = new moodle_url('/mod/aicode/report.php', ['id' => $id, 'filter' => $filter]);
    if ($userid) {
        $redir->param('userid', $gradeduserid);
    }

    if ($result === GRADE_UPDATE_OK) {
        grade_regrade_final_grades($course->id);
        redirect($redir, get_string('gradesaved', 'aicode'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    redirect(
        $redir,
        get_string('gradesavefailed', 'aicode', $result),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

if ($action === 'saveoverride' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    require_capability('mod/aicode:overridefeedback', $context);

    $overrideuserid = required_param('overrideuserid', PARAM_INT);
    $notes          = optional_param('notes', '', PARAM_TEXT);

    // Find the latest attempt for this user and problem.
    $latestattempts = $DB->get_records_select(
        'aicode_attempts',
        'problemid = :pid AND userid = :uid',
        ['pid' => $aicode->id, 'uid' => $overrideuserid],
        'timecreated DESC',
        '*',
        0, 1
    );
    $latestattempt = !empty($latestattempts) ? reset($latestattempts) : null;

    if ($latestattempt) {
        $existing = $DB->get_record('aicode_teacher_overrides', ['attemptid' => $latestattempt->id]);
        // Preserve structured fields if a correction record already exists.
        $existing_data = [];
        if ($existing && !empty($existing->corrected_feedback_json)) {
            $existing_data = json_decode($existing->corrected_feedback_json, true) ?: [];
        }
        $existing_data['notes']       = $notes;
        $existing_data['timeupdated'] = time();
        $overridedata = json_encode($existing_data);

        if ($existing) {
            $existing->corrected_feedback_json = $overridedata;
            $existing->timecreated             = time();
            $DB->update_record('aicode_teacher_overrides', $existing);
        } else {
            $rec                          = new stdClass();
            $rec->attemptid               = $latestattempt->id;
            $rec->teacherid               = $USER->id;
            $rec->corrected_feedback_json = $overridedata;
            $rec->feedback_rating         = null;
            $rec->use_as_example          = 0;
            $rec->timecreated             = time();
            $DB->insert_record('aicode_teacher_overrides', $rec);
        }
    }

    redirect(
        new moodle_url('/mod/aicode/report.php', ['id' => $id, 'userid' => $overrideuserid]),
        'Catatan berhasil disimpan.',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($action === 'savecorrection' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    require_capability('mod/aicode:overridefeedback', $context);

    $correctionuserid     = required_param('correctionuserid', PARAM_INT);
    $rating               = optional_param('feedback_rating', 0, PARAM_INT);
    $corrshort            = optional_param('corrected_message_short', '', PARAM_TEXT);
    $corrlong             = optional_param('corrected_message_long', '', PARAM_TEXT);
    $corrhintsraw         = optional_param('corrected_hints', '', PARAM_TEXT);
    $corrfix              = optional_param('corrected_suggested_fix', '', PARAM_TEXT);
    $notes                = optional_param('notes', '', PARAM_TEXT);
    $useasexample         = optional_param('use_as_example', 0, PARAM_INT);

    // Parse hints: one per line, strip blanks.
    $correctedhints = array_values(array_filter(
        array_map('trim', explode("\n", str_replace("\r", '', $corrhintsraw))),
        fn($h) => $h !== ''
    ));

    // Clamp rating to 1-5 (0 = not set).
    $rating = ($rating >= 1 && $rating <= 5) ? (int)$rating : null;

    // Find the latest attempt for this user and problem.
    $latestattempts = $DB->get_records_select(
        'aicode_attempts',
        'problemid = :pid AND userid = :uid',
        ['pid' => $aicode->id, 'uid' => $correctionuserid],
        'timecreated DESC',
        '*',
        0, 1
    );
    $latestattempt = !empty($latestattempts) ? reset($latestattempts) : null;

    if ($latestattempt) {
        $existing = $DB->get_record('aicode_teacher_overrides', ['attemptid' => $latestattempt->id]);
        // Preserve existing notes if the notes field is empty in this submission.
        $existingnotes = '';
        if ($existing && !empty($existing->corrected_feedback_json)) {
            $ed = json_decode($existing->corrected_feedback_json, true) ?: [];
            $existingnotes = $ed['notes'] ?? '';
        }

        $corrdata = [
            'notes'       => $notes !== '' ? $notes : $existingnotes,
            'rating'      => $rating,
            'corrected_diagnosis' => [
                'message_short' => $corrshort,
                'message_long'  => $corrlong,
            ],
            'corrected_hints'         => $correctedhints,
            'corrected_suggested_fix' => $corrfix,
            'timeupdated'             => time(),
        ];

        $corrjson = json_encode($corrdata);
        if ($existing) {
            $existing->corrected_feedback_json = $corrjson;
            $existing->feedback_rating         = $rating;
            $existing->use_as_example          = $useasexample ? 1 : 0;
            $existing->timecreated             = time();
            $DB->update_record('aicode_teacher_overrides', $existing);
        } else {
            $rec                          = new stdClass();
            $rec->attemptid               = $latestattempt->id;
            $rec->teacherid               = $USER->id;
            $rec->corrected_feedback_json = $corrjson;
            $rec->feedback_rating         = $rating;
            $rec->use_as_example          = $useasexample ? 1 : 0;
            $rec->timecreated             = time();
            $DB->insert_record('aicode_teacher_overrides', $rec);
        }
    }

    redirect(
        new moodle_url('/mod/aicode/report.php', ['id' => $id, 'userid' => $correctionuserid]),
        'Koreksi feedback AI berhasil disimpan.',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// ── Page setup ─────────────────────────────────────────────────────────────

$PAGE->set_url(new moodle_url('/mod/aicode/report.php', ['id' => $id]));
$PAGE->set_title(format_string($aicode->name) . ' — Laporan Guru');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->navbar->add('Laporan Guru');

// Disable the activity header (removes "To do" completion bar and description
// from the report page — the report is a standalone full-page view).
if (method_exists($PAGE->activityheader, 'disable')) {
    $PAGE->activityheader->disable();
}

// ── Data helpers ──────────────────────────────────────────────────────────

/**
 * Load all non-anonymous attempts for a problem, grouped by userid.
 */
function aicode_rpt_attempts_by_user(int $problemid): array {
    global $DB;
    $attempts = $DB->get_records_select(
        'aicode_attempts',
        'problemid = :pid AND userid IS NOT NULL',
        ['pid' => $problemid],
        'timecreated ASC'
    );
    $byuser = [];
    foreach ($attempts as $a) {
        $byuser[(int)$a->userid][] = $a;
    }
    return $byuser;
}

/**
 * Determine display status for a student's attempts.
 * Returns: key, label, cls (Bootstrap badge class).
 */
function aicode_rpt_status(array $attempts): array {
    if (empty($attempts)) {
        return ['key' => 'notstarted', 'label' => 'Belum Mulai',      'cls' => 'badge bg-secondary'];
    }
    foreach ($attempts as $a) {
        $r = json_decode($a->result_json ?? '{}', true);
        if (!empty($r['teacher_review_requested'])) {
            return ['key' => 'submitted', 'label' => 'Sudah Submit',   'cls' => 'badge bg-success'];
        }
    }
    return ['key' => 'inprogress', 'label' => 'Sedang Mengerjakan', 'cls' => 'badge bg-warning text-dark'];
}

/**
 * Summarise hint usage across all attempts.
 */
function aicode_rpt_hints(array $attempts): array {
    $total = 0;
    foreach ($attempts as $a) {
        $hints = json_decode($a->used_hints_json ?? '[]', true);
        if (!is_array($hints)) {
            continue;
        }
        $total += count($hints);
    }
    if ($total === 0) {
        return ['used' => false, 'label' => '—', 'total' => 0];
    }
    return ['used' => true, 'label' => $total . '×', 'total' => $total];
}

/**
 * Get timestamp of the most recent attempt.
 */
function aicode_rpt_last_active(array $attempts): ?int {
    if (empty($attempts)) {
        return null;
    }
    return (int)max(array_column($attempts, 'timecreated'));
}

/**
 * Get the best available code submission.
 * Prefers "Send to Teacher" attempts (have full code), then any run with stored code.
 */
function aicode_rpt_submitted_code(array $attempts): ?array {
    // Sort DESC by time.
    $sorted = $attempts;
    usort($sorted, fn($a, $b) => (int)$b->timecreated - (int)$a->timecreated);

    // 1. Prefer explicit submit.
    foreach ($sorted as $a) {
        $r = json_decode($a->result_json ?? '{}', true);
        if (!empty($r['teacher_review_requested']) && isset($r['code']) && (string)$r['code'] !== '') {
            return ['code' => $r['code'], 'type' => 'submit', 'time' => (int)$a->timecreated];
        }
    }
    // 2. Fall back to any stored run code.
    foreach ($sorted as $a) {
        $r = json_decode($a->result_json ?? '{}', true);
        if (isset($r['code']) && (string)$r['code'] !== '') {
            return ['code' => $r['code'], 'type' => 'run', 'time' => (int)$a->timecreated];
        }
    }
    return null;
}

/**
 * Get the stored console/stdout output for the best available submission.
 * Priority: (1) submit with browser-captured console_output, (2) any attempt stdout from executor.
 */
function aicode_rpt_submitted_output(array $attempts): string {
    $sorted = $attempts;
    usort($sorted, fn($a, $b) => (int)$b->timecreated - (int)$a->timecreated);

    // 1. Prefer submit attempt that includes browser-captured console output.
    foreach ($sorted as $a) {
        $r = json_decode($a->result_json ?? '{}', true);
        if (!empty($r['teacher_review_requested']) && isset($r['console_output']) && trim((string)$r['console_output']) !== '') {
            return (string)$r['console_output'];
        }
    }
    // 2. Fall back to stdout stored by the executor (Node.js captures console.log to stdout).
    foreach ($sorted as $a) {
        $r = json_decode($a->result_json ?? '{}', true);
        if (isset($r['stdout']) && trim((string)$r['stdout']) !== '') {
            return (string)$r['stdout'];
        }
    }
    return '';
}

/**
 * Get the most recent successful AI feedback for a student.
 */
function aicode_rpt_ai_feedback(array $attempts): ?array {
    $sorted = $attempts;
    usort($sorted, fn($a, $b) => (int)$b->timecreated - (int)$a->timecreated);
    foreach ($sorted as $a) {
        if (!empty($a->ai_feedback_json)) {
            $f = json_decode($a->ai_feedback_json, true);
            if (is_array($f) && ($f['status'] ?? '') === 'success') {
                return $f;
            }
        }
    }
    return null;
}

/**
 * Format a Unix timestamp as Indonesian date-time in WIB (Asia/Jakarta, UTC+7).
 * Output example: "13 Apr 2026, 17:26 WIB"
 */
function aicode_rpt_format_wib(int $ts): string {
    if ($ts <= 0) {
        return '—';
    }
    static $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    try {
        $dt = new DateTime('@' . $ts);
        $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
        return $dt->format('d') . ' ' . $bulan[(int)$dt->format('n')] . ' ' . $dt->format('Y, H:i') . ' WIB';
    } catch (Throwable $e) {
        return '—';
    }
}

/**
 * Human-readable relative time (falls back to WIB date for older timestamps).
 */
function aicode_rpt_timediff(?int $ts): string {
    if (!$ts) {
        return '—';
    }
    $d = time() - $ts;
    if ($d < 60)     return 'Baru saja';
    if ($d < 3600)   return intval($d / 60) . ' menit lalu';
    if ($d < 86400)  return intval($d / 3600) . ' jam lalu';
    if ($d < 604800) return intval($d / 86400) . ' hari lalu';
    return aicode_rpt_format_wib($ts);
}

/**
 * Return a Bootstrap colour class for a diagnosis category.
 */
function aicode_rpt_category_cls(string $cat): string {
    $map = [
        'syntax'      => 'danger',
        'runtime'     => 'warning',
        'logic'       => 'info',
        'style'       => 'secondary',
        'security'    => 'dark',
        'performance' => 'primary',
    ];
    return 'badge bg-' . ($map[$cat] ?? 'secondary');
}

// ── Load main data ────────────────────────────────────────────────────────

$byuser   = aicode_rpt_attempts_by_user($aicode->id);
$students = get_enrolled_users($context, 'mod/aicode:submit', 0, 'u.id, u.firstname, u.lastname, u.email, u.idnumber');

$gradesuserids = array_keys($students);
$gradinginfo   = grade_get_grades($course->id, 'mod', 'aicode', $aicode->id, $gradesuserids);
$gradeitem     = !empty($gradinginfo->items) ? reset($gradinginfo->items) : null;
$gradevalues   = $gradeitem ? $gradeitem->grades : [];

// ── Output ────────────────────────────────────────────────────────────────

echo $OUTPUT->header();
echo '<div class="aicode-rpt-wrap">';

// ── Shared page header ────────────────────────────────────────────────────

$modelabel = $aicode->mode === 'exam'
    ? '<span class="badge bg-danger ms-2">Mode Ujian</span>'
    : '<span class="badge bg-primary ms-2">Mode Latihan</span>';

echo '<div class="aicode-rpt-page-head">';

// Breadcrumb navigation to section and course dashboards.
$cm_section = $DB->get_field('course_modules', 'section', ['id' => $cm->id]);
echo '<div class="aicode-rpt-breadcrumb-nav" style="display:flex;gap:16px;margin-bottom:8px;font-size:.8125rem">';
echo html_writer::link(
    new moodle_url('/mod/aicode/course_report.php', ['courseid' => $course->id]),
    '📊 Dashboard Course',
    ['class' => 'aicode-rpt-back']
);
echo '<span style="color:#dee2e6">|</span>';
echo html_writer::link(
    new moodle_url('/mod/aicode/section_report.php', ['courseid' => $course->id, 'sectionid' => $cm_section]),
    '📋 Dashboard Section',
    ['class' => 'aicode-rpt-back']
);
echo '<span style="color:#dee2e6">|</span>';
echo html_writer::link(
    new moodle_url('/mod/aicode/view.php', ['id' => $id]),
    '&larr; Kembali ke Aktivitas',
    ['class' => 'aicode-rpt-back']
);
echo '</div>';
if ($userid > 0) {
    $headstudent = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname');
    echo '<h2>Detail Siswa: '
        . html_writer::span(fullname($headstudent), 'aicode-rpt-student-name') . '</h2>';
} else {
    echo '<h2>Laporan Guru</h2>';
}
echo '<div class="aicode-rpt-meta">' . format_string($aicode->name) . $modelabel . '</div>';
echo '</div>';

// ── Breadcrumb tabs (overview ↔ detail) ───────────────────────────────────

echo '<div class="aicode-rpt-tabs">';
echo html_writer::link(
    new moodle_url('/mod/aicode/report.php', ['id' => $id]),
    'Semua Siswa',
    ['class' => 'aicode-rpt-tab' . ($userid === 0 ? ' active' : '')]
);
if ($userid > 0) {
    echo html_writer::link(
        new moodle_url('/mod/aicode/report.php', ['id' => $id, 'userid' => $userid]),
        'Detail: ' . fullname($headstudent),
        ['class' => 'aicode-rpt-tab active']
    );
}
echo '</div>';

// ==========================================================================
// DETAIL VIEW
// ==========================================================================

if ($userid > 0) {

    $userattempts = $byuser[$userid] ?? [];
    $status       = aicode_rpt_status($userattempts);
    $hints        = aicode_rpt_hints($userattempts);
    $submitcode   = aicode_rpt_submitted_code($userattempts);
    $currentgrade = isset($gradevalues[$userid]) ? $gradevalues[$userid]->grade : null;

    // Get teacher notes + correction for the latest attempt.
    $sortedattempts = $userattempts;
    usort($sortedattempts, fn($a, $b) => (int)$b->timecreated - (int)$a->timecreated);
    $latestattempt = !empty($sortedattempts) ? $sortedattempts[0] : null;

    $teachernotes     = '';
    $existingoverride = null;
    $existingcorrdata = [];
    if ($latestattempt) {
        $existingoverride = $DB->get_record('aicode_teacher_overrides', ['attemptid' => $latestattempt->id]);
        if ($existingoverride) {
            $existingcorrdata = json_decode($existingoverride->corrected_feedback_json ?? '{}', true) ?: [];
            $teachernotes     = $existingcorrdata['notes'] ?? '';
        }
    }
    // Latest successful AI feedback for this student (used in the correction panel).
    $latestaifb = aicode_rpt_ai_feedback($userattempts);

    // ── Stat cards ────────────────────────────────────────────────────────

    echo '<div class="aicode-rpt-cards">';

    echo '<div class="aicode-rpt-card">';
    echo '<div class="aicode-rpt-card-label">Status</div>';
    echo '<div class="aicode-rpt-card-value">';
    echo '<span class="' . $status['cls'] . '">' . $status['label'] . '</span>';
    echo '</div></div>';

    echo '<div class="aicode-rpt-card">';
    echo '<div class="aicode-rpt-card-label">Jumlah Percobaan</div>';
    echo '<div class="aicode-rpt-card-value aicode-rpt-num">';
    if (count($userattempts) > 0) {
        echo '<a href="#aicode-rpt-history-section" class="aicode-rpt-attempt-count-link"'
            . ' title="Klik untuk melihat semua riwayat jawaban">'
            . count($userattempts) . '</a>';
    } else {
        echo '0';
    }
    echo '</div>';
    if (count($userattempts) > 0) {
        echo '<div class="aicode-rpt-card-pct">klik untuk lihat riwayat</div>';
    }
    echo '</div>';

    if ($aicode->mode !== 'exam') {
        echo '<div class="aicode-rpt-card">';
        echo '<div class="aicode-rpt-card-label">AI Hint Digunakan</div>';
        echo '<div class="aicode-rpt-card-value aicode-rpt-num">' . ($hints['used'] ? $hints['total'] : '—') . '</div>';
        if ($hints['used']) {
            echo '<div class="aicode-rpt-card-pct">kali</div>';
        }
        echo '</div>';
    }

    echo '<div class="aicode-rpt-card">';
    echo '<div class="aicode-rpt-card-label">Nilai Saat Ini</div>';
    echo '<div class="aicode-rpt-card-value aicode-rpt-num">'
        . ($currentgrade !== null ? round((float)$currentgrade, 1) : '—') . '</div>';
    echo '</div>';

    echo '</div>'; // .aicode-rpt-cards

    // ── Submitted code ────────────────────────────────────────────────────

    echo '<div class="aicode-rpt-section">';
    echo '<h4>Kode Terakhir Dikumpulkan</h4>';
    if ($submitcode) {
        $typelabel = $submitcode['type'] === 'submit'
            ? '<span class="badge bg-success">Submit ke Guru</span>'
            : '<span class="badge bg-secondary">Run Terakhir</span>';
        echo '<div class="aicode-rpt-code-meta">'
            . $typelabel . ' &middot; ' . aicode_rpt_timediff($submitcode['time'])
            . '</div>';
        echo '<pre class="aicode-rpt-code">' . s($submitcode['code']) . '</pre>';

        // ── Preview (HTML template) + stored output ───────────────────────────
        $htmltpl    = $aicode->htmltemplate ?? '';
        $csstpl     = $aicode->csstemplate  ?? '';
        $jscode     = $submitcode['code'];
        $hasHtmlTpl = trim($htmltpl) !== '';
        $pid        = 'rptpv' . (int)$userid;

        // Stored output: read from DB (executor stdout or browser-captured console_output).
        // This is reliable — no re-running code in the teacher's browser needed.
        $storedoutput = aicode_rpt_submitted_output($userattempts);

        // ── HTML-template mode: "Lihat Preview" button with rendered iframe ──
        if ($hasHtmlTpl) {
            $safejs  = str_replace('</', '<\/', $jscode);
            $safecss = str_replace('</', '<\/', $csstpl);

            $jsinner = '(function(){'
                . 'function post(type,payload){'
                . 'try{parent.postMessage({source:"aicode-preview",type:type,payload:payload},"*");}catch(e){}}'
                . '["log","info","warn","error"].forEach(function(level){'
                . 'var orig=console[level];'
                . 'console[level]=function(){'
                . 'try{post("console",{level:level,args:Array.prototype.slice.call(arguments).map(String)});}catch(e){}'
                . 'if(orig)orig.apply(console,arguments);};});'
                . 'window.addEventListener("error",function(e){'
                . 'post("error",{message:e.message||"Error",stack:e.error&&e.error.stack?e.error.stack:""});});'
                . 'window.addEventListener("unhandledrejection",function(e){'
                . 'var r=e&&e.reason?e.reason:"Unhandled promise rejection";'
                . 'post("error",{message:r.message?r.message:String(r),stack:r.stack?r.stack:""});});'
                . 'try{'
                . $safejs
                . '}catch(err){'
                . 'post("error",{message:err&&err.message?err.message:String(err),stack:err&&err.stack?err.stack:""});}'
                . '})();';

            $srcdoc = '<!doctype html><html><head><meta charset="utf-8">'
                . '<meta name="viewport" content="width=device-width,initial-scale=1">'
                . '<style>body{margin:0;padding:8px}' . $safecss . '</style>'
                . '</head><body>' . $htmltpl
                . '<script>' . $jsinner . '</script>'
                . '</body></html>';

            $sdVarName  = '_rptsd_' . $pid;
            $srcdocJson = json_encode($srcdoc, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            echo '<script>window[' . json_encode($sdVarName) . ']=' . $srcdocJson . ';</script>';

            $svgEye    = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 20 20"'
                . ' fill="currentColor" style="margin-right:5px;vertical-align:-1px">'
                . '<path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>'
                . '<path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7'
                . 'c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"'
                . ' clip-rule="evenodd"/></svg>';
            $svgEyeOff = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 20 20"'
                . ' fill="currentColor" style="margin-right:5px;vertical-align:-1px">'
                . '<path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414'
                . 'l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512'
                . ' 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0'
                . ' 00-5.478-5.478z" clip-rule="evenodd"/>'
                . '<path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458'
                . ' 10c1.274 4.057 5.064 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/></svg>';

            $jBtn    = json_encode($pid . '-btn');
            $jPanel  = json_encode($pid);
            $jClose  = json_encode($pid . '-close');
            $jFrame  = json_encode($pid . '-frame');
            $jSdVar  = json_encode($sdVarName);
            $jOpen   = json_encode($svgEyeOff . 'Sembunyikan Preview');
            $jClose2 = json_encode($svgEye    . 'Lihat Preview');
            $jOutPanel = json_encode($pid . '-outpanel');
            $jOutList  = json_encode($pid . '-outlist');
            $jOutCount = json_encode($pid . '-outcount');

            echo '<div class="aicode-rpt-preview-wrap">';
            echo '<button type="button" class="btn btn-sm btn-outline-info aicode-rpt-pv-btn"'
                . ' id="' . $pid . '-btn" aria-expanded="false">'
                . $svgEye . 'Lihat Preview</button>';

            echo '<div class="aicode-rpt-preview-panel" id="' . $pid . '" style="display:none">';
            echo '<div class="aicode-rpt-preview-header">';
            echo '<span class="aicode-rpt-preview-title">PREVIEW</span>';
            echo '<button type="button" class="aicode-rpt-preview-close" id="' . $pid . '-close">'
                . '&#10005; Sembunyikan</button>';
            echo '</div>';
            echo '<iframe class="aicode-rpt-preview-iframe" id="' . $pid . '-frame"'
                . ' style="min-height:380px" sandbox="allow-scripts"'
                . ' referrerpolicy="no-referrer"></iframe>';

            // Console output panel for HTML-template mode (receives live postMessages).
            echo '<div id="' . $pid . '-outpanel" class="aicode-rpt-outpanel" style="display:none">';
            echo '<div class="aicode-rpt-outpanel-hdr">';
            echo '<span class="aicode-rpt-outpanel-title">OUTPUT</span>';
            echo '<span id="' . $pid . '-outcount" class="aicode-rpt-outpanel-count"></span>';
            echo '</div>';
            echo '<div id="' . $pid . '-outlist" class="aicode-rpt-outpanel-list"></div>';
            echo '</div>';

            echo '</div>'; // preview-panel
            echo '</div>'; // preview-wrap

            echo '<script>(function(){';
            echo 'var btn=document.getElementById(' . $jBtn . ');';
            echo 'var panel=document.getElementById(' . $jPanel . ');';
            echo 'var cls=document.getElementById(' . $jClose . ');';
            echo 'var frm=document.getElementById(' . $jFrame . ');';
            echo 'var loaded=false;';
            echo 'window.addEventListener("message",function(e){'
                . 'var d=e.data||{};'
                . 'if(d.source!=="aicode-preview")return;'
                . 'var op=document.getElementById(' . $jOutPanel . ');'
                . 'var ol=document.getElementById(' . $jOutList . ');'
                . 'var oc=document.getElementById(' . $jOutCount . ');'
                . 'if(!op||!ol)return;'
                . 'if(d.type==="console"){'
                . 'var level=d.payload&&d.payload.level?d.payload.level:"log";'
                . 'var args=d.payload&&d.payload.args?d.payload.args:[];'
                . 'var msg=args.join(" ");'
                . 'if(!msg)return;'
                . 'op.style.display="block";'
                . 'if(oc){var n=(parseInt(oc.dataset.count||"0",10)||0)+1;oc.dataset.count=String(n);oc.textContent=n+" line"+(n!==1?"s":"");}'
                . 'var el=document.createElement("div");'
                . 'el.className="aicode-rpt-outline"+(level==="warn"?" aicode-rpt-outwarn":level==="error"?" aicode-rpt-outerr":"");'
                . 'el.textContent=msg;'
                . 'ol.appendChild(el);ol.scrollTop=ol.scrollHeight;}'
                . 'if(d.type==="error"){'
                . 'var emsg=d.payload&&d.payload.message?d.payload.message:"Error";'
                . 'op.style.display="block";'
                . 'var el2=document.createElement("div");'
                . 'el2.className="aicode-rpt-outline aicode-rpt-outerr";'
                . 'el2.textContent=emsg;'
                . 'ol.appendChild(el2);ol.scrollTop=ol.scrollHeight;}'
                . '},false);';
            echo 'function openPanel(){'
                . 'if(!loaded){frm.srcdoc=window[' . $jSdVar . '];loaded=true;}'
                . 'panel.style.display="block";'
                . 'btn.setAttribute("aria-expanded","true");'
                . 'btn.innerHTML=' . $jOpen . ';}';
            echo 'function closePanel(){'
                . 'panel.style.display="none";'
                . 'btn.setAttribute("aria-expanded","false");'
                . 'btn.innerHTML=' . $jClose2 . ';}';
            echo 'btn.addEventListener("click",function(){'
                . 'btn.getAttribute("aria-expanded")==="true"?closePanel():openPanel();});';
            echo 'cls.addEventListener("click",closePanel);';
            echo '})();</script>';
        }

        // ── Stored console output — hidden by default, toggled by button ────────
        // Works for both console-only code and HTML-template code.
        // Data comes from: (1) console_output sent at submit time, or (2) executor stdout.
        if ($storedoutput !== '' || !$hasHtmlTpl) {
            $lines     = $storedoutput !== '' ? explode("\n", rtrim($storedoutput)) : [];
            $linecount = count($lines);
            $opid      = $pid . '-out';

            $svgTerminal = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 20 20"'
                . ' fill="currentColor" style="margin-right:5px;vertical-align:-1px">'
                . '<path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0'
                . ' 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414'
                . 'L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z"'
                . ' clip-rule="evenodd"/></svg>';

            $jOBtn    = json_encode($opid . '-btn');
            $jOPanel  = json_encode($opid . '-panel');
            $jOClose  = json_encode($opid . '-close');
            $jOOpen   = json_encode($svgTerminal . 'Sembunyikan Output');
            $jOClose2 = json_encode($svgTerminal . 'Lihat Output');
            $jLineCount = $storedoutput !== ''
                ? json_encode($linecount . ' line' . ($linecount !== 1 ? 's' : ''))
                : json_encode('');

            echo '<div class="aicode-rpt-preview-wrap">';
            echo '<button type="button" class="btn btn-sm btn-outline-secondary aicode-rpt-pv-btn"'
                . ' id="' . $opid . '-btn" aria-expanded="false">'
                . $svgTerminal . 'Lihat Output</button>';

            echo '<div class="aicode-rpt-preview-panel" id="' . $opid . '-panel" style="display:none">';
            echo '<div class="aicode-rpt-preview-header">';
            echo '<span class="aicode-rpt-preview-title">OUTPUT</span>';
            if ($storedoutput !== '') {
                echo '<span class="aicode-rpt-outpanel-count" style="margin-left:8px">'
                    . $linecount . ' line' . ($linecount !== 1 ? 's' : '') . '</span>';
            }
            echo '<button type="button" class="aicode-rpt-preview-close" id="' . $opid . '-close">'
                . '&#10005; Sembunyikan</button>';
            echo '</div>';

            echo '<div class="aicode-rpt-outpanel-list">';
            if ($storedoutput !== '') {
                foreach ($lines as $line) {
                    echo '<div class="aicode-rpt-outline">' . s($line) . '</div>';
                }
            } else {
                echo '<div class="aicode-rpt-outline aicode-rpt-outinfo">'
                    . 'Output belum tersedia. Akan muncul otomatis setelah siswa menjalankan kode.'
                    . '</div>';
            }
            echo '</div>';
            echo '</div>'; // preview-panel
            echo '</div>'; // preview-wrap

            echo '<script>(function(){'
                . 'var b=document.getElementById(' . $jOBtn . ');'
                . 'var p=document.getElementById(' . $jOPanel . ');'
                . 'var c=document.getElementById(' . $jOClose . ');'
                . 'function open_(){p.style.display="block";b.setAttribute("aria-expanded","true");b.innerHTML=' . $jOOpen . ';}'
                . 'function close_(){p.style.display="none";b.setAttribute("aria-expanded","false");b.innerHTML=' . $jOClose2 . ';}'
                . 'b.addEventListener("click",function(){b.getAttribute("aria-expanded")==="true"?close_():open_();});'
                . 'c.addEventListener("click",close_);'
                . '})();</script>';
        }

    } else {
        echo '<div class="aicode-rpt-empty">'
            . 'Kode tidak tersedia. Siswa belum menggunakan tombol &ldquo;Submit&rdquo; atau &ldquo;Run&rdquo; setelah pembaruan plugin.'
            . '</div>';
    }
    echo '</div>';

    // ── Set grade form (di bawah kode) ────────────────────────────────────

    $gradeaction = new moodle_url('/mod/aicode/report.php', [
            'id'     => $id,
            'userid' => $userid,
            'action' => 'savegrade',
        ]);
        echo '<div class="aicode-rpt-section">';
        echo '<h4>' . get_string('setgrade', 'aicode') . '</h4>';
        echo '<form method="post" action="' . s($gradeaction->out(false)) . '" class="aicode-rpt-grade-form">';
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
        echo '<input type="hidden" name="gradeduserid" value="' . (int)$userid . '">';
        echo '<div class="input-group" style="max-width:260px">';
        echo '<input type="number" name="rawgrade" class="form-control" min="0" max="100" step="0.5" '
            . 'placeholder="0 – 100" value="'
            . ($currentgrade !== null ? s(round((float)$currentgrade, 1)) : '') . '">';
        echo '<button type="submit" class="btn btn-primary">Simpan Nilai</button>';
        echo '</div>';
        echo '<div class="form-text text-muted mt-1">' . get_string('gradeemptyreset', 'aicode') . '</div>';
        $gradebookurl = new moodle_url('/grade/report/grader/index.php', ['id' => $course->id]);
        echo '<div class="form-text mt-1"><a href="' . s($gradebookurl->out(false)) . '">'
            . get_string('viewingradebook', 'aicode') . '</a></div>';
    echo '</form>';
    echo '</div>';

    // ── AI Feedback Correction Panel ──────────────────────────────────────

    if ($canoverride && $aicode->mode !== 'exam') {
        $corractionurl = new moodle_url('/mod/aicode/report.php', [
            'id'     => $id,
            'userid' => $userid,
            'action' => 'savecorrection',
        ]);

        // Pre-fill values from existing correction.
        $prerating      = $existingoverride ? (int)($existingoverride->feedback_rating ?? 0) : 0;
        $preuseasex     = $existingoverride ? (int)($existingoverride->use_as_example ?? 0) : 0;
        $prediagshort   = $existingcorrdata['corrected_diagnosis']['message_short'] ?? '';
        $prediaglong    = $existingcorrdata['corrected_diagnosis']['message_long'] ?? '';
        $prehints       = implode("\n", $existingcorrdata['corrected_hints'] ?? []);
        $prefix         = $existingcorrdata['corrected_suggested_fix'] ?? '';
        $hasprior       = $existingoverride !== null;
        // Prefer timeupdated from JSON (always set to time() on save); fall back to DB timecreated.
        $priorupdated   = 0;
        if ($hasprior) {
            $priorupdated = (int)($existingcorrdata['timeupdated'] ?? 0);
            if ($priorupdated <= 0) {
                $priorupdated = (int)($existingoverride->timecreated ?? 0);
            }
        }

        echo '<div class="aicode-rpt-section aicode-rpt-correction-section">';
        echo '<h4 class="aicode-rpt-correction-title">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="color:#198754;margin-right:6px;flex-shrink:0;vertical-align:-2px">'
            . '<path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>'
            . '</svg>';
        echo 'Evaluasi &amp; Koreksi Feedback AI';
        if ($hasprior) {
            echo ' <span class="aicode-rpt-correction-badge">';
            echo '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 20 20" fill="currentColor" style="margin-right:3px;vertical-align:-1px">'
                . '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>'
                . '</svg>Sudah dikoreksi</span>';
        }
        echo '</h4>';

        echo '<p class="aicode-rpt-correction-desc">Nilai kualitas feedback AI dan koreksi bagian yang kurang tepat. '
            . 'Koreksi yang disetujui akan dipakai sebagai <strong>contoh few-shot</strong> untuk meningkatkan feedback AI berikutnya.</p>';

        if ($hasprior) {
            $ratinglabels = ['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Baik', 'Sangat Baik'];
            echo '<div class="aicode-rpt-correction-existing alert alert-success py-2 px-3 mb-3">';
            echo '<div class="d-flex align-items-center gap-3 flex-wrap">';
            if ($priorupdated > 0) {
                echo '<span class="fw-semibold small">Terakhir diperbarui: ' . aicode_rpt_format_wib($priorupdated) . '</span>';
            } else {
                echo '<span class="fw-semibold small text-muted">Koreksi tersimpan ✓</span>';
            }
            if ($prerating >= 1) {
                echo '<span class="small">Rating: ';
                for ($si = 1; $si <= 5; $si++) {
                    echo '<span style="color:' . ($si <= $prerating ? '#ffc107' : '#dee2e6') . ';font-size:1rem">&#9733;</span>';
                }
                echo ' <em>' . ($ratinglabels[$prerating] ?? '') . '</em></span>';
            }
            if ($preuseasex) {
                echo '<span class="badge bg-success">&#10003; Aktif sebagai contoh few-shot</span>';
            } else {
                echo '<span class="badge bg-secondary">Belum dijadikan contoh few-shot</span>';
            }
            echo '</div>';
            echo '</div>';
        }

        // Show current AI feedback summary (read-only) for context.
        if ($latestaifb !== null) {
            $aidiag = $latestaifb['diagnosis'] ?? [];
            $aishort = $aidiag['message_short'] ?? '';
            $ailong  = $aidiag['message_long'] ?? '';
            $aicat   = $aidiag['category'] ?? 'runtime';
            $aiconf  = isset($aidiag['confidence']) ? (int)round((float)$aidiag['confidence'] * 100) : null;

            echo '<div class="aicode-rpt-correction-aifb-preview">';
            echo '<div class="aicode-rpt-correction-aifb-label">Feedback AI saat ini (untuk dikoreksi):</div>';
            echo '<div class="aicode-rpt-correction-aifb-content">';
            echo '<div class="d-flex align-items-center gap-2 mb-2">';
            echo '<span class="' . aicode_rpt_category_cls($aicat) . '">' . ucfirst($aicat) . '</span>';
            if ($aiconf !== null) {
                $confcls = $aiconf >= 70 ? 'bg-success' : ($aiconf >= 40 ? 'bg-warning text-dark' : 'bg-danger');
                echo '<span class="badge ' . $confcls . '">Kepercayaan: ' . $aiconf . '%</span>';
            }
            echo '</div>';
            if ($aishort !== '') {
                echo '<div class="fw-semibold mb-1">' . s($aishort) . '</div>';
            }
            if ($ailong !== '') {
                echo '<div class="text-muted small" style="white-space:pre-line">' . s(mb_substr($ailong, 0, 300)) . (mb_strlen($ailong) > 300 ? '…' : '') . '</div>';
            }
            $aihints = $latestaifb['hints'] ?? [];
            if (!empty($aihints)) {
                echo '<div class="mt-2"><strong class="small">Petunjuk AI:</strong><ol class="mb-0 mt-1" style="font-size:.85rem">';
                foreach (array_slice($aihints, 0, 3) as $ah) {
                    echo '<li>' . s($ah['hint'] ?? '') . '</li>';
                }
                echo '</ol></div>';
            }
            $aifix = $latestaifb['suggested_fix'] ?? null;
            if ($aifix && !empty($aifix['explanation'])) {
                echo '<div class="mt-2"><strong class="small">Saran perbaikan AI:</strong>'
                    . '<div class="small text-muted" style="white-space:pre-line">'
                    . s(mb_substr($aifix['explanation'], 0, 200)) . (mb_strlen($aifix['explanation']) > 200 ? '…' : '')
                    . '</div></div>';
            }
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-warning py-2 small mb-3">'
                . 'Belum ada feedback AI yang bisa dikoreksi untuk siswa ini. '
                . 'Feedback AI akan muncul setelah siswa menggunakan tombol <strong>AI Hint</strong>.'
                . '</div>';
        }

        // Correction form.
        echo '<form method="post" action="' . s($corractionurl->out(false)) . '" class="aicode-rpt-correction-form">';
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
        echo '<input type="hidden" name="correctionuserid" value="' . (int)$userid . '">';

        // ── Rating stars ──────────────────────────────────────────────────
        echo '<div class="mb-3">';
        echo '<label class="form-label fw-semibold">Nilai kualitas feedback AI ini</label>';
        echo '<div class="aicode-star-rating" role="group" aria-label="Rating feedback AI">';
        // Reverse order 5→1 for CSS sibling trick, displayed RTL then reversed.
        foreach ([5, 4, 3, 2, 1] as $sv) {
            $checkedattr = ($prerating === $sv) ? ' checked' : '';
            echo '<input type="radio" name="feedback_rating" id="aicode-star-' . $sv . '" value="' . $sv . '"' . $checkedattr . '>';
            $starlabels = ['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Baik', 'Sangat Baik'];
            echo '<label for="aicode-star-' . $sv . '" title="' . $starlabels[$sv] . '">&#9733;</label>';
        }
        echo '</div>';
        echo '<div class="aicode-star-desc text-muted" id="aicode-star-desc" style="font-size:.8rem;min-height:1.2em;margin-top:4px">'
            . ($prerating >= 1 ? (['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Baik', 'Sangat Baik'][$prerating] ?? '') : '')
            . '</div>';
        echo '</div>';

        // Determine whether detail section should start open:
        // open if any field already has content OR existing low rating.
        $hasfilledcorrection = $prediagshort !== '' || $prediaglong !== '' || $prehints !== '' || $prefix !== '' || $teachernotes !== '';
        $detailstartopen     = $hasfilledcorrection || ($prerating > 0 && $prerating < 3);
        $detailstyle         = $detailstartopen ? '' : ' style="display:none"';
        $toggletext          = $detailstartopen
            ? '&#9650; Sembunyikan detail koreksi'
            : '&#9660; Tambahkan koreksi detail (opsional)';

        // ── Low-rating alert (shown via JS when star 1 or 2 is chosen) ────
        $alertinitial = ($prerating > 0 && $prerating < 3) ? '' : ' style="display:none"';
        echo '<div id="aicode-low-rating-alert" class="aicode-rpt-low-rating-alert"' . $alertinitial . '>';
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 20 20" fill="currentColor" style="flex-shrink:0;margin-top:1px">'
            . '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>'
            . '</svg>';
        echo '<div><strong>Rating rendah terdeteksi.</strong> Isi koreksi di bawah agar AI dapat belajar dari penilaian Anda dan menghasilkan feedback yang lebih baik untuk siswa berikutnya.</div>';
        echo '</div>';

        // ── Toggle button ─────────────────────────────────────────────────
        echo '<button type="button" id="aicode-detail-toggle" class="aicode-rpt-detail-toggle" aria-expanded="'
            . ($detailstartopen ? 'true' : 'false') . '">'
            . $toggletext
            . '</button>';

        // ── Collapsible detail section ────────────────────────────────────
        echo '<div id="aicode-correction-detail"' . $detailstyle . '>';

        // Koreksi Diagnosis.
        echo '<div class="aicode-rpt-correction-group">';
        echo '<div class="aicode-rpt-correction-group-title">Koreksi Diagnosis</div>';
        echo '<div class="mb-2">';
        echo '<label class="form-label small fw-semibold" for="aicode-corrshort">Pesan singkat yang lebih tepat</label>';
        echo '<input type="text" id="aicode-corrshort" name="corrected_message_short" class="form-control form-control-sm"'
            . ' placeholder="Contoh: Variabel belum didefinisikan sebelum digunakan"'
            . ' value="' . s($prediagshort) . '">';
        echo '</div>';
        echo '<div class="mb-2">';
        echo '<label class="form-label small fw-semibold" for="aicode-corrlong">Penjelasan lengkap yang lebih tepat</label>';
        echo '<textarea id="aicode-corrlong" name="corrected_message_long" class="form-control form-control-sm" rows="4"'
            . ' placeholder="Jelaskan kesalahan siswa secara lebih detail dan tepat...">'
            . s($prediaglong) . '</textarea>';
        echo '</div>';
        echo '</div>';

        // Koreksi Petunjuk (Hints).
        echo '<div class="aicode-rpt-correction-group">';
        echo '<div class="aicode-rpt-correction-group-title">Koreksi Petunjuk (Hints)</div>';
        echo '<label class="form-label small fw-semibold" for="aicode-corrhints">Satu petunjuk per baris</label>';
        echo '<textarea id="aicode-corrhints" name="corrected_hints" class="form-control form-control-sm font-monospace" rows="3"'
            . ' placeholder="Periksa apakah variabel sudah dideklarasikan dengan let/const/var\nCek urutan deklarasi dan pemanggilan fungsi">'
            . s($prehints) . '</textarea>';
        echo '</div>';

        // Koreksi Saran Perbaikan.
        echo '<div class="aicode-rpt-correction-group">';
        echo '<div class="aicode-rpt-correction-group-title">Koreksi Saran Perbaikan</div>';
        echo '<textarea name="corrected_suggested_fix" class="form-control form-control-sm" rows="3"'
            . ' placeholder="Tuliskan saran perbaikan yang lebih akurat untuk siswa...">'
            . s($prefix) . '</textarea>';
        echo '</div>';

        // Catatan Guru.
        echo '<div class="aicode-rpt-correction-group">';
        echo '<div class="aicode-rpt-correction-group-title">Catatan Guru (opsional)</div>';
        echo '<textarea name="notes" class="form-control form-control-sm" rows="2"'
            . ' placeholder="Catatan tambahan untuk siswa ini...">'
            . s($teachernotes) . '</textarea>';
        echo '</div>';

        echo '</div>'; // #aicode-correction-detail

        // ── Use as example checkbox (always visible) ──────────────────────
        echo '<div class="aicode-rpt-correction-fewshot mt-3">';
        $examplechecked = $preuseasex ? ' checked' : '';
        echo '<div class="form-check">';
        echo '<input class="form-check-input" type="checkbox" name="use_as_example" value="1"'
            . ' id="aicode-use-as-example"' . $examplechecked . '>';
        echo '<label class="form-check-label fw-semibold" for="aicode-use-as-example">&#x1F9E0; Jadikan contoh untuk meningkatkan AI (few-shot learning)</label>';
        echo '</div>';
        echo '<div class="form-text text-muted mt-1" style="font-size:.8rem;padding-left:1.5rem">'
            . 'Jika dicentang, koreksi ini akan diinjeksikan ke prompt Gemini sebagai contoh nyata '
            . 'saat menganalisis kode serupa di masa mendatang — membantu AI belajar dari penilaian guru.'
            . '</div>';
        echo '</div>';

        echo '<div class="d-flex gap-2 mt-3">';
        echo '<button type="submit" class="btn btn-success">&#10003; Simpan Koreksi</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>'; // correction section
    } else {
        // Read-only view for non-teachers: show stored notes + correction if available.
        $hasvisiblecorr = !empty($existingcorrdata['corrected_diagnosis']['message_short']) || $teachernotes !== '';
        if ($hasvisiblecorr) {
            echo '<div class="aicode-rpt-section">';
            echo '<h4>Catatan &amp; Koreksi Guru</h4>';
            if ($teachernotes !== '') {
                echo '<div class="alert alert-info mb-2">' . nl2br(s($teachernotes)) . '</div>';
            }
            if (!empty($existingcorrdata['corrected_diagnosis']['message_short'])) {
                echo '<div class="alert alert-light border mb-0">'
                    . '<strong>' . s($existingcorrdata['corrected_diagnosis']['message_short']) . '</strong>';
                if (!empty($existingcorrdata['corrected_diagnosis']['message_long'])) {
                    echo '<div class="mt-1 small">' . nl2br(s($existingcorrdata['corrected_diagnosis']['message_long'])) . '</div>';
                }
                echo '</div>';
            }
            echo '</div>';
        }
    }

    // ── Attempt history (dengan AI feedback inline per percobaan) ─────────

    echo '<div class="aicode-rpt-section" id="aicode-rpt-history-section">';
    echo '<h4>Riwayat Percobaan'
        . ' <span class="aicode-rpt-history-count badge bg-secondary ms-1">'
        . count($userattempts) . ' percobaan</span>'
        . '</h4>';

    if (!empty($userattempts)) {
        echo '<div class="aicode-rpt-timeline">';
        $sorted = $userattempts;
        usort($sorted, fn($a, $b) => (int)$b->timecreated - (int)$a->timecreated);
        $idx = 1;
        foreach ($sorted as $attempt) {
            $r         = json_decode($attempt->result_json ?? '{}', true);
            $issubmit  = !empty($r['teacher_review_requested']);
            $exitcode  = isset($r['exitCode']) ? (int)$r['exitCode'] : -1;
            $stdout    = isset($r['stdout']) ? (string)$r['stdout'] : '';
            $stderr    = isset($r['stderr']) ? (string)$r['stderr'] : '';
            $code      = isset($r['code'])   ? (string)$r['code']   : null;
            $atfb      = !empty($attempt->ai_feedback_json)
                ? json_decode($attempt->ai_feedback_json, true)
                : null;
            if (!is_array($atfb) || ($atfb['status'] ?? '') !== 'success') {
                $atfb = null;
            }

            if ($exitcode === 0) {
                $exitbadge = '<span class="badge bg-success">&#10003; Exit 0</span>';
            } else if ($issubmit) {
                $exitbadge = '<span class="badge bg-primary">Submit</span>';
            } else {
                $exitbadge = '<span class="badge bg-danger">Exit ' . $exitcode . '</span>';
            }

            $tlid = 'rpt-tl-' . (int)$userid . '-' . $idx;

            echo '<div class="aicode-rpt-tl-item" id="' . $tlid . '">';

            // ── Header row (always visible, clickable to expand) ──────
            echo '<div class="aicode-rpt-tl-header aicode-rpt-tl-toggle" role="button"'
                . ' aria-expanded="false" aria-controls="' . $tlid . '-body"'
                . ' tabindex="0" title="Klik untuk melihat detail percobaan ini">';
            echo '<span class="aicode-rpt-tl-num">' . $idx . '</span>';
            echo $exitbadge;
            if ($issubmit) {
                echo ' <span class="badge bg-primary">Dikirim ke Guru</span>';
            }
            if ($aicode->mode !== 'exam' && !empty($attempt->used_hints_json)) {
                $htmp = json_decode($attempt->used_hints_json, true);
                if (!empty($htmp)) {
                    echo ' <span class="badge bg-light text-dark border">AI Hint: ' . count($htmp) . '×</span>';
                }
            }
            if ($atfb !== null) {
                echo ' <span class="badge bg-info text-dark">AI Feedback</span>';
            }
            echo '<span class="aicode-rpt-tl-time">'
                . aicode_rpt_format_wib((int)$attempt->timecreated)
                . '</span>';
            // Chevron icon – rotates when expanded
            echo '<span class="aicode-rpt-tl-chevron" aria-hidden="true">'
                . '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor">'
                . '<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>'
                . '</svg></span>';
            echo '</div>'; // tl-header / tl-toggle

            // ── Collapsible body (hidden by default) ──────────────────
            echo '<div class="aicode-rpt-tl-body" id="' . $tlid . '-body" hidden>';

            // Code block (no longer nested <details>, just shown inside the body).
            if ($code !== null && $code !== '') {
                echo '<div class="aicode-rpt-tl-subsection">';
                echo '<div class="aicode-rpt-tl-sublabel">Kode</div>';
                echo '<pre class="aicode-rpt-code-sm">' . s($code) . '</pre>';
                echo '</div>';
            }

            // Output & error – always show at least one block.
            if ($stdout !== '') {
                echo '<div class="aicode-rpt-tl-output">';
                echo '<span class="aicode-rpt-tl-block-label">Output</span>';
                echo '<code>' . s(mb_substr($stdout, 0, 400)) . '</code>';
                echo '</div>';
            }
            if ($stderr !== '') {
                echo '<div class="aicode-rpt-tl-error">';
                echo '<span class="aicode-rpt-tl-block-label">Error</span>';
                echo '<code>' . s(mb_substr($stderr, 0, 400)) . '</code>';
                echo '</div>';
            }
            if ($stdout === '' && $stderr === '' && !$issubmit) {
                echo '<div class="aicode-rpt-tl-noout">';
                echo '<span class="aicode-rpt-tl-block-label">Output</span>';
                echo '<span class="text-muted fst-italic">Tidak ada output.</span>';
                echo '</div>';
            }

            // ── AI Feedback ───────────────────────────────────────────
            if ($atfb !== null) {
                $diag    = $atfb['diagnosis'] ?? [];
                $cat     = $diag['category'] ?? 'runtime';
                $conf    = isset($diag['confidence']) ? (int)round((float)$diag['confidence'] * 100) : null;
                $confcls = $conf !== null ? ($conf >= 70 ? 'bg-success' : ($conf >= 40 ? 'bg-warning text-dark' : 'bg-danger')) : 'bg-secondary';

                echo '<div class="aicode-rpt-tl-aifb">';
                echo '<div class="aicode-rpt-tl-aifb-header">';
                echo '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor" style="color:#0d6efd;flex-shrink:0">'
                    . '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>'
                    . '</svg>';
                echo '<span class="aicode-rpt-tl-aifb-label">Feedback AI</span>';
                echo '<span class="' . aicode_rpt_category_cls($cat) . ' ms-1">' . ucfirst($cat) . '</span>';
                if ($conf !== null) {
                    echo '<span class="aicode-rpt-ai-conf ms-2">Kepercayaan: <span class="badge ' . $confcls . '">' . $conf . '%</span></span>';
                }
                echo '</div>';

                if (!empty($diag['message_short'])) {
                    echo '<div class="aicode-rpt-ai-short">' . s($diag['message_short']) . '</div>';
                }
                if (!empty($diag['message_long'])) {
                    echo '<div class="aicode-rpt-ai-long">' . nl2br(s($diag['message_long'])) . '</div>';
                }

                $loc = $atfb['location'] ?? [];
                if (!empty($loc['line']) && (int)$loc['line'] > 0) {
                    echo '<div class="aicode-rpt-ai-loc">Baris ' . (int)$loc['line'];
                    if (!empty($loc['column'])) echo ', Kolom ' . (int)$loc['column'];
                    echo '</div>';
                    if (!empty($loc['snippet'])) {
                        echo '<pre class="aicode-rpt-code-sm">' . s($loc['snippet']) . '</pre>';
                    }
                }

                $aihints = $atfb['hints'] ?? [];
                if (!empty($aihints)) {
                    echo '<div class="aicode-rpt-ai-section-title">Petunjuk AI:</div>';
                    echo '<ol class="aicode-rpt-ai-list">';
                    foreach ($aihints as $h) {
                        echo '<li>' . s($h['hint'] ?? '') . '</li>';
                    }
                    echo '</ol>';
                }

                $fix = $atfb['suggested_fix'] ?? null;
                if ($fix && !empty($fix['explanation'])) {
                    echo '<div class="aicode-rpt-ai-section-title">Saran Perbaikan:</div>';
                    echo '<div class="aicode-rpt-ai-fix">' . nl2br(s($fix['explanation'])) . '</div>';
                    if (!empty($fix['code_patch'])) {
                        echo '<pre class="aicode-rpt-code-sm">' . s($fix['code_patch']) . '</pre>';
                    }
                }

                $materials = $atfb['recommended_materials'] ?? [];
                if (!empty($materials)) {
                    echo '<div class="aicode-rpt-ai-section-title">Materi Rekomendasi:</div>';
                    echo '<ul class="aicode-rpt-ai-list">';
                    foreach ($materials as $mat) {
                        $mattitle = s($mat['title'] ?? '');
                        $maturl   = $mat['url'] ?? '';
                        echo '<li>' . ($maturl ? html_writer::link($maturl, $mattitle, ['target' => '_blank', 'rel' => 'noopener']) : $mattitle);
                        if (!empty($mat['reason'])) {
                            echo ' <span class="text-muted">— ' . s($mat['reason']) . '</span>';
                        }
                        echo '</li>';
                    }
                    echo '</ul>';
                }

                echo '<div class="aicode-rpt-ai-explain">' . s($atfb['explainability'] ?? '') . '</div>';

                // ── AI Performance Metrics Panel ─────────────────────
                $perf = $atfb['_performance'] ?? null;
                if (!empty($perf) && is_array($perf)) {
                    $perflatency = isset($perf['latency_ms']) ? number_format($perf['latency_ms'] / 1000, 2) . 's' : '—';
                    $perfconf    = isset($perf['confidence']) ? (int)round((float)$perf['confidence'] * 100) . '%' : '—';
                    $perfconfval = (float)($perf['confidence'] ?? 0);
                    $perfconfcls = $perfconfval >= 0.7 ? 'bg-success' : ($perfconfval >= 0.4 ? 'bg-warning text-dark' : 'bg-danger');
                    $perfcache   = !empty($perf['from_cache']) ? 'Ya ✓' : 'Tidak';
                    $perfcachecls = !empty($perf['from_cache']) ? 'text-success' : 'text-muted';
                    $perfprovider = s($perf['provider'] ?? '—');
                    $perfmodel   = s($perf['model'] ?? '—');
                    $perftemp    = isset($perf['temperature']) ? number_format((float)$perf['temperature'], 1) : '—';
                    $perfptkn    = isset($perf['prompt_tokens']) ? number_format((int)$perf['prompt_tokens']) : '—';
                    $perfrtkn    = isset($perf['response_tokens']) ? number_format((int)$perf['response_tokens']) : '—';
                    $perfttkn    = isset($perf['total_tokens']) ? number_format((int)$perf['total_tokens']) : '—';
                    $perfplen    = isset($perf['prompt_length']) ? number_format((int)$perf['prompt_length']) . ' chars' : '—';
                    $perftime    = !empty($perf['timestamp']) ? aicode_rpt_format_wib((int)$perf['timestamp']) : '—';

                    $perfuid = 'perf-' . (int)$userid . '-' . $idx;

                    echo '<details class="aicode-rpt-ai-perf mt-2" style="border-top:1px solid rgba(0,0,0,.08);padding-top:6px;">';
                    echo '<summary style="cursor:pointer;font-size:0.82em;color:#555;user-select:none;">';
                    echo '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 16 16" fill="currentColor" '
                        . 'style="vertical-align:-2px;margin-right:4px;opacity:.7;">'
                        . '<path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 '
                        . '2.246 2.246 0 0 1-4.492 0z"/>'
                        . '<path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892'
                        . '-3.433.902-1.793 1.793l.16.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a'
                        . '.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 1.793 1.793l.292-.16a.873.873 0 0 1 1.255.52l.094'
                        . '.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 1.793-1.793'
                        . 'l-.16-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255'
                        . 'l.16-.292c.893-1.64-.902-3.433-1.793-1.793l-.292.16a.873.873 0 0 1-1.255-.52l-.094-.319z"/></svg>';
                    echo 'Performa AI & Prompt</summary>';

                    echo '<div style="margin-top:8px;font-size:0.82em;">';
                    echo '<table class="table table-sm table-borderless mb-2" style="font-size:0.95em;">';
                    echo '<tbody>';
                    $perfrows = [
                        ['Provider', '<strong>' . $perfprovider . '</strong>'],
                        ['Model', '<code style="font-size:0.9em;background:#e9ecef;padding:1px 5px;border-radius:3px;">' . $perfmodel . '</code>'],
                        ['Latensi', !empty($perf['from_cache'])
                            ? '<span class="' . $perfcachecls . ' fw-semibold">Dari Cache</span>'
                            : '<strong>' . $perflatency . '</strong>'],
                        ['Kepercayaan', '<span class="badge ' . $perfconfcls . '">' . $perfconf . '</span>'],
                        ['Token Prompt', $perfptkn],
                        ['Token Respons', $perfrtkn],
                        ['Total Token', $perfttkn],
                        ['Panjang Prompt', $perfplen],
                        ['Temperature', $perftemp],
                        ['Dari Cache', '<span class="' . $perfcachecls . ' fw-semibold">' . $perfcache . '</span>'],
                        ['Waktu Generasi', $perftime],
                    ];
                    foreach ($perfrows as $pr) {
                        echo '<tr><td style="color:#666;white-space:nowrap;width:130px;padding:2px 6px 2px 0;">'
                            . $pr[0] . '</td><td style="padding:2px 0;">' . $pr[1] . '</td></tr>';
                    }
                    echo '</tbody></table>';

                    // Prompt text (collapsible within).
                    $prompttext = $perf['prompt_text'] ?? '';
                    if ($prompttext !== '') {
                        echo '<details class="mt-1" style="border-top:1px dashed rgba(0,0,0,.08);padding-top:4px;">';
                        echo '<summary style="cursor:pointer;font-size:0.9em;color:#777;user-select:none;">'
                            . '📝 Lihat Prompt Lengkap (' . number_format(strlen($prompttext)) . ' chars)</summary>';
                        echo '<pre style="max-height:300px;overflow:auto;background:#f8f9fa;border:1px solid #dee2e6;'
                            . 'border-radius:4px;padding:8px;font-size:0.85em;margin-top:6px;white-space:pre-wrap;'
                            . 'word-wrap:break-word;">' . s($prompttext) . '</pre>';
                        echo '</details>';
                    }

                    echo '</div></details>';
                }

                echo '</div>'; // tl-aifb
            }

            echo '</div>'; // tl-body
            echo '</div>'; // tl-item
            $idx++;
        }
        echo '</div>'; // timeline
    } else {
        echo '<div class="aicode-rpt-empty">Siswa belum melakukan percobaan apapun.</div>';
    }
    echo '</div>'; // section

// ==========================================================================
// OVERVIEW TABLE
// ==========================================================================

} else {

    // ── Compute aggregate stats ───────────────────────────────────────────

    $totalenrolled = count($students);
    $nsubmitted    = 0;
    $ninprogress   = 0;
    $nnotstarted   = 0;
    $totalattempts = 0;
    $nwithcode     = 0;

    $studentstats = [];
    foreach ($students as $stu) {
        $ua  = $byuser[$stu->id] ?? [];
        $st  = aicode_rpt_status($ua);
        $cnt = count($ua);

        if ($st['key'] === 'submitted')  $nsubmitted++;
        if ($st['key'] === 'inprogress') $ninprogress++;
        if ($st['key'] === 'notstarted') $nnotstarted++;
        $totalattempts += $cnt;
        if (aicode_rpt_submitted_code($ua) !== null) $nwithcode++;

        $studentstats[$stu->id] = [
            'attempts' => $ua,
            'status'   => $st,
            'count'    => $cnt,
            'hints'    => aicode_rpt_hints($ua),
            'lastact'  => aicode_rpt_last_active($ua),
            'hascode'  => aicode_rpt_submitted_code($ua) !== null,
        ];
    }
    $avgatt = $totalenrolled > 0 ? round($totalattempts / $totalenrolled, 1) : 0;
    $pctsubmit = $totalenrolled > 0 ? round($nsubmitted / $totalenrolled * 100) : 0;

    // ── Analytics data ────────────────────────────────────────────────────

    // Grade distribution (bins 0-20, 21-40, 41-60, 61-80, 81-100).
    $gradebins       = ['0-20' => 0, '21-40' => 0, '41-60' => 0, '61-80' => 0, '81-100' => 0];
    $ngradedstudents = 0;
    $sumdgrades      = 0.0;
    foreach ($students as $stu) {
        if (!isset($gradevalues[$stu->id])) {
            continue;
        }
        $g = $gradevalues[$stu->id]->grade;
        if (!is_numeric($g)) {
            continue;
        }
        $gf = (float)$g;
        $ngradedstudents++;
        $sumdgrades += $gf;
        if ($gf <= 20)      { $gradebins['0-20']++; }
        elseif ($gf <= 40)  { $gradebins['21-40']++; }
        elseif ($gf <= 60)  { $gradebins['41-60']++; }
        elseif ($gf <= 80)  { $gradebins['61-80']++; }
        else                { $gradebins['81-100']++; }
    }
    $avggrade = $ngradedstudents > 0 ? round($sumdgrades / $ngradedstudents, 1) : 0;

    // Daily activity — last 14 days (PHP-side aggregation, DB-agnostic).
    $dailylabels = [];
    $dailymap    = [];
    for ($doffset = 13; $doffset >= 0; $doffset--) {
        $ts  = mktime(0, 0, 0, (int)date('n'), (int)date('j') - $doffset);
        $dailylabels[] = date('d/m', $ts);
        $dailymap[date('Y-m-d', $ts)] = 0;
    }
    $since = time() - 14 * DAYSECS;
    $recentattempts = $DB->get_records_select(
        'aicode_attempts',
        'problemid = :pid AND userid IS NOT NULL AND timecreated > :since',
        ['pid' => $aicode->id, 'since' => $since],
        'timecreated ASC',
        'id, timecreated'
    );
    foreach ($recentattempts as $ra) {
        $dk = date('Y-m-d', (int)$ra->timecreated);
        if (isset($dailymap[$dk])) {
            $dailymap[$dk]++;
        }
    }
    $dailyvalues         = array_values($dailymap);
    $totalrecentattempts = array_sum($dailyvalues);

    // Error category distribution from stored AI feedback.
    $categorycount = [];
    foreach ($byuser as $uid => $uattempts) {
        foreach ($uattempts as $attempt) {
            if (empty($attempt->ai_feedback_json)) {
                continue;
            }
            $fb = json_decode($attempt->ai_feedback_json, true);
            if (!is_array($fb) || ($fb['status'] ?? '') !== 'success') {
                continue;
            }
            $cat = $fb['diagnosis']['category'] ?? 'unknown';
            $categorycount[$cat] = ($categorycount[$cat] ?? 0) + 1;
        }
    }
    arsort($categorycount);

    // Attempt-count distribution per student (buckets).
    $attemptbuckets = ['0' => 0, '1-3' => 0, '4-7' => 0, '8-15' => 0, '16+' => 0];
    foreach ($studentstats as $stat) {
        $cnt = $stat['count'];
        if ($cnt === 0)      { $attemptbuckets['0']++; }
        elseif ($cnt <= 3)   { $attemptbuckets['1-3']++; }
        elseif ($cnt <= 7)   { $attemptbuckets['4-7']++; }
        elseif ($cnt <= 15)  { $attemptbuckets['8-15']++; }
        else                 { $attemptbuckets['16+']++; }
    }

    // Security violations (graceful — column may not exist on older installs).
    $nsecblocked = 0;
    $nsecwarned  = 0;
    foreach ($byuser as $uid => $uattempts) {
        foreach ($uattempts as $attempt) {
            $sf = !empty($attempt->security_flags)
                ? json_decode($attempt->security_flags, true)
                : null;
            if (!is_array($sf)) {
                continue;
            }
            if (!empty($sf['blocked'])) {
                $nsecblocked++;
            } elseif (empty($sf['safe'])) {
                $nsecwarned++;
            }
        }
    }

    // AI feedback & hint stats.
    $nstudentswithfeedback = 0;
    $totalhints            = 0;
    $nwithhints            = 0;
    foreach ($studentstats as $uid => $stat) {
        foreach ($stat['attempts'] as $attempt) {
            if (!empty($attempt->ai_feedback_json)) {
                $fb = json_decode($attempt->ai_feedback_json, true);
                if (is_array($fb) && ($fb['status'] ?? '') === 'success') {
                    $nstudentswithfeedback++;
                    break;
                }
            }
        }
        if ($stat['hints']['used']) {
            $totalhints += $stat['hints']['total'];
            $nwithhints++;
        }
    }

    // ── Learning Analytics Dashboard ──────────────────────────────────────

    $jchartstatusLabels  = json_encode(['Sudah Submit', 'Sedang Mengerjakan', 'Belum Mulai']);
    $jchartstatusData    = json_encode([$nsubmitted, $ninprogress, $nnotstarted]);
    $jchartgradeLabels   = json_encode(array_keys($gradebins));
    $jchartgradeData     = json_encode(array_values($gradebins));
    $jchartdailyLabels   = json_encode($dailylabels);
    $jchartdailyData     = json_encode($dailyvalues);
    $jchartcatLabels     = json_encode(array_keys($categorycount));
    $jchartcatData       = json_encode(array_values($categorycount));
    $jchartdistLabels    = json_encode(array_keys($attemptbuckets));
    $jchartdistData      = json_encode(array_values($attemptbuckets));

    echo '<div class="aicode-rpt-analytics mb-3">';

    // Header row.
    echo '<div class="aicode-rpt-analytics-hdr">';
    echo '<div style="display:flex;align-items:center;gap:12px">';
    echo '<span style="font-size:1.4rem;line-height:1">📊</span>';
    echo '<div>';
    echo '<div class="aicode-rpt-analytics-title">Learning Analytics Dashboard</div>';
    echo '<div class="aicode-rpt-analytics-sub">Visualisasi kinerja kelas secara real-time</div>';
    echo '</div>';
    echo '</div>';
    echo '<button class="btn btn-sm btn-outline-secondary" id="aicode-analytics-toggle"'
        . ' aria-expanded="false" aria-controls="aicode-analytics-body">Tampilkan Dashboard</button>';
    echo '</div>'; // analytics-hdr

    echo '<div id="aicode-analytics-body" style="display:none">';

    // ── Metrik Kunci (full-width, paling atas) ────────────────────────────
    echo '<div class="aicode-rpt-metrics-top-row">';

    // Progress bar ringkasan kelas.
    echo '<div class="aicode-rpt-metrics-progress">';
    echo '<div class="aicode-rpt-progressbar-label">Progress Kelas</div>';
    echo '<div class="progress" style="height:10px;border-radius:999px">';
    if ($nsubmitted)  echo '<div class="progress-bar bg-success"   style="width:' . round($nsubmitted  / max(1, $totalenrolled) * 100) . '%" title="Submit">' . $nsubmitted . '</div>';
    if ($ninprogress) echo '<div class="progress-bar bg-warning"   style="width:' . round($ninprogress / max(1, $totalenrolled) * 100) . '%" title="Dikerjakan">' . $ninprogress . '</div>';
    if ($nnotstarted) echo '<div class="progress-bar bg-secondary" style="width:' . round($nnotstarted / max(1, $totalenrolled) * 100) . '%" title="Belum mulai">' . $nnotstarted . '</div>';
    echo '</div>';
    echo '<div class="aicode-rpt-progressbar-legend">';
    echo '<span class="aicode-rpt-legend-dot bg-success"></span> Submit &nbsp;';
    echo '<span class="aicode-rpt-legend-dot bg-warning"></span> Dikerjakan &nbsp;';
    echo '<span class="aicode-rpt-legend-dot bg-secondary"></span> Belum Mulai';
    echo '</div>';
    echo '</div>'; // metrics-progress

    echo '<div class="aicode-rpt-metric-list">';
    $topmetrics = [
        ['icon' => '👥', 'label' => 'Total Terdaftar',          'value' => $totalenrolled,          'sub' => 'siswa di kelas',                    'cls' => ''],
        ['icon' => '📤', 'label' => 'Sudah Submit',             'value' => $nsubmitted,             'sub' => $pctsubmit . '% dari kelas',         'cls' => $nsubmitted  > 0 ? 'aicode-rpt-metric-success' : ''],
        ['icon' => '🔄', 'label' => 'Sedang Mengerjakan',       'value' => $ninprogress,            'sub' => 'dalam proses',                      'cls' => $ninprogress > 0 ? 'aicode-rpt-metric-inprog'  : ''],
        ['icon' => '⭕', 'label' => 'Belum Mulai',              'value' => $nnotstarted,            'sub' => 'belum buka aktivitas',              'cls' => ''],
        ['icon' => '📝', 'label' => 'Total Percobaan',          'value' => $totalattempts,          'sub' => 'keseluruhan kelas',                 'cls' => ''],
        ['icon' => '🎯', 'label' => 'Rata-rata Percobaan',      'value' => $avgatt,                 'sub' => 'per siswa',                         'cls' => ''],
        ['icon' => '💾', 'label' => 'Ada Kode Tersimpan',       'value' => $nwithcode,              'sub' => 'siswa punya kode',                  'cls' => ''],
        ['icon' => '🤖', 'label' => 'Siswa Dapat AI Feedback',  'value' => $nstudentswithfeedback,  'sub' => 'dari ' . $totalenrolled . ' siswa', 'cls' => '', 'examhide' => true],
        ['icon' => '💡', 'label' => 'Total Hint Digunakan',     'value' => $totalhints,             'sub' => 'oleh ' . $nwithhints . ' siswa',    'cls' => '', 'examhide' => true],
        ['icon' => '✅', 'label' => 'Sudah Dinilai',            'value' => $ngradedstudents,        'sub' => 'dari ' . $totalenrolled . ' siswa', 'cls' => ''],
        ['icon' => '🔒', 'label' => 'Percobaan Diblokir',       'value' => $nsecblocked,            'sub' => 'security violation',                'cls' => $nsecblocked > 0 ? 'aicode-rpt-metric-danger' : ''],
        ['icon' => '⚠️', 'label' => 'Security Warning',         'value' => $nsecwarned,             'sub' => 'percobaan diperingatkan',           'cls' => $nsecwarned  > 0 ? 'aicode-rpt-metric-warn'   : ''],
    ];
    foreach ($topmetrics as $m) {
        if (!empty($m['examhide']) && $aicode->mode === 'exam') {
            continue;
        }
        echo '<div class="aicode-rpt-metric-item ' . $m['cls'] . '">';
        echo '<span class="aicode-rpt-metric-icon">' . $m['icon'] . '</span>';
        echo '<div class="aicode-rpt-metric-content">';
        echo '<div class="aicode-rpt-metric-label">' . $m['label'] . '</div>';
        echo '<div class="aicode-rpt-metric-value">' . $m['value'] . '</div>';
        echo '<div class="aicode-rpt-metric-sub">' . $m['sub'] . '</div>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>'; // metric-list

    echo '</div>'; // metrics-top-row

    // ── Row 1: Status · Nilai · Aktivitas ────────────────────────────────
    echo '<div class="aicode-rpt-chart-row">';

    // Chart 1 — Status kelas (donut).
    echo '<div class="aicode-rpt-chart-card">';
    echo '<div class="aicode-rpt-chart-title">Status Kelas</div>';
    if ($totalenrolled > 0) {
        echo '<div class="aicode-rpt-chart-wrap"><canvas id="aicode-chart-status" aria-label="Distribusi status kelas"></canvas></div>';
    } else {
        echo '<div class="aicode-rpt-chart-empty">Belum ada data siswa.</div>';
    }
    echo '</div>';

    // Chart 2 — Distribusi nilai (bar).
    echo '<div class="aicode-rpt-chart-card">';
    echo '<div class="aicode-rpt-chart-title">Distribusi Nilai</div>';
    if ($ngradedstudents > 0) {
        echo '<div class="aicode-rpt-chart-wrap"><canvas id="aicode-chart-grades" aria-label="Distribusi nilai siswa"></canvas></div>';
        echo '<div class="aicode-rpt-chart-sub">Rata-rata: <strong>' . $avggrade . '</strong>'
            . ' &nbsp;·&nbsp; Dinilai: <strong>' . $ngradedstudents . '</strong> siswa</div>';
    } else {
        echo '<div class="aicode-rpt-chart-empty">Belum ada siswa yang dinilai.</div>';
    }
    echo '</div>';

    // Chart 3 — Aktivitas harian (line).
    echo '<div class="aicode-rpt-chart-card">';
    echo '<div class="aicode-rpt-chart-title">Aktivitas 14 Hari Terakhir</div>';
    if ($totalrecentattempts > 0) {
        echo '<div class="aicode-rpt-chart-wrap"><canvas id="aicode-chart-daily" aria-label="Percobaan per hari"></canvas></div>';
        echo '<div class="aicode-rpt-chart-sub">Total: <strong>' . $totalrecentattempts . '</strong> percobaan</div>';
    } else {
        echo '<div class="aicode-rpt-chart-empty">Belum ada aktivitas 14 hari terakhir.</div>';
    }
    echo '</div>';

    echo '</div>'; // chart-row 1

    // ── Row 2: Distribusi percobaan · Kategori error · Metrik kunci ───────
    echo '<div class="aicode-rpt-chart-row">';

    // Chart 4 — Distribusi percobaan per siswa (bar).
    echo '<div class="aicode-rpt-chart-card">';
    echo '<div class="aicode-rpt-chart-title">Distribusi Percobaan per Siswa</div>';
    echo '<div class="aicode-rpt-chart-wrap"><canvas id="aicode-chart-attdist" aria-label="Distribusi jumlah percobaan"></canvas></div>';
    echo '</div>';

    // Chart 5 — Kategori error AI (horizontal bar).
    echo '<div class="aicode-rpt-chart-card">';
    echo '<div class="aicode-rpt-chart-title">Kategori Error AI</div>';
    if (!empty($categorycount)) {
        echo '<div class="aicode-rpt-chart-wrap"><canvas id="aicode-chart-categories" aria-label="Kategori error dari AI feedback"></canvas></div>';
    } else {
        echo '<div class="aicode-rpt-chart-empty">Belum ada data AI feedback.</div>';
    }
    echo '</div>';

    echo '</div>'; // chart-row 2
    echo '</div>'; // analytics-body
    echo '</div>'; // analytics section

    // Inject chart data + Chart.js bootstrap.
    // Inject chart data as a single JSON object then bootstrap the analytics module.
    $analyticsdatajson = json_encode([
        'status' => ['labels' => ['Sudah Submit', 'Sedang Mengerjakan', 'Belum Mulai'],
                     'data'   => [$nsubmitted, $ninprogress, $nnotstarted]],
        'grades' => ['labels' => array_keys($gradebins),   'data' => array_values($gradebins)],
        'daily'  => ['labels' => $dailylabels,             'data' => $dailyvalues],
        'cat'    => ['labels' => array_keys($categorycount),  'data' => array_values($categorycount)],
        'dist'   => ['labels' => array_keys($attemptbuckets), 'data' => array_values($attemptbuckets)],
    ], JSON_HEX_TAG | JSON_HEX_AMP);

    // Load Chart.js as a plain synchronous <script> tag — guaranteed available before our IIFE.
    $chartjsurl = $CFG->wwwroot . '/mod/aicode/javascript/chart.min.js';
    echo '<script src="' . s($chartjsurl) . '"></script>';

    echo '<script>';
    echo 'window._aicodeAnalytics = ' . $analyticsdatajson . ';';
    echo <<<'ANALYTICSJS'
(function () {
    "use strict";

    var btn  = document.getElementById("aicode-analytics-toggle");
    var body = document.getElementById("aicode-analytics-body");
    var chartsReady = false;

    /* Chart.js is already loaded synchronously above; this is just a safety wrapper. */
    function loadChartJs(cb) {
        if (window.Chart) { cb(); return; }
        // Rare fallback: poll for up to 3 s if somehow not ready yet.
        var attempts = 0;
        var timer = setInterval(function () {
            attempts++;
            if (window.Chart) {
                clearInterval(timer);
                cb();
            } else if (attempts > 30) {
                clearInterval(timer);
                console.warn("AICode: Chart.js tidak tersedia.");
            }
        }, 100);
    }

    /* Build all five charts — called once after Chart.js is available. */
    function buildCharts() {
        if (chartsReady) { return; }
        chartsReady = true;

        var d = window._aicodeAnalytics;
        Chart.defaults.font.family = "system-ui,-apple-system,sans-serif";
        Chart.defaults.font.size   = 11;
        Chart.defaults.color       = "#6c757d";

        var grey = "#f1f3f5";

        /* 1 — Status donut */
        var c1 = document.getElementById("aicode-chart-status");
        if (c1) {
            new Chart(c1, {
                type: "doughnut",
                data: {
                    labels: d.status.labels,
                    datasets: [{
                        data: d.status.data,
                        backgroundColor: ["#198754", "#ffc107", "#adb5bd"],
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "62%",
                    plugins: {
                        legend: { position: "bottom", labels: { boxWidth: 11, padding: 10 } },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                    var pct   = total > 0 ? Math.round(ctx.parsed / total * 100) : 0;
                                    return " " + ctx.label + ": " + ctx.parsed + " (" + pct + "%)";
                                }
                            }
                        }
                    }
                }
            });
        }

        /* 2 — Grade distribution bar */
        var c2 = document.getElementById("aicode-chart-grades");
        if (c2) {
            new Chart(c2, {
                type: "bar",
                data: {
                    labels: d.grades.labels,
                    datasets: [{
                        label: "Siswa",
                        data: d.grades.data,
                        backgroundColor: ["#dc3545", "#fd7e14", "#ffc107", "#20c997", "#198754"],
                        borderRadius: 4,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, grid: { color: grey } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        /* 3 — Daily activity line */
        var c3 = document.getElementById("aicode-chart-daily");
        if (c3) {
            new Chart(c3, {
                type: "line",
                data: {
                    labels: d.daily.labels,
                    datasets: [{
                        label: "Percobaan",
                        data: d.daily.data,
                        borderColor: "#0d6efd",
                        backgroundColor: "rgba(13,110,253,0.07)",
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: "#0d6efd"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, grid: { color: grey } },
                        x: { grid: { display: false }, ticks: { maxRotation: 45, font: { size: 10 } } }
                    }
                }
            });
        }

        /* 4 — Attempt-count distribution bar */
        var c4 = document.getElementById("aicode-chart-attdist");
        if (c4) {
            new Chart(c4, {
                type: "bar",
                data: {
                    labels: d.dist.labels,
                    datasets: [{
                        label: "Siswa",
                        data: d.dist.data,
                        backgroundColor: "#6f42c1",
                        borderRadius: 4,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, grid: { color: grey } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        /* 5 — Error categories horizontal bar */
        var c5 = document.getElementById("aicode-chart-categories");
        if (c5 && d.cat.labels.length) {
            new Chart(c5, {
                type: "bar",
                data: {
                    labels: d.cat.labels,
                    datasets: [{
                        label: "Frekuensi",
                        data: d.cat.data,
                        backgroundColor: ["#0d6efd","#198754","#dc3545","#ffc107","#6f42c1","#20c997","#fd7e14","#0dcaf0"],
                        borderRadius: 4,
                        borderWidth: 0
                    }]
                },
                options: {
                    indexAxis: "y",
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, grid: { color: grey } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }
    }

    /* Toggle — attached immediately, no CDN wait. */
    if (btn && body) {
        btn.addEventListener("click", function () {
            var isOpen = btn.getAttribute("aria-expanded") === "true";
            if (isOpen) {
                body.style.display = "none";
                btn.setAttribute("aria-expanded", "false");
                btn.textContent = "Tampilkan Dashboard";
            } else {
                body.style.display = "";
                btn.setAttribute("aria-expanded", "true");
                btn.textContent = "Sembunyikan";
                // Two rAF frames: first lets the browser apply display change,
                // second ensures layout/paint is complete so canvas has real dimensions.
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () { loadChartJs(buildCharts); });
                });
            }
        });
    }
}());
ANALYTICSJS;
    echo '</script>';

    // ── Filter bar ────────────────────────────────────────────────────────

    echo '<div class="aicode-rpt-filter-bar">';
    $filteritems = [
        'all'        => 'Semua (' . $totalenrolled . ')',
        'submitted'  => 'Sudah Submit (' . $nsubmitted . ')',
        'inprogress' => 'Sedang Mengerjakan (' . $ninprogress . ')',
        'notstarted' => 'Belum Mulai (' . $nnotstarted . ')',
    ];
    foreach ($filteritems as $fkey => $flabel) {
        $active = ($filter === $fkey) ? ' active' : '';
        echo html_writer::link(
            new moodle_url('/mod/aicode/report.php', ['id' => $id, 'filter' => $fkey]),
            $flabel,
            ['class' => 'aicode-rpt-filter-btn' . $active]
        );
    }
    echo '</div>';

    // ── Students table ────────────────────────────────────────────────────

    echo '<div class="table-responsive">';
    echo '<table class="table table-hover align-middle aicode-rpt-table">';
    echo '<thead class="table-light"><tr>';
    echo '<th style="width:36px">#</th>';
    echo '<th>Nama Siswa</th>';
    echo '<th>Status</th>';
    echo '<th>Percobaan</th>';
    if ($aicode->mode !== 'exam') {
        echo '<th>AI Hint</th>';
    }
    echo '<th>Kode</th>';
    echo '<th>Terakhir Aktif</th>';
    echo '<th>Nilai (0–100)</th>';
    echo '<th></th>';
    echo '</tr></thead>';
    echo '<tbody>';

    $rownum = 0;
    foreach ($students as $stu) {
        $stat   = $studentstats[$stu->id]['status'];
        $ua     = $studentstats[$stu->id]['attempts'];
        $hints  = $studentstats[$stu->id]['hints'];
        $lastact = $studentstats[$stu->id]['lastact'];
        $hascode = $studentstats[$stu->id]['hascode'];
        $cnt    = $studentstats[$stu->id]['count'];

        // Apply filter.
        if ($filter !== 'all' && $stat['key'] !== $filter) {
            continue;
        }
        $rownum++;

        $grade     = isset($gradevalues[$stu->id]) ? $gradevalues[$stu->id]->grade : null;
        $detailurl = new moodle_url('/mod/aicode/report.php', ['id' => $id, 'userid' => $stu->id]);

        echo '<tr>';
        echo '<td class="text-muted small">' . $rownum . '</td>';
        echo '<td>';
        echo '<a href="' . s($detailurl->out(false)) . '" class="fw-semibold text-decoration-none aicode-rpt-student-link">';
        echo fullname($stu);
        echo '</a>';
        echo '</td>';
        echo '<td><span class="' . $stat['cls'] . '">' . $stat['label'] . '</span></td>';
        echo '<td>' . $cnt . '</td>';
        if ($aicode->mode !== 'exam') {
            echo '<td class="small text-muted">' . $hints['label'] . '</td>';
        }
        echo '<td>';
        if ($hascode) {
            echo '<span class="badge bg-success-subtle text-success border border-success-subtle">Ada</span>';
        } else {
            echo '<span class="badge bg-light text-secondary border">—</span>';
        }
        echo '</td>';
        echo '<td class="text-muted small">' . aicode_rpt_timediff($lastact) . '</td>';

        // Inline grade input.
        echo '<td>';
        $gradeaction = new moodle_url('/mod/aicode/report.php', ['id' => $id, 'action' => 'savegrade', 'filter' => $filter]);
        echo '<form method="post" action="' . s($gradeaction->out(false)) . '" class="d-flex gap-1 align-items-center aicode-rpt-inline-grade">';
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
        echo '<input type="hidden" name="gradeduserid" value="' . (int)$stu->id . '">';
        echo '<input type="number" name="rawgrade" class="form-control form-control-sm aicode-rpt-grade-input"'
            . ' min="0" max="100" step="0.5"'
            . ' value="' . ($grade !== null ? s(round((float)$grade, 1)) : '') . '"'
            . ' placeholder="—">';
        echo '<button type="submit" class="btn btn-sm btn-outline-primary" title="'
            . s(get_string('setgrade', 'aicode')) . '">&#10003;</button>';
        echo '</form>';
        echo '</td>';

        echo '<td>';
        echo html_writer::link($detailurl, 'Detail &rarr;', ['class' => 'btn btn-sm btn-outline-secondary']);
        echo '</td>';
        echo '</tr>';
    }

    if ($rownum === 0) {
        echo '<tr><td colspan="9" class="text-center text-muted py-5">Tidak ada siswa yang sesuai filter.</td></tr>';
    }

    echo '</tbody></table>';
    echo '</div>'; // table-responsive

} // end overview

// ── CSS ────────────────────────────────────────────────────────────────────

echo '<style>
/* Full-page layout: expand beyond Moodle default content box */
#region-main,#region-main-box,.course-content,.activity-header{max-width:none!important}
#page-content,#region-main-settings-menu{padding-left:0;padding-right:0}
.activity-information,.activity-header .description,.completion-info{display:none!important}
.aicode-rpt-wrap{max-width:100%;margin:0;padding:0 0 48px}
.aicode-rpt-page-head{margin-bottom:16px}
.aicode-rpt-back{font-size:.8125rem;color:#6c757d;text-decoration:none;display:inline-block;margin-bottom:10px}
.aicode-rpt-back:hover{color:#0d6efd}
.aicode-rpt-page-head h2{margin:0 0 4px;font-size:1.5rem;font-weight:700;display:flex;align-items:center;flex-wrap:wrap;gap:8px}
.aicode-rpt-student-name{font-weight:600;color:#0d6efd}
.aicode-rpt-meta{font-size:.875rem;color:#6c757d;display:flex;align-items:center;gap:6px}
.aicode-rpt-tabs{display:flex;gap:0;border-bottom:2px solid #dee2e6;margin-bottom:24px}
.aicode-rpt-tab{padding:8px 18px;font-size:.875rem;font-weight:500;color:#6c757d;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s}
.aicode-rpt-tab:hover{color:#343a40}
.aicode-rpt-tab.active{color:#0d6efd;border-bottom-color:#0d6efd}
.aicode-rpt-cards{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:24px}
.aicode-rpt-card{background:#fff;border:1px solid #dee2e6;border-radius:.5rem;padding:16px 20px;min-width:140px;flex:1 1 auto;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.aicode-rpt-card-success{border-left:4px solid #198754}
.aicode-rpt-card-warning{border-left:4px solid #ffc107}
.aicode-rpt-card-muted{border-left:4px solid #adb5bd}
.aicode-rpt-card-label{font-size:.72rem;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
.aicode-rpt-card-value{font-size:.9375rem;font-weight:500;line-height:1.2}
.aicode-rpt-num{font-size:1.875rem;font-weight:700;color:#212529;line-height:1}
.aicode-rpt-card-pct{font-size:.75rem;color:#6c757d;margin-top:4px}
.aicode-rpt-progressbar{background:#fff;border:1px solid #dee2e6;border-radius:.5rem;padding:14px 18px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.aicode-rpt-progressbar-label{font-size:.75rem;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px}
.aicode-rpt-progressbar-legend{display:flex;align-items:center;gap:4px;font-size:.78rem;color:#6c757d;margin-top:8px}
.aicode-rpt-legend-dot{display:inline-block;width:10px;height:10px;border-radius:50%}
.aicode-rpt-filter-bar{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px}
.aicode-rpt-filter-btn{padding:5px 16px;border-radius:999px;font-size:.8125rem;font-weight:500;border:1px solid #dee2e6;background:#f8f9fa;color:#495057;text-decoration:none;transition:all .15s}
.aicode-rpt-filter-btn:hover,.aicode-rpt-filter-btn:focus{background:#e9ecef;color:#212529;text-decoration:none}
.aicode-rpt-filter-btn.active{background:#0d6efd;border-color:#0d6efd;color:#fff}
.aicode-rpt-table th{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;white-space:nowrap}
.aicode-rpt-student-link{color:#212529}
.aicode-rpt-student-link:hover{color:#0d6efd}
.aicode-rpt-inline-grade .aicode-rpt-grade-input{width:72px;text-align:center}
.aicode-rpt-section{background:#fff;border:1px solid #dee2e6;border-radius:.5rem;padding:20px 24px;margin-bottom:20px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.aicode-rpt-section h4{font-size:1rem;font-weight:700;margin:0 0 16px;color:#212529;padding-bottom:10px;border-bottom:1px solid #f1f3f5}
.aicode-rpt-code{background:#f8f9fa;border:1px solid #e9ecef;border-radius:.375rem;padding:14px 16px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;font-size:.83rem;line-height:1.55;white-space:pre-wrap;max-height:460px;overflow:auto;margin:0;color:#212529}
.aicode-rpt-code-sm{background:#f8f9fa;border:1px solid #e9ecef;border-radius:.375rem;padding:10px 12px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;font-size:.78rem;line-height:1.5;white-space:pre-wrap;max-height:260px;overflow:auto;margin:8px 0 0;color:#212529}
.aicode-rpt-code-meta{font-size:.8rem;color:#6c757d;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.aicode-rpt-empty{color:#6c757d;font-style:italic;font-size:.875rem;padding:4px 0}
.aicode-rpt-grade-form .form-text{font-size:.75rem}
.aicode-rpt-ai-header{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.aicode-rpt-ai-conf{font-size:.8125rem;color:#6c757d}
.aicode-rpt-ai-short{font-size:1rem;font-weight:600;color:#212529;margin-bottom:8px}
.aicode-rpt-ai-long{font-size:.875rem;color:#495057;line-height:1.65;background:#f8f9fa;border-left:3px solid #dee2e6;padding:10px 14px;border-radius:0 .25rem .25rem 0;margin-bottom:12px}
.aicode-rpt-ai-loc{font-size:.78rem;color:#6c757d;margin-bottom:4px}
.aicode-rpt-ai-section-title{font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6c757d;margin:14px 0 6px}
.aicode-rpt-ai-list{font-size:.875rem;line-height:1.65;margin:0 0 8px;padding-left:20px}
.aicode-rpt-ai-fix{font-size:.875rem;color:#495057;line-height:1.65;background:#fffbeb;border:1px solid #fde68a;border-radius:.375rem;padding:10px 14px;margin-bottom:8px}
.aicode-rpt-ai-explain{font-size:.72rem;color:#adb5bd;margin-top:12px;font-style:italic}
.aicode-rpt-timeline{display:flex;flex-direction:column;gap:8px}
.aicode-rpt-tl-item{border:1px solid #dee2e6;border-radius:.375rem;background:#fff;overflow:hidden;transition:box-shadow .15s}
.aicode-rpt-tl-item:has(.aicode-rpt-tl-toggle[aria-expanded="true"]){box-shadow:0 2px 8px rgba(0,0,0,.07)}
/* Header / toggle */
.aicode-rpt-tl-header{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 14px;cursor:pointer;user-select:none;transition:background .15s}
.aicode-rpt-tl-toggle:hover{background:#f8f9fa}
.aicode-rpt-tl-toggle:focus-visible{outline:2px solid #0d6efd;outline-offset:-2px}
.aicode-rpt-tl-num{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;background:#e9ecef;border-radius:50%;font-size:.72rem;font-weight:700;color:#495057;flex-shrink:0}
.aicode-rpt-tl-time{margin-left:auto;font-size:.76rem;color:#6c757d;white-space:nowrap}
.aicode-rpt-tl-chevron{display:inline-flex;align-items:center;color:#adb5bd;flex-shrink:0;transition:transform .2s;margin-left:4px}
.aicode-rpt-tl-toggle[aria-expanded="true"] .aicode-rpt-tl-chevron{transform:rotate(180deg);color:#0d6efd}
/* Body (collapsed by default, expanded via JS) */
.aicode-rpt-tl-body{padding:0 14px 12px;border-top:1px solid #f1f3f5}
.aicode-rpt-tl-body[hidden]{display:none}
/* Subsection (code block inside body) */
.aicode-rpt-tl-subsection{margin-top:10px}
.aicode-rpt-tl-sublabel{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6c757d;margin-bottom:4px}
/* Output / error / no-output blocks */
.aicode-rpt-tl-output{font-size:.78rem;margin-top:8px;background:#f0fff4;border:1px solid #c3e6cb;border-radius:.25rem;padding:6px 10px;word-break:break-all;display:flex;align-items:flex-start;gap:8px}
.aicode-rpt-tl-error{font-size:.78rem;margin-top:8px;background:#fff5f5;border:1px solid #f5c6cb;border-radius:.25rem;padding:6px 10px;word-break:break-all;display:flex;align-items:flex-start;gap:8px}
.aicode-rpt-tl-noout{font-size:.78rem;margin-top:8px;background:#f8f9fa;border:1px solid #dee2e6;border-radius:.25rem;padding:6px 10px;display:flex;align-items:center;gap:8px}
.aicode-rpt-tl-block-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;padding:1px 6px;border-radius:.2rem;flex-shrink:0}
.aicode-rpt-tl-output .aicode-rpt-tl-block-label{background:#c3e6cb;color:#155724}
.aicode-rpt-tl-error .aicode-rpt-tl-block-label{background:#f5c6cb;color:#721c24}
.aicode-rpt-tl-noout .aicode-rpt-tl-block-label{background:#dee2e6;color:#495057}
.aicode-rpt-preview-wrap{margin-top:12px}
.aicode-rpt-pv-btn{font-size:.8125rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;border-radius:999px;padding:5px 16px}
.aicode-rpt-preview-panel{margin-top:10px;border:1px solid #3e3e3e;border-radius:.5rem;overflow:hidden;background:#1e1e1e}
.aicode-rpt-preview-header{display:flex;align-items:center;justify-content:space-between;padding:6px 14px;background:#252526;border-bottom:1px solid #3e3e3e}
.aicode-rpt-preview-title{font-size:.68rem;font-weight:700;color:#cccccc;letter-spacing:.07em;text-transform:uppercase}
.aicode-rpt-preview-close{background:transparent;border:none;color:#858585;font-size:.8rem;cursor:pointer;padding:2px 6px;border-radius:.25rem;transition:color .15s,background .15s}
.aicode-rpt-preview-close:hover{color:#cccccc;background:#3e3e3e}
.aicode-rpt-preview-iframe{width:100%;min-height:380px;border:none;background:#ffffff;display:block}
.aicode-rpt-outpanel{background:#1e1e1e;border-top:1px solid #3e3e3e;font-family:Consolas,Monaco,monospace;font-size:.8125rem}
.aicode-rpt-outpanel-hdr{display:flex;align-items:center;gap:10px;padding:5px 10px;background:#252526;border-bottom:1px solid #3e3e3e;user-select:none}
.aicode-rpt-outpanel-title{font-size:.68rem;font-weight:700;color:#cccccc;letter-spacing:.07em;text-transform:uppercase}
.aicode-rpt-outpanel-count{font-size:.72rem;color:#858585;font-weight:400;margin-left:auto}
.aicode-rpt-outpanel-list{padding:4px 0;max-height:240px;overflow-y:auto;background:#1e1e1e;font-family:Consolas,Monaco,monospace;font-size:.8125rem}
.aicode-rpt-outline{display:block;padding:3px 14px;color:#d4d4d4;border-bottom:1px solid #2a2a2a;white-space:pre-wrap;word-break:break-all;line-height:1.5}
.aicode-rpt-outline:last-child{border-bottom:none}
.aicode-rpt-outinfo{color:#4ec9b0}
.aicode-rpt-outwarn{color:#dcdcaa}
.aicode-rpt-outerr{color:#f48771}
.aicode-rpt-tl-aifb{margin-top:10px;border:1px solid #b6d4fe;border-left:3px solid #0d6efd;border-radius:.375rem;background:#f0f6ff;padding:12px 14px}
.aicode-rpt-tl-aifb-header{display:flex;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid #cfe2ff}
.aicode-rpt-tl-aifb-label{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#0d6efd}
.aicode-rpt-attempt-count-link{color:inherit;text-decoration:none;border-bottom:2px dashed #adb5bd;padding-bottom:1px;transition:color .15s,border-color .15s;cursor:pointer}
.aicode-rpt-attempt-count-link:hover{color:#0d6efd;border-bottom-color:#0d6efd}
.aicode-rpt-history-count{font-size:.65rem;font-weight:600;vertical-align:middle;letter-spacing:.03em}
#aicode-rpt-history-section.is-highlighted{outline:2px solid #0d6efd;outline-offset:4px;border-radius:.5rem;animation:aicode-rpt-pulse .6s ease-out}
@keyframes aicode-rpt-pulse{0%{outline-color:#0d6efd}100%{outline-color:transparent}}
@media(max-width:768px){
  .aicode-rpt-cards{flex-direction:column}
  .aicode-rpt-card{min-width:unset}
  .aicode-rpt-table{font-size:.8rem}
  .aicode-rpt-section{padding:14px 16px}
  .aicode-rpt-num{font-size:1.4rem}
}
/* ── Learning Analytics Dashboard ── */
.aicode-rpt-analytics{background:#fff;border:1px solid #dee2e6;border-radius:.5rem;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.05);overflow:hidden}
.aicode-rpt-analytics-hdr{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:linear-gradient(135deg,#f8f9fa 0%,#fff 100%);border-bottom:1px solid #f1f3f5}
.aicode-rpt-analytics-title{font-size:1rem;font-weight:700;color:#212529;line-height:1.2}
.aicode-rpt-analytics-sub{font-size:.72rem;color:#6c757d;margin-top:2px}
#aicode-analytics-body{padding:16px 20px 20px}
.aicode-rpt-chart-row{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:14px}
.aicode-rpt-chart-row:last-child{margin-bottom:0}
.aicode-rpt-chart-card{background:#f8f9fa;border:1px solid #e9ecef;border-radius:.5rem;padding:14px 16px;flex:1 1 220px;min-width:0;display:flex;flex-direction:column}
.aicode-rpt-chart-title{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;margin-bottom:12px;flex-shrink:0}
.aicode-rpt-chart-wrap{position:relative;height:200px;flex:1}
.aicode-rpt-chart-sub{font-size:.72rem;color:#6c757d;margin-top:8px;text-align:center;flex-shrink:0}
.aicode-rpt-chart-empty{height:200px;display:flex;align-items:center;justify-content:center;color:#adb5bd;font-size:.82rem;font-style:italic;text-align:center;flex:1}
/* Metrik Kunci — full-width top section */
.aicode-rpt-metrics-top-row{background:#f8f9fa;border:1px solid #e9ecef;border-radius:.5rem;padding:16px;margin-bottom:14px}
.aicode-rpt-metrics-progress{margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #e9ecef}
.aicode-rpt-metric-list{display:flex;flex-wrap:wrap;gap:8px}
.aicode-rpt-metric-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:.375rem;background:#fff;border:1px solid #e9ecef;flex:1 1 150px;min-width:130px;transition:box-shadow .15s}
.aicode-rpt-metric-item:hover{box-shadow:0 2px 6px rgba(0,0,0,.08)}
.aicode-rpt-metric-icon{font-size:1.1rem;flex-shrink:0;width:24px;text-align:center;line-height:1}
.aicode-rpt-metric-content{min-width:0;flex:1}
.aicode-rpt-metric-label{font-size:.62rem;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.aicode-rpt-metric-value{font-size:1.25rem;font-weight:700;color:#212529;line-height:1.15}
.aicode-rpt-metric-sub{font-size:.65rem;color:#adb5bd;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.aicode-rpt-metric-danger{background:#fff5f5;border-color:#f5c6cb}
.aicode-rpt-metric-danger .aicode-rpt-metric-value{color:#dc3545}
.aicode-rpt-metric-warn{background:#fffbeb;border-color:#fde68a}
.aicode-rpt-metric-warn .aicode-rpt-metric-value{color:#856404}
.aicode-rpt-metric-success{background:#f0fdf4;border-color:#a7f3d0}
.aicode-rpt-metric-success .aicode-rpt-metric-value{color:#198754}
.aicode-rpt-metric-inprog{background:#fffbeb;border-color:#fde68a}
.aicode-rpt-metric-inprog .aicode-rpt-metric-value{color:#92400e}
@media(max-width:900px){
  .aicode-rpt-chart-row{flex-direction:column}
  .aicode-rpt-metric-item{flex:1 1 120px}
}
/* ── AI Feedback Correction Panel ── */
.aicode-rpt-correction-section{border-left:4px solid #198754}
.aicode-rpt-correction-title{display:flex;align-items:center;gap:0;flex-wrap:wrap}
.aicode-rpt-correction-badge{display:inline-flex;align-items:center;margin-left:10px;font-size:.7rem;font-weight:600;background:#d1e7dd;color:#0a3622;border-radius:999px;padding:2px 10px;letter-spacing:.02em}
.aicode-rpt-correction-desc{font-size:.875rem;color:#495057;margin-bottom:16px}
.aicode-rpt-correction-aifb-preview{background:#f0f6ff;border:1px solid #b6d4fe;border-radius:.375rem;padding:12px 14px;margin-bottom:16px}
.aicode-rpt-correction-aifb-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#0d6efd;margin-bottom:8px}
.aicode-rpt-correction-aifb-content{font-size:.875rem;color:#212529}
.aicode-rpt-correction-group{border:1px solid #e9ecef;border-radius:.375rem;padding:12px 14px;margin-bottom:12px;background:#fafafa}
.aicode-rpt-correction-group-title{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;margin-bottom:10px}
.aicode-rpt-correction-fewshot{background:#f0fdf4;border:1px solid #a7f3d0;border-radius:.375rem;padding:12px 14px;margin-bottom:0}
/* ── Star rating ── */
.aicode-star-rating{display:inline-flex;flex-direction:row-reverse;gap:2px}
.aicode-star-rating input[type=radio]{position:absolute;opacity:0;width:0;height:0;pointer-events:none}
.aicode-star-rating label{cursor:pointer;font-size:1.6rem;color:#dee2e6;transition:color .1s;line-height:1;padding:0 1px}
.aicode-star-rating input[type=radio]:checked ~ label{color:#ffc107}
.aicode-star-rating label:hover,.aicode-star-rating label:hover ~ label{color:#ffc107}
/* ── Low-rating alert ── */
.aicode-rpt-low-rating-alert{display:flex;align-items:flex-start;gap:10px;background:#fff3cd;border:1px solid #ffc107;border-left:4px solid #fd7e14;border-radius:.375rem;padding:10px 14px;margin-bottom:12px;font-size:.875rem;color:#664d03}
/* ── Detail toggle button ── */
.aicode-rpt-detail-toggle{display:inline-flex;align-items:center;gap:6px;background:none;border:1px dashed #adb5bd;border-radius:.375rem;padding:7px 14px;font-size:.8125rem;font-weight:500;color:#495057;cursor:pointer;width:100%;justify-content:center;margin-bottom:12px;transition:background .15s,border-color .15s,color .15s}
.aicode-rpt-detail-toggle:hover{background:#f8f9fa;border-color:#6c757d;color:#212529}
.aicode-rpt-detail-toggle[aria-expanded="true"]{background:#f0f6ff;border-color:#b6d4fe;color:#0d6efd}
/* ── Correction detail panel animation ── */
#aicode-correction-detail{animation:aicode-slidedown .2s ease-out}
@keyframes aicode-slidedown{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
</style>';

echo '</div>'; // .aicode-rpt-wrap

// ── JS: timeline toggle + smooth scroll + auto-expand ─────────────────────
echo '<script>(function(){';

// Toggle individual timeline items on header click.
echo 'function rptToggle(toggle){';
echo 'var bodyId=toggle.getAttribute("aria-controls");';
echo 'var body=bodyId?document.getElementById(bodyId):null;';
echo 'if(!body)return;';
echo 'var open=toggle.getAttribute("aria-expanded")==="true";';
echo 'if(open){body.hidden=true;toggle.setAttribute("aria-expanded","false");}';
echo 'else{body.hidden=false;toggle.setAttribute("aria-expanded","true");}';
echo '}';
echo 'document.querySelectorAll(".aicode-rpt-tl-toggle").forEach(function(t){';
echo 't.addEventListener("click",function(){rptToggle(t);});';
// Also allow keyboard (Enter / Space).
echo 't.addEventListener("keydown",function(e){if(e.key==="Enter"||e.key===" "){e.preventDefault();rptToggle(t);}});';
echo '});';

// Expand all timeline bodies.
echo 'function expandAllTimeline(){';
echo 'document.querySelectorAll(".aicode-rpt-tl-toggle").forEach(function(t){';
echo 'var bodyId=t.getAttribute("aria-controls");';
echo 'var body=bodyId?document.getElementById(bodyId):null;';
echo 'if(body){body.hidden=false;t.setAttribute("aria-expanded","true");}';
echo '});';
echo '}';

// Attempt count link → smooth scroll + expand all.
echo 'document.querySelectorAll(".aicode-rpt-attempt-count-link").forEach(function(link){';
echo 'link.addEventListener("click",function(e){';
echo 'e.preventDefault();';
echo 'var sec=document.getElementById("aicode-rpt-history-section");';
echo 'if(!sec)return;';
echo 'expandAllTimeline();';
echo 'sec.scrollIntoView({behavior:"smooth",block:"start"});';
echo 'sec.classList.remove("is-highlighted");';
echo 'void sec.offsetWidth;';
echo 'sec.classList.add("is-highlighted");';
echo 'setTimeout(function(){sec.classList.remove("is-highlighted");},1200);';
echo '});});';

// Star rating + detail toggle logic.
echo '(function(){';
echo 'var labels={"1":"Sangat Buruk","2":"Buruk","3":"Cukup","4":"Baik","5":"Sangat Baik"};';
echo 'var desc=document.getElementById("aicode-star-desc");';
echo 'var alert=document.getElementById("aicode-low-rating-alert");';
echo 'var detail=document.getElementById("aicode-correction-detail");';
echo 'var toggle=document.getElementById("aicode-detail-toggle");';

// Helper: open/close the detail section.
echo 'function openDetail(){';
echo '  if(!detail)return;';
echo '  detail.style.display="block";';
echo '  if(toggle){toggle.setAttribute("aria-expanded","true");toggle.innerHTML="&#9650; Sembunyikan detail koreksi";}';
echo '}';
echo 'function closeDetail(){';
echo '  if(!detail)return;';
echo '  detail.style.display="none";';
echo '  if(toggle){toggle.setAttribute("aria-expanded","false");toggle.innerHTML="&#9660; Tambahkan koreksi detail (opsional)";}';
echo '}';

// Toggle button click.
echo 'if(toggle){toggle.addEventListener("click",function(){';
echo '  if(toggle.getAttribute("aria-expanded")==="true"){closeDetail();}else{openDetail();}';
echo '});}';

// Star rating change: update label, show/hide alert, auto-open detail for low ratings.
echo 'document.querySelectorAll(".aicode-star-rating input[type=radio]").forEach(function(r){';
echo '  r.addEventListener("change",function(){';
echo '    if(desc)desc.textContent=labels[r.value]||"";';
echo '    var val=parseInt(r.value,10);';
echo '    if(val<=2){';
echo '      if(alert)alert.style.display="flex";';
echo '      openDetail();'; // auto-expand when rating is low
echo '    }else{';
echo '      if(alert)alert.style.display="none";';
echo '    }';
echo '  });';
echo '});';
echo '})();';

echo '})();</script>';

echo $OUTPUT->footer();
