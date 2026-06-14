<?php
/**
 * Practice, micro quiz, and weekly assessment data for Week 1.
 */

require_once(__DIR__ . '/js_course_week_helpers.php');

return [
    'lesson_enrichment' => [
        0 => [
            'practice' => [
                'name' => 'Praktik ML1.1 - Hello World',
                'description' => '<h3>Instruksi</h3><ol><li>Gunakan <code>console.log()</code> untuk menampilkan teks <strong>Hello World</strong></li><li>Jalankan kode dan pastikan output muncul di console</li></ol><p><strong>Petunjuk AI:</strong> Aktifkan hint jika kesulitan.</p>',
                'startercode' => "// Tampilkan Hello World di console\n\n",
                'testcases' => '[{"input":"","expected":"Hello World"}]',
            ],
            'micro_quiz' => [
                js_mcq('Apa fungsi utama JavaScript?', ['Membuat website menjadi interaktif', 'Mendesain tampilan website', 'Menyimpan data di database', 'Membuat server website'], 0, 'JavaScript membuat website interaktif dan dinamis.'),
                js_mcq('Siapa yang menciptakan JavaScript?', ['Bill Gates', 'Brendan Eich', 'Mark Zuckerberg', 'Steve Jobs'], 1, 'JavaScript diciptakan oleh Brendan Eich pada 1995.'),
                js_mcq('Apakah JavaScript sama dengan Java?', ['Ya, keduanya sama', 'Tidak, keduanya berbeda', 'Java adalah versi lama JavaScript', 'JavaScript adalah versi baru Java'], 1, 'Keduanya adalah bahasa pemrograman yang berbeda.'),
            ],
        ],
        1 => [
            'practice' => [
                'name' => 'Praktik ML1.2 - Script di HTML',
                'description' => '<h3>Instruksi</h3><p>Tulis kode yang menampilkan:</p><ol><li><code>console.log("Program dimulai")</code></li><li><code>alert("Selamat Datang")</code></li></ol>',
                'startercode' => "console.log('Program dimulai');\n// Tambahkan alert di bawah\n\n",
                'testcases' => '[{"input":"","expected":"Program dimulai"}]',
            ],
            'micro_quiz' => [
                js_mcq('Cara tercepat menjalankan JavaScript adalah?', ['File .js', 'Browser Console', 'Text editor', 'Command prompt'], 1, 'Console browser (F12) paling cepat untuk uji coba.'),
                js_mcq('Shortcut membuka Console di Windows?', ['F11', 'F12', 'Ctrl+C', 'Alt+F4'], 1, 'Tekan F12 atau Ctrl+Shift+J.'),
                js_mcq('Tag script sebaiknya diletakkan?', ['Di head', 'Sebelum /body', 'Di title', 'Setelah /html'], 1, 'Sebelum penutup body agar halaman cepat dimuat.'),
            ],
        ],
        2 => [
            'practice' => [
                'name' => 'Praktik ML1.3 - Sintaks Dasar',
                'description' => '<h3>Instruksi</h3><p>Buat dua variabel <code>nama</code> dan <code>Nama</code> dengan nilai berbeda, lalu tampilkan keduanya dengan <code>console.log</code> untuk membuktikan case sensitive.</p>',
                'startercode' => "let nama = 'budi';\nlet Nama = 'BUDI';\n// Tampilkan keduanya\n\n",
                'testcases' => '[{"input":"","expected":"budi"}]',
            ],
            'micro_quiz' => [
                js_mcq('Statement JavaScript diakhiri dengan?', ['Titik (.)', 'Titik koma (;)', 'Koma (,)', 'Titik dua (:)'], 1, 'Disarankan mengakhiri statement dengan titik koma.'),
                js_mcq('Apakah JavaScript case sensitive?', ['Ya', 'Tidak', 'Tergantung browser', 'Hanya variabel'], 0, 'Huruf besar dan kecil dianggap berbeda.'),
                js_mcq('Komentar satu baris menggunakan?', ['/* */', '//', '#', '<!-- -->'], 1, 'Gunakan // untuk komentar satu baris.'),
            ],
        ],
        3 => [
            'practice' => [
                'name' => 'Praktik ML1.4 - Output JavaScript',
                'description' => '<h3>Instruksi</h3><p>Gunakan <code>console.log</code> untuk menampilkan kalimat: <strong>Belajar JavaScript itu menyenangkan</strong></p>',
                'startercode' => "// Gunakan console.log\n\n",
                'testcases' => '[{"input":"","expected":"Belajar JavaScript itu menyenangkan"}]',
            ],
            'micro_quiz' => [
                js_mcq('Method paling sering untuk debugging?', ['alert()', 'console.log()', 'document.write()', 'prompt()'], 1, 'console.log() untuk debugging.'),
                js_mcq('Method yang menampilkan popup?', ['console.log()', 'alert()', 'print()', 'show()'], 1, 'alert() menampilkan dialog popup.'),
                js_mcq('Mengapa document.write() tidak direkomendasikan?', ['Terlalu lambat', 'Tidak berfungsi', 'Menghapus konten halaman', 'Tidak didukung'], 2, 'Dapat menimpa konten halaman yang sudah dimuat.'),
            ],
        ],
    ],
    'weekly_quiz' => [
        js_mcq('JavaScript berjalan di?', ['Server', 'Browser', 'Database', 'OS'], 1, 'JavaScript client-side berjalan di browser.'),
        js_mcq('Output: console.log("Hello" + " " + "World");', ['Hello World', 'HelloWorld', 'Hello + World', 'Error'], 0, 'Operator + menggabungkan string.'),
        js_mcq('Komentar multi-baris yang benar?', ['// //', '/* */', '# #', '<!-- -->'], 1, '/* */ untuk multi-baris.'),
        js_mcq('Fungsi console.log()?', ['Alert', 'Output di console', 'Simpan data', 'Hapus data'], 1, 'Menampilkan output di console.'),
        js_mcq('Nama variabel TIDAK valid?', ['nama_siswa', '_private', '123nama', '$jquery'], 2, 'Tidak boleh diawali angka.'),
        js_mcq('Case sensitive artinya?', ['Besar/kecil sama', 'Besar/kecil beda', 'Hanya huruf besar', 'Hanya huruf kecil'], 1, 'nama dan Nama berbeda.'),
        js_mcq('Tag untuk JavaScript di HTML?', ['<js>', '<javascript>', '<script>', '<code>'], 2, 'Gunakan tag script.'),
        js_mcq('Ekstensi file JavaScript?', ['.java', '.js', '.javascript', '.jscript'], 1, 'Ekstensi standar .js'),
        js_mcq('Mengubah konten elemen HTML?', ['console.log()', 'alert()', 'innerHTML', 'document.write()'], 2, 'innerHTML mengubah isi elemen.'),
        js_mcq('Lupa titik koma di akhir statement?', ['Error fatal', 'Tidak jalan', 'Biasanya tetap jalan (ASI)', 'Browser crash'], 2, 'Automatic semicolon insertion membantu.'),
    ],
    'weekly_assignment' => [
        'name' => 'Weekly Assignment - Minggu 1',
        'description' => '<h3>Tugas: Profil Saya</h3><p>Buat program yang menampilkan:</p><ul><li>Nama lengkap</li><li>Umur</li><li>3 hobi</li></ul><p>Gunakan <code>console.log</code> dengan format header <strong>=== PROFIL SAYA ===</strong></p><h4>Rubrik</h4><ul><li>Kode berjalan (30%)</li><li>console.log benar (40%)</li><li>Format rapi (30%)</li></ul>',
        'startercode' => "console.log('=== PROFIL SAYA ===');\n// Nama, umur, hobi\n\n",
        'testcases' => '[{"input":"","expected":"=== PROFIL SAYA ==="}]',
    ],
];
