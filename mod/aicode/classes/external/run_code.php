<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * External API for running code
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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * External API for running code
 */
class run_code extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'problemid' => new external_value(PARAM_INT, 'Problem ID'),
            'code' => new external_value(PARAM_RAW, 'Code to execute'),
            'language' => new external_value(PARAM_ALPHA, 'Programming language'),
            'sesskey' => new external_value(PARAM_RAW, 'Session key', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute code
     *
     * @param int $problemid
     * @param string $code
     * @param string $language
     * @param string $sesskey
     * @return array
     */
    public static function execute($problemid, $code, $language, $sesskey) {
        global $DB, $USER, $CFG, $PAGE;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'problemid' => $problemid,
            'code' => $code,
            'language' => $language,
            'sesskey' => $sesskey,
        ]);

        // Validate session key if provided.
        if (!empty($params['sesskey']) && !confirm_sesskey($params['sesskey'])) {
            throw new \moodle_exception('invalidsesskey');
        }

        // Get problem and validate access.
        $problem = $DB->get_record('aicode', ['id' => $params['problemid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('aicode', $problem->id);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        $PAGE->set_context($context);

        require_capability('mod/aicode:submit', $context);

        // Validate input length.
        if (strlen($params['code']) > 50000) {
            throw new \moodle_exception('Code too long (max 50KB)');
        }

        // Get executor URL from config.
        $executorurl = get_config('aicode', 'executor_url');
        if (empty($executorurl)) {
            $executorurl = 'http://127.0.0.1:3001';
        }

        // Prepare request payload.
        $payload = [
            'code' => $params['code'],
            'language' => $params['language'],
            'testcase' => json_decode($problem->testcases ?? '[]', true),
        ];

        // Send to executor service.
        $curl = new \curl(['ignoresecurity' => true]);
        $curl->setHeader(['Content-Type: application/json']);
        $response = $curl->post($executorurl . '/run', json_encode($payload));

        if ($curl->get_errno()) {
            throw new \moodle_exception('Executor service unavailable: ' . $curl->error);
        }

        $result = null;
        if (is_string($response) && trim($response) !== '') {
            $result = json_decode($response, true);
        }
        if (!is_array($result)) {
            $result = [
                'stdout' => '',
                'stderr' => 'Executor returned empty or invalid response.',
                'exitCode' => 1,
                'trace' => '',
            ];
        }

        // Store attempt (anonymized if configured).
        $attempt = new \stdClass();
        $attempt->problemid = $params['problemid'];
        $attempt->userid = $problem->allow_training ? null : $USER->id;
        $attempt->is_anonymous = $problem->allow_training ? 1 : 0;
        $attempt->code_hash = hash('sha256', $params['code']);
        $attempt->result_json = json_encode([
            'exitCode' => $result['exitCode'] ?? -1,
            'stdout' => substr($result['stdout'] ?? '', 0, 1000),
            'stderr' => substr($result['stderr'] ?? '', 0, 1000),
        ]);
        $attempt->timecreated = time();
        $DB->insert_record('aicode_attempts', $attempt);

        return [
            'result' => json_encode($result),
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'result' => new external_value(PARAM_RAW, 'Execution result as JSON'),
        ]);
    }
}

