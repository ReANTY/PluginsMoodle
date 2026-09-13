<?php
$file = __DIR__ . '/../Bank_Soal_dan_Latihan_JS_FUND_2026/master_dataset.json';
$data = json_decode(file_get_contents($file), true);

foreach ($data['latihan_aicode'] as &$item) {
    if ($item['aicode_id'] == 362) {
        $item['starter_code'] = "// Tampilkan 'Siap membangun Todo List!' di elemen #info saat halaman dimuat\n";
        echo "Updated 362 starter_code in dataset\n";
    }
    if ($item['aicode_id'] == 352) {
        $item['starter_code'] = "function tambah(a, b) {\n  // Return hasil penjumlahan a dan b\n}\n\ndocument.getElementById('btnHitung').addEventListener('click', function() {\n  // Ambil angka1 dan angka2, panggil tambah(a, b), tampilkan 'Hasil: [jumlah]' di #hasil\n});\n";
        echo "Updated 352 starter_code in dataset\n";
    }
}
unset($item);

file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo "Saved master_dataset.json successfully\n";
