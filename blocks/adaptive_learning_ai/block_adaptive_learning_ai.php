<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/gemini_api.php');
require_once(__DIR__ . '/classes/path_manager.php');

class block_adaptive_learning_ai extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_adaptive_learning_ai');
    }

    public function has_config() {
        return true;
    }

    public function applicable_formats() {
        return ['course-view' => true];
    }

    public function get_content() {
        global $COURSE, $CFG, $DB, $USER, $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $courseid      = $COURSE->id;

        // ============================================================
        // 1. BACA NILAI QUIZ MINGGU SEBELUMNYA via Grade API
        // ============================================================
        require_once($CFG->libdir . '/gradelib.php');

        $userScore    = 0;
        $weekNum      = 1;
        $quizName     = '-';
        $lastQuizTime = null;
        $attemptCount = 0;
        $quizMarksStr = '';

        try {
            // --------------------------------------------------------
            // Ambil quiz terakhir yang selesai dikerjakan siswa
            // Beserta info section-nya untuk deteksi week number
            // --------------------------------------------------------
            $sql = "SELECT qa.id, qa.sumgrades, q.grade AS maxgrade,
                           q.sumgrades AS quiz_maxsumgrades,
                           q.name AS quizname, qa.timefinish, q.id AS quizid,
                           cs.section AS secnum
                    FROM {quiz_attempts} qa
                    JOIN {quiz} q ON qa.quiz = q.id
                    JOIN {course_modules} cm
                        ON cm.instance = q.id
                        AND cm.course = q.course
                        AND cm.module = (SELECT id FROM {modules} WHERE name = 'quiz')
                    JOIN {course_sections} cs ON cs.id = cm.section
                    WHERE q.course = :courseid
                      AND qa.userid = :userid
                      AND qa.state = 'finished'
                    ORDER BY qa.timefinish DESC
                    LIMIT 1";

            $result = $DB->get_record_sql($sql, ['courseid' => $courseid, 'userid' => $USER->id]);

            if ($result) {
                // Menghitung persentase skor berdasarkan jumlah soal / total nilai quiz yang dikerjakan
                $maxmarks = (!empty($result->quiz_maxsumgrades) && $result->quiz_maxsumgrades > 0)
                    ? (float) $result->quiz_maxsumgrades
                    : ((!empty($result->maxgrade) && $result->maxgrade > 0) ? (float) $result->maxgrade : 100);

                $rawScore     = (float) ($result->sumgrades ?? 0);
                $userScore    = round(($rawScore / $maxmarks) * 100);
                $quizName     = $result->quizname;
                $lastQuizTime = $result->timefinish;
                $weekNum      = intval($result->secnum); // section number = week number
                $quizMarksStr = (round($rawScore, 1) == round($rawScore) ? round($rawScore) : round($rawScore, 1)) . '/' . 
                                (round($maxmarks, 1) == round($maxmarks) ? round($maxmarks) : round($maxmarks, 1)) . ' benar';
            }

            // Hitung total attempts user
            $attemptCount = $DB->count_records_sql(
                "SELECT COUNT(*) FROM {quiz_attempts} qa
                 JOIN {quiz} q ON qa.quiz = q.id
                 WHERE q.course = ? AND qa.userid = ? AND qa.state = 'finished'",
                [$courseid, $USER->id]
            );

        } catch (Exception $e) {
            $userScore = 0;
        }

        // ============================================================
        // 2. TENTUKAN LEVEL OTOMATIS DARI PENGATURAN MOODLE
        // ============================================================
        $primaryThreshold = (int) (get_config('block_adaptive_learning_ai', 'primary_threshold') ?: (get_config('block_adaptive_learning_ai', 'remedial_threshold') ?: 70));
        $expertThreshold  = (int) (get_config('block_adaptive_learning_ai', 'expert_threshold') ?: (get_config('block_adaptive_learning_ai', 'advanced_threshold') ?: 85));

        // Evaluasi performa kognitif gabungan (Kuis + AICode) jika ada
        $prevWeek = max(1, $weekNum - 1);
        $sectionPerf = \block_adaptive_learning_ai\path_manager::calculate_section_performance($courseid, (int)$USER->id, $prevWeek);
        if ($sectionPerf !== null && isset($sectionPerf['composite'])) {
            $userScore = round($sectionPerf['composite']);
        }

        $level        = 'INTERMEDIATE';
        $levelText    = 'Menengah';
        $statusClass  = 'intermediate';
        $scoreColor   = '#f59e0b';
        $levelColor   = '#fcd34d';
        $progressColor = '#f59e0b';
        $greetingMsg  = 'Pilih materi untuk mulai belajar!';
        $nextTarget   = $expertThreshold;
        $remedialNote = '';

        if ($userScore === 0 && $attemptCount === 0) {
            $level         = 'NODATA';
            $levelText     = 'Belum Mulai';
            $statusClass   = 'nodata';
            $scoreColor    = '#94a3b8';
            $levelColor    = '#94a3b8';
            $progressColor = '#6366f1';
            $greetingMsg   = '📝 Selesaikan <b>Materi & Latihan Minggu 1</b> untuk memetakan level kognitif Anda!';
            $nextTarget    = $primaryThreshold;
        } elseif ($userScore < $primaryThreshold) {
            $level         = 'PRIMARY';
            $levelText     = 'Dasar (Primary)';
            $statusClass   = 'primary';
            $scoreColor    = '#3b82f6';
            $levelColor    = '#93c5fd';
            $progressColor = '#3b82f6';
            $greetingMsg   = '🔵 Skor ' . $userScore . '% → Jalur <b>Dasar (Primary)</b>: Fokus pada penguatan konsep fondasi & latihan terbimbing. Target: ' . $primaryThreshold . '%+!';
            $nextTarget    = $primaryThreshold;
            $remedialNote  = '💡 Anda berada di level Dasar. Pelajari materi fondasi dan gunakan latihan kode terbimbing untuk memperkuat pemahaman.';
        } elseif ($userScore < $expertThreshold) {
            $level         = 'INTERMEDIATE';
            $levelText     = 'Menengah (Intermediate)';
            $statusClass   = 'intermediate';
            $scoreColor    = '#f59e0b';
            $levelColor    = '#fcd34d';
            $progressColor = '#f59e0b';
            $greetingMsg   = '🟡 Skor ' . $userScore . '% → Jalur <b>Menengah (Intermediate)</b>: Pemahaman bagus! Target: ' . $expertThreshold . '%+ untuk jalur Mahir!';
            $nextTarget    = $expertThreshold;
        } else {
            $level         = 'EXPERT';
            $levelText     = 'Mahir (Expert)';
            $statusClass   = 'expert';
            $scoreColor    = '#10b981';
            $levelColor    = '#6ee7b7';
            $progressColor = '#10b981';
            $greetingMsg   = '🟢 Skor ' . $userScore . '% → Luar biasa! Anda berada di jalur <b>Mahir (Expert)</b>: Siap untuk tantangan kode tingkat lanjut!';
            $nextTarget    = 100;
        }

        // ============================================================
        // 3. STATISTIK KETERSEDIAAN MATERI & FILTERING ADAPTIF
        // ============================================================
        $coursecontext = context_course::instance($courseid);
        $isTeacher     = \block_adaptive_learning_ai\path_manager::is_teacher_or_admin($courseid);

        $availabilityUnlocked = 0;
        $availabilityLocked   = 0;

        if (!$isTeacher) {
            $hidden_info = \block_adaptive_learning_ai\path_manager::get_hidden_cmids_for_user($courseid, (int)$USER->id);
            $availabilityLocked = count($hidden_info);
            // Hitung total modul di course dikurangi modul yang dihide
            $modinfo = get_fast_modinfo($courseid);
            $totalMods = count($modinfo->cms);
            $availabilityUnlocked = max(0, $totalMods - $availabilityLocked);
        }

        // ============================================================
        // 4. REKOMENDASI BELAJAR ADAPTIF DARI GEMINI AI (OPENROUTER)
        // ============================================================
        $recData = alai_get_course_material_recommendations(
            $courseid,
            (int) $USER->id,
            $userScore,
            $level,
            $quizName,
            $weekNum,
            false
        );

        // ============================================================
        // 5. PROGRESS STATS
        // ============================================================
        $progressToNext  = ($nextTarget > 0 && $nextTarget > $userScore)
            ? round(($userScore / $nextTarget) * 100)
            : 100;
        $overallProgress = min(100, $userScore);

        $quizTimeStr = $lastQuizTime
            ? date('d M Y, H:i', $lastQuizTime)
            : 'Belum ada';

        // ============================================================
        // 6. LINK REPORT
        // ============================================================
        $wwwroot          = $CFG->wwwroot;
        $studentreportlink = '';
        if (!$isTeacher) {
            $studentreportlink = '<a class="alai-report-btn" href="'
                . $wwwroot . '/blocks/adaptive_learning_ai/reports/student_report.php?courseid=' . $courseid . '">'
                . '<i class="fas fa-chart-bar"></i> Lihat Laporan Saya</a>';
        } else {
            $studentreportlink = '<a class="alai-report-btn alai-report-btn-teacher" href="'
                . $wwwroot . '/blocks/adaptive_learning_ai/reports/teacher_report.php?courseid=' . $courseid . '">'
                . '<i class="fas fa-users"></i> Dashboard Guru</a>';
        }

        // ============================================================
        // 7. BUILD HTML & INJECT ADAPTIVE VIEW
        // ============================================================
        $pluginUrl = $wwwroot . '/blocks/adaptive_learning_ai';

        $adaptive_injection = block_adaptive_learning_ai_render_adaptive_view($courseid);

        $this->content->text = $adaptive_injection . $this->render_block_html(
            $courseid, $userScore, $level, $levelText, $statusClass,
            $scoreColor, $levelColor, $progressColor, $greetingMsg,
            $weekNum, $quizName, $quizTimeStr, $attemptCount,
            $overallProgress, $progressToNext, $nextTarget,
            $remedialNote, $recData,
            $availabilityUnlocked, $availabilityLocked,
            $studentreportlink, $pluginUrl, $isTeacher
        );

        $this->content->footer = '';
        return $this->content;
    }

    // ================================================================
    // APPLY ADAPTIVE AVAILABILITY — Unlock/Lock berdasarkan NAMA modul
    //
    // STRUKTUR KURSUS YANG DIDUKUNG:
    //   Section 0: Penganalan / Placement Quiz
    //   Section 1: Remidial  ← untuk nilai < 70 dari section 0
    //   Section 2: Minggu 1  ← dibuka jika section 0 ≥ 70, atau section 1 selesai
    //   Section 3: Remidial  ← untuk nilai < 70 dari section 2 (Minggu 1)
    //   Section 4: Minggu 2  ← dibuka jika section 2 ≥ 70, atau section 3 selesai
    //   ...dst
    //
    // LOGIKA PER SECTION:
    //   Jika section adalah REMIDIAL:
    //     → Buka jika section non-remidial TEPAT sebelumnya nilai < 70
    //     → Kunci jika nilai ≥ 70 (tidak perlu remedial)
    //
    //   Jika section adalah MINGGU BIASA:
    //     → Lihat nilai section non-remidial sebelumnya (prevMinggu)
    //     → Jika nilai ≥ 90  : buka level HIGH langsung
    //     → Jika nilai 70-89 : buka level MEDIUM langsung
    //     → Jika nilai < 70  : cek apakah section Remidial antara prevMinggu
    //                          dan section ini sudah dikerjakan
    //                          → Sudah: buka MEDIUM
    //                          → Belum: kunci semua
    //     → Jika belum ada nilai sama sekali: kunci semua
    // ================================================================
    private static function apply_adaptive_availability($courseid, $userid, $level, $weekNum, $DB, $CFG) {
        $unlocked = 0;
        $locked   = 0;

        try {
            // ────────────────────────────────────────────────────────
            // STEP 1: Ambil semua section beserta namanya
            // ────────────────────────────────────────────────────────
            $allSections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC');

            // ────────────────────────────────────────────────────────
            // STEP 2: Klasifikasikan setiap section:
            //   'remedial' → namanya mengandung remidial/remedial
            //   'minggu'   → section konten biasa (Week/Minggu)
            //   'placement'→ section 0
            // ────────────────────────────────────────────────────────
            $sectionTypes = []; // secNum => 'placement'|'remedial'|'minggu'
            foreach ($allSections as $sec) {
                $num  = intval($sec->section);
                $name = strtolower(trim($sec->name ?? ''));
                if ($num === 0) {
                    $sectionTypes[$num] = 'placement';
                } elseif (strpos($name, 'remidial') !== false || strpos($name, 'remedial') !== false) {
                    $sectionTypes[$num] = 'remedial';
                } else {
                    $sectionTypes[$num] = 'minggu';
                }
            }

            // ────────────────────────────────────────────────────────
            // STEP 3: Bangun score map hanya untuk section NON-remedial
            // (quiz Low/Medium/High hasilnya masuk ke score section minggu itu)
            // ────────────────────────────────────────────────────────
            $sectionScoreMap = self::build_section_score_map($courseid, $userid, $DB);

            // ────────────────────────────────────────────────────────
            // STEP 4: Buat mapping: setiap section Remidial → secNum
            // section Minggu mana yang jadi "sumber nilainya"
            // (yaitu section Minggu non-remedial tepat sebelumnya)
            //
            // Contoh urutan: 0(P), 1(R), 2(M1), 3(R), 4(M2), 5(R), 6(M3)
            //   Section 1 Remidial ← sumber: section 0 (Placement)
            //   Section 3 Remidial ← sumber: section 2 (Minggu 1)
            //   Section 5 Remidial ← sumber: section 4 (Minggu 2)
            // ────────────────────────────────────────────────────────
            $remedialSourceMap = []; // remedialSecNum => sourceMingguSecNum
            $secNums = array_keys($sectionTypes);
            sort($secNums);

            foreach ($secNums as $sn) {
                if ($sectionTypes[$sn] !== 'remedial') continue;
                // Cari section non-remedial tepat sebelum section ini
                for ($prev = $sn - 1; $prev >= 0; $prev--) {
                    if (isset($sectionTypes[$prev]) && $sectionTypes[$prev] !== 'remedial') {
                        $remedialSourceMap[$sn] = $prev;
                        break;
                    }
                }
            }

            // ────────────────────────────────────────────────────────
            // STEP 5: Untuk setiap section Minggu, cari Remidial
            // yang ada di antara section sebelumnya dan section ini
            // ────────────────────────────────────────────────────────
            // mingguSecNum => remedialSecNum (atau null kalau tidak ada)
            $mingguToRemedial = [];
            foreach ($secNums as $sn) {
                if ($sectionTypes[$sn] !== 'minggu' && $sectionTypes[$sn] !== 'placement') continue;
                // Cari section Remidial yang sumbernya adalah section ini
                foreach ($remedialSourceMap as $rSec => $srcSec) {
                    if ($srcSec === $sn) {
                        $mingguToRemedial[$sn] = $rSec;
                        break;
                    }
                }
            }

            // ────────────────────────────────────────────────────────
            // STEP 6: Proses tiap section
            // ────────────────────────────────────────────────────────
            foreach ($allSections as $section) {
                $secNum  = intval($section->section);
                $secType = $sectionTypes[$secNum] ?? 'minggu';

                // Section 0 = Placement: selalu terbuka, skip
                if ($secType === 'placement') continue;

                if (empty($section->sequence)) continue;
                $cmIds = array_filter(array_map('intval', explode(',', $section->sequence)));

                // ── SECTION REMIDIAL ──────────────────────────────
                if ($secType === 'remedial') {
                    $sourceSec   = $remedialSourceMap[$secNum] ?? null;
                    $sourceScore = ($sourceSec !== null) ? ($sectionScoreMap[$sourceSec] ?? null) : null;

                    // Buka Remidial jika nilai section sumber < 70
                    // Kunci jika nilai ≥ 70 (tidak perlu remedial) atau belum ada nilai
                    $remedialNeeded = ($sourceScore !== null && $sourceScore < 70);

                    foreach ($cmIds as $cmId) {
                        if ($cmId <= 0) continue;
                        $remedialNeeded ? $unlocked++ : $locked++;
                    }
                    continue;
                }

                // ── SECTION MINGGU BIASA ──────────────────────────
                $prevMingguSec = null;
                for ($prev = $secNum - 1; $prev >= 0; $prev--) {
                    if (isset($sectionTypes[$prev]) && $sectionTypes[$prev] !== 'remedial') {
                        $prevMingguSec = $prev;
                        break;
                    }
                }

                $prevScore      = ($prevMingguSec !== null) ? ($sectionScoreMap[$prevMingguSec] ?? null) : null;
                $effectiveLevel = null;

                if ($prevScore !== null) {
                    if ($prevScore >= 90) {
                        $effectiveLevel = 'HIGH';
                    } elseif ($prevScore >= 70) {
                        $effectiveLevel = 'MEDIUM';
                    } else {
                        $remedialBetween = $mingguToRemedial[$prevMingguSec] ?? null;
                        $remedialScore   = ($remedialBetween !== null)
                            ? ($sectionScoreMap[$remedialBetween] ?? null)
                            : null;

                        if ($remedialScore !== null) {
                            if ($remedialScore >= 90) {
                                $effectiveLevel = 'HIGH';
                            } elseif ($remedialScore >= 70) {
                                $effectiveLevel = 'MEDIUM';
                            } else {
                                $effectiveLevel = 'LOW';
                            }
                        } else {
                            $effectiveLevel = null;
                        }
                    }
                }

                // Hitung status rekomendasi (Aman: TIDAK mengubah DB course_modules)
                foreach ($cmIds as $cmId) {
                    if ($cmId <= 0) continue;

                    $cm = $DB->get_record('course_modules', ['id' => $cmId], 'id,module,instance,visible');
                    if (!$cm) continue;

                    $modType  = $DB->get_field('modules', 'name', ['id' => $cm->module]);
                    $modTitle = self::get_module_title($modType, $cm->instance, $DB);
                    $modTag   = self::detect_level_tag($modTitle);

                    if ($effectiveLevel === null) {
                        if ($modTag !== null) {
                            $locked++;
                        } else {
                            $unlocked++;
                        }
                    } else {
                        if ($modTag === null || $modTag === $effectiveLevel) {
                            $unlocked++;
                        } else {
                            $locked++;
                        }
                    }
                }
            }

        } catch (Exception $e) {
            // Silent fail — jangan crash halaman
        }

        return [$unlocked, $locked];
    }

    // ================================================================
    // BUILD SECTION SCORE MAP
    // Kembalikan array: [section_number => best_score%]
    // Berdasarkan nilai quiz terbaik (tertinggi) yang selesai per section
    // Mencakup semua quiz di section (Low, Medium, High, Remedial)
    // ================================================================
    private static function build_section_score_map($courseid, $userid, $DB) {
        $map = [];

        $sql = "SELECT q.id AS quizid, q.name AS quizname,
                       cs.section AS secnum,
                       qa.sumgrades, q.grade AS maxgrade,
                       q.sumgrades AS quiz_maxsumgrades,
                       qa.timefinish
                FROM {quiz} q
                JOIN {course_modules} cm ON cm.instance = q.id
                    AND cm.module = (SELECT id FROM {modules} WHERE name = 'quiz')
                    AND cm.course = :courseid
                JOIN {course_sections} cs ON cs.id = cm.section
                JOIN {quiz_attempts} qa ON qa.quiz = q.id
                    AND qa.userid = :userid
                    AND qa.state = 'finished'
                ORDER BY cs.section ASC, qa.timefinish DESC";

        $rows = $DB->get_records_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);

        foreach ($rows as $r) {
            $secNum   = intval($r->secnum);
            $maxmarks = (!empty($r->quiz_maxsumgrades) && $r->quiz_maxsumgrades > 0)
                ? (float) $r->quiz_maxsumgrades
                : ((!empty($r->maxgrade) && $r->maxgrade > 0) ? (float) $r->maxgrade : 100);
            $score    = round(((float) $r->sumgrades / $maxmarks) * 100);

            // Simpan nilai TERTINGGI per section (siswa mungkin retake)
            if (!isset($map[$secNum]) || $score > $map[$secNum]) {
                $map[$secNum] = $score;
            }
        }

        return $map;
    }

    // ================================================================
    // BUILD SECTION LEVEL MAP (wrapper — dipakai komponen lain)
    // Kembalikan array: [section_number => 'LOW'|'MEDIUM'|'HIGH']
    // ================================================================
    private static function build_section_level_map($courseid, $userid, $DB) {
        $scoreMap = self::build_section_score_map($courseid, $userid, $DB);
        $levelMap = [];
        foreach ($scoreMap as $secNum => $score) {
            if ($score < 70)     $levelMap[$secNum] = 'LOW';
            elseif ($score < 90) $levelMap[$secNum] = 'MEDIUM';
            else                 $levelMap[$secNum] = 'HIGH';
        }
        return $levelMap;
    }

    // ================================================================
    // GET MODULE TITLE — ambil nama/judul modul dari tabelnya
    // ================================================================
    private static function get_module_title($modType, $instanceId, $DB) {
        $titleFields = [
            'quiz'     => 'name',
            'page'     => 'name',
            'resource' => 'name',
            'url'      => 'name',
            'folder'   => 'name',
            'label'    => 'name',
            'assign'   => 'name',
            'forum'    => 'name',
            'scorm'    => 'name',
            'hvp'      => 'name',
            'h5pactivity' => 'name',
        ];

        $field = $titleFields[$modType] ?? 'name';
        try {
            return $DB->get_field($modType, $field, ['id' => $instanceId]) ?: '';
        } catch (Exception $e) {
            return '';
        }
    }

    // ================================================================
    // DETECT LEVEL TAG — deteksi kata Primary/Intermediate/Expert dalam nama modul
    // Return: 'PRIMARY' | 'INTERMEDIATE' | 'EXPERT' | null (modul umum)
    // ================================================================
    private static function detect_level_tag($title) {
        return \block_adaptive_learning_ai\path_manager::detect_module_level($title);
    }

    // ================================================================
    // GEMINI API — Get AI Recommendation
    // ================================================================
    private static function get_gemini_recommendation($apiKey, $score, $level, $quizName, $courseid) {
        $prompt = "Kamu adalah AI tutor adaptif di Moodle. Seorang siswa mendapat nilai quiz '$quizName' sebesar {$score}% dengan level {$level}.\n";

        if ($level === 'LOW') {
            $prompt .= "Siswa perlu remedial. Berikan: 1) Penyebab nilai rendah, 2) 3 tips belajar spesifik, 3) Motivasi singkat. ";
        } elseif ($level === 'MEDIUM') {
            $prompt .= "Siswa di level menengah. Berikan: 1) Topik yang perlu diperkuat, 2) 2 strategi naik ke level High, 3) Tantangan belajar. ";
        } else {
            $prompt .= "Siswa di level advanced! Berikan: 1) Pujian spesifik, 2) Tantangan lebih lanjut, 3) Proyek pengayaan. ";
        }

        $prompt .= "Jawab dalam 3-4 kalimat singkat, bahasa Indonesia, format HTML dengan <b> untuk penekanan.";

        $url  = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
        $data = [
            "contents" => [["parts" => [["text" => $prompt]]]],
            "generationConfig" => ["maxOutputTokens" => 200, "temperature" => 0.7]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                return $result['candidates'][0]['content']['parts'][0]['text'];
            }
        }

        return '';
    }

    /**
     * Render dynamic course topics (Option C: All activities and quizzes grouped by section)
     *
     * @param int $courseid
     * @param bool $isTeacher
     * @return string
     */
    private function render_course_topic_options($courseid, $isTeacher) {
        global $DB;

        $optionsHtml = '';

        try {
            $course = $DB->get_record('course', ['id' => $courseid]);
            if (!$course) {
                return '';
            }

            $modinfo = get_fast_modinfo($course);
            $sections = $modinfo->get_section_info_all();

            foreach ($sections as $secnum => $section) {
                if ($secnum === 0) {
                    continue; // Skip section 0 (announcements/general)
                }
                if (empty($modinfo->sections[$secnum])) {
                    continue;
                }

                $secName = $section->name ? trim($section->name) : ('Minggu / Topik ' . $secnum);

                $secOptions = '';
                // Ringkasan section option
                $secOptions .= '<option value="Ringkasan: ' . s($secName) . '">🎯 Ringkasan ' . s($secName) . '</option>';

                foreach ($modinfo->sections[$secnum] as $cmid) {
                    $cm = $modinfo->cms[$cmid];
                    if (!$cm->uservisible) {
                        continue;
                    }
                    // Filter materi khusus guru atau kunci jawaban untuk siswa
                    if (!$isTeacher && (stripos($cm->name, 'khusus guru') !== false || stripos($cm->name, 'kunci jawaban') !== false)) {
                        continue;
                    }

                    $icon = '📖 [Materi] ';
                    if ($cm->modname === 'quiz') {
                        $icon = '❓ [Kuis] ';
                    } elseif ($cm->modname === 'aicode' || $cm->modname === 'assign') {
                        $icon = '💻 [Praktik] ';
                    } elseif ($cm->modname === 'forum') {
                        $icon = '💬 [Diskusi] ';
                    }

                    $val = $secName . ' - ' . $cm->name;
                    $secOptions .= '<option value="' . s($val) . '">' . $icon . s($cm->name) . '</option>';
                }

                if (!empty($secOptions)) {
                    $optionsHtml .= '<optgroup label="📁 ' . s($secName) . '">' . $secOptions . '</optgroup>';
                }
            }
        } catch (Exception $e) {
            $optionsHtml = '';
        }

        // Fallback jika kursus belum memiliki section/modul
        if (empty($optionsHtml)) {
            $optionsHtml = '
            <optgroup label="📚 Topik Pembelajaran">
                <option value="Variabel & Tipe Data">📦 Variabel &amp; Tipe Data</option>
                <option value="Logika Percabangan If-Else">🔀 Percabangan If-Else</option>
                <option value="Perulangan Loop">🔁 Perulangan Loop</option>
                <option value="Fungsi dan Method">⚙️ Fungsi (Function)</option>
            </optgroup>';
        }

        return $optionsHtml;
    }

    // ================================================================
    // RENDER BLOCK HTML
    // ================================================================
    private function render_block_html(
        $courseid, $userScore, $level, $levelText, $statusClass,
        $scoreColor, $levelColor, $progressColor, $greetingMsg,
        $weekNum, $quizName, $quizTimeStr, $attemptCount,
        $overallProgress, $progressToNext, $nextTarget,
        $remedialNote, $recData,
        $availabilityUnlocked, $availabilityLocked,
        $studentreportlink, $pluginUrl, $isTeacher
    ) {
        global $CFG;

        $levelIcon = [
            'PRIMARY'      => '🌱',
            'INTERMEDIATE' => '⚡',
            'EXPERT'       => '👑',
            'LOW'          => '🌱',
            'MEDIUM'       => '⚡',
            'HIGH'         => '👑',
            'nodata'       => '⚪',
        ][$level] ?? '⚪';

        // Rekomendasi AI Gemini & Materi Kursus
        $adviceText = !empty($recData['advice']) ? $recData['advice'] : '';
        $recList    = !empty($recData['recommendations']) ? $recData['recommendations'] : [];

        $itemsHtml = '';
        if (!empty($recList)) {
            foreach ($recList as $item) {
                $purposeClass = 'alai-badge-core';
                $purposeText  = $item['purpose'] ?? 'Rekomendasi';
                if (stripos($purposeText, 'perbaikan') !== false || stripos($purposeText, 'remedial') !== false) {
                    $purposeClass = 'alai-badge-remedial';
                } elseif (stripos($purposeText, 'lanjutan') !== false || stripos($purposeText, 'pengayaan') !== false || stripos($purposeText, 'tantangan') !== false) {
                    $purposeClass = 'alai-badge-advanced';
                }

                $iconHtml = function_exists('alai_render_module_icon') 
                    ? alai_render_module_icon($item['type']) 
                    : '<span class="alai-type-icon alai-icon-page"><i class="fas fa-book-open"></i></span>';

                $itemsHtml .= '
                <div class="alai-rec-item">
                    <div class="alai-rec-item-icon">' . $iconHtml . '</div>
                    <div class="alai-rec-item-content">
                        <div class="alai-rec-item-meta">
                            <span class="alai-sec-tag">' . htmlspecialchars($item['section']) . '</span>
                            <span class="alai-purpose-tag ' . $purposeClass . '">' . htmlspecialchars($purposeText) . '</span>
                        </div>
                        <a href="' . s($item['url']) . '" class="alai-rec-item-title" title="Buka materi ' . htmlspecialchars($item['title']) . '">'
                            . htmlspecialchars($item['title']) .
                        '</a>
                        ' . (!empty($item['reason']) ? '<div class="alai-rec-item-reason"><i class="fas fa-info-circle"></i> ' . htmlspecialchars($item['reason']) . '</div>' : '') . '
                    </div>
                    <a href="' . s($item['url']) . '" class="alai-rec-item-action" title="Buka Materi">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>';
            }
        }

        $aiHtml = '
        <div class="alai-ai-card" id="alaiAiCard_' . $courseid . '">
            <div class="alai-ai-header">
                <div class="alai-ai-title">
                    <span class="alai-ai-icon-wrap"><i class="fas fa-robot"></i></span>
                    <span>Rekomendasi AI Gemini</span>
                </div>
                <div class="alai-ai-actions">
                    <span class="alai-model-badge"><i class="fas fa-sparkles"></i> AI Gemini</span>
                    <button type="button" class="alai-refresh-btn" id="alaiRefreshBtn_' . $courseid . '" onclick="alaiRefreshRec_' . $courseid . '()" title="Perbarui rekomendasi materi AI">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="alai-ai-body" id="alaiAiBody_' . $courseid . '">
                ' . (!empty($itemsHtml) ? '<div class="alai-rec-list-header"><i class="fas fa-book-reader"></i> Materi yang Disarankan:</div><div class="alai-rec-list">' . $itemsHtml . '</div>' : '<div class="alai-rec-empty">Tidak ada materi rekomendasi baru saat ini.</div>') . '
            </div>
        </div>';

        $remedialHtml = '';
        if ($remedialNote) {
            $remedialHtml = '<div class="alai-remedial-note">
                <i class="fas fa-exclamation-circle"></i> ' . $remedialNote . '
            </div>';
        }

        $availHtml = '';
        if ($availabilityUnlocked > 0 || $availabilityLocked > 0) {
            $availHtml = '<div class="alai-avail-info">
                <span><i class="fas fa-unlock-alt" style="color:#059669"></i> ' . $availabilityUnlocked . ' materi dibuka</span>
                <span><i class="fas fa-lock" style="color:#dc2626"></i> ' . $availabilityLocked . ' dikunci</span>
            </div>';
        }

        $quizMarksStr = '';
        if (!empty($quizName) && $quizName !== '-') {
            $quizMarksStr = $userScore . '%';
        }

        return '
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" crossorigin="anonymous">
<style>
/* ===== ALAI NATIVE MOODLE BOOST DESIGN ===== */
.block_adaptive_learning_ai .card-body { padding: 0 !important; }
.block_adaptive_learning_ai .card { background: transparent !important; border: none !important; box-shadow: none !important; }
.block_adaptive_learning_ai.block { padding: 0; background: transparent; border: none; }

.alai-wrap * { box-sizing: border-box; }
.alai-wrap {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
    overflow: hidden;
    width: 100%;
    position: relative;
    container-type: inline-size;
    container-name: alaicard;
}

/* HEADER */
.alai-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 16px 14px;
}
.alai-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.alai-logo {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    background: linear-gradient(135deg, #0f6cbf 0%, #2563eb 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 10px rgba(15, 108, 191, 0.22);
}
.alai-brand-text {
    min-width: 0;
    flex: 1;
}
.alai-brand-text h3 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.25;
}
.alai-brand-text p {
    margin: 2px 0 0;
    font-size: 0.68rem;
    color: #64748b;
    font-weight: 500;
}
.alai-week-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 600;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    flex-shrink: 0;
    white-space: nowrap;
}

/* METRICS */
.alai-metrics {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 12px;
}
.alai-metric {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 8px;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    min-height: 64px;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.alai-metric:hover {
    border-color: #cbd5e1;
    box-shadow: 0 3px 8px rgba(0,0,0,0.03);
}
.alai-metric-val {
    font-size: 1.45rem;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 4px;
    white-space: nowrap;
}
.alai-metric-level-val {
    font-size: 0.95rem;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 4px;
    white-space: nowrap;
}
.alai-metric-lbl {
    font-size: 0.62rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}
.alai-metric-primary .alai-metric-val, .alai-metric-primary .alai-metric-level-val { color: #2563eb; }
.alai-metric-intermediate .alai-metric-val, .alai-metric-intermediate .alai-metric-level-val { color: #d97706; }
.alai-metric-expert .alai-metric-val, .alai-metric-expert .alai-metric-level-val { color: #059669; }
.alai-metric-remedial .alai-metric-val, .alai-metric-remedial .alai-metric-level-val { color: #2563eb; }
.alai-metric-standard .alai-metric-val, .alai-metric-standard .alai-metric-level-val { color: #d97706; }
.alai-metric-advanced .alai-metric-val, .alai-metric-advanced .alai-metric-level-val { color: #059669; }
.alai-metric-nodata .alai-metric-val, .alai-metric-nodata .alai-metric-level-val { color: #64748b; }

/* PROGRESS */
.alai-progress-wrap { margin-bottom: 10px; }
.alai-progress-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.68rem;
    color: #64748b;
    font-weight: 500;
    margin-bottom: 5px;
}
.alai-progress-track {
    height: 7px;
    background: #e2e8f0;
    border-radius: 6px;
    overflow: hidden;
}
.alai-progress-bar {
    height: 100%;
    border-radius: 6px;
    transition: width 0.8s ease-in-out;
}
.alai-progress-primary .alai-progress-bar { background: linear-gradient(90deg, #3b82f6, #2563eb); }
.alai-progress-intermediate .alai-progress-bar { background: linear-gradient(90deg, #f59e0b, #d97706); }
.alai-progress-expert .alai-progress-bar { background: linear-gradient(90deg, #10b981, #059669); }
.alai-progress-remedial .alai-progress-bar { background: linear-gradient(90deg, #3b82f6, #2563eb); }
.alai-progress-standard .alai-progress-bar { background: linear-gradient(90deg, #f59e0b, #d97706); }
.alai-progress-advanced .alai-progress-bar { background: linear-gradient(90deg, #10b981, #059669); }
.alai-progress-nodata .alai-progress-bar { background: #94a3b8; }

/* GREETING */
.alai-greeting {
    font-size: 0.74rem;
    line-height: 1.45;
    border-radius: 8px;
    padding: 8px 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-left: 3px solid #0f6cbf;
    color: #1e293b;
}

/* STATS ROW */
.alai-stats-row {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}
.alai-stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.66rem;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-weight: 500;
}
.alai-stat-chip i { font-size: 0.65rem; color: #0f6cbf; }

/* BODY */
.alai-body {
    padding: 14px 16px;
}

/* REMEDIAL NOTE & NOTICE */
.alai-adaptive-notice {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 8px;
    padding: 8px 10px;
    margin-bottom: 12px;
    font-size: 0.7rem;
    color: #166534;
    display: flex;
    gap: 8px;
    align-items: flex-start;
}
.alai-adaptive-notice i { color: #16a34a; margin-top: 2px; flex-shrink: 0; }

.alai-avail-info {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
    padding: 6px 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.68rem;
    font-weight: 600;
}

.alai-remedial-note {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 9px 11px;
    margin-bottom: 12px;
    font-size: 0.72rem;
    color: #991b1b;
    line-height: 1.45;
}

/* AI RECOMMENDATION CARD */
.alai-ai-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 12px;
}
.alai-ai-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e2e8f0;
}
.alai-ai-title {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 0.82rem;
    font-weight: 700;
    color: #0f172a;
}
.alai-ai-icon-wrap {
    width: 22px;
    height: 22px;
    background: #eff6ff;
    color: #1d4ed8;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
}
.alai-ai-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}
.alai-model-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.62rem;
    font-weight: 600;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    padding: 2px 7px;
    border-radius: 6px;
}
.alai-refresh-btn {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #64748b;
    border-radius: 6px;
    width: 24px;
    height: 24px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.68rem;
    cursor: pointer;
    transition: all 0.2s;
}
.alai-refresh-btn:hover {
    color: #0f6cbf;
    border-color: #0f6cbf;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
.alai-ai-body {
    font-size: 0.78rem;
    color: #334155;
    line-height: 1.55;
}
.alai-ai-text {
    margin-bottom: 12px;
    color: #334155;
}

/* COURSE MATERIAL RECOMMENDATION ITEMS */
.alai-rec-list-header {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #475569;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.alai-rec-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.alai-rec-item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s;
    text-decoration: none !important;
}
.alai-rec-item:hover {
    border-color: #93c5fd;
    box-shadow: 0 4px 12px rgba(15, 108, 191, 0.08);
    transform: translateY(-1px);
}
.alai-rec-item-icon {
    flex-shrink: 0;
}
.alai-type-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
}
.alai-icon-page   { background: #eff6ff; color: #1d4ed8; }
.alai-icon-quiz   { background: #fef3c7; color: #b45309; }
.alai-icon-code   { background: #ecfdf5; color: #047857; }
.alai-icon-assign { background: #f5f3ff; color: #6d28d9; }
.alai-icon-forum  { background: #fdf2f8; color: #be185d; }

.alai-rec-item-content {
    flex: 1;
    min-width: 0;
}
.alai-rec-item-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 2px;
}
.alai-sec-tag {
    font-size: 0.6rem;
    font-weight: 600;
    color: #64748b;
}
.alai-purpose-tag {
    display: inline-block;
    font-size: 0.58rem;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.alai-badge-remedial { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.alai-badge-core     { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.alai-badge-advanced { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }

.alai-rec-item-title {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #0f172a !important;
    line-height: 1.35;
    text-decoration: none !important;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.alai-rec-item:hover .alai-rec-item-title {
    color: #0f6cbf !important;
}
.alai-rec-item-reason {
    font-size: 0.68rem;
    color: #64748b;
    margin-top: 2px;
    line-height: 1.3;
}
.alai-rec-item-action {
    flex-shrink: 0;
    width: 26px;
    height: 26px;
    border-radius: 6px;
    background: #f1f5f9;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    text-decoration: none !important;
    transition: all 0.2s;
}
.alai-rec-item:hover .alai-rec-item-action {
    background: #0f6cbf;
    color: #ffffff;
}

/* FOOTER & BUTTON */
.alai-footer {
    padding: 0 16px 16px;
}
.alai-report-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 10px 14px;
    border-radius: 8px;
    background: #0f6cbf;
    border: 1px solid #0f6cbf;
    color: #ffffff !important;
    text-decoration: none !important;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.2s;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.alai-report-btn:hover {
    background: #0d5ca3;
    border-color: #0d5ca3;
    color: #ffffff !important;
}
.alai-report-btn-teacher {
    background: #059669;
    border-color: #059669;
}
.alai-report-btn-teacher:hover {
    background: #047857;
    border-color: #047857;
}

/* RESPONSIVENESS */
@container alaicard (max-width: 330px) {
    .alai-header { padding: 12px; }
    .alai-body { padding: 10px 12px; }
    .alai-footer { padding: 0 12px 12px; }
    .alai-metrics { gap: 6px; }
    .alai-metric { padding: 8px 4px; min-height: 58px; }
    .alai-metric-val { font-size: 1.45rem; }
    .alai-metric-level-val { font-size: 0.88rem; }
    .alai-rec-item { padding: 8px 10px; }
    .alai-type-icon { width: 28px; height: 28px; font-size: 0.75rem; }
}
@container alaicard (max-width: 260px) {
    .alai-metrics { grid-template-columns: 1fr; }
    .alai-week-badge { display: none; }
    .alai-stats-row { display: none; }
}
</style>

<div class="alai-wrap ' . $statusClass . '" id="alaiBlock_' . $courseid . '">

    <!-- HEADER -->
    <div class="alai-header">
        <div class="alai-brand">
            <div class="alai-logo">AI</div>
            <div class="alai-brand-text">
                <h3>Adaptive Learning AI</h3>
                <p>Cerdas · Adaptif · AI Gemini</p>
            </div>
            <div style="margin-left:auto">
                <span class="alai-week-badge"><i class="fas fa-calendar-week"></i> Minggu ' . $weekNum . '</span>
            </div>
        </div>

        <!-- METRICS -->
        <div class="alai-metrics">
            <div class="alai-metric alai-metric-' . $statusClass . '">
                <div class="alai-metric-val">' . $userScore . '%</div>
                <div class="alai-metric-lbl">Nilai Kuis</div>
            </div>
            <div class="alai-metric alai-metric-' . $statusClass . '">
                <div class="alai-metric-level-val">' . $levelText . '</div>
                <div class="alai-metric-lbl">Level Saat Ini</div>
            </div>
        </div>

        <!-- PROGRESS BAR -->
        <div class="alai-progress-wrap">
            <div class="alai-progress-labels">
                <span>Kemajuan ke ' . $nextTarget . '%</span>
                <span>' . $overallProgress . '%</span>
            </div>
            <div class="alai-progress-track alai-progress-' . $statusClass . '">
                <div class="alai-progress-bar" id="alaiProgressBar" style="width:' . $overallProgress . '%"></div>
            </div>
        </div>

        <!-- GREETING -->
        <div class="alai-greeting">' . $greetingMsg . '</div>
    </div>

    <!-- BODY -->
    <div class="alai-body">

        <!-- STATS CHIPS -->
        <div class="alai-stats-row">
            ' . ($quizMarksStr ? '<span class="alai-stat-chip" title="Nilai Kuis Terakhir"><i class="fas fa-check-circle" style="color:#059669"></i> ' . $quizMarksStr . '</span>' : '') . '
            <span class="alai-stat-chip"><i class="fas fa-clock"></i> ' . $quizTimeStr . '</span>
            <span class="alai-stat-chip"><i class="fas fa-redo"></i> ' . $attemptCount . ' percobaan</span>
            <span class="alai-stat-chip"><i class="fas fa-file-alt"></i> ' . htmlspecialchars(mb_substr($quizName, 0, 22)) . '</span>
        </div>

        <!-- ADAPTIVE AVAILABILITY INFO -->
        ' . ($availHtml ?: '<div class="alai-adaptive-notice">
            <i class="fas fa-magic"></i>
            <span>Sistem Pembelajaran Adaptif membuka materi otomatis sesuai capaian kuis Anda.</span>
        </div>') . '

        <!-- AI RECOMMENDATION & COURSE MATERIALS -->
        ' . $aiHtml . '

        <!-- REMEDIAL NOTE -->
        ' . $remedialHtml . '

    </div>

    <!-- FOOTER -->
    <div class="alai-footer">' . $studentreportlink . '</div>
</div>

<script>
(function() {
    var CID       = ' . $courseid . ';
    var PLUGINURL = "' . $pluginUrl . '";

    window["alaiRefreshRec_" + CID] = function() {
        var btn = document.getElementById("alaiRefreshBtn_" + CID);
        var body = document.getElementById("alaiAiBody_" + CID);
        if (!body) return;

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = \'<i class="fas fa-spinner fa-spin"></i>\';
        }

        var sesskey = (window.M && window.M.cfg && window.M.cfg.sesskey) ? window.M.cfg.sesskey : "";

        fetch(PLUGINURL + "/ajax_recommendation.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({
                sesskey: sesskey,
                courseid: CID,
                force: 1
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = \'<i class="fas fa-sync-alt"></i>\';
            }
            if (d && d.success && d.html) {
                body.innerHTML = d.html;
            } else {
                alert(d && d.error ? d.error : "Gagal memperbarui rekomendasi materi.");
            }
        })
        .catch(function(e) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = \'<i class="fas fa-sync-alt"></i>\';
            }
            alert("Koneksi gagal saat memperbarui rekomendasi.");
        });
    };
})();
</script>
';
    }
}