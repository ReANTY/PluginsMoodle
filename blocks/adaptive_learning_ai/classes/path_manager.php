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

        // Modul remedial adalah aktivitas umum berbasis kuis evaluasi, jangan difilter oleh CSS level track
        if (preg_match('/(remedial|remidial)/i', $t)) {
            return null;
        }

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

        // 3. Cek Primary / Dasar: [Primary], [Dasar], atau kata 'primary' / 'pemula'
        if (preg_match('/[\[\(](primary|dasar|pemula|beginner)[\]\)]/i', $t)
            || preg_match('/\b(primary|pemula|beginner)\b/i', $t)
            || preg_match('/\b(tingkat|level)\s+dasar\b/i', $t)) {
            return self::LEVEL_PRIMARY;
        }

        return null;
    }


    /**
     * Membersihkan tag level (seperti [Primary], [Intermediate], [Expert], dll.) dari nama judul modul
     * sehingga tampilan judul menjadi rapi dan bersih.
     *
     * @param string $title
     * @return string
     */
    public static function strip_level_tag(string $title): string {
        if (empty(trim($title))) {
            return $title;
        }
        $pattern = '/^\s*[\[\(](primary|intermediate|expert|dasar|menengah|mahir|pemula|beginner|advanced|remedial|remidial)[\]\)]\s*[-–:]?\s*/i';
        $cleaned = preg_replace($pattern, '', $title);
        return trim($cleaned);
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

        // 1. Ambil semua kuis di section ini
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

        $eval_best     = null;
        $remedial_best = null;

        foreach ($quiz_rows as $row) {
            if ($row->sumgrades !== null) {
                $maxmarks = (!empty($row->quiz_maxsumgrades) && $row->quiz_maxsumgrades > 0)
                    ? (float) $row->quiz_maxsumgrades
                    : ((!empty($row->maxgrade) && $row->maxgrade > 0) ? (float) $row->maxgrade : 100);

                $pct = round(((float)$row->sumgrades / $maxmarks) * 100, 1);
                $is_remedial = (bool) preg_match('/(remedial|remidial)/i', $row->name);

                if ($is_remedial) {
                    if ($remedial_best === null || $pct > $remedial_best) {
                        $remedial_best = $pct;
                    }
                } else {
                    if ($eval_best === null || $pct > $eval_best) {
                        $eval_best = $pct;
                    }
                }
            }
        }

        // Penilaian murni 100% kuis:
        // Jika ada pengerjaan kuis remedial, maka nilai remedial yang menentukan level minggu selanjutnya.
        // Jika hanya kuis evaluasi, maka nilai kuis evaluasi yang digunakan.
        if ($remedial_best !== null) {
            $composite = $remedial_best;
            $source    = 'remedial';
        } elseif ($eval_best !== null) {
            $composite = $eval_best;
            $source    = 'eval';
        } else {
            return null; // Belum ada data pengerjaan kuis evaluasi maupun remedial
        }

        return [
            'section'     => $sectionnum,
            'quiz_avg'    => $composite,
            'aicode_avg'  => null,
            'composite'   => $composite,
            'source'      => $source
        ];
    }

    /**
     * Dapatkan level kognitif siswa untuk suatu section (Minggu).
     *
     * Aturan:
     * - Section 0: Umum / Orientasi -> LEVEL_ALL
     * - Section 1: Profiling Baseline (Minggu 1) -> LEVEL_ALL (semua siswa mengerjakan modul yang sama)
     * - Section >= 2: Adaptif berdasarkan performa section sebelumnya (Section N - 1).
     *   - Skor < 70: LEVEL_PRIMARY
     *   - Skor 70 - 89: LEVEL_INTERMEDIATE
     *   - Skor >= 90: LEVEL_EXPERT
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

        // Ambil ambang batas dari konfigurasi Moodle (default: Primary < 70, Expert >= 90)
        $primary_threshold = (int) (get_config('block_adaptive_learning_ai', 'primary_threshold') ?: 70);
        $expert_threshold  = (int) (get_config('block_adaptive_learning_ai', 'expert_threshold') ?: 90);

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
