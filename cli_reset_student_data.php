<?php
/**
 * CLI script to reset student testing data for Moodle, AICode, and ACMLS.
 *
 * Preserves courses, user accounts, enrolments, modules, and quiz questions.
 * Resets student activity completions, grades, quiz attempts, AICode submissions,
 * ACMLS emotion check-ins, and ACMLS longitudinal profiles.
 *
 * Usage:
 *   php cli_reset_student_data.php [--courseid=22] [--all] [--confirm]
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

list($options, $unrecognized) = cli_get_params([
    'help'     => false,
    'courseid' => 22,
    'all'      => false,
    'confirm'  => false,
], [
    'h' => 'help',
    'c' => 'courseid',
    'a' => 'all',
    'y' => 'confirm',
]);

if ($options['help']) {
    $help = "
Script Reset Data Siswa untuk Pengujian (Testing State)

Options:
  -h, --help            Tampilkan bantuan ini
  -c, --courseid=INT    ID Course yang ingin direset (Default: 22)
  -a, --all             Reset data siswa di SEMUA course
  -y, --confirm         Konfirmasi eksekusi tanpa konfirmasi interaktif

Contoh:
  php cli_reset_student_data.php --courseid=22 --confirm
  php cli_reset_student_data.php --all --confirm
";
    cli_writeln($help);
    exit(0);
}

global $DB;

// Determine target courses.
$target_courses = [];
if (!empty($options['all'])) {
    $target_courses = $DB->get_records_sql("SELECT id, fullname, shortname FROM {course} WHERE id > 1");
} else {
    $courseid = (int) $options['courseid'];
    $course = $DB->get_record('course', ['id' => $courseid]);
    if (!$course) {
        cli_error("Error: Course dengan ID {$courseid} tidak ditemukan!");
    }
    $target_courses[$courseid] = $course;
}

cli_heading("=== RESET DATA SISWA UNTUK TESTING ===");
cli_writeln("Course yang akan diproses:");
foreach ($target_courses as $c) {
    cli_writeln(" - Course [{$c->id}]: {$c->fullname} ({$c->shortname})");
}
cli_writeln("");

// If not confirmed via flag, prompt user.
if (empty($options['confirm'])) {
    $input = cli_input("Ketik 'yes' untuk melanjutkan proses reset data: ");
    if (strtolower(trim($input)) !== 'yes') {
        cli_writeln("Proses dibatalkan.");
        exit(0);
    }
}

$transaction = $DB->start_delegated_transaction();

try {
    foreach ($target_courses as $course) {
        $cid = (int) $course->id;
        cli_writeln("\n>>> Memproses Course [{$cid}] {$course->fullname}...");

        // -------------------------------------------------------------
        // 1. ACMLS (Attendance Leaderboard & Motivation)
        // -------------------------------------------------------------
        $acmls_tables = [
            'acmls_motivation_feedback' => 'courseid',
            'acmls_learner_profile'     => 'courseid',
            'acmls_learner_record'      => 'courseid',
            'acmls_activity_log'        => 'courseid',
            'acmls_coach_decision'      => 'courseid',
            'acmls_leaderboard'         => 'courseid',
            'acmls_learner_consent'     => 'courseid',
        ];

        foreach ($acmls_tables as $table => $col) {
            if ($DB->get_manager()->table_exists($table)) {
                $count = $DB->count_records($table, [$col => $cid]);
                if ($count > 0) {
                    $DB->delete_records($table, [$col => $cid]);
                    cli_writeln("  [ACMLS] {$table}: {$count} data dihapus.");
                }
            }
        }

        // -------------------------------------------------------------
        // 2. AICode (Coding problems, attempts & activity logs)
        // -------------------------------------------------------------
        if ($DB->get_manager()->table_exists('aicode')) {
            $problems = $DB->get_records('aicode', ['course' => $cid], '', 'id');
            if (!empty($problems)) {
                $problem_ids = array_keys($problems);
                list($insql, $inparams) = $DB->get_in_or_equal($problem_ids);

                if ($DB->get_manager()->table_exists('aicode_attempts')) {
                    $count = $DB->count_records_select('aicode_attempts', "problemid {$insql}", $inparams);
                    if ($count > 0) {
                        $DB->delete_records_select('aicode_attempts', "problemid {$insql}", $inparams);
                        cli_writeln("  [AICode] aicode_attempts: {$count} data dihapus.");
                    }
                }

                if ($DB->get_manager()->table_exists('aicode_activity_log')) {
                    $count = $DB->count_records('aicode_activity_log', ['id_kursus' => $cid]);
                    if ($count > 0) {
                        $DB->delete_records('aicode_activity_log', ['id_kursus' => $cid]);
                        cli_writeln("  [AICode] aicode_activity_log: {$count} data dihapus.");
                    }
                }
            }
        }

        // -------------------------------------------------------------
        // 3. Quiz Attempts & Question Usages
        // -------------------------------------------------------------
        $quizzes = $DB->get_records('quiz', ['course' => $cid]);
        $total_attempts_deleted = 0;
        foreach ($quizzes as $quiz) {
            $attempts = $DB->get_records('quiz_attempts', ['quiz' => $quiz->id]);
            foreach ($attempts as $attempt) {
                quiz_delete_attempt($attempt, $quiz);
                $total_attempts_deleted++;
            }
            $DB->delete_records('quiz_grades', ['quiz' => $quiz->id]);
        }
        if ($total_attempts_deleted > 0) {
            cli_writeln("  [Quiz] {$total_attempts_deleted} pengerjaan kuis dihapus.");
        }

        // -------------------------------------------------------------
        // 4. Activity Completion
        // -------------------------------------------------------------
        $cms = $DB->get_records('course_modules', ['course' => $cid], '', 'id');
        if (!empty($cms)) {
            $cm_ids = array_keys($cms);
            list($cm_insql, $cm_params) = $DB->get_in_or_equal($cm_ids);
            $cmc_count = $DB->count_records_select('course_modules_completion', "coursemoduleid {$cm_insql}", $cm_params);
            if ($cmc_count > 0) {
                $DB->delete_records_select('course_modules_completion', "coursemoduleid {$cm_insql}", $cm_params);
                cli_writeln("  [Completion] course_modules_completion: {$cmc_count} data dihapus.");
            }
        }

        $cc_count = $DB->count_records('course_completions', ['course' => $cid]);
        if ($cc_count > 0) {
            $DB->delete_records('course_completions', ['course' => $cid]);
            $DB->delete_records('course_completion_crit_compl', ['course' => $cid]);
            cli_writeln("  [Completion] course_completions: {$cc_count} data dihapus.");
        }

        // -------------------------------------------------------------
        // 5. Gradebook (Grade Items & Grades for Students)
        // -------------------------------------------------------------
        $grade_items = $DB->get_records('grade_items', ['courseid' => $cid], '', 'id');
        if (!empty($grade_items)) {
            $gi_ids = array_keys($grade_items);
            list($gi_insql, $gi_params) = $DB->get_in_or_equal($gi_ids);

            // Delete grade grades (except admin/system users id <= 2).
            $gg_count = $DB->count_records_select('grade_grades', "itemid {$gi_insql} AND userid > 2", $gi_params);
            if ($gg_count > 0) {
                $DB->delete_records_select('grade_grades', "itemid {$gi_insql} AND userid > 2", $gi_params);
                $DB->delete_records_select('grade_grades_history', "itemid {$gi_insql} AND userid > 2", $gi_params);
                cli_writeln("  [Gradebook] grade_grades: {$gg_count} nilai siswa dikosongkan.");
            }
        }

        // -------------------------------------------------------------
        // 6. Logstore Standard Log (Student Access Logs)
        // -------------------------------------------------------------
        if ($DB->get_manager()->table_exists('logstore_standard_log')) {
            $log_count = $DB->count_records_select('logstore_standard_log', "courseid = :cid AND userid > 2", ['cid' => $cid]);
            if ($log_count > 0) {
                $DB->delete_records_select('logstore_standard_log', "courseid = :cid AND userid > 2", ['cid' => $cid]);
                cli_writeln("  [Log] logstore_standard_log: {$log_count} log akses siswa dibersihkan.");
            }
        }
    }

    $transaction->allow_commit();
    cli_writeln("\n========================================================");
    cli_writeln("BERHASIL! Seluruh data testing siswa telah direset.");
    cli_writeln("Kondisi sekarang:");
    cli_writeln(" - Siswa belum memiliki nilai.");
    cli_writeln(" - Materi berstatus belum diakses.");
    cli_writeln(" - Form Emosi ACMLS akan otomatis muncul kembali.");
    cli_writeln(" - Akun siswa, course, dan soal tetap aman dan utuh.");
    cli_writeln("========================================================");

} catch (Throwable $e) {
    $transaction->rollback($e);
    cli_error("TERJADI KESALAHAN (Rollback berhasil): " . $e->getMessage() . "\n" . $e->getTraceAsString());
}
