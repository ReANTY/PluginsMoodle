<?php
// This file is part of Moodle - http://moodle.org/

namespace local_llmmotivation\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External API for recording user interaction with motivation popups.
 */
class record_interaction extends \external_api {

    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'userid'           => new \external_value(PARAM_INT, 'Moodle user ID'),
            'courseid'         => new \external_value(PARAM_INT, 'Moodle course ID'),
            'interaction_type' => new \external_value(PARAM_ALPHANUMEXT, 'Interaction type'),
            'data'             => new \external_value(PARAM_RAW, 'JSON encoded data', VALUE_DEFAULT, '{}'),
        ]);
    }

    public static function execute(int $userid, int $courseid, string $interaction_type, string $data = '{}'): array {
        global $USER, $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'userid'           => $userid,
            'courseid'         => $courseid,
            'interaction_type' => $interaction_type,
            'data'             => $data,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        if ((int) $USER->id !== (int) $params['userid']) {
            throw new \moodle_exception('accessdenied', 'error');
        }

        try {
            if ($params['interaction_type'] === 'quiz_motivation_dismissed') {
                $DB->set_field('acmls_learner_record', 'record_type', 'delivered_quiz_motivation', [
                    'userid' => $params['userid'],
                    'courseid' => $params['courseid'],
                    'record_type' => 'pending_quiz_motivation',
                ]);
            }

            $rec = new \stdClass();
            $rec->userid = (int)$params['userid'];
            $rec->courseid = (int)$params['courseid'];
            $rec->record_type = 'interaction';
            $rec->data_payload = json_encode([
                'interaction_type' => $params['interaction_type'],
                'data' => json_decode($params['data'], true),
            ]);
            $rec->timecreated = time();
            $DB->insert_record('acmls_learner_record', $rec);

            return ['success' => true, 'message' => ''];
        } catch (\Throwable $e) {
            debugging('local_llmmotivation record_interaction error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Success status'),
            'message' => new \external_value(PARAM_TEXT, 'Error message if any'),
        ]);
    }
}
