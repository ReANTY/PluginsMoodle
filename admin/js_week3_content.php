<?php
/**
 * MINGGU 3: OPERATOR DAN INPUT
 * Micro Lessons: ML3.1–ML3.4
 */

require_once(__DIR__ . '/js_course_week_helpers.php');

$quizextra = require(__DIR__ . '/js_quiz_supplements.php');

$week3_data = [
    'section_name' => 'Minggu 3: Operator dan Input',
    'section_summary' => js_week_summary(
        '<p>Setelah menyelesaikan minggu ini, Anda akan mampu:</p>
        <ul>
            <li>Menggunakan operator aritmatika dan assignment</li>
            <li>Memahami operator perbandingan dan logika</li>
            <li>Membaca input pengguna dengan <code>prompt()</code> (konsep)</li>
            <li>Melakukan konversi tipe data (string ↔ number)</li>
        </ul>',
        4,
        60
    ),

    'micro_lessons' => [
        [
            'title' => 'ML3.1 - Operator Aritmatika',
            'intro' => 'Pelajari operator matematika dasar dan urutan operasi di JavaScript.',
            'content' => js_ml_wrap(
                'Operator Aritmatika',
                js_ml_explain(
                    '<p>Operator aritmatika digunakan untuk perhitungan numerik:</p>
                    <table><tr><td><code>+</code></td><td>penjumlahan</td></tr>
                    <tr><td><code>-</code></td><td>pengurangan</td></tr>
                    <tr><td><code>*</code></td><td>perkalian</td></tr>
                    <tr><td><code>/</code></td><td>pembagian</td></tr>
                    <tr><td><code>%</code></td><td>sisa bagi (modulo)</td></tr>
                    <tr><td><code>**</code></td><td>pangkat</td></tr></table>
                    <p>Urutan operasi mengikuti aturan matematika: kurung → pangkat → kali/bagi → tambah/kurang.</p>'
                )
                . js_ml_points([
                    'Gunakan kurung untuk memperjelas urutan: <code>(a + b) * 2</code>',
                    '<code>+</code> dengan string = concatenation',
                    '<code>%</code> berguna untuk cek genap/ganjil',
                    '<code>2 ** 3</code> sama dengan 2³ = 8',
                ])
                . js_ml_code(
                    'Contoh Operator Aritmatika',
                    'let harga = 100000;
let qty = 3;
let total = harga * qty;
console.log(total);

console.log(10 + 5 * 2);   // 20, bukan 30
console.log((10 + 5) * 2); // 30

console.log(17 % 5);  // 2
console.log(2 ** 4);  // 16

let genap = 8 % 2 === 0;
console.log(genap); // true'
                )
                . js_ml_note('Jika salah satu operand string dan operator +, JavaScript akan menggabungkan string, bukan menjumlahkan angka.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML3.1 - Kalkulator Sederhana',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Buat fungsi <code>kalkulator</code> dengan parameter <code>a</code>, <code>b</code>, dan <code>operasi</code> (<code>"+"</code>, <code>"-"</code>, <code>"*"</code>, <code>"/"</code>). Kembalikan hasil perhitungan. Untuk pembagian dengan nol, return <code>"Error"</code>.</p>',
                'startercode' => "function kalkulator(a, b, operasi) {\n    // Gunakan if atau switch untuk operasi\n}\n\nconsole.log(kalkulator(10, 5, '+'));\nconsole.log(kalkulator(10, 5, '-'));\nconsole.log(kalkulator(10, 5, '*'));\nconsole.log(kalkulator(10, 0, '/'));",
                'testcases' => '[{"input":"10,5,+","expected":"15"},{"input":"10,5,-","expected":"5"},{"input":"10,5,*","expected":"50"},{"input":"10,0,/","expected":"Error"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Apa hasil <code>2 + 3 * 4</code>?', ['20', '14', '24', '9'], 1, 'Perkalian dulu: 3*4=12, lalu 2+12=14.'),
                js_mcq('Operator mana untuk pangkat?', ['^', '**', 'pow', '%%'], 1, '** adalah exponentiation operator di ES6+.'),
                js_mcq('Apa hasil <code>15 % 4</code>?', ['3', '3.75', '1', '0'], 0, '15 ÷ 4 = 3 sisa 3.'),
            ], $quizextra[3][0] ?? []),
        ],
        [
            'title' => 'ML3.2 - Operator Assignment',
            'intro' => 'Pelajari cara menugaskan dan memperbarui nilai variabel dengan operator assignment.',
            'content' => js_ml_wrap(
                'Operator Assignment',
                js_ml_explain(
                    '<p>Operator <code>=</code> menugaskan nilai ke variabel. JavaScript menyediakan assignment gabungan untuk memperpendek kode:</p>
                    <ul>
                        <li><code>+=</code> — tambah lalu assign</li>
                        <li><code>-=</code> — kurang lalu assign</li>
                        <li><code>*=</code>, <code>/=</code>, <code>%=</code> — operasi lalu assign</li>
                    </ul>
                    <p>Operator increment/decrement: <code>++</code> dan <code>--</code> menambah atau mengurangi 1.</p>'
                )
                . js_ml_points([
                    '<code>x += 5</code> sama dengan <code>x = x + 5</code>',
                    '<code>++x</code> (prefix) increment sebelum dipakai',
                    '<code>x++</code> (postfix) increment setelah dipakai',
                    'Jangan gunakan <code>++</code> pada ekspresi kompleks',
                ])
                . js_ml_code(
                    'Contoh Assignment',
                    'let skor = 10;
skor += 5;
console.log(skor); // 15

let poin = 100;
poin -= 20;
console.log(poin); // 80

let harga = 50;
harga *= 2;
console.log(harga); // 100

let i = 5;
console.log(++i); // 6
console.log(i++); // 6 (lalu i jadi 7)
console.log(i);   // 7'
                )
                . js_ml_note('const tidak bisa menggunakan += atau operator assignment lain karena nilainya tidak boleh diubah.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML3.2 - Tambah Skor Game',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Buat fungsi <code>tambahSkor</code> yang menerima <code>skorAwal</code> dan <code>poinTambah</code>, menggunakan operator <code>+=</code>, lalu mengembalikan skor akhir.</p>',
                'startercode' => "function tambahSkor(skorAwal, poinTambah) {\n    // Gunakan += lalu return skor\n}\n\nconsole.log(tambahSkor(10, 5));\nconsole.log(tambahSkor(0, 100));\nconsole.log(tambahSkor(50, 25));",
                'testcases' => '[{"input":"10,5","expected":"15"},{"input":"0,100","expected":"100"},{"input":"50,25","expected":"75"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Apa hasil setelah kode ini?<br><code>let x = 10; x += 3; console.log(x);</code>', ['10', '3', '13', '103'], 2, 'x += 3 menambah 3 ke x: 10+3=13.'),
                js_mcq('Ekspresi mana yang SAMA dengan <code>x = x * 2</code>?', ['x *= 2', 'x =* 2', 'x ++ 2', 'x = 2x'], 0, 'x *= 2 adalah shorthand multiplication assignment.'),
                js_mcq('Variabel const bisa memakai += ?', ['Ya, selalu', 'Tidak, const tidak bisa diubah', 'Hanya untuk angka', 'Hanya di dalam function'], 1, 'const tidak boleh di-reassign termasuk dengan +=.'),
            ], $quizextra[3][1] ?? []),
        ],
        [
            'title' => 'ML3.3 - Input dengan prompt()',
            'intro' => 'Pelajari cara membaca input dari pengguna menggunakan prompt() di browser.',
            'content' => js_ml_wrap(
                'Input dengan prompt()',
                js_ml_explain(
                    '<p>Fungsi <code>prompt()</code> menampilkan dialog input di browser dan mengembalikan string yang diketik pengguna. Jika pengguna menekan Batal, hasilnya <code>null</code>.</p>
                    <p>Karena <code>prompt()</code> selalu mengembalikan <strong>string</strong>, angka harus dikonversi sebelum dihitung. Di latihan AICode, kita mensimulasikan input dengan parameter fungsi.</p>'
                )
                . js_ml_points([
                    '<code>let nama = prompt("Nama Anda?");</code>',
                    'Hasil prompt selalu string',
                    'Batal → <code>null</code>',
                    'Konversi: <code>Number(input)</code> atau <code>parseInt(input)</code>',
                ])
                . js_ml_code(
                    'Contoh prompt() di Browser',
                    '// Di browser console atau HTML:
let nama = prompt("Siapa nama Anda?");
console.log("Halo, " + nama + "!");

let umurStr = prompt("Berapa umur Anda?");
let umur = Number(umurStr);
console.log("Tahun depan umur Anda:", umur + 1);

// Simulasi di latihan (tanpa prompt):
function sapaPengguna(nama) {
    return "Halo, " + nama + "!";
}
console.log(sapaPengguna("Budi"));'
                )
                . js_ml_note('prompt() memblokir eksekusi kode sampai user merespons. Untuk aplikasi modern, form HTML lebih umum dipakai.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML3.3 - Sapa Pengguna',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Buat fungsi <code>sapaPengguna</code> yang mensimulasikan hasil <code>prompt()</code>: terima <code>nama</code> dan <code>umur</code> (string), konversi umur ke number, return string: <code>Halo, [nama]! Tahun depan umur Anda [umur+1].</code></p>',
                'startercode' => "function sapaPengguna(nama, umurStr) {\n    // Konversi umurStr ke number, return pesan\n}\n\nconsole.log(sapaPengguna('Budi', '20'));\nconsole.log(sapaPengguna('Ani', '17'));",
                'testcases' => '[{"input":"Budi,20","expected":"Halo, Budi! Tahun depan umur Anda 21."},{"input":"Ani,17","expected":"Halo, Ani! Tahun depan umur Anda 18."}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Apa tipe data hasil <code>prompt("Umur?")</code>?', ['number', 'string', 'boolean', 'object'], 1, 'prompt() selalu mengembalikan string (atau null jika batal).'),
                js_mcq('User menekan Batal pada prompt. Nilai variabel?', ['""', 'undefined', 'null', 'false'], 2, 'Batal mengembalikan null.'),
                js_mcq('Cara benar mengubah input string "25" menjadi angka?', ['"25" + 0', 'Number("25")', 'String(25)', '"25".toString()'], 1, 'Number() mengkonversi string ke number.'),
            ], $quizextra[3][2] ?? []),
        ],
        [
            'title' => 'ML3.4 - Konversi Tipe Data',
            'intro' => 'Pelajari cara mengubah tipe data antar string, number, dan boolean di JavaScript.',
            'content' => js_ml_wrap(
                'Konversi Tipe Data',
                js_ml_explain(
                    '<p>Konversi tipe (type conversion) membuat data sesuai kebutuhan operasi:</p>
                    <ul>
                        <li><strong>Eksplisit:</strong> Anda memanggil fungsi konversi (<code>Number()</code>, <code>String()</code>, <code>Boolean()</code>)</li>
                        <li><strong>Implisit:</strong> JavaScript otomatis mengubah tipe (coercion), misalnya <code>"5" + 1</code> → <code>"51"</code></li>
                    </ul>
                    <p><code>parseInt()</code> untuk bilangan bulat, <code>parseFloat()</code> untuk desimal. Hati-hati: <code>Number("abc")</code> → <code>NaN</code>.</p>'
                )
                . js_ml_points([
                    'Number("42") → 42',
                    'String(42) → "42"',
                    'Boolean(0) → false, Boolean(1) → true',
                    'isNaN(x) mengecek apakah x bukan angka',
                ])
                . js_ml_code(
                    'Contoh Konversi',
                    'console.log(Number("42"));     // 42
console.log(String(42));     // "42"
console.log(Boolean(1));     // true
console.log(Boolean(0));     // false

console.log(parseInt("10px"));   // 10
console.log(parseFloat("3.14")); // 3.14

console.log(Number(""));       // 0
console.log(Number("hello"));  // NaN
console.log(isNaN(Number("hello"))); // true

let input = "15";
let hasil = Number(input) + 5;
console.log(hasil); // 20'
                )
                . js_ml_note('Selalu validasi input pengguna sebelum konversi. Gunakan isNaN() setelah Number() untuk menghindari perhitungan salah.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML3.4 - Konversi Input',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Buat fungsi <code>hitungTotal</code> yang menerima <code>hargaStr</code> dan <code>qtyStr</code> (string), konversi ke number, return hasil perkalian. Jika hasil NaN, return <code>"Input tidak valid"</code>.</p>',
                'startercode' => "function hitungTotal(hargaStr, qtyStr) {\n    // Konversi dan hitung, handle NaN\n}\n\nconsole.log(hitungTotal('5000', '3'));\nconsole.log(hitungTotal('10000', '2'));\nconsole.log(hitungTotal('abc', '2'));",
                'testcases' => '[{"input":"5000,3","expected":"15000"},{"input":"10000,2","expected":"20000"},{"input":"abc,2","expected":"Input tidak valid"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Apa hasil <code>Number("3.14")</code>?', ['"3.14"', '3.14', 'NaN', 'undefined'], 1, 'Number() mengkonversi string desimal ke number.'),
                js_mcq('Apa hasil <code>parseInt("10px")</code>?', ['10', 'NaN', '"10px"', '0'], 0, 'parseInt membaca angka dari awal string hingga karakter non-angka.'),
                js_mcq('Apa hasil <code>String(true)</code>?', ['true', '"true"', '1', 'boolean'], 1, 'String(true) menghasilkan string "true".'),
            ], $quizextra[3][3] ?? []),
        ],
    ],

    'weekly_quiz' => [
        js_mcq('Apa hasil <code>100 / 10 * 2</code>?', ['5', '20', '0.2', '200'], 1, 'Kiri ke kanan: 10 * 2 = 20.'),
        js_mcq('Ekspresi mana hasilnya 8?', ['2 + 2 * 2', '(2 + 2) * 2', '2 ** 2 + 2', '16 / 2 / 2'], 1, '(2+2)*2 = 8.'),
        js_mcq('Setelah <code>let n = 5; n *= 2;</code> nilai n?', ['5', '7', '10', '52'], 2, 'n *= 2 → n = 10.'),
        js_mcq('prompt() mengembalikan tipe apa jika user mengetik "100"?', ['number', 'string', 'boolean', 'integer'], 1, 'Selalu string meskipun terlihat seperti angka.'),
        js_mcq('Apa hasil <code>Number("")</code>?', ['NaN', '0', '""', 'undefined'], 1, 'String kosong dikonversi ke 0.'),
        js_mcq('Operator untuk sisa bagi?', ['//', '%', 'mod', 'rem'], 1, '% adalah modulo.'),
        js_mcq('Apa hasil <code>Boolean("false")</code>?', ['false', 'true', 'undefined', 'null'], 1, 'String tidak kosong adalah truthy, termasuk "false".'),
        js_mcq('parseFloat("3.14em") menghasilkan?', ['3.14', 'NaN', '3', '"3.14"'], 0, 'parseFloat membaca angka desimal dari awal.'),
        js_mcq('Apa hasil <code>"5" + 2</code>?', ['7', '"52"', 'NaN', 'Error'], 1, 'String + number = concatenation.'),
        js_mcq('Fungsi untuk cek NaN?', ['isNull()', 'isNaN()', 'isNumber()', 'checkNaN()'], 1, 'isNaN(value) mengecek Not a Number.'),
    ],

    'weekly_assignment' => [
        'name' => 'Weekly Assignment - Minggu 3: Kalkulator Belanja',
        'description' => '<h3>Tugas Mingguan</h3><p>Buat fungsi <code>hitungBelanja</code> yang menerima <code>namaBarang</code> (string), <code>hargaStr</code> (string), <code>jumlahStr</code> (string), dan <code>diskonPersen</code> (number, 0–100).</p><h3>Langkah</h3><ol><li>Konversi hargaStr dan jumlahStr ke number</li><li>Hitung subtotal = harga × jumlah</li><li>Hitung total setelah diskon: subtotal - (subtotal × diskonPersen / 100)</li><li>Return string: <code>[namaBarang]: Rp[total] (diskon [diskonPersen]%)</code> — total bulatkan tanpa desimal</li></ol>',
        'mode' => 'exam',
        'startercode' => "function hitungBelanja(namaBarang, hargaStr, jumlahStr, diskonPersen) {\n    // Konversi, hitung, return string\n}\n\nconsole.log(hitungBelanja('Buku', '50000', '2', 10));\nconsole.log(hitungBelanja('Pulpen', '5000', '3', 0));",
        'testcases' => '[{"input":"Buku,50000,2,10","expected":"Buku: Rp90000 (diskon 10%)"},{"input":"Pulpen,5000,3,0","expected":"Pulpen: Rp15000 (diskon 0%)"}]',
    ],
];

return $week3_data;
