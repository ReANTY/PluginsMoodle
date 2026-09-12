<?php
// =============================================================
// Adaptive Learning AI — AI Provider (OpenRouter & Google Gemini)
// Supports dynamic configuration via Moodle get_config()
// =============================================================

if (!defined('MOODLE_INTERNAL') && !defined('AJAX_SCRIPT')) {
    die('Direct access not permitted');
}

/**
 * Get configured AI API Key (OpenRouter or Google)
 *
 * @return string
 */
function alai_get_api_key() {
    $key = get_config('block_adaptive_learning_ai', 'gemini_apikey');
    return !empty($key) ? trim($key) : '';
}

/**
 * Get configured AI Model
 *
 * @return string
 */
function alai_get_model() {
    $model = get_config('block_adaptive_learning_ai', 'gemini_model');
    return !empty($model) ? trim($model) : 'google/gemini-2.5-flash';
}

// -------------------------------------------------------------
// CORE: Call AI API (Auto-detects OpenRouter vs Google AI Studio)
// -------------------------------------------------------------
function alai_call_gemini($prompt, $maxTokens = 400, $temperature = 0.7) {
    global $CFG;

    $apiKey = alai_get_api_key();
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'API Key belum dikonfigurasi di pengaturan admin Moodle.', 'text' => ''];
    }

    $model = alai_get_model();

    // Deteksi apakah menggunakan OpenRouter (Key dimulai dengan sk-or- atau model memakai slash)
    $isOpenRouter = (strpos($apiKey, 'sk-or-') === 0) || (strpos($model, '/') !== false);

    if ($isOpenRouter) {
        $endpoint = 'https://openrouter.ai/api/v1/chat/completions';
        $siteUrl  = !empty($CFG->wwwroot) ? $CFG->wwwroot : 'http://localhost/moodle';

        $data = [
            'model'       => $model,
            'messages'    => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens'  => $maxTokens,
            'temperature' => $temperature,
        ];

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'HTTP-Referer: ' . $siteUrl,
            'X-Title: Moodle Adaptive Learning AI'
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => 'cURL OpenRouter: ' . $curlError, 'text' => ''];
        }
        if ($httpCode !== 200) {
            $errBody = json_decode($response, true);
            $errMsg  = $errBody['error']['message'] ?? ('HTTP ' . $httpCode);
            return ['success' => false, 'error' => 'OpenRouter Error: ' . $errMsg, 'text' => ''];
        }

        $result = json_decode($response, true);
        $text   = $result['choices'][0]['message']['content'] ?? '';

        // Bersihkan pembungkus markdown ```html ... ``` jika ada
        $text = preg_replace('/^```(?:html)?\s*([\s\S]*?)\s*```$/i', '$1', trim($text));

        return ['success' => true, 'text' => trim($text), 'error' => ''];

    } else {
        // Direct Google Generative Language API
        $cleanModel = str_replace('google/', '', $model);
        $endpoint   = 'https://generativelanguage.googleapis.com/v1beta/models/' . $cleanModel . ':generateContent?key=' . $apiKey;

        $data = [
            'contents'         => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'maxOutputTokens' => $maxTokens,
                'temperature'     => $temperature,
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
            ],
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => 'cURL Gemini: ' . $curlError, 'text' => ''];
        }
        if ($httpCode !== 200) {
            $errBody = json_decode($response, true);
            $errMsg  = $errBody['error']['message'] ?? ('HTTP ' . $httpCode);
            return ['success' => false, 'error' => 'Google Gemini Error: ' . $errMsg, 'text' => ''];
        }

        $result = json_decode($response, true);
        $text   = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $text   = preg_replace('/^```(?:html)?\s*([\s\S]*?)\s*```$/i', '$1', trim($text));

        return ['success' => true, 'text' => trim($text), 'error' => ''];
    }
}

// -------------------------------------------------------------
// 1. DYNAMIC COURSE MATERIAL RECOMMENDATIONS (Gemini AI + Course Data)
// -------------------------------------------------------------
/**
 * Dapatkan rekomendasi materi kursus berbasis Gemini AI (OpenRouter).
 * Rekomendasi materi WAJIB diambil dari materi yang ada di dalam course.
 *
 * @param int $courseid
 * @param int $userid
 * @param int $score
 * @param string $level
 * @param string $quizName
 * @param int $weekNum
 * @param bool $forceRefresh
 * @return array
 */
function alai_get_course_material_recommendations($courseid, $userid, $score, $level, $quizName = '', $weekNum = 1, $forceRefresh = false) {
    global $DB, $CFG;

    // 1. Cek Cache User Preference
    if (!$forceRefresh && $userid > 0) {
        $cachedRaw = get_user_preferences('alai_rec_' . $courseid, '', $userid);
        if (!empty($cachedRaw)) {
            $cached = json_decode($cachedRaw, true);
            if (!empty($cached) && isset($cached['score'], $cached['data']) 
                && $cached['score'] == $score 
                && ($cached['level'] ?? '') == $level
                && (time() - ($cached['time'] ?? 0)) < 7200) {
                return $cached['data'];
            }
        }
    }

    // 2. Kumpulkan Modul Nyata dari Kursus
    $course = $DB->get_record('course', ['id' => $courseid]);
    if (!$course) {
        return [
            'advice' => 'Kursus tidak ditemukan.',
            'recommendations' => [],
            'source' => 'fallback'
        ];
    }

    require_once($CFG->dirroot . '/course/lib.php');
    $modinfo = get_fast_modinfo($course, $userid);
    $sections = $modinfo->get_section_info_all();

    $allCandidatesByCmid = [];
    $scopedCandidates    = [];
    $targetWeeks         = [max(1, $weekNum - 1), $weekNum, $weekNum + 1];

    foreach ($sections as $secnum => $section) {
        if ($secnum === 0 || empty($modinfo->sections[$secnum])) {
            continue;
        }
        $secName = $section->name ? trim($section->name) : ('Minggu ' . $secnum);

        foreach ($modinfo->sections[$secnum] as $cmid) {
            $cm = $modinfo->cms[$cmid];
            if (!$cm->uservisible) {
                continue;
            }
            if (stripos($cm->name, 'khusus guru') !== false || stripos($cm->name, 'kunci jawaban') !== false) {
                continue;
            }

            $url = $cm->url ? $cm->url->out(false) : ($CFG->wwwroot . '/mod/' . $cm->modname . '/view.php?id=' . $cmid);
            $item = [
                'cmid'    => (int) $cmid,
                'title'   => $cm->name,
                'type'    => $cm->modname,
                'secnum'  => (int) $secnum,
                'section' => $secName,
                'url'     => $url,
            ];

            $allCandidatesByCmid[$cmid] = $item;

            if (in_array($secnum, $targetWeeks)) {
                $scopedCandidates[] = $item;
            }
        }
    }

    // Jika target weeks sedikit, gunakan semua materi kursus yang tersedia
    $candidates = (count($scopedCandidates) >= 3) ? $scopedCandidates : array_values($allCandidatesByCmid);

    if (empty($candidates)) {
        return [
            'advice' => 'Belum ada materi pembelajaran yang tersedia pada kursus ini.',
            'recommendations' => [],
            'source' => 'empty'
        ];
    }

    // 3. Siapkan Prompt ke OpenRouter Gemini
    $candidatesForPrompt = array_map(function($c) {
        return [
            'cmid'    => $c['cmid'],
            'title'   => $c['title'],
            'type'    => $c['type'],
            'section' => $c['section']
        ];
    }, array_slice($candidates, 0, 24));

    $candidatesJson = json_encode($candidatesForPrompt, JSON_UNESCAPED_UNICODE);
    $quizLabel = !empty($quizName) ? "pada {$quizName}" : "pada kuis terbaru";

    $prompt = "Kamu adalah AI Tutor Pembelajaran Adaptif di LMS Moodle.
Profil Siswa:
- Skor Kuis: {$score}% ({$quizLabel})
- Level Kognitif: {$level} (Threshold: Primary < 70%, Expert >= 85%)
- Posisi Pembelajaran: Minggu {$weekNum}

Berikut adalah DAFTAR MATERI RESMI YANG ADA DI DALAM KURSUS INI:
{$candidatesJson}

INSTRUKSI WAJIB:
1. Tulis 'advice': 2-3 kalimat motivasi & analisis belajar dalam bahasa Indonesia yang suportif, ramah, dan sesuai skor siswa.
2. PILIH TEPAT 2 atau 3 materi DARI DAFTAR DI ATAS yang paling tepat untuk siswa:
   - Jika skor rendah / level PRIMARY/LOW (< 70%): Pilih materi/kuis konsep dasar untuk 'Perbaikan Nilai' (Remedial).
   - Jika level INTERMEDIATE/MEDIUM (70-84%): Pilih materi pemahaman dan praktik untuk 'Penguatan Konsep'.
   - Jika skor tinggi / level EXPERT/HIGH (>= 85%): Pilih materi lanjutan, praktik kode, atau kuis minggu berikutnya untuk 'Materi Lanjutan' (Pengayaan).
   - PERINGATAN KERAS: DILARANG KERAS mengambil atau mengarang materi dari luar daftar di atas! Nilai 'cmid' HARUS persis ada di daftar.
3. Untuk setiap materi yang dipilih, tentukan:
   - 'cmid': nomor cmid sesuai daftar
   - 'purpose': salah satu dari ['Perbaikan Nilai', 'Penguatan Konsep', 'Materi Lanjutan']
   - 'reason': 1 kalimat singkat alasan materi ini disarankan

Format output: HANYA keluarkan format JSON murni tanpa pembungkus markdown (tanpa ```json atau ```):
{
  \"advice\": \"Pesan motivasi dan arahan belajar di sini...\",
  \"recommendations\": [
    {
      \"cmid\": 1234,
      \"purpose\": \"Perbaikan Nilai\",
      \"reason\": \"Alasan singkat rekomendasi.\"
    }
  ]
}";

    $aiRes = alai_call_gemini($prompt, 550, 0.4);

    if ($aiRes['success'] && !empty($aiRes['text'])) {
        $clean = trim($aiRes['text']);
        $clean = preg_replace('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', '$1', $clean);
        if (preg_match('/\{[\s\S]*\}/', $clean, $matches)) {
            $clean = $matches[0];
        }

        $parsed = json_decode($clean, true);
        if (!empty($parsed) && !empty($parsed['recommendations']) && is_array($parsed['recommendations'])) {
            $finalRecs = [];
            foreach ($parsed['recommendations'] as $rec) {
                $targetCmid = (int) ($rec['cmid'] ?? 0);
                if (isset($allCandidatesByCmid[$targetCmid])) {
                    $cand = $allCandidatesByCmid[$targetCmid];
                    $finalRecs[] = [
                        'cmid'    => $cand['cmid'],
                        'title'   => $cand['title'],
                        'type'    => $cand['type'],
                        'section' => $cand['section'],
                        'url'     => $cand['url'],
                        'purpose' => $rec['purpose'] ?? 'Rekomendasi Materi',
                        'reason'  => $rec['reason'] ?? '',
                    ];
                }
            }

            if (!empty($finalRecs)) {
                $resultData = [
                    'advice'          => $parsed['advice'] ?? '',
                    'recommendations' => $finalRecs,
                    'source'          => 'gemini_ai'
                ];

                // Simpan ke User Preference Cache
                if ($userid > 0) {
                    $cachePayload = json_encode([
                        'score' => $score,
                        'level' => $level,
                        'time'  => time(),
                        'data'  => $resultData
                    ], JSON_UNESCAPED_UNICODE);
                    if (strlen($cachePayload) <= 1330) {
                        set_user_preference('alai_rec_' . $courseid, $cachePayload, $userid);
                    }
                }

                return $resultData;
            }
        }
    }

    // 4. Fallback jika Gemini offline atau respon tidak valid
    return alai_build_fallback_recommendations($courseid, $allCandidatesByCmid, $score, $level, $weekNum, $userid);
}

/**
 * Fallback cerdas rekomendasi materi dari dalam course jika AI tidak merespon
 */
function alai_build_fallback_recommendations($courseid, $allCandidatesByCmid, $score, $level, $weekNum, $userid = 0) {
    $recs = [];
    $isLow  = ($score < 70 || in_array($level, ['PRIMARY', 'LOW', 'NODATA']));
    $isHigh = ($score >= 85 || in_array($level, ['EXPERT', 'HIGH']));

    $advice = '';
    if ($isLow) {
        $advice = "Skor Anda membutuhkan penguatan konsep dasar. Kami menyarankan untuk mempelajari kembali materi fondasi dan latihan berikut untuk perbaikan nilai Anda.";
        $purpose = 'Perbaikan Nilai';
    } elseif ($isHigh) {
        $advice = "Prestasi luar biasa! Pemahaman Anda sangat tinggi. Tantang diri Anda dengan materi pengayaan dan latihan lanjutan berikut.";
        $purpose = 'Materi Lanjutan';
    } else {
        $advice = "Pemahaman Anda berada di jalur yang baik. Kuatkan konsep dengan materi praktik berikut untuk meningkatkan nilai ke level Expert.";
        $purpose = 'Penguatan Konsep';
    }

    $sortedCandidates = array_values($allCandidatesByCmid);
    if ($isHigh) {
        // Ambil modul minggu saat ini atau minggu berikutnya (terutama aicode/quiz/assignment)
        foreach ($sortedCandidates as $c) {
            if ($c['secnum'] >= $weekNum && in_array($c['type'], ['aicode', 'quiz', 'assign', 'page'])) {
                $recs[] = [
                    'cmid'    => $c['cmid'],
                    'title'   => $c['title'],
                    'type'    => $c['type'],
                    'section' => $c['section'],
                    'url'     => $c['url'],
                    'purpose' => $purpose,
                    'reason'  => 'Materi lanjutan untuk memperdalam pemahaman praktis Anda.'
                ];
                if (count($recs) >= 3) break;
            }
        }
    } else {
        // Ambil materi penguatan dasar
        foreach ($sortedCandidates as $c) {
            if ($c['secnum'] <= max(1, $weekNum)) {
                $recs[] = [
                    'cmid'    => $c['cmid'],
                    'title'   => $c['title'],
                    'type'    => $c['type'],
                    'section' => $c['section'],
                    'url'     => $c['url'],
                    'purpose' => $purpose,
                    'reason'  => $isLow ? 'Pelajari materi ini untuk perbaikan nilai dan penguasaan fondasi.' : 'Latihan ini akan memantapkan konsep Anda.'
                ];
                if (count($recs) >= 3) break;
            }
        }
    }

    // Fallback jika belum cukup
    if (empty($recs) && !empty($sortedCandidates)) {
        foreach (array_slice($sortedCandidates, 0, 2) as $c) {
            $recs[] = [
                'cmid'    => $c['cmid'],
                'title'   => $c['title'],
                'type'    => $c['type'],
                'section' => $c['section'],
                'url'     => $c['url'],
                'purpose' => $purpose,
                'reason'  => 'Materi rekomendasi untuk jalur belajar Anda.'
            ];
        }
    }

    $resultData = [
        'advice'          => $advice,
        'recommendations' => $recs,
        'source'          => 'fallback'
    ];

    return $resultData;
}

/**
 * Render Icon Modul Moodle dengan styling modern yang rapi
 */
function alai_render_module_icon($modname) {
    switch ($modname) {
        case 'quiz':
            return '<span class="alai-type-icon alai-icon-quiz" title="Kuis"><i class="fas fa-question-circle"></i></span>';
        case 'aicode':
            return '<span class="alai-type-icon alai-icon-code" title="Praktik Kode"><i class="fas fa-laptop-code"></i></span>';
        case 'assign':
            return '<span class="alai-type-icon alai-icon-assign" title="Tugas Praktik"><i class="fas fa-file-signature"></i></span>';
        case 'forum':
            return '<span class="alai-type-icon alai-icon-forum" title="Forum Diskusi"><i class="fas fa-comments"></i></span>';
        case 'page':
        default:
            return '<span class="alai-type-icon alai-icon-page" title="Materi Pembelajaran"><i class="fas fa-book-open"></i></span>';
    }
}

// -------------------------------------------------------------
// 2. LEARNING RECOMMENDATION — berdasarkan nilai & level
// -------------------------------------------------------------
function alai_get_recommendation($score, $level, $quizName = '', $topic = '') {
    $quizInfo = $quizName ? "Quiz '$quizName'" : 'Quiz';

    if ($level === 'LOW') {
        $prompt = "Kamu AI tutor di Moodle. Siswa mendapat nilai {$score}% pada {$quizInfo} (Level REMEDIAL).
Berikan dalam HTML langsung tanpa markdown code block:
1. <b>Analisis singkat</b>: kenapa nilai bisa rendah (2 kalimat)
2. <b>3 Tips belajar spesifik</b> untuk topik '{$topic}'
3. <b>Motivasi</b>: 1 kalimat penyemangat
Format: paragraf pendek, gunakan <b> untuk penekanan, bahasa Indonesia, max 120 kata.";
    } elseif ($level === 'HIGH') {
        $prompt = "Kamu AI tutor di Moodle. Siswa mendapat nilai {$score}% pada {$quizInfo} (Level ADVANCED).
Berikan dalam HTML langsung tanpa markdown code block:
1. <b>Pujian spesifik</b> atas pencapaian (1 kalimat)
2. <b>2 Tantangan pengayaan</b> untuk topik '{$topic}'
3. <b>Proyek mini</b>: 1 ide proyek untuk dipraktikkan
Format: paragraf pendek, gunakan <b> untuk penekanan, bahasa Indonesia, max 100 kata.";
    } else {
        $prompt = "Kamu AI tutor di Moodle. Siswa mendapat nilai {$score}% pada {$quizInfo} (Level STANDARD).
Berikan dalam HTML langsung tanpa markdown code block:
1. <b>Topik yang perlu diperkuat</b> (2 kalimat)
2. <b>2 Strategi belajar</b> untuk naik ke level Advanced (nilai ≥90%)
3. <b>Latihan fokus</b>: 1 latihan spesifik
Format: paragraf pendek, gunakan <b> untuk penekanan, bahasa Indonesia, max 110 kata.";
    }

    $result = alai_call_gemini($prompt, 250, 0.7);
    return $result['success'] ? $result['text'] : '';
}

// -------------------------------------------------------------
// 2. DYNAMIC MICROLEARNING CONTENT — generate konten kursus 5-menit
// Mendukung Materi Pembelajaran, Persiapan Kuis, & Ringkasan Minggu
// -------------------------------------------------------------
function alai_generate_microlearning($topic, $level, $score, $courseName = '') {
    $levelDesc = [
        'LOW'    => 'pemula / remedial — fokus pemahaman konsep dasar, bahasa mudah dipahami, langkah demi langkah',
        'MEDIUM' => 'menengah / standard — fokus implementasi praktis dan penyelesaian tugas',
        'HIGH'   => 'advanced / pengayaan — fokus optimasi kode, best practices, dan studi kasus kompleks',
    ][$level] ?? 'menengah';

    $contextStr = !empty($courseName) ? "pada kursus '{$courseName}'" : "di LMS Moodle";
    $isQuiz     = (stripos($topic, 'kuis') !== false || stripos($topic, 'quiz') !== false);
    $isSummary  = (stripos($topic, 'ringkasan') !== false);

    if ($isQuiz) {
        $prompt = "Kamu adalah AI tutor {$contextStr}. Siswa di tingkat {$level} (skor {$score}%) ingin bersiap menghadapi kuis: '{$topic}'.
Buat panduan persiapan kuis 5 menit dalam HTML murni (tanpa tag html/body/code block markdown, langsung konten HTML, max 220 kata):
<b>🎯 Persiapan Kuis:</b> {$topic}<br>
<b>⏱️ Durasi Belajar:</b> 5 menit<br><br>
<b>📌 Konsep Kunci yang Sering Diuji:</b><br>
[Jelaskan 2-3 poin konsep paling krusial yang diujikan pada materi ini]<br><br>
<b>❓ Contoh Soal Latihan & Pembahasan:</b><br>
[Berikan 1 contoh soal tipe kuis beserta penjelasan jawabannya yang gamblang]<br><br>
<b>💡 Tips Sukses Kuis:</b> [Tips penting agar dapat nilai maksimal]";
    } elseif ($isSummary) {
        $prompt = "Kamu adalah AI tutor {$contextStr}. Siswa di tingkat {$level} (skor {$score}%) memilih: '{$topic}'.
Buat intisari rangkuman minggu ini dalam HTML murni (tanpa tag html/body/code block markdown, max 220 kata):
<b>🎯 Rangkuman Pembelajaran:</b> {$topic}<br>
<b>⏱️ Durasi:</b> 5 menit<br><br>
<b>📖 Intisari Konsep:</b><br>
[Rangkum 3-4 poin konsep inti yang wajib dipahami]<br><br>
<b>💻 Sintaks / Kode Kunci:</b><br>
<pre style='background:rgba(15,23,42,.85);padding:10px;border-radius:8px;font-size:.72rem;overflow-x:auto'>[Kode contoh ringkas]</pre>
<b>💡 Arah Belajar Selanjutnya:</b> [Langkah penguasaan sebelum melangkah ke topik berikutnya]";
    } else {
        $prompt = "Kamu adalah AI tutor {$contextStr}. Siswa di tingkat {$level} ({$levelDesc}) memilih materi: '{$topic}'.
Buat modul microlearning 5 menit dalam HTML murni (tanpa tag html/body/code block markdown, max 220 kata):
<b>🎯 Materi:</b> {$topic}<br>
<b>⏱️ Durasi:</b> 5 menit<br><br>
<b>📖 Penjelasan Konsep Inti:</b><br>
[Penjelasan 2-3 kalimat yang ringkas dan mudah dipahami]<br><br>
<b>💻 Contoh Kode Praktis:</b><br>
<pre style='background:rgba(15,23,42,.85);padding:10px;border-radius:8px;font-size:.72rem;overflow-x:auto'>[Contoh kode bersih dan jelas]</pre>
<b>✅ Mini Quiz Pemahaman:</b><br>
[1 pertanyaan singkat beserta jawabannya]<br><br>
<b>💡 Tips Praktik:</b> [1 tips penting terkait penulisan kode atau penerapan]";
    }

    $result = alai_call_gemini($prompt, 450, 0.7);
    return $result['success'] ? $result['text'] : null;
}

// -------------------------------------------------------------
// 3. CHAT ANSWER — jawab pertanyaan bebas siswa
// -------------------------------------------------------------
function alai_answer_question($question, $level, $score, $courseName = '') {
    $context = !empty($courseName) ? " dalam konteks pembelajaran kursus '{$courseName}'" : '';

    $prompt = "Kamu adalah AI tutor pemrograman{$context}. Siswa dengan level {$level} (skor {$score}%) bertanya:
\"{$question}\"

Jawab dalam HTML murni (tanpa pembungkus markdown ```html, max 150 kata):
- Gunakan <b> untuk penekanan
- Sertakan contoh kode dalam tag <pre> jika relevan
- Berikan penjelasan ramah, terstruktur, dan mudah dipahami sesuai kemampuan level {$level}";

    $result = alai_call_gemini($prompt, 350, 0.7);

    if ($result['success'] && $result['text']) {
        return $result['text'];
    }

    return null;
}