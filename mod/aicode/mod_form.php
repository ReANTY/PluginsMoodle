<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * The main aicode configuration form
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 */
class mod_aicode_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('aicoproblemname', 'aicode'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Problem description.
        $mform->addElement('textarea', 'description', get_string('description', 'aicode'), 
            ['wrap' => 'virtual', 'rows' => '10', 'cols' => '50']);
        $mform->setType('description', PARAM_TEXT);
        $mform->addHelpButton('description', 'description', 'aicode');

        // Programming language.
        $languages = ['javascript' => 'JavaScript'];
        $mform->addElement('select', 'language', get_string('language', 'aicode'), $languages);
        $mform->setDefault('language', 'javascript');

        // Activity mode: training or exam.
        $mform->addElement('select', 'mode', get_string('mode', 'aicode'), [
            'training' => get_string('mode_training', 'aicode'),
            'exam' => get_string('mode_exam', 'aicode'),
        ]);
        $mform->setDefault('mode', 'training');
        $mform->addHelpButton('mode', 'mode', 'aicode');

        // Test cases (JSON format).
        $mform->addElement('textarea', 'testcases', get_string('testcases', 'aicode'), 
            ['wrap' => 'virtual', 'rows' => '8', 'cols' => '50']);
        $mform->setType('testcases', PARAM_TEXT);
        $mform->addHelpButton('testcases', 'testcases', 'aicode');

        // Starter code template.
        $mform->addElement('textarea', 'startercode', get_string('startercode', 'aicode'), 
            ['wrap' => 'virtual', 'rows' => '15', 'cols' => '80']);
        $mform->setType('startercode', PARAM_TEXT);

        // Read-only HTML template for the problem.
        $mform->addElement('textarea', 'htmltemplate', get_string('htmltemplate', 'aicode'),
            ['wrap' => 'virtual', 'rows' => '10', 'cols' => '80']);
        $mform->setType('htmltemplate', PARAM_RAW);
        $mform->addHelpButton('htmltemplate', 'htmltemplate', 'aicode');

        // Read-only CSS template for the problem.
        $mform->addElement('textarea', 'csstemplate', get_string('csstemplate', 'aicode'),
            ['wrap' => 'virtual', 'rows' => '10', 'cols' => '80']);
        $mform->setType('csstemplate', PARAM_RAW);
        $mform->addHelpButton('csstemplate', 'csstemplate', 'aicode');

        // Privacy and consent.
        $mform->addElement('header', 'privacy', get_string('privacy', 'aicode'));
        $mform->addElement('advcheckbox', 'allow_training', 
            get_string('allowtraining', 'aicode'), 
            get_string('allowtraining_desc', 'aicode'));
        $mform->setDefault('allow_training', 0);

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Custom validation
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate testcases JSON if provided.
        if (!empty($data['testcases'])) {
            $testcases = json_decode($data['testcases'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors['testcases'] = get_string('invalidjson', 'aicode');
            }
        }

        return $errors;
    }
}

