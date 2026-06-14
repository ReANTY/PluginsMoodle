<?php
/**
 * [KHUSUS GURU] Generator halaman kunci jawaban lengkap course JS Fundamental.
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/js_course_week_helpers.php');

/**
 * Load merged week data (same structure as build_js_course_full.php).
 */
function js_teacher_keys_load_weeks(): array {
    $week1 = require(__DIR__ . '/js_week1_content.php');
    $w1extra = require(__DIR__ . '/js_week1_enrichment.php');
    foreach ($week1['micro_lessons'] as $i => &$lesson) {
        if (isset($w1extra['lesson_enrichment'][$i])) {
            $lesson['practice'] = $w1extra['lesson_enrichment'][$i]['practice'];
            $lesson['micro_quiz'] = $w1extra['lesson_enrichment'][$i]['micro_quiz'];
        }
    }
    unset($lesson);
    $week1['weekly_quiz'] = $w1extra['weekly_quiz'];
    $week1['weekly_assignment'] = $w1extra['weekly_assignment'];

    $weeks = [1 => $week1];
    for ($w = 2; $w <= 8; $w++) {
        $weeks[$w] = require(__DIR__ . '/js_week' . $w . '_content.php');
    }
    return $weeks;
}

/**
 * Render solution block for practice/assignment.
 */
function js_teacher_keys_render_solution(string $name, array $solutions, array $activity): string {
    $html = '<h5>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</h5>';

    if (!empty($activity['testcases']) && $activity['testcases'] !== '[]') {
        $html .= '<p><strong>Testcase Aicode:</strong> <code>' . htmlspecialchars($activity['testcases'], ENT_QUOTES, 'UTF-8') . '</code></p>';
    }

    if (isset($solutions[$name])) {
        $sol = $solutions[$name];
        if (!empty($sol['rubric'])) {
            $html .= '<p><strong>Rubrik:</strong> ' . htmlspecialchars($sol['rubric'], ENT_QUOTES, 'UTF-8') . '</p>';
        }
        if (!empty($sol['note'])) {
            $html .= '<p><em>' . htmlspecialchars($sol['note'], ENT_QUOTES, 'UTF-8') . '</em></p>';
        }
        if (!empty($sol['code'])) {
            $html .= '<p><strong>Kode solusi ideal:</strong></p>' . js_render_code_block($sol['code']);
        }
    } else {
        $html .= '<p class="text-warning"><em>Kode solusi belum terdaftar — gunakan testcase di atas.</em></p>';
    }

    return $html;
}

/**
 * Build teacher keys page payload for create_teacher_only_page_resource().
 */
function build_teacher_keys_page(): array {
    $weeks = js_teacher_keys_load_weeks();
    $solutions = require(__DIR__ . '/js_teacher_solutions_data.php');

    $html = '<div class="teacher-keys" style="max-width:960px;">';
    $html .= '<div class="alert alert-danger">';
    $html .= '<strong>🔒 RAHASIA GURU</strong> — Halaman ini hanya terlihat oleh Guru, Admin, dan peran dengan izin ';
    $html .= '<em>melihat aktivitas tersembunyi</em>. Jangan bagikan ke siswa.';
    $html .= '</div>';

    $html .= '<p>Berisi kunci jawaban: <strong>Praktik/Latihan</strong>, <strong>Kuis Micro Lesson</strong>, ';
    $html .= '<strong>Weekly Quiz</strong>, <strong>Weekly Assignment (Ujian)</strong>, dan <strong>Final Project</strong>.</p>';

    foreach ($weeks as $wnum => $week) {
        $wtitle = $week['section_name'] ?? ('Minggu ' . $wnum);
        $html .= '<hr><h2>' . htmlspecialchars($wtitle, ENT_QUOTES, 'UTF-8') . '</h2>';

        foreach ($week['micro_lessons'] as $lesson) {
            $mltitle = $lesson['title'] ?? 'Micro Lesson';
            $html .= '<h3>' . htmlspecialchars($mltitle, ENT_QUOTES, 'UTF-8') . '</h3>';

            if (!empty($lesson['practice'])) {
                $pname = $lesson['practice']['name'] ?? 'Praktik';
                $html .= '<div style="background:#fff8e1;padding:12px;border-radius:8px;margin-bottom:12px;">';
                $html .= '<h4>📋 Praktik / Latihan</h4>';
                $html .= js_teacher_keys_render_solution($pname, $solutions, $lesson['practice']);
                $html .= '</div>';
            }

            if (!empty($lesson['micro_quiz'])) {
                $html .= '<div style="background:#e3f2fd;padding:12px;border-radius:8px;margin-bottom:12px;">';
                $html .= js_render_mcq_key_html($lesson['micro_quiz'], '📝 Kuis Micro Lesson');
                $html .= '</div>';
            }
        }

        if (!empty($week['weekly_quiz'])) {
            $html .= '<div style="background:#e8f5e9;padding:12px;border-radius:8px;margin-bottom:12px;">';
            $html .= js_render_mcq_key_html($week['weekly_quiz'], '📊 Weekly Quiz');
            $html .= '</div>';
        }

        if (!empty($week['weekly_assignment'])) {
            $aname = $week['weekly_assignment']['name'] ?? 'Assignment';
            $html .= '<div style="background:#fce4ec;padding:12px;border-radius:8px;margin-bottom:12px;">';
            $html .= '<h4>📌 Weekly Assignment (Ujian)</h4>';
            $html .= js_teacher_keys_render_solution($aname, $solutions, $week['weekly_assignment']);
            $html .= '</div>';
        }

        if ($wnum === 8 && !empty($week['final_project'])) {
            $fname = $week['final_project']['name'] ?? 'Final Project';
            $html .= '<div style="background:#f3e5f5;padding:12px;border-radius:8px;margin-bottom:12px;">';
            $html .= '<h4>🏆 Final Project (Ujian)</h4>';
            $html .= js_teacher_keys_render_solution($fname, $solutions, $week['final_project']);
            $html .= '</div>';
        }
    }

    $html .= '<hr><p><small>Diperbarui otomatis oleh <code>build_js_course_full.php</code>. ';
    $html .= 'Untuk course yang sudah ada, jalankan builder ulang atau salin konten ini ke Page tersembunyi secara manual.</small></p>';
    $html .= '</div>';

    return [
        'title' => '🔒 [KHUSUS GURU] Kunci Jawaban Lengkap',
        'intro' => 'Kunci jawaban praktik, kuis, assignment, dan final project — hanya untuk Guru dan Admin.',
        'content' => $html,
    ];
}
