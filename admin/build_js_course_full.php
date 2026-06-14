<?php
/**
 * JavaScript Fundamental Course - Complete Builder
 * Access: /admin/build_js_course_full.php
 */

set_time_limit(600);
ini_set('memory_limit', '512M');

require_once(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once(__DIR__ . '/course_builder_helpers.php');

require_login();
$context = context_system::instance();
require_capability('moodle/course:create', $context);

$PAGE->set_context($context);
$PAGE->set_url('/admin/build_js_course_full.php');
$PAGE->set_title('Build JavaScript Fundamental Course');
$PAGE->set_heading('Build JavaScript Fundamental Course');

echo $OUTPUT->header();
echo '<div class="alert alert-info"><h2>Building JavaScript Fundamental Course</h2><p>Shortname: <strong>JS-FUND-2026</strong>. Mohon tunggu...</p></div>';

if (ob_get_level() == 0) {
    ob_start();
}
echo str_pad('', 4096);
ob_flush();
flush();

if (!customcert_plugin_available()) {
    echo '<div class="alert alert-danger"><strong>Plugin mod_customcert belum terinstall.</strong> ';
    echo 'Jalankan upgrade Moodle setelah plugin ada di mod/customcert/, lalu muat ulang halaman ini.</div>';
    echo $OUTPUT->footer();
    die();
}

$config = require(__DIR__ . '/js_course_data.php');
$coursecfg = $config['course'];
$shortname = $coursecfg['shortname'];

if (course_exists_by_shortname($shortname)) {
    echo '<div class="alert alert-danger"><strong>Course sudah ada:</strong> ' . s($shortname);
    echo '<p>Hapus course lama atau gunakan shortname lain sebelum menjalankan builder.</p></div>';
    echo $OUTPUT->footer();
    die();
}

$transaction = $DB->start_delegated_transaction();

try {
    $coursedata = new stdClass();
    $coursedata->fullname = $coursecfg['fullname'];
    $coursedata->shortname = $shortname;
    $coursedata->category = $coursecfg['category'];
    $coursedata->summary = $coursecfg['summary'];
    $coursedata->summaryformat = FORMAT_HTML;
    $coursedata->format = $coursecfg['format'];
    $coursedata->numsections = $coursecfg['numsections'];
    $coursedata->startdate = $coursecfg['startdate'];
    $coursedata->visible = $coursecfg['visible'];
    $coursedata->enablecompletion = COMPLETION_ENABLED;
    $coursedata->showgrades = 1;

    $course = create_course($coursedata);
    $courseid = $course->id;
    echo '<div class="alert alert-success">Course dibuat (ID: ' . $courseid . ')</div>';
    ob_flush();
    flush();

    $generalpage = require(__DIR__ . '/js_course_general_page.php');
    create_page_resource(
        $courseid,
        0,
        $generalpage['title'],
        $generalpage['intro'],
        $generalpage['content']
    );
    echo '<div class="alert alert-success">Page selamat datang ditambahkan di section General.</div>';
    ob_flush();
    flush();

    require_once(__DIR__ . '/js_course_teacher_keys.php');
    $teacherkeys = build_teacher_keys_page();
    create_teacher_only_page_resource(
        $courseid,
        0,
        $teacherkeys['title'],
        $teacherkeys['intro'],
        $teacherkeys['content']
    );
    echo '<div class="alert alert-success">Page kunci jawaban guru ditambahkan (tersembunyi dari siswa).</div>';
    ob_flush();
    flush();

    $gbmap = [
        'micro_quiz' => [],
        'weekly_quiz' => [],
        'weekly_assignment' => [],
        'final_project' => [],
    ];

    $unlockcmid = null;

    $trackweekly = function (int $courseid, array &$gbmap) {
        $modinfo = get_fast_modinfo($courseid);
        $gbmap['weekly_quiz'] = [];
        $gbmap['weekly_assignment'] = [];
        foreach ($modinfo->cms as $cm) {
            if ($cm->modname === 'quiz' && strpos($cm->name, 'Weekly Quiz') !== false) {
                $gbmap['weekly_quiz'][] = $cm->id;
            }
            if ($cm->modname === 'aicode' && strpos($cm->name, 'Weekly Assignment') !== false) {
                $gbmap['weekly_assignment'][] = $cm->id;
            }
        }
    };

    // Week 1 with enrichment merge.
    $week1 = require(__DIR__ . '/js_week1_content.php');
    $w1extra = require(__DIR__ . '/js_week1_enrichment.php');
    foreach ($week1['micro_lessons'] as $i => &$lesson) {
        if (isset($w1extra['lesson_enrichment'][$i])) {
            $lesson['practice'] = $w1extra['lesson_enrichment'][$i]['practice'];
            $lesson['micro_quiz'] = $w1extra['lesson_enrichment'][$i]['micro_quiz'];
        }
    }
    unset($lesson);
    $week1['weekly_quiz'] = $w1extra['weekly_quiz'];
    $week1['weekly_assignment'] = $w1extra['weekly_assignment'];

    echo '<div class="alert alert-primary">Minggu 1...</div>';
    $w1result = build_week_from_data($courseid, 1, $week1, $unlockcmid);
    $gbmap['micro_quiz'] = array_merge($gbmap['micro_quiz'], $w1result['micro_quiz_cmids']);
    $unlockcmid = $w1result['weekly_assignment_cmid'];
    ob_flush();
    flush();

    for ($w = 2; $w <= 7; $w++) {
        echo '<div class="alert alert-primary">Minggu ' . $w . '...</div>';
        $weekdata = require(__DIR__ . '/js_week' . $w . '_content.php');
        $result = build_week_from_data($courseid, $w, $weekdata, $unlockcmid);
        $gbmap['micro_quiz'] = array_merge($gbmap['micro_quiz'], $result['micro_quiz_cmids']);
        $unlockcmid = $result['weekly_assignment_cmid'];
        ob_flush();
        flush();
    }

    // Week 8 - micro lessons + weekly quiz + final project.
    echo '<div class="alert alert-primary">Minggu 8 (Final Project)...</div>';
    $week8 = require(__DIR__ . '/js_week8_content.php');
    $w8result = build_week_from_data($courseid, 8, $week8, $unlockcmid);
    $gbmap['micro_quiz'] = array_merge($gbmap['micro_quiz'], $w8result['micro_quiz_cmids']);

    $avail = $unlockcmid ? availability_require_cm_completion($unlockcmid) : null;
    $opts = $avail ? ['availability' => $avail] : [];

    $fp = $week8['final_project'];
    $finalcmid = create_aicode_activity($courseid, 8, [
        'name' => $fp['name'],
        'intro' => $fp['intro'],
        'description' => $fp['description'],
        'mode' => 'exam',
        'startercode' => $fp['startercode'],
        'testcases' => $fp['testcases'],
        'htmltemplate' => $fp['htmltemplate'] ?? '',
        'csstemplate' => $fp['csstemplate'] ?? '',
    ], $opts);
    $gbmap['final_project'][] = $finalcmid;

    $trackweekly($courseid, $gbmap);

    echo '<div class="alert alert-primary">Gradebook...</div>';
    configure_gradebook($courseid, $gbmap);

    echo '<div class="alert alert-primary">Course completion...</div>';
    configure_course_completion($courseid, $finalcmid);

    echo '<div class="alert alert-primary">Sertifikat...</div>';
    $certintro = '<p>Sertifikat internal course JavaScript Fundamental. Tersedia setelah menyelesaikan course dengan nilai minimal 70%.</p>';
    $certcmid = create_customcert_activity(
        $courseid,
        8,
        'Sertifikat JavaScript Fundamental',
        $certintro,
        $opts
    );

    rebuild_course_cache($courseid, true);

    $transaction->allow_commit();

    echo '<div class="alert alert-success" style="padding:24px;margin-top:16px;">';
    echo '<h2>Course berhasil dibuat</h2>';
    echo '<ul>';
    echo '<li><strong>ID:</strong> ' . $courseid . '</li>';
    echo '<li><strong>Shortname:</strong> ' . s($shortname) . '</li>';
    echo '<li><strong>Sertifikat CMID:</strong> ' . $certcmid . '</li>';
    echo '</ul>';
    echo '<p><a class="btn btn-primary btn-lg" href="' . $CFG->wwwroot . '/course/view.php?id=' . $courseid . '">Buka Course</a></p>';
    echo '<p><em>Langkah manual:</em> Desain template sertifikat di aktivitas Custom Certificate (tambah elemen teks: nama, course, grade, tanggal).</p>';
    echo '</div>';

} catch (Throwable $e) {
    $transaction->rollback($e);
    echo '<div class="alert alert-danger"><h3>Error</h3><p>' . s($e->getMessage()) . '</p>';
    if ($e instanceof dml_exception && !empty($e->debuginfo)) {
        echo '<p><strong>Detail database:</strong></p><pre>' . s($e->debuginfo) . '</pre>';
    }
    echo '<pre>' . s($e->getTraceAsString()) . '</pre></div>';
}

echo $OUTPUT->footer();
