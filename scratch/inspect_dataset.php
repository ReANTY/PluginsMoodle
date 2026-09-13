<?php
$data = json_decode(file_get_contents(__DIR__ . '/../Bank_Soal_dan_Latihan_JS_FUND_2026/master_dataset.json'), true);
echo "Keys: " . implode(', ', array_keys($data)) . "\n";
if (isset($data['latihan_aicode'])) {
    echo "Latihan count: " . count($data['latihan_aicode']) . "\n";
    echo "Sample latihan keys: " . implode(', ', array_keys($data['latihan_aicode'][0])) . "\n";
    print_r($data['latihan_aicode'][0]);
}
if (isset($data['ujian_aicode'])) {
    echo "Ujian count: " . count($data['ujian_aicode']) . "\n";
    echo "Sample ujian keys: " . implode(', ', array_keys($data['ujian_aicode'][0])) . "\n";
    print_r($data['ujian_aicode'][0]);
}
