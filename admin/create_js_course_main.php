<?php
/**
 * JavaScript Fundamental Course - Complete Course Creator
 * 
 * Script ini akan membuat course JavaScript Fundamental lengkap dengan:
 * - 8 Minggu (Topics)
 * - 30+ Micro Lessons (Page resources)
 * - 30+ Micro Quizzes
 * - 30+ Praktik Coding (Aicode activities)
 * - 8 Weekly Quizzes
 * - 8 Weekly Assignments
 * - 1 Final Project
 * - Completion tracking
 * - Gradebook configuration
 * 
 * Akses via: http://localhost/moodle/admin/create_js_course_main.php
 */

// Include Moodle config and libraries
require_once(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');

// Must be run as admin
require_login();
$context = context_system::instance();
require_capability('moodle/course:create', $context);

// Set page context
$PAGE->set_context($context);
$PAGE->set_url('/admin/create_js_course_main.php');
$PAGE->set_title('Create JavaScript Fundamental Course');
$PAGE->set_heading('Create JavaScript Fundamental Course');

echo $OUTPUT->header();

echo '<div class="alert alert-info">';
echo '<h2>🚀 Creating JavaScript Fundamental Course</h2>';
echo '<p>This will create a complete 8-week JavaScript course with all materials, quizzes, and assignments.</p>';
echo '</div>';

// Start transaction
$transaction = $DB->start_delegated_transaction();

try {
    // ========================================
    // STEP 1: CREATE COURSE
    // ========================================
    echo '<div class="alert alert-primary"><strong>Step 1:</strong> Creating course...</div>';
    
    $coursedata = new stdClass();
    $coursedata->fullname = 'JavaScript Fundamental';
    $coursedata->shortname = 'JS-FUND-' . date('Y-m-d-His');
    $coursedata->category = 1;
    $coursedata->summary = '<div class="course-summary">
        <h3>🚀 Selamat Datang di Course JavaScript Fundamental!</h3>
        <p>Course ini dirancang khusus untuk pemula yang ingin mempelajari JavaScript dari dasar dengan metode <strong>microlearning</strong>.</p>
        <h4>📋 Apa yang Akan Anda Pelajari?</h4>
        <ul>
            <li>✅ Dasar-dasar JavaScript dan sintaksnya</li>
            <li>✅ Variabel, tipe data, dan operator</li>
            <li>✅ Percabangan dan perulangan</li>
            <li>✅ Function, array, dan object</li>
            <li>✅ Praktik coding langsung di browser</li>
        </ul>
        <h4>⏱️ Durasi & Format</h4>
        <ul>
            <li><strong>Durasi:</strong> 8 minggu</li>
            <li><strong>Format:</strong> Microlearning (5-10 menit per lesson)</li>
            <li><strong>Bahasa:</strong> Indonesia</li>
            <li><strong>Level:</strong> Beginner (Pemula Total)</li>
        </ul>
        <h4>🎓 Sertifikat</h4>
        <p>Anda akan mendapatkan <strong>sertifikat internal</strong> setelah menyelesaikan semua materi, kuis, assignment, dan final project dengan nilai minimal 70%.</p>
        <h4>📊 Komponen Penilaian</h4>
        <ul>
            <li>Micro Lesson Quiz: 20%</li>
            <li>Weekly Quiz: 25%</li>
            <li>Weekly Assignment: 25%</li>
            <li>Final Project: 30%</li>
        </ul>
    </div>';
    $coursedata->summaryformat = FORMAT_HTML;
    $coursedata->format = 'topics';
    $coursedata->numsections = 8;
    $coursedata->startdate = time();
    $coursedata->visible = 1;
    $coursedata->enablecompletion = COMPLETION_ENABLED;
    $coursedata->showgrades = 1;
    
    $course = create_course($coursedata);
    $courseid = $course->id;
    
    echo '<div class="alert alert-success">✓ Course created! ID: ' . $courseid . '</div>';
    
    // Get module IDs
    $page_module_id = $DB->get_field('modules', 'id', array('name' => 'page'));
    $quiz_module_id = $DB->get_field('modules', 'id', array('name' => 'quiz'));
    $aicode_module_id = $DB->get_field('modules', 'id', array('name' => 'aicode'));
    
    echo '<div class="alert alert-info">';
    echo 'Module IDs: Page=' . $page_module_id . ', Quiz=' . $quiz_module_id . ', Aicode=' . $aicode_module_id;
    echo '</div>';
    
    // Include course content data
    require_once(__DIR__ . '/js_course_content.php');
    
    echo '<div class="alert alert-success">';
    echo '<h3>🎉 COURSE CREATED SUCCESSFULLY!</h3>';
    echo '<p><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $courseid . '" class="btn btn-primary btn-lg" target="_blank">Open JavaScript Fundamental Course</a></p>';
    echo '<p><strong>Course ID:</strong> ' . $courseid . '</p>';
    echo '<p><strong>Short Name:</strong> ' . $course->shortname . '</p>';
    echo '</div>';
    
    // Commit transaction
    $transaction->allow_commit();
    
} catch (Exception $e) {
    $transaction->rollback($e);
    echo '<div class="alert alert-danger">';
    echo '<strong>✗ ERROR:</strong> ' . $e->getMessage() . '<br>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
    echo '</div>';
}

echo $OUTPUT->footer();
