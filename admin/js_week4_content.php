<?php
/**
 * MINGGU 4: PERCABANGAN
 * Micro Lessons: ML4.1–ML4.4
 */

require_once(__DIR__ . '/js_course_week_helpers.php');

$quizextra = require(__DIR__ . '/js_quiz_supplements.php');

$week4_data = [
    'section_name' => 'Minggu 4: Percabangan',
    'section_summary' => js_week_summary(
        '<p>Setelah menyelesaikan minggu ini, Anda akan mampu:</p>
        <ul>
            <li>Membuat keputusan dalam program dengan <code>if</code></li>
            <li>Menggunakan <code>else</code> dan <code>else if</code> untuk banyak kondisi</li>
            <li>Memilih salah satu dari banyak opsi dengan <code>switch</code></li>
            <li>Menulis kondisi singkat dengan operator ternary</li>
        </ul>',
        4,
        60
    ),

    'micro_lessons' => [
        [
            'title' => 'ML4.1 - Percabangan if',
            'intro' => 'Pelajari cara menjalankan kode hanya jika kondisi tertentu terpenuhi.',
            'content' => js_ml_wrap(
                'Percabangan if',
                js_ml_explain(
                    '<p>Struktur <code>if</code> menjalankan blok kode hanya ketika kondisi bernilai <code>true</code>. Kondisi ditulis dalam tanda kurung dan biasanya memakai operator perbandingan: <code>===</code> <code>!==</code> <code>&gt;</code> <code>&lt;</code> <code>&gt;=</code> <code>&lt;=</code>.</p>
                    <p>Blok kode bisa satu baris atau beberapa baris dalam kurung kurawal <code>{ }</code>.</p>'
                )
                . js_ml_points([
                    'Format: <code>if (kondisi) { ... }</code>',
                    'Gunakan <code>===</code> untuk perbandingan ketat',
                    'Kondisi menghasilkan boolean',
                    'Tanpa {} hanya satu statement setelah if',
                ])
                . js_ml_code(
                    'Contoh if',
                    'let nilai = 85;

if (nilai >= 75) {
    console.log("Lulus");
}

let umur = 16;
if (umur >= 17) {
    console.log("Boleh membuat SIM");
}

let stok = 0;
if (stok === 0) {
    console.log("Stok habis");
}

// Operator logika dalam kondisi
let cuaca = "cerah";
let libur = true;
if (cuaca === "cerah" && libur) {
    console.log("Saatnya piknik!");
}'
                )
                . js_ml_note('Satu tanda sama dengan (=) adalah assignment, bukan perbandingan. Untuk membandingkan selalu pakai === atau !==.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML4.1 - Cek Genap',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Buat fungsi <code>cekGenap</code> yang menerima <code>n</code> dan mengembalikan <code>"Genap"</code> jika n habis dibagi 2, atau <code>"Ganjil"</code> jika tidak.</p>',
                'startercode' => "function cekGenap(n) {\n    // Gunakan if dan operator %\n}\n\nconsole.log(cekGenap(4));\nconsole.log(cekGenap(7));\nconsole.log(cekGenap(0));",
                'testcases' => '[{"input":"4","expected":"Genap"},{"input":"7","expected":"Ganjil"},{"input":"0","expected":"Genap"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Syntax if yang BENAR?', ['if nilai > 70 {}', 'if (nilai > 70) {}', 'if [nilai > 70]', 'if nilai > 70 then'],
                    1, 'Kondisi harus dalam tanda kurung ().'),
                js_mcq('Apa hasil jika <code>if (5 > 10) { console.log("Ya"); }</code>?', ['Ya', 'Tidak ada output', 'Error', 'undefined'], 1, '5 > 10 adalah false, blok tidak dijalankan.'),
                js_mcq('Operator untuk "dan" (kedua kondisi harus true)?', ['||', '&&', '!', '??'], 1, '&& adalah logical AND.'),
            ], $quizextra[4][0] ?? []),
        ],
        [
            'title' => 'ML4.2 - else dan else if',
            'intro' => 'Pelajari percabangan dua arah dan banyak kondisi dengan else dan else if.',
            'content' => js_ml_wrap(
                'else dan else if',
                js_ml_explain(
                    '<p><code>else</code> menjalankan blok alternatif jika kondisi <code>if</code> false. <code>else if</code> memeriksa kondisi tambahan secara berurutan hingga salah satu terpenuhi.</p>
                    <p>Cocok untuk klasifikasi nilai, menu pilihan, atau status dengan beberapa level.</p>'
                )
                . js_ml_points([
                    'if → else: dua kemungkinan',
                    'if → else if → else: banyak kemungkinan',
                    'Hanya satu blok yang dieksekusi',
                    'Urutan kondisi penting (dari spesifik ke umum)',
                ])
                . js_ml_code(
                    'Contoh else dan else if',
                    'let nilai = 82;
if (nilai >= 90) {
    console.log("A");
} else if (nilai >= 80) {
    console.log("B");
} else if (nilai >= 70) {
    console.log("C");
} else {
    console.log("Tidak Lulus");
}

let suhu = 35;
if (suhu > 30) {
    console.log("Panas");
} else {
    console.log("Normal");
}

let login = false;
if (login) {
    console.log("Dashboard");
} else {
    console.log("Silakan login");
}'
                )
                . js_ml_note('Letakkan kondisi paling ketat/rendah di atas. Misalnya cek >= 90 sebelum >= 80 agar tidak tertimpa.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML4.2 - Grade Nilai',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Buat fungsi <code>gradeNilai</code> yang menerima <code>nilai</code> (0–100) dan mengembalikan huruf: A (&gt;=90), B (&gt;=80), C (&gt;=70), D (&gt;=60), E (&lt;60).</p>',
                'startercode' => "function gradeNilai(nilai) {\n    // Gunakan if / else if\n}\n\nconsole.log(gradeNilai(95));\nconsole.log(gradeNilai(82));\nconsole.log(gradeNilai(55));",
                'testcases' => '[{"input":"95","expected":"A"},{"input":"82","expected":"B"},{"input":"55","expected":"E"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Berapa blok yang dieksekusi dalam rantai if-else if-else?', ['Semua yang true', 'Hanya satu', 'Dua', 'Tidak ada'], 1, 'Hanya blok pertama dengan kondisi true yang jalan.'),
                js_mcq('nilai = 85. grade dengan A>=90, B>=80, C>=70?', ['A', 'B', 'C', 'D'], 1, '85 >= 80 dan < 90 → B.'),
                js_mcq('else if diletakkan setelah?', ['else', 'if atau else if sebelumnya', 'return', 'function'], 1, 'else if mengikuti if atau else if lain.'),
            ], $quizextra[4][1] ?? []),
        ],
        [
            'title' => 'ML4.3 - Percabangan switch',
            'intro' => 'Pelajari struktur switch untuk memilih dari banyak nilai tetap.',
            'content' => js_ml_wrap(
                'Percabangan switch',
                js_ml_explain(
                    '<p><code>switch</code> membandingkan satu ekspresi dengan banyak <code>case</code>. Jika cocok, blok kode dijalankan hingga <code>break</code>. <code>default</code> seperti <code>else</code> untuk nilai yang tidak cocok.</p>
                    <p>Efisien untuk nilai diskrit: hari, kode menu, kode error. Untuk rentang angka, <code>if/else if</code> sering lebih jelas.</p>'
                )
                . js_ml_points([
                    'switch (ekspresi) { case nilai: ... break; }',
                    'Jangan lupa <code>break</code> atau terjadi fall-through',
                    '<code>default</code> opsional untuk kasus lain',
                    'case memakai === (strict equality)',
                ])
                . js_ml_code(
                    'Contoh switch',
                    'let hari = 3;
switch (hari) {
    case 1:
        console.log("Senin");
        break;
    case 2:
        console.log("Selasa");
        break;
    case 3:
        console.log("Rabu");
        break;
    default:
        console.log("Hari tidak valid");
}

let warna = "merah";
switch (warna) {
    case "merah":
        console.log("Stop");
        break;
    case "kuning":
        console.log("Hati-hati");
        break;
    case "hijau":
        console.log("Jalan");
        break;
    default:
        console.log("Lampu mati");
}'
                )
                . js_ml_note('Tanpa break, eksekusi "jatuh" ke case berikutnya (fall-through). Kadang sengaja dipakai, tetapi untuk pemula selalu tulis break.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML4.3 - Nama Hari',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Buat fungsi <code>namaHari</code> yang menerima angka 1–7 (1=Senin … 7=Minggu) dan mengembalikan nama hari. Gunakan <code>switch</code>. Nilai di luar 1–7 return <code>"Tidak valid"</code>.</p>',
                'startercode' => "function namaHari(nomor) {\n    // Gunakan switch\n}\n\nconsole.log(namaHari(1));\nconsole.log(namaHari(5));\nconsole.log(namaHari(9));",
                'testcases' => '[{"input":"1","expected":"Senin"},{"input":"5","expected":"Jumat"},{"input":"9","expected":"Tidak valid"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Apa fungsi break dalam switch?', ['Menghentikan program', 'Keluar dari switch setelah case', 'Lompat ke default', 'Mengulang switch'], 1, 'break menghentikan eksekusi switch.'),
                js_mcq('case mana yang cocok untuk switch (warna = "biru")?', ['case biru:', 'case "biru":', 'case = "biru"', 'case blue'], 1, 'case memakai nilai literal yang sama tipe dengan ekspresi.'),
                js_mcq('default dalam switch sama seperti?', ['if', 'else', 'for', 'return'], 1, 'default = jalur jika tidak ada case cocok.'),
            ], $quizextra[4][2] ?? []),
        ],
        [
            'title' => 'ML4.4 - Operator Ternary',
            'intro' => 'Pelajari penulisan kondisi singkat dengan operator ternary (? :).',
            'content' => js_ml_wrap(
                'Operator Ternary',
                js_ml_explain(
                    '<p>Operator ternary adalah singkatan if-else satu baris:</p>
                    <p><code>kondisi ? nilaiJikaTrue : nilaiJikaFalse</code></p>
                    <p>Berguna untuk assign nilai berdasarkan kondisi tanpa menulis blok if panjang.</p>'
                )
                . js_ml_points([
                    'Format: <code>kondisi ? a : b</code>',
                    'Mengembalikan ekspresi, bukan statement',
                    'Bisa nested tetapi hindari terlalu dalam',
                    'Sering dipakai di template dan assign',
                ])
                . js_ml_code(
                    'Contoh Ternary',
                    'let umur = 20;
let status = umur >= 17 ? "Dewasa" : "Anak";
console.log(status);

let nilai = 75;
let hasil = nilai >= 70 ? "Lulus" : "Tidak Lulus";
console.log(hasil);

let member = true;
let harga = 100000;
let bayar = member ? harga * 0.9 : harga;
console.log(bayar);

// Nested (hati-hati)
let n = 0;
let label = n > 0 ? "positif" : n < 0 ? "negatif" : "nol";
console.log(label);'
                )
                . js_ml_note('Jangan gunakan ternary untuk logika kompleks. Jika lebih dari satu baris kode per cabang, pakai if/else biasa.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML4.4 - Diskon Member',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Buat fungsi <code>hitungBayar</code> dengan parameter <code>isMember</code> (boolean) dan <code>harga</code> (number). Member dapat diskon 10% (bayar 90% harga). Gunakan operator ternary. Return total bayar (angka).</p>',
                'startercode' => "function hitungBayar(isMember, harga) {\n    // Gunakan ternary\n}\n\nconsole.log(hitungBayar(true, 100000));\nconsole.log(hitungBayar(false, 100000));\nconsole.log(hitungBayar(true, 50000));",
                'testcases' => '[{"input":"true,100000","expected":"90000"},{"input":"false,100000","expected":"100000"},{"input":"true,50000","expected":"45000"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Apa hasil <code>let x = 5 > 3 ? "A" : "B";</code>?', ['A', 'B', 'true', '5'], 0, '5 > 3 true, maka "A".'),
                js_mcq('Bagian mana yang dieksekusi jika kondisi false?', ['Sebelum ?', 'Antara ? dan :', 'Setelah :', 'Semua'], 2, 'Nilai setelah : dipilih jika false.'),
                js_mcq('Ternary cocok untuk?', ['Banyak baris kode per cabang', 'Assign nilai singkat berdasarkan kondisi', 'Loop', 'Deklarasi function'], 1, 'Ternary untuk ekspresi singkat.'),
            ], $quizextra[4][3] ?? []),
        ],
    ],

    'weekly_quiz' => [
        js_mcq('Kondisi dalam if harus bertipe?', ['string', 'number', 'boolean', 'object'], 2, 'Kondisi dievaluasi sebagai true/false.'),
        js_mcq('Apa output?<br><code>let x=10; if(x>5){console.log("A")}else{console.log("B")}</code>', ['A', 'B', 'AB', 'tidak ada'], 0, '10 > 5 true → A.'),
        js_mcq('else if digunakan ketika?', ['Hanya dua pilihan', 'Lebih dari dua kondisi', 'Tidak ada kondisi', 'Setelah switch'], 1, 'else if untuk banyak cabang.'),
        js_mcq('Tanpa break di case, apa yang terjadi?', ['Error', 'Fall-through ke case berikut', 'Program berhenti', 'default selalu jalan'], 1, 'Eksekusi lanjut ke case berikut (fall-through).'),
        js_mcq('Ternary: <code>false ? 1 : 2</code> hasilnya?', ['1', '2', 'false', '0'], 1, 'Kondisi false → nilai setelah : yaitu 2.'),
        js_mcq('Operator "atau" (salah satu true)?', ['&&', '||', '!', '==='], 1, '|| adalah OR logika.'),
        js_mcq('nilai=88. if>=90 A, else if>=80 B, else C. Hasil?', ['A', 'B', 'C', 'Error'], 1, '88 >= 80 dan < 90 → B.'),
        js_mcq('switch cocok untuk?', ['Rentang 1–100', 'Banyak nilai tetap diskrit', 'Loop', 'Try-catch'], 1, 'switch untuk nilai diskrit yang jelas.'),
        js_mcq('Perbandingan ketat 5 === "5"?', ['true', 'false', 'NaN', 'undefined'], 1, 'Tipe berbeda (number vs string) → false.'),
        js_mcq('NOT logika ditulis?', ['&&', '||', '!', '??'], 2, '! membalik boolean.'),
    ],

    'weekly_assignment' => [
        'name' => 'Weekly Assignment - Minggu 4: Sistem Tiket Bioskop',
        'description' => '<h3>Tugas Mingguan</h3><p>Buat fungsi <code>hargaTiket</code> dengan parameter:</p><ul><li><code>umur</code> (number)</li><li><code>hari</code> (string: "senin"|"selasa"|...|"minggu" — huruf kecil)</li><li><code>isMember</code> (boolean)</li></ul><h3>Aturan Harga Dasar</h3><ul><li>Anak (&lt;12): Rp25000</li><li>Remaja (12–17): Rp35000</li><li>Dewasa (18–59): Rp50000</li><li>Lansia (&gt;=60): Rp30000</li></ul><h3>Tambahan</h3><ul><li>Weekend (sabtu/minggu): +Rp5000 ke harga dasar</li><li>Member: diskon 15% dari total (setelah weekend)</li></ul><p>Return string: <code>Tiket: Rp[total]</code> (bulatkan ke integer).</p>',
        'mode' => 'exam',
        'startercode' => "function hargaTiket(umur, hari, isMember) {\n    // Tentukan harga dasar by umur, tambah weekend, diskon member\n}\n\nconsole.log(hargaTiket(25, 'senin', false));\nconsole.log(hargaTiket(10, 'sabtu', true));\nconsole.log(hargaTiket(65, 'minggu', false));",
        'testcases' => '[{"input":"25,senin,false","expected":"Tiket: Rp50000"},{"input":"10,sabtu,true","expected":"Tiket: Rp25500"},{"input":"65,minggu,false","expected":"Tiket: Rp35000"}]',
    ],
];

return $week4_data;
