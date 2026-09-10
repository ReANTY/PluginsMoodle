<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace block_adaptive_learning_ai;

defined('MOODLE_INTERNAL') || die();

/**
 * Path Manager for Adaptive Learning AI
 *
 * Mengelola penentuan Learning Path (Primary, Intermediate, Expert),
 * evaluasi mingguan (Kuis + AICode), dan seleksi modul yang disembunyikan/ditampilkan.
 */
class path_manager {

    const LEVEL_PRIMARY      = 'PRIMARY';
    const LEVEL_INTERMEDIATE = 'INTERMEDIATE';
    const LEVEL_EXPERT       = 'EXPERT';
    const LEVEL_ALL          = 'ALL';

    /**
     * Deteksi tingkatan level dari nama/judul modul.
     *
     * @param string $title
     * @return string|null 'PRIMARY' | 'INTERMEDIATE' | 'EXPERT' | null (modul umum)
     */
    public static function detect_module_level(string $title): ?string {
        if (empty(trim($title))) {
            return null;
        }

        $t = strtolower($title);

        // 1. Cek Expert / Mahir: [Expert], (Expert), atau kata 'expert' / 'mahir'
        if (preg_match('/[\[\(](expert|mahir|advanced)[\]\)]/i', $t)
            || preg_match('/\b(expert|mahir)\b/i', $t)
            || preg_match('/\b(tingkat|level)\s+(expert|mahir|lanjut)\b/i', $t)) {
            return self::LEVEL_EXPERT;
        }

        // 2. Cek Intermediate / Menengah: [Intermediate], (Intermediate), atau kata 'intermediate' / 'menengah'
        if (preg_match('/[\[\(](intermediate|menengah|standard)[\]\)]/i', $t)
            || preg_match('/\b(intermediate|menengah)\b/i', $t)
            || preg_match('/\b(tingkat|level)\s+(intermediate|menengah)\b/i', $t)) {
            return self::LEVEL_INTERMEDIATE;
        }

        // 3. Cek Primary / Dasar: [Primary], [Dasar], atau kata 'primary' / 'pemula' / 'remedial'
        if (preg_match('/[\[\(](primary|dasar|pemula|beginner|remedial|remidial)[\]\)]/i', $t)
            || preg_match('/\b(primary|pemula|beginner|remedial|remidial)\b/i', $t)
            || preg_match('/\b(tingkat|level)\s+dasar\b/i', $t)) {
            return self::LEVEL_PRIMARY;
        }

        return null;
    }

    /**
     * Mendapatkan informasi label & badge warna untuk suatu level.
     *
     * @param string $level
     * @return array
     */
    public static function get_level_badge_info(string $level): array {
        switch (strtoupper($level)) {
            case self::LEVEL_EXPERT:
                return [
                    'key'       => self::LEVEL_EXPERT,
                    'label'     => 'Expert',
                    'tag'       => '[Expert]',
                    'color'     => '#059669',
                    'bg'        => 'rgba(16, 185, 129, 0.12)',
                    'border'    => 'rgba(16, 185, 129, 0.35)',
                    'icon'      => 'fa-crown',
                    'desc'      => 'Tantangan Tingkat Lanjut'
                ];
            case self::LEVEL_INTERMEDIATE:
                return [
                    'key'       => self::LEVEL_INTERMEDIATE,
                    'label'     => 'Intermediate',
                    'tag'       => '[Intermediate]',
                    'color'     => '#d97706',
                    'bg'        => 'rgba(245, 158, 11, 0.12)',
                    'border'    => 'rgba(245, 158, 11, 0.35)',
                    'icon'      => 'fa-bolt',
                    'desc'      => 'Pemahaman Menengah'
                ];
            case self::LEVEL_PRIMARY:
            default:
                return [
                    'key'       => self::LEVEL_PRIMARY,
                    'label'     => 'Primary',
                    'tag'       => '[Primary]',
                    'color'     => '#2563eb',
                    'bg'        => 'rgba(37, 99, 235, 0.12)',
                    'border'    => 'rgba(37, 99, 235, 0.35)',
                    'icon'      => 'fa-seedling',
                    'desc'      => 'Penguatan Fondasi Dasar'
                ];
        }
    }

    /**
     * Hitung performa gabungan kognitif siswa (Kuis + AICode) pada section tertentu.
     *
     * @param int $courseid
     * @param int $userid
     * @param int $sectionnum
     * @return array|null Array info nilai atau null jika belum ada aktivitas dinilai
     */
    public static function calculate_section_performance(int $courseid, int $userid, int $sectionnum): ?array {
        global $DB;

        // 1. Ambil semua quiz di section ini
        $sql_quiz = "SELECT q.id AS quizid, q.name, q.grade AS maxgrade, q.sumgrades AS quiz_maxsumgrades,
                            qa.sumgrades, qa.timefinish
                     FROM {quiz} q
                     JOIN {course_modules} cm ON cm.instance = q.id
                     JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
                     JOIN {course_sections} cs ON cs.id = cm.section
                     LEFT JOIN {quiz_attempts} qa ON qa.quiz = q.id AND qa.userid = :userid AND qa.state = 'finished'
                     WHERE q.course = :courseid AND cs.section = :secnum
                     ORDER BY qa.timefinish DESC";

        $quiz_rows = $DB->get_records_sql($sql_quiz, [
            'courseid' => $courseid,
            'userid'   => $userid,
            'secnum'   => $sectionnum
        ]);

        $quiz_scores = [];
        $quiz_best   = [];
        foreach ($quiz_rows as $row) {
            if ($row->sumgrades !== null) {
                $maxmarks = (!empty($row->quiz_maxsumgrades) && $row->quiz_maxsumgrades > 0)
                    ? (float) $row->quiz_maxsumgrades
                    : ((!empty($row->maxgrade) && $row->maxgrade > 0) ? (float) $row->maxgrade : 100);

                $pct = round(((float)$row->sumgrades / $maxmarks) * 100, 1);
                if (!isset($quiz_best[$row->quizid]) || $pct > $quiz_best[$row->quizid]) {
                    $quiz_best[$row->quizid] = $pct;
                }
            }
        }
        $quiz_avg = !empty($quiz_best) ? (array_sum($quiz_best) / count($quiz_best)) : null;

        // 2. Ambil semua tugas praktik aicode di section ini
        $sql_aicode = "SELECT a.id AS aicodeid, a.name, gi.id AS gradeitemid, gg.finalgrade, gi.grademax
                       FROM {aicode} a
                       JOIN {course_modules} cm ON cm.instance = a.id
                       JOIN {modules} m ON m.id = cm.module AND m.name = 'aicode'
                       JOIN {course_sections} cs ON cs.id = cm.section
                       LEFT JOIN {grade_items} gi ON gi.itemmodule = 'aicode' AND gi.iteminstance = a.id AND gi.courseid = :courseid
                       LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = :userid
                       WHERE a.course = :courseid2 AND cs.section = :secnum";

        $aicode_rows = $DB->get_records_sql($sql_aicode, [
            'courseid'  => $courseid,
            'courseid2' => $courseid,
            'userid'    => $userid,
            'secnum'    => $sectionnum
        ]);

        $aicode_scores = [];
        foreach ($aicode_rows as $row) {
            if ($row->finalgrade !== null) {
                $max = ($row->grademax && $row->grademax > 0) ? (float)$row->grademax : 100.0;
                $pct = round(((float)$row->finalgrade / $max) * 100, 1);
                $aicode_scores[$row->aicodeid] = $pct;
            } else {
                // Periksa apakah ada attempt aicode tersimpan
                $attempt = $DB->get_record_sql(
                    "SELECT result_json FROM {aicode_attempts} WHERE problemid = ? AND userid = ? ORDER BY id DESC LIMIT 1",
                    [$row->aicodeid, $userid]
                );
                if ($attempt && !empty($attempt->result_json)) {
                    $res = json_decode($attempt->result_json, true);
                    if (is_array($res)) {
                        // Jika lulus run (exitCode == 0) atau teacher_review_requested
                        if (isset($res['exitCode']) && $res['exitCode'] === 0) {
                            $aicode_scores[$row->aicodeid] = 100.0;
                        } elseif (!empty($res['teacher_review_requested'])) {
                            $aicode_scores[$row->aicodeid] = 85.0;
                        }
                    }
                }
            }
        }
        $aicode_avg = !empty($aicode_scores) ? (array_sum($aicode_scores) / count($aicode_scores)) : null;

        // 3. Kombinasikan nilai kuis dan tugas aicode
        if ($quiz_avg === null && $aicode_avg === null) {
            return null; // Belum ada data pengerjaan
        }

        if ($quiz_avg !== null && $aicode_avg !== null) {
            $composite = round(($quiz_avg * 0.5) + ($aicode_avg * 0.5), 1);
        } elseif ($quiz_avg !== null) {
            $composite = round($quiz_avg, 1);
        } else {
            $composite = round($aicode_avg, 1);
        }

        return [
            'section'     => $sectionnum,
            'quiz_avg'    => $quiz_avg !== null ? round($quiz_avg, 1) : null,
            'aicode_avg'  => $aicode_avg !== null ? round($aicode_avg, 1) : null,
            'composite'   => $composite
        ];
    }

    /**
     * Dapatkan level kognitif siswa untuk suatu section (Minggu).
     *
     * Aturan:
     * - Section 0: Umum / Orientasi -> LEVEL_ALL
     * - Section 1: Profiling Baseline (Minggu 1) -> LEVEL_ALL (semua siswa mengerjakan modul yang sama)
     * - Section >= 2: Adaptif berdasarkan performa section sebelumnya (Section N - 1).
     *
     * @param int $courseid
     * @param int $userid
     * @param int $sectionnum
     * @return string LEVEL_ALL | LEVEL_PRIMARY | LEVEL_INTERMEDIATE | LEVEL_EXPERT
     */
    public static function get_user_level_for_section(int $courseid, int $userid, int $sectionnum): string {
        // Section 0 dan 1 (Minggu 1) selalu terbuka penuh untuk baseline profiling
        if ($sectionnum <= 1) {
            return self::LEVEL_ALL;
        }

        // Ambil ambang batas dari konfigurasi Moodle
        $primary_threshold = (int) (get_config('block_adaptive_learning_ai', 'primary_threshold') ?: 70);
        $expert_threshold  = (int) (get_config('block_adaptive_learning_ai', 'expert_threshold') ?: 85);

        // Evaluasi performa dari section tepat sebelumnya
        $prev_section = $sectionnum - 1;
        $perf = self::calculate_section_performance($courseid, $userid, $prev_section);

        // Jika section tepat sebelumnya belum ada nilai, telusuri section sebelumnya lagi yang ada nilainya
        if ($perf === null) {
            for ($s = $prev_section - 1; $s >= 1; $s--) {
                $perf = self::calculate_section_performance($courseid, $userid, $s);
                if ($perf !== null) {
                    break;
                }
            }
        }

        // Jika belum ada nilai sama sekali dari minggu-minggu sebelumnya,
        // siswa diarahkan ke jalur PRIMARY (fondasi dasar) agar tidak kewalahan
        if ($perf === null) {
            return self::LEVEL_PRIMARY;
        }

        $score = $perf['composite'];

        if ($score < $primary_threshold) {
            return self::LEVEL_PRIMARY;
        } elseif ($score < $expert_threshold) {
            return self::LEVEL_INTERMEDIATE;
        } else {
            return self::LEVEL_EXPERT;
        }
    }

    /**
     * Mendapatkan daftar cmid yang harus disembunyikan (hide) dari siswa pada suatu kursus.
     *
     * @param int $courseid
     * @param int $userid
     * @return array Array of cmid => ['level' => ..., 'title' => ..., 'student_level' => ...]
     */
    public static function get_hidden_cmids_for_user(int $courseid, int $userid): array {
        global $DB;

        $hidden_cmids = [];

        try {
            $course = $DB->get_record('course', ['id' => $courseid]);
            if (!$course) {
                return [];
            }

            $modinfo = get_fast_modinfo($course);
            $sections = $modinfo->get_section_info_all();

            foreach ($sections as $secnum => $section) {
                // Section 0 dan 1 (Minggu 1) adalah profiling awal, tidak ada yang disembunyikan
                if ($secnum <= 1) {
                    continue;
                }

                if (empty($modinfo->sections[$secnum])) {
                    continue;
                }

                // Tentukan level siswa pada section ini
                $student_level = self::get_user_level_for_section($courseid, $userid, $secnum);

                if ($student_level === self::LEVEL_ALL) {
                    continue;
                }

                // Loop setiap aktivitas di section ini
                foreach ($modinfo->sections[$secnum] as $cmid) {
                    $cm = $modinfo->cms[$cmid];
                    $mod_level = self::detect_module_level($cm->name);

                    // Jika modul memiliki tag level dan levelnya berbeda dari level siswa, sembunyikan!
                    if ($mod_level !== null && $mod_level !== $student_level) {
                        $hidden_cmids[$cmid] = [
                            'cmid'          => $cmid,
                            'name'          => $cm->name,
                            'section'       => $secnum,
                            'mod_level'     => $mod_level,
                            'student_level' => $student_level
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            debugging('get_hidden_cmids_for_user error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return $hidden_cmids;
    }

    /**
     * Cek apakah user saat ini adalah guru/admin (memiliki hak edit/kelola aktivitas).
     *
     * @param int $courseid
     * @return bool
     */
    public static function is_teacher_or_admin(int $courseid): bool {
        global $USER;
        if (!isloggedin() || isguestuser()) {
            return false;
        }
        if (is_siteadmin($USER)) {
            return true;
        }
        $coursecontext = \context_course::instance($courseid);
        return has_capability('moodle/course:manageactivities', $coursecontext)
            || has_capability('moodle/course:update', $coursecontext);
    }
}
