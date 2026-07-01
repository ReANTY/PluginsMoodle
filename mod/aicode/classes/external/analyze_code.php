<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * External API for AI code analysis
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aicode\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use mod_aicode\local\ai_prompt;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External API for AI code analysis
 */
class analyze_code extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'problemid' => new external_value(PARAM_INT, 'Problem ID'),
            'code' => new external_value(PARAM_RAW, 'Code to analyze'),
            'stderr' => new external_value(PARAM_RAW, 'Error output'),
            'trace' => new external_value(PARAM_RAW, 'Execution trace'),
            'sesskey' => new external_value(PARAM_RAW, 'Session key', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Analyze code with AI
     *
     * @param int $problemid
     * @param string $code
     * @param string $stderr
     * @param string $trace
     * @param string $sesskey
     * @return array
     */
    public static function execute($problemid, $code, $stderr, $trace, $sesskey) {
        global $DB, $USER, $PAGE;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'problemid' => $problemid,
            'code' => $code,
            'stderr' => $stderr,
            'trace' => $trace,
            'sesskey' => $sesskey,
        ]);

        // Validate session key if provided.
        if (!empty($params['sesskey']) && !confirm_sesskey($params['sesskey'])) {
            throw new \moodle_exception('invalidsesskey');
        }

        // Get problem and validate access.
        $problem = $DB->get_record('aicode', ['id' => $params['problemid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('aicode', $problem->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        $PAGE->set_context($context);
        require_capability('mod/aicode:submit', $context);

        // Check rate limiting — only count rows where the student actually clicked AI Hint
        // (ai_requested_at IS NOT NULL). Plain code runs do NOT consume AI quota.
        $maxcalls = get_config('aicode', 'max_calls_per_day') ?: 50;
        $daystart = strtotime('today');
        $count = $DB->count_records_select(
            'aicode_attempts',
            'userid = :userid AND ai_requested_at IS NOT NULL AND ai_requested_at >= :daystart',
            ['userid' => $USER->id, 'daystart' => $daystart]
        );

        if ($count >= $maxcalls) {
            throw new \moodle_exception('Rate limit exceeded. Please try again tomorrow.');
        }

        // Fetch the student's latest attempt for this problem up-front so we can
        // stamp ai_requested_at (and optionally ai_feedback_json) on it later.
        $latestattemptrows = $DB->get_records_select(
            'aicode_attempts',
            'problemid = :pid AND userid = :uid',
            ['pid' => $params['problemid'], 'uid' => $USER->id],
            'timecreated DESC',
            'id',
            0, 1
        );
        $latestattempt = !empty($latestattemptrows) ? reset($latestattemptrows) : null;

        $prompttemplate = self::resolve_ai_prompt_template($problem);

        // Fetch teacher correction examples early so they influence the cache key.
        // When a teacher adds/removes a few-shot override the hash changes,
        // preventing stale cached responses from being served.
        $teacherexamples = (int)$params['problemid'] > 0
            ? self::get_teacher_correction_examples((int)$params['problemid'])
            : '';

        // Check cache.
        $payloadhash = hash(
            'sha256',
            'feedback-v6|' . hash('sha256', $prompttemplate) . '|' . hash('sha256', $teacherexamples)
            . '|' . $params['code'] . $params['stderr'] . $params['trace']
        );
        $cachettl = get_config('aicode', 'cache_ttl') ?: 3600;
        $cached = $DB->get_record('aicode_cache', ['payload_hash' => $payloadhash]);

        if ($cached && ($cached->timecreated + $cachettl) > time() && !empty($cached->ai_response_json)) {
            $cachedfeedback = json_decode($cached->ai_response_json, true);
            if (self::is_success_feedback($cachedfeedback)) {
                // Stamp ai_requested_at so this cached hit is counted against the quota.
                if ($latestattempt) {
                    $DB->set_field('aicode_attempts', 'ai_requested_at', time(), ['id' => $latestattempt->id]);
                }
                \mod_aicode\local\activity_log::record(
                    $context,
                    (int) $params['problemid'],
                    (int) $USER->id,
                    \mod_aicode\local\activity_log::ACTION_AI_ANALYZE,
                    [
                        'from_cache' => true,
                        'outcome' => 'success',
                    ],
                    $problem
                );
                return ['feedback' => $cached->ai_response_json];
            }
        }

        // Anonymize code (strip comments with names, emails, etc.).
        $anonymizedcode = self::anonymize_code($params['code']);
        $feedback = self::get_ai_feedback(
            $anonymizedcode,
            (string)$params['stderr'],
            (string)$params['trace'],
            $prompttemplate,
            $teacherexamples
        );
        if (!is_array($feedback)) {
            $feedback = self::build_ai_error_feedback(
                'Feedback AI tidak dapat diproses. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                'invalid_feedback_payload'
            );
        }

        // Validate confidence threshold for successful AI responses only.
        $threshold = get_config('aicode', 'confidence_threshold') ?: 0.6;
        if (self::is_success_feedback($feedback) && isset($feedback['diagnosis']['confidence'])
                && $feedback['diagnosis']['confidence'] < $threshold) {
            $feedback = self::build_ai_error_feedback(
                'Respons Moodle AI berada di bawah ambang kepercayaan. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                'low_confidence'
            );
        }

        $feedbackjson = json_encode($feedback);
        if ($feedbackjson === false) {
            $feedback = self::build_ai_error_feedback(
                'Gagal mengubah respons AI ke format JSON. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                'json_encoding_failed'
            );
            $feedbackjson = json_encode($feedback);
            if ($feedbackjson === false) {
                throw new \moodle_exception('erroraifeedback', 'aicode');
            }
        }

        $shouldcache = self::is_success_feedback($feedback);

        // Cache the response (update stale record if it already exists).
        if ($shouldcache) {
            if ($cached) {
                $cached->ai_response_json = $feedbackjson;
                $cached->timecreated = time();
                $DB->update_record('aicode_cache', $cached);
            } else {
                $cacherecord = new \stdClass();
                $cacherecord->payload_hash = $payloadhash;
                $cacherecord->ai_response_json = $feedbackjson;
                $cacherecord->timecreated = time();
                $DB->insert_record('aicode_cache', $cacherecord);
            }
        } else if ($cached) {
            $DB->delete_records('aicode_cache', ['id' => $cached->id]);
        }

        // Persist AI request timestamp (always) and successful feedback (on success)
        // to the student's most recent attempt so teachers can see it in the report.
        if ($latestattempt) {
            $DB->set_field('aicode_attempts', 'ai_requested_at', time(), ['id' => $latestattempt->id]);
            if ($shouldcache) {
                $DB->set_field('aicode_attempts', 'ai_feedback_json', $feedbackjson, ['id' => $latestattempt->id]);
            }
        }

        $fbdecoded = json_decode($feedbackjson, true);
        $outcome = (is_array($fbdecoded) && (($fbdecoded['status'] ?? '') === 'success')) ? 'success' : 'error';
        $metaline = [];
        if (is_array($fbdecoded)) {
            $errdata = $fbdecoded['error'] ?? null;
            $errcode = (is_array($errdata) && isset($errdata['code'])) ? $errdata['code'] : '';
            if (is_scalar($errcode) && (string) $errcode !== '') {
                $metaline['error_code'] = (string) $errcode;
            }
        }

        \mod_aicode\local\activity_log::record(
            $context,
            (int) $params['problemid'],
            (int) $USER->id,
            \mod_aicode\local\activity_log::ACTION_AI_ANALYZE,
            array_merge([
                'from_cache' => false,
                'outcome' => $outcome,
            ], $metaline),
            $problem
        );

        return ['feedback' => $feedbackjson];
    }

    /**
     * Anonymize code by removing PII
     *
     * @param string $code
     * @return string
     */
    private static function anonymize_code($code) {
        // Remove single-line comments.
        $code = preg_replace('/\/\/.*$/m', '', $code);
        // Remove multi-line comments.
        $code = preg_replace('/\/\*.*?\*\//s', '', $code);
        // Remove email addresses.
        $code = preg_replace('/[\w\.-]+@[\w\.-]+\.\w+/', '[EMAIL]', $code);
        return $code;
    }

    /**
     * Get AI feedback via the configured provider (OpenRouter or Google Gemini).
     *
     * @param string $code
     * @param string $stderr
     * @param string $trace
     * @param string $prompttemplate
     * @param string $teacherexamples  Pre-fetched few-shot examples from teacher corrections.
     * @return array
     */
    private static function get_ai_feedback($code, $stderr, $trace, $prompttemplate, $teacherexamples = '') {
        $provider = trim((string)get_config('aicode', 'ai_provider'));
        if ($provider === '') {
            $provider = 'openrouter'; // default fallback
        }

        $prompt = self::build_ai_feedback_prompt($prompttemplate, $code, $stderr, $trace, $teacherexamples);

        if ($provider === 'gemini') {
            return self::call_google_gemini_api($prompt);
        } else {
            return self::call_openrouter_api($prompt);
        }
    }

    /**
     * Call Google Gemini API directly.
     *
     * @param string $prompt
     * @return array
     */
    private static function call_google_gemini_api($prompt) {
        $apikey = trim((string)get_config('aicode', 'gemini_api_key'));
        if ($apikey === '') {
            return self::build_ai_error_feedback(
                'API key belum dikonfigurasi. Silakan isi API key Google Gemini di pengaturan plugin AICode.',
                'api_key_missing'
            );
        }

        $model = trim((string)get_config('aicode', 'gemini_model'));
        if ($model === '') {
            $model = 'gemini-2.5-flash';
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $apikey;

        $requestbody = json_encode([
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'topP' => 0.8,
                'maxOutputTokens' => 2048,
            ]
        ]);

        try {
            $curl = new \curl();
            $curl->setHeader([
                'Content-Type: application/json',
            ]);
            $rawresponse = $curl->post($url, $requestbody);
            $httpcode = $curl->get_info()['http_code'] ?? 0;

            if ($curl->get_errno()) {
                return self::build_ai_error_feedback(
                    'Koneksi ke Google Gemini API gagal: ' . $curl->error . '. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                    'gemini_connection_error'
                );
            }

            $decoded = json_decode($rawresponse, true);

            if ($httpcode !== 200) {
                $apierror = trim((string)($decoded['error']['message'] ?? ''));
                $reason = $apierror !== '' ? 'Google Gemini API error: ' . $apierror : 'Google Gemini API mengembalikan status HTTP ' . $httpcode . '.';
                return self::build_ai_error_feedback(
                    $reason . ' Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                    'gemini_api_error'
                );
            }

            $generatedcontent = trim((string)($decoded['candidates'][0]['content']['parts'][0]['text'] ?? ''));
            if ($generatedcontent === '') {
                return self::build_ai_error_feedback(
                    'Google Gemini API tidak menghasilkan teks. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                    'gemini_empty_response'
                );
            }

            $feedback = self::extract_feedback_json($generatedcontent);
            if (!is_array($feedback)) {
                return self::build_ai_error_feedback(
                    'Format respons AI tidak valid. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                    'invalid_ai_response_format'
                );
            }

            $normalized = self::normalize_feedback($feedback);
            $normalized['explainability'] = trim($normalized['explainability'] . ' (model: ' . $model . ')');

            if (!self::is_success_feedback($normalized)) {
                return self::build_ai_error_feedback(
                    'Respons AI tidak memenuhi format feedback yang diperlukan. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                    'invalid_ai_feedback_schema'
                );
            }

            return $normalized;
        } catch (\Throwable $e) {
            $reason = trim((string)$e->getMessage());
            $reason = $reason === '' ? 'Permintaan Google Gemini API gagal.' : 'Permintaan Google Gemini API gagal: ' . $reason;
            return self::build_ai_error_feedback(
                $reason . ' Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                'ai_request_failed'
            );
        }
    }

    /**
     * Call OpenRouter API.
     *
     * @param string $prompt
     * @return array
     */
    private static function call_openrouter_api($prompt) {
        $apikey = trim((string)get_config('aicode', 'openrouter_api_key'));
        if ($apikey === '') {
            return self::build_ai_error_feedback(
                'API key belum dikonfigurasi. Silakan isi API key OpenRouter di pengaturan plugin AICode.',
                'api_key_missing'
            );
        }

        $model = trim((string)get_config('aicode', 'openrouter_model'));
        if ($model === '') {
            $model = 'google/gemma-2-9b-it:free';
        }

        $url = 'https://openrouter.ai/api/v1/chat/completions';

        $requestbody = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.2,
            'top_p' => 0.8,
            'max_tokens' => 2048,
        ]);

        try {
            $curl = new \curl();
            $curl->setHeader([
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apikey,
                'HTTP-Referer: https://moodle.org',
                'X-Title: Moodle AICode Plugin',
            ]);
            $rawresponse = $curl->post($url, $requestbody);
            $httpcode = $curl->get_info()['http_code'] ?? 0;

            if ($curl->get_errno()) {
                return self::build_ai_error_feedback(
                    'Koneksi ke OpenRouter API gagal: ' . $curl->error . '. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                    'openrouter_connection_error'
                );
            }

            $decoded = json_decode($rawresponse, true);

            if ($httpcode !== 200) {
                $apierror = trim((string)($decoded['error']['message'] ?? ''));
                $reason = $apierror !== '' ? 'OpenRouter API error: ' . $apierror : 'OpenRouter API mengembalikan status HTTP ' . $httpcode . '.';
                return self::build_ai_error_feedback(
                    $reason . ' Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                    'openrouter_api_error'
                );
            }

            $generatedcontent = trim((string)($decoded['choices'][0]['message']['content'] ?? ''));
            if ($generatedcontent === '') {
                return self::build_ai_error_feedback(
                    'OpenRouter API tidak menghasilkan teks. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                    'openrouter_empty_response'
                );
            }

            $feedback = self::extract_feedback_json($generatedcontent);
            if (!is_array($feedback)) {
                return self::build_ai_error_feedback(
                    'Format respons AI tidak valid. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                    'invalid_ai_response_format'
                );
            }

            $normalized = self::normalize_feedback($feedback);
            $normalized['explainability'] = trim($normalized['explainability'] . ' (model: ' . $model . ')');

            if (!self::is_success_feedback($normalized)) {
                return self::build_ai_error_feedback(
                    'Respons AI tidak memenuhi format feedback yang diperlukan. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                    'invalid_ai_feedback_schema'
                );
            }

            return $normalized;
        } catch (\Throwable $e) {
            $reason = trim((string)$e->getMessage());
            $reason = $reason === '' ? 'Permintaan OpenRouter API gagal.' : 'Permintaan OpenRouter API gagal: ' . $reason;
            return self::build_ai_error_feedback(
                $reason . ' Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                'ai_request_failed'
            );
        }
    }

    /**
     * Build AI prompt for strict JSON diagnostic response.
     *
     * @param string $prompttemplate
     * @param string $code
     * @param string $stderr
     * @param string $trace
     * @param string $teacherexamples  Optional few-shot examples from teacher corrections.
     * @return string
     */
    private static function build_ai_feedback_prompt($prompttemplate, $code, $stderr, $trace, $teacherexamples = '') {
        $template = trim((string)$prompttemplate);
        if ($template === '') {
            $template = ai_prompt::get_default_template();
        }
        $prompt = ai_prompt::render_template($template, (string)$code, (string)$stderr, (string)$trace);
        if ($teacherexamples !== '') {
            // Prepend teacher corrections so the model uses them as in-context guidance.
            $prompt = $teacherexamples . "\n\n" . $prompt;
        }
        return $prompt;
    }

    /**
     * Fetch approved teacher correction examples for the given problem.
     * Returns a formatted string to prepend to the AI prompt, or '' if none found.
     *
     * @param int $problemid
     * @return string
     */
    private static function get_teacher_correction_examples(int $problemid): string {
        global $DB;

        $sql = "SELECT o.corrected_feedback_json, a.ai_feedback_json
                  FROM {aicode_teacher_overrides} o
                  JOIN {aicode_attempts} a ON a.id = o.attemptid
                 WHERE a.problemid = :pid
                   AND o.use_as_example = 1
                 ORDER BY o.timecreated DESC";

        $rows = $DB->get_records_sql($sql, ['pid' => $problemid], 0, 3);
        if (empty($rows)) {
            return '';
        }

        $parts = [];
        $idx   = 1;
        foreach ($rows as $row) {
            $correction = json_decode($row->corrected_feedback_json ?? '{}', true);
            $originalfb = json_decode($row->ai_feedback_json ?? '{}', true);
            if (!is_array($correction)) {
                continue;
            }

            $block = "=== Contoh Koreksi Guru #{$idx} ===\n";

            // Show what the AI originally said (for contrast).
            if (is_array($originalfb) && ($originalfb['status'] ?? '') === 'success') {
                $origshort = trim((string)($originalfb['diagnosis']['message_short'] ?? ''));
                if ($origshort !== '') {
                    $block .= "Feedback AI awal: \"{$origshort}\"\n";
                }
            }

            // Teacher corrected diagnosis.
            $cd        = $correction['corrected_diagnosis'] ?? [];
            $corrshort = trim((string)($cd['message_short'] ?? ''));
            $corrlong  = trim((string)($cd['message_long'] ?? ''));
            if ($corrshort !== '') {
                $block .= "Koreksi guru (ringkas): \"{$corrshort}\"\n";
            }
            if ($corrlong !== '') {
                $block .= "Koreksi guru (lengkap): \"{$corrlong}\"\n";
            }

            // Teacher corrected hints.
            $hints = $correction['corrected_hints'] ?? [];
            if (!empty($hints) && is_array($hints)) {
                $block .= "Petunjuk yang lebih baik:\n";
                foreach (array_slice($hints, 0, 3) as $hi => $hint) {
                    $block .= ($hi + 1) . '. ' . trim((string)$hint) . "\n";
                }
            }

            // Teacher corrected fix.
            $fix = trim((string)($correction['corrected_suggested_fix'] ?? ''));
            if ($fix !== '') {
                $block .= "Saran perbaikan yang tepat: \"{$fix}\"\n";
            }

            // Teacher notes (general guidance).
            $notes = trim((string)($correction['notes'] ?? ''));
            if ($notes !== '') {
                $block .= "Catatan guru: \"{$notes}\"\n";
            }

            $parts[] = $block;
            $idx++;
        }

        if (empty($parts)) {
            return '';
        }

        $header = "PENTING: Guru telah memberikan koreksi pada feedback AI sebelumnya untuk soal ini. "
            . "Gunakan contoh-contoh di bawah sebagai panduan untuk menghasilkan feedback yang lebih akurat, "
            . "sesuai kesalahan nyata siswa, dan sesuai dengan harapan guru:\n\n";

        return $header . implode("\n", $parts) . "\n";
    }

    /**
     * Resolve AI prompt template from activity override or plugin setting.
     *
     * @param \stdClass $problem
     * @return string
     */
    private static function resolve_ai_prompt_template($problem) {
        $activitytemplate = trim((string)($problem->aiprompttemplate ?? ''));
        if ($activitytemplate !== '') {
            return $activitytemplate;
        }

        $globaltemplate = trim((string)get_config('aicode', 'ai_feedback_prompt_template'));
        if ($globaltemplate !== '') {
            return $globaltemplate;
        }

        return ai_prompt::get_default_template();
    }

    /**
     * Extract feedback JSON from model output.
     *
     * @param string $content
     * @return array|null
     */
    private static function extract_feedback_json($content) {
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/is', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $jsonobject = self::find_first_json_object($content);
        if ($jsonobject !== null) {
            $decoded = json_decode($jsonobject, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Find first JSON object in plain text.
     *
     * @param string $text
     * @return string|null
     */
    private static function find_first_json_object($text) {
        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $instring = false;
        $escaped = false;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($instring) {
                if ($escaped) {
                    $escaped = false;
                } else if ($char === '\\') {
                    $escaped = true;
                } else if ($char === '"') {
                    $instring = false;
                }
                continue;
            }

            if ($char === '"') {
                $instring = true;
                continue;
            }

            if ($char === '{') {
                $depth++;
            } else if ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($text, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    /**
     * Build standardized AI error payload for frontend.
     *
     * @param string $reason
     * @param string $code
     * @return array
     */
    private static function build_ai_error_feedback($reason, $code = 'feedback_unavailable') {
        $cleanreason = trim((string)$reason);
        if ($cleanreason === '') {
            $cleanreason = 'Feedback AI belum tersedia saat ini.';
        }

        return [
            'status' => 'error',
            'error' => [
                'code' => (string)$code,
                'message' => 'Feedback AI gagal diberikan saat ini.',
                'reason' => $cleanreason,
                'action' => 'Silakan klik tombol Bantuan lagi beberapa saat lagi.',
            ],
            'explainability' => $cleanreason,
        ];
    }

    /**
     * Determine whether payload contains successful AI feedback.
     *
     * @param mixed $feedback
     * @return bool
     */
    private static function is_success_feedback($feedback) {
        if (!is_array($feedback)) {
            return false;
        }

        $status = (string)($feedback['status'] ?? 'success');
        if ($status !== 'success') {
            return false;
        }

        if (empty($feedback['diagnosis']) || !is_array($feedback['diagnosis'])) {
            return false;
        }

        $diagnosis = $feedback['diagnosis'];
        $shortmessage = trim((string)($diagnosis['message_short'] ?? ''));
        $longmessage = trim((string)($diagnosis['message_long'] ?? ''));
        if ($shortmessage === '' || $longmessage === '') {
            return false;
        }

        $confidence = (float)($diagnosis['confidence'] ?? -1);
        if ($confidence < 0 || $confidence > 1) {
            return false;
        }

        return true;
    }

    /**
     * Normalize model feedback into stable schema for frontend.
     *
     * @param array $feedback
     * @return array
     */
    private static function normalize_feedback($feedback) {
        $normalized = [
            'status' => 'success',
            'diagnosis' => [
                'category' => 'runtime',
                'confidence' => 0.0,
                'message_short' => '',
                'message_long' => '',
            ],
            'location' => [
                'line' => 0,
                'column' => 0,
                'snippet' => '',
            ],
            'hints' => [],
            'suggested_fix' => null,
            'recommended_materials' => [],
            'explainability' => 'Dihasilkan oleh Gemini AI.',
        ];

        if (!empty($feedback['diagnosis']) && is_array($feedback['diagnosis'])) {
            $diagnosis = $feedback['diagnosis'];
            $normalized['diagnosis']['category'] = self::normalize_diagnosis_category(
                (string)($diagnosis['category'] ?? $normalized['diagnosis']['category'])
            );
            $normalized['diagnosis']['confidence'] = max(0.0, min(1.0, (float)($diagnosis['confidence'] ?? $normalized['diagnosis']['confidence'])));
            $normalized['diagnosis']['message_short'] = trim((string)($diagnosis['message_short'] ?? $normalized['diagnosis']['message_short']));
            $normalized['diagnosis']['message_long'] = trim((string)($diagnosis['message_long'] ?? $normalized['diagnosis']['message_long']));
        }

        if (!empty($feedback['location']) && is_array($feedback['location'])) {
            $location = $feedback['location'];
            $normalized['location']['line'] = max(0, (int)($location['line'] ?? 0));
            $normalized['location']['column'] = max(0, (int)($location['column'] ?? 0));
            $normalized['location']['snippet'] = (string)($location['snippet'] ?? '');
        }

        if (!empty($feedback['hints']) && is_array($feedback['hints'])) {
            $hints = [];
            foreach ($feedback['hints'] as $hint) {
                $hinttext = '';
                if (is_string($hint)) {
                    $hinttext = trim($hint);
                } else if (is_array($hint)) {
                    $hinttext = trim((string)($hint['hint'] ?? $hint['text'] ?? $hint['message'] ?? ''));
                }
                if ($hinttext === '') {
                    continue;
                }
                $hints[] = $hinttext;
                if (count($hints) >= 3) {
                    break;
                }
            }
            if (!empty($hints)) {
                $normalized['hints'] = $hints;
            }
        }

        if (array_key_exists('suggested_fix', $feedback)) {
            $suggestedfix = $feedback['suggested_fix'];
            if (is_array($suggestedfix) && !empty($suggestedfix['explanation'])) {
                $normalized['suggested_fix'] = [
                    'explanation' => (string)$suggestedfix['explanation'],
                    'code_patch' => (string)($suggestedfix['code_patch'] ?? ''),
                ];
            } else {
                $normalized['suggested_fix'] = null;
            }
        }

        if (!empty($feedback['recommended_materials']) && is_array($feedback['recommended_materials'])) {
            $materials = [];
            foreach ($feedback['recommended_materials'] as $material) {
                if (!is_array($material) || empty($material['title'])) {
                    continue;
                }
                $materials[] = [
                    'title' => (string)$material['title'],
                    'url' => (string)($material['url'] ?? ''),
                    'reason' => (string)($material['reason'] ?? ''),
                ];
                if (count($materials) >= 3) {
                    break;
                }
            }
            $normalized['recommended_materials'] = $materials;
        }

        if (!empty($feedback['explainability'])) {
            $normalized['explainability'] = (string)$feedback['explainability'];
        } else {
            $normalized['explainability'] = 'Dihasilkan oleh Gemini AI.';
        }

        return $normalized;
    }

    /**
     * Normalize diagnosis category to allowed values.
     *
     * @param string $category
     * @return string
     */
    private static function normalize_diagnosis_category($category) {
        $allowed = ['syntax', 'runtime', 'logic', 'style', 'security', 'performance'];
        $normalized = strtolower(trim((string)$category));
        if (in_array($normalized, $allowed, true)) {
            return $normalized;
        }
        return 'runtime';
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'feedback' => new external_value(PARAM_RAW, 'AI feedback as JSON'),
        ]);
    }
}

