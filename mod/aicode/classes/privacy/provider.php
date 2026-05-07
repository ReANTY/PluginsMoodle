<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Privacy Subsystem implementation for mod_aicode.
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aicode\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for mod_aicode.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'aicode_attempts',
            [
                'userid' => 'privacy:metadata:aicode_attempts:userid',
                'code_hash' => 'privacy:metadata:aicode_attempts:code_hash',
                'timecreated' => 'privacy:metadata:aicode_attempts:timecreated',
            ],
            'privacy:metadata:aicode_attempts'
        );

        $collection->add_database_table(
            'aicode_activity_log',
            [
                'id_pengguna' => 'privacy:metadata:aicode_activity_log:id_pengguna',
                'id_kursus' => 'privacy:metadata:aicode_activity_log:id_kursus',
                'id_aktivitas_aicode' => 'privacy:metadata:aicode_activity_log:id_aktivitas_aicode',
                'kode_kejadian' => 'privacy:metadata:aicode_activity_log:kode_kejadian',
                'metadata_json' => 'privacy:metadata:aicode_activity_log:metadata_json',
                'nama_lengkap' => 'privacy:metadata:aicode_activity_log:nama_lengkap',
                'mode_aktivitas' => 'privacy:metadata:aicode_activity_log:mode_aktivitas',
                'jumlah_ai_hint' => 'privacy:metadata:aicode_activity_log:jumlah_ai_hint',
                'jumlah_run' => 'privacy:metadata:aicode_activity_log:jumlah_run',
                'jumlah_kirim_guru' => 'privacy:metadata:aicode_activity_log:jumlah_kirim_guru',
                'nilai_snapshot' => 'privacy:metadata:aicode_activity_log:nilai_snapshot',
                'waktu_dicatat' => 'privacy:metadata:aicode_activity_log:waktu_dicatat',
            ],
            'privacy:metadata:aicode_activity_log'
        );

        $collection->add_external_location_link(
            'moodleai',
            [
                'code' => 'privacy:metadata:external:moodleai:code',
                'errors' => 'privacy:metadata:external:moodleai:errors',
            ],
            'privacy:metadata:external:moodleai'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {aicode} ap ON ap.id = cm.instance
            INNER JOIN {aicode_attempts} aa ON aa.problemid = ap.id
                 WHERE aa.userid = :userid";

        $params = [
            'modname' => 'aicode',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        $sqllogs = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {aicode} ap ON ap.id = cm.instance
            INNER JOIN {aicode_activity_log} lg ON lg.id_aktivitas_aicode = ap.id
                 WHERE lg.id_pengguna = :userid";

        $contextlist->add_from_sql($sqllogs, $params);

        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT cm.id AS cmid,
                       aa.*
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {aicode} ap ON ap.id = cm.instance
            INNER JOIN {aicode_attempts} aa ON aa.problemid = ap.id
                 WHERE c.id {$contextsql}
                       AND aa.userid = :userid
              ORDER BY cm.id";

        $params = [
            'modname' => 'aicode',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $user->id,
        ];
        $params += $contextparams;

        $attempts = $DB->get_recordset_sql($sql, $params);
        foreach ($attempts as $attempt) {
            $context = \context_module::instance($attempt->cmid);
            $data = (object) [
                'timecreated' => \core_privacy\local\request\transform::datetime($attempt->timecreated),
                'code_hash' => $attempt->code_hash,
                'result' => $attempt->result_json,
                'used_hints' => $attempt->used_hints_json,
            ];
            writer::with_context($context)->export_data([], $data);
        }
        $attempts->close();

        $sqllogs = "SELECT cm.id AS cmid,
                           lg.*
                      FROM {context} c
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                INNER JOIN {aicode} ap ON ap.id = cm.instance
                INNER JOIN {aicode_activity_log} lg ON lg.id_aktivitas_aicode = ap.id
                     WHERE c.id {$contextsql}
                           AND lg.id_pengguna = :userid
                  ORDER BY cm.id";

        $logs = $DB->get_recordset_sql($sqllogs, $params);
        foreach ($logs as $log) {
            $context = \context_module::instance($log->cmid);
            $ldata = (object) [
                'waktu_dicatat' => \core_privacy\local\request\transform::datetime($log->waktu_dicatat),
                'kode_kejadian' => $log->kode_kejadian ?? '',
                'metadata' => $log->metadata_json ?? '',
                'nama_lengkap_snapshot' => $log->nama_lengkap ?? '',
                'mode_aktivitas' => $log->mode_aktivitas ?? '',
                'jumlah_ai_hint' => $log->jumlah_ai_hint ?? '',
                'jumlah_run' => $log->jumlah_run ?? '',
                'jumlah_kirim_guru' => $log->jumlah_kirim_guru ?? '',
                'nilai_snapshot' => $log->nilai_snapshot ?? '',
            ];
            writer::with_context($context)->export_data(
                ['metadata-activity-log', 'entry-' . $log->id],
                $ldata
            );
        }
        $logs->close();
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('aicode', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('aicode_attempts', ['problemid' => $cm->instance]);
        $DB->delete_records('aicode_activity_log', ['id_aktivitas_aicode' => $cm->instance]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('aicode', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $DB->delete_records('aicode_attempts', [
                'problemid' => $cm->instance,
                'userid' => $userid,
            ]);
            $DB->delete_records('aicode_activity_log', [
                'id_aktivitas_aicode' => $cm->instance,
                'id_pengguna' => $userid,
            ]);
        }
    }
}

