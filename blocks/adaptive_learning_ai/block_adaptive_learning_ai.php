<?php
defined('MOODLE_INTERNAL') || die();

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
        $levelText    = 'Intermediate';
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
            $levelText     = 'Primary';
            $statusClass   = 'primary';
            $scoreColor    = '#3b82f6';
            $levelColor    = '#93c5fd';
            $progressColor = '#3b82f6';
            $greetingMsg   = '🔵 Skor ' . $userScore . '% → Jalur <b>Primary</b>: Fokus pada penguatan konsep dasar & latihan terbimbing. Target: ' . $primaryThreshold . '%+!';
            $nextTarget    = $primaryThreshold;
            $remedialNote  = '💡 Anda berada di level Primary. Pelajari materi fondasi dan gunakan latihan kode terbimbing untuk memperkuat pemahaman.';
        } elseif ($userScore < $expertThreshold) {
            $level         = 'INTERMEDIATE';
            $levelText     = 'Intermediate';
            $statusClass   = 'intermediate';
            $scoreColor    = '#f59e0b';
            $levelColor    = '#fcd34d';
            $progressColor = '#f59e0b';
            $greetingMsg   = '🟡 Skor ' . $userScore . '% → Jalur <b>Intermediate</b>: Pemahaman bagus! Target: ' . $expertThreshold . '%+ untuk jalur Expert!';
            $nextTarget    = $expertThreshold;
        } else {
            $level         = 'EXPERT';
            $levelText     = 'Expert';
            $statusClass   = 'expert';
            $scoreColor    = '#10b981';
            $levelColor    = '#6ee7b7';
            $progressColor = '#10b981';
            $greetingMsg   = '🟢 Skor ' . $userScore . '% → Luar biasa! Anda berada di jalur <b>Expert</b>: Siap untuk tantangan kode tingkat lanjut!';
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
        // 4. REKOMENDASI BELAJAR ADAPTIF
        // ============================================================
        $aiRecommendation = '';
        if ($userScore === 0 && $attemptCount === 0) {
            $aiRecommendation = 'Selamat datang! Minggu 1 adalah fase pemetaan kemampuan dasar (baseline). Selesaikan materi dan kuis pertama untuk membuka jalur belajar adaptif Anda.';
        } elseif ($userScore < $primaryThreshold) {
            $aiRecommendation = 'Skor Anda (' . $userScore . '%) berada di jalur <b>Primary</b>. Fokuskan pemahaman pada sintaks dasar dan manfaatkan tutor kode AI di bawah untuk latihan.';
        } elseif ($userScore < $expertThreshold) {
            $aiRecommendation = 'Pemahaman Anda (' . $userScore . '%) berada di jalur <b>Intermediate</b>. Pertahankan konsistensi latihan untuk mencapai level Expert.';
        } else {
            $aiRecommendation = 'Prestasi istimewa! Skor Anda (' . $userScore . '%) mencapai level <b>Expert</b>. Anda siap mengeksplorasi studi kasus nyata dan tantangan algoritma kompleks.';
        }

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
            $remedialNote, $aiRecommendation,
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
        $remedialNote, $aiRecommendation,
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

        $levelBadgeColor = [
            'primary'      => 'background:rgba(59,130,246,0.15);color:#93c5fd;border:1px solid rgba(59,130,246,0.4)',
            'intermediate' => 'background:rgba(245,158,11,0.15);color:#fcd34d;border:1px solid rgba(245,158,11,0.4)',
            'expert'       => 'background:rgba(16,185,129,0.15);color:#6ee7b7;border:1px solid rgba(16,185,129,0.4)',
            'remedial'     => 'background:rgba(59,130,246,0.15);color:#93c5fd;border:1px solid rgba(59,130,246,0.4)',
            'standard'     => 'background:rgba(245,158,11,0.15);color:#fcd34d;border:1px solid rgba(245,158,11,0.4)',
            'advanced'     => 'background:rgba(16,185,129,0.15);color:#6ee7b7;border:1px solid rgba(16,185,129,0.4)',
            'nodata'       => 'background:rgba(148,163,184,0.15);color:#94a3b8;border:1px solid rgba(148,163,184,0.4)',
        ][$statusClass] ?? '';

        $aiHtml = '';
        if ($aiRecommendation) {
            $aiHtml = '<div class="alai-ai-card">
                <div class="alai-ai-header"><i class="fas fa-robot"></i> Rekomendasi AI Gemini</div>
                <div class="alai-ai-body">' . nl2br($aiRecommendation) . '</div>
            </div>';
        }

        $remedialHtml = '';
        if ($remedialNote) {
            $remedialHtml = '<div class="alai-remedial-note">
                <i class="fas fa-exclamation-circle"></i> ' . $remedialNote . '
            </div>';
        }

        $availHtml = '';
        if ($availabilityUnlocked > 0 || $availabilityLocked > 0) {
            $availHtml = '<div class="alai-avail-info">
                <span><i class="fas fa-unlock-alt" style="color:#10b981"></i> ' . $availabilityUnlocked . ' materi dibuka</span>
                <span><i class="fas fa-lock" style="color:#ef4444"></i> ' . $availabilityLocked . ' dikunci</span>
            </div>';
        }

        $wwwroot = $CFG->wwwroot;

        return '
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" crossorigin="anonymous">
<style>
/* ===== ALAI PREMIUM v2 — THEME COMPATIBILITY & RESPONSIVENESS ===== */
.block_adaptive_learning_ai .card-body{padding:0 !important}
.block_adaptive_learning_ai .card{background:transparent !important;border:none !important;box-shadow:none !important}
.block_adaptive_learning_ai.block{padding:0;background:transparent;border:none}

.alai-wrap *{box-sizing:border-box}
.alai-wrap,
.alai-wrap p,
.alai-wrap h1, .alai-wrap h2, .alai-wrap h3, .alai-wrap h4,
.alai-wrap span, .alai-wrap div, .alai-wrap button,
.alai-wrap input, .alai-wrap select, .alai-wrap option, .alai-wrap optgroup,
.alai-wrap a, .alai-wrap strong, .alai-wrap b {
    font-family:"Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
}
.alai-wrap .fas, .alai-wrap .far, .alai-wrap .fab, .alai-wrap .fa,
.alai-wrap i[class*="fa-"], .alai-wrap [class*="fa-"] {
    font-family:"Font Awesome 6 Free", "FontAwesome" !important;
    font-style:normal;
}
.alai-wrap{
    background:linear-gradient(160deg,#0f172a 0%,#1e3a5f 50%,#0f2044 100%);
    border-radius:18px;overflow:hidden;
    border:1px solid rgba(99,160,255,.18);
    box-shadow:0 15px 45px rgba(0,0,0,.45),0 0 0 1px rgba(255,255,255,.04);
    position:relative;
    width:100%;max-width:100%;
    container-type:inline-size;
    container-name:alaicard;
}
.alai-wrap::before{
    content:"";position:absolute;inset:0;
    background:radial-gradient(ellipse 80% 50% at 50% -10%,rgba(96,165,250,.18),transparent);
    pointer-events:none;z-index:0;
}

/* HEADER */
.alai-header{
    position:relative;z-index:1;
    background:linear-gradient(135deg,rgba(59,130,246,.22) 0%,rgba(96,165,250,.10) 100%);
    border-bottom:1px solid rgba(99,160,255,.15);
    padding:16px 16px 12px;
    backdrop-filter:blur(20px);
}
.alai-brand{display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:nowrap}
.alai-logo{
    width:42px;height:42px;flex-shrink:0;
    background:linear-gradient(135deg,#3b82f6,#60a5fa);
    border-radius:12px;display:flex;align-items:center;justify-content:center;
    font-size:.68rem;font-weight:900;color:#fff;letter-spacing:.05em;
    box-shadow:0 6px 16px rgba(59,130,246,.35);
}
.alai-brand-text{min-width:0;flex:1}
.alai-brand-text h3{
    margin:0;font-size:.95rem;font-weight:800;
    background:linear-gradient(90deg,#fff,#93c5fd);
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
    line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.alai-brand-text p{margin:2px 0 0;font-size:.58rem;color:rgba(255,255,255,.5);letter-spacing:.4px;text-transform:uppercase;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.alai-week-badge{
    display:inline-flex;align-items:center;gap:4px;
    padding:4px 8px;border-radius:8px;font-size:.58rem;font-weight:700;
    background:rgba(99,102,241,.15);color:#a5b4fc;border:1px solid rgba(99,102,241,.25);
    flex-shrink:0;white-space:nowrap;
}

/* METRICS GRID */
.alai-metrics{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px}
.alai-metric{
    background:rgba(255,255,255,.05);
    border:1px solid rgba(255,255,255,.08);
    border-radius:12px;padding:10px 12px;
    transition:transform .2s,box-shadow .2s;text-align:center;
    min-width:0;overflow:hidden;
}
.alai-metric:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.3)}
.alai-metric-val{font-size:1.35rem;font-weight:900;line-height:1;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.alai-metric-lbl{font-size:.55rem;color:rgba(255,255,255,.48);text-transform:uppercase;letter-spacing:.6px;font-weight:600;white-space:nowrap}
.alai-metric-primary      .alai-metric-val{color:#60a5fa}
.alai-metric-intermediate .alai-metric-val{color:#fbbf24}
.alai-metric-expert       .alai-metric-val{color:#34d399}
.alai-metric-remedial     .alai-metric-val{color:#60a5fa}
.alai-metric-standard     .alai-metric-val{color:#fbbf24}
.alai-metric-advanced     .alai-metric-val{color:#34d399}
.alai-metric-nodata       .alai-metric-val{color:#94a3b8}

/* PROGRESS BAR */
.alai-progress-wrap{margin-bottom:6px}
.alai-progress-labels{display:flex;justify-content:space-between;font-size:.58rem;color:rgba(255,255,255,.45);margin-bottom:3px}
.alai-progress-track{
    height:6px;background:rgba(255,255,255,.08);border-radius:4px;overflow:hidden;
    border:1px solid rgba(255,255,255,.05);
}
.alai-progress-bar{
    height:100%;border-radius:4px;
    transition:width 1.2s cubic-bezier(.4,0,.2,1);
}
.alai-progress-primary      .alai-progress-bar{background:linear-gradient(90deg,#3b82f6,#60a5fa)}
.alai-progress-intermediate .alai-progress-bar{background:linear-gradient(90deg,#f59e0b,#fbbf24)}
.alai-progress-expert       .alai-progress-bar{background:linear-gradient(90deg,#10b981,#34d399)}
.alai-progress-remedial     .alai-progress-bar{background:linear-gradient(90deg,#3b82f6,#60a5fa)}
.alai-progress-standard     .alai-progress-bar{background:linear-gradient(90deg,#f59e0b,#fbbf24)}
.alai-progress-advanced     .alai-progress-bar{background:linear-gradient(90deg,#10b981,#34d399)}
.alai-progress-nodata       .alai-progress-bar{background:linear-gradient(90deg,#6366f1,#818cf8)}

/* LEVEL BADGE & GREETING */
.alai-greeting{
    font-size:.74rem;color:rgba(255,255,255,.82);
    margin-top:6px;line-height:1.45;
    background:rgba(255,255,255,.04);border-radius:8px;padding:7px 10px;
    border-left:3px solid rgba(99,160,255,.5);word-break:break-word;
}

/* STATS ROW */
.alai-stats-row{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px}
.alai-stat-chip{
    display:inline-flex;align-items:center;gap:4px;
    padding:4px 8px;border-radius:6px;font-size:.62rem;
    background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);
    color:rgba(255,255,255,.65);max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}
.alai-stat-chip i{font-size:.58rem;color:#60a5fa;flex-shrink:0}

/* BODY */
.alai-body{position:relative;z-index:1;padding:14px}

/* AI RECOMMENDATION CARD */
.alai-ai-card{
    background:linear-gradient(135deg,rgba(59,130,246,.12),rgba(99,102,241,.08));
    border:1px solid rgba(99,160,255,.2);
    border-radius:12px;padding:12px;margin-bottom:10px;word-break:break-word;
}
.alai-ai-header{font-size:.7rem;font-weight:700;color:#93c5fd;margin-bottom:6px;display:flex;align-items:center;gap:6px}
.alai-ai-body{font-size:.76rem;color:rgba(255,255,255,.78);line-height:1.6}
.alai-ai-body b{color:#93c5fd}

/* REMEDIAL NOTE */
.alai-remedial-note{
    background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);
    border-radius:10px;padding:9px 11px;margin-bottom:10px;
    font-size:.7rem;color:#fca5a5;line-height:1.45;word-break:break-word;
}

/* AVAILABILITY INFO */
.alai-adaptive-notice{
    background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.25);
    border-radius:10px;padding:8px 10px;margin-bottom:10px;
    font-size:.66rem;color:rgba(255,255,255,.65);line-height:1.5;
    display:flex;gap:7px;align-items:flex-start;
}
.alai-adaptive-notice i{color:#818cf8;margin-top:2px;flex-shrink:0}

/* TOPICS SELECT */
.alai-topic-select{
    width:100%;padding:10px 12px;
    background:rgba(255,255,255,.07);
    border:1px solid rgba(99,160,255,.22);
    border-radius:10px;font-size:.76rem;font-weight:600;
    color:#f1f5f9;margin-bottom:8px;cursor:pointer;
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3E%3Cpath stroke=\'%2360a5fa\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'m6 8 4 4 4-4\'/%3E%3C/svg%3E");
    background-position:right 10px center;background-repeat:no-repeat;background-size:14px;
    transition:border-color .2s,box-shadow .2s;
    text-overflow:ellipsis;overflow:hidden;white-space:nowrap;
}
.alai-topic-select:focus{outline:none;border-color:#60a5fa;box-shadow:0 0 0 2px rgba(96,165,250,.18)}
.alai-topic-select optgroup{background:#0f2044;color:#93c5fd;font-weight:700;font-style:normal;padding:6px 8px}
.alai-topic-select option{background:#1e3a5f;color:#f8fafc;padding:6px 10px}

/* CHAT HEADER */
.alai-chat-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;padding:0 2px}
.alai-chat-title{font-size:.68rem;font-weight:600;color:rgba(255,255,255,.55);display:flex;align-items:center;gap:5px}
.alai-clear-btn{background:transparent;border:none;color:rgba(255,255,255,.45);font-size:.66rem;cursor:pointer;padding:2px 6px;border-radius:4px;transition:all .2s;display:flex;align-items:center;gap:4px}
.alai-clear-btn:hover{color:#fca5a5;background:rgba(239,68,68,.15)}

/* CHAT AREA */
.alai-chat{
    background:rgba(255,255,255,.04);border:1px solid rgba(99,160,255,.12);
    border-radius:12px;padding:12px;
    min-height:200px;max-height:clamp(220px,40vh,340px);
    overflow-y:auto;margin-bottom:10px;
    scrollbar-width:thin;scrollbar-color:rgba(96,165,250,.35) transparent;
}
.alai-chat::-webkit-scrollbar{width:4px}
.alai-chat::-webkit-scrollbar-thumb{background:rgba(96,165,250,.35);border-radius:2px}

/* MESSAGES */
.alai-msg{
    padding:10px 12px;border-radius:12px;font-size:.76rem;
    line-height:1.6;margin-bottom:8px;
    animation:msgIn .35s cubic-bezier(.25,.46,.45,.94);
    word-break:break-word;overflow-wrap:anywhere;
}
@keyframes msgIn{from{opacity:0;transform:translateY(8px) scale(.98)}to{opacity:1;transform:none}}
.alai-msg-user{
    background:linear-gradient(135deg,#3b82f6,#6366f1);
    color:#fff;margin-left:auto;max-width:90%;
    border:1px solid rgba(99,160,255,.3);
}
.alai-msg-ai{
    background:rgba(255,255,255,.06);color:rgba(255,255,255,.88);
    border:1px solid rgba(255,255,255,.08);
}
.alai-msg-ai strong{color:#93c5fd}
.alai-chat pre, .alai-code{
    background:rgba(15,23,42,.85);border:1px solid rgba(99,160,255,.18);
    border-radius:8px;padding:10px;font-family:"Fira Code","Monaco",monospace;
    font-size:.7rem;max-width:100%;overflow-x:auto;margin:6px 0;
    color:#e2e8f0;line-height:1.65;white-space:pre-wrap;word-break:break-word;
}
.alai-msg-loading{
    display:flex;gap:5px;padding:12px;align-items:center;
    background:rgba(255,255,255,.06);border-radius:12px;margin-bottom:8px;
}
.alai-dot{width:6px;height:6px;background:#60a5fa;border-radius:50%;animation:bounce .8s infinite}
.alai-dot:nth-child(2){animation-delay:.15s}
.alai-dot:nth-child(3){animation-delay:.3s}
@keyframes bounce{0%,80%,100%{transform:translateY(0)}40%{transform:translateY(-8px)}}

/* INPUT AREA */
.alai-input-row{display:flex;gap:6px;align-items:center}
.alai-input{
    flex:1;min-width:0;padding:10px 14px;
    background:rgba(255,255,255,.07);
    border:1px solid rgba(99,160,255,.2);
    border-radius:10px;font-size:.76rem;
    color:#f8fafc;transition:border-color .2s,box-shadow .2s;
}
.alai-input::placeholder{color:rgba(255,255,255,.35)}
.alai-input:focus{outline:none;border-color:#60a5fa;box-shadow:0 0 0 2px rgba(96,165,250,.15)}
.alai-send{
    background:linear-gradient(135deg,#3b82f6,#6366f1);
    color:#fff;border:none;padding:10px 14px;border-radius:10px;
    font-size:.76rem;font-weight:700;cursor:pointer;flex-shrink:0;
    transition:transform .2s,box-shadow .2s;
    display:flex;align-items:center;gap:5px;
    box-shadow:0 4px 12px rgba(59,130,246,.35);
}
.alai-send:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(59,130,246,.45)}

/* REPORT BTN & FOOTER */
.alai-footer{position:relative;z-index:1;padding:0 14px 14px}
.alai-report-btn{
    display:flex;align-items:center;justify-content:center;gap:7px;
    width:100%;padding:10px;border-radius:10px;
    background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.3);
    color:#93c5fd;text-decoration:none;font-size:.76rem;font-weight:600;
    transition:all .2s;
}
.alai-report-btn:hover{background:rgba(59,130,246,.25);color:#bfdbfe;text-decoration:none}
.alai-report-btn-teacher{background:rgba(16,185,129,.12);border-color:rgba(16,185,129,.3);color:#6ee7b7}
.alai-report-btn-teacher:hover{background:rgba(16,185,129,.22)}

/* CONTAINER QUERIES (Sidebar & Compact Region Responsiveness) */
@container alaicard (max-width: 330px) {
    .alai-header{padding:12px 12px 10px}
    .alai-body{padding:10px}
    .alai-footer{padding:0 10px 10px}
    .alai-brand{gap:8px;margin-bottom:10px}
    .alai-logo{width:36px;height:36px;font-size:.62rem;border-radius:10px}
    .alai-brand-text h3{font-size:.84rem}
    .alai-metrics{gap:6px}
    .alai-metric{padding:8px 4px}
    .alai-metric-val{font-size:1.15rem}
    .alai-metric-lbl{font-size:.5rem;letter-spacing:.3px}
    .alai-greeting{font-size:.7rem;padding:6px 8px}
    .alai-stats-row .alai-stat-chip:nth-child(n+3){display:none}
    .alai-chat{min-height:180px;max-height:280px;padding:10px}
    .alai-msg{padding:8px 10px;font-size:.73rem}
    .alai-input{padding:8px 10px;font-size:.73rem}
    .alai-send{padding:8px 12px;font-size:.73rem}
}
@container alaicard (max-width: 260px) {
    .alai-metrics{grid-template-columns:1fr}
    .alai-week-badge{display:none}
    .alai-stats-row{display:none}
}

/* VIEWPORT MEDIA QUERIES (Mobile Screen Responsiveness) */
@media (max-width: 576px) {
    .alai-wrap{border-radius:14px}
    .alai-header{padding:14px 12px 10px}
    .alai-body{padding:10px}
    .alai-footer{padding:0 10px 12px}
    .alai-chat{min-height:190px;max-height:290px}
}
</style>

<div class="alai-wrap ' . $statusClass . '" id="alaiBlock_' . $courseid . '">

    <!-- HEADER -->
    <div class="alai-header">
        <div class="alai-brand">
            <div class="alai-logo">AI</div>
            <div class="alai-brand-text">
                <h3>Adaptive Learning AI</h3>
                <p>Smart · Adaptive · Gemini Powered</p>
            </div>
            <div style="margin-left:auto">
                <span class="alai-week-badge"><i class="fas fa-calendar-week"></i> Week ' . $weekNum . '</span>
            </div>
        </div>

        <!-- METRICS -->
        <div class="alai-metrics">
            <div class="alai-metric alai-metric-' . $statusClass . '">
                <div class="alai-metric-val">' . $userScore . '%</div>
                <div class="alai-metric-lbl">Quiz Score</div>
            </div>
            <div class="alai-metric alai-metric-' . $statusClass . '">
                <div class="alai-metric-val" style="font-size:1.1rem">' . $levelIcon . ' ' . $levelText . '</div>
                <div class="alai-metric-lbl">Level Saat Ini</div>
            </div>
        </div>

        <!-- PROGRESS BAR -->
        <div class="alai-progress-wrap">
            <div class="alai-progress-labels">
                <span>Progress ke ' . $nextTarget . '%</span>
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
            ' . ($quizMarksStr ? '<span class="alai-stat-chip" title="Jawaban Benar / Total Soal"><i class="fas fa-check-circle" style="color:#34d399"></i> ' . $quizMarksStr . '</span>' : '') . '
            <span class="alai-stat-chip"><i class="fas fa-clock"></i> ' . $quizTimeStr . '</span>
            <span class="alai-stat-chip"><i class="fas fa-redo"></i> ' . $attemptCount . ' attempt</span>
            <span class="alai-stat-chip"><i class="fas fa-file-alt"></i> ' . htmlspecialchars(mb_substr($quizName, 0, 22)) . '</span>
        </div>

        <!-- ADAPTIVE AVAILABILITY INFO -->
        ' . ($availHtml ?: '<div class="alai-adaptive-notice">
            <i class="fas fa-magic"></i>
            <span>Sistem Adaptive membuka materi otomatis sesuai nilai quiz Anda. Selesaikan quiz untuk mengaktifkan.</span>
        </div>') . '

        <!-- AI RECOMMENDATION -->
        ' . $aiHtml . '

        <!-- REMEDIAL NOTE -->
        ' . $remedialHtml . '

        <!-- TOPIC SELECT DINAMIS KURSUS (Option C: Semua Materi & Kuis) -->
        <select class="alai-topic-select" id="alaiTopicSelect_' . $courseid . '" onchange="alaiSendTopic_' . $courseid . '(this.value); this.selectedIndex=0;">
            <option value="">🎯 Pilih Materi / Kuis untuk belajar dengan AI...</option>
            ' . $this->render_course_topic_options($courseid, $isTeacher) . '
        </select>

        <!-- CHAT HEADER & RESET BUTTON -->
        <div class="alai-chat-header">
            <span class="alai-chat-title"><i class="fas fa-comments"></i> Percakapan AI</span>
            <button type="button" class="alai-clear-btn" id="alaiClearBtn_' . $courseid . '" onclick="alaiClearChat_' . $courseid . '()" title="Reset dan bersihkan riwayat chat sesi ini">
                <i class="fas fa-trash-alt"></i> Reset
            </button>
        </div>

        <!-- CHAT AREA -->
        <div class="alai-chat" id="alaiChat_' . $courseid . '">
            <div class="alai-msg alai-msg-ai">
                <strong><i class="fas fa-robot"></i> Adaptive Learning AI</strong>
                <div style="margin-top:6px;font-size:.8rem;">
                    Level: <span style="color:' . $levelColor . ';font-weight:700;">' . $levelText . '</span> |
                    Score: <span style="color:' . $scoreColor . ';font-weight:700;">' . $userScore . '%</span>
                </div>
                <div style="margin-top:6px;color:rgba(255,255,255,.65);font-size:.77rem;">' . $greetingMsg . '</div>
            </div>
        </div>

        <!-- INPUT -->
        <div class="alai-input-row">
            <input type="text" class="alai-input" id="alaiInput_' . $courseid . '"
                placeholder="Tanya AI atau pilih topik..."
                onkeypress="if(event.key===\'Enter\') { alaiSendTopic_' . $courseid . '(this.value); this.value=\'\'; }">
            <button class="alai-send" onclick="var inp=document.getElementById(\'alaiInput_' . $courseid . '\'); if(inp && inp.value){ alaiSendTopic_' . $courseid . '(inp.value); inp.value=\'\'; }">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="alai-footer">' . $studentreportlink . '</div>
</div>

<script>
(function() {
    var CID         = ' . $courseid . ';
    var USERID      = ' . (int) $USER->id . ';
    var SCORE       = ' . $userScore . ';
    var LEVEL       = "' . $level . '";
    var SCLASS      = "' . $statusClass . '";
    var PLUGINURL   = "' . $pluginUrl . '";
    var WWWROOT     = "' . $CFG->wwwroot . '";
    var STORAGE_KEY = "alai_chat_c" + CID + "_u" + USERID;

    // Simpan pesan ke storage browser
    function saveChatMessage(role, content) {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            var history = raw ? JSON.parse(raw) : [];
            if (!Array.isArray(history)) history = [];
            history.push({ role: role, content: content, time: Date.now() });
            if (history.length > 25) {
                history = history.slice(-25);
            }
            localStorage.setItem(STORAGE_KEY, JSON.stringify(history));
        } catch (e) {}
    }

    // Muat riwayat chat saat halaman dibuka
    function loadChatHistory() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            var history = JSON.parse(raw);
            if (!Array.isArray(history) || history.length === 0) return;

            var now = Date.now();
            var valid = [];
            var chat = document.getElementById("alaiChat_" + CID);
            if (!chat) return;

            for (var i = 0; i < history.length; i++) {
                var item = history[i];
                // Pertahankan riwayat obrolan dalam jangka waktu 24 jam
                if (item && item.content && (!item.time || (now - item.time < 86400000))) {
                    valid.push(item);
                    var msgDiv = document.createElement("div");
                    if (item.role === "user") {
                        msgDiv.className = "alai-msg alai-msg-user";
                        var icon = document.createElement("i");
                        icon.className = "fas fa-user";
                        msgDiv.appendChild(icon);
                        msgDiv.appendChild(document.createTextNode(" " + item.content));
                    } else {
                        msgDiv.className = "alai-msg alai-msg-ai";
                        msgDiv.innerHTML = item.content;
                    }
                    chat.appendChild(msgDiv);
                }
            }

            if (valid.length > 0) {
                chat.scrollTop = chat.scrollHeight;
            }
        } catch (e) {}
    }

    // Panggil saat script dieksekusi
    loadChatHistory();

    // Fungsi reset riwayat chat
    window["alaiClearChat_" + CID] = function() {
        if (confirm("Reset dan bersihkan riwayat percakapan AI kursus ini?")) {
            try {
                localStorage.removeItem(STORAGE_KEY);
            } catch (e) {}
            var chat = document.getElementById("alaiChat_" + CID);
            if (chat) {
                var welcome = chat.querySelector(".alai-msg-ai");
                chat.innerHTML = "";
                if (welcome) {
                    chat.appendChild(welcome);
                }
            }
        }
    };

    window["alaiSendTopic_" + CID] = function(topic, isAuto) {
        if (!topic) return;
        var chat  = document.getElementById("alaiChat_" + CID);
        var input = document.getElementById("alaiInput_" + CID);

        if (!isAuto) {
            var userMsg       = document.createElement("div");
            userMsg.className = "alai-msg alai-msg-user";
            var userIcon      = document.createElement("i");
            userIcon.className = "fas fa-user";
            userMsg.appendChild(userIcon);
            userMsg.appendChild(document.createTextNode(" " + topic));
            chat.appendChild(userMsg);
            saveChatMessage("user", topic);
        }

        // Loading dots
        var loader       = document.createElement("div");
        loader.className = "alai-msg-loading";
        loader.innerHTML = "<div class=\"alai-dot\"></div><div class=\"alai-dot\"></div><div class=\"alai-dot\"></div>";
        chat.appendChild(loader);
        chat.scrollTop = chat.scrollHeight;

        // AJAX ke ajax_microlearning.php dengan sesskey CSRF
        var sesskey = (window.M && window.M.cfg && window.M.cfg.sesskey) ? window.M.cfg.sesskey : "";

        fetch(PLUGINURL + "/ajax_microlearning.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({
                sesskey: sesskey,
                courseid: CID,
                message: topic
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            loader.remove();
            var aiMsg       = document.createElement("div");
            aiMsg.className = "alai-msg alai-msg-ai";
            var content     = d.success ? formatAlaiResponse(d.reply) : "<span style=\"color:#f87171\">Error: " + (d.reply||"Unknown") + "</span>";
            aiMsg.innerHTML = content;
            chat.appendChild(aiMsg);
            chat.scrollTop = chat.scrollHeight;
            saveChatMessage("ai", content);
        })
        .catch(function(e) {
            loader.remove();
            var errMsg       = document.createElement("div");
            errMsg.className = "alai-msg alai-msg-ai";
            errMsg.innerHTML = "<span style=\"color:#f87171\"><i class=\"fas fa-exclamation-triangle\"></i> Gagal memuat respon AI. Coba lagi.</span>";
            chat.appendChild(errMsg);
        });
    };

    function formatAlaiResponse(text) {
        if (!text) return "";
        // Clean markdown ```html ... ``` wrapper if returned by LLM
        text = text.replace(/^```html\s*([\s\S]*?)\s*```$/gi, \'$1\');
        text = text.replace(/```html\s*([\s\S]*?)```/gi, \'$1\');
        // Format pre/code blocks
        text = text.replace(/```([\s\S]*?)```/g, \'<div class="alai-code">$1</div>\');
        // Bold
        text = text.replace(/\*\*([^*]+)\*\*/g, \'<strong>$1</strong>\');
        return text;
    }
})();
</script>
';
    }
}