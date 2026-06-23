<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Course-level analytics dashboard for all AICode activities.
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/gradelib.php');

$courseid = required_param('courseid', PARAM_INT);
$course   = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($courseid);
require_capability('moodle/grade:viewall', $context);

// ── Page setup ─────────────────────────────────────────────────────────────
$PAGE->set_url(new moodle_url('/mod/aicode/course_report.php', ['courseid' => $courseid]));
$PAGE->set_title('AICode — Dashboard Keseluruhan Course');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->navbar->add('AICode Dashboard');

// ── Helper: Indonesian date format ─────────────────────────────────────────
function crpt_format_wib(int $ts): string {
    if ($ts <= 0) return '—';
    static $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    try {
        $dt = new DateTime('@' . $ts);
        $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
        return $dt->format('d') . ' ' . $bulan[(int)$dt->format('n')] . ' ' . $dt->format('Y, H:i') . ' WIB';
    } catch (Throwable $e) { return '—'; }
}

// ── Load data ──────────────────────────────────────────────────────────────

// Get all enrolled students with submit capability (students).
$students = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname, u.email');

// Get aicode module id.
$aicode_modid = $DB->get_field('modules', 'id', ['name' => 'aicode']);

// Get all sections that have aicode activities.
$sections = $DB->get_records_sql(
    "SELECT cs.id, cs.section, cs.name
     FROM {course_sections} cs
     WHERE cs.course = ?
     ORDER BY cs.section",
    [$courseid]
);

// Get all aicode course_modules + activity data, grouped by section.
$all_cms = $DB->get_records_sql(
    "SELECT cm.id as cmid, cm.instance, cm.section, a.name, a.mode, a.course
     FROM {course_modules} cm
     JOIN {aicode} a ON a.id = cm.instance
     WHERE cm.module = ? AND cm.course = ?
     ORDER BY cm.section, cm.id",
    [$aicode_modid, $courseid]
);

// Group by section.
$cms_by_section = [];
foreach ($all_cms as $cm) {
    $cms_by_section[$cm->section][] = $cm;
}

// Load ALL attempts for this course's aicode activities.
$all_instance_ids = array_column(array_values($all_cms), 'instance');
$attempts_by_problem = [];
if (!empty($all_instance_ids)) {
    list($insql, $inparams) = $DB->get_in_or_equal($all_instance_ids, SQL_PARAMS_NAMED);
    $all_attempts = $DB->get_records_select(
        'aicode_attempts',
        "problemid $insql AND userid IS NOT NULL",
        $inparams,
        'timecreated ASC'
    );
    foreach ($all_attempts as $att) {
        $attempts_by_problem[(int)$att->problemid][(int)$att->userid][] = $att;
    }
}

// Load grades for all aicode activities.
$grades_by_activity = [];
foreach ($all_cms as $cm) {
    $gi = grade_get_grades($courseid, 'mod', 'aicode', $cm->instance, array_keys($students));
    $gradeitem = !empty($gi->items) ? reset($gi->items) : null;
    $grades_by_activity[$cm->instance] = $gradeitem ? $gradeitem->grades : [];
}

// ── Compute per-section stats ──────────────────────────────────────────────

$section_stats = [];
$total_activities = 0;
$total_attempts_all = 0;
$total_hints_all = 0;
$total_graded = 0;
$sum_grades = 0.0;
$total_submitted_all = 0;
$total_ai_feedback = 0;

// Error category counts across all.
$global_categories = [];

// Daily activity across all.
$daily_labels = [];
$daily_map = [];
for ($d = 13; $d >= 0; $d--) {
    $ts = mktime(0, 0, 0, (int)date('n'), (int)date('j') - $d);
    $daily_labels[] = date('d/m', $ts);
    $daily_map[date('Y-m-d', $ts)] = 0;
}

// Hint usage per section for chart.
$hints_per_section = [];

foreach ($sections as $sec) {
    $sec_cms = $cms_by_section[$sec->id] ?? [];
    if (empty($sec_cms)) continue;

    $sec_total_activities = count($sec_cms);
    $sec_total_attempts = 0;
    $sec_total_hints = 0;
    $sec_total_submitted = 0;  // students who submitted all activities
    $sec_graded_count = 0;
    $sec_grade_sum = 0.0;
    $sec_ai_feedback = 0;

    // Track per-student submission status across this section.
    $student_submitted_count = []; // userid => count of submitted activities

    foreach ($sec_cms as $cm) {
        $pid = $cm->instance;
        $byuser = $attempts_by_problem[$pid] ?? [];
        $total_activities++;

        foreach ($students as $stu) {
            $ua = $byuser[$stu->id] ?? [];
            $att_count = count($ua);
            $sec_total_attempts += $att_count;
            $total_attempts_all += $att_count;

            // Check submitted status.
            $is_submitted = false;
            foreach ($ua as $a) {
                $r = json_decode($a->result_json ?? '{}', true);
                if (!empty($r['teacher_review_requested'])) {
                    $is_submitted = true;
                    break;
                }
            }
            if ($is_submitted) {
                $student_submitted_count[$stu->id] = ($student_submitted_count[$stu->id] ?? 0) + 1;
            }

            // Hints.
            foreach ($ua as $a) {
                $hints = json_decode($a->used_hints_json ?? '[]', true);
                if (is_array($hints)) {
                    $hcount = count($hints);
                    $sec_total_hints += $hcount;
                    $total_hints_all += $hcount;
                }

                // AI feedback categories.
                if (!empty($a->ai_feedback_json)) {
                    $fb = json_decode($a->ai_feedback_json, true);
                    if (is_array($fb) && ($fb['status'] ?? '') === 'success') {
                        $sec_ai_feedback++;
                        $total_ai_feedback++;
                        $cat = $fb['diagnosis']['category'] ?? 'unknown';
                        $global_categories[$cat] = ($global_categories[$cat] ?? 0) + 1;
                    }
                }

                // Daily activity.
                $dk = date('Y-m-d', (int)$a->timecreated);
                if (isset($daily_map[$dk])) {
                    $daily_map[$dk]++;
                }
            }

            // Grades.
            $grades = $grades_by_activity[$pid] ?? [];
            if (isset($grades[$stu->id]) && is_numeric($grades[$stu->id]->grade)) {
                $sec_graded_count++;
                $sec_grade_sum += (float)$grades[$stu->id]->grade;
                $total_graded++;
                $sum_grades += (float)$grades[$stu->id]->grade;
            }
        }
    }

    // Count students who submitted ALL activities in this section.
    $fully_submitted = 0;
    $partially_submitted = 0;
    $not_started = 0;
    foreach ($students as $stu) {
        $sc = $student_submitted_count[$stu->id] ?? 0;
        if ($sc >= $sec_total_activities) {
            $fully_submitted++;
        } else if ($sc > 0) {
            $partially_submitted++;
        } else {
            $not_started++;
        }
    }
    $total_submitted_all += $fully_submitted;

    $sec_avg = $sec_graded_count > 0 ? round($sec_grade_sum / $sec_graded_count, 1) : 0;

    $hints_per_section[$sec->name ?: 'Section ' . $sec->section] = $sec_total_hints;

    $section_stats[$sec->id] = [
        'section' => $sec,
        'activities' => $sec_total_activities,
        'attempts' => $sec_total_attempts,
        'hints' => $sec_total_hints,
        'fully_submitted' => $fully_submitted,
        'partially' => $partially_submitted,
        'not_started' => $not_started,
        'graded' => $sec_graded_count,
        'avg_grade' => $sec_avg,
        'ai_feedback' => $sec_ai_feedback,
    ];
}

$total_students = count($students);
$avg_grade_all = $total_graded > 0 ? round($sum_grades / $total_graded, 1) : 0;
$avg_attempts = $total_students > 0 ? round($total_attempts_all / $total_students, 1) : 0;

arsort($global_categories);
$daily_values = array_values($daily_map);
$total_recent = array_sum($daily_values);

// Grade distribution bins.
$grade_bins = ['0-20' => 0, '21-40' => 0, '41-60' => 0, '61-80' => 0, '81-100' => 0];
foreach ($grades_by_activity as $pid => $grades) {
    foreach ($grades as $uid => $g) {
        if (!is_numeric($g->grade ?? null)) continue;
        $gf = (float)$g->grade;
        if ($gf <= 20) $grade_bins['0-20']++;
        elseif ($gf <= 40) $grade_bins['21-40']++;
        elseif ($gf <= 60) $grade_bins['41-60']++;
        elseif ($gf <= 80) $grade_bins['61-80']++;
        else $grade_bins['81-100']++;
    }
}

// Progress per section for stacked bar.
$progress_labels = [];
$progress_submit = [];
$progress_partial = [];
$progress_notstarted = [];
foreach ($section_stats as $sid => $ss) {
    $sname = $ss['section']->name ?: 'Section ' . $ss['section']->section;
    // Short label: "Mg 1", "Mg 2", etc.
    $progress_labels[] = 'Mg ' . $ss['section']->section;
    $progress_submit[] = $ss['fully_submitted'];
    $progress_partial[] = $ss['partially'];
    $progress_notstarted[] = $ss['not_started'];
}

// Hints per section for chart.
$hint_sec_labels = [];
$hint_sec_data = [];
foreach ($section_stats as $sid => $ss) {
    $hint_sec_labels[] = 'Mg ' . $ss['section']->section;
    $hint_sec_data[] = $ss['hints'];
}

// ── Output ────────────────────────────────────────────────────────────────

echo $OUTPUT->header();
echo '<div class="aicode-crpt-wrap">';

// ── Header ────────────────────────────────────────────────────────────────
echo '<div class="aicode-crpt-page-head">';
echo html_writer::link(
    new moodle_url('/course/view.php', ['id' => $courseid]),
    '&larr; Kembali ke Course',
    ['class' => 'aicode-crpt-back']
);
echo '<h2>';
echo '<span style="font-size:1.5rem;margin-right:8px">📊</span>';
echo 'AICode — Dashboard Keseluruhan Course';
echo '</h2>';
echo '<div class="aicode-crpt-meta">' . format_string($course->fullname) . '</div>';
echo '</div>';

// ── Metrics row ───────────────────────────────────────────────────────────
echo '<div class="aicode-crpt-metrics">';

$metrics = [
    ['icon' => '👥', 'label' => 'Total Siswa',          'value' => $total_students,     'sub' => 'enrolled di course'],
    ['icon' => '📦', 'label' => 'Total Aktivitas',       'value' => $total_activities,   'sub' => 'aicode di seluruh course'],
    ['icon' => '📝', 'label' => 'Total Percobaan',       'value' => $total_attempts_all, 'sub' => 'keseluruhan'],
    ['icon' => '🎯', 'label' => 'Rata-rata Percobaan',   'value' => $avg_attempts,       'sub' => 'per siswa'],
    ['icon' => '💡', 'label' => 'Total AI Hint',         'value' => $total_hints_all,    'sub' => 'penggunaan hint'],
    ['icon' => '📈', 'label' => 'Rata-rata Nilai',       'value' => $avg_grade_all,      'sub' => $total_graded . ' nilai terhitung'],
    ['icon' => '🤖', 'label' => 'AI Feedback',           'value' => $total_ai_feedback,  'sub' => 'total feedback AI'],
    ['icon' => '✅', 'label' => 'Minggu Selesai',        'value' => count(array_filter($section_stats, fn($s) => $s['fully_submitted'] >= $total_students && $total_students > 0)), 'sub' => 'dari ' . count($section_stats) . ' minggu'],
];

foreach ($metrics as $m) {
    echo '<div class="aicode-crpt-metric-card">';
    echo '<span class="aicode-crpt-metric-icon">' . $m['icon'] . '</span>';
    echo '<div class="aicode-crpt-metric-body">';
    echo '<div class="aicode-crpt-metric-label">' . $m['label'] . '</div>';
    echo '<div class="aicode-crpt-metric-value">' . $m['value'] . '</div>';
    echo '<div class="aicode-crpt-metric-sub">' . $m['sub'] . '</div>';
    echo '</div></div>';
}
echo '</div>';

// ── Charts ────────────────────────────────────────────────────────────────
echo '<div class="aicode-crpt-charts">';

// Row 1.
echo '<div class="aicode-crpt-chart-row">';

// Chart 1 — Progress per Minggu (stacked bar).
echo '<div class="aicode-crpt-chart-card aicode-crpt-chart-wide">';
echo '<div class="aicode-crpt-chart-title">Progress Siswa per Minggu</div>';
echo '<div class="aicode-crpt-chart-wrap"><canvas id="crpt-chart-progress"></canvas></div>';
echo '</div>';

// Chart 2 — Distribusi Nilai.
echo '<div class="aicode-crpt-chart-card">';
echo '<div class="aicode-crpt-chart-title">Distribusi Nilai Keseluruhan</div>';
if ($total_graded > 0) {
    echo '<div class="aicode-crpt-chart-wrap"><canvas id="crpt-chart-grades"></canvas></div>';
    echo '<div class="aicode-crpt-chart-sub">Rata-rata: <strong>' . $avg_grade_all . '</strong></div>';
} else {
    echo '<div class="aicode-crpt-chart-empty">Belum ada nilai.</div>';
}
echo '</div>';

echo '</div>'; // row 1

// Row 2.
echo '<div class="aicode-crpt-chart-row">';

// Chart 3 — Aktivitas 14 hari.
echo '<div class="aicode-crpt-chart-card">';
echo '<div class="aicode-crpt-chart-title">Aktivitas 14 Hari Terakhir</div>';
if ($total_recent > 0) {
    echo '<div class="aicode-crpt-chart-wrap"><canvas id="crpt-chart-daily"></canvas></div>';
    echo '<div class="aicode-crpt-chart-sub">Total: <strong>' . $total_recent . '</strong> percobaan</div>';
} else {
    echo '<div class="aicode-crpt-chart-empty">Belum ada aktivitas.</div>';
}
echo '</div>';

// Chart 4 — AI Hint per Minggu.
echo '<div class="aicode-crpt-chart-card">';
echo '<div class="aicode-crpt-chart-title">Penggunaan AI Hint per Minggu</div>';
if ($total_hints_all > 0) {
    echo '<div class="aicode-crpt-chart-wrap"><canvas id="crpt-chart-hints"></canvas></div>';
} else {
    echo '<div class="aicode-crpt-chart-empty">Belum ada penggunaan AI Hint.</div>';
}
echo '</div>';

// Chart 5 — Kategori Error.
echo '<div class="aicode-crpt-chart-card">';
echo '<div class="aicode-crpt-chart-title">Kategori Error AI</div>';
if (!empty($global_categories)) {
    echo '<div class="aicode-crpt-chart-wrap"><canvas id="crpt-chart-errors"></canvas></div>';
} else {
    echo '<div class="aicode-crpt-chart-empty">Belum ada data AI feedback.</div>';
}
echo '</div>';

echo '</div>'; // row 2
echo '</div>'; // charts

// ── Section Table ─────────────────────────────────────────────────────────
echo '<div class="aicode-crpt-section-table-wrap">';
echo '<h3 class="aicode-crpt-table-title">📋 Ringkasan per Minggu</h3>';
echo '<div class="table-responsive">';
echo '<table class="table table-hover align-middle aicode-crpt-table">';
echo '<thead class="table-light"><tr>';
echo '<th style="width:36px">#</th>';
echo '<th>Minggu / Section</th>';
echo '<th class="text-center">Aktivitas</th>';
echo '<th class="text-center">Sudah Submit</th>';
echo '<th class="text-center">Sedang</th>';
echo '<th class="text-center">Belum</th>';
echo '<th class="text-center">Rata-rata Nilai</th>';
echo '<th class="text-center">Percobaan</th>';
echo '<th class="text-center">AI Hint</th>';
echo '<th></th>';
echo '</tr></thead>';
echo '<tbody>';

$rownum = 0;
foreach ($section_stats as $sid => $ss) {
    $rownum++;
    $sec = $ss['section'];
    $sname = $sec->name ?: 'Section ' . $sec->section;
    $detail_url = new moodle_url('/mod/aicode/section_report.php', ['courseid' => $courseid, 'sectionid' => $sec->id]);

    $pct = $total_students > 0 ? round($ss['fully_submitted'] / $total_students * 100) : 0;

    // Color code the row based on completion.
    $rowclass = '';
    if ($ss['fully_submitted'] >= $total_students && $total_students > 0) {
        $rowclass = ' class="table-success"';
    }

    echo '<tr' . $rowclass . '>';
    echo '<td class="text-muted small fw-semibold">' . $sec->section . '</td>';
    echo '<td>';
    echo '<a href="' . s($detail_url->out(false)) . '" class="fw-semibold text-decoration-none aicode-crpt-section-link">';
    echo s($sname);
    echo '</a>';
    echo '</td>';
    echo '<td class="text-center">' . $ss['activities'] . '</td>';
    echo '<td class="text-center">';
    echo '<span class="badge bg-success">' . $ss['fully_submitted'] . '</span>';
    echo ' <span class="text-muted small">(' . $pct . '%)</span>';
    echo '</td>';
    echo '<td class="text-center">';
    if ($ss['partially'] > 0) echo '<span class="badge bg-warning text-dark">' . $ss['partially'] . '</span>';
    else echo '<span class="text-muted">0</span>';
    echo '</td>';
    echo '<td class="text-center">';
    if ($ss['not_started'] > 0) echo '<span class="badge bg-secondary">' . $ss['not_started'] . '</span>';
    else echo '<span class="text-muted">0</span>';
    echo '</td>';
    echo '<td class="text-center fw-semibold">' . ($ss['avg_grade'] > 0 ? $ss['avg_grade'] : '—') . '</td>';
    echo '<td class="text-center">' . $ss['attempts'] . '</td>';
    echo '<td class="text-center">' . ($ss['hints'] > 0 ? $ss['hints'] : '—') . '</td>';
    echo '<td>';
    echo html_writer::link($detail_url, 'Detail &rarr;', ['class' => 'btn btn-sm btn-outline-primary']);
    echo '</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div>';
echo '</div>';

// ── Charts JS ─────────────────────────────────────────────────────────────
$chartdata = json_encode([
    'progress' => [
        'labels' => $progress_labels,
        'submit' => $progress_submit,
        'partial' => $progress_partial,
        'notstarted' => $progress_notstarted,
    ],
    'grades' => ['labels' => array_keys($grade_bins), 'data' => array_values($grade_bins)],
    'daily'  => ['labels' => $daily_labels, 'data' => $daily_values],
    'hints'  => ['labels' => $hint_sec_labels, 'data' => $hint_sec_data],
    'errors' => ['labels' => array_keys($global_categories), 'data' => array_values($global_categories)],
], JSON_HEX_TAG | JSON_HEX_AMP);

$chartjsurl = $CFG->wwwroot . '/mod/aicode/javascript/chart.min.js';
echo '<script src="' . s($chartjsurl) . '"></script>';
echo '<script>';
echo 'window._crptData = ' . $chartdata . ';';
echo <<<'CHARTJS'
(function(){
    "use strict";
    if(!window.Chart)return;
    var d=window._crptData;
    Chart.defaults.font.family="system-ui,-apple-system,sans-serif";
    Chart.defaults.font.size=11;
    Chart.defaults.color="#6c757d";
    var grey="#f1f3f5";

    // 1 — Progress per Minggu (stacked bar).
    var c1=document.getElementById("crpt-chart-progress");
    if(c1){
        new Chart(c1,{
            type:"bar",
            data:{
                labels:d.progress.labels,
                datasets:[
                    {label:"Sudah Submit",data:d.progress.submit,backgroundColor:"#198754",borderRadius:3,borderWidth:0},
                    {label:"Sedang",data:d.progress.partial,backgroundColor:"#ffc107",borderRadius:3,borderWidth:0},
                    {label:"Belum Mulai",data:d.progress.notstarted,backgroundColor:"#adb5bd",borderRadius:3,borderWidth:0}
                ]
            },
            options:{
                responsive:true,maintainAspectRatio:false,
                plugins:{legend:{position:"bottom",labels:{boxWidth:11,padding:10}}},
                scales:{
                    x:{stacked:true,grid:{display:false}},
                    y:{stacked:true,beginAtZero:true,ticks:{stepSize:1,precision:0},grid:{color:grey}}
                }
            }
        });
    }

    // 2 — Grade distribution.
    var c2=document.getElementById("crpt-chart-grades");
    if(c2){
        new Chart(c2,{
            type:"bar",
            data:{
                labels:d.grades.labels,
                datasets:[{label:"Siswa",data:d.grades.data,backgroundColor:["#dc3545","#fd7e14","#ffc107","#20c997","#198754"],borderRadius:4,borderWidth:0}]
            },
            options:{
                responsive:true,maintainAspectRatio:false,
                plugins:{legend:{display:false}},
                scales:{y:{beginAtZero:true,ticks:{stepSize:1,precision:0},grid:{color:grey}},x:{grid:{display:false}}}
            }
        });
    }

    // 3 — Daily activity.
    var c3=document.getElementById("crpt-chart-daily");
    if(c3){
        new Chart(c3,{
            type:"line",
            data:{
                labels:d.daily.labels,
                datasets:[{label:"Percobaan",data:d.daily.data,borderColor:"#0d6efd",backgroundColor:"rgba(13,110,253,0.07)",borderWidth:2,tension:0.35,fill:true,pointRadius:3,pointHoverRadius:5,pointBackgroundColor:"#0d6efd"}]
            },
            options:{
                responsive:true,maintainAspectRatio:false,
                plugins:{legend:{display:false}},
                scales:{y:{beginAtZero:true,ticks:{stepSize:1,precision:0},grid:{color:grey}},x:{grid:{display:false},ticks:{maxRotation:45,font:{size:10}}}}
            }
        });
    }

    // 4 — Hints per minggu.
    var c4=document.getElementById("crpt-chart-hints");
    if(c4){
        new Chart(c4,{
            type:"bar",
            data:{
                labels:d.hints.labels,
                datasets:[{label:"AI Hint",data:d.hints.data,backgroundColor:"#6f42c1",borderRadius:4,borderWidth:0}]
            },
            options:{
                responsive:true,maintainAspectRatio:false,
                plugins:{legend:{display:false}},
                scales:{y:{beginAtZero:true,ticks:{stepSize:1,precision:0},grid:{color:grey}},x:{grid:{display:false}}}
            }
        });
    }

    // 5 — Error categories.
    var c5=document.getElementById("crpt-chart-errors");
    if(c5&&d.errors.labels.length){
        new Chart(c5,{
            type:"bar",
            data:{
                labels:d.errors.labels,
                datasets:[{label:"Frekuensi",data:d.errors.data,backgroundColor:["#0d6efd","#198754","#dc3545","#ffc107","#6f42c1","#20c997","#fd7e14"],borderRadius:4,borderWidth:0}]
            },
            options:{
                indexAxis:"y",responsive:true,maintainAspectRatio:false,
                plugins:{legend:{display:false}},
                scales:{x:{beginAtZero:true,ticks:{stepSize:1,precision:0},grid:{color:grey}},y:{grid:{display:false}}}
            }
        });
    }
}());
CHARTJS;
echo '</script>';

// ── CSS ───────────────────────────────────────────────────────────────────
echo '<style>
#region-main,#region-main-box,.course-content{max-width:none!important}
#page-content{padding-left:0;padding-right:0}
.aicode-crpt-wrap{max-width:100%;margin:0;padding:0 0 48px}
.aicode-crpt-page-head{margin-bottom:20px}
.aicode-crpt-back{font-size:.8125rem;color:#6c757d;text-decoration:none;display:inline-block;margin-bottom:10px}
.aicode-crpt-back:hover{color:#0d6efd}
.aicode-crpt-page-head h2{margin:0 0 4px;font-size:1.5rem;font-weight:700;display:flex;align-items:center;gap:4px}
.aicode-crpt-meta{font-size:.875rem;color:#6c757d}
/* Metrics grid */
.aicode-crpt-metrics{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-bottom:24px}
.aicode-crpt-metric-card{display:flex;align-items:center;gap:12px;padding:14px 16px;background:#fff;border:1px solid #e9ecef;border-radius:.5rem;box-shadow:0 1px 3px rgba(0,0,0,.04);transition:box-shadow .15s,transform .15s}
.aicode-crpt-metric-card:hover{box-shadow:0 3px 10px rgba(0,0,0,.08);transform:translateY(-1px)}
.aicode-crpt-metric-icon{font-size:1.3rem;flex-shrink:0;width:28px;text-align:center}
.aicode-crpt-metric-body{min-width:0;flex:1}
.aicode-crpt-metric-label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.aicode-crpt-metric-value{font-size:1.35rem;font-weight:700;color:#212529;line-height:1.15}
.aicode-crpt-metric-sub{font-size:.65rem;color:#adb5bd;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
/* Charts */
.aicode-crpt-charts{margin-bottom:24px}
.aicode-crpt-chart-row{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:14px}
.aicode-crpt-chart-row:last-child{margin-bottom:0}
.aicode-crpt-chart-card{background:#fff;border:1px solid #e9ecef;border-radius:.5rem;padding:16px;flex:1 1 260px;min-width:0;display:flex;flex-direction:column;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.aicode-crpt-chart-wide{flex:2 1 400px}
.aicode-crpt-chart-title{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;margin-bottom:12px}
.aicode-crpt-chart-wrap{position:relative;height:220px;flex:1}
.aicode-crpt-chart-sub{font-size:.72rem;color:#6c757d;margin-top:8px;text-align:center}
.aicode-crpt-chart-empty{height:200px;display:flex;align-items:center;justify-content:center;color:#adb5bd;font-size:.82rem;font-style:italic}
/* Section table */
.aicode-crpt-section-table-wrap{background:#fff;border:1px solid #e9ecef;border-radius:.5rem;padding:20px 24px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.aicode-crpt-table-title{font-size:1.1rem;font-weight:700;margin:0 0 16px;color:#212529}
.aicode-crpt-table th{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;white-space:nowrap}
.aicode-crpt-section-link{color:#212529;transition:color .15s}
.aicode-crpt-section-link:hover{color:#0d6efd}
@media(max-width:768px){
    .aicode-crpt-metrics{grid-template-columns:repeat(2,1fr)}
    .aicode-crpt-chart-row{flex-direction:column}
}
</style>';

echo '</div>';
echo $OUTPUT->footer();
