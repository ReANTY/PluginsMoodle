<?php
/**
 * Seed script: Generate dummy data for 10 students in Minggu 1 (Section 208).
 *
 * Creates realistic attempt data for the Learning Analytics Dashboard.
 * Run: php cli/seed_minggu1.php
 *
 * @package    mod_aicode
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once(__DIR__ . '/../lib.php');

// ─── Configuration ────────────────────────────────────────────────────────────

$COURSE_ID  = 22;
$SECTION_ID = 208;
$ENROL_ID   = 61; // manual enrol instance

// Student user IDs (student1 → student10).
$STUDENT_IDS = [3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

// AICode activities in Minggu 1.
$AICODE_ACTIVITIES = [
    ['cmid' => 958, 'instance' => 326, 'name' => 'Praktik ML1.1 - Hello World'],
    ['cmid' => 961, 'instance' => 327, 'name' => 'Praktik ML1.2 - Script di HTML'],
    ['cmid' => 964, 'instance' => 328, 'name' => 'Praktik ML1.3 - Sintaks Dasar'],
    ['cmid' => 967, 'instance' => 329, 'name' => 'Praktik ML1.4 - Output JavaScript'],
    ['cmid' => 970, 'instance' => 330, 'name' => 'Weekly Assignment - Minggu 1'],
];

// Quiz activities in Minggu 1.
$QUIZ_ACTIVITIES = [
    ['cmid' => 959, 'instance' => 308, 'name' => 'Kuis ML1.1'],
    ['cmid' => 962, 'instance' => 309, 'name' => 'Kuis ML1.2'],
    ['cmid' => 965, 'instance' => 310, 'name' => 'Kuis ML1.3'],
    ['cmid' => 968, 'instance' => 311, 'name' => 'Kuis ML1.4'],
    ['cmid' => 969, 'instance' => 312, 'name' => 'Weekly Quiz Minggu 1'],
];

// Page activities in Minggu 1.
$PAGE_CMIDS = [957, 960, 963, 966];

// All CMIDs for completion marking.
$ALL_CMIDS = array_merge(
    $PAGE_CMIDS,
    array_column($AICODE_ACTIVITIES, 'cmid'),
    array_column($QUIZ_ACTIVITIES, 'cmid')
);

// ─── Student Profiles ─────────────────────────────────────────────────────────

$STUDENT_PROFILES = [
    3  => ['attempts' => 3, 'hints' => 2, 'grade' => 85,  'label' => 'Rata-rata'],
    4  => ['attempts' => 5, 'hints' => 3, 'grade' => 78,  'label' => 'Banyak coba'],
    5  => ['attempts' => 2, 'hints' => 1, 'grade' => 95,  'label' => 'Cepat paham'],
    6  => ['attempts' => 4, 'hints' => 4, 'grade' => 77,  'label' => 'Butuh bantuan'],
    7  => ['attempts' => 3, 'hints' => 2, 'grade' => 90,  'label' => 'Cukup baik'],
    8  => ['attempts' => 6, 'hints' => 3, 'grade' => 75,  'label' => 'Struggle'],
    9  => ['attempts' => 2, 'hints' => 1, 'grade' => 100, 'label' => 'Sangat mahir'],
    10 => ['attempts' => 4, 'hints' => 2, 'grade' => 82,  'label' => 'Stabil'],
    11 => ['attempts' => 5, 'hints' => 4, 'grade' => 76,  'label' => 'Perlu dampingan'],
    12 => ['attempts' => 3, 'hints' => 2, 'grade' => 88,  'label' => 'Cukup baik'],
];

// ─── Code Templates per Activity ──────────────────────────────────────────────

// Each activity has: wrong_codes (array of wrong attempts), correct_code, expected_output
$CODE_TEMPLATES = [
    326 => [ // ML1.1 - Hello World
        'wrong_codes' => [
            ['code' => "console.log(\"Hello\")", 'stdout' => 'Hello', 'stderr' => '', 'exit' => 0],
            ['code' => "console.log('hello world');", 'stdout' => 'hello world', 'stderr' => '', 'exit' => 0],
            ['code' => "consolee.log('Hello World')", 'stdout' => '', 'stderr' => "ReferenceError: consolee is not defined\n    at Object.<anonymous> (script.js:1:1)", 'exit' => 1],
            ['code' => "console.log('Hello World'", 'stdout' => '', 'stderr' => "SyntaxError: missing ) after argument list\n    at script.js:1:27", 'exit' => 1],
            ['code' => "Console.log('Hello World');", 'stdout' => '', 'stderr' => "ReferenceError: Console is not defined\n    at Object.<anonymous> (script.js:1:1)", 'exit' => 1],
        ],
        'correct_code' => "console.log('Hello World');",
        'correct_stdout' => 'Hello World',
    ],
    327 => [ // ML1.2 - Script di HTML
        'wrong_codes' => [
            ['code' => "console.log('program dimulai');", 'stdout' => 'program dimulai', 'stderr' => '', 'exit' => 0],
            ['code' => "console.log(Program dimulai);", 'stdout' => '', 'stderr' => "SyntaxError: missing ) after argument list\n    at script.js:1:20", 'exit' => 1],
            ['code' => "console.log('Program');", 'stdout' => 'Program', 'stderr' => '', 'exit' => 0],
            ['code' => "alert('Program dimulai');", 'stdout' => '', 'stderr' => "ReferenceError: alert is not defined\n    at Object.<anonymous> (script.js:1:1)", 'exit' => 1],
            ['code' => "console.log('Program dimulai')\nalert('Halo');", 'stdout' => 'Program dimulai', 'stderr' => "ReferenceError: alert is not defined\n    at Object.<anonymous> (script.js:2:1)", 'exit' => 1],
        ],
        'correct_code' => "console.log('Program dimulai');\n// alert hanya berjalan di browser\nconsole.log('Alert: Halo JavaScript!');",
        'correct_stdout' => "Program dimulai\nAlert: Halo JavaScript!",
    ],
    328 => [ // ML1.3 - Sintaks Dasar
        'wrong_codes' => [
            ['code' => "let nama = 'budi';\nlet Nama = 'BUDI';\nconsole.log(Nama);", 'stdout' => 'BUDI', 'stderr' => '', 'exit' => 0],
            ['code' => "let nama = 'budi'\nlet Nama = 'BUDI'\nconsole.log(name);", 'stdout' => '', 'stderr' => "ReferenceError: name is not defined\n    at Object.<anonymous> (script.js:3:13)", 'exit' => 1],
            ['code' => "let nama = budi;\nlet Nama = 'BUDI';\nconsole.log(nama);", 'stdout' => '', 'stderr' => "ReferenceError: budi is not defined\n    at Object.<anonymous> (script.js:1:12)", 'exit' => 1],
            ['code' => "let nama = 'budi';\nconsole.log(nama);", 'stdout' => 'budi', 'stderr' => '', 'exit' => 0],
        ],
        'correct_code' => "let nama = 'budi';\nlet Nama = 'BUDI';\nconsole.log(nama);\nconsole.log(Nama);",
        'correct_stdout' => "budi\nBUDI",
    ],
    329 => [ // ML1.4 - Output JavaScript
        'wrong_codes' => [
            ['code' => "console.log('Belajar JavaScript');", 'stdout' => 'Belajar JavaScript', 'stderr' => '', 'exit' => 0],
            ['code' => "console.log(Belajar JavaScript itu menyenangkan);", 'stdout' => '', 'stderr' => "SyntaxError: missing ) after argument list\n    at script.js:1:25", 'exit' => 1],
            ['code' => "console.log('belajar javascript itu menyenangkan');", 'stdout' => 'belajar javascript itu menyenangkan', 'stderr' => '', 'exit' => 0],
            ['code' => "console.log('Belajar JavaScript itu Menyenangkan');", 'stdout' => 'Belajar JavaScript itu Menyenangkan', 'stderr' => '', 'exit' => 0],
            ['code' => "console.log('Belajar JavaScript itu menyenangkan'", 'stdout' => '', 'stderr' => "SyntaxError: missing ) after argument list\n    at script.js:1:51", 'exit' => 1],
        ],
        'correct_code' => "console.log('Belajar JavaScript itu menyenangkan');",
        'correct_stdout' => 'Belajar JavaScript itu menyenangkan',
    ],
    330 => [ // Weekly Assignment
        'wrong_codes' => [
            ['code' => "console.log('Profil Saya');", 'stdout' => 'Profil Saya', 'stderr' => '', 'exit' => 0],
            ['code' => "console.log('=== PROFIL SAYA ==');\nconsole.log('Nama: Budi');", 'stdout' => "=== PROFIL SAYA ==\nNama: Budi", 'stderr' => '', 'exit' => 0],
            ['code' => "console.log('=== PROFIL SAYA ==='\nconsole.log('Nama: Budi');", 'stdout' => '', 'stderr' => "SyntaxError: missing ) after argument list\n    at script.js:1:34", 'exit' => 1],
            ['code' => "console.log('=== PROFIL SAYA ===');\nconsole.log('Nama: ');", 'stdout' => "=== PROFIL SAYA ===\nNama: ", 'stderr' => '', 'exit' => 0],
        ],
        'correct_code' => "console.log('=== PROFIL SAYA ===');\nconsole.log('Nama: Budi Santoso');\nconsole.log('Umur: 16 tahun');\nconsole.log('Hobi: Coding dan Gaming');",
        'correct_stdout' => "=== PROFIL SAYA ===\nNama: Budi Santoso\nUmur: 16 tahun\nHobi: Coding dan Gaming",
    ],
];

// ─── AI Feedback Templates ────────────────────────────────────────────────────

function make_ai_feedback(string $category, string $short, string $long, int $line = 1, string $snippet = '', float $confidence = 0.85): string {
    return json_encode([
        'status' => 'success',
        'diagnosis' => [
            'category' => $category,
            'message_short' => $short,
            'message_long' => $long,
            'confidence' => $confidence,
        ],
        'hints' => [
            ['hint' => 'Perhatikan penulisan nama fungsi dan method — JavaScript bersifat case-sensitive.'],
            ['hint' => 'Pastikan setiap tanda kurung buka memiliki pasangan tanda kurung tutup.'],
            ['hint' => 'Gunakan console.log() untuk menampilkan output di console.'],
        ],
        'suggested_fix' => [
            'explanation' => 'Perbaiki penulisan kode agar sesuai dengan sintaks JavaScript yang benar. Pastikan string diapit dengan tanda kutip dan setiap statement diakhiri semicolon.',
            'code_patch' => '',
        ],
        'location' => [
            'line' => $line,
            'column' => 1,
            'snippet' => $snippet,
        ],
        'recommended_materials' => [
            [
                'title' => 'MDN - JavaScript Guide',
                'url' => 'https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide',
                'reason' => 'Referensi lengkap sintaks JavaScript',
            ],
        ],
        'explainability' => 'Analisis dilakukan berdasarkan pola error umum pemula JavaScript: kesalahan penulisan nama method, lupa menutup tanda kurung, dan perbedaan huruf besar/kecil.',
    ], JSON_UNESCAPED_UNICODE);
}

$AI_FEEDBACK_TEMPLATES = [
    'syntax' => [
        ['short' => 'Tanda kurung tidak lengkap', 'long' => 'Kode memiliki tanda kurung yang tidak berpasangan. Setiap tanda kurung buka \'(\' harus memiliki pasangan tanda kurung tutup \')\'. Periksa baris yang ditandai error.', 'confidence' => 0.92],
        ['short' => 'String tidak ditutup', 'long' => 'Terdapat string literal yang tidak ditutup dengan tanda kutip. Pastikan setiap string memiliki tanda kutip pembuka dan penutup yang sesuai.', 'confidence' => 0.88],
    ],
    'runtime' => [
        ['short' => 'Variabel tidak terdefinisi', 'long' => 'Kode mencoba mengakses variabel yang belum dideklarasikan. Pastikan variabel sudah dideklarasikan dengan let, const, atau var sebelum digunakan.', 'confidence' => 0.90],
        ['short' => 'Fungsi tidak dikenali', 'long' => 'Pemanggilan fungsi menggunakan nama yang salah atau belum didefinisikan. JavaScript bersifat case-sensitive, jadi Console.log() berbeda dengan console.log().', 'confidence' => 0.87],
    ],
    'logic' => [
        ['short' => 'Output tidak sesuai yang diharapkan', 'long' => 'Program berjalan tanpa error, namun output yang dihasilkan tidak sesuai dengan yang diminta soal. Periksa kembali teks yang ditampilkan, termasuk penggunaan huruf besar/kecil.', 'confidence' => 0.78],
        ['short' => 'Variabel yang salah ditampilkan', 'long' => 'Program menampilkan variabel yang bukan dimaksud oleh soal. Perhatikan nama variabel dengan teliti — JavaScript membedakan huruf besar dan kecil (case-sensitive).', 'confidence' => 0.82],
    ],
];

// ─── Helper Functions ─────────────────────────────────────────────────────────

function seed_log(string $msg): void {
    echo "[SEED] $msg\n";
}

function random_time_offset(int $dayOffset, int $hourMin = 8, int $hourMax = 15): int {
    $base = mktime(
        rand($hourMin, $hourMax),
        rand(0, 59),
        rand(0, 59),
        (int)date('n'),
        (int)date('j') - $dayOffset
    );
    return $base;
}

// ─── Step 0: Clean existing data ──────────────────────────────────────────────

seed_log("Step 0: Cleaning existing data for students in Minggu 1 activities...");

$aicode_instance_ids = array_column($AICODE_ACTIVITIES, 'instance');
$quiz_instance_ids = array_column($QUIZ_ACTIVITIES, 'instance');

foreach ($STUDENT_IDS as $uid) {
    // Clean aicode_attempts.
    foreach ($aicode_instance_ids as $pid) {
        $existing = $DB->get_records('aicode_attempts', ['problemid' => $pid, 'userid' => $uid]);
        foreach ($existing as $att) {
            $DB->delete_records('aicode_teacher_overrides', ['attemptid' => $att->id]);
        }
        $DB->delete_records('aicode_attempts', ['problemid' => $pid, 'userid' => $uid]);
    }

    // Clean activity log.
    foreach ($AICODE_ACTIVITIES as $act) {
        $DB->delete_records('aicode_activity_log', [
            'id_aktivitas_aicode' => $act['instance'],
            'id_pengguna' => $uid,
        ]);
    }
}

seed_log("  Cleaned aicode_attempts, teacher_overrides, and activity_log.");

// ─── Step 1: Enroll Students ──────────────────────────────────────────────────

seed_log("Step 1: Enrolling students...");

$manualenrol = enrol_get_plugin('manual');
$enrolinstance = $DB->get_record('enrol', ['id' => $ENROL_ID], '*', MUST_EXIST);
$studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);

if (!$studentroleid) {
    die("ERROR: Student role not found!\n");
}

foreach ($STUDENT_IDS as $uid) {
    $user = $DB->get_record('user', ['id' => $uid]);
    if (!$user) {
        seed_log("  WARNING: User ID $uid not found, skipping.");
        continue;
    }

    // Check if already enrolled.
    $already = $DB->record_exists('user_enrolments', ['enrolid' => $ENROL_ID, 'userid' => $uid]);
    if ($already) {
        seed_log("  {$user->username} already enrolled.");
    } else {
        $manualenrol->enrol_user($enrolinstance, $uid, $studentroleid);
        seed_log("  Enrolled {$user->username} (ID: $uid).");
    }
}

// ─── Step 2: Generate AICode Attempts ─────────────────────────────────────────

seed_log("Step 2: Generating aicode attempts...");

$totalAttempts = 0;
$totalHints = 0;

foreach ($STUDENT_IDS as $uid) {
    $profile = $STUDENT_PROFILES[$uid];
    $user = $DB->get_record('user', ['id' => $uid], 'id, username, firstname, lastname');
    $fullname = $user->firstname . ' ' . $user->lastname;

    seed_log("  Processing {$user->username} ({$profile['label']}) — {$profile['attempts']} attempts/activity, {$profile['hints']} hints...");

    foreach ($AICODE_ACTIVITIES as $actIdx => $activity) {
        $instanceId = $activity['instance'];
        $cmid = $activity['cmid'];
        $template = $CODE_TEMPLATES[$instanceId];
        $numAttempts = $profile['attempts'];
        $numHints = $profile['hints'];

        // Spread across days: activity index determines the day offset.
        // ML1.1 → 7-6 days ago, ML1.2 → 5-4, ML1.3 → 3-2, ML1.4 → 2-1, Weekly → 1-0
        $dayOffsets = [
            0 => [7, 6],  // ML1.1
            1 => [5, 4],  // ML1.2
            2 => [3, 3],  // ML1.3
            3 => [2, 1],  // ML1.4
            4 => [1, 0],  // Weekly Assignment
        ];
        $dayRange = $dayOffsets[$actIdx];

        // Pick wrong code samples for this student.
        $wrongCodes = $template['wrong_codes'];
        shuffle($wrongCodes);
        $wrongCodesForStudent = array_slice($wrongCodes, 0, min($numAttempts - 1, count($wrongCodes)));

        // Generate attempts.
        $hintsUsedInActivity = 0;
        $runCount = 0;
        $submitCount = 0;

        for ($attemptNum = 0; $attemptNum < $numAttempts; $attemptNum++) {
            $isLastAttempt = ($attemptNum === $numAttempts - 1);

            // Determine timing.
            $dayOffset = rand($dayRange[0], $dayRange[1]);
            $baseTime = random_time_offset($dayOffset, 8 + $attemptNum, 9 + $attemptNum * 2);

            if ($isLastAttempt) {
                // Final attempt: correct code, submit to teacher.
                $code = $template['correct_code'];
                $stdout = $template['correct_stdout'];
                $stderr = '';
                $exitCode = 0;
                $teacherReview = true;
            } else {
                // Wrong attempt.
                $wrongIdx = min($attemptNum, count($wrongCodesForStudent) - 1);
                if ($wrongIdx >= 0 && $wrongIdx < count($wrongCodesForStudent)) {
                    $wrong = $wrongCodesForStudent[$wrongIdx];
                    $code = $wrong['code'];
                    $stdout = $wrong['stdout'];
                    $stderr = $wrong['stderr'];
                    $exitCode = $wrong['exit'];
                } else {
                    // Fallback: use first wrong code.
                    $wrong = $template['wrong_codes'][0];
                    $code = $wrong['code'];
                    $stdout = $wrong['stdout'];
                    $stderr = $wrong['stderr'];
                    $exitCode = $wrong['exit'];
                }
                $teacherReview = false;
            }

            $codeHash = hash('sha256', $code);

            // Build result_json.
            $resultData = [
                'exitCode' => $exitCode,
                'stdout' => $stdout,
                'stderr' => $stderr,
                'code' => $code,
                'language' => 'javascript',
            ];
            if ($teacherReview) {
                $resultData['teacher_review_requested'] = true;
                $resultData['console_output'] = $stdout;
            }

            // Determine if this attempt uses AI Hint.
            $useHintOnThisAttempt = false;
            $aiRequestedAt = null;
            $aiFeedbackJson = null;
            $usedHintsJson = null;

            // Use hints on non-final wrong attempts.
            if (!$isLastAttempt && $hintsUsedInActivity < $numHints && $exitCode !== 0) {
                $useHintOnThisAttempt = true;
                $hintsUsedInActivity++;
            }
            // Also use hint on some correct-but-wrong-output attempts.
            if (!$isLastAttempt && $hintsUsedInActivity < $numHints && $exitCode === 0 && rand(0, 1) === 1) {
                $useHintOnThisAttempt = true;
                $hintsUsedInActivity++;
            }

            if ($useHintOnThisAttempt) {
                $aiRequestedAt = $baseTime + rand(30, 120);
                $hintTimestamps = [];
                for ($h = 0; $h < rand(1, 2); $h++) {
                    $hintTimestamps[] = $aiRequestedAt + $h * rand(5, 15);
                }
                $usedHintsJson = json_encode($hintTimestamps);

                // Pick AI feedback based on error type.
                if ($exitCode !== 0) {
                    $category = ($stderr && strpos($stderr, 'SyntaxError') !== false) ? 'syntax' : 'runtime';
                } else {
                    $category = 'logic';
                }

                $templates = $AI_FEEDBACK_TEMPLATES[$category];
                $picked = $templates[array_rand($templates)];
                $snippet = explode("\n", $code)[0] ?? $code;
                $aiFeedbackJson = make_ai_feedback(
                    $category,
                    $picked['short'],
                    $picked['long'],
                    1,
                    $snippet,
                    $picked['confidence']
                );
                $totalHints++;
            }

            // Insert attempt.
            $attempt = new stdClass();
            $attempt->problemid = $instanceId;
            $attempt->userid = $uid;
            $attempt->code_hash = $codeHash;
            $attempt->is_anonymous = 0;
            $attempt->result_json = json_encode($resultData, JSON_UNESCAPED_UNICODE);
            $attempt->used_hints_json = $usedHintsJson;
            $attempt->ai_feedback_json = $aiFeedbackJson;
            $attempt->security_flags = json_encode(['safe' => true, 'blocked' => false, 'violations' => []]);
            $attempt->ai_requested_at = $aiRequestedAt;
            $attempt->teacher_review_requested = $teacherReview ? 1 : 0;
            $attempt->timecreated = $baseTime;

            $DB->insert_record('aicode_attempts', $attempt);
            $totalAttempts++;
            $runCount++;
            if ($teacherReview) $submitCount++;
        }

        // ── Insert Activity Log ───────────────────────────────────────────
        $logTime = random_time_offset($dayRange[1], 10, 16);

        $logEntry = new stdClass();
        $logEntry->id_kursus = $COURSE_ID;
        $logEntry->id_modul = $cmid;
        $logEntry->id_aktivitas_aicode = $instanceId;
        $logEntry->id_pengguna = $uid;
        $logEntry->kode_kejadian = 'submit_teacher';
        $logEntry->metadata_json = json_encode([
            'final_exit_code' => 0,
            'total_attempts' => $numAttempts,
            'used_ai_hint' => $hintsUsedInActivity > 0,
        ], JSON_UNESCAPED_UNICODE);
        $logEntry->nama_lengkap = $fullname;
        $logEntry->mode_aktivitas = ($instanceId === 330) ? 'exam' : 'training';
        $logEntry->jumlah_ai_hint = $hintsUsedInActivity;
        $logEntry->jumlah_run = $runCount;
        $logEntry->jumlah_kirim_guru = $submitCount;
        $logEntry->nilai_snapshot = (float)$profile['grade'];
        $logEntry->waktu_dicatat = $logTime;

        $DB->insert_record('aicode_activity_log', $logEntry);

        // Also insert individual event logs for AI hint requests.
        if ($hintsUsedInActivity > 0) {
            $hintLog = new stdClass();
            $hintLog->id_kursus = $COURSE_ID;
            $hintLog->id_modul = $cmid;
            $hintLog->id_aktivitas_aicode = $instanceId;
            $hintLog->id_pengguna = $uid;
            $hintLog->kode_kejadian = 'ai_hint_request';
            $hintLog->metadata_json = json_encode(['hint_count' => $hintsUsedInActivity]);
            $hintLog->nama_lengkap = $fullname;
            $hintLog->mode_aktivitas = ($instanceId === 330) ? 'exam' : 'training';
            $hintLog->jumlah_ai_hint = $hintsUsedInActivity;
            $hintLog->jumlah_run = 0;
            $hintLog->jumlah_kirim_guru = 0;
            $hintLog->nilai_snapshot = null;
            $hintLog->waktu_dicatat = $logTime - rand(300, 1800);

            $DB->insert_record('aicode_activity_log', $hintLog);
        }
    }
}

seed_log("  Total attempts inserted: $totalAttempts");
seed_log("  Total AI hint usages: $totalHints");

// ─── Step 3: Set AICode Grades via Gradebook ──────────────────────────────────

seed_log("Step 3: Setting AICode grades...");

foreach ($AICODE_ACTIVITIES as $activity) {
    $aicode = $DB->get_record('aicode', ['id' => $activity['instance']], '*', MUST_EXIST);
    $aicode->course = $COURSE_ID;
    $aicode = aicode_enrich_for_gradebook($aicode);

    // Ensure grade item exists.
    aicode_grade_item_update($aicode);

    foreach ($STUDENT_IDS as $uid) {
        $profile = $STUDENT_PROFILES[$uid];
        // Vary grade slightly per activity (+/- 5).
        $grade = max(75, min(100, $profile['grade'] + rand(-5, 5)));
        $result = aicode_set_user_grade($aicode, $uid, (float)$grade);
        if ($result === GRADE_UPDATE_OK) {
            // OK
        } else {
            seed_log("  WARNING: Failed to set grade for user $uid on activity {$activity['name']} (result: $result)");
        }
    }
    seed_log("  Grades set for {$activity['name']}");
}

// ─── Step 4: Set Quiz Grades via Gradebook ────────────────────────────────────

seed_log("Step 4: Setting Quiz grades...");

foreach ($QUIZ_ACTIVITIES as $quiz) {
    foreach ($STUDENT_IDS as $uid) {
        $profile = $STUDENT_PROFILES[$uid];
        // Quiz grades: vary slightly per quiz (+/- 8), keep in 75-100.
        $grade = max(75, min(100, $profile['grade'] + rand(-8, 8)));

        $gradeObj = new stdClass();
        $gradeObj->userid = $uid;
        $gradeObj->rawgrade = (float)$grade;
        $gradeObj->dategraded = time();
        $gradeObj->datesubmitted = time() - rand(3600, 86400 * 7);

        $result = grade_update(
            'mod/quiz',
            $COURSE_ID,
            'mod',
            'quiz',
            $quiz['instance'],
            0,
            [$uid => $gradeObj]
        );

        if ($result !== GRADE_UPDATE_OK) {
            seed_log("  WARNING: Failed to set quiz grade for user $uid on {$quiz['name']} (result: $result)");
        }
    }
    seed_log("  Grades set for {$quiz['name']}");
}

// Regrade.
grade_regrade_final_grades($COURSE_ID);
seed_log("  Final grades regraded.");

// ─── Step 5: Set Activity Completion ──────────────────────────────────────────

seed_log("Step 5: Setting activity completion...");

$course = $DB->get_record('course', ['id' => $COURSE_ID], '*', MUST_EXIST);

foreach ($ALL_CMIDS as $cmid) {
    $cm = get_coursemodule_from_id('', $cmid, $COURSE_ID, false, IGNORE_MISSING);
    if (!$cm) {
        seed_log("  WARNING: CM $cmid not found, skipping completion.");
        continue;
    }

    foreach ($STUDENT_IDS as $uid) {
        // Check if completion record already exists.
        $existing = $DB->get_record('course_modules_completion', [
            'coursemoduleid' => $cmid,
            'userid' => $uid,
        ]);

        if ($existing) {
            // Update to completed.
            $existing->completionstate = 1;
            $existing->timemodified = time();
            $DB->update_record('course_modules_completion', $existing);
        } else {
            // Insert completion record.
            $completion = new stdClass();
            $completion->coursemoduleid = $cmid;
            $completion->userid = $uid;
            $completion->completionstate = 1;
            $completion->viewed = 1;
            $completion->overrideby = null;
            $completion->timemodified = time();
            $DB->insert_record('course_modules_completion', $completion);
        }
    }
}

seed_log("  Activity completion set for " . count($ALL_CMIDS) . " activities × " . count($STUDENT_IDS) . " students.");

// ─── Step 6: Verify ───────────────────────────────────────────────────────────

seed_log("");
seed_log("═══════════════════════════════════════════════════");
seed_log("  SEED COMPLETE — VERIFICATION SUMMARY");
seed_log("═══════════════════════════════════════════════════");

$enrolledCount = $DB->count_records_sql(
    "SELECT COUNT(DISTINCT ue.userid)
     FROM {user_enrolments} ue
     JOIN {enrol} e ON e.id = ue.enrolid
     JOIN {user} u ON u.id = ue.userid
     WHERE e.courseid = ? AND u.username LIKE 'student%'",
    [$COURSE_ID]
);
seed_log("  Students enrolled: $enrolledCount / 10");

foreach ($AICODE_ACTIVITIES as $act) {
    $attCount = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {aicode_attempts} WHERE problemid = ? AND userid IN (3,4,5,6,7,8,9,10,11,12)",
        [$act['instance']]
    );
    $submitCount = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {aicode_attempts} WHERE problemid = ? AND userid IN (3,4,5,6,7,8,9,10,11,12) AND teacher_review_requested = 1",
        [$act['instance']]
    );
    $hintCount = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {aicode_attempts} WHERE problemid = ? AND userid IN (3,4,5,6,7,8,9,10,11,12) AND ai_requested_at IS NOT NULL",
        [$act['instance']]
    );
    seed_log("  {$act['name']}: $attCount attempts, $submitCount submitted, $hintCount with AI hints");
}

$totalLogEntries = $DB->count_records_sql(
    "SELECT COUNT(*) FROM {aicode_activity_log} WHERE id_kursus = ? AND id_pengguna IN (3,4,5,6,7,8,9,10,11,12)",
    [$COURSE_ID]
);
seed_log("  Activity log entries: $totalLogEntries");

$completionCount = $DB->count_records_sql(
    "SELECT COUNT(*) FROM {course_modules_completion}
     WHERE coursemoduleid IN (" . implode(',', $ALL_CMIDS) . ")
     AND userid IN (3,4,5,6,7,8,9,10,11,12)
     AND completionstate = 1"
);
seed_log("  Completion records: $completionCount");

seed_log("");
seed_log("Done! Open the dashboard to see the results:");
seed_log("  http://localhost/moodle/mod/aicode/report.php?id=958");
seed_log("  http://localhost/moodle/mod/aicode/report.php?id=961");
seed_log("  http://localhost/moodle/mod/aicode/report.php?id=964");
seed_log("  http://localhost/moodle/mod/aicode/report.php?id=967");
seed_log("  http://localhost/moodle/mod/aicode/report.php?id=970");
