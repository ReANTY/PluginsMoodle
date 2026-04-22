<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * External API for sending code to teacher
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

/**
 * External API for sending code to teacher
 */
class send_to_teacher extends external_api {

    /**
     * Returns description of method parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'problemid' => new external_value(PARAM_INT, 'Problem ID'),
            'code' => new external_value(PARAM_RAW, 'Code to send'),
            'console_output' => new external_value(PARAM_RAW, 'Console output captured in browser', VALUE_DEFAULT, ''),
            'sesskey' => new external_value(PARAM_RAW, 'Session key', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Send code to teacher
     */
    public static function execute($problemid, $code, $console_output, $sesskey) {
        global $DB, $USER, $PAGE;

        $params = self::validate_parameters(self::execute_parameters(), [
            'problemid' => $problemid,
            'code' => $code,
            'console_output' => $console_output,
            'sesskey' => $sesskey,
        ]);

        if (!empty($params['sesskey']) && !confirm_sesskey($params['sesskey'])) {
            throw new \moodle_exception('invalidsesskey');
        }

        $problem = $DB->get_record('aicode', ['id' => $params['problemid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('aicode', $problem->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        $PAGE->set_context($context);
        require_capability('mod/aicode:submit', $context);

        // In exam mode, only one submission is allowed per student.
        // The dedicated teacher_review_requested column (indexed) makes this an
        // efficient point-lookup — no full-table LIKE scan on result_json needed.
        if (($problem->mode ?? 'training') === 'exam') {
            $alreadysubmitted = $DB->record_exists(
                'aicode_attempts',
                ['problemid' => $params['problemid'], 'userid' => $USER->id, 'teacher_review_requested' => 1]
            );
            if ($alreadysubmitted) {
                return ['success' => false, 'already_submitted' => true];
            }
        }

        // Store as a special attempt marked for teacher review.
        $attempt = new \stdClass();
        $attempt->problemid = $params['problemid'];
        $attempt->userid = $USER->id;
        $attempt->code_hash = hash('sha256', $params['code']);
        $attempt->is_anonymous = 0;
        $attempt->teacher_review_requested = 1;
        $attempt->result_json = json_encode([
            'teacher_review_requested' => true,
            'code' => $params['code'],
            'console_output' => substr($params['console_output'], 0, 5000),
        ]);
        $attempt->timecreated = time();
        $DB->insert_record('aicode_attempts', $attempt);

        return ['success' => true, 'already_submitted' => false];
    }

    /**
     * Returns description of method result value
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'already_submitted' => new external_value(PARAM_BOOL, 'Whether student already submitted once', VALUE_DEFAULT, false),
        ]);
    }
}

