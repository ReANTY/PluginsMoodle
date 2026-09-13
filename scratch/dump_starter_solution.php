<?php
$data = json_decode(file_get_contents(__DIR__ . '/../Bank_Soal_dan_Latihan_JS_FUND_2026/master_dataset.json'), true);

echo "=== LATIHAN AICODE ===\n";
foreach ($data['latihan_aicode'] as $item) {
    echo "ID: {$item['aicode_id']} | Name: {$item['name']}\n";
    echo "Starter Code:\n" . $item['starter_code'] . "\n";
    echo "Solution Code:\n" . $item['solution_code'] . "\n";
    echo "--------------------------------------------------------\n";
}

echo "=== UJIAN AICODE ===\n";
foreach ($data['ujian_aicode'] as $item) {
    echo "ID: {$item['aicode_id']} | Name: {$item['name']}\n";
    echo "Starter Code:\n" . $item['starter_code'] . "\n";
    echo "Solution Code:\n" . $item['solution_code'] . "\n";
    echo "--------------------------------------------------------\n";
}
