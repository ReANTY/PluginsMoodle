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
$total = count($rows);
$answers = 0;
$mismatches = 0;

foreach ($rows as $r) {
    $matched = $map[$r->name] ?? null;
    $db_starter = trim($r->startercode ?? '');
    
    if ($matched) {
        $json_starter = trim($matched['starter_code'] ?? '');
        $json_solution = trim($matched['solution_code'] ?? '');
        
        $is_solution = ($db_starter === $json_solution && !empty($json_solution));
        $is_starter = ($db_starter === $json_starter);
        
        if ($is_solution && !$is_starter) {
            echo "[ANSWER KEY FOUND] ID {$r->id} '{$r->name}'\n";
            $answers++;
        } elseif (!$is_starter) {
            echo "[DIFF FROM DATASET STARTER] ID {$r->id} '{$r->name}'\n";
            echo "   DB: " . str_replace("\n", " ", substr($db_starter, 0, 60)) . "\n";
            echo "   JSON: " . str_replace("\n", " ", substr($json_starter, 0, 60)) . "\n";
            $mismatches++;
        }
    } else {
        echo "[NOT IN DATASET] ID {$r->id} '{$r->name}' (Course: {$r->course})\n";
        echo "   DB: " . str_replace("\n", " ", substr($db_starter, 0, 60)) . "\n";
    }
}

echo "\nSummary: Total {$total}, Answer keys: {$answers}, Starter mismatches: {$mismatches}\n";
