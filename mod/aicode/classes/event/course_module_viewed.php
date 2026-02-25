<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * The mod_aicode course module viewed event.
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aicode\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_aicode course module viewed event class.
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'aicode';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Get objectid mapping
     */
    public static function get_objectid_mapping() {
        return ['db' => 'aicode', 'restore' => 'aicode'];
    }
}

