<?php
/**
 * Helper Functions for JavaScript Fundamental Course Builder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Check if course exists by shortname.
 */
function course_exists_by_shortname(string $shortname): bool {
    global $DB;
    return $DB->record_exists('course', ['shortname' => $shortname]);
}

/**
 * Get course id by shortname.
 */
function get_course_id_by_shortname(string $shortname): ?int {
    global $DB;
    $id = $DB->get_field('course', 'id', ['shortname' => $shortname]);
    return $id ? (int)$id : null;
}

/**
 * Create a Page resource with view completion.
 */
function create_page_resource($courseid, $sectionnum, $title, $intro, $content, array $options = []) {
    global $DB;

    $page = new stdClass();
    $page->course = $courseid;
    $page->name = $title;
    $page->intro = $intro;
    $page->introformat = FORMAT_HTML;
    $page->content = $content;
    $page->contentformat = FORMAT_HTML;
    $page->display = 5;
    $page->printheading = 1;
    $page->printintro = 1;
    $page->timecreated = time();
    $page->timemodified = time();

    $pageid = $DB->insert_record('page', $page);

    $cm = new stdClass();
    $cm->course = $courseid;
    $cm->module = $DB->get_field('modules', 'id', ['name' => 'page']);
    $cm->instance = $pageid;
    $cm->section = $sectionnum;
    $teacheronly = !empty($options['teacher_only']);
    $cm->visible = $teacheronly ? 0 : 1;
    if ($teacheronly) {
        $cm->completion = COMPLETION_TRACKING_NONE;
        $cm->completionview = 0;
    } else {
        $cm->completion = COMPLETION_TRACKING_AUTOMATIC;
        $cm->completionview = 1;
    }

    $cmid = add_course_module($cm);
    course_add_cm_to_section($courseid, $cmid, $sectionnum);

    if (!empty($options['availability'])) {
        set_activity_availability($cmid, $options['availability']);
    }

    return $cmid;
}

/**
 * Create a Page visible only to users with viewhiddenactivities (guru/admin).
 */
function create_teacher_only_page_resource(
    int $courseid,
    int $sectionnum,
    string $title,
    string $intro,
    string $content,
    array $options = []
): int {
    $options['teacher_only'] = true;
    return create_page_resource($courseid, $sectionnum, $title, $intro, $content, $options);
}

/**
 * Set activity availability JSON on course module.
 */
function set_activity_availability(int $cmid, array $availability): void {
    global $DB;
    $DB->set_field('course_modules', 'availability', json_encode($availability), ['id' => $cmid]);
    rebuild_course_cache($DB->get_field('course_modules', 'course', ['id' => $cmid]), true);
}

/**
 * Build availability requiring completion of another activity.
 */
function availability_require_cm_completion(int $cmid): array {
    return [
        'op' => '&',
        'c' => [
            [
                'type' => 'completion',
                'cm' => $cmid,
                'e' => 1,
            ],
        ],
        'showc' => [true],
    ];
}

/**
 * Create a Quiz activity.
 */
function create_quiz_activity($courseid, $sectionnum, $name, $intro, $questions, $passing_grade = 70, array $options = []) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/quiz/lib.php');
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');
    require_once($CFG->libdir . '/questionlib.php');
    require_once($CFG->dirroot . '/lib/grade/grade_item.php');

    $quiz = new stdClass();
    $quiz->course = $courseid;
    $quiz->name = $name;
    $quiz->intro = $intro;
    $quiz->introformat = FORMAT_HTML;
    $quiz->timeopen = 0;
    $quiz->timeclose = 0;
    $quiz->timelimit = 0;
    $quiz->overduehandling = 'autosubmit';
    $quiz->graceperiod = 0;
    $quiz->preferredbehaviour = 'deferredfeedback';
    $quiz->canredoquestions = 0;
    $quiz->attempts = 0;
    $quiz->attemptonlast = 0;
    $quiz->grademethod = 1;
    $quiz->decimalpoints = 2;
    $quiz->questiondecimalpoints = -1;
    $quiz->reviewattempt = 69904;
    $quiz->reviewcorrectness = 69904;
    $quiz->reviewmarks = 69904;
    $quiz->reviewspecificfeedback = 69904;
    $quiz->reviewgeneralfeedback = 69904;
    $quiz->reviewrightanswer = 69904;
    $quiz->reviewoverallfeedback = 69904;
    $quiz->questionsperpage = 1;
    $quiz->navmethod = 'free';
    $quiz->shuffleanswers = 1;
    $quiz->sumgrades = max(1, count($questions));
    $quiz->grade = 100;
    $quiz->timecreated = time();
    $quiz->timemodified = time();
    $quiz->completionattemptsexhausted = 0;
    $quiz->completionminattempts = 0;
    $quiz->completionpass = 1;

    $quizid = $DB->insert_record('quiz', $quiz);
    $quiz->id = $quizid;
    ensure_quiz_default_section($quizid);

    $cm = new stdClass();
    $cm->course = $courseid;
    $cm->module = $DB->get_field('modules', 'id', ['name' => 'quiz']);
    $cm->instance = $quizid;
    $cm->section = $sectionnum;
    $cm->visible = 1;
    $cm->completion = COMPLETION_TRACKING_AUTOMATIC;
    $cm->completionpassgrade = 1;
    $cm->completionusegrade = 1;

    $cmid = add_course_module($cm);
    course_add_cm_to_section($courseid, $cmid, $sectionnum);

    $context = context_module::instance($cmid);
    $category = new stdClass();
    $category->name = $name . ' Questions';
    $category->contextid = $context->id;
    $category->info = '';
    $category->infoformat = FORMAT_HTML;
    $category->stamp = make_unique_id_code();
    $category->parent = 0;
    $category->sortorder = 999;
    $category->idnumber = null;
    $categoryid = $DB->insert_record('question_categories', $category);

    if (!empty($questions)) {
        $quiz->cmid = $cmid;
        $page = 1;
        foreach ($questions as $q) {
            $questionid = create_multichoice_question($categoryid, $q);
            quiz_add_quiz_question($questionid, $quiz, $page, 1.0);
            $page++;
        }
        $sumgrades = $DB->get_field_sql('SELECT SUM(maxmark) FROM {quiz_slots} WHERE quizid = ?', [$quizid]);
        $DB->set_field('quiz', 'sumgrades', $sumgrades ?: count($questions), ['id' => $quizid]);
    }

    $gradeitem = grade_item::fetch(['itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quizid, 'courseid' => $courseid]);
    if ($gradeitem) {
        $gradeitem->gradepass = $passing_grade;
        $gradeitem->update();
    }

    if (!empty($options['availability'])) {
        set_activity_availability($cmid, $options['availability']);
    }

    rebuild_course_cache($courseid, true);
    return $cmid;
}

/**
 * Build multichoice question form data for save_question() API.
 */
function build_multichoice_form(int $categoryid, array $qdata): stdClass {
    $options = $qdata['options'] ?? [];
    $numanswers = max(4, count($options));
    while (count($options) < $numanswers) {
        $options[] = '';
    }

    $form = new stdClass();
    $form->category = $categoryid;
    $form->name = substr(strip_tags($qdata['question']), 0, 255);
    $form->questiontext = ['text' => $qdata['question'], 'format' => FORMAT_HTML];
    $form->generalfeedback = ['text' => $qdata['feedback'] ?? '', 'format' => FORMAT_HTML];
    $form->defaultmark = 1;
    $form->penalty = 0.3333333;
    $form->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
    $form->shuffleanswers = 1;
    $form->answernumbering = 'abc';
    $form->showstandardinstruction = 0;
    $form->single = '1';
    $form->layout = 0;
    $form->noanswers = $numanswers;
    $form->numhints = 0;
    $form->correctfeedback = ['text' => get_string('correctfeedbackdefault', 'question'), 'format' => FORMAT_HTML];
    $form->partiallycorrectfeedback = ['text' => get_string('partiallycorrectfeedbackdefault', 'question'), 'format' => FORMAT_HTML];
    $form->incorrectfeedback = ['text' => get_string('incorrectfeedbackdefault', 'question'), 'format' => FORMAT_HTML];
    $form->shownumcorrect = 1;

    $correct = (int)($qdata['correct'] ?? 0);
    $form->answer = [];
    $form->fraction = [];
    $form->feedback = [];
    for ($i = 0; $i < $numanswers; $i++) {
        $form->answer[$i] = ['text' => (string)($options[$i] ?? ''), 'format' => FORMAT_HTML];
        $form->fraction[$i] = ($i === $correct) ? '1.0' : '0.0';
        $form->feedback[$i] = ['text' => '', 'format' => FORMAT_HTML];
    }

    return $form;
}

/**
 * Create a multiple choice question via the official Question API (Moodle 5 compatible).
 */
function create_multichoice_question($categoryid, $qdata) {
    global $USER;

    $form = build_multichoice_form($categoryid, $qdata);

    $question = new stdClass();
    $question->category = $categoryid;
    $question->qtype = 'multichoice';
    $question->createdby = !empty($USER->id) ? $USER->id : 2;
    $question->modifiedby = $question->createdby;
    $question->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;

    $saved = question_bank::get_qtype('multichoice')->save_question($question, $form);
    return (int)$saved->id;
}

/**
 * Create Aicode activity with full configuration.
 */
function create_aicode_activity($courseid, $sectionnum, array $config, array $options = []) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/aicode/lib.php');

    $mode = $config['mode'] ?? 'training';
    $name = $config['name'];
    $intro = $config['intro'] ?? '';
    $description = $config['description'] ?? '';
    $startercode = $config['startercode'] ?? "// Tulis kode JavaScript Anda di sini\n\n";
    $testcases = $config['testcases'] ?? '[]';

    $aicode = new stdClass();
    $aicode->course = $courseid;
    $aicode->name = $name;
    $aicode->intro = $intro;
    $aicode->introformat = FORMAT_HTML;
    $aicode->description = $description;
    $aicode->language = 'javascript';
    $aicode->testcases = $testcases;
    $aicode->startercode = $startercode;
    $aicode->htmltemplate = $config['htmltemplate'] ?? '';
    $aicode->csstemplate = $config['csstemplate'] ?? '';
    $aicode->allow_training = ($mode === 'training') ? 1 : 0;
    $aicode->mode = $mode;
    $aicode->aiprompttemplate = $config['aiprompttemplate'] ?? '';

    $aicodeid = aicode_add_instance($aicode);

    $cm = new stdClass();
    $cm->course = $courseid;
    $cm->module = $DB->get_field('modules', 'id', ['name' => 'aicode']);
    $cm->instance = $aicodeid;
    $cm->section = $sectionnum;
    $cm->visible = 1;
    $cm->completion = COMPLETION_TRACKING_AUTOMATIC;
    if ($mode === 'training') {
        $cm->completionview = 1;
    } else {
        $cm->completionview = 0;
    }

    $cmid = add_course_module($cm);
    course_add_cm_to_section($courseid, $cmid, $sectionnum);

    if (!empty($options['availability'])) {
        set_activity_availability($cmid, $options['availability']);
    }

    return $cmid;
}

/**
 * Create Custom Certificate activity (requires mod_customcert).
 */
function create_customcert_activity($courseid, $sectionnum, string $name, string $intro, array $options = []) {
    global $DB, $CFG;

    if (!file_exists($CFG->dirroot . '/mod/customcert/lib.php')) {
        throw new moodle_exception('customcertmissing', 'error', '', null, 'Plugin mod_customcert belum terinstall.');
    }

    require_once($CFG->dirroot . '/mod/customcert/lib.php');

    require_once($CFG->dirroot . '/course/modlib.php');

    $course = get_course($courseid);
    $moduleinfo = new stdClass();
    $moduleinfo->modulename = 'customcert';
    $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => 'customcert']);
    $moduleinfo->course = $courseid;
    $moduleinfo->section = $sectionnum;
    $moduleinfo->name = $name;
    $moduleinfo->intro = $intro;
    $moduleinfo->introformat = FORMAT_HTML;
    $moduleinfo->visible = 1;
    $moduleinfo->emailstudents = 0;
    $moduleinfo->emailteachers = 0;
    $moduleinfo->emailothers = '';
    $moduleinfo->deliveryoption = 'I';
    $moduleinfo->usecustomfilename = 0;
    $moduleinfo->customfilenamepattern = '';
    $moduleinfo->requiredtime = 0;
    $moduleinfo->verifyany = 1;
    $moduleinfo->protection_print = 0;
    $moduleinfo->protection_modify = 0;
    $moduleinfo->protection_copy = 0;
    $moduleinfo->completion = COMPLETION_TRACKING_MANUAL;

    $moduleinfo = add_moduleinfo($moduleinfo, $course);
    $cmid = $moduleinfo->coursemodule;

    course_add_cm_to_section($courseid, $cmid, $sectionnum);

    if (!empty($options['availability'])) {
        set_activity_availability($cmid, $options['availability']);
    }

    return $cmid;
}

/**
 * Update section name and summary.
 */
function update_section($courseid, $sectionnum, $name, $summary) {
    global $DB;

    $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum]);
    if ($section) {
        $section->name = $name;
        $section->summary = $summary;
        $section->summaryformat = FORMAT_HTML;
        $DB->update_record('course_sections', $section);
    }
}

/**
 * Build one standard week (micro lessons + weekly quiz + assignment).
 *
 * @return array{weekly_assignment_cmid:int,micro_quiz_cmids:int[]}
 */
function build_week_from_data(int $courseid, int $sectionnum, array $weekdata, ?int $unlockaftercmid = null): array {
    update_section($courseid, $sectionnum, $weekdata['section_name'], $weekdata['section_summary']);

    $avail = $unlockaftercmid ? availability_require_cm_completion($unlockaftercmid) : null;
    $opts = $avail ? ['availability' => $avail] : [];

    $microquizcmids = [];

    foreach ($weekdata['micro_lessons'] as $lesson) {
        create_page_resource(
            $courseid,
            $sectionnum,
            $lesson['title'],
            $lesson['intro'],
            $lesson['content'],
            $opts
        );

        $practice = $lesson['practice'];
        create_aicode_activity($courseid, $sectionnum, [
            'name' => $practice['name'],
            'intro' => $practice['intro'] ?? 'Latihan coding dengan bantuan AI hint.',
            'description' => $practice['description'],
            'mode' => $practice['mode'] ?? 'training',
            'startercode' => $practice['startercode'],
            'testcases' => $practice['testcases'],
            'htmltemplate' => $practice['htmltemplate'] ?? '',
            'csstemplate' => $practice['csstemplate'] ?? '',
        ], $opts);

        $microquizcmids[] = create_quiz_activity(
            $courseid,
            $sectionnum,
            'Kuis ' . $lesson['title'],
            'Kuis singkat untuk mengecek pemahaman Anda.',
            $lesson['micro_quiz'],
            70,
            $opts
        );
    }

    if (!empty($weekdata['weekly_quiz'])) {
        create_quiz_activity(
            $courseid,
            $sectionnum,
            'Weekly Quiz - Minggu ' . $sectionnum,
            'Kuis mingguan untuk menguji pemahaman keseluruhan materi minggu ini.',
            $weekdata['weekly_quiz'],
            70,
            $opts
        );
    }

    $assignment = $weekdata['weekly_assignment'];
    $assignmentcmid = create_aicode_activity($courseid, $sectionnum, [
        'name' => $assignment['name'],
        'intro' => $assignment['intro'] ?? 'Tugas mingguan — mode ujian tanpa AI hint untuk siswa.',
        'description' => $assignment['description'],
        'mode' => $assignment['mode'] ?? 'exam',
        'startercode' => $assignment['startercode'],
        'testcases' => $assignment['testcases'] ?? '[]',
        'htmltemplate' => $assignment['htmltemplate'] ?? '',
        'csstemplate' => $assignment['csstemplate'] ?? '',
    ], $opts);

    return [
        'weekly_assignment_cmid' => $assignmentcmid,
        'micro_quiz_cmids' => $microquizcmids,
    ];
}

/**
 * Configure gradebook categories and weights.
 *
 * @param array $cmmap Keys: micro_quiz, weekly_quiz, weekly_assignment, final_project => cmid[]
 */
function configure_gradebook(int $courseid, array $cmmap): void {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    require_once($CFG->dirroot . '/lib/grade/grade_category.php');
    require_once($CFG->dirroot . '/lib/grade/grade_item.php');

    $course = get_course($courseid);
    $parent = grade_category::fetch_course_category($courseid);

    $categories = [
        'micro' => ['name' => 'Micro Quizzes', 'weight' => 0.20],
        'weeklyquiz' => ['name' => 'Weekly Quizzes', 'weight' => 0.25],
        'assignment' => ['name' => 'Weekly Assignments', 'weight' => 0.25],
        'final' => ['name' => 'Final Project', 'weight' => 0.30],
    ];

    $catids = [];
    foreach ($categories as $key => $catinfo) {
        $gc = new grade_category();
        $gc->courseid = $courseid;
        $gc->parent = $parent->id;
        $gc->fullname = $catinfo['name'];
        $gc->aggregation = GRADE_AGGREGATE_WEIGHTED_MEAN2;
        $gc->aggregateonlygraded = 0;
        $gc->aggregateoutcomes = 0;
        $gc->timecreated = time();
        $gc->timemodified = time();
        $gc->hidden = 0;
        $gc->insert();
        $catids[$key] = $gc->id;
        $catitem = $gc->get_grade_item();
        if ($catitem) {
            $catitem->aggregationcoef2 = $catinfo['weight'];
            $catitem->update();
        }
    }

    $moveto = function (array $cmids, string $catkey) use ($courseid, $catids) {
        if (empty($cmids)) {
            return;
        }
        $targetcat = grade_category::fetch(['id' => $catids[$catkey]]);
        foreach ($cmids as $cmid) {
            $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
            $item = grade_item::fetch(['itemtype' => 'mod', 'itemmodule' => $cm->modname, 'iteminstance' => $cm->instance, 'courseid' => $courseid]);
            if ($item) {
                $item->set_parent($targetcat->id);
                $item->gradepass = 70;
                $item->update();
            }
        }
    };

    $moveto($cmmap['micro_quiz'] ?? [], 'micro');
    $moveto($cmmap['weekly_quiz'] ?? [], 'weeklyquiz');
    $moveto($cmmap['weekly_assignment'] ?? [], 'assignment');
    $moveto($cmmap['final_project'] ?? [], 'final');

    $courseitem = grade_item::fetch_course_item($courseid);
    if ($courseitem) {
        $courseitem->gradepass = 70;
        $courseitem->aggregationcoef = 0;
        $courseitem->update();
    }

    grade_regrade_final_grades($courseid);
}

/**
 * Enable course completion criteria: course grade >= 70%.
 */
function configure_course_completion(int $courseid, int $finalprojectcmid): void {
    global $DB;

    $DB->set_field('course', 'enablecompletion', COMPLETION_ENABLED, ['id' => $courseid]);

    if (!$DB->record_exists('course_completion_criteria', ['course' => $courseid, 'criteriatype' => COMPLETION_CRITERIA_TYPE_COURSE])) {
        $c = new stdClass();
        $c->course = $courseid;
        $c->criteriatype = COMPLETION_CRITERIA_TYPE_COURSE;
        $c->module = null;
        $c->moduleinstance = null;
        $c->courseinstance = null;
        $c->enrolperiod = null;
        $c->timeend = null;
        $c->gradepass = 70;
        $DB->insert_record('course_completion_criteria', $c);
    }

    if ($finalprojectcmid && !$DB->record_exists('course_completion_criteria', [
        'course' => $courseid,
        'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY,
        'moduleinstance' => $DB->get_field('course_modules', 'instance', ['id' => $finalprojectcmid]),
    ])) {
        $cm = get_coursemodule_from_id('', $finalprojectcmid, 0, false, MUST_EXIST);
        $c = new stdClass();
        $c->course = $courseid;
        $c->criteriatype = COMPLETION_CRITERIA_TYPE_ACTIVITY;
        $c->module = $cm->id;
        $c->moduleinstance = $cm->instance;
        $c->courseinstance = null;
        $c->enrolperiod = null;
        $c->timeend = null;
        $c->gradepass = 0;
        $DB->insert_record('course_completion_criteria', $c);
    }
}

/**
 * Verify customcert plugin is available.
 */
function customcert_plugin_available(): bool {
    global $CFG;
    return file_exists($CFG->dirroot . '/mod/customcert/lib.php');
}

/**
 * Ensure a quiz has the default section row (required for attempt layout in Moodle 4+).
 */
function ensure_quiz_default_section(int $quizid): bool {
    global $DB;
    if ($DB->record_exists('quiz_sections', ['quizid' => $quizid])) {
        return false;
    }
    $DB->insert_record('quiz_sections', [
        'quizid' => $quizid,
        'firstslot' => 1,
        'heading' => '',
        'shufflequestions' => 0,
    ]);
    return true;
}

/**
 * Add missing quiz sections and repair broken attempt layouts for a course.
 *
 * @return array{sections:int,deleted:int,fixedlayout:int}
 */
function repair_course_quiz_structure(int $courseid): array {
    global $DB;

    $sectionsadded = 0;
    foreach ($DB->get_records('quiz', ['course' => $courseid]) as $quiz) {
        if (ensure_quiz_default_section((int)$quiz->id)) {
            $sectionsadded++;
        }
    }

    $attemptfix = cleanup_broken_quiz_attempts($courseid);
    return [
        'sections' => $sectionsadded,
        'deleted' => $attemptfix['deleted'],
        'fixedlayout' => $attemptfix['fixedlayout'],
    ];
}

/**
 * Build comma-separated attempt layout from quiz slots (one question per page).
 */
function build_quiz_attempt_layout_from_slots(array $slots): string {
    $layout = [];
    $prevpage = null;
    foreach ($slots as $slot) {
        if ($prevpage !== null && $slot->page != $prevpage) {
            $layout[] = 0;
        }
        $layout[] = $slot->slot;
        $prevpage = $slot->page;
    }
    $layout[] = 0;
    return implode(',', $layout);
}

/**
 * Delete or repair quiz attempts with empty layout (stale previews after build/repair).
 *
 * @return array{deleted:int,fixedlayout:int}
 */
function cleanup_broken_quiz_attempts(int $courseid): array {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');

    $deleted = 0;
    $fixedlayout = 0;

    $quizzes = $DB->get_records('quiz', ['course' => $courseid]);
    foreach ($quizzes as $quiz) {
        $attempts = $DB->get_records('quiz_attempts', ['quiz' => $quiz->id]);
        if (empty($attempts)) {
            continue;
        }

        $slots = $DB->get_records('quiz_slots', ['quizid' => $quiz->id], 'slot ASC');
        if (empty($slots)) {
            continue;
        }

        $goodlayout = build_quiz_attempt_layout_from_slots($slots);

        foreach ($attempts as $attempt) {
            $slotcount = $DB->count_records('question_attempts', ['questionusageid' => $attempt->uniqueid]);
            $layoutempty = ($attempt->layout === '' || $attempt->layout === null);

            if ($layoutempty) {
                if ($slotcount === 0) {
                    quiz_delete_attempt($attempt, $quiz);
                    $deleted++;
                } else {
                    $DB->set_field('quiz_attempts', 'layout', $goodlayout, ['id' => $attempt->id]);
                    $fixedlayout++;
                }
                continue;
            }

            if ($slotcount === 0) {
                quiz_delete_attempt($attempt, $quiz);
                $deleted++;
                continue;
            }

            $layoutslots = array_filter(explode(',', $attempt->layout), static fn($v) => $v !== '0' && $v !== '');
            if (count($layoutslots) !== $slotcount) {
                quiz_delete_attempt($attempt, $quiz);
                $deleted++;
                continue;
            }

            if ($attempt->layout !== $goodlayout) {
                $DB->set_field('quiz_attempts', 'layout', $goodlayout, ['id' => $attempt->id]);
                $fixedlayout++;
            }
        }
    }

    return ['deleted' => $deleted, 'fixedlayout' => $fixedlayout];
}
