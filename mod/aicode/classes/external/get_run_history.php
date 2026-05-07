<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * External API: get student run history from DB
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
 * Returns the last 20 code submissions for the current student on a given problem.
 * Code is extracted from result_json stored by run_code and send_to_teacher.
 */
class get_run_history extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'problemid' => new external_value(PARAM_INT, 'Problem ID'),
            'sesskey'   => new external_value(PARAM_RAW, 'Session key', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * @param int    $problemid
     * @param string $sesskey
     * @return array
     */
    public static function execute($problemid, $sesskey) {
        global $DB, $USER, $PAGE;

        $params = self::validate_parameters(self::execute_parameters(), [
            'problemid' => $problemid,
            'sesskey'   => $sesskey,
        ]);

        if (!empty($params['sesskey']) && !confirm_sesskey($params['sesskey'])) {
            throw new \moodle_exception('invalidsesskey');
        }

        $problem = $DB->get_record('aicode', ['id' => $params['problemid']], '*', MUST_EXIST);
        $cm      = get_coursemodule_from_instance('aicode', $problem->id);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        $PAGE->set_context($context);
        require_capability('mod/aicode:submit', $context);

        // Fetch the 20 most-recent identified attempts (DESC so the LIMIT window is the
        // newest rows), then reverse to chronological order for the history timeline.
        $attempts = $DB->get_records_select(
            'aicode_attempts',
            'problemid = :pid AND userid = :uid',
            ['pid' => $params['problemid'], 'uid' => $USER->id],
            'timecreated DESC',
            'id, result_json, timecreated',
            0, 20
        );
        $attempts = array_reverse($attempts, true);

        $history = [];
        foreach ($attempts as $attempt) {
            $result = json_decode($attempt->result_json ?? '{}', true);
            $code   = isset($result['code']) ? (string)$result['code'] : '';
            if (trim($code) === '') {
                continue;
            }
            $history[] = [
                'code'        => $code,
                'timecreated' => (int)$attempt->timecreated,
                'exitcode'    => isset($result['exitCode']) ? (int)$result['exitCode'] : -1,
            ];
        }

        \mod_aicode\local\activity_log::record(
            $context,
            (int) $params['problemid'],
            (int) $USER->id,
            \mod_aicode\local\activity_log::ACTION_RUN_HISTORY_VIEW,
            ['entries_count' => count($history)],
            $problem
        );

        return ['history' => json_encode($history)];
    }

    /**
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'history' => new external_value(PARAM_RAW, 'JSON array of {code, timecreated, exitcode}'),
        ]);
    }
}
