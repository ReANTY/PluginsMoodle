<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Render popups content and register required AMD scripts.
 *
 * @return string HTML to append to the footer.
 */
function local_llmmotivation_render_popups(): string {
    global $USER, $COURSE, $PAGE;

    static $rendered = false;
    if ($rendered) {
        return '';
    }

    if (!isloggedin() || isguestuser()) {
        return '';
    }

    $courseid = 0;
    if (!empty($COURSE) && (int)$COURSE->id > 1) {
        $courseid = (int)$COURSE->id;
    } else if (!empty($PAGE->course) && (int)$PAGE->course->id > 1) {
        $courseid = (int)$PAGE->course->id;
    } else if (!empty($PAGE->context)) {
        $coursecontext = $PAGE->context->get_course_context(false);
        if ($coursecontext && (int)$coursecontext->instanceid > 1) {
            $courseid = (int)$coursecontext->instanceid;
        }
    }

    if ($courseid <= 1) {
        return '';
    }

    // Role check: Only students (or admin testing/previewing as student) see the popups.
    if (!\local_llmmotivation\delivery\delivery_system::is_student_user((int)$USER->id, $courseid)) {
        return '';
    }

    try {
        $delivery = new \local_llmmotivation\delivery\delivery_system();
        $pending_quiz_mot = $delivery->get_pending_quiz_motivation((int)$USER->id, $courseid);
        $emotion_state = $delivery->resolve_emotion_checkin_state((int)$USER->id, $courseid, $pending_quiz_mot);

        $has_emotion = !empty($emotion_state['show']);
        $has_quiz_mot = !empty($pending_quiz_mot);

        if (!$has_emotion && !$has_quiz_mot) {
            return '';
        }

        $rendered = true;
        $html = '';

        $wait_for_emotion = ($has_emotion && ($emotion_state['stage'] ?? '') === 'post');

        if ($has_emotion) {
            $html .= $delivery->display_emotion_checkin((int)$USER->id, $courseid, $emotion_state);
        }

        if ($has_quiz_mot) {
            $html .= $delivery->display_quiz_motivation((int)$USER->id, $courseid, $pending_quiz_mot, $wait_for_emotion);
        }

        // Initialize AMD JavaScript for popup actions
        $PAGE->requires->js_call_amd('local_llmmotivation/main', 'init', [
            (int)$USER->id,
            $courseid
        ]);

        return $html;

    } catch (\Throwable $e) {
        debugging('local_llmmotivation render_popups error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return '';
    }
}

/**
 * Legacy Moodle before_footer callback.
 *
 * @return string HTML to append to footer.
 */
function local_llmmotivation_before_footer(): string {
    return local_llmmotivation_render_popups();
}

/**
 * Legacy Moodle standard_footer_html callback.
 *
 * @return string HTML to append to footer.
 */
function local_llmmotivation_standard_footer_html(): string {
    return local_llmmotivation_render_popups();
}
