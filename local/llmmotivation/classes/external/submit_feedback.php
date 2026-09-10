<?php
// This file is part of Moodle - http://moodle.org/

namespace local_llmmotivation\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External API for submitting emotion and motivation feedback.
 */
class submit_feedback extends \external_api {

    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'userid' => new \external_value(PARAM_INT, 'Moodle user ID'),
            'courseid' => new \external_value(PARAM_INT, 'Moodle course ID'),
            'sentenceid' => new \external_value(PARAM_INT, 'Sentence repository ID', VALUE_DEFAULT, 0),
            'category' => new \external_value(PARAM_TEXT, 'Motivation category'),
            'source' => new \external_value(PARAM_TEXT, 'Message source'),
            'message_content' => new \external_value(PARAM_TEXT, 'Delivered message content'),
            'e1' => new \external_value(PARAM_INT, 'Motivation rating (1-5)'),
            'e2' => new \external_value(PARAM_INT, 'Confidence rating (1-5)'),
            'e3' => new \external_value(PARAM_INT, 'Support rating (1-5)'),
            'reflection_note' => new \external_value(PARAM_TEXT, 'Optional reflection note', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(
        int $userid,
        int $courseid,
        int $sentenceid = 0,
        string $category = '',
        string $source = '',
        string $message_content = '',
        int $e1 = 0,
        int $e2 = 0,
        int $e3 = 0,
        string $reflection_note = ''
    ): array {
        global $USER, $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'courseid' => $courseid,
            'sentenceid' => $sentenceid,
            'category' => $category,
            'source' => $source,
            'message_content' => $message_content,
            'e1' => $e1,
            'e2' => $e2,
            'e3' => $e3,
            'reflection_note' => $reflection_note,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        if ((int) $USER->id !== (int) $params['userid']) {
            throw new \moodle_exception('accessdenied', 'error');
        }

        try {
            $feedback = new \stdClass();
            $feedback->userid = (int)$params['userid'];
            $feedback->courseid = (int)$params['courseid'];
            $feedback->sentenceid = $params['sentenceid'] > 0 ? (int)$params['sentenceid'] : null;
            $feedback->category = (string)$params['category'];
            $feedback->source = (string)$params['source'];
            $feedback->message_content = (string)$params['message_content'];
            $feedback->e1 = (int)$params['e1'];
            $feedback->e2 = (int)$params['e2'];
            $feedback->e3 = (int)$params['e3'];
            $feedback->reflection_note = (string)$params['reflection_note'];
            $feedback->timecreated = time();

            $feedbackid = $DB->insert_record('acmls_motivation_feedback', $feedback);

            // Update learner profile if available.
            if (class_exists('\local_llmmotivation\profiling\profiling_system')) {
                $profiler = new \local_llmmotivation\profiling\profiling_system();
                $profiler->update_profile((int)$params['userid'], (int)$params['courseid'], [
                    'e1' => (int)$params['e1'],
                    'e2' => (int)$params['e2'],
                    'e3' => (int)$params['e3'],
                ]);
            }

            return [
                'success' => true,
                'message' => '',
                'feedbackid' => (int)$feedbackid,
            ];
        } catch (\Throwable $e) {
            debugging('local_llmmotivation submit_feedback error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'feedbackid' => 0,
            ];
        }
    }

    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Success indicator'),
            'message' => new \external_value(PARAM_TEXT, 'Error message if any'),
            'feedbackid' => new \external_value(PARAM_INT, 'Feedback record ID'),
        ]);
    }
}
