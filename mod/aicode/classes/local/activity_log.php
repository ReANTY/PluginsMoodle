<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Server-side metadata activity log (no source code stored).
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aicode\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Inserts into {aicode_activity_log} for research export; failures must not break requests.
 */
class activity_log {

    /** User opened the activity (view.php). */
    public const ACTION_ACTIVITY_VIEW = 'activity_view';

    /** run_code completed and an attempt row was stored (or would be for normal path). */
    public const ACTION_CODE_RUN = 'code_run';

    /** run_code blocked by server-side security check (no executor call). */
    public const ACTION_CODE_RUN_BLOCKED = 'code_run_blocked';

    /** AI feedback / analyze_code web service returned a payload. */
    public const ACTION_AI_ANALYZE = 'ai_analyze';

    /** record_hint updated the latest attempt hint list. */
    public const ACTION_HINT_RECORDED = 'hint_recorded';

    /** send_to_teacher stored a teacher-review attempt. */
    public const ACTION_SEND_TO_TEACHER = 'send_to_teacher';

    /** get_run_history returned history metadata (count only, no code in this table). */
    public const ACTION_RUN_HISTORY_VIEW = 'run_history_view';

    /**
     * Snapshot grade from gradebook if a grade item exists.
     *
     * @param int $courseid
     * @param int $problemid aicode.id
     * @param int $userid
     * @return float|null
     */
    public static function snapshot_gradebook_value(int $courseid, int $problemid, int $userid): ?float {
        global $CFG;

        require_once($CFG->libdir . '/gradelib.php');

        try {
            $gradinginfo = grade_get_grades($courseid, 'mod', 'aicode', $problemid, $userid);
            if (empty($gradinginfo->items)) {
                return null;
            }
            $gradeitem = reset($gradinginfo->items);
            if (!$gradeitem || empty($gradeitem->grades)) {
                return null;
            }
            $cell = null;
            foreach ($gradeitem->grades as $candidate) {
                if ((int) ($candidate->userid ?? 0) === $userid) {
                    $cell = $candidate;
                    break;
                }
            }
            if ($cell === null) {
                return null;
            }
            if ($cell->grade === null || $cell->grade === '') {
                return null;
            }
            return round((float) $cell->grade, 5);
        } catch (\Throwable $e) {
            debugging('aicode_activity_log gradesnapshot: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }

    /**
     * Builds cumulative counters for spreadsheet-friendly columns.
     * Run totals use max(attempts, log) so anonymous training runs are still counted.
     *
     * Call after any related DB write for this HTTP request so attempt-based counts include just-persisted rows.
     *
     * @param \moodle_database $DB
     * @param int $problemid aicode.id
     * @param int $userid
     * @param string $action This row's ACTION_* value (applied before inserting the row itself)
     * @return array{aihint_so_far:int,runs_so_far:int,teacher_submit_so_far:int}
     */
    public static function compute_cumulative_counts(
        \moodle_database $DB,
        int $problemid,
        int $userid,
        string $action
    ): array {
        $params = ['pid' => $problemid, 'uid' => $userid];

        $ailog = (int) $DB->count_records_select(
            'aicode_activity_log',
            'id_aktivitas_aicode = :pid AND id_pengguna = :uid AND kode_kejadian = :aia',
            $params + ['aia' => self::ACTION_AI_ANALYZE]
        );
        if ($action === self::ACTION_AI_ANALYZE) {
            $ailog++;
        }

        $runattempts = (int) $DB->count_records_select(
            'aicode_attempts',
            'problemid = :pid AND userid = :uid AND teacher_review_requested = 0',
            $params
        );

        $runlog = (int) $DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {aicode_activity_log}
              WHERE id_aktivitas_aicode = :pid
                AND id_pengguna = :uid
                AND kode_kejadian IN ('code_run', 'code_run_blocked') ",
            $params
        );
        if ($action === self::ACTION_CODE_RUN || $action === self::ACTION_CODE_RUN_BLOCKED) {
            $runlog++;
        }

        $runs = max($runattempts, $runlog);

        $subs = (int) $DB->count_records_select(
            'aicode_attempts',
            'problemid = :pid AND userid = :uid AND teacher_review_requested = 1',
            $params
        );

        return [
            'aihint_so_far' => $ailog,
            'runs_so_far' => $runs,
            'teacher_submit_so_far' => $subs,
        ];
    }

    /**
     * @param \context_module $context Module context
     * @param int $problemid aicode.id
     * @param int $userid Effectively always the session user for these web services
     * @param string $action One of the ACTION_* constants
     * @param array|null $meta Small JSON-serializable metadata (no code)
     * @param \stdClass|null $problem Problem row from {aicode} (optional — loaded if omitted)
     * @param \stdClass|null $user User row for fullname snapshot (defaults to global USER if ids match else loaded)
     */
    public static function record(
        \context_module $context,
        int $problemid,
        int $userid,
        string $action,
        ?array $meta = null,
        ?\stdClass $problem = null,
        ?\stdClass $user = null
    ): void {
        global $DB, $USER;

        if ($userid <= 0 || $problemid <= 0) {
            return;
        }

        $coursecontext = $context->get_course_context();
        $courseid = (int) $coursecontext->instanceid;

        if ($problem === null) {
            $problem = $DB->get_record('aicode', ['id' => $problemid], 'id,mode', IGNORE_MISSING);
        }
        if (!$problem) {
            return;
        }

        if ($user === null || (int) ($user->id ?? 0) !== $userid) {
            if ((int) $USER->id === $userid) {
                $user = $USER;
            } else {
                $user = $DB->get_record('user', ['id' => $userid], '*', IGNORE_MISSING);
            }
        }
        if (!$user) {
            return;
        }

        $fullname = fullname($user);
        if (\core_text::strlen($fullname) > 255) {
            $fullname = \core_text::substr($fullname, 0, 255);
        }

        $mode = (string) ($problem->mode ?? 'training');
        if ($mode !== 'exam' && $mode !== 'training') {
            $mode = 'training';
        }

        $counts = self::compute_cumulative_counts($DB, $problemid, $userid, $action);
        $grade = self::snapshot_gradebook_value($courseid, $problemid, $userid);

        $record = new \stdClass();
        $record->id_kursus = $courseid;
        $record->id_modul = (int) $context->instanceid;
        $record->id_aktivitas_aicode = $problemid;
        $record->id_pengguna = $userid;
        $record->kode_kejadian = $action;
        if ($meta !== null && $meta !== []) {
            $record->metadata_json = json_encode($meta, JSON_UNESCAPED_SLASHES);
        } else {
            $record->metadata_json = null;
        }
        $record->nama_lengkap = $fullname;
        $record->mode_aktivitas = $mode;
        $record->jumlah_ai_hint = $counts['aihint_so_far'];
        $record->jumlah_run = $counts['runs_so_far'];
        $record->jumlah_kirim_guru = $counts['teacher_submit_so_far'];
        $record->nilai_snapshot = $grade;
        $record->waktu_dicatat = time();

        try {
            $DB->insert_record('aicode_activity_log', $record);
        } catch (\Throwable $e) {
            debugging('aicode_activity_log insert failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
