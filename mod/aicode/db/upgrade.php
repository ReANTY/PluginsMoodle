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

    return true;
}

