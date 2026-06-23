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

    // Create grade item (use full form data so grade max from activity settings is applied).
    try {
        aicode_grade_item_update($aicode);
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

    // Update grade item (preserve grade / idnumber from the activity form).
    try {
        aicode_grade_item_update($aicode);
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

    // Remove gradebook column for this activity.
    aicode_grade_item_delete($aicode);

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
 * Build grade_item parameters for an AICode activity instance.
 *
 * Manual grading from Laporan Guru requires a numeric grade item. When the
 * activity form leaves "Grade" at None (0), we still default to 0–100 so
 * teachers can enter scores from the report page.
 *
 * @param stdClass $aicode Instance with at least id, course, name; optional grade, cmidnumber.
 * @return array Parameters for grade_update() itemdetails.
 */
function aicode_build_grade_item_params(stdClass $aicode): array {
    global $DB;

    $params = ['itemname' => $aicode->name];

    if (!empty($aicode->cmidnumber)) {
        $params['idnumber'] = $aicode->cmidnumber;
    }

    $grademax = 100.0;

    if (isset($aicode->grade)) {
        $formgrade = (float) $aicode->grade;
        if ($formgrade > 0) {
            $grademax = $formgrade;
        } else if ($formgrade < 0) {
            $params['gradetype'] = GRADE_TYPE_SCALE;
            $params['scaleid'] = (int) (-$formgrade);
            return $params;
        }
    } else if (!empty($aicode->id)) {
        $existing = $DB->get_record('grade_items', [
            'courseid' => $aicode->course,
            'itemtype' => 'mod',
            'itemmodule' => 'aicode',
            'iteminstance' => $aicode->id,
            'itemnumber' => 0,
        ], 'gradetype, grademax, scaleid', IGNORE_MISSING);
        if ($existing && (int) $existing->gradetype === GRADE_TYPE_SCALE && $existing->scaleid) {
            $params['gradetype'] = GRADE_TYPE_SCALE;
            $params['scaleid'] = (int) $existing->scaleid;
            return $params;
        }
        if ($existing && (int) $existing->gradetype === GRADE_TYPE_VALUE && $existing->grademax > 0) {
            $grademax = (float) $existing->grademax;
        }
    }

    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax'] = $grademax;
    $params['grademin'] = 0;
    $params['hidden'] = 0;

    return $params;
}

/**
 * Enrich an aicode instance object with gradebook metadata from the course module.
 *
 * @param stdClass $aicode Must contain id and course.
 * @return stdClass Same object, enriched with cmidnumber and grade when available.
 */
function aicode_enrich_for_gradebook(stdClass $aicode): stdClass {
    global $DB;

    if (empty($aicode->id) || empty($aicode->course)) {
        return $aicode;
    }

    $cm = get_coursemodule_from_instance('aicode', $aicode->id, $aicode->course, false, IGNORE_MISSING);
    if ($cm) {
        if (!isset($aicode->cmidnumber) && !empty($cm->idnumber)) {
            $aicode->cmidnumber = $cm->idnumber;
        }
        if (!isset($aicode->grade)) {
            $gi = $DB->get_record('grade_items', [
                'courseid' => $aicode->course,
                'itemtype' => 'mod',
                'itemmodule' => 'aicode',
                'iteminstance' => $aicode->id,
                'itemnumber' => 0,
            ], 'gradetype, grademax', IGNORE_MISSING);
            if ($gi && (int) $gi->gradetype === GRADE_TYPE_VALUE && $gi->grademax > 0) {
                $aicode->grade = (float) $gi->grademax;
            }
        }
    }

    return $aicode;
}

/**
 * Create or update the gradebook column for an AICode activity.
 *
 * @param stdClass $aicode object with extra cmidnumber / grade from mod_form when available
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function aicode_grade_item_update($aicode, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $aicode = aicode_enrich_for_gradebook($aicode);
    $params = aicode_build_grade_item_params($aicode);

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/aicode', $aicode->course, 'mod', 'aicode', $aicode->id, 0, $grades, $params);
}

/**
 * Delete the gradebook column for an AICode activity instance.
 *
 * @param stdClass $aicode
 * @return int grade_update() status code
 */
function aicode_grade_item_delete(stdClass $aicode): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update(
        'mod/aicode',
        $aicode->course,
        'mod',
        'aicode',
        $aicode->id,
        0,
        null,
        ['deleted' => 1]
    );
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

    aicode_grade_item_update($aicode, null);
}

/**
 * Add module-specific items to the activity secondary navigation.
 * The "Laporan Guru" link appears next to "Settings" for users with
 * the mod/aicode:viewattempts capability.
 *
 * @param settings_navigation $settingsnav The Moodle settings navigation tree.
 * @param navigation_node     $activitynode The activity node to attach items to.
 */
function aicode_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $activitynode = null) {
    global $PAGE;

    if (!$activitynode || !$PAGE->cm) {
        return;
    }

    $context = context_module::instance($PAGE->cm->id);

    if (!has_capability('mod/aicode:viewattempts', $context)) {
        return;
    }

    $url = new moodle_url('/mod/aicode/report.php', ['id' => $PAGE->cm->id]);
    $activitynode->add(
        'Laporan Guru',
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'aicode_report',
        new pix_icon('i/report', 'Laporan Guru')
    );
}

/**
 * Save or reset a single student's grade for an AICode problem.
 *
 * @param stdClass $aicode   The aicode record (must have ->id, ->course, ->name).
 * @param int      $userid   The student's user ID.
 * @param float|null $rawgrade Grade 0–100, or null to remove/reset the grade.
 * @return int  Result of grade_update(): 0 = success, GRADE_UPDATE_FAILED etc.
 */
function aicode_set_user_grade(stdClass $aicode, int $userid, ?float $rawgrade): int {
    $gradeobj         = new stdClass();
    $gradeobj->userid = $userid;
    $gradeobj->rawgrade = $rawgrade;

    // Pass itemdetails + grades together so the grade item is created/updated
    // before user grades are written (required for gradebook visibility).
    return aicode_grade_item_update($aicode, [$userid => $gradeobj]);
}

/**
 * Add "AICode Analytics" link to the course navigation for teachers.
 *
 * @param navigation_node $navigation The course navigation node.
 * @param stdClass        $course     The course record.
 * @param context_course  $context    The course context.
 */
function aicode_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context) {
    if (has_capability('moodle/grade:viewall', $context)) {
        $url = new moodle_url('/mod/aicode/course_report.php', ['courseid' => $course->id]);
        $navigation->add(
            'AICode Analytics',
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'aicode_course_analytics',
            new pix_icon('i/report', 'AICode Analytics')
        );
    }
}
