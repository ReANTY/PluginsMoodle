<?php
// =============================================================
// Adaptive Learning AI — AJAX Recommendation Handler
// =============================================================
define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/adaptive_learning_ai/gemini_api.php');
require_once($CFG->dirroot . '/blocks/adaptive_learning_ai/classes/path_manager.php');

require_login();

header('Content-Type: application/json; charset=utf-8');

$raw      = file_get_contents('php://input');
$data     = json_decode($raw, true) ?: [];
$courseid = intval($data['courseid'] ?? optional_param('courseid', 0, PARAM_INT));
$force    = !empty($data['force']) || optional_param('force', 0, PARAM_INT);
$sesskey  = $data['sesskey'] ?? optional_param('sesskey', '', PARAM_RAW);

if (!confirm_sesskey($sesskey)) {
    echo json_encode(['success' => false, 'error' => 'Sesi tidak valid (CSRF). Silakan muat ulang halaman.']);
    exit;
}

if (!$courseid) {
    echo json_encode(['success' => false, 'error' => 'Parameter courseid tidak valid.']);
    exit;
}

global $DB, $USER;

$context = context_course::instance($courseid, IGNORE_MISSING);
if (!$context || (!is_enrolled($context, $USER->id) && !has_capability('moodle/course:view', $context))) {
    echo json_encode(['success' => false, 'error' => 'Akses ditolak. Anda tidak memiliki akses ke kursus ini.']);
    exit;
}

// 1. Ambil nilai quiz terakhir
$userScore    = 0;
$weekNum      = 1;
$quizName     = '-';
$attemptCount = 0;

try {
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
            WHERE q.course = :courseid
              AND qa.userid = :userid
              AND qa.state = 'finished'
            ORDER BY qa.timefinish DESC
            LIMIT 1";

    $result = $DB->get_record_sql($sql, ['courseid' => $courseid, 'userid' => $USER->id]);

    if ($result) {
        $maxmarks = (!empty($result->quiz_maxsumgrades) && $result->quiz_maxsumgrades > 0)
            ? (float) $result->quiz_maxsumgrades
            : ((!empty($result->maxgrade) && $result->maxgrade > 0) ? (float) $result->maxgrade : 100);

        $rawScore  = (float) ($result->sumgrades ?? 0);
        $userScore = round(($rawScore / $maxmarks) * 100);
        $quizName  = $result->quizname;
        $weekNum   = intval($result->secnum);
    }

    $attemptCount = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {quiz_attempts} qa
         JOIN {quiz} q ON qa.quiz = q.id
         WHERE q.course = ? AND qa.userid = ? AND qa.state = 'finished'",
        [$courseid, $USER->id]
    );
} catch (Exception $e) {
    $userScore = 0;
}

$primaryThreshold = (int) (get_config('block_adaptive_learning_ai', 'primary_threshold') ?: (get_config('block_adaptive_learning_ai', 'remedial_threshold') ?: 70));
$expertThreshold  = (int) (get_config('block_adaptive_learning_ai', 'expert_threshold') ?: (get_config('block_adaptive_learning_ai', 'advanced_threshold') ?: 85));

// Evaluasi performa kognitif
$prevWeek = max(1, $weekNum - 1);
$sectionPerf = \block_adaptive_learning_ai\path_manager::calculate_section_performance($courseid, (int)$USER->id, $prevWeek);
if ($sectionPerf !== null && isset($sectionPerf['composite'])) {
    $userScore = round($sectionPerf['composite']);
}

$level = 'INTERMEDIATE';
if ($userScore === 0 && $attemptCount === 0) {
    $level = 'NODATA';
} elseif ($userScore < $primaryThreshold) {
    $level = 'PRIMARY';
} elseif ($userScore < $expertThreshold) {
    $level = 'INTERMEDIATE';
} else {
    $level = 'EXPERT';
}

$recData = alai_get_course_material_recommendations($courseid, (int)$USER->id, $userScore, $level, $quizName, $weekNum, $force);

// Render HTML
$recHtml = '';
if (!empty($recData['recommendations'])) {
    $recHtml .= '<div class="alai-rec-list-header"><i class="fas fa-book-reader"></i> Materi yang Disarankan:</div>';
    $recHtml .= '<div class="alai-rec-list">';
    foreach ($recData['recommendations'] as $item) {
        $purposeClass = 'alai-badge-core';
        $purposeText = $item['purpose'] ?? 'Rekomendasi';
        if (stripos($purposeText, 'perbaikan') !== false || stripos($purposeText, 'remedial') !== false) {
            $purposeClass = 'alai-badge-remedial';
        } elseif (stripos($purposeText, 'lanjutan') !== false || stripos($purposeText, 'pengayaan') !== false || stripos($purposeText, 'tantangan') !== false) {
            $purposeClass = 'alai-badge-advanced';
        }

        $iconHtml = alai_render_module_icon($item['type']);
        $recHtml .= '
        <div class="alai-rec-item">
            <div class="alai-rec-item-icon">' . $iconHtml . '</div>
            <div class="alai-rec-item-content">
                <div class="alai-rec-item-meta">
                    <span class="alai-sec-tag">' . htmlspecialchars($item['section']) . '</span>
                    <span class="alai-purpose-tag ' . $purposeClass . '">' . htmlspecialchars($purposeText) . '</span>
                </div>
                <a href="' . s($item['url']) . '" class="alai-rec-item-title" target="_top">' . htmlspecialchars($item['title']) . '</a>
                ' . (!empty($item['reason']) ? '<div class="alai-rec-item-reason"><i class="fas fa-info-circle"></i> ' . htmlspecialchars($item['reason']) . '</div>' : '') . '
            </div>
            <a href="' . s($item['url']) . '" class="alai-rec-item-action" title="Buka Materi" target="_top">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>';
    }
    $recHtml .= '</div>';
}

echo json_encode([
    'success' => true,
    'data'    => $recData,
    'html'    => $recHtml
]);
