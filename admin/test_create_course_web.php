<?php
/**
 * Test Script: Create a simple test course in Moodle (Web Version)
 * Access via: http://localhost/moodle/admin/test_create_course_web.php
 */

// Include Moodle config and libraries
require_once(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/accesslib.php');

// Must be run as admin
require_login();
$context = context_system::instance();
require_capability('moodle/course:create', $context);

// Set page context
$PAGE->set_context($context);
$PAGE->set_url('/admin/test_create_course_web.php');
$PAGE->set_title('Test Course Creation');
$PAGE->set_heading('Test Course Creation');

echo $OUTPUT->header();

echo '<div class="alert alert-info">';
echo '<h2>🧪 MOODLE COURSE CREATION TEST</h2>';
echo '</div>';

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
    echo '<div class="alert alert-primary">📝 Creating test course...</div>';
    $course = create_course($coursedata);
    
    echo '<div class="alert alert-success">';
    echo '<strong>✓ Course created successfully!</strong><br>';
    echo '<ul>';
    echo '<li><strong>Course ID:</strong> ' . $course->id . '</li>';
    echo '<li><strong>Full Name:</strong> ' . $course->fullname . '</li>';
    echo '<li><strong>Short Name:</strong> ' . $course->shortname . '</li>';
    echo '<li><strong>URL:</strong> <a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '" target="_blank">View Course</a></li>';
    echo '</ul>';
    echo '</div>';
    
    // Test creating a Page resource
    echo '<div class="alert alert-primary">📄 Creating test Page resource...</div>';
    $page = new stdClass();
    $page->course = $course->id;
    $page->name = 'Test Page - Pengenalan JavaScript';
    $page->intro = 'Ini adalah test page resource';
    $page->introformat = FORMAT_HTML;
    $page->content = '<h2>Test Content</h2><p>Ini adalah contoh konten page.</p><pre><code>console.log("Hello World");</code></pre>';
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
    
    // Rebuild course cache
    rebuild_course_cache($course->id, true);
    
    echo '<div class="alert alert-success">';
    echo '<strong>✓ Page resource created successfully!</strong><br>';
    echo '<ul>';
    echo '<li><strong>Page ID:</strong> ' . $pageid . '</li>';
    echo '<li><strong>Course Module ID:</strong> ' . $cmid . '</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<div class="alert alert-success">';
    echo '<h3>🎉 TEST COMPLETED SUCCESSFULLY!</h3>';
    echo '<p>Silakan cek course di: <a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '" class="btn btn-primary" target="_blank">Open Test Course</a></p>';
    echo '<p><strong>Jika test berhasil, saya akan lanjut membuat course JavaScript Fundamental lengkap!</strong></p>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">';
    echo '<strong>✗ ERROR:</strong> ' . $e->getMessage() . '<br>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
    echo '</div>';
}

echo $OUTPUT->footer();
