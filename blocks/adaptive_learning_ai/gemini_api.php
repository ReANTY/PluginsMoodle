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
// 1. LEARNING RECOMMENDATION — berdasarkan nilai & level
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