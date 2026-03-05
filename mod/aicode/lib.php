<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Library of interface functions and constants for module aicode
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns whether a feature is supported by aicode module.
 *
 * @param string $feature FEATURE_xx constant
 * @return mixed True if feature is supported, null otherwise
 */
function aicode_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the aicode into the database
 *
 * @param stdClass $aicode An object from the form in mod_form.php
 * @param mod_aicode_mod_form $mform The form instance
 * @return int The id of the newly inserted aicode record
 */
function aicode_add_instance(stdClass $aicode, mod_aicode_mod_form $mform = null) {
    global $DB;

    $aicode->timecreated = time();
    $aicode->timemodified = time();

    // Ensure testcases are properly encoded.
    if (isset($aicode->testcases)) {
        if (is_array($aicode->testcases)) {
            $aicode->testcases = json_encode($aicode->testcases);
        } else if (is_string($aicode->testcases) && !empty($aicode->testcases)) {
            // Validate it's valid JSON
            $decoded = json_decode($aicode->testcases);
            if (json_last_error() !== JSON_ERROR_NONE) {
                debugging('Invalid JSON in testcases: ' . json_last_error_msg(), DEBUG_DEVELOPER);
                $aicode->testcases = '[]'; // Default to empty array
            }
        }
    }

    // Only insert fields that exist in the database table.
    $record = new stdClass();
    $record->course = $aicode->course;
    $record->name = $aicode->name;
    $record->intro = $aicode->intro ?? '';
    $record->introformat = $aicode->introformat ?? FORMAT_HTML;
    $record->description = $aicode->description ?? '';
    $record->language = $aicode->language ?? 'javascript';
    $record->testcases = $aicode->testcases ?? '';
    $record->startercode = $aicode->startercode ?? '';
    $record->htmltemplate = $aicode->htmltemplate ?? '';
    $record->csstemplate = $aicode->csstemplate ?? '';
    $record->allow_training = $aicode->allow_training ?? 0;
    $record->mode = $aicode->mode ?? 'training';
    $record->aiprompttemplate = $aicode->aiprompttemplate ?? '';
    $record->timecreated = $aicode->timecreated;
    $record->timemodified = $aicode->timemodified;

    $aicode->id = $DB->insert_record('aicode', $record);

    // Create grade item.
    $record->id = $aicode->id;
    try {
        aicode_grade_item_update($record);
    } catch (Exception $e) {
        debugging('Failed to create grade item: ' . $e->getMessage(), DEBUG_DEVELOPER);
        // Continue anyway - the activity is created, just no grade item
    }

    return $aicode->id;
}

/**
 * Updates an instance of the aicode in the database
 *
 * @param stdClass $aicode An object from the form in mod_form.php
 * @param mod_aicode_mod_form $mform The form instance
 * @return boolean Success/Fail
 */
function aicode_update_instance(stdClass $aicode, mod_aicode_mod_form $mform = null) {
    global $DB;

    $aicode->timemodified = time();
    $aicode->id = $aicode->instance;

    // Ensure testcases are properly encoded.
    if (isset($aicode->testcases)) {
        if (is_array($aicode->testcases)) {
            $aicode->testcases = json_encode($aicode->testcases);
        } else if (is_string($aicode->testcases) && !empty($aicode->testcases)) {
            // Validate it's valid JSON
            $decoded = json_decode($aicode->testcases);
            if (json_last_error() !== JSON_ERROR_NONE) {
                debugging('Invalid JSON in testcases: ' . json_last_error_msg(), DEBUG_DEVELOPER);
                $aicode->testcases = '[]'; // Default to empty array
            }
        }
    }

    // Only update fields that exist in the database table.
    $record = new stdClass();
    $record->id = $aicode->id;
    $record->course = $aicode->course;
    $record->name = $aicode->name;
    $record->intro = $aicode->intro ?? '';
    $record->introformat = $aicode->introformat ?? FORMAT_HTML;
    $record->description = $aicode->description ?? '';
    $record->language = $aicode->language ?? 'javascript';
    $record->testcases = $aicode->testcases ?? '';
    $record->startercode = $aicode->startercode ?? '';
    $record->htmltemplate = $aicode->htmltemplate ?? '';
    $record->csstemplate = $aicode->csstemplate ?? '';
    $record->allow_training = $aicode->allow_training ?? 0;
    $record->mode = $aicode->mode ?? 'training';
    $record->aiprompttemplate = $aicode->aiprompttemplate ?? '';
    $record->timemodified = $aicode->timemodified;

    $result = $DB->update_record('aicode', $record);

    // Update grade item.
    try {
        aicode_grade_item_update($record);
    } catch (Exception $e) {
        debugging('Failed to update grade item: ' . $e->getMessage(), DEBUG_DEVELOPER);
        // Continue anyway - the activity is updated, just no grade item update
    }

    return $result;
}

/**
 * Removes an instance of the aicode from the database
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function aicode_delete_instance($id) {
    global $DB;

    if (!$aicode = $DB->get_record('aicode', ['id' => $id])) {
        return false;
    }

    // Delete all attempts for this problem.
    $DB->delete_records('aicode_attempts', ['problemid' => $id]);

    // Delete the problem.
    $DB->delete_records('aicode', ['id' => $id]);

    return true;
}

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function mod_aicode_supports($feature) {
    return aicode_supports($feature);
}

/**
 * Create grade item for given aicode problem
 *
 * @param stdClass $aicode object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function aicode_grade_item_update($aicode, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = ['itemname' => $aicode->name];
    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax'] = 100;
    $params['grademin'] = 0;

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/aicode', $aicode->course, 'mod', 'aicode', $aicode->id, 0, $grades, $params);
}

/**
 * Update grades in the gradebook
 *
 * @param stdClass $aicode instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @param bool $nullifnone If true, null will be inserted when no grade exists
 */
function aicode_update_grades($aicode, $userid = 0, $nullifnone = true) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    if ($userid != 0) {
        // Update specific user grade.
        $grade = null;
        // TODO: Implement grade calculation based on attempts.
        // For now, we'll just update the grade item without specific grades.
    }

    aicode_grade_item_update($aicode);
}

