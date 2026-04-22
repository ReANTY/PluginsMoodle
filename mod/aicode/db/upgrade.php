<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Upgrade script for mod_aicode
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute aicode upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_aicode_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Rename aicode_problems to aicode (main table must match module name).
    if ($oldversion < 2025041401) {
        $table = new xmldb_table('aicode_problems');
        
        if ($dbman->table_exists($table)) {
            // Rename the table.
            $dbman->rename_table($table, 'aicode');
        }

        // Update foreign key in aicode_attempts.
        $attemptstable = new xmldb_table('aicode_attempts');
        if ($dbman->table_exists($attemptstable)) {
            // Drop old foreign key.
            $key = new xmldb_key('problemid', XMLDB_KEY_FOREIGN, ['problemid'], 'aicode_problems', ['id']);
            $dbman->drop_key($attemptstable, $key);
            
            // Add new foreign key.
            $newkey = new xmldb_key('problemid', XMLDB_KEY_FOREIGN, ['problemid'], 'aicode', ['id']);
            $dbman->add_key($attemptstable, $newkey);
        }

        upgrade_mod_savepoint(true, 2025041401, 'aicode');
    }

    if ($oldversion < 2026012000) {
        $table = new xmldb_table('aicode');

        $htmlfield = new xmldb_field('htmltemplate', XMLDB_TYPE_TEXT, null, null, null, null, null, 'startercode');
        if (!$dbman->field_exists($table, $htmlfield)) {
            $dbman->add_field($table, $htmlfield);
        }

        $cssfield = new xmldb_field('csstemplate', XMLDB_TYPE_TEXT, null, null, null, null, null, 'htmltemplate');
        if (!$dbman->field_exists($table, $cssfield)) {
            $dbman->add_field($table, $cssfield);
        }

        upgrade_mod_savepoint(true, 2026012000, 'aicode');
    }

    if ($oldversion < 2026020900) {
        $table = new xmldb_table('aicode');
        $modefield = new xmldb_field('mode', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'training', 'allow_training');

        if (!$dbman->field_exists($table, $modefield)) {
            $dbman->add_field($table, $modefield);
        }

        upgrade_mod_savepoint(true, 2026020900, 'aicode');
    }

    if ($oldversion < 2026021200) {
        // Cleanup deprecated AI analyzer/Gemini configuration.
        unset_config('analyzer_url', 'aicode');
        unset_config('gemini_api_key', 'aicode');

        upgrade_mod_savepoint(true, 2026021200, 'aicode');
    }

    if ($oldversion < 2026030200) {
        $table = new xmldb_table('aicode');
        $promptfield = new xmldb_field('aiprompttemplate', XMLDB_TYPE_TEXT, null, null, null, null, null, 'mode');

        if (!$dbman->field_exists($table, $promptfield)) {
            $dbman->add_field($table, $promptfield);
        }

        upgrade_mod_savepoint(true, 2026030200, 'aicode');
    }

    if ($oldversion < 2026041001) {
        $table = new xmldb_table('aicode_attempts');
        $field = new xmldb_field(
            'security_flags',
            XMLDB_TYPE_TEXT,
            null, null, null, null, null,
            'ai_feedback_json'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026041001, 'aicode');
    }

    if ($oldversion < 2026041302) {
        $table = new xmldb_table('aicode_attempts');
        $field = new xmldb_field(
            'ai_requested_at',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            null,   // nullable – NULL means no AI request was made on this attempt
            null,
            null,
            'security_flags'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Index for efficient daily AI-request rate-limit queries.
        $index = new xmldb_index('userid_ai_requested_at', XMLDB_INDEX_NOTUNIQUE, ['userid', 'ai_requested_at']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2026041302, 'aicode');
    }

    if ($oldversion < 2026041301) {
        $table = new xmldb_table('aicode_teacher_overrides');

        // Add feedback_rating column: nullable integer 1-5.
        $ratingfield = new xmldb_field(
            'feedback_rating',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            null,   // not null = false (nullable)
            null,
            null,
            'corrected_feedback_json'
        );
        if (!$dbman->field_exists($table, $ratingfield)) {
            $dbman->add_field($table, $ratingfield);
        }

        // Add use_as_example column: 0/1, default 0.
        $examplefield = new xmldb_field(
            'use_as_example',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'feedback_rating'
        );
        if (!$dbman->field_exists($table, $examplefield)) {
            $dbman->add_field($table, $examplefield);
        }

        // Add index on use_as_example for efficient few-shot queries.
        $index = new xmldb_index('use_as_example', XMLDB_INDEX_NOTUNIQUE, ['use_as_example']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2026041301, 'aicode');
    }

    if ($oldversion < 2026041400) {
        // Drop the aicode_materials table which was defined in install.xml but never
        // referenced by any PHP code. Recommended materials are generated inline by
        // the Gemini AI response and do not require a separate database table.
        $table = new xmldb_table('aicode_materials');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_mod_savepoint(true, 2026041400, 'aicode');
    }

    if ($oldversion < 2026041401) {
        $table = new xmldb_table('aicode_attempts');

        // Add dedicated flag column so the exam-mode guard can use a simple
        // indexed integer lookup instead of a full-table LIKE scan on result_json.
        $field = new xmldb_field(
            'teacher_review_requested',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'ai_requested_at'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Composite index: covers the exact WHERE clause used by the guard
        // (problemid = ? AND userid = ? AND teacher_review_requested = 1).
        $index = new xmldb_index(
            'problemid_userid_teacher_review',
            XMLDB_INDEX_NOTUNIQUE,
            ['problemid', 'userid', 'teacher_review_requested']
        );
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Back-fill existing rows that were submitted via send_to_teacher
        // (identified by the JSON marker written before this migration).
        $DB->execute(
            "UPDATE {aicode_attempts}
                SET teacher_review_requested = 1
              WHERE teacher_review_requested = 0
                AND " . $DB->sql_like('result_json', ':marker'),
            ['marker' => '%"teacher_review_requested":true%']
        );

        upgrade_mod_savepoint(true, 2026041401, 'aicode');
    }

    return true;
}

