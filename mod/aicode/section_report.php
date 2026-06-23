<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Section-level analytics dashboard for AICode activities within a topic/week.
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/gradelib.php');

$courseid  = required_param('courseid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$section = $DB->get_record('course_sections', ['id' => $sectionid, 'course' => $courseid], '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($courseid);
require_capability('moodle/grade:viewall', $context);

$sectionname = $section->name ?: 'Minggu ' . $section->section;

// ── Page setup ─────────────────────────────────────────────────────────────
$PAGE->set_url(new moodle_url('/mod/aicode/section_report.php', ['courseid' => $courseid, 'sectionid' => $sectionid]));
$PAGE->set_title('AICode — ' . $sectionname);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->navbar->add('AICode Dashboard', new moodle_url('/mod/aicode/course_report.php', ['courseid' => $courseid]));
$PAGE->navbar->add($sectionname);

// ── Helper ─────────────────────────────────────────────────────────────────
function srpt_format_wib(int $ts): string {
    if ($ts <= 0) return '—';
    static $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    try {
        $dt = new DateTime('@' . $ts);
        $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
        return $dt->format('d') . ' ' . $bulan[(int)$dt->format('n')] . ' ' . $dt->format('Y, H:i') . ' WIB';
    } catch (Throwable $e) { return '—'; }
}

// ── Load data ──────────────────────────────────────────────────────────────

$students = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname, u.email');
$total_students = count($students);

$aicode_modid = $DB->get_field('modules', 'id', ['name' => 'aicode']);

// AICode activities in this section.
$sec_cms = $DB->get_records_sql(
    "SELECT cm.id as cmid, cm.instance, a.name, a.mode, a.course
     FROM {course_modules} cm
     JOIN {aicode} a ON a.id = cm.instance
     WHERE cm.module = ? AND cm.section = ?
     ORDER BY cm.id",
    [$aicode_modid, $sectionid]
);

if (empty($sec_cms)) {
    echo $OUTPUT->header();
    echo '<div class="alert alert-warning">Tidak ada aktivitas AICode di section ini.</div>';
    echo $OUTPUT->footer();
    exit;
}

// Load attempts grouped by problemid and userid.
$instance_ids = array_column(array_values($sec_cms), 'instance');
list($insql, $inparams) = $DB->get_in_or_equal($instance_ids, SQL_PARAMS_NAMED);
$all_attempts = $DB->get_records_select(
    'aicode_attempts',
    "problemid $insql AND userid IS NOT NULL",
    $inparams,
    'timecreated ASC'
);
$attempts_by_problem = [];
foreach ($all_attempts as $att) {
    $attempts_by_problem[(int)$att->problemid][(int)$att->userid][] = $att;
}

// Load grades.
$grades_by_activity = [];
foreach ($sec_cms as $cm) {
    $gi = grade_get_grades($courseid, 'mod', 'aicode', $cm->instance, array_keys($students));
    $gradeitem = !empty($gi->items) ? reset($gi->items) : null;
    $grades_by_activity[$cm->instance] = $gradeitem ? $gradeitem->grades : [];
}

// ── Compute per-activity stats ─────────────────────────────────────────────

$activity_stats = [];
$sec_total_attempts = 0;
$sec_total_hints = 0;
$sec_graded = 0;
$sec_grade_sum = 0.0;
$sec_total_submitted = 0;
$sec_categories = [];

// Per-student × per-activity data for heatmap.
$heatmap = []; // [userid][instance] = ['grade' => ..., 'status' => ..., 'attempts' => ..., 'hints' => ...]

// Progress per activity for chart.
$act_labels = [];
$act_submit = [];
$act_partial = [];
$act_notstarted = [];

// Daily activity.
$daily_labels = [];
$daily_map = [];
for ($d = 13; $d >= 0; $d--) {
    $ts = mktime(0, 0, 0, (int)date('n'), (int)date('j') - $d);
    $daily_labels[] = date('d/m', $ts);
    $daily_map[date('Y-m-d', $ts)] = 0;
}

foreach ($sec_cms as $cm) {
    $pid = $cm->instance;
    $byuser = $attempts_by_problem[$pid] ?? [];
    $grades = $grades_by_activity[$pid] ?? [];

    $a_attempts = 0;
    $a_hints = 0;
    $a_submitted = 0;
    $a_inprogress = 0;
    $a_notstarted = 0;
    $a_graded = 0;
    $a_grade_sum = 0.0;

    foreach ($students as $stu) {
        $ua = $byuser[$stu->id] ?? [];
        $att_count = count($ua);
        $a_attempts += $att_count;
        $sec_total_attempts += $att_count;

        // Status.
        $is_submitted = false;
        $is_started = $att_count > 0;
        foreach ($ua as $a) {
            $r = json_decode($a->result_json ?? '{}', true);
            if (!empty($r['teacher_review_requested'])) {
                $is_submitted = true;
                break;
            }
        }

        if ($is_submitted) { $a_submitted++; $sec_total_submitted++; }
        elseif ($is_started) { $a_inprogress++; }
        else { $a_notstarted++; }

        // Hints.
        $stu_hints = 0;
        foreach ($ua as $a) {
            $h = json_decode($a->used_hints_json ?? '[]', true);
            if (is_array($h)) { $stu_hints += count($h); }

            // Daily.
            $dk = date('Y-m-d', (int)$a->timecreated);
            if (isset($daily_map[$dk])) $daily_map[$dk]++;

            // Categories.
            if (!empty($a->ai_feedback_json)) {
                $fb = json_decode($a->ai_feedback_json, true);
                if (is_array($fb) && ($fb['status'] ?? '') === 'success') {
                    $cat = $fb['diagnosis']['category'] ?? 'unknown';
                    $sec_categories[$cat] = ($sec_categories[$cat] ?? 0) + 1;
                }
            }
        }
        $a_hints += $stu_hints;
        $sec_total_hints += $stu_hints;

        // Grade.
        $grade_val = null;
        if (isset($grades[$stu->id]) && is_numeric($grades[$stu->id]->grade)) {
            $grade_val = round((float)$grades[$stu->id]->grade, 1);
            $a_graded++;
            $a_grade_sum += $grade_val;
            $sec_graded++;
            $sec_grade_sum += $grade_val;
        }

        // Heatmap data.
        $heatmap[$stu->id][$pid] = [
            'grade' => $grade_val,
            'status' => $is_submitted ? 'submitted' : ($is_started ? 'inprogress' : 'notstarted'),
            'attempts' => $att_count,
            'hints' => $stu_hints,
        ];
    }

    $a_avg = $a_graded > 0 ? round($a_grade_sum / $a_graded, 1) : 0;

    $activity_stats[$pid] = [
        'cm' => $cm,
        'attempts' => $a_attempts,
        'hints' => $a_hints,
        'submitted' => $a_submitted,
        'inprogress' => $a_inprogress,
        'notstarted' => $a_notstarted,
        'graded' => $a_graded,
        'avg_grade' => $a_avg,
    ];

    // Short label for chart.
    $short = $cm->name;
    if (mb_strlen($short) > 25) $short = mb_substr($short, 0, 22) . '...';
    $act_labels[] = $short;
    $act_submit[] = $a_submitted;
    $act_partial[] = $a_inprogress;
    $act_notstarted[] = $a_notstarted;
}

$sec_avg = $sec_graded > 0 ? round($sec_grade_sum / $sec_graded, 1) : 0;
$daily_values = array_values($daily_map);
$total_recent = array_sum($daily_values);
arsort($sec_categories);

// Students who submitted ALL activities.
$fully_submitted = 0;
foreach ($students as $stu) {
    $all_done = true;
    foreach ($sec_cms as $cm) {
        $h = $heatmap[$stu->id][$cm->instance] ?? null;
        if (!$h || $h['status'] !== 'submitted') { $all_done = false; break; }
    }
    if ($all_done) $fully_submitted++;
}

// Grade distribution.
$grade_bins = ['0-20' => 0, '21-40' => 0, '41-60' => 0, '61-80' => 0, '81-100' => 0];
foreach ($grades_by_activity as $grades) {
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

// ── Output ────────────────────────────────────────────────────────────────

echo $OUTPUT->header();
echo '<div class="aicode-srpt-wrap">';

// ── Header ────────────────────────────────────────────────────────────────
echo '<div class="aicode-srpt-page-head">';
echo '<div class="aicode-srpt-breadcrumb">';
echo html_writer::link(
    new moodle_url('/mod/aicode/course_report.php', ['courseid' => $courseid]),
    '&larr; Dashboard Course',
    ['class' => 'aicode-srpt-back']
);
echo '</div>';
echo '<h2>';
echo '<span style="font-size:1.5rem;margin-right:8px">📊</span>';
echo 'AICode — ' . s($sectionname);
echo '</h2>';
echo '<div class="aicode-srpt-meta">' . format_string($course->fullname) . ' &middot; ' . count($sec_cms) . ' aktivitas AICode</div>';
echo '</div>';

// ── Metrics ───────────────────────────────────────────────────────────────
echo '<div class="aicode-srpt-metrics">';

$metrics = [
    ['icon' => '📦', 'label' => 'Aktivitas',          'value' => count($sec_cms),       'sub' => 'aicode di section ini'],
    ['icon' => '👥', 'label' => 'Siswa Enrolled',      'value' => $total_students,       'sub' => 'di course'],
    ['icon' => '✅', 'label' => 'Semua Submit',         'value' => $fully_submitted,      'sub' => round($total_students > 0 ? $fully_submitted / $total_students * 100 : 0) . '% siswa'],
    ['icon' => '📈', 'label' => 'Rata-rata Nilai',      'value' => $sec_avg,              'sub' => $sec_graded . ' nilai terhitung'],
    ['icon' => '📝', 'label' => 'Total Percobaan',      'value' => $sec_total_attempts,   'sub' => 'keseluruhan section'],
    ['icon' => '💡', 'label' => 'Total AI Hint',        'value' => $sec_total_hints,      'sub' => 'penggunaan hint'],
];

foreach ($metrics as $m) {
    echo '<div class="aicode-srpt-metric-card">';
    echo '<span class="aicode-srpt-metric-icon">' . $m['icon'] . '</span>';
    echo '<div class="aicode-srpt-metric-body">';
    echo '<div class="aicode-srpt-metric-label">' . $m['label'] . '</div>';
    echo '<div class="aicode-srpt-metric-value">' . $m['value'] . '</div>';
    echo '<div class="aicode-srpt-metric-sub">' . $m['sub'] . '</div>';
    echo '</div></div>';
}
echo '</div>';

// ── Charts ────────────────────────────────────────────────────────────────
echo '<div class="aicode-srpt-charts">';
echo '<div class="aicode-srpt-chart-row">';

// Chart 1 — Progress per Aktivitas.
echo '<div class="aicode-srpt-chart-card aicode-srpt-chart-wide">';
echo '<div class="aicode-srpt-chart-title">Progress Siswa per Aktivitas</div>';
echo '<div class="aicode-srpt-chart-wrap"><canvas id="srpt-chart-progress"></canvas></div>';
echo '</div>';

// Chart 2 — Distribusi Nilai.
echo '<div class="aicode-srpt-chart-card">';
echo '<div class="aicode-srpt-chart-title">Distribusi Nilai</div>';
if ($sec_graded > 0) {
    echo '<div class="aicode-srpt-chart-wrap"><canvas id="srpt-chart-grades"></canvas></div>';
    echo '<div class="aicode-srpt-chart-sub">Rata-rata: <strong>' . $sec_avg . '</strong></div>';
} else {
    echo '<div class="aicode-srpt-chart-empty">Belum ada nilai.</div>';
}
echo '</div>';

// Chart 3 — Daily.
echo '<div class="aicode-srpt-chart-card">';
echo '<div class="aicode-srpt-chart-title">Aktivitas 14 Hari Terakhir</div>';
if ($total_recent > 0) {
    echo '<div class="aicode-srpt-chart-wrap"><canvas id="srpt-chart-daily"></canvas></div>';
} else {
    echo '<div class="aicode-srpt-chart-empty">Belum ada aktivitas.</div>';
}
echo '</div>';

echo '</div></div>'; // chart-row + charts

// ── Activity Table ────────────────────────────────────────────────────────
echo '<div class="aicode-srpt-section-box">';
echo '<h3 class="aicode-srpt-box-title">📋 Ringkasan Aktivitas</h3>';
echo '<div class="table-responsive">';
echo '<table class="table table-hover align-middle aicode-srpt-table">';
echo '<thead class="table-light"><tr>';
echo '<th style="width:36px">#</th>';
echo '<th>Aktivitas</th>';
echo '<th class="text-center">Mode</th>';
echo '<th class="text-center">Submit</th>';
echo '<th class="text-center">Sedang</th>';
echo '<th class="text-center">Belum</th>';
echo '<th class="text-center">Rata-rata</th>';
echo '<th class="text-center">Percobaan</th>';
echo '<th class="text-center">AI Hint</th>';
echo '<th></th>';
echo '</tr></thead><tbody>';

$rn = 0;
foreach ($activity_stats as $pid => $as) {
    $rn++;
    $cm = $as['cm'];
    $report_url = new moodle_url('/mod/aicode/report.php', ['id' => $cm->cmid]);
    $modelabel = $cm->mode === 'exam'
        ? '<span class="badge bg-danger">Ujian</span>'
        : '<span class="badge bg-primary">Latihan</span>';

    $pct = $total_students > 0 ? round($as['submitted'] / $total_students * 100) : 0;

    echo '<tr>';
    echo '<td class="text-muted small fw-semibold">' . $rn . '</td>';
    echo '<td><a href="' . s($report_url->out(false)) . '" class="fw-semibold text-decoration-none aicode-srpt-act-link">' . s($cm->name) . '</a></td>';
    echo '<td class="text-center">' . $modelabel . '</td>';
    echo '<td class="text-center"><span class="badge bg-success">' . $as['submitted'] . '</span> <span class="text-muted small">(' . $pct . '%)</span></td>';
    echo '<td class="text-center">' . ($as['inprogress'] > 0 ? '<span class="badge bg-warning text-dark">' . $as['inprogress'] . '</span>' : '<span class="text-muted">0</span>') . '</td>';
    echo '<td class="text-center">' . ($as['notstarted'] > 0 ? '<span class="badge bg-secondary">' . $as['notstarted'] . '</span>' : '<span class="text-muted">0</span>') . '</td>';
    echo '<td class="text-center fw-semibold">' . ($as['avg_grade'] > 0 ? $as['avg_grade'] : '—') . '</td>';
    echo '<td class="text-center">' . $as['attempts'] . '</td>';
    echo '<td class="text-center">' . ($as['hints'] > 0 ? $as['hints'] : '—') . '</td>';
    echo '<td>' . html_writer::link($report_url, 'Laporan &rarr;', ['class' => 'btn btn-sm btn-outline-primary']) . '</td>';
    echo '</tr>';
}

echo '</tbody></table></div></div>';

// ── Student Progress Heatmap ──────────────────────────────────────────────
echo '<div class="aicode-srpt-section-box">';
echo '<h3 class="aicode-srpt-box-title">🎯 Progress Siswa per Aktivitas</h3>';
echo '<p class="text-muted small mb-3">Tabel menunjukkan nilai setiap siswa per aktivitas. Warna hijau = tinggi, kuning = sedang, merah = rendah.</p>';
echo '<div class="table-responsive">';
echo '<table class="table table-bordered align-middle aicode-srpt-heatmap">';
echo '<thead class="table-light"><tr>';
echo '<th style="width:36px">#</th>';
echo '<th>Nama Siswa</th>';

// Activity column headers (shortened).
foreach ($sec_cms as $cm) {
    $short = $cm->name;
    // Remove "Praktik " prefix if exists.
    $short = preg_replace('/^Praktik\s+/', '', $short);
    if (mb_strlen($short) > 18) $short = mb_substr($short, 0, 15) . '...';
    echo '<th class="text-center aicode-srpt-heatmap-th" title="' . s($cm->name) . '">' . s($short) . '</th>';
}
echo '<th class="text-center">Rata-rata</th>';
echo '<th class="text-center">Hint</th>';
echo '</tr></thead><tbody>';

// Sort students by average grade descending.
$student_avgs = [];
foreach ($students as $stu) {
    $sum = 0;
    $cnt = 0;
    $totalh = 0;
    foreach ($sec_cms as $cm) {
        $h = $heatmap[$stu->id][$cm->instance] ?? null;
        if ($h && $h['grade'] !== null) {
            $sum += $h['grade'];
            $cnt++;
        }
        if ($h) $totalh += $h['hints'];
    }
    $student_avgs[$stu->id] = [
        'avg' => $cnt > 0 ? round($sum / $cnt, 1) : 0,
        'hints' => $totalh,
        'graded' => $cnt,
    ];
}
// Sort by avg desc.
$sorted_students = array_values($students);
usort($sorted_students, function($a, $b) use ($student_avgs) {
    return ($student_avgs[$b->id]['avg'] ?? 0) <=> ($student_avgs[$a->id]['avg'] ?? 0);
});

$rn = 0;
foreach ($sorted_students as $stu) {
    $rn++;
    $avg = $student_avgs[$stu->id]['avg'];
    $totalh = $student_avgs[$stu->id]['hints'];

    echo '<tr>';
    echo '<td class="text-muted small">' . $rn . '</td>';
    echo '<td class="fw-semibold" style="white-space:nowrap">' . fullname($stu) . '</td>';

    foreach ($sec_cms as $cm) {
        $h = $heatmap[$stu->id][$cm->instance] ?? null;
        if (!$h || $h['status'] === 'notstarted') {
            echo '<td class="text-center aicode-srpt-heat-cell" style="background:#f8f9fa"><span class="text-muted">—</span></td>';
        } elseif ($h['grade'] !== null) {
            $g = $h['grade'];
            // Color gradient: red(0) → yellow(50) → green(100).
            if ($g >= 85) {
                $bg = '#d1fae5'; $tc = '#065f46';
            } elseif ($g >= 70) {
                $bg = '#fef3c7'; $tc = '#92400e';
            } else {
                $bg = '#fee2e2'; $tc = '#991b1b';
            }
            $icon = $h['status'] === 'submitted' ? '✅' : '🔄';
            echo '<td class="text-center aicode-srpt-heat-cell" style="background:' . $bg . ';color:' . $tc . '">';
            echo '<span class="aicode-srpt-heat-grade">' . $g . '</span>';
            echo '<span class="aicode-srpt-heat-icon">' . $icon . '</span>';
            echo '</td>';
        } else {
            // In progress, no grade yet.
            echo '<td class="text-center aicode-srpt-heat-cell" style="background:#eff6ff">';
            echo '<span class="badge bg-warning text-dark" style="font-size:.65rem">🔄</span>';
            echo '</td>';
        }
    }

    // Average.
    $avgbg = '';
    if ($avg >= 85) $avgbg = 'background:#d1fae5;color:#065f46';
    elseif ($avg >= 70) $avgbg = 'background:#fef3c7;color:#92400e';
    elseif ($avg > 0) $avgbg = 'background:#fee2e2;color:#991b1b';
    echo '<td class="text-center fw-bold" style="' . $avgbg . '">' . ($avg > 0 ? $avg : '—') . '</td>';

    // Hints.
    echo '<td class="text-center text-muted small">' . ($totalh > 0 ? $totalh . '×' : '—') . '</td>';
    echo '</tr>';
}

echo '</tbody></table></div></div>';

// ── Charts JS ─────────────────────────────────────────────────────────────
$chartdata = json_encode([
    'progress' => ['labels' => $act_labels, 'submit' => $act_submit, 'partial' => $act_partial, 'notstarted' => $act_notstarted],
    'grades' => ['labels' => array_keys($grade_bins), 'data' => array_values($grade_bins)],
    'daily' => ['labels' => $daily_labels, 'data' => $daily_values],
], JSON_HEX_TAG | JSON_HEX_AMP);

$chartjsurl = $CFG->wwwroot . '/mod/aicode/javascript/chart.min.js';
echo '<script src="' . s($chartjsurl) . '"></script>';
echo '<script>';
echo 'window._srptData = ' . $chartdata . ';';
echo <<<'CHARTJS'
(function(){
    "use strict";
    if(!window.Chart)return;
    var d=window._srptData;
    Chart.defaults.font.family="system-ui,-apple-system,sans-serif";
    Chart.defaults.font.size=11;
    Chart.defaults.color="#6c757d";
    var grey="#f1f3f5";

    var c1=document.getElementById("srpt-chart-progress");
    if(c1){
        new Chart(c1,{
            type:"bar",
            data:{
                labels:d.progress.labels,
                datasets:[
                    {label:"Submit",data:d.progress.submit,backgroundColor:"#198754",borderRadius:3,borderWidth:0},
                    {label:"Sedang",data:d.progress.partial,backgroundColor:"#ffc107",borderRadius:3,borderWidth:0},
                    {label:"Belum",data:d.progress.notstarted,backgroundColor:"#adb5bd",borderRadius:3,borderWidth:0}
                ]
            },
            options:{
                responsive:true,maintainAspectRatio:false,
                plugins:{legend:{position:"bottom",labels:{boxWidth:11,padding:10}}},
                scales:{x:{stacked:true,grid:{display:false}},y:{stacked:true,beginAtZero:true,ticks:{stepSize:1,precision:0},grid:{color:grey}}}
            }
        });
    }

    var c2=document.getElementById("srpt-chart-grades");
    if(c2){
        new Chart(c2,{
            type:"bar",
            data:{labels:d.grades.labels,datasets:[{label:"Siswa",data:d.grades.data,backgroundColor:["#dc3545","#fd7e14","#ffc107","#20c997","#198754"],borderRadius:4,borderWidth:0}]},
            options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1,precision:0},grid:{color:grey}},x:{grid:{display:false}}}}
        });
    }

    var c3=document.getElementById("srpt-chart-daily");
    if(c3){
        new Chart(c3,{
            type:"line",
            data:{labels:d.daily.labels,datasets:[{label:"Percobaan",data:d.daily.data,borderColor:"#0d6efd",backgroundColor:"rgba(13,110,253,0.07)",borderWidth:2,tension:0.35,fill:true,pointRadius:3,pointBackgroundColor:"#0d6efd"}]},
            options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1,precision:0},grid:{color:grey}},x:{grid:{display:false},ticks:{maxRotation:45,font:{size:10}}}}}
        });
    }
}());
CHARTJS;
echo '</script>';

// ── CSS ───────────────────────────────────────────────────────────────────
echo '<style>
#region-main,#region-main-box,.course-content{max-width:none!important}
#page-content{padding-left:0;padding-right:0}
.aicode-srpt-wrap{max-width:100%;margin:0;padding:0 0 48px}
.aicode-srpt-page-head{margin-bottom:20px}
.aicode-srpt-breadcrumb{margin-bottom:8px}
.aicode-srpt-back{font-size:.8125rem;color:#6c757d;text-decoration:none}
.aicode-srpt-back:hover{color:#0d6efd}
.aicode-srpt-page-head h2{margin:0 0 4px;font-size:1.5rem;font-weight:700;display:flex;align-items:center;gap:4px}
.aicode-srpt-meta{font-size:.875rem;color:#6c757d}
/* Metrics */
.aicode-srpt-metrics{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:10px;margin-bottom:24px}
.aicode-srpt-metric-card{display:flex;align-items:center;gap:12px;padding:14px 16px;background:#fff;border:1px solid #e9ecef;border-radius:.5rem;box-shadow:0 1px 3px rgba(0,0,0,.04);transition:box-shadow .15s,transform .15s}
.aicode-srpt-metric-card:hover{box-shadow:0 3px 10px rgba(0,0,0,.08);transform:translateY(-1px)}
.aicode-srpt-metric-icon{font-size:1.3rem;flex-shrink:0;width:28px;text-align:center}
.aicode-srpt-metric-body{min-width:0;flex:1}
.aicode-srpt-metric-label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6c757d}
.aicode-srpt-metric-value{font-size:1.35rem;font-weight:700;color:#212529;line-height:1.15}
.aicode-srpt-metric-sub{font-size:.65rem;color:#adb5bd}
/* Charts */
.aicode-srpt-charts{margin-bottom:24px}
.aicode-srpt-chart-row{display:flex;flex-wrap:wrap;gap:14px}
.aicode-srpt-chart-card{background:#fff;border:1px solid #e9ecef;border-radius:.5rem;padding:16px;flex:1 1 260px;min-width:0;display:flex;flex-direction:column;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.aicode-srpt-chart-wide{flex:2 1 400px}
.aicode-srpt-chart-title{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;margin-bottom:12px}
.aicode-srpt-chart-wrap{position:relative;height:220px;flex:1}
.aicode-srpt-chart-sub{font-size:.72rem;color:#6c757d;margin-top:8px;text-align:center}
.aicode-srpt-chart-empty{height:200px;display:flex;align-items:center;justify-content:center;color:#adb5bd;font-size:.82rem;font-style:italic}
/* Tables */
.aicode-srpt-section-box{background:#fff;border:1px solid #e9ecef;border-radius:.5rem;padding:20px 24px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.aicode-srpt-box-title{font-size:1.1rem;font-weight:700;margin:0 0 16px;color:#212529}
.aicode-srpt-table th{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;white-space:nowrap}
.aicode-srpt-act-link{color:#212529;transition:color .15s}
.aicode-srpt-act-link:hover{color:#0d6efd}
/* Heatmap */
.aicode-srpt-heatmap th,.aicode-srpt-heatmap td{padding:6px 8px!important;font-size:.8rem}
.aicode-srpt-heatmap-th{font-size:.62rem!important;max-width:100px;white-space:normal;line-height:1.3}
.aicode-srpt-heat-cell{min-width:60px;transition:transform .1s}
.aicode-srpt-heat-cell:hover{transform:scale(1.05);z-index:1;box-shadow:0 2px 8px rgba(0,0,0,.12)}
.aicode-srpt-heat-grade{font-weight:700;font-size:.82rem;display:block;line-height:1.2}
.aicode-srpt-heat-icon{font-size:.6rem;display:block;line-height:1}
@media(max-width:768px){
    .aicode-srpt-metrics{grid-template-columns:repeat(2,1fr)}
    .aicode-srpt-chart-row{flex-direction:column}
    .aicode-srpt-heatmap{font-size:.7rem}
}
</style>';

echo '</div>';
echo $OUTPUT->footer();
