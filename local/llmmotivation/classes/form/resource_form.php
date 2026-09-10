<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moodle form for adding/editing a Learning Resource.
 *
 * Fields: title, resource_type, difficulty_level, topic_tags, learning_styles, is_active.
 * Used by manage_resources.php for both add and edit actions.
 *
 * @package   local_llmmotivation
 * @copyright 2024 ACMLS Project
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_llmmotivation\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form class for adding and editing a Learning Resource.
 *
 * Implements Moodle's moodleform to provide a standard Moodle form
 * for instructors to manage learning resources in the ACMLS system.
 *
 * @package   local_llmmotivation
 * @copyright 2024 ACMLS Project
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resource_form extends \moodleform {

    /**
     * Define the form fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        // Hidden fields: resource ID (0 = new) and course ID.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid', 0);
        $mform->setType('courseid', PARAM_INT);

        // ----------------------------------------------------------------
        // Title.
        // ----------------------------------------------------------------
        $mform->addElement(
            'text',
            'title',
            get_string('resource_title', 'local_llmmotivation'),
            ['size' => 60, 'maxlength' => 255]
        );
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('title', 'resource_title', 'local_llmmotivation');

        // ----------------------------------------------------------------
        // Resource type.
        // ----------------------------------------------------------------
        $resource_types = [
            'resource'  => get_string('resource_type_resource', 'local_llmmotivation'),
            'page'      => get_string('resource_type_page', 'local_llmmotivation'),
            'book'      => get_string('resource_type_book', 'local_llmmotivation'),
            'video'     => get_string('resource_type_video', 'local_llmmotivation'),
            'audio'     => get_string('resource_type_audio', 'local_llmmotivation'),
            'document'  => get_string('resource_type_document', 'local_llmmotivation'),
            'pdf'       => get_string('resource_type_pdf', 'local_llmmotivation'),
            'quiz'      => get_string('resource_type_quiz', 'local_llmmotivation'),
            'assign'    => get_string('resource_type_assign', 'local_llmmotivation'),
            'workshop'  => get_string('resource_type_workshop', 'local_llmmotivation'),
            'forum'     => get_string('resource_type_forum', 'local_llmmotivation'),
            'chat'      => get_string('resource_type_chat', 'local_llmmotivation'),
        ];

        $mform->addElement(
            'select',
            'resource_type',
            get_string('resource_type', 'local_llmmotivation'),
            $resource_types
        );
        $mform->setDefault('resource_type', 'resource');
        $mform->addHelpButton('resource_type', 'resource_type', 'local_llmmotivation');

        // ----------------------------------------------------------------
        // Difficulty level.
        // ----------------------------------------------------------------
        $difficulty_levels = [
            1 => get_string('difficulty_basic', 'local_llmmotivation'),
            2 => get_string('difficulty_intermediate', 'local_llmmotivation'),
            3 => get_string('difficulty_advanced', 'local_llmmotivation'),
        ];

        $mform->addElement(
            'select',
            'difficulty_level',
            get_string('difficulty_level', 'local_llmmotivation'),
            $difficulty_levels
        );
        $mform->setDefault('difficulty_level', 1);
        $mform->addHelpButton('difficulty_level', 'difficulty_level', 'local_llmmotivation');

        // ----------------------------------------------------------------
        // Topic tags (comma-separated text).
        // ----------------------------------------------------------------
        $mform->addElement(
            'text',
            'topic_tags',
            get_string('topic_tags', 'local_llmmotivation'),
            ['size' => 60]
        );
        $mform->setType('topic_tags', PARAM_TEXT);
        $mform->addHelpButton('topic_tags', 'topic_tags', 'local_llmmotivation');

        // ----------------------------------------------------------------
        // Learning styles (multi-select checkboxes).
        // ----------------------------------------------------------------
        $learning_styles = [
            'visual'      => get_string('style_visual', 'local_llmmotivation'),
            'auditory'    => get_string('style_auditory', 'local_llmmotivation'),
            'reading'     => get_string('style_reading', 'local_llmmotivation'),
            'kinesthetic' => get_string('style_kinesthetic', 'local_llmmotivation'),
        ];

        $mform->addElement(
            'select',
            'learning_styles',
            get_string('learning_styles', 'local_llmmotivation'),
            $learning_styles,
            ['multiple' => 'multiple', 'size' => 4]
        );
        $mform->setDefault('learning_styles', ['reading']);
        $mform->addHelpButton('learning_styles', 'learning_styles', 'local_llmmotivation');

        // ----------------------------------------------------------------
        // Is active.
        // ----------------------------------------------------------------
        $mform->addElement(
            'advcheckbox',
            'is_active',
            get_string('resource_is_active', 'local_llmmotivation'),
            get_string('resource_is_active_desc', 'local_llmmotivation'),
            [],
            [0, 1]
        );
        $mform->setDefault('is_active', 1);

        // ----------------------------------------------------------------
        // Action buttons.
        // ----------------------------------------------------------------
        $this->add_action_buttons(true, get_string('saveresource', 'local_llmmotivation'));
    }

    /**
     * Validate form data.
     *
     * @param  array $data  Form data.
     * @param  array $files Uploaded files (unused).
     * @return array        Array of validation errors keyed by field name.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        // Title must not be empty after trimming.
        if (empty(trim($data['title'] ?? ''))) {
            $errors['title'] = get_string('required');
        }

        // Difficulty level must be 1, 2, or 3.
        $difficulty = (int) ($data['difficulty_level'] ?? 0);
        if (!in_array($difficulty, [1, 2, 3], true)) {
            $errors['difficulty_level'] = get_string('invaliddata', 'error');
        }

        return $errors;
    }
}
