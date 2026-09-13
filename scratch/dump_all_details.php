<?php
$data = json_decode(file_get_contents(__DIR__ . '/../Bank_Soal_dan_Latihan_JS_FUND_2026/master_dataset.json'), true);

echo "=== ALL LATIHAN AICODE ===\n";
foreach ($data['latihan_aicode'] as $item) {
    echo "ID: {$item['aicode_id']} | {$item['name']}\n";
    echo "--- DESCRIPTION:\n" . trim($item['description']) . "\n";
    echo "--- STARTER CODE:\n" . trim($item['starter_code']) . "\n";
    echo "--- SOLUTION CODE:\n" . trim($item['solution_code']) . "\n";
    echo "========================================================\n";
}

echo "=== ALL UJIAN AICODE ===\n";
foreach ($data['ujian_aicode'] as $item) {
    echo "ID: {$item['aicode_id']} | {$item['name']}\n";
    echo "--- DESCRIPTION:\n" . trim($item['description']) . "\n";
    echo "--- STARTER CODE:\n" . trim($item['starter_code']) . "\n";
    echo "--- SOLUTION CODE:\n" . trim($item['solution_code']) . "\n";
    echo "========================================================\n";
}
