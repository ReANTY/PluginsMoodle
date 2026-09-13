<?php
$data = json_decode(file_get_contents(__DIR__ . '/../Bank_Soal_dan_Latihan_JS_FUND_2026/master_dataset.json'), true);

$all = array_merge($data['latihan_aicode'], $data['ujian_aicode']);

$suspicious = [];
foreach ($all as $item) {
    $s = trim($item['starter_code']);
    $sol = trim($item['solution_code']);
    
    // Check if lines in starter match lines in solution (ignoring comments)
    $s_lines = array_filter(array_map('trim', explode("\n", $s)), function($l) {
        return !empty($l) && strpos($l, '//') !== 0;
    });
    
    $sol_lines = array_filter(array_map('trim', explode("\n", $sol)), function($l) {
        return !empty($l) && strpos($l, '//') !== 0;
    });
    
    $overlap = array_intersect($s_lines, $sol_lines);
    if (!empty($overlap)) {
        $suspicious[] = [
            'id' => $item['aicode_id'],
            'name' => $item['name'],
            'overlap' => $overlap,
            'starter' => $s,
            'solution' => $sol,
            'desc' => $item['description'],
        ];
    }
}

echo "Found " . count($suspicious) . " items where starter code contains code lines from solution:\n\n";
foreach ($suspicious as $item) {
    echo "ID: {$item['id']} | {$item['name']}\n";
    echo "  Desc: " . substr(str_replace("\n", " ", $item['desc']), 0, 80) . "\n";
    echo "  Overlap lines: " . implode(" | ", $item['overlap']) . "\n";
    echo "--------------------------------------------------------\n";
}
