<?php
/**
 * HTML helpers for JavaScript Fundamental course micro lessons.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Wrap micro lesson body HTML.
 */
function js_ml_wrap(string $title, string $body): string {
    return '<div class="micro-lesson"><h3>' . $title . '</h3>' . $body . '</div>';
}

/**
 * Build explanation block.
 */
function js_ml_explain(string $html): string {
    return '<h4>Penjelasan</h4>' . $html;
}

/**
 * Build bullet points block.
 */
function js_ml_points(array $points): string {
    $items = '';
    foreach ($points as $p) {
        $items .= '<li>' . $p . '</li>';
    }
    return '<h4>Poin Penting</h4><ul>' . $items . '</ul>';
}

/**
 * Build code example block.
 */
function js_ml_code(string $label, string $code, string $lang = 'javascript'): string {
    $escaped = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    return '<h4>' . $label . '</h4><pre><code class="language-' . $lang . '">' . $escaped . '</code></pre>';
}

/**
 * Build note alert.
 */
function js_ml_note(string $text): string {
    return '<div class="alert alert-warning"><strong>Catatan:</strong><br>' . $text . '</div>';
}

/**
 * Build info alert.
 */
function js_ml_info(string $text): string {
    return '<div class="alert alert-info">' . $text . '</div>';
}

/**
 * Standard week section summary HTML.
 */
function js_week_summary(string $goalshtml, int $lessons = 4, int $minutes = 60): string {
    return '<div class="week-summary">
        <h4>Tujuan Mingguan</h4>
        ' . $goalshtml . '
        <h4>Materi</h4>
        <p>' . $lessons . ' Micro Lessons | 4 Praktik Coding | 4 Kuis Singkat | 1 Weekly Quiz | 1 Weekly Assignment</p>
        <h4>Estimasi Waktu</h4>
        <p>Total: ±' . $minutes . ' menit</p>
    </div>';
}

/**
 * MCQ helper.
 */
function js_mcq(string $question, array $options, int $correct, string $feedback): array {
    return [
        'question' => $question,
        'options' => $options,
        'correct' => $correct,
        'feedback' => $feedback,
    ];
}

/**
 * Format MCQ answer as letter + text for teacher key page.
 */
function js_mcq_answer_label(array $q): string {
    $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    $correct = (int)($q['correct'] ?? 0);
    $text = strip_tags((string)($q['options'][$correct] ?? ''));
    $letter = $letters[$correct] ?? '?';
    return '<strong>' . $letter . '.</strong> ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Render HTML block for a set of MCQ answer keys.
 */
function js_render_mcq_key_html(array $questions, string $heading): string {
    if (empty($questions)) {
        return '';
    }
    $html = '<h4>' . $heading . '</h4><ol>';
    foreach ($questions as $i => $q) {
        $num = $i + 1;
        $qtext = strip_tags((string)($q['question'] ?? ''));
        $feedback = htmlspecialchars((string)($q['feedback'] ?? ''), ENT_QUOTES, 'UTF-8');
        $html .= '<li><p><strong>Soal ' . $num . ':</strong> ' . htmlspecialchars($qtext, ENT_QUOTES, 'UTF-8') . '</p>';
        $html .= '<p>Jawaban: ' . js_mcq_answer_label($q) . '</p>';
        if ($feedback !== '') {
            $html .= '<p><em>Feedback:</em> ' . $feedback . '</p>';
        }
        $html .= '</li>';
    }
    $html .= '</ol>';
    return $html;
}

/**
 * Render escaped code block for teacher key page.
 */
function js_render_code_block(string $code, string $lang = 'javascript'): string {
    $escaped = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    return '<pre><code class="language-' . $lang . '">' . $escaped . '</code></pre>';
}

/**
 * Merge base micro-quiz with supplemental questions (target 10 per lesson).
 */
function js_micro_quiz_full(array $base, array $extra): array {
    return array_merge($base, $extra);
}

/**
 * Standard HTML shell for Aicode iframe preview (weeks 6–8).
 */
function js_iframe_html(string $bodyinner, string $title = 'Preview'): string {
    return '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>' . $title . '</title>
</head>
<body>
' . $bodyinner . '
<script>
// Tulis JavaScript Anda di bawah ini

</script>
</body>
</html>';
}

/**
 * Default iframe page styles.
 */
function js_iframe_css(): string {
    return 'body { font-family: "Segoe UI", Arial, sans-serif; max-width: 520px; margin: 24px auto; padding: 16px; }
button, input { font-size: 16px; padding: 8px 12px; margin: 4px; }
button { cursor: pointer; background: #2563eb; color: #fff; border: none; border-radius: 6px; }
button:hover { background: #1d4ed8; }
input { border: 1px solid #ccc; border-radius: 6px; }
table { width: 100%; border-collapse: collapse; margin-top: 12px; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f3f4f6; }
ul { list-style: none; padding: 0; }
li { padding: 8px; border-bottom: 1px solid #eee; }';
}
