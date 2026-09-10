<?php
// This file is part of Moodle - http://moodle.org/

namespace local_llmmotivation\delivery;

defined('MOODLE_INTERNAL') || die();

/**
 * Delivery system for local_llmmotivation.
 */
class delivery_system {

    const TABLE_LEARNER_RECORD  = 'acmls_learner_record';
    const TABLE_LEARNER_PROFILE = 'acmls_learner_profile';

    /**
     * Check if a user is a student in the course.
     * Supports Moodle's built-in "Switch role to... Student" and admin preview mode.
     */
    public static function is_student_user(int $userid, int $courseid): bool {
        global $DB, $USER;
        try {
            $context = \context_course::instance($courseid);

            // 1. Allow testing if Admin/Teacher switched role to Student in Moodle UI
            if (is_role_switched($courseid)) {
                $switched_role = $USER->access['rsw'][$context->path] ?? 0;
                if ($switched_role > 0) {
                    $role = $DB->get_record('role', ['id' => $switched_role]);
                    if ($role && ($role->shortname === 'student' || $role->archetype === 'student')) {
                        return true;
                    }
                }
            }

            // 2. Allow testing if admin preview mode is enabled in config or via URL parameter ?preview_motivation=1
            $allow_preview = (bool) get_config('local_llmmotivation', 'admin_preview');
            if ($allow_preview || optional_param('preview_motivation', 0, PARAM_INT) == 1) {
                if (is_siteadmin($userid) || has_capability('moodle/course:update', $context, $userid)) {
                    return true;
                }
            }

            // Normal teacher/admin check: hide popups during regular administrative work
            if (is_siteadmin($userid)) {
                return false;
            }
            if (has_capability('moodle/course:manageactivities', $context, $userid) ||
                has_capability('moodle/course:update', $context, $userid)) {
                return false;
            }

            // Active enrolled student
            return is_enrolled($context, $userid, '', true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get the current active section for the student.
     */
    public function get_current_section_for_student(int $userid, int $courseid): int {
        global $DB;
        try {
            $course = $DB->get_record('course', ['id' => $courseid]);
            if (!$course) return 1;

            $modinfo = get_fast_modinfo($course, $userid);
            $sections = $modinfo->get_section_info_all();

            $highest_unlocked = 1;
            foreach ($sections as $sec) {
                if ((int)$sec->section <= 0) continue;
                if ($sec->uservisible && $sec->available) {
                    $highest_unlocked = (int)$sec->section;
                }
            }
            return $highest_unlocked;
        } catch (\Throwable $e) {
            return 1;
        }
    }

    /**
     * Resolve whether emotion checkin (pre or post) should be displayed.
     */
    public function resolve_emotion_checkin_state(int $userid, int $courseid, ?array $pending_quiz_mot = null): array {
        global $DB;

        if (!self::is_student_user($userid, $courseid)) {
            return ['show' => false];
        }

        // 1. Post-Emotion Check-In: Priority when quiz/assignment completed / pending motivation exists.
        if (!empty($pending_quiz_mot)) {
            $mot_section = isset($pending_quiz_mot['section']) ? (int)$pending_quiz_mot['section'] : 0;
            if ($mot_section <= 0) {
                $mot_section = $this->get_current_section_for_student($userid, $courseid);
            }

            $post_category = 'post_section_' . $mot_section;
            $has_post = $DB->record_exists('acmls_motivation_feedback', [
                'userid' => $userid,
                'courseid' => $courseid,
                'category' => $post_category,
            ]);

            if (!$has_post) {
                return [
                    'show' => true,
                    'section' => $mot_section,
                    'stage' => 'post',
                    'category' => $post_category,
                    'source' => 'section_post',
                    'title' => "Evaluasi Emosi Akhir — Minggu {$mot_section}",
                    'subtitle' => "Selamat telah menuntaskan materi dan penugasan Minggu {$mot_section}! Sampaikan bagaimana perasaan dan refleksimu setelah belajar.",
                    'submit' => 'Kirim & Lihat Motivasi Belajar',
                ];
            }
        }

        // 2. Pre-Emotion Check-In: Awal section saat siswa membuka section baru.
        $current_section = $this->get_current_section_for_student($userid, $courseid);
        $pre_category = 'checkin_section_' . $current_section;

        $has_pre = $DB->record_exists('acmls_motivation_feedback', [
            'userid' => $userid,
            'courseid' => $courseid,
            'category' => $pre_category,
        ]);

        if (!$has_pre) {
            return [
                'show' => true,
                'section' => $current_section,
                'stage' => 'pre',
                'category' => $pre_category,
                'source' => 'section_pre',
                'title' => "Check-in Kesiapan Emosi Awal — Minggu {$current_section}",
                'subtitle' => "Sebelum memulai pembelajaran materi Minggu {$current_section}, sampaikan kesiapan emosimu hari ini.",
                'submit' => "Mulai Belajar Minggu {$current_section}",
            ];
        }

        return ['show' => false];
    }

    /**
     * Retrieve any pending post-quiz/assignment motivation message.
     */
    public function get_pending_quiz_motivation(int $userid, int $courseid): ?array {
        global $DB;

        if (!self::is_student_user($userid, $courseid)) {
            return null;
        }

        try {
            $records = $DB->get_records_select(
                self::TABLE_LEARNER_RECORD,
                "userid = :userid AND courseid = :courseid AND record_type = 'pending_quiz_motivation'",
                ['userid' => $userid, 'courseid' => $courseid],
                'id DESC',
                '*',
                0,
                1
            );

            if (empty($records)) {
                return null;
            }

            $rec = reset($records);
            $payload = json_decode($rec->data_payload, true) ?: [];

            return [
                'recordid' => (int) $rec->id,
                'content' => (string) ($payload['content'] ?? ''),
                'suggestion' => (string) ($payload['suggestion'] ?? ''),
                'category' => (string) ($payload['category'] ?? 'reinforcement'),
                'source' => (string) ($payload['source'] ?? 'system'),
                'quizgrade' => isset($payload['quizgrade']) ? (float) $payload['quizgrade'] : null,
                'section' => isset($payload['section']) ? (int) $payload['section'] : 1,
            ];
        } catch (\Throwable $e) {
            debugging('delivery_system::get_pending_quiz_motivation error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }

    /**
     * Render the emotional readiness check-in modal.
     */
    public function display_emotion_checkin(int $userid, int $courseid, array $state = []): string {
        global $OUTPUT;

        if (empty($state)) {
            $state = $this->resolve_emotion_checkin_state($userid, $courseid);
        }

        $secnum = $state['section'] ?? 1;
        $stage = $state['stage'] ?? 'pre';
        $category = $state['category'] ?? ('checkin_section_' . $secnum);
        $source = $state['source'] ?? 'section_pre';
        $title = $state['title'] ?? ("Check-in Kesiapan Emosi Awal — Minggu " . $secnum);
        $subtitle = $state['subtitle'] ?? ("Sebelum memulai pembelajaran materi Minggu " . $secnum . ", sampaikan kesiapan emosimu hari ini.");
        $submit = $state['submit'] ?? ("Mulai Belajar Minggu " . $secnum);

        return $OUTPUT->render_from_template(
            'local_llmmotivation/emotion_checkin',
            [
                'userid' => $userid,
                'courseid' => $courseid,
                'section_num' => $secnum,
                'stage' => $stage,
                'category' => $category,
                'source' => $source,
                'likert_options' => $this->get_likert_options(),
                'str_popup_title' => $title,
                'str_popup_subtitle' => $subtitle,
                'str_e1_prompt' => get_string('motivation_e1_prompt', 'local_llmmotivation'),
                'str_e2_prompt' => get_string('motivation_e2_prompt', 'local_llmmotivation'),
                'str_e3_prompt' => get_string('motivation_e3_prompt', 'local_llmmotivation'),
                'str_reflection_label' => get_string('motivation_reflection_label', 'local_llmmotivation'),
                'str_reflection_placeholder' => get_string('motivation_reflection_placeholder', 'local_llmmotivation'),
                'str_submit' => $submit,
                'str_required' => get_string('motivation_feedback_required', 'local_llmmotivation'),
                'str_research_notice' => get_string('motivation_research_notice', 'local_llmmotivation'),
            ]
        );
    }

    /**
     * Render the post-quiz/assignment motivation modal.
     */
    public function display_quiz_motivation(int $userid, int $courseid, array $mot, bool $wait_for_emotion = false): string {
        global $OUTPUT;

        $quizgrade = $mot['quizgrade'] ?? null;
        $has_quizgrade = ($quizgrade !== null);
        $quizgrade_formatted = $has_quizgrade
            ? get_string('quiz_motivation_score', 'local_llmmotivation', round($quizgrade, 1))
            : '';

        $category = $mot['category'] ?? '';
        $suggestion = $mot['suggestion'] ?? '';

        return $OUTPUT->render_from_template(
            'local_llmmotivation/quiz_motivation',
            [
                'userid' => $userid,
                'courseid' => $courseid,
                'recordid' => $mot['recordid'] ?? 0,
                'content' => $mot['content'] ?? '',
                'has_suggestion' => !empty($suggestion),
                'suggestion' => $suggestion,
                'category' => $category,
                'category_label' => $this->get_category_label($category),
                'source' => $mot['source'] ?? '',
                'is_gemini' => ($mot['source'] ?? '') === 'gemini',
                'has_quizgrade' => $has_quizgrade,
                'quizgrade_formatted' => $quizgrade_formatted,
                'wait_for_emotion' => $wait_for_emotion,
                'str_title' => get_string('quiz_motivation_title', 'local_llmmotivation'),
                'str_subtitle' => get_string('quiz_motivation_subtitle', 'local_llmmotivation'),
                'str_suggestion_title' => get_string('quiz_motivation_suggestion_title', 'local_llmmotivation'),
                'str_continue' => get_string('quiz_motivation_continue', 'local_llmmotivation'),
            ]
        );
    }

    private function get_category_label(string $category): string {
        $map = [
            'reinforcement' => get_string('encouragement_reinforcement', 'local_llmmotivation'),
            'achievement' => get_string('encouragement_achievement', 'local_llmmotivation'),
            'recovery' => get_string('encouragement_recovery', 'local_llmmotivation'),
            'persistence' => get_string('encouragement_persistence', 'local_llmmotivation'),
        ];
        return $map[$category] ?? ucfirst($category);
    }

    private function get_likert_options(): array {
        return [
            ['score' => 5, 'label' => get_string('motivation_likert_5', 'local_llmmotivation')],
            ['score' => 4, 'label' => get_string('motivation_likert_4', 'local_llmmotivation')],
            ['score' => 3, 'label' => get_string('motivation_likert_3', 'local_llmmotivation')],
            ['score' => 2, 'label' => get_string('motivation_likert_2', 'local_llmmotivation')],
            ['score' => 1, 'label' => get_string('motivation_likert_1', 'local_llmmotivation')],
        ];
    }

    /**
     * Get adaptive improvement advice based on Cognitive, Behavioral, and Emotional dimensions.
     */
    public static function get_adaptive_suggestion(
        $profile = null,
        ?string $category = null,
        ?float $quizgrade = null,
        array $context = []
    ): string {
        $c_score = $quizgrade !== null ? (float)$quizgrade : ($profile ? (float)$profile->cognitive_score : 50.0);
        $b_score = $profile ? (float)$profile->behavioral_score : 50.0;
        $e_score = $profile ? (float)$profile->emotional_score : 50.0;
        $e_conf  = $profile ? (float)($profile->e2_score ?? 50.0) : 50.0;
        $b_access = $profile ? (int)($profile->b1_access_count ?? 0) : 0;
        $b_punct  = $profile ? (int)($profile->b3_punctual_count ?? 0) : 0;

        $name = !empty($context['student_name']) ? trim($context['student_name']) : '';
        $quizname = !empty($context['quiz_name']) ? trim($context['quiz_name']) : 'materi ini';
        $duration_sec = !empty($context['duration_seconds']) ? (int)$context['duration_seconds'] : 0;
        $duration_text = !empty($context['duration_text']) ? trim($context['duration_text']) : '';
        $has_rushed = ($duration_sec > 0 && $duration_sec < 150 && $c_score < 70.0);

        $greeting = $name !== '' ? "Halo {$name}! " : "";
        $rush_note = $has_rushed ? "Tadi kamu mengerjakannya cukup cepat ({$duration_text}), lain kali coba santai dan baca tiap butir soal lebih teliti ya. " : "";

        // Target action notes based on specific weak quiz or incomplete reading
        $target_actions = [];
        if (!empty($context['low_quizzes_summary'])) {
            $target_actions[] = "Fokuskan latihan mandirimu pada materi kuis: {$context['low_quizzes_summary']}";
        }
        if (!empty($context['incomplete_readings_summary'])) {
            $target_actions[] = "Luangkan waktu membaca modul bacaan: {$context['incomplete_readings_summary']}";
        }
        $target_note = !empty($target_actions) ? (" Catatan langkah belajarmu: " . implode('. ', $target_actions) . ".") : "";

        // 1. Kognitif Rendah (< 50)
        if ($c_score < 50.0) {
            if ($b_score >= 50.0 || $b_access >= 10) {
                return $greeting . $rush_note . "Keaktifan dan dedikasi kamu dalam mengakses modul sudah sangat bagus! Supaya pemahaman konsep di {$quizname} makin mantap, coba ubah strategimu dengan mempraktikkan langsung kode contoh baris demi baris dan buat rangkuman sendiri ya.{$target_note}";
            } else if ($e_conf < 45.0 || $e_score < 40.0) {
                return $greeting . $rush_note . "Wajar banget kok kalau materi di {$quizname} terasa agak menantang di awal. Jangan merasa terbebani ya—fokus pahami satu konsep kecil dulu pelan-pelan, manfaatkan rangkuman modul, dan diskusikan jika ada bagian yang membingungkan.{$target_note}";
            } else {
                return $greeting . $rush_note . "Yuk luangkan waktu yang teratur untuk membaca ulang materi di {$quizname} secara bertahap. Coba catat poin-poin pentingnya dan kerjakan latihan mandiri sebelum lanjut ke materi berikutnya ya!{$target_note}";
            }
        }

        // 2. Kognitif Sedang (50 - 69.9)
        if ($c_score < 70.0) {
            if ($e_conf < 50.0) {
                return $greeting . $rush_note . "Pemahaman dasarmu di {$quizname} sebenarnya sudah di jalur yang tepat kok. Biar makin percaya diri, coba ulas kembali butir-butir kuis yang belum tepat kemarin dan kerjakan latihan serupa secara mandiri ya.{$target_note}";
            } else if ($b_score < 40.0 || $b_punct === 0) {
                return $greeting . $rush_note . "Konsep di {$quizname} sudah mulai kamu kuasai dengan baik! Yuk jaga keteraturanmu dalam menuntaskan aktivitas modul dan kumpulkan tugas tepat waktu biar hasil belajarmu makin maksimal.{$target_note}";
            } else {
                return $greeting . $rush_note . "Pemahaman dasar dan kebiasaan belajarmu sudah terbentuk dengan baik. Tinjau kembali beberapa butir kuis {$quizname} yang keliru dan perbanyak latihan studi kasus mandiri supaya konsepnya makin matang.{$target_note}";
            }
        }

        // 3. Kognitif Tinggi (>= 70)
        if ($b_score < 50.0) {
            return $greeting . "Penguasaan konsepmu di {$quizname} tajam dan keren banget! Biar prestasimu makin sempurna, yuk jaga konsistensi dalam menyelesaikan seluruh aktivitas modul dan kumpulkan tugas tepat waktu ya.{$target_note}";
        } else if ($e_score >= 70.0 && $b_score >= 70.0) {
            return $greeting . "Luar biasa! Motivasi, kedisiplinan, dan penguasaan materi di {$quizname} solid banget. Pertahankan ritme belajarmu ini, coba tantang dirimu dengan eksperimen proyek mandiri, dan jangan ragu berbagi ilmu dengan teman-teman sekelasmu ya.{$target_note}";
        } else {
            return $greeting . "Hasil kuis kamu di {$quizname} keren banget! Untuk memperdalam wawasanmu, coba eksplorasi materi pengayaan, terapkan konsepnya ke latihan yang lebih menantang, dan diskusikan ide-ide kreatif bersama rekan belajarmu.{$target_note}";
        }
    }

    public static function get_default_suggestion(?string $category, ?float $quizgrade = null): string {
        return self::get_adaptive_suggestion(null, $category, $quizgrade);
    }
}
