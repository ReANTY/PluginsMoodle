<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/classes/path_manager.php');

/**
 * Hook untuk extend navigation di course
 */
function block_adaptive_learning_ai_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context) {
    global $USER;

    if (!is_enrolled($context, $USER, '', true)) {
        return;
    }

    $is_teacher = has_capability('moodle/course:update', $context) || has_capability('mod/quiz:viewreports', $context);

    if ($is_teacher) {
        $url   = new moodle_url('/blocks/adaptive_learning_ai/reports/teacher_report.php', ['courseid' => $course->id]);
        $title = get_string('report_teacher', 'block_adaptive_learning_ai');
    } else {
        $url   = new moodle_url('/blocks/adaptive_learning_ai/reports/student_report.php', ['courseid' => $course->id]);
        $title = get_string('report_student', 'block_adaptive_learning_ai');
    }

    $node = $navigation->add($title, $url,
            navigation_node::TYPE_SETTING, null, 'adaptive_learning_ai_report', new pix_icon('i/report', ''));
    $node->set_force_into_more_menu(true);
}

/**
 * Injeksi tampilan adaptif ke halaman kursus (menyembunyikan modul tingkat lain untuk siswa,
 * atau memberikan badge level visual bagi guru/admin).
 *
 * @param int $courseid
 * @return string HTML & CSS/JS snippet
 */
function block_adaptive_learning_ai_render_adaptive_view(int $courseid): string {
    global $USER, $PAGE, $DB;

    static $already_rendered = [];
    if (!empty($already_rendered[$courseid])) {
        return '';
    }
    $already_rendered[$courseid] = true;

    if (!isloggedin() || isguestuser()) {
        return '';
    }

    $is_teacher = \block_adaptive_learning_ai\path_manager::is_teacher_or_admin($courseid);

    // Kumpulkan semua section dan modul
    $course = $DB->get_record('course', ['id' => $courseid]);
    if (!$course) {
        return '';
    }

    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();

    $html = '';

    if ($is_teacher) {
        // =========================================================================
        // MODE GURU / ADMIN: Berikan visual badge penanda tingkatan pada modul
        // =========================================================================
        $badge_data = [];
        foreach ($sections as $secnum => $section) {
            if ($secnum <= 0 || empty($modinfo->sections[$secnum])) {
                continue;
            }
            foreach ($modinfo->sections[$secnum] as $cmid) {
                $cm = $modinfo->cms[$cmid];
                $mod_level = \block_adaptive_learning_ai\path_manager::detect_module_level($cm->name);
                if ($mod_level !== null) {
                    $binfo = \block_adaptive_learning_ai\path_manager::get_level_badge_info($mod_level);
                    $badge_data[] = [
                        'cmid'   => $cmid,
                        'level'  => $mod_level,
                        'label'  => $binfo['label'],
                        'color'  => $binfo['color'],
                        'bg'     => $binfo['bg'],
                        'border' => $binfo['border'],
                        'icon'   => $binfo['icon']
                    ];
                }
            }
        }

        $json_badges = json_encode($badge_data);

        $html .= <<<HTML
<style>
.alai-teacher-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
    margin-left: 8px;
    vertical-align: middle;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.alai-teacher-notice {
    background: #f8fafc;
    border-left: 4px solid #3b82f6;
    padding: 8px 14px;
    margin: 10px 0 16px 0;
    border-radius: 0 8px 8px 0;
    font-size: 0.82rem;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var badges = {$json_badges};
    badges.forEach(function(b) {
        var el = document.querySelector('#module-' + b.cmid + ' .activityname, [data-id="' + b.cmid + '"] .activityname, #module-' + b.cmid + ' .instancename');
        if (el && !el.querySelector('.alai-teacher-badge')) {
            var span = document.createElement('span');
            span.className = 'alai-teacher-badge';
            span.style.color = b.color;
            span.style.background = b.bg;
            span.style.border = '1px solid ' + b.border;
            span.innerHTML = '<i class="fa ' + b.icon + '"></i> ' + b.label;
            el.appendChild(span);
        }
    });
});
</script>
HTML;

    } else {
        // =========================================================================
        // MODE SISWA: Sembunyikan modul level lain, tampilkan banner jalur aktif
        // =========================================================================
        $hidden_info = \block_adaptive_learning_ai\path_manager::get_hidden_cmids_for_user($courseid, (int)$USER->id);
        $hidden_cmids = array_keys($hidden_info);

        $css_selectors = [];
        foreach ($hidden_cmids as $cmid) {
            $css_selectors[] = "#module-{$cmid}";
            $css_selectors[] = "li.activity#module-{$cmid}";
            $css_selectors[] = ".activity-item[data-id=\"{$cmid}\"]";
        }

        $hide_css = '';
        if (!empty($css_selectors)) {
            $hide_css = implode(', ', $css_selectors) . " { display: none !important; }\n";
        }

        // Siapkan info banner level per section untuk siswa
        $section_banners = [];
        foreach ($sections as $secnum => $section) {
            if ($secnum <= 1) {
                continue; // Section 0 & 1 adalah profiling awal, tidak perlu banner
            }
            $slevel = \block_adaptive_learning_ai\path_manager::get_user_level_for_section($courseid, (int)$USER->id, $secnum);
            $binfo = \block_adaptive_learning_ai\path_manager::get_level_badge_info($slevel);
            $section_banners[] = [
                'secnum' => $secnum,
                'secid'  => $section->id,
                'level'  => $slevel,
                'label'  => $binfo['label'],
                'color'  => $binfo['color'],
                'bg'     => $binfo['bg'],
                'border' => $binfo['border'],
                'icon'   => $binfo['icon'],
                'desc'   => $binfo['desc']
            ];
        }

        $json_banners = json_encode($section_banners);

        $html .= <<<HTML
<style>
{$hide_css}
.alai-path-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 16px;
    border-radius: 12px;
    margin: 10px 0 16px 0;
    font-size: 0.85rem;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.alai-path-left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.alai-path-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.78rem;
}
.alai-path-desc {
    color: #475569;
    font-weight: 500;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var banners = {$json_banners};
    banners.forEach(function(b) {
        var secEl = document.querySelector('#section-' + b.secnum + ' .content, #section-' + b.secnum + ' .course-section-header, li#section-' + b.secnum);
        if (secEl && !secEl.querySelector('.alai-path-banner[data-sec="' + b.secnum + '"]')) {
            var bannerDiv = document.createElement('div');
            bannerDiv.className = 'alai-path-banner';
            bannerDiv.setAttribute('data-sec', b.secnum);
            bannerDiv.style.background = b.bg;
            bannerDiv.style.border = '1px solid ' + b.border;
            bannerDiv.innerHTML = '<div class="alai-path-left">' +
                '<span class="alai-path-tag" style="background:#ffffff; color:' + b.color + '; border:1px solid ' + b.border + ';">' +
                '<i class="fa ' + b.icon + '"></i> Jalur Belajar: ' + b.label + '</span>' +
                '<span class="alai-path-desc">' + b.desc + ' (Disaring berdasarkan performa minggu sebelumnya)</span>' +
                '</div>' +
                '<span style="font-size:0.75rem; color:#64748b;"><i class="fa fa-filter"></i> Materi adaptif aktif</span>';

            var headerEl = secEl.querySelector('.course-section-header') || secEl.querySelector('h3.sectionname') || secEl.firstChild;
            if (headerEl && headerEl.nextSibling) {
                headerEl.parentNode.insertBefore(bannerDiv, headerEl.nextSibling);
            } else {
                secEl.insertBefore(bannerDiv, secEl.firstChild);
            }
        }
    });
});
</script>
HTML;
    }

    return $html;
}

