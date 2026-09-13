<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
global $DB;

$dataset = json_decode(file_get_contents(__DIR__ . '/../Bank_Soal_dan_Latihan_JS_FUND_2026/master_dataset.json'), true);

$map = [];
foreach ($dataset['latihan_aicode'] as $item) {
    $map[$item['name']] = $item;
}
foreach ($dataset['ujian_aicode'] as $item) {
    $map[$item['name']] = $item;
}

$rows = $DB->get_records('aicode', null, 'id ASC');
foreach ($rows as $r) {
    echo "ID {$r->id}: {$r->name}\n";
    $matched = $map[$r->name] ?? null;
    if ($matched) {
        $db_starter = trim($r->startercode ?? '');
        $json_starter = trim($matched['starter_code'] ?? '');
        $json_solution = trim($matched['solution_code'] ?? '');
        
        $is_solution = ($db_starter === $json_solution && !empty($json_solution));
        $is_starter = ($db_starter === $json_starter);
        
        echo "  Matches dataset: YES\n";
        echo "  Is DB starter == solution? " . ($is_solution ? "YES (HAS ANSWER KEY!)" : "NO") . "\n";
        echo "  Is DB starter == starter_code? " . ($is_starter ? "YES" : "NO") . "\n";
        if (!$is_starter) {
            echo "  DB STARTER:\n    " . str_replace("\n", "\n    ", substr($db_starter, 0, 100)) . "\n";
            echo "  JSON STARTER:\n    " . str_replace("\n", "\n    ", substr($json_starter, 0, 100)) . "\n";
            echo "  JSON SOLUTION:\n    " . str_replace("\n", "\n    ", substr($json_solution, 0, 100)) . "\n";
        }
    } else {
        echo "  Matches dataset: NO\n";
        echo "  DB STARTER: " . substr(trim($r->startercode ?? ''), 0, 80) . "\n";
    }
    echo "--------------------------------------------------------\n";
}
