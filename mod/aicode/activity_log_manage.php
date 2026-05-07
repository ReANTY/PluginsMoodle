<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Site admin: export or purge AICode activity log (metadata only).
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

/**
 * Paket bahasa untuk halaman & ekspor log aktivitas: Indonesia (lang/id/aicode.php).
 *
 * @return string
 */
function mod_aicode_activity_log_locale(): string {
    return 'id';
}

/**
 * @param mixed|null $param
 */
function mod_aicode_activity_log_str(string $identifier, $param = null): string {
    return get_string($identifier, 'aicode', $param, mod_aicode_activity_log_locale());
}

function mod_aicode_activity_log_csv_action_label(string $code): string {
    $code = strtolower(trim((string) $code));
    if ($code === '') {
        return '';
    }
    $key = 'activitylog_action_' . $code;
    $lang = mod_aicode_activity_log_locale();
    $strings = get_string_manager()->load_component_strings('aicode', $lang);
    return $strings[$key] ?? $code;
}

function mod_aicode_activity_log_csv_mode_label(?string $mode): string {
    if ($mode !== null && trim((string) $mode) === 'exam') {
        return mod_aicode_activity_log_str('activitylog_mode_exam_label');
    }
    return mod_aicode_activity_log_str('activitylog_mode_training_label');
}

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url(new moodle_url('/mod/aicode/activity_log_manage.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(mod_aicode_activity_log_str('activitylog_pagetitle'));
$PAGE->set_heading(mod_aicode_activity_log_str('activitylog_pagetitle'));

/**
 * Parse YYYY-MM-DD into start-of-day unix time.
 *
 * @param string $str
 * @return int|null
 */
function mod_aicode_parse_date_start(string $str): ?int {
    $str = trim($str);
    if ($str === '') {
        return null;
    }
    $t = strtotime($str . ' 00:00:00');
    return ($t === false) ? null : $t;
}

/**
 * Parse YYYY-MM-DD into end-of-day unix time.
 *
 * @param string $str
 * @return int|null
 */
function mod_aicode_parse_date_end(string $str): ?int {
    $str = trim($str);
    if ($str === '') {
        return null;
    }
    $t = strtotime($str . ' 23:59:59');
    return ($t === false) ? null : $t;
}

/**
 * Build WHERE for {aicode_activity_log} alias lg + params.
 *
 * @param int $courseid 0 = all
 * @param string $datefrom YYYY-MM-DD
 * @param string $dateto YYYY-MM-DD
 * @return array{0:string,1:array<string,mixed>}
 */
function mod_aicode_activity_log_filters(int $courseid, string $datefrom, string $dateto): array {
    $conds = ['1=1'];
    $params = [];
    if ($courseid > 0) {
        $conds[] = 'lg.id_kursus = :courseid';
        $params['courseid'] = $courseid;
    }
    $tsstart = mod_aicode_parse_date_start($datefrom);
    $tsend = mod_aicode_parse_date_end($dateto);
    if ($tsstart !== null) {
        $conds[] = 'lg.waktu_dicatat >= :tsstart';
        $params['tsstart'] = $tsstart;
    }
    if ($tsend !== null) {
        $conds[] = 'lg.waktu_dicatat <= :tsend';
        $params['tsend'] = $tsend;
    }
    return [implode(' AND ', $conds), $params];
}

$courseid = optional_param('courseid', 0, PARAM_INT);
$datefrom = optional_param('datefrom', '', PARAM_RAW_TRIMMED);
$dateto = optional_param('dateto', '', PARAM_RAW_TRIMMED);
$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'exportcsv') {
    require_sesskey();

    [$finalwhere, $sqlparams] = mod_aicode_activity_log_filters($courseid, $datefrom, $dateto);

    $sql = "SELECT lg.*, c.shortname AS courseshortname, c.fullname AS coursefullname,
                   u.username, u.email,
                   ap.name AS activityname
              FROM {aicode_activity_log} lg
             JOIN {user} u ON u.id = lg.id_pengguna
             JOIN {course} c ON c.id = lg.id_kursus
             JOIN {aicode} ap ON ap.id = lg.id_aktivitas_aicode
             WHERE $finalwhere
          ORDER BY lg.waktu_dicatat ASC, lg.id ASC";

    $filename = 'log_aktivitas_aicode_' . userdate(time(), '%Y%m%d_%H%M', 99, false) . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, [
        mod_aicode_activity_log_str('activitylog_csv_col_id'),
        mod_aicode_activity_log_str('activitylog_csv_col_time_iso'),
        mod_aicode_activity_log_str('activitylog_csv_col_userid'),
        mod_aicode_activity_log_str('activitylog_csv_col_username'),
        mod_aicode_activity_log_str('activitylog_csv_col_email'),
        mod_aicode_activity_log_str('activitylog_csv_col_userfullname'),
        mod_aicode_activity_log_str('activitylog_csv_col_activitymode'),
        mod_aicode_activity_log_str('activitylog_csv_col_aihint'),
        mod_aicode_activity_log_str('activitylog_csv_col_runs'),
        mod_aicode_activity_log_str('activitylog_csv_col_teacher_submit'),
        mod_aicode_activity_log_str('activitylog_csv_col_grade'),
        mod_aicode_activity_log_str('activitylog_csv_col_courseid'),
        mod_aicode_activity_log_str('activitylog_csv_col_courseshort'),
        mod_aicode_activity_log_str('activitylog_csv_col_coursefull'),
        mod_aicode_activity_log_str('activitylog_csv_col_cmid'),
        mod_aicode_activity_log_str('activitylog_csv_col_problemid'),
        mod_aicode_activity_log_str('activitylog_csv_col_activityname'),
        mod_aicode_activity_log_str('activitylog_csv_col_action_code'),
        mod_aicode_activity_log_str('activitylog_csv_col_action_label'),
        mod_aicode_activity_log_str('activitylog_csv_col_meta_json'),
    ]);

    $rs = $DB->get_recordset_sql($sql, $sqlparams);
    foreach ($rs as $row) {
        $gr = '';
        if (isset($row->nilai_snapshot) && is_numeric($row->nilai_snapshot)) {
            $gr = round((float) $row->nilai_snapshot, 2);
        }
        fputcsv($out, [
            $row->id,
            userdate((int) $row->waktu_dicatat, '%Y-%m-%d %H:%M:%S', 99, false),
            $row->id_pengguna,
            $row->username,
            $row->email,
            $row->nama_lengkap ?? '',
            mod_aicode_activity_log_csv_mode_label($row->mode_aktivitas ?? null),
            isset($row->jumlah_ai_hint) ? (int) $row->jumlah_ai_hint : '',
            isset($row->jumlah_run) ? (int) $row->jumlah_run : '',
            isset($row->jumlah_kirim_guru) ? (int) $row->jumlah_kirim_guru : '',
            $gr,
            $row->id_kursus,
            $row->courseshortname,
            $row->coursefullname,
            $row->id_modul,
            $row->id_aktivitas_aicode,
            $row->activityname,
            $row->kode_kejadian,
            mod_aicode_activity_log_csv_action_label((string) ($row->kode_kejadian ?? '')),
            $row->metadata_json ?? '',
        ]);
    }
    $rs->close();
    fclose($out);
    exit;
}

if ($action === 'purge' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $confirm = optional_param('confirm', 0, PARAM_INT);
    if (!$confirm) {
        \core\notification::warning(mod_aicode_activity_log_str('activitylog_purge_needconfirm'));
        redirect(new moodle_url('/mod/aicode/activity_log_manage.php', [
            'courseid' => $courseid,
            'datefrom' => $datefrom,
            'dateto' => $dateto,
        ]));
    }

    [$finalwhere, $sqlparams] = mod_aicode_activity_log_filters($courseid, $datefrom, $dateto);
    $DB->delete_records_select('aicode_activity_log', $finalwhere, $sqlparams);

    \core\notification::success(mod_aicode_activity_log_str('activitylog_purged'));
    redirect(new moodle_url('/mod/aicode/activity_log_manage.php', [
        'courseid' => $courseid,
        'datefrom' => $datefrom,
        'dateto' => $dateto,
    ]));
}

$courses = $DB->get_records_sql_menu(
    "SELECT c.id, c.shortname
       FROM {course} c
      WHERE c.id <> :siteid
   ORDER BY c.sortorder ASC, c.shortname ASC",
    ['siteid' => SITEID]
);

echo $OUTPUT->header();

echo $OUTPUT->heading(mod_aicode_activity_log_str('activitylog_exportheading'));
echo html_writer::tag('p', mod_aicode_activity_log_str('activitylog_exportdesc'));

echo html_writer::tag('h3', mod_aicode_activity_log_str('activitylog_filterheading'), ['class' => 'mt-3']);

echo '<form method="get" action="' . $PAGE->url->out(false) . '" class="mb-4">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
echo '<label for="courseid">' . mod_aicode_activity_log_str('activitylog_course') . '</label><br>';
echo html_writer::select(['0' => mod_aicode_activity_log_str('activitylog_allcourses')] + $courses, 'courseid', $courseid, false) . '<br><br>';
echo '<label for="datefrom">' . mod_aicode_activity_log_str('activitylog_datefrom') . '</label><br>';
echo '<input id="datefrom" type="text" name="datefrom" value="' . s($datefrom) . '" placeholder="YYYY-MM-DD" class="form-control mb-2" style="max-width:280px">';
echo '<label for="dateto">' . mod_aicode_activity_log_str('activitylog_dateto') . '</label><br>';
echo '<input id="dateto" type="text" name="dateto" value="' . s($dateto) . '" placeholder="YYYY-MM-DD" class="form-control mb-2" style="max-width:280px">';
echo '<br><button type="submit" class="btn btn-secondary">' . mod_aicode_activity_log_str('activitylog_applyfilters') . '</button>';
echo '</form>';

$newexport = new moodle_url($PAGE->url, [
    'action' => 'exportcsv',
    'sesskey' => sesskey(),
    'courseid' => $courseid,
    'datefrom' => $datefrom,
    'dateto' => $dateto,
]);
echo html_writer::tag(
    'p',
    mod_aicode_activity_log_str('activitylog_exportfiltered') . ' ' .
        html_writer::link($newexport, mod_aicode_activity_log_str('activitylog_downloadcsv')),
    ['class' => 'mb-4']
);

echo html_writer::tag('hr', '');

echo $OUTPUT->heading(mod_aicode_activity_log_str('activitylog_purgeheading'), 3);
echo html_writer::tag('p', mod_aicode_activity_log_str('activitylog_purgedesc'));

$purgeurl = $PAGE->url->out(false);
echo '<form method="post" action="' . $purgeurl . '" class="mt-3">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
echo '<input type="hidden" name="action" value="purge">';
echo '<input type="hidden" name="courseid" value="' . $courseid . '">';
echo '<input type="hidden" name="datefrom" value="' . s($datefrom) . '">';
echo '<input type="hidden" name="dateto" value="' . s($dateto) . '">';
echo '<div class="form-check mb-2">';
echo '<label class="form-check-label">';
echo '<input type="checkbox" name="confirm" value="1" class="form-check-input" required> ';
echo mod_aicode_activity_log_str('activitylog_purge_checkbox');
echo '</label></div>';
echo '<button type="submit" class="btn btn-danger">' . mod_aicode_activity_log_str('activitylog_purge_submit') . '</button>';
echo '</form>';

echo $OUTPUT->footer();
