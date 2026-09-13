<?php
$data = json_decode(file_get_contents(__DIR__ . '/../Bank_Soal_dan_Latihan_JS_FUND_2026/master_dataset.json'), true);

$all = array_merge($data['latihan_aicode'], $data['ujian_aicode']);

foreach ($all as $item) {
    $s = trim($item['starter_code']);
    $sol = trim($item['solution_code']);
    if ($s === $sol) {
        echo "EXACT MATCH (STARTER == SOLUTION):\n";
        echo "ID: {$item['aicode_id']} | {$item['name']}\n";
        echo "Code: {$s}\n";
        echo "--------------------------------------------------------\n";
    }
}
