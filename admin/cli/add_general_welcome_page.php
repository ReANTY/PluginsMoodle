<?php
/**
 * CLI: Tambah Page selamat datang ke section General (0) pada course JS-FUND-2026.
 *
 * @package    local
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/admin/course_builder_helpers.php');

raise_memory_limit(MEMORY_EXTRA);

$config = require($CFG->dirroot . '/admin/js_course_data.php');
$shortname = $config['course']['shortname'];
$courseid = get_course_id_by_shortname($shortname);

if (!$courseid) {
    mtrace('ERROR: Course not found: ' . $shortname);
    exit(1);
}

$page = require($CFG->dirroot . '/admin/js_course_general_page.php');

global $DB;
$modid = $DB->get_field('modules', 'id', ['name' => 'page']);
$existing = $DB->get_records_sql(
    "SELECT cm.id, p.name
       FROM {course_modules} cm
       JOIN {page} p ON p.id = cm.instance
      WHERE cm.course = ? AND cm.module = ? AND cm.section = (
            SELECT cs.id FROM {course_sections} cs
             WHERE cs.course = ? AND cs.section = 0
          )",
    [$courseid, $modid, $courseid]
);

foreach ($existing as $cm) {
    if ($cm->name === $page['title']) {
        mtrace('Page already exists (cmid ' . $cm->id . '): ' . $page['title']);
        exit(0);
    }
}

$cmid = create_page_resource(
    $courseid,
    0,
    $page['title'],
    $page['intro'],
    $page['content']
);

rebuild_course_cache($courseid, true);
mtrace('Created welcome page in General section. cmid=' . $cmid);
