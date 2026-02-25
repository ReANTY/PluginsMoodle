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

        // Check cache.
        $payloadhash = hash('sha256', 'feedback-v3|' . $params['code'] . $params['stderr'] . $params['trace']);
        $cachettl = get_config('aicode', 'cache_ttl') ?: 3600;
        $cached = $DB->get_record('aicode_cache', ['payload_hash' => $payloadhash]);

        if ($cached && ($cached->timecreated + $cachettl) > time() && !empty($cached->ai_response_json)) {
            $cachedfeedback = json_decode($cached->ai_response_json, true);
            $cachedexplainability = '';
            if (is_array($cachedfeedback)) {
                $cachedexplainability = (string)($cachedfeedback['explainability'] ?? '');
            }
            if (!self::is_provider_failure_explainability($cachedexplainability)) {
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
            (string)$params['trace']
        );
        if (!is_array($feedback)) {
            $feedback = self::get_fallback_feedback($params['stderr']);
        }

        // Validate confidence threshold.
        $threshold = get_config('aicode', 'confidence_threshold') ?: 0.6;
        if (isset($feedback['diagnosis']['confidence']) && $feedback['diagnosis']['confidence'] < $threshold) {
            // Fall back to rule-based response.
            $feedback = self::get_fallback_feedback($params['stderr']);
            $feedback['explainability'] = 'Moodle AI response below confidence threshold.';
        }

        $feedbackjson = json_encode($feedback);
        if ($feedbackjson === false) {
            $feedback = self::get_fallback_feedback($params['stderr']);
            $feedback['explainability'] = 'Fallback used because JSON encoding failed.';
            $feedbackjson = json_encode($feedback);
        }

        $explainability = (string)($feedback['explainability'] ?? '');
        $shouldcache = !self::is_provider_failure_explainability($explainability);

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
     * @return array
     */
    private static function get_feedback_from_moodle_ai($contextid, $userid, $code, $stderr, $trace) {
        if (!class_exists(manager::class) || !class_exists(generate_text::class)) {
            $feedback = self::get_fallback_feedback($stderr);
            $feedback['explainability'] = 'Moodle AI subsystem is not available.';
            return $feedback;
        }

        try {
            $aimanager = \core\di::get(manager::class);
            if (!$aimanager->is_action_available(generate_text::class)) {
                $feedback = self::get_fallback_feedback($stderr);
                $feedback['explainability'] = 'No Moodle AI provider is available for text generation.';
                return $feedback;
            }

            $prompt = self::build_ai_feedback_prompt($code, $stderr, $trace);
            $action = new generate_text($contextid, $userid, $prompt);
            $response = $aimanager->process_action($action);

            if (!$response->get_success()) {
                $feedback = self::get_fallback_feedback($stderr);
                $feedback['explainability'] = 'Moodle AI provider error: ' . $response->get_errormessage();
                return $feedback;
            }

            $responsedata = $response->get_response_data();
            $generatedcontent = trim((string)($responsedata['generatedcontent'] ?? ''));
            $feedback = self::extract_feedback_json($generatedcontent);

            if (!is_array($feedback) || empty($feedback['diagnosis'])) {
                $fallback = self::get_fallback_feedback($stderr);
                $fallback['explainability'] = 'Moodle AI response format was invalid.';
                return $fallback;
            }

            $normalized = self::normalize_feedback($feedback, $stderr);
            $modelused = $response->get_model_used();
            if (!empty($modelused)) {
                $normalized['explainability'] = trim($normalized['explainability'] . ' (model: ' . $modelused . ')');
            }

            return $normalized;
        } catch (\Throwable $e) {
            $feedback = self::get_fallback_feedback($stderr);
            $feedback['explainability'] = 'Moodle AI request failed: ' . $e->getMessage();
            return $feedback;
        }
    }

    /**
     * Build AI prompt for strict JSON diagnostic response.
     *
     * @param string $code
     * @param string $stderr
     * @param string $trace
     * @return string
     */
    private static function build_ai_feedback_prompt($code, $stderr, $trace) {
        return "You are an expert JavaScript tutor for beginners.\n"
            . "Analyze the student's code and execution errors.\n"
            . "Return ONLY a valid JSON object (no markdown, no backticks, no explanation outside JSON).\n\n"
            . "Required JSON schema:\n"
            . "{\n"
            . "  \"diagnosis\": {\n"
            . "    \"category\": \"syntax|runtime|logic|style|security|performance\",\n"
            . "    \"confidence\": 0.0,\n"
            . "    \"message_short\": \"short sentence\",\n"
            . "    \"message_long\": \"2-3 sentences for beginner\"\n"
            . "  },\n"
            . "  \"location\": {\n"
            . "    \"line\": 0,\n"
            . "    \"column\": 0,\n"
            . "    \"snippet\": \"relevant snippet\"\n"
            . "  },\n"
            . "  \"hints\": [\n"
            . "    \"best actionable guidance\",\n"
            . "    \"deeper explanation to avoid repeating the same mistake\"\n"
            . "  ],\n"
            . "  \"suggested_fix\": {\n"
            . "    \"explanation\": \"step-by-step what to change and why\",\n"
            . "    \"code_patch\": \"minimal corrected code snippet\"\n"
            . "  },\n"
            . "  \"recommended_materials\": [\n"
            . "    {\"title\": \"resource title\", \"url\": \"https://example.com\", \"reason\": \"why this helps\"}\n"
            . "  ],\n"
            . "  \"explainability\": \"brief reason for diagnosis\"\n"
            . "}\n\n"
            . "Rules:\n"
            . "- If location is unknown, use line 0 and column 0.\n"
            . "- Confidence must be between 0 and 1.\n"
            . "- Give detailed but beginner-friendly feedback.\n"
            . "- Hints must not use levels, and must focus on root cause plus prevention.\n"
            . "- Suggested fix should be concrete, practical, and easy to apply.\n"
            . "- Recommended materials should be trustworthy, with real URLs when possible.\n"
            . "- If no reliable code patch, set suggested_fix to null.\n\n"
            . "Student code:\n"
            . $code . "\n\n"
            . "stderr:\n"
            . $stderr . "\n\n"
            . "trace:\n"
            . $trace . "\n";
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
     * Determine whether explainability indicates provider-side AI failure.
     *
     * @param string $explainability
     * @return bool
     */
    private static function is_provider_failure_explainability($explainability) {
        if ($explainability === '') {
            return false;
        }
        $prefixes = [
            'Moodle AI request failed:',
            'Moodle AI provider error:',
            'No Moodle AI provider is available',
            'Moodle AI subsystem is not available.',
            'Moodle AI response format was invalid.',
        ];
        foreach ($prefixes as $prefix) {
            if (strpos($explainability, $prefix) === 0) {
                return true;
            }
        }
        return false;
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
     * Normalize model feedback into stable schema for frontend.
     *
     * @param array $feedback
     * @param string $stderr
     * @return array
     */
    private static function normalize_feedback($feedback, $stderr) {
        $normalized = self::get_fallback_feedback($stderr);

        if (!empty($feedback['diagnosis']) && is_array($feedback['diagnosis'])) {
            $diagnosis = $feedback['diagnosis'];
            $normalized['diagnosis']['category'] = (string)($diagnosis['category'] ?? $normalized['diagnosis']['category']);
            $normalized['diagnosis']['confidence'] = max(0.0, min(1.0, (float)($diagnosis['confidence'] ?? $normalized['diagnosis']['confidence'])));
            $normalized['diagnosis']['message_short'] = (string)($diagnosis['message_short'] ?? $normalized['diagnosis']['message_short']);
            $normalized['diagnosis']['message_long'] = (string)($diagnosis['message_long'] ?? $normalized['diagnosis']['message_long']);
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
            $normalized['explainability'] = 'Generated by Moodle AI provider.';
        }

        return $normalized;
    }

    /**
     * Get fallback rule-based feedback
     *
     * @param string $stderr
     * @return array
     */
    private static function get_fallback_feedback($stderr) {
        $errortext = trim((string)$stderr);
        $firstline = self::extract_first_error_line($errortext);
        $location = self::extract_location_from_stderr($errortext);
        $category = 'runtime';
        $message = 'A runtime error happened while your code was running.';
        $messagelong = 'Your code runs, but it fails during execution. Focus on the first error line, check variable values, and verify scope and data type at that exact point.';
        $hints = [
            'Read only the first error line first, then inspect the related code block.',
            'Check variable names and values right before the failing line with console.log.',
            'Test your code in small steps so you can isolate where the wrong value appears.',
        ];
        $suggestedfix = [
            'explanation' => 'Review the failing line and the lines before it. Make sure every variable is declared and has the expected type before you use it.',
            'code_patch' => "console.log('debug value:', value);\n// Verify value exists and has the expected type before using it.",
        ];
        $materials = [
            [
                'title' => 'MDN JavaScript guide: Debugging',
                'url' => 'https://developer.mozilla.org/en-US/docs/Learn_web_development/Core/Scripting/Debugging_JavaScript',
                'reason' => 'Step-by-step debugging process for beginners.',
            ],
            [
                'title' => 'MDN console.log() reference',
                'url' => 'https://developer.mozilla.org/en-US/docs/Web/API/console/log_static',
                'reason' => 'Shows how to inspect values while your code runs.',
            ],
        ];

        if (strpos($errortext, 'SyntaxError') !== false) {
            $category = 'syntax';
            $message = 'There is a syntax error in your code.';
            $messagelong = 'JavaScript cannot parse your code structure. This usually means missing or extra brackets, commas, quotes, or parentheses.';
            $hints = [
                'Check the line before the reported error because syntax issues often start earlier.',
                'Make sure every opening bracket, brace, parenthesis, and quote has a closing pair.',
                'Write short statements first, run again, then add complexity gradually.',
            ];
            $suggestedfix = [
                'explanation' => 'Fix unmatched symbols and split long expressions into smaller lines. This makes parse errors easier to catch.',
                'code_patch' => "if (condition) {\n  doSomething();\n}\n// Ensure brackets and punctuation are balanced.",
            ];
            $materials = [
                [
                    'title' => 'MDN SyntaxError reference',
                    'url' => 'https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/SyntaxError',
                    'reason' => 'Explains common syntax mistakes and how to fix them.',
                ],
                [
                    'title' => 'JavaScript statements and declarations',
                    'url' => 'https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Statements',
                    'reason' => 'Helps you understand correct JavaScript statement structure.',
                ],
            ];
        } else if (strpos($errortext, 'ReferenceError') !== false || preg_match('/\bis not defined\b/i', $errortext)) {
            $category = 'runtime';
            $message = 'A variable is used before it is declared or available in scope.';
            $messagelong = 'The runtime cannot find one of the variable names you are using. This usually happens because of a typo, missing declaration, or scope mismatch.';
            $hints = [
                'Declare variables with const or let before using them.',
                'Use exactly the same variable name everywhere (JavaScript is case-sensitive).',
                'If the variable is created inside a function/block, it cannot be used outside that scope.',
            ];
            $suggestedfix = [
                'explanation' => 'Locate the undefined variable in the error message. Then either declare it before use or replace it with the correct existing variable name.',
                'code_patch' => "const numbers = [1, 2, 3];\nconst total = numbers.reduce((sum, n) => sum + n, 0);\nconsole.log(total);",
            ];
            $materials = [
                [
                    'title' => 'MDN ReferenceError reference',
                    'url' => 'https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/ReferenceError',
                    'reason' => 'Explains why variables are reported as undefined.',
                ],
                [
                    'title' => 'MDN let declaration',
                    'url' => 'https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Statements/let',
                    'reason' => 'Shows correct variable declaration and block scope usage.',
                ],
                [
                    'title' => 'MDN JavaScript scope glossary',
                    'url' => 'https://developer.mozilla.org/en-US/docs/Glossary/Scope',
                    'reason' => 'Builds understanding of local and global scope to prevent repeated mistakes.',
                ],
            ];
        } else if (strpos($errortext, 'TypeError') !== false || preg_match('/cannot read (properties|property) of/i', $errortext)) {
            $category = 'runtime';
            $message = 'A value is used with the wrong data type.';
            $messagelong = 'Your code tries to call a method or access a property on a value that does not support it (often undefined or null).';
            $hints = [
                'Check the real value before using it: console.log(value).',
                'Guard against undefined/null before reading properties.',
                'Confirm the value type matches the method you want to call.',
            ];
            $suggestedfix = [
                'explanation' => 'Validate values before property access to avoid runtime failures.',
                'code_patch' => "if (user && user.name) {\n  console.log(user.name);\n}\n// Guard null/undefined values before use.",
            ];
            $materials = [
                [
                    'title' => 'MDN TypeError reference',
                    'url' => 'https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/TypeError',
                    'reason' => 'Explains common type misuse scenarios.',
                ],
                [
                    'title' => 'MDN Optional chaining',
                    'url' => 'https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Optional_chaining',
                    'reason' => 'Shows safer access patterns for nested properties.',
                ],
            ];
        }

        if ($firstline !== '') {
            $messagelong .= ' Runtime message: ' . $firstline;
        }

        return [
            'diagnosis' => [
                'category' => $category,
                'confidence' => 0.5,
                'message_short' => $message,
                'message_long' => $messagelong,
            ],
            'location' => $location,
            'hints' => array_map(function($hint) {
                return ['hint' => $hint];
            }, $hints),
            'suggested_fix' => $suggestedfix,
            'recommended_materials' => $materials,
            'explainability' => 'Rule-based fallback because AI response was unavailable, invalid, or below confidence threshold.',
        ];
    }

    /**
     * Extract first meaningful error line from stderr.
     *
     * @param string $stderr
     * @return string
     */
    private static function extract_first_error_line($stderr) {
        if ($stderr === '') {
            return '';
        }

        $lines = preg_split('/\R/', $stderr);
        foreach ($lines as $line) {
            $trimmed = trim((string)$line);
            if ($trimmed === '') {
                continue;
            }
            if (strpos($trimmed, 'at ') === 0) {
                continue;
            }
            return substr($trimmed, 0, 280);
        }

        return '';
    }

    /**
     * Extract best-effort line and column from stderr.
     *
     * @param string $stderr
     * @return array
     */
    private static function extract_location_from_stderr($stderr) {
        $line = 0;
        $column = 0;

        if (preg_match('/:(\d+):(\d+)/', $stderr, $matches)) {
            $line = (int)$matches[1];
            $column = (int)$matches[2];
        } else if (preg_match('/line\s+(\d+)/i', $stderr, $matches)) {
            $line = (int)$matches[1];
        }

        return [
            'line' => max(0, $line),
            'column' => max(0, $column),
            'snippet' => '',
        ];
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

