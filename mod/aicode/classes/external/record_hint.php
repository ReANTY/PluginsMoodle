<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * External API for recording hint usage
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
 * External API for recording hint usage
 */
class record_hint extends external_api {

    /**
     * Returns description of method parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'problemid' => new external_value(PARAM_INT, 'Problem ID'),
            'sesskey' => new external_value(PARAM_RAW, 'Session key', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Record hint usage
     */
    public static function execute($problemid, $sesskey) {
        global $DB, $USER, $PAGE;

        $params = self::validate_parameters(self::execute_parameters(), [
            'problemid' => $problemid,
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

        // Find the most recent attempt for this user and problem.
        $latestattempts = $DB->get_records_select(
            'aicode_attempts',
            'problemid = :pid AND userid = :uid',
            ['pid' => $params['problemid'], 'uid' => $USER->id],
            'timecreated DESC',
            '*',
            0, 1
        );
        $attempt = !empty($latestattempts) ? reset($latestattempts) : null;

        if ($attempt) {
            $hints = json_decode($attempt->used_hints_json ?? '[]', true);
            if (!is_array($hints)) {
                $hints = [];
            }
            $hints[] = ['time' => time()];
            $attempt->used_hints_json = json_encode($hints);
            $DB->update_record('aicode_attempts', $attempt);

            \mod_aicode\local\activity_log::record(
                $context,
                (int) $params['problemid'],
                (int) $USER->id,
                \mod_aicode\local\activity_log::ACTION_HINT_RECORDED,
                ['hints_count' => count($hints)],
                $problem
            );
        }

        return ['success' => true];
    }

    /**
     * Returns description of method result value
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
        ]);
    }
}

