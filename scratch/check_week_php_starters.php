<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/completionlib.php');

$files = glob($CFG->dirroot . '/admin/js_week*.php');
foreach ($files as $f) {
    if (basename($f) === 'js_week_helpers.php' || basename($f) === 'js_course_week_helpers.php') continue;
    echo "=== File: " . basename($f) . " ===\n";
    $content = require($f);
    if (!is_array($content)) continue;
    
    // check lesson_enrichment
    if (isset($content['lesson_enrichment'])) {
        foreach ($content['lesson_enrichment'] as $k => $item) {
            if (isset($item['practice'])) {
                $p = $item['practice'];
                echo "Practice: {$p['name']}\n";
                echo "  Starter:\n    " . str_replace("\n", "\n    ", trim($p['startercode'] ?? '')) . "\n";
            }
        }
    }
    
    // check micro_lessons
    if (isset($content['micro_lessons'])) {
        foreach ($content['micro_lessons'] as $k => $item) {
            if (isset($item['practice'])) {
                $p = $item['practice'];
                echo "Practice: {$p['name']}\n";
                echo "  Starter:\n    " . str_replace("\n", "\n    ", trim($p['startercode'] ?? '')) . "\n";
            }
        }
    }
    
    // check weekly_assignment
    if (isset($content['weekly_assignment'])) {
        $p = $content['weekly_assignment'];
        echo "Weekly Assignment: {$p['name']}\n";
        echo "  Starter:\n    " . str_replace("\n", "\n    ", trim($p['startercode'] ?? '')) . "\n";
    }
    
    // check final_project
    if (isset($content['final_project'])) {
        $p = $content['final_project'];
        echo "Final Project: {$p['name']}\n";
        echo "  Starter:\n    " . str_replace("\n", "\n    ", trim($p['startercode'] ?? '')) . "\n";
    }
}
