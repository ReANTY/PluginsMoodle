<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Render popups content and register required AMD scripts.
 *
 * @return string HTML to append to the footer.
 */
function local_llmmotivation_render_popups(): string {
    global $USER, $COURSE, $PAGE, $CFG;

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

    // Injeksi kontrol tampilan adaptif (left drawer course index & main section)
    $adaptive_html = '';
    $adaptive_lib = $CFG->dirroot . '/blocks/adaptive_learning_ai/lib.php';
    if (file_exists($adaptive_lib)) {
        require_once($adaptive_lib);
        $adaptive_html = block_adaptive_learning_ai_render_adaptive_view($courseid);
    }

    // Role check: Only students (or admin testing/previewing as student) see the popups.
    if (!\local_llmmotivation\delivery\delivery_system::is_student_user((int)$USER->id, $courseid)) {
        return $adaptive_html;
    }

    try {
        $delivery = new \local_llmmotivation\delivery\delivery_system();
        $pending_quiz_mot = $delivery->get_pending_quiz_motivation((int)$USER->id, $courseid);
        $emotion_state = $delivery->resolve_emotion_checkin_state((int)$USER->id, $courseid, $pending_quiz_mot);

        $has_emotion = !empty($emotion_state['show']);
        $has_quiz_mot = !empty($pending_quiz_mot);

        if (!$has_emotion && !$has_quiz_mot) {
            return $adaptive_html;
        }

        $rendered = true;
        $html = '';

        $has_post_emotion = ($has_emotion && ($emotion_state['stage'] ?? '') === 'post');

        if ($has_quiz_mot) {
            $html .= $delivery->display_quiz_motivation((int)$USER->id, $courseid, $pending_quiz_mot, $has_post_emotion);
        }

        if ($has_emotion) {
            // Emotion check-in (both initial pre and post-section evaluation) must ALWAYS appear immediately first!
            $html .= $delivery->display_emotion_checkin((int)$USER->id, $courseid, $emotion_state, false);
        }


        // Initialize AMD JavaScript for popup actions
        $PAGE->requires->js_call_amd('local_llmmotivation/main', 'init', [
            (int)$USER->id,
            $courseid
        ]);

        return $adaptive_html . $html;

    } catch (\Throwable $e) {
        debugging('local_llmmotivation render_popups error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return $adaptive_html;
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

/**
 * Callback after require_login.
 * Mencegah siswa mengakses materi dan kuis pada tingkatan level lain secara langsung melalui URL.
 *
 * @param mixed $courseorid
 * @param mixed $autologinguest
 * @param mixed $cm
 * @param mixed $setwantsurltome
 * @param mixed $preventredirect
 */
function local_llmmotivation_after_require_login($courseorid = null, $autologinguest = null, $cm = null, $setwantsurltome = null, $preventredirect = null) {
    global $USER, $CFG;

    if (empty($cm) || empty($USER->id) || isguestuser() || is_siteadmin()) {
        return;
    }

    $cmid = is_object($cm) ? (int)$cm->id : (int)$cm;
    if ($cmid <= 0) {
        return;
    }

    $courseid = 0;
    if (is_object($courseorid)) {
        $courseid = (int)$courseorid->id;
    } else if (is_numeric($courseorid) && $courseorid > 0) {
        $courseid = (int)$courseorid;
    } else if (is_object($cm) && !empty($cm->course)) {
        $courseid = (int)$cm->course;
    }

    if ($courseid <= 1) {
        return;
    }

    $path_manager_file = $CFG->dirroot . '/blocks/adaptive_learning_ai/classes/path_manager.php';
    if (!file_exists($path_manager_file)) {
        return;
    }
    require_once($path_manager_file);

    // Guru dan admin memiliki akses penuh untuk preview/grading
    if (\block_adaptive_learning_ai\path_manager::is_teacher_or_admin($courseid)) {
        return;
    }

    // Periksa apakah modul ini disembunyikan dari siswa saat ini
    $hidden_info = \block_adaptive_learning_ai\path_manager::get_hidden_cmids_for_user($courseid, (int)$USER->id);
    if (isset($hidden_info[$cmid])) {
        $info = $hidden_info[$cmid];
        $student_level = $info['student_level'] ?? 'Dasar';
        $mod_level = $info['mod_level'] ?? 'Lain';

        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            throw new \moodle_exception('nopermissions', 'error', '', 'Aktivitas adaptif ini berada di luar jalur belajar Anda.');
        }

        $warning_message = "Aktivitas ini berada pada tingkat [{$mod_level}] yang bukan merupakan jalur belajar adaptif Anda minggu ini ([{$student_level}]). Silakan pelajari materi dan kerjakan kuis sesuai tingkatan Anda.";
        redirect(new \moodle_url('/course/view.php', ['id' => $courseid]), $warning_message, null, \core\output\notification::NOTIFY_WARNING);
    }
}
