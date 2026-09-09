<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/blocklib.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/adaptive_learning_ai/block_adaptive_learning_ai.php');

global $DB, $USER, $COURSE;

// User 4 is the student who took the quiz
$USER = $DB->get_record('user', ['id' => 4]);
$COURSE = $DB->get_record('course', ['id' => 22]);

$block = new block_adaptive_learning_ai();
$block->init();
$block->page = new moodle_page();
$block->page->set_context(context_course::instance(22));
$block->page->set_course($COURSE);

$content = $block->get_content();

$sql = "SELECT qa.id, qa.sumgrades, q.grade AS maxgrade,
               q.sumgrades AS quiz_maxsumgrades,
               q.name AS quizname, qa.timefinish, q.id AS quizid,
               cs.section AS secnum
        FROM {quiz_attempts} qa
        JOIN {quiz} q ON qa.quiz = q.id
        JOIN {course_modules} cm
            ON cm.instance = q.id
            AND cm.course = q.course
            AND cm.module = (SELECT id FROM {modules} WHERE name = 'quiz')
        JOIN {course_sections} cs ON cs.id = cm.section
        WHERE q.course = 22
          AND qa.userid = 4
          AND qa.state = 'finished'
        ORDER BY qa.timefinish DESC
        LIMIT 1";

$res = $DB->get_record_sql($sql);
echo "Debug raw sumgrades: " . var_export($res->sumgrades, true) . "\n";
echo "Debug quiz_maxsumgrades: " . var_export($res->quiz_maxsumgrades, true) . "\n";
echo "Debug maxgrade: " . var_export($res->maxgrade, true) . "\n";

$maxmarks = (!empty($res->quiz_maxsumgrades) && $res->quiz_maxsumgrades > 0)
    ? (float) $res->quiz_maxsumgrades
    : ((!empty($res->maxgrade) && $res->maxgrade > 0) ? (float) $res->maxgrade : 100);

$rawScore = (float) ($res->sumgrades ?? 0);
$qStr = (round($rawScore, 1) == round($rawScore) ? round($rawScore) : round($rawScore, 1)) . '/' . 
        (round($maxmarks, 1) == round($maxmarks) ? round($maxmarks) : round($maxmarks, 1)) . ' benar';
echo "Evaluated qStr: " . var_export($qStr, true) . "\n";

// Check score in HTML
preg_match('/<div class="alai-metric-val">([^<]+)<\/div>\s*<div class="alai-metric-lbl">Quiz Score<\/div>/', $content->text, $scoreMatch);
echo "Quiz Score displayed: " . ($scoreMatch[1] ?? 'NOT FOUND') . "\n";

// Check level in HTML
preg_match('/<div class="alai-metric-val"[^>]*>([^<]+)<\/div>\s*<div class="alai-metric-lbl">Level Saat Ini<\/div>/', $content->text, $levelMatch);
echo "Level displayed: " . trim($levelMatch[1] ?? 'NOT FOUND') . "\n";

// Check chips
preg_match('/<div class="alai-stats-row">(.*?)<\/div>/s', $content->text, $chipsMatch);
echo "Contains '7/10 benar': " . (strpos($content->text, '7/10 benar') !== false ? 'YES' : 'NO') . "\n";
echo "Chips raw HTML:\n" . ($chipsMatch[0] ?? '') . "\n";

// Check AJAX calculation as well
$sql = "SELECT qa.id, qa.sumgrades, q.grade AS maxgrade, q.sumgrades AS quiz_maxsumgrades
        FROM {quiz_attempts} qa
        JOIN {quiz} q ON qa.quiz = q.id
        WHERE q.course = 22
          AND qa.userid = 4
          AND qa.state = 'finished'
        ORDER BY qa.timefinish DESC";
$lastAttempt = $DB->get_record_sql($sql, [], IGNORE_MULTIPLE);
$maxmarks = (!empty($lastAttempt->quiz_maxsumgrades) && $lastAttempt->quiz_maxsumgrades > 0)
    ? (float) $lastAttempt->quiz_maxsumgrades
    : ((!empty($lastAttempt->maxgrade) && $lastAttempt->maxgrade > 0) ? (float) $lastAttempt->maxgrade : 100);
$ajaxScore = round(((float) $lastAttempt->sumgrades / $maxmarks) * 100);
echo "AJAX Handler calculated score: {$ajaxScore}%\n";
