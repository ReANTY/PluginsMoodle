<?php
$data = json_decode(file_get_contents(__DIR__ . '/../Bank_Soal_dan_Latihan_JS_FUND_2026/master_dataset.json'), true);

$all = array_merge($data['latihan_aicode'], $data['ujian_aicode']);

echo "Checking " . count($all) . " items...\n";

foreach ($all as $item) {
    $starter = trim($item['starter_code']);
    $solution = trim($item['solution_code']);
    
    // Check if starter has executable solution lines
    echo "ID: {$item['aicode_id']} | {$item['name']}\n";
    echo "  [STARTER]:\n" . preg_replace('/^/m', '    ', $starter) . "\n";
    echo "  [SOLUTION]:\n" . preg_replace('/^/m', '    ', $solution) . "\n";
    echo "--------------------------------------------------------\n";
}
