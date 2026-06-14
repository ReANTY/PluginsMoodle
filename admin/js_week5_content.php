<?php
/**
 * MINGGU 5: PERULANGAN (LOOPS)
 * Micro Lessons: for, while, do-while, break/continue
 */

require_once(__DIR__ . '/js_course_week_helpers.php');

$quizextra = require(__DIR__ . '/js_quiz_supplements.php');

$week5_data = [
    'section_name' => 'Minggu 5: Perulangan',
    'section_summary' => js_week_summary(
        '<ul>
            <li>Menulis perulangan dengan <code>for</code>, <code>while</code>, dan <code>do-while</code></li>
            <li>Memahami kapan memilih jenis loop yang tepat</li>
            <li>Menggunakan <code>break</code> dan <code>continue</code> untuk mengontrol alur loop</li>
            <li>Menyelesaikan masalah berulang dengan kode JavaScript yang rapi</li>
        </ul>',
        4,
        65
    ),

    'micro_lessons' => [
        [
            'title' => 'ML5.1 - Perulangan for',
            'intro' => 'Pelajari sintaks loop for untuk mengulangi kode sejumlah kali yang sudah diketahui.',
            'content' => js_ml_wrap(
                'Perulangan for',
                js_ml_explain(
                    '<p>Loop <strong>for</strong> digunakan ketika Anda sudah tahu berapa kali perulangan akan dijalankan. '
                    . 'Struktur umum: inisialisasi, kondisi, dan langkah penambahan/pengurangan.</p>'
                ) .
                js_ml_points([
                    'Tiga bagian dalam kurung: <code>inisialisasi; kondisi; langkah</code>',
                    'Blok kode di dalam kurung kurawal <code>{ }</code> dijalankan berulang selama kondisi bernilai true',
                    'Variabel penghitung (misalnya <code>i</code>) biasanya bertipe <code>let</code>',
                    'Cocok untuk iterasi array berdasarkan indeks',
                ]) .
                js_ml_code(
                    'Contoh: cetak angka 1 sampai 5',
                    "for (let i = 1; i <= 5; i++) {\n    console.log(i);\n}\n// Output: 1, 2, 3, 4, 5"
                ) .
                js_ml_code(
                    'Contoh: jumlahkan angka 1 sampai n',
                    "function jumlahSampai(n) {\n    let total = 0;\n    for (let i = 1; i <= n; i++) {\n        total += i;\n    }\n    return total;\n}\n\nconsole.log(jumlahSampai(5)); // 15"
                ) .
                js_ml_note('Pastikan kondisi loop pada akhirnya menjadi false, agar tidak terjadi infinite loop.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML5.1 - Jumlah dengan for',
                'mode' => 'training',
                'description' => '<p>Buat fungsi <code>jumlahSampai(n)</code> yang mengembalikan penjumlahan bilangan bulat dari 1 sampai <code>n</code> menggunakan loop <code>for</code>.</p>'
                    . '<ul><li>Parameter: <code>n</code> (angka bulat positif)</li><li>Return: total penjumlahan</li><li>Contoh: <code>jumlahSampai(5)</code> → <code>15</code></li></ul>',
                'startercode' => "function jumlahSampai(n) {\n    // Gunakan for loop\n    \n}\n\nconsole.log(jumlahSampai(5));\nconsole.log(jumlahSampai(10));",
                'testcases' => '[{"input":"5","expected":"15"},{"input":"10","expected":"55"},{"input":"1","expected":"1"},{"input":"0","expected":"0"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq(
                    'Bagian manakah yang menjadi kondisi berhenti pada loop for?',
                    ['Langkah i++', 'Kurung kurawal { }', 'Ekspresi i <= 5', 'Kata kunci for'],
                    2,
                    'Benar. Kondisi (misalnya i <= 5) dievaluasi setiap iterasi; jika false, loop berhenti.'
                ),
                js_mcq(
                    'Output kode berikut: for (let i = 0; i < 3; i++) { console.log(i); }',
                    ['1 2 3', '0 1 2', '0 1 2 3', '3'],
                    1,
                    'Loop dimulai dari i = 0 dan berhenti saat i < 3, sehingga mencetak 0, 1, 2.'
                ),
                js_mcq(
                    'Kapan loop for paling tepat digunakan?',
                    ['Ketika jumlah iterasi sudah diketahui', 'Ketika kondisi hanya dicek di akhir', 'Hanya untuk array kosong', 'Hanya untuk string'],
                    0,
                    'for cocok ketika Anda tahu berapa kali perulangan diperlukan, misalnya iterasi indeks 0..n-1.'
                ),
            ], $quizextra[5][0] ?? []),
        ],
        [
            'title' => 'ML5.2 - Perulangan while',
            'intro' => 'Pelajari loop while yang mengecek kondisi sebelum menjalankan blok kode.',
            'content' => js_ml_wrap(
                'Perulangan while',
                js_ml_explain(
                    '<p>Loop <strong>while</strong> menjalankan blok kode selama kondisi bernilai <code>true</code>. '
                    . 'Kondisi dicek <em>sebelum</em> setiap iterasi. Jika kondisi awal sudah false, blok tidak pernah dijalankan.</p>'
                ) .
                js_ml_points([
                    'Sintaks: <code>while (kondisi) { ... }</code>',
                    'Pastikan ada perubahan variabel di dalam loop agar kondisi akhirnya false',
                    'Cocok ketika jumlah iterasi belum pasti (misalnya menunggu input valid)',
                    'Hati-hati infinite loop jika kondisi tidak pernah berubah',
                ]) .
                js_ml_code(
                    'Contoh: hitung mundur',
                    "let hitung = 5;\nwhile (hitung > 0) {\n    console.log(hitung);\n    hitung--;\n}\nconsole.log('Selesai!');"
                ) .
                js_ml_code(
                    'Contoh: jumlahkan elemen array',
                    "function jumlahArray(arr) {\n    let i = 0;\n    let total = 0;\n    while (i < arr.length) {\n        total += arr[i];\n        i++;\n    }\n    return total;\n}\n\nconsole.log(jumlahArray([10, 20, 30])); // 60"
                ) .
                js_ml_info('Gunakan while ketika Anda tidak tahu pasti berapa kali loop akan berjalan, tetapi tahu kapan harus berhenti.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML5.2 - Jumlah array dengan while',
                'mode' => 'training',
                'description' => '<p>Buat fungsi <code>jumlahArray(arr)</code> yang menjumlahkan semua angka dalam array menggunakan loop <code>while</code> (bukan <code>for</code>).</p>',
                'startercode' => "function jumlahArray(arr) {\n    // Gunakan while loop\n    \n}\n\nconsole.log(jumlahArray([10, 20, 30]));\nconsole.log(jumlahArray([5]));",
                'testcases' => '[{"input":"[10, 20, 30]","expected":"60"},{"input":"[5]","expected":"5"},{"input":"[]","expected":"0"},{"input":"[1, 2, 3, 4]","expected":"10"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq(
                    'Kapan blok kode while pertama kali dieksekusi?',
                    ['Setelah kondisi dicek dan bernilai true', 'Sebelum kondisi dicek', 'Hanya jika variabel genap', 'Selalu tepat satu kali'],
                    0,
                    'while mengecek kondisi dulu; jika true, barulah blok dijalankan.'
                ),
                js_mcq(
                    'Apa risiko utama jika variabel penghitung tidak diubah di dalam while?',
                    ['Syntax error', 'Infinite loop', 'Array otomatis kosong', 'Return selalu null'],
                    1,
                    'Tanpa perubahan kondisi, loop bisa berjalan selamanya (infinite loop).'
                ),
                js_mcq(
                    'Output: let x = 3; while (x > 0) { console.log(x); x--; }',
                    ['3 2 1', '3 2 1 0', '2 1 0', '321'],
                    0,
                    'x berkurang setiap iterasi: 3, lalu 2, lalu 1; berhenti saat x = 0.'
                ),
            ], $quizextra[5][1] ?? []),
        ],
        [
            'title' => 'ML5.3 - Perulangan do-while',
            'intro' => 'Pelajari do-while yang menjalankan blok minimal satu kali sebelum mengecek kondisi.',
            'content' => js_ml_wrap(
                'Perulangan do-while',
                js_ml_explain(
                    '<p>Loop <strong>do-while</strong> mirip <code>while</code>, tetapi kondisi dicek <em>setelah</em> blok kode dijalankan. '
                    . 'Artinya, blok selalu dijalankan minimal satu kali.</p>'
                ) .
                js_ml_points([
                    'Sintaks: <code>do { ... } while (kondisi);</code>',
                    'Titik koma setelah kondisi while wajib ada',
                    'Cocok untuk menu, validasi input, atau aksi yang harus dijalankan sekali dulu',
                    'Perbedaan utama dengan while: pengecekan kondisi di akhir',
                ]) .
                js_ml_code(
                    'Contoh: minimal satu kali cetak',
                    "let angka = 10;\ndo {\n    console.log(angka);\n    angka++;\n} while (angka < 5);\n// Tetap mencetak 10 sekali, meski kondisi sudah false"
                ) .
                js_ml_code(
                    'Contoh: hitung mundur dengan do-while',
                    "function hitungMundur(n) {\n    let hasil = [];\n    do {\n        hasil.push(n);\n        n--;\n    } while (n > 0);\n    return hasil;\n}\n\nconsole.log(JSON.stringify(hitungMundur(3))); // [3,2,1]"
                ) .
                js_ml_note('Gunakan do-while hanya jika Anda memang perlu menjalankan blok setidaknya sekali sebelum mengecek kondisi.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML5.3 - Hitung mundur (do-while)',
                'mode' => 'training',
                'description' => '<p>Buat fungsi <code>hitungMundur(n)</code> yang mengembalikan array angka dari <code>n</code> turun ke <code>1</code> menggunakan <code>do-while</code>.</p>'
                    . '<p>Contoh: <code>hitungMundur(3)</code> → <code>[3,2,1]</code>. Jika <code>n &lt;= 0</code>, return <code>[]</code>. '
                    . 'Gunakan <code>JSON.stringify</code> pada console.log untuk array.</p>',
                'startercode' => "function hitungMundur(n) {\n    // Gunakan do-while\n    \n}\n\nconsole.log(JSON.stringify(hitungMundur(3)));\nconsole.log(JSON.stringify(hitungMundur(1)));",
                'testcases' => '[{"input":"3","expected":"[3,2,1]"},{"input":"1","expected":"[1]"},{"input":"5","expected":"[5,4,3,2,1]"},{"input":"0","expected":"[]"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq(
                    'Perbedaan utama do-while dibanding while adalah…',
                    ['Kondisi dicek setelah blok dijalankan', 'Tidak memerlukan kurung kurawal', 'Hanya untuk array', 'Tidak bisa infinite loop'],
                    0,
                    'do-while mengeksekusi blok dulu, baru mengecek kondisi di akhir.'
                ),
                js_mcq(
                    'Berapa kali minimal blok do-while dijalankan jika kondisi awal false?',
                    ['0 kali', '1 kali', '2 kali', 'Tergantung panjang array'],
                    1,
                    'Blok do selalu dijalankan minimal sekali, baru kemudian kondisi while dievaluasi.'
                ),
                js_mcq(
                    'Sintaks yang benar untuk do-while adalah…',
                    ['do { } while kondisi', 'do { } while (kondisi);', 'while do { }', 'do while { }'],
                    1,
                    'Format standar: do { ... } while (kondisi); — perhatikan titik koma di akhir.'
                ),
            ], $quizextra[5][2] ?? []),
        ],
        [
            'title' => 'ML5.4 - break dan continue',
            'intro' => 'Pelajari cara menghentikan loop lebih awal atau melewati satu iterasi.',
            'content' => js_ml_wrap(
                'break dan continue',
                js_ml_explain(
                    '<p><strong>break</strong> menghentikan loop sepenuhnya dan melanjutkan kode setelah loop. '
                    . '<strong>continue</strong> melewati sisa iterasi saat ini dan langsung ke iterasi berikutnya.</p>'
                ) .
                js_ml_points([
                    '<code>break</code> — keluar dari loop (for/while/do-while)',
                    '<code>continue</code> — loncat ke iterasi berikutnya tanpa mengeksekusi sisa blok',
                    'break cocok saat target sudah ditemukan (misalnya pencarian)',
                    'continue cocok untuk melewati kondisi tertentu (misalnya angka ganjil)',
                ]) .
                js_ml_code(
                    'Contoh: break saat menemukan angka',
                    "let angka = [2, 7, 4, 9, 1];\nlet ditemukan = -1;\n\nfor (let i = 0; i < angka.length; i++) {\n    if (angka[i] === 9) {\n        ditemukan = i;\n        break;\n    }\n}\nconsole.log(ditemukan); // 3"
                ) .
                js_ml_code(
                    'Contoh: continue untuk lewati genap',
                    "for (let i = 1; i <= 6; i++) {\n    if (i % 2 === 0) {\n        continue;\n    }\n    console.log(i); // 1, 3, 5\n}"
                ) .
                js_ml_note('Jangan berlebihan menggunakan break/continue; kode yang terlalu banyak loncat bisa sulit dibaca.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML5.4 - Cari indeks (break)',
                'mode' => 'training',
                'description' => '<p>Buat fungsi <code>cariIndeks(arr, target)</code> yang mengembalikan indeks pertama <code>target</code> dalam array, atau <code>-1</code> jika tidak ada. Gunakan <code>break</code> setelah ditemukan.</p>',
                'startercode' => "function cariIndeks(arr, target) {\n    // Gunakan loop dan break\n    \n}\n\nconsole.log(cariIndeks([2, 7, 4, 9], 9));\nconsole.log(cariIndeks([1, 2, 3], 5));",
                'testcases' => '[{"input":"[2, 7, 4, 9], 9","expected":"3"},{"input":"[1, 2, 3], 5","expected":"-1"},{"input":"[10], 10","expected":"0"},{"input":"[], 1","expected":"-1"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq(
                    'Apa efek perintah break di dalam loop for?',
                    ['Melewati satu iterasi', 'Menghentikan loop sepenuhnya', 'Mengulang dari awal', 'Mengubah tipe variabel'],
                    1,
                    'break keluar dari loop dan melanjutkan baris kode setelah loop.'
                ),
                js_mcq(
                    'Apa efek continue di dalam loop?',
                    ['Menghentikan seluruh program', 'Loncat ke iterasi berikutnya', 'Menghapus array', 'Mengubah kondisi while menjadi false'],
                    1,
                    'continue melewati sisa blok pada iterasi saat ini dan lanjut ke iterasi berikutnya.'
                ),
                js_mcq(
                    'Kapan continue lebih tepat daripada break?',
                    ['Saat ingin melewati nilai tertentu tetapi loop tetap lanjut', 'Saat ingin menghentikan program', 'Saat array kosong', 'Saat tidak ada kondisi'],
                    0,
                    'continue berguna untuk skip (misalnya angka genap) sementara loop masih berjalan.'
                ),
            ], $quizextra[5][3] ?? []),
        ],
    ],

    'weekly_quiz' => [
        js_mcq('Loop manakah yang mengecek kondisi setelah blok dijalankan?', ['for', 'while', 'do-while', 'if'], 2, 'do-while mengeksekusi blok dulu, baru mengecek kondisi.'),
        js_mcq('Output: for (let i = 1; i <= 3; i++) console.log(i * 2);', ['2 4 6', '1 2 3', '2 4 6 8', '1 4 9'], 0, 'i = 1,2,3 dikali 2 menghasilkan 2, 4, 6.'),
        js_mcq('Variabel penghitung pada for biasanya dideklarasikan dengan…', ['var saja', 'let', 'const saja', 'Tidak perlu dideklarasikan'], 1, 'let umum dipakai agar scope terbatas pada loop.'),
        js_mcq('Infinite loop terjadi ketika…', ['Kondisi loop selalu true', 'Array memiliki satu elemen', 'Menggunakan break', 'Menggunakan console.log'], 0, 'Jika kondisi tidak pernah false, loop tidak pernah berhenti.'),
        js_mcq('break paling cocok digunakan untuk…', ['Melewati satu iterasi', 'Keluar dari loop saat syarat terpenuhi', 'Mendeklarasikan variabel', 'Mengubah tipe data'], 1, 'break menghentikan loop lebih awal, misalnya saat data ditemukan.'),
        js_mcq('continue pada loop for (let i=1;i<=5;i++) jika i===3 akan…', ['Menghentikan loop', 'Melewati sisa iterasi i=3 dan lanjut i=4', 'Mengulang dari i=1', 'Menghapus i=3 dari memori'], 1, 'continue loncat ke iterasi berikutnya tanpa menjalankan sisa blok untuk i=3.'),
        js_mcq('while (false) { console.log("A"); } — berapa kali "A" dicetak?', ['0', '1', '2', 'Tak terhingga'], 0, 'Kondisi false sejak awal, blok while tidak pernah dijalankan.'),
        js_mcq('Loop yang paling cocok iterasi indeks array 0..length-1?', ['do-while saja', 'for dengan indeks', 'while tanpa variabel', 'switch'], 1, 'for dengan indeks i < arr.length adalah pola umum untuk array.'),
        js_mcq('Pada do-while, titik koma setelah kondisi…', ['Opsional', 'Wajib ada', 'Dilarang', 'Hanya untuk const'], 1, 'Sintaks do-while memerlukan ; setelah while (kondisi);'),
        js_mcq('jumlahSampai(4) = 1+2+3+4 bernilai…', ['8', '9', '10', '12'], 2, '1+2+3+4 = 10.'),
    ],

    'weekly_assignment' => [
        'name' => 'Weekly Assignment - Minggu 5: Bilangan Genap dengan Loop',
        'description' => '<h3>Tugas Mingguan — Perulangan</h3>'
            . '<p>Buat fungsi <code>kumpulkanGenap(arr)</code> yang mengembalikan <strong>array baru</strong> berisi hanya bilangan genap dari parameter <code>arr</code>.</p>'
            . '<h4>Persyaratan</h4><ul>'
            . '<li>Gunakan minimal satu loop (<code>for</code>, <code>while</code>, atau <code>do-while</code>)</li>'
            . '<li>Bilangan genap: habis dibagi 2 (gunakan modulo <code>%</code>)</li>'
            . '<li>Urutan elemen sama seperti array asal</li>'
            . '<li>Return array kosong <code>[]</code> jika tidak ada genap</li>'
            . '</ul><p><strong>Petunjuk:</strong> Gunakan <code>JSON.stringify()</code> pada <code>console.log</code> untuk output array.</p>',
        'mode' => 'exam',
        'startercode' => "function kumpulkanGenap(arr) {\n    // Tulis kode Anda di sini\n    \n}\n\nconsole.log(JSON.stringify(kumpulkanGenap([1, 2, 3, 4, 5, 6])));\nconsole.log(JSON.stringify(kumpulkanGenap([7, 9, 11])));",
        'testcases' => '[{"input":"[1, 2, 3, 4, 5, 6]","expected":"[2,4,6]"},{"input":"[7, 9, 11]","expected":"[]"},{"input":"[2, 4, 6, 8]","expected":"[2,4,6,8]"},{"input":"[10, 15, 20, 25]","expected":"[10,20]"},{"input":"[]","expected":"[]"}]',
    ],
];

return $week5_data;
