<?php
/**
 * Test Script: Create a simple test course in Moodle
 * This will verify we can create courses programmatically
 */

// Include Moodle config and libraries
require_once(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/accesslib.php');

// Must be run as admin
require_login();
require_capability('moodle/course:create', context_system::instance());

echo "=== MOODLE COURSE CREATION TEST ===\n\n";

// Test course data
$coursedata = new stdClass();
$coursedata->fullname = 'TEST - JavaScript Fundamental';
$coursedata->shortname = 'TEST-JS-FUND-' . time();
$coursedata->category = 1; // Miscellaneous category
$coursedata->summary = 'Test course untuk verifikasi pembuatan course otomatis';
$coursedata->summaryformat = FORMAT_HTML;
$coursedata->format = 'topics'; // Topics format
$coursedata->numsections = 2; // 2 sections for test
$coursedata->startdate = time();
$coursedata->visible = 1;
$coursedata->enablecompletion = 1; // Enable completion tracking
$coursedata->showgrades = 1;

try {
    echo "Creating test course...\n";
    $course = create_course($coursedata);
    
    echo "✓ Course created successfully!\n";
    echo "  - Course ID: {$course->id}\n";
    echo "  - Full Name: {$course->fullname}\n";
    echo "  - Short Name: {$course->shortname}\n";
    echo "  - URL: {$CFG->wwwroot}/course/view.php?id={$course->id}\n\n";
    
    // Test creating a section
    echo "Creating test section...\n";
    $section = new stdClass();
    $section->course = $course->id;
    $section->section = 1;
    $section->name = 'Test Section - Minggu 1';
    $section->summary = 'Ini adalah test section';
    $section->summaryformat = FORMAT_HTML;
    
    $DB->update_record('course_sections', $section);
    echo "✓ Section updated successfully!\n\n";
    
    // Test creating a Page resource
    echo "Creating test Page resource...\n";
    $page = new stdClass();
    $page->course = $course->id;
    $page->name = 'Test Page - Pengenalan JavaScript';
    $page->intro = 'Ini adalah test page resource';
    $page->introformat = FORMAT_HTML;
    $page->content = '<h2>Test Content</h2><p>Ini adalah contoh konten page.</p>';
    $page->contentformat = FORMAT_HTML;
    $page->display = 5; // Open in page
    $page->printheading = 1;
    $page->printintro = 1;
    $page->timecreated = time();
    $page->timemodified = time();
    
    $pageid = $DB->insert_record('page', $page);
    
    // Add to course section
    $cm = new stdClass();
    $cm->course = $course->id;
    $cm->module = $DB->get_field('modules', 'id', array('name' => 'page'));
    $cm->instance = $pageid;
    $cm->section = 1;
    $cm->visible = 1;
    $cm->completion = 1; // Manual completion
    
    $cmid = add_course_module($cm);
    course_add_cm_to_section($course->id, $cmid, 1);
    
    echo "✓ Page resource created successfully!\n";
    echo "  - Page ID: {$pageid}\n";
    echo "  - Course Module ID: {$cmid}\n\n";
    
    echo "=== TEST COMPLETED SUCCESSFULLY ===\n";
    echo "Silakan cek course di: {$CFG->wwwroot}/course/view.php?id={$course->id}\n";
    echo "\nJika test berhasil, saya akan lanjut membuat course JavaScript Fundamental lengkap!\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
