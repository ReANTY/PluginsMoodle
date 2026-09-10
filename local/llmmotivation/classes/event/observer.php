<?php
// This file is part of Moodle - http://moodle.org/

namespace local_llmmotivation\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for local_llmmotivation.
 */
class observer {

    /**
     * Handle quiz attempt_submitted event.
     */
    public static function quiz_attempt_submitted(\mod_quiz\event\attempt_submitted $event): void {
        global $DB;
        try {
            $attempt = $DB->get_record('quiz_attempts', ['id' => $event->objectid]);
            $userid = (int) ($event->relateduserid ?: $event->userid);
            if ($attempt && !empty($attempt->userid)) {
                $userid = (int) $attempt->userid;
            }
            $courseid = (int) $event->courseid;

            if (!\local_llmmotivation\delivery\delivery_system::is_student_user($userid, $courseid)) {
                return;
            }

            $cm = get_coursemodule_from_instance('quiz', (int)$attempt->quiz, $courseid);
            $section_num = 1;
            if ($cm) {
                $sec = $DB->get_record('course_sections', ['id' => $cm->section], 'section');
                if ($sec && (int)$sec->section > 0) {
                    $section_num = (int)$sec->section;
                }
            }

            // Check if all peak activities (Quiz + Assignment) in this section are completed.
            $status = self::check_section_completion_status($userid, $courseid, $section_num);
            if (!empty($status['completed'])) {
                self::trigger_section_completion_motivation($userid, $courseid, $section_num, $status, $attempt);
            }
        } catch (\Throwable $e) {
            debugging('local_llmmotivation observer::quiz_attempt_submitted error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Triggered when student submits an AICode assignment to the teacher.
     */
    public static function assignment_submitted_for_motivation(\stdClass $cm, int $userid): void {
        global $DB;

        try {
            $courseid = (int)$cm->course;
            if (!\local_llmmotivation\delivery\delivery_system::is_student_user($userid, $courseid)) {
                return;
            }

            $sec = $DB->get_record('course_sections', ['id' => $cm->section], 'section');
            $section_num = $sec ? (int)$sec->section : 1;

            // Check if all peak activities (Quiz + Assignment) in this section are completed.
            $status = self::check_section_completion_status($userid, $courseid, $section_num);
            if (!empty($status['completed'])) {
                self::trigger_section_completion_motivation($userid, $courseid, $section_num, $status);
            }
        } catch (\Throwable $e) {
            debugging('local_llmmotivation observer::assignment_submitted_for_motivation error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Handle activity completion updated.
     */
    public static function activity_completed(\core\event\course_module_completion_updated $event): void {
        // Reserved for general tracking.
    }

    /**
     * Check if all required peak activities (Weekly Quiz & Weekly Assignment) in a section are completed by student.
     */
    public static function check_section_completion_status(int $userid, int $courseid, int $section_num): array {
        global $DB;

        $result = [
            'completed' => false,
            'has_quiz' => false,
            'quiz_completed' => false,
            'quiz_grade' => null,
            'quiz_name' => '',
            'has_assignment' => false,
            'assignment_completed' => false,
            'assignment_name' => '',
        ];

        try {
            $sec = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $section_num]);
            if (!$sec) {
                return $result;
            }

            $sql = "SELECT cm.id, cm.instance, m.name AS modname
                      FROM {course_modules} cm
                      JOIN {modules} m ON m.id = cm.module
                     WHERE cm.course = :courseid AND cm.section = :secid
                  ORDER BY cm.id ASC";
            $cms = $DB->get_records_sql($sql, ['courseid' => $courseid, 'secid' => $sec->id]);

            $weekly_quiz_cm = null;
            $weekly_assign_cm = null;
            $low_quizzes = [];
            $all_quizzes = [];
            $incomplete_readings = [];
            $completed_readings = [];

            foreach ($cms as $cm) {
                $inst = $DB->get_record($cm->modname, ['id' => $cm->instance], 'id, name');
                if (!$inst) continue;
                $name = $inst->name;

                if ($cm->modname === 'quiz') {
                    $attempts = $DB->get_records_select(
                        'quiz_attempts',
                        'quiz = :quizid AND userid = :userid AND state = :state',
                        ['quizid' => $cm->instance, 'userid' => $userid, 'state' => 'finished'],
                        'timemodified DESC',
                        '*',
                        0,
                        1
                    );
                    $grade = null;
                    $duration_sec = 0;
                    if (!empty($attempts)) {
                        $att = reset($attempts);
                        $quiz = $DB->get_record('quiz', ['id' => $cm->instance]);
                        if ($quiz && (float)$quiz->sumgrades > 0 && isset($att->sumgrades)) {
                            $grade = round(((float)$att->sumgrades / (float)$quiz->sumgrades) * 100.0, 1);
                        }
                        if (!empty($att->timefinish) && !empty($att->timestart)) {
                            $duration_sec = (int)($att->timefinish - $att->timestart);
                        }
                    }

                    $is_weekly = (stripos($name, 'Weekly Quiz') !== false);
                    if ($is_weekly || $weekly_quiz_cm === null) {
                        $weekly_quiz_cm = $cm;
                        $weekly_quiz_cm->instancename = $name;
                        $weekly_quiz_cm->calculated_grade = $grade;
                        $weekly_quiz_cm->duration_sec = $duration_sec;
                    }

                    if ($grade !== null) {
                        $all_quizzes[] = "{$name} ({$grade}%)";
                        if ($grade < 70.0) {
                            $low_quizzes[] = "{$name} ({$grade}%)";
                        }
                    } else if (!$is_weekly) {
                        $low_quizzes[] = "{$name} (belum selesai)";
                    }
                } else if ($cm->modname === 'aicode') {
                    if (stripos($name, 'Weekly Assignment') !== false || stripos($name, 'Final Project') !== false || $weekly_assign_cm === null) {
                        $weekly_assign_cm = $cm;
                        $weekly_assign_cm->instancename = $name;
                    }
                } else if (in_array($cm->modname, ['page', 'resource', 'url'])) {
                    $is_done = false;
                    if ($cm->completion > 0) {
                        $comp = $DB->get_record('course_modules_completion', [
                            'coursemoduleid' => $cm->id,
                            'userid' => $userid,
                        ]);
                        $is_done = ($comp && $comp->completionstate > 0);
                    }
                    if ($is_done) {
                        $completed_readings[] = $name;
                    } else {
                        $incomplete_readings[] = $name;
                    }
                }
            }

            if ($weekly_quiz_cm) {
                $result['has_quiz'] = true;
                $result['quiz_name'] = $weekly_quiz_cm->instancename;
                if (isset($weekly_quiz_cm->calculated_grade) && $weekly_quiz_cm->calculated_grade !== null) {
                    $result['quiz_completed'] = true;
                    $result['quiz_grade'] = $weekly_quiz_cm->calculated_grade;
                    $result['duration_seconds'] = $weekly_quiz_cm->duration_sec ?? 0;
                }
            }

            $result['low_quizzes'] = $low_quizzes;
            $result['incomplete_readings'] = $incomplete_readings;
            $result['completed_readings_count'] = count($completed_readings);
            $result['total_readings_count'] = count($completed_readings) + count($incomplete_readings);

            if ($weekly_assign_cm) {
                $result['has_assignment'] = true;
                $result['assignment_name'] = $weekly_assign_cm->instancename;

                $has_attempt = $DB->record_exists('aicode_attempts', [
                    'problemid' => $weekly_assign_cm->instance,
                    'userid' => $userid,
                    'teacher_review_requested' => 1,
                ]);

                $has_completion = $DB->record_exists('course_modules_completion', [
                    'coursemoduleid' => $weekly_assign_cm->id,
                    'userid' => $userid,
                    'completionstate' => 1,
                ]);

                if ($has_attempt || $has_completion) {
                    $result['assignment_completed'] = true;
                }
            }

            $quiz_ok = (!$result['has_quiz'] || $result['quiz_completed']);
            $assign_ok = (!$result['has_assignment'] || $result['assignment_completed']);
            $result['completed'] = ($quiz_ok && $assign_ok && ($result['has_quiz'] || $result['has_assignment']));

        } catch (\Throwable $e) {
            debugging('local_llmmotivation check_section_completion_status error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return $result;
    }

    /**
     * Generate and queue a post-section motivational encouragement message when both quiz and assignment are complete.
     */
    public static function trigger_section_completion_motivation(
        int $userid,
        int $courseid,
        int $section_num,
        array $status,
        ?object $attempt = null
    ): void {
        global $DB;

        try {
            $already_has_pending = $DB->record_exists_select(
                'acmls_learner_record',
                "userid = :userid AND courseid = :courseid AND record_type = 'pending_quiz_motivation' AND data_payload LIKE :sec",
                ['userid' => $userid, 'courseid' => $courseid, 'sec' => '%"section":' . $section_num . '%']
            );
            $already_has_delivered = $DB->record_exists_select(
                'acmls_learner_record',
                "userid = :userid AND courseid = :courseid AND record_type = 'delivered_quiz_motivation' AND data_payload LIKE :sec",
                ['userid' => $userid, 'courseid' => $courseid, 'sec' => '%"section":' . $section_num . '%']
            );
            $already_has_feedback = $DB->record_exists('acmls_motivation_feedback', [
                'userid' => $userid,
                'courseid' => $courseid,
                'category' => 'post_section_' . $section_num,
            ]);

            if ($already_has_pending || $already_has_delivered || $already_has_feedback) {
                return;
            }

            $quizgrade = $status['quiz_grade'] ?? null;
            if ($quizgrade !== null) {
                if ($quizgrade >= 70.0) {
                    $category = 'achievement';
                } else if ($quizgrade >= 40.0) {
                    $category = 'reinforcement';
                } else {
                    $category = 'recovery';
                }
            } else {
                $category = 'reinforcement';
            }

            $profile = null;
            if (class_exists('\local_llmmotivation\profiling\profiling_system')) {
                $profiler = new \local_llmmotivation\profiling\profiling_system();
                $metrics = [];
                if ($quizgrade !== null) {
                    $metrics['score'] = $quizgrade;
                }
                $profile = $profiler->update_profile($userid, $courseid, $metrics);
            }

            $user = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname');
            $student_name = $user ? trim($user->firstname) : '';
            $quiz_name = !empty($status['quiz_name']) ? $status['quiz_name'] : 'Kuis Mingguan';
            $assign_name = !empty($status['assignment_name']) ? $status['assignment_name'] : 'Tugas Mingguan';

            $low_quizzes = $status['low_quizzes'] ?? [];
            $incomplete_readings = $status['incomplete_readings'] ?? [];
            $completed_readings_count = $status['completed_readings_count'] ?? 0;
            $total_readings_count = $status['total_readings_count'] ?? 0;

            // Retrieve pre-emotion checkin data if available
            $pre_records = $DB->get_records_select(
                'acmls_motivation_feedback',
                'userid = :userid AND courseid = :courseid AND category = :cat',
                ['userid' => $userid, 'courseid' => $courseid, 'cat' => 'checkin_section_' . $section_num],
                'id DESC',
                '*',
                0,
                1
            );
            $pre_emotion = !empty($pre_records) ? reset($pre_records) : null;

            $emotion_summary = '';
            if ($pre_emotion) {
                $emotion_summary = "Kesiapan awal: Motivasi {$pre_emotion->e1}/5, Percaya diri {$pre_emotion->e2}/5, Kesiapan praktik {$pre_emotion->e3}/5.";
                if (!empty($pre_emotion->reflection_note)) {
                    $emotion_summary .= " Refleksi: \"" . $pre_emotion->reflection_note . "\".";
                }
            }

            $duration_sec = (int)($status['duration_seconds'] ?? 0);
            $duration_text = '';
            if ($duration_sec > 0) {
                $m = floor($duration_sec / 60);
                $s = $duration_sec % 60;
                $duration_text = ($m > 0) ? "{$m} menit {$s} detik" : "{$s} detik";
            }

            $context_data = [
                'student_name' => $student_name,
                'quiz_name' => $quiz_name,
                'quiz_grade' => $quizgrade,
                'duration_seconds' => $duration_sec,
                'duration_text' => $duration_text,
                'assignment_name' => $assign_name,
                'assignment_submitted' => true,
                'section_num' => $section_num,
                'low_quizzes' => $low_quizzes,
                'low_quizzes_summary' => !empty($low_quizzes) ? implode(', ', array_slice($low_quizzes, 0, 3)) : '',
                'incomplete_readings' => $incomplete_readings,
                'incomplete_readings_summary' => !empty($incomplete_readings) ? implode(', ', array_slice($incomplete_readings, 0, 3)) : '',
                'completed_readings_count' => $completed_readings_count,
                'total_readings_count' => $total_readings_count,
                'emotion_summary' => $emotion_summary,
            ];

            $message = [];

            // Check Gemini API key from local_llmmotivation
            $apikey = (string) get_config('local_llmmotivation', 'gemini_apikey');
            if (!empty($apikey) && class_exists('\local_llmmotivation\motivation\llm_preparation')) {
                try {
                    $repo = new \local_llmmotivation\motivation\motivation_sentence_repository();
                    $generator = \local_llmmotivation\motivation\llm_preparation::build_from_config($repo);
                    $message = $generator->generate_encouragement_record($profile, $category, $context_data);
                    if ($quizgrade !== null && method_exists($generator, 'generate_suggestion')) {
                        $llm_sugg = $generator->generate_suggestion($profile, $category, $quizgrade, $context_data);
                        if (!empty($llm_sugg)) {
                            $message['suggestion'] = $llm_sugg;
                        }
                    }
                } catch (\Throwable $ex) {
                    debugging('local_llmmotivation LLM error: ' . $ex->getMessage(), DEBUG_DEVELOPER);
                }
            }

            // Fallback message with rich personalized learning data
            if (empty($message['content'])) {
                $greeting = !empty($student_name) ? "Halo {$student_name}! " : "Halo! ";
                $grade_info = ($quizgrade !== null) ? "dengan raihan nilai {$quizgrade}% pada {$quiz_name}" : "pada {$quiz_name}";

                $detail_notes = [];
                if (!empty($low_quizzes)) {
                    $detail_notes[] = "bagian yang perlu kamu ulas kembali: " . implode(', ', array_slice($low_quizzes, 0, 2));
                }
                if (!empty($incomplete_readings)) {
                    $detail_notes[] = "modul bacaan yang belum tuntas: " . implode(', ', array_slice($incomplete_readings, 0, 2));
                }

                $detail_text = "";
                if (!empty($detail_notes)) {
                    $detail_text = " Catatan belajarmu: " . implode('; ', $detail_notes) . ".";
                } else {
                    $detail_text = " Hebat sekali, seluruh materi bacaan dan kuis latihan minggu ini berhasil kamu selesaikan secara optimal!";
                }

                $message = [
                    'content' => $greeting . "Luar biasa! Kamu telah menuntaskan rangkaian aktivitas di Minggu {$section_num} {$grade_info}, serta berhasil mengirimkan {$assign_name} ke guru.{$detail_text} Terus pertahankan semangat dan konsistensi belajarmu!",
                    'category' => $category,
                    'source' => 'system',
                ];
            }

            $suggestion = !empty($message['suggestion'])
                ? (string) $message['suggestion']
                : \local_llmmotivation\delivery\delivery_system::get_adaptive_suggestion($profile, $category, $quizgrade, $context_data);

            $pending = new \stdClass();
            $pending->userid = $userid;
            $pending->courseid = $courseid;
            $pending->record_type = 'pending_quiz_motivation';
            $pending->source_component = 'motivation';
            $pending->data_payload = json_encode([
                'content' => $message['content'],
                'suggestion' => $suggestion,
                'category' => $message['category'] ?? $category,
                'source' => $message['source'] ?? 'system',
                'quizgrade' => $quizgrade,
                'section' => $section_num,
                'quizname' => $quiz_name,
                'assignmentname' => $assign_name,
                'timecreated' => time(),
            ]);
            $pending->profile_version = $profile ? (int) $profile->profile_version : 0;
            $pending->timecreated = time();

            $DB->insert_record('acmls_learner_record', $pending);

        } catch (\Throwable $e) {
            debugging('local_llmmotivation observer::trigger_section_completion_motivation error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
