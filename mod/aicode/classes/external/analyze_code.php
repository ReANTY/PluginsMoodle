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
use core_ai\aiactions\generate_text;
use core_ai\manager;
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

        // Check rate limiting.
        $maxcalls = get_config('aicode', 'max_calls_per_day') ?: 50;
        $daystart = strtotime('today');
        $count = $DB->count_records_select('aicode_attempts',
            'userid = :userid AND timecreated >= :daystart',
            ['userid' => $USER->id, 'daystart' => $daystart]);

        if ($count >= $maxcalls) {
            throw new \moodle_exception('Rate limit exceeded. Please try again tomorrow.');
        }

        $prompttemplate = self::resolve_ai_prompt_template($problem);

        // Check cache.
        $payloadhash = hash(
            'sha256',
            'feedback-v5|' . hash('sha256', $prompttemplate) . '|' . $params['code'] . $params['stderr'] . $params['trace']
        );
        $cachettl = get_config('aicode', 'cache_ttl') ?: 3600;
        $cached = $DB->get_record('aicode_cache', ['payload_hash' => $payloadhash]);

        if ($cached && ($cached->timecreated + $cachettl) > time() && !empty($cached->ai_response_json)) {
            $cachedfeedback = json_decode($cached->ai_response_json, true);
            if (self::is_success_feedback($cachedfeedback)) {
                return ['feedback' => $cached->ai_response_json];
            }
        }

        // Anonymize code (strip comments with names, emails, etc.).
        $anonymizedcode = self::anonymize_code($params['code']);
        $feedback = self::get_feedback_from_moodle_ai(
            (int)$context->id,
            (int)$USER->id,
            $anonymizedcode,
            (string)$params['stderr'],
            (string)$params['trace'],
            $prompttemplate
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
     * Get AI feedback via Moodle AI subsystem.
     *
     * @param int $contextid
     * @param int $userid
     * @param string $code
     * @param string $stderr
     * @param string $trace
     * @param string $prompttemplate
     * @return array
     */
    private static function get_feedback_from_moodle_ai($contextid, $userid, $code, $stderr, $trace, $prompttemplate) {
        if (!class_exists(manager::class) || !class_exists(generate_text::class)) {
            return self::build_ai_error_feedback(
                'Subsystem Moodle AI tidak tersedia. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                'ai_subsystem_unavailable'
            );
        }

        try {
            $aimanager = \core\di::get(manager::class);
            if (!$aimanager->is_action_available(generate_text::class)) {
                return self::build_ai_error_feedback(
                    'Tidak ada provider Moodle AI untuk generasi teks. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                    'ai_provider_unavailable'
                );
            }

            $prompt = self::build_ai_feedback_prompt($prompttemplate, $code, $stderr, $trace);
            $action = new generate_text($contextid, $userid, $prompt);
            $response = $aimanager->process_action($action);

            if (!$response->get_success()) {
                $reason = trim((string)$response->get_errormessage());
                if ($reason === '') {
                    $reason = 'Provider Moodle AI gagal memproses permintaan.';
                } else {
                    $reason = 'Kesalahan provider Moodle AI: ' . $reason;
                }
                return self::build_ai_error_feedback(
                    $reason . ' Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                    'ai_provider_error'
                );
            }

            $responsedata = $response->get_response_data();
            $generatedcontent = trim((string)($responsedata['generatedcontent'] ?? ''));
            $feedback = self::extract_feedback_json($generatedcontent);

            if (!is_array($feedback)) {
                return self::build_ai_error_feedback(
                    'Format respons Moodle AI tidak valid. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                    'invalid_ai_response_format'
                );
            }

            $normalized = self::normalize_feedback($feedback);
            $modelused = $response->get_model_used();
            if (!empty($modelused)) {
                $normalized['explainability'] = trim($normalized['explainability'] . ' (model: ' . $modelused . ')');
            }

            if (!self::is_success_feedback($normalized)) {
                return self::build_ai_error_feedback(
                    'Respons Moodle AI tidak memenuhi format feedback yang diperlukan. Silakan klik tombol Bantuan lagi beberapa saat lagi.',
                    'invalid_ai_feedback_schema'
                );
            }

            return $normalized;
        } catch (\Throwable $e) {
            $reason = trim((string)$e->getMessage());
            if ($reason === '') {
                $reason = 'Permintaan Moodle AI gagal.';
            } else {
                $reason = 'Permintaan Moodle AI gagal: ' . $reason;
            }
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
     * @return string
     */
    private static function build_ai_feedback_prompt($prompttemplate, $code, $stderr, $trace) {
        $template = trim((string)$prompttemplate);
        if ($template === '') {
            $template = ai_prompt::get_default_template();
        }
        return ai_prompt::render_template($template, (string)$code, (string)$stderr, (string)$trace);
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
            'explainability' => 'Dihasilkan oleh provider Moodle AI.',
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
                $hints[] = [
                    'hint' => $hinttext,
                ];
                if (count($hints) >= 5) {
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
            $normalized['explainability'] = 'Dihasilkan oleh provider Moodle AI.';
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

