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
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AI prompt template helper for AICode.
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aicode\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper for AI prompt templates.
 */
class ai_prompt {
    /** @var string Token for source code replacement. */
    private const CODE_TOKEN = '[[CODE]]';
    /** @var string Token for stderr replacement. */
    private const STDERR_TOKEN = '[[STDERR]]';
    /** @var string Token for trace replacement. */
    private const TRACE_TOKEN = '[[TRACE]]';

    /**
     * Default AI prompt template.
     *
     * @return string
     */
    public static function get_default_template() {
        return <<<'PROMPT'
Anda adalah tutor JavaScript untuk siswa SMA/SMK di Indonesia.
Tugas Anda: menganalisis kode siswa dan error eksekusi, lalu memberikan feedback yang akurat, detail, dan mudah dipahami.
Keluaran HARUS hanya satu objek JSON valid (tanpa markdown, tanpa backticks, tanpa teks di luar JSON).

Gunakan skema JSON berikut:
{
  "diagnosis": {
    "category": "syntax|runtime|logic|style|security|performance",
    "confidence": 0.0,
    "message_short": "kalimat ringkas",
    "message_long": "analisis lengkap yang mudah dipahami siswa"
  },
  "location": {
    "line": 0,
    "column": 0,
    "snippet": "potongan kode yang relevan"
  },
  "hints": [
    "panduan aksi yang bisa langsung dilakukan",
    "penjelasan pencegahan agar kesalahan tidak terulang"
  ],
  "suggested_fix": {
    "explanation": "langkah bernomor yang jelas tentang apa yang perlu diubah dan alasannya",
    "code_patch": "potongan kode perbaikan minimal"
  },
  "recommended_materials": [
    {"title": "judul materi", "url": "https://example.com", "reason": "alasan materi ini relevan"}
  ],
  "explainability": "alasan singkat yang merujuk bukti error"
}

Aturan kualitas:
- Semua nilai teks WAJIB menggunakan Bahasa Indonesia baku yang sederhana dan ramah siswa SMA/SMK.
- Hindari jargon; jika harus memakai istilah teknis, jelaskan artinya dalam kalimat yang sama.
- diagnosis.message_short: 1 kalimat ringkas, maksimal 18 kata.
- diagnosis.message_long: 4-6 kalimat dengan urutan: akar masalah, bukti dari stderr/trace, dampak pada program, dan pencegahan agar tidak terulang.
- Hints harus spesifik, dapat langsung dikerjakan, fokus pada akar masalah dan pencegahan.
- suggested_fix.explanation harus berupa langkah bernomor 1), 2), 3) yang konkret.
- suggested_fix.code_patch harus potongan kode minimal yang relevan langsung dengan error.
- Jika tidak ada patch yang andal, set suggested_fix ke null.
- recommended_materials berisi 1-3 sumber tepercaya; reason wajib menjelaskan manfaatnya untuk kasus saat ini.
- Jangan menyalahkan siswa; gunakan nada membimbing dan suportif.
- Confidence harus bernilai 0 sampai 1. Gunakan nilai lebih rendah jika bukti kurang kuat.
- Jika lokasi tidak pasti, gunakan line 0 dan column 0.
- Jika stderr dan trace kosong, lakukan analisis statis dari kode dan jelaskan keterbatasannya di explainability.

Kode siswa:
[[CODE]]

stderr:
[[STDERR]]

trace:
[[TRACE]]
PROMPT;
    }

    /**
     * Render prompt template by replacing tokens.
     *
     * @param string $template
     * @param string $code
     * @param string $stderr
     * @param string $trace
     * @return string
     */
    public static function render_template($template, $code, $stderr, $trace) {
        $template = trim((string)$template);
        if ($template === '') {
            $template = self::get_default_template();
        }

        $prompt = strtr($template, [
            self::CODE_TOKEN => (string)$code,
            self::STDERR_TOKEN => (string)$stderr,
            self::TRACE_TOKEN => (string)$trace,
        ]);

        $hastokens = strpos($template, self::CODE_TOKEN) !== false
            || strpos($template, self::STDERR_TOKEN) !== false
            || strpos($template, self::TRACE_TOKEN) !== false;

        // Keep custom templates safe even if tokens were omitted.
        if (!$hastokens) {
            $prompt .= "\n\nKode siswa:\n" . (string)$code
                . "\n\nstderr:\n" . (string)$stderr
                . "\n\ntrace:\n" . (string)$trace . "\n";
        }

        return $prompt;
    }
}

