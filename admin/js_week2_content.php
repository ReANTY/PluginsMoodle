<?php
/**
 * MINGGU 2: VARIABEL DAN TIPE DATA
 * Micro Lessons: ML2.1–ML2.4
 */

require_once(__DIR__ . '/js_course_week_helpers.php');

$quizextra = require(__DIR__ . '/js_quiz_supplements.php');

$week2_data = [
    'section_name' => 'Minggu 2: Variabel dan Tipe Data',
    'section_summary' => js_week_summary(
        '<p>Setelah menyelesaikan minggu ini, Anda akan mampu:</p>
        <ul>
            <li>Mendeklarasikan variabel dengan <code>let</code>, <code>const</code>, dan <code>var</code></li>
            <li>Memahami tipe data String, Number, dan Boolean</li>
            <li>Membedakan <code>null</code> dan <code>undefined</code></li>
            <li>Menulis program sederhana yang menyimpan dan menampilkan data</li>
        </ul>',
        4,
        60
    ),

    'micro_lessons' => [
        [
            'title' => 'ML2.1 - Variabel (let, const, var)',
            'intro' => 'Pelajari cara menyimpan data dalam variabel menggunakan let, const, dan var di JavaScript.',
            'content' => js_ml_wrap(
                'Variabel di JavaScript',
                js_ml_explain(
                    '<p><strong>Variabel</strong> adalah wadah untuk menyimpan nilai data yang dapat digunakan berulang kali dalam program. Di JavaScript modern, terdapat tiga kata kunci deklarasi variabel:</p>
                    <ul>
                        <li><code>let</code> — nilai dapat diubah (reassign)</li>
                        <li><code>const</code> — nilai tidak boleh diubah setelah dideklarasikan</li>
                        <li><code>var</code> — cara lama (masih berfungsi, tetapi disarankan memakai <code>let</code>/<code>const</code>)</li>
                    </ul>
                    <p>Penamaan variabel menggunakan <em>camelCase</em> (contoh: <code>namaLengkap</code>) dan tidak boleh diawali angka.</p>'
                )
                . js_ml_points([
                    'Gunakan <code>const</code> untuk nilai yang tidak berubah',
                    'Gunakan <code>let</code> untuk nilai yang akan diubah',
                    'Hindari <code>var</code> pada kode baru',
                    'Satu baris deklarasi: <code>let umur = 20;</code>',
                ])
                . js_ml_code(
                    'Contoh Deklarasi Variabel',
                    '// let — bisa diubah
let skor = 0;
skor = 10;
console.log(skor); // 10

// const — tidak bisa diubah
const PI = 3.14;
console.log(PI);

// var — cara lama
var kota = "Bandung";
console.log(kota);'
                )
                . js_ml_note('Mendeklarasikan variabel tanpa nilai awal menghasilkan <code>undefined</code>. Selalu beri nama yang jelas agar kode mudah dibaca.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML2.1 - Profil Variabel',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Lengkapi fungsi <code>tampilkanProfil</code> yang menerima <code>nama</code>, <code>umur</code>, dan <code>kota</code>, lalu <strong>mengembalikan</strong> string: <code>Nama: [nama], Umur: [umur], Kota: [kota]</code>.</p>',
                'startercode' => "function tampilkanProfil(nama, umur, kota) {\n    // Return string profil\n}\n\nconsole.log(tampilkanProfil('Budi', 20, 'Jakarta'));\nconsole.log(tampilkanProfil('Ani', 22, 'Surabaya'));",
                'testcases' => '[{"input":"Budi,20,Jakarta","expected":"Nama: Budi, Umur: 20, Kota: Jakarta"},{"input":"Ani,22,Surabaya","expected":"Nama: Ani, Umur: 22, Kota: Surabaya"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq(
                    'Kata kunci mana yang digunakan untuk variabel yang nilainya TIDAK boleh diubah?',
                    ['var', 'let', 'const', 'static'],
                    2,
                    'const digunakan untuk konstanta — nilainya tidak dapat ditugaskan ulang.'
                ),
                js_mcq(
                    'Manakah deklarasi variabel yang VALID?',
                    ['let 2nama = "Budi";', 'let nama-lengkap = "Budi";', 'let namaLengkap = "Budi";', 'let for = 10;'],
                    2,
                    'Nama variabel harus dimulai huruf/underscore/$ dan tidak boleh memakai kata reserved seperti for.'
                ),
                js_mcq(
                    'Apa output kode berikut?<br><code>let x = 5;<br>x = 8;<br>console.log(x);</code>',
                    ['5', '8', 'undefined', 'Error'],
                    1,
                    'Nilai x diubah dari 5 menjadi 8, sehingga output terakhir adalah 8.'
                ),
            ], $quizextra[2][0] ?? []),
        ],
        [
            'title' => 'ML2.2 - Tipe Data String',
            'intro' => 'Pelajari cara menyimpan dan memanipulasi teks (string) di JavaScript.',
            'content' => js_ml_wrap(
                'Tipe Data String',
                js_ml_explain(
                    '<p><strong>String</strong> adalah tipe data untuk teks. Nilai string ditulis di antara tanda kutip tunggal (<code>\'</code>) atau ganda (<code>"</code>). Template literal (backtick) memungkinkan interpolasi variabel dengan <code>${variabel}</code>.</p>
                    <p>Operator <code>+</code> pada string berfungsi sebagai penggabungan (concatenation). Properti <code>.length</code> mengembalikan jumlah karakter.</p>'
                )
                . js_ml_points([
                    'String = teks dalam tanda kutip',
                    'Gabung string: <code>"Halo" + " " + "Dunia"</code>',
                    'Template literal: <code>`Halo, ${nama}!`</code>',
                    '<code>"JavaScript".length</code> menghasilkan 10',
                ])
                . js_ml_code(
                    'Contoh String',
                    'let depan = "Budi";
let belakang = "Santoso";
let lengkap = depan + " " + belakang;
console.log(lengkap);

let usia = 20;
console.log(`Nama: ${depan}, Usia: ${usia}`);

console.log("Halo".toUpperCase());
console.log("  trim  ".trim());'
                )
                . js_ml_note('Angka dalam tanda kutip adalah string, bukan number. Contoh: <code>"10" + 5</code> menghasilkan <code>"105"</code>, bukan 15.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML2.2 - Gabung Nama',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Buat fungsi <code>gabungNama</code> yang menerima <code>depan</code> dan <code>belakang</code>, lalu mengembalikan string gabungan dengan spasi di tengahnya.</p>',
                'startercode' => "function gabungNama(depan, belakang) {\n    // Gabungkan depan dan belakang\n}\n\nconsole.log(gabungNama('Budi', 'Santoso'));\nconsole.log(gabungNama('Ani', 'Wijaya'));",
                'testcases' => '[{"input":"Budi,Santoso","expected":"Budi Santoso"},{"input":"Ani,Wijaya","expected":"Ani Wijaya"},{"input":"Java,Script","expected":"Java Script"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq(
                    'Apa hasil dari <code>"10" + 5</code>?',
                    ['15', '"105"', 'Error', 'undefined'],
                    1,
                    'Karena "10" adalah string, operator + melakukan concatenation: "10" + "5" = "105".'
                ),
                js_mcq(
                    'Syntax mana yang benar untuk template literal?',
                    ['"Halo ${nama}"', "'Halo ${nama}'", '`Halo ${nama}`', 'Halo ${nama}'],
                    2,
                    'Template literal menggunakan backtick (`) bukan kutip tunggal atau ganda.'
                ),
                js_mcq(
                    'Apa hasil <code>"JavaScript".length</code>?',
                    ['9', '10', '11', 'undefined'],
                    1,
                    'String "JavaScript" memiliki 10 karakter (J-a-v-a-S-c-r-i-p-t).'
                ),
            ], $quizextra[2][1] ?? []),
        ],
        [
            'title' => 'ML2.3 - Tipe Data Number',
            'intro' => 'Pelajari angka, operasi matematika dasar, dan nilai khusus NaN di JavaScript.',
            'content' => js_ml_wrap(
                'Tipe Data Number',
                js_ml_explain(
                    '<p><strong>Number</strong> menyimpan nilai numerik: bilangan bulat maupun desimal. JavaScript hanya memiliki satu tipe angka (tidak memisahkan int dan float).</p>
                    <p>Operator aritmatika: <code>+</code> <code>-</code> <code>*</code> <code>/</code> <code>%</code> (sisa bagi). Jika operasi tidak valid (misalnya teks dikurangi angka), hasilnya <code>NaN</code> (Not a Number).</p>'
                )
                . js_ml_points([
                    'Number tanpa tanda kutip: <code>let umur = 25;</code>',
                    'Desimal memakai titik: <code>3.14</code>',
                    '<code>%</code> = modulo (sisa pembagian)',
                    '<code>typeof 42</code> menghasilkan <code>"number"</code>',
                ])
                . js_ml_code(
                    'Contoh Number',
                    'let a = 10;
let b = 3;
console.log(a + b);  // 13
console.log(a - b);  // 7
console.log(a * b);  // 30
console.log(a / b);  // 3.333...
console.log(a % b);  // 1

let harga = 50000;
let diskon = 0.1;
console.log(harga * (1 - diskon)); // 45000

console.log(typeof 42);
console.log("abc" - 1); // NaN'
                )
                . js_ml_note('Bandingkan angka dengan <code>===</code>. Hindari membandingkan float desimal secara ketat karena presisi floating point.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML2.3 - Hitung Luas',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Buat fungsi <code>hitungLuas</code> yang menerima <code>panjang</code> dan <code>lebar</code>, lalu mengembalikan luas persegi panjang (panjang × lebar).</p>',
                'startercode' => "function hitungLuas(panjang, lebar) {\n    // Hitung dan return luas\n}\n\nconsole.log(hitungLuas(5, 4));\nconsole.log(hitungLuas(10, 3));\nconsole.log(hitungLuas(7, 7));",
                'testcases' => '[{"input":"5,4","expected":"20"},{"input":"10,3","expected":"30"},{"input":"7,7","expected":"49"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq(
                    'Apa hasil dari <code>17 % 5</code>?',
                    ['3', '2', '3.4', '0'],
                    1,
                    '17 dibagi 5 = 3 sisa 2. Operator % mengembalikan sisa bagi.'
                ),
                js_mcq(
                    'Apa hasil <code>typeof 3.14</code>?',
                    ['"integer"', '"float"', '"number"', '"decimal"'],
                    2,
                    'JavaScript hanya punya satu tipe angka: number.'
                ),
                js_mcq(
                    'Apa hasil <code>"hello" - 1</code>?',
                    ['-1', 'NaN', '0', '"hello-1"'],
                    1,
                    'Tidak bisa mengurangi angka dari string — hasilnya NaN.'
                ),
            ], $quizextra[2][2] ?? []),
        ],
        [
            'title' => 'ML2.4 - Boolean, null, dan undefined',
            'intro' => 'Pelajari tipe data logika (boolean) serta nilai khusus null dan undefined.',
            'content' => js_ml_wrap(
                'Boolean, null, dan undefined',
                js_ml_explain(
                    '<p><strong>Boolean</strong> hanya memiliki dua nilai: <code>true</code> atau <code>false</code>. Digunakan untuk kondisi dan perbandingan.</p>
                    <p><strong>undefined</strong> berarti variabel belum diberi nilai. <strong>null</strong> berarti nilai sengaja dikosongkan oleh programmer. Keduanya "falsy", tetapi berbeda makna.</p>
                    <p>Nilai falsy lain: <code>0</code>, <code>""</code>, <code>NaN</code>. Nilai truthy: hampir semua nilai selain falsy.</p>'
                )
                . js_ml_points([
                    'Boolean: true / false',
                    'undefined = belum diisi',
                    'null = sengaja dikosongkan',
                    'Perbandingan: <code>5 > 3</code> → true',
                ])
                . js_ml_code(
                    'Contoh Boolean dan Nilai Khusus',
                    'let aktif = true;
let lulus = false;
console.log(aktif);
console.log(10 > 5);

let data;
console.log(data); // undefined

let kosong = null;
console.log(kosong); // null

console.log(typeof true);      // "boolean"
console.log(typeof undefined); // "undefined"
console.log(null == undefined);  // true
console.log(null === undefined); // false'
                )
                . js_ml_note('Selalu gunakan <code>===</code> dan <code>!==</code> untuk perbandingan ketat. Hindari <code>==</code> karena bisa membingungkan (misalnya null == undefined).')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML2.4 - Cek Usia Dewasa',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Buat fungsi <code>isDewasa</code> yang menerima <code>usia</code> dan mengembalikan <code>true</code> jika usia &gt;= 17, otherwise <code>false</code>.</p>',
                'startercode' => "function isDewasa(usia) {\n    // Return true atau false\n}\n\nconsole.log(isDewasa(20));\nconsole.log(isDewasa(15));\nconsole.log(isDewasa(17));",
                'testcases' => '[{"input":"20","expected":"true"},{"input":"15","expected":"false"},{"input":"17","expected":"true"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq(
                    'Nilai mana yang bertipe boolean?',
                    ['"true"', '1', 'true', 'null'],
                    2,
                    'true tanpa tanda kutip adalah boolean. "true" adalah string.'
                ),
                js_mcq(
                    'Apa perbedaan utama null dan undefined?',
                    ['Sama persis', 'null diset sengaja, undefined belum diisi', 'undefined hanya untuk angka', 'null hanya untuk string'],
                    1,
                    'undefined = belum didefinisikan; null = sengaja dikosongkan.'
                ),
                js_mcq(
                    'Apa hasil <code>Boolean("")</code>?',
                    ['true', 'false', 'undefined', 'null'],
                    1,
                    'String kosong adalah nilai falsy, sehingga Boolean("") menghasilkan false.'
                ),
            ], $quizextra[2][3] ?? []),
        ],
    ],

    'weekly_quiz' => [
        js_mcq('Kata kunci terbaik untuk menyimpan nilai konstanta PI = 3.14?', ['var PI', 'let PI', 'const PI', 'PI = 3.14'], 2, 'const untuk nilai yang tidak berubah.'),
        js_mcq('Apa tipe data dari <code>typeof "42"</code>?', ['number', 'string', 'boolean', 'undefined'], 1, 'Meskipun berisi angka, "42" dalam kutip adalah string.'),
        js_mcq('Apa hasil <code>10 + "5"</code>?', ['15', '"105"', 'Error', 'NaN'], 1, 'String "5" menyebabkan concatenation.'),
        js_mcq('Variabel mana yang TIDAK valid?', ['let _total', 'const $harga', 'let nilaiAkhir', 'let 2x'], 3, 'Nama variabel tidak boleh diawali angka.'),
        js_mcq('Apa output <code>console.log(8 % 3)</code>?', ['2', '2.666', '3', '0'], 0, '8 % 3 = sisa 2.'),
        js_mcq('Nilai mana yang falsy?', ['"0"', '[]', '0', 'true'], 2, 'Angka 0 adalah falsy di JavaScript.'),
        js_mcq('Apa hasil <code>null === undefined</code>?', ['true', 'false', 'null', 'undefined'], 1, '=== membandingkan tipe dan nilai — keduanya berbeda.'),
        js_mcq('Operator mana untuk sisa bagi?', ['/', '%', '*', '//'], 1, '% adalah modulo operator.'),
        js_mcq('Apa hasil <code>`2 + 2 = ${2+2}`</code>?', ['"2 + 2 = 4"', '"2 + 2 = ${2+2}"', '4', 'Error'], 0, 'Template literal mengevaluasi ekspresi di dalam ${}.'),
        js_mcq('Manakah pernyataan BENAR tentang let dan const?', ['const bisa di-reassign', 'let tidak bisa di-reassign', 'let bisa di-reassign, const tidak', 'keduanya sama dengan var'], 2, 'let mutable, const immutable setelah deklarasi.'),
    ],

    'weekly_assignment' => [
        'name' => 'Weekly Assignment - Minggu 2: Kartu Data Siswa',
        'description' => '<h3>Tugas Mingguan</h3><p>Buat fungsi <code>buatKartuSiswa</code> yang menerima parameter <code>nama</code> (string), <code>umur</code> (number), <code>aktif</code> (boolean), dan <code>nilai</code> (number).</p><h3>Return</h3><p>String satu baris dengan format persis:</p><pre>Kartu: [nama] | Umur: [umur] | Aktif: [true/false] | Nilai: [nilai]</pre><p>Gunakan template literal atau concatenation. Tampilkan hasil dengan <code>console.log</code> di bawah fungsi (sudah disediakan).</p>',
        'mode' => 'exam',
        'startercode' => "function buatKartuSiswa(nama, umur, aktif, nilai) {\n    // Return string kartu sesuai format\n}\n\nconsole.log(buatKartuSiswa('Budi', 18, true, 85));\nconsole.log(buatKartuSiswa('Siti', 16, false, 72));",
        'testcases' => '[{"input":"Budi,18,true,85","expected":"Kartu: Budi | Umur: 18 | Aktif: true | Nilai: 85"},{"input":"Siti,16,false,72","expected":"Kartu: Siti | Umur: 16 | Aktif: false | Nilai: 72"}]',
    ],
];

return $week2_data;
