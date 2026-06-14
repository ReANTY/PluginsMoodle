<?php
/**
 * MINGGU 8: FINAL PROJECT
 * Micro Lessons: ML8.1–ML8.4 + Final Project (Iframe)
 */

require_once(__DIR__ . '/js_course_week_helpers.php');

$quizextra = require(__DIR__ . '/js_quiz_supplements.php');

$todohtml = js_iframe_html(
    '<h1>📝 Todo List Saya</h1>
<div class="input-group" style="display:flex;gap:8px;margin-bottom:16px;">
  <input type="text" id="inputTodo" placeholder="Tulis tugas baru..." style="flex:1;">
  <button id="btnTambah">Tambah</button>
</div>
<ul id="listTodo"></ul>
<p id="info">Total: 0 tugas</p>',
    'Final Project - Todo List'
);

$week8_data = [
    'section_name' => 'Minggu 8: Final Project',
    'section_summary' => js_week_summary(
        '<p>Setelah menyelesaikan minggu ini, Anda akan mampu:</p>
        <ul>
            <li>Merencanakan aplikasi web interaktif sederhana</li>
            <li>Menyusun struktur HTML/CSS dan logika JavaScript</li>
            <li>Mengintegrasikan array, function, event, dan DOM</li>
            <li>Menyelesaikan dan mengirim final project</li>
        </ul>',
        4,
        90
    ),

    'micro_lessons' => [
        [
            'title' => 'ML8.1 - Persiapan Final Project',
            'intro' => 'Pelajari cara merencanakan project, memilih opsi, dan menyusun pseudocode.',
            'content' => js_ml_wrap(
                '🎯 Persiapan Final Project',
                js_ml_explain(
                    '<p>Final project adalah bukti kompetensi Anda setelah 7 minggu belajar JavaScript. '
                    . 'Pilih <strong>satu</strong> dari tiga opsi:</p>
                    <ol>
                        <li><strong>Kalkulator Digital</strong> — tombol angka & operasi</li>
                        <li><strong>Todo List</strong> — tambah & hapus tugas (rekomendasi)</li>
                        <li><strong>Kasir Sederhana</strong> — daftar produk & total belanja</li>
                    </ol>
                    <p>Langkah perencanaan: tentukan fitur → sketsa elemen HTML → daftar event → struktur data (array/object).</p>'
                )
                . js_ml_points([
                    'Rencanakan sebelum menulis kode',
                    'Gunakan array untuk menyimpan data dinamis',
                    'Pisahkan: data, render, event handler',
                    'Uji edge case: input kosong, hapus item',
                ])
                . js_ml_code(
                    'Contoh Pseudocode Todo List',
                    '// 1. Array todos = []
// 2. Function render() — tampilkan ul
// 3. Klik Tambah — push ke array, render ulang
// 4. Klik Hapus — splice dari array, render ulang
// 5. Update counter total'
                )
                . js_ml_note('Final project dinilai via aktivitas Aicode mode Ujian dengan Iframe Preview. Passing grade: 70%.')
            ),
            'estimated_time' => 10,
            'practice' => [
                'name' => 'Praktik ML8.1 - Pseudocode Project',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Tulis pseudocode 5 langkah untuk Todo List menggunakan <code>console.log</code> (satu langkah per baris).</p>',
                'startercode' => "console.log('1. Buat array todos');\nconsole.log('2. ...');\n// Lengkapi langkah 3-5\n",
                'testcases' => '[{"input":"","expected":"1. Buat array todos"}]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Langkah pertama membuat project?', ['Langsung coding tanpa rencana', 'Merencanakan fitur', 'Copy paste semua', 'Hanya CSS'], 1, 'Perencanaan mencegah kebingungan saat coding.'),
                js_mcq('Todo List membutuhkan struktur data?', ['Array', 'Boolean saja', 'Null saja', 'Tidak perlu data'], 0, 'Array menyimpan daftar tugas.'),
                js_mcq('Event penting pada Todo List?', ['click / input', 'Hanya load', 'Hanya error', 'Tidak ada event'], 0, 'Tombol tambah/hapus membutuhkan event click.'),
                js_mcq('Edge case yang harus diuji?', ['Input kosong', 'Warna font', 'Ukuran monitor', 'Versi PHP'], 0, 'Input kosong harus ditangani.'),
            ], $quizextra[8][0] ?? []),
        ],
        [
            'title' => 'ML8.2 - Struktur HTML/CSS Project',
            'intro' => 'Susun kerangka HTML dan CSS untuk project web interaktif.',
            'content' => js_ml_wrap(
                '🏗️ Struktur HTML/CSS',
                js_ml_explain(
                    '<p>Gunakan elemen HTML yang jelas: <code>input</code>, <code>button</code>, <code>ul</code>/<code>li</code>, atau <code>table</code>. '
                    . 'Berikan <code>id</code> pada setiap elemen yang akan dimanipulasi JavaScript.</p>'
                )
                . js_ml_points([
                    'id unik per elemen target DOM',
                    'class untuk styling konsisten',
                    'Layout sederhana: flexbox untuk form',
                    'CSS minimal — fokus fungsi dulu',
                ])
                . js_ml_code(
                    'Kerangka Todo List',
                    '<input id="inputTodo" placeholder="Tugas...">
<button id="btnTambah">Tambah</button>
<ul id="listTodo"></ul>
<p id="info">Total: 0</p>'
                )
                . js_ml_info('Di Aicode, HTML/CSS disediakan guru sebagai template. Siswa menulis JavaScript saja.')
            ),
            'estimated_time' => 10,
            'practice' => [
                'name' => 'Praktik ML8.2 - Siapkan DOM Todo',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Template HTML sudah disediakan. Tulis JS yang menampilkan <code>"Siap membangun Todo List!"</code> di <code>#info</code> saat halaman dimuat.</p>',
                'htmltemplate' => $todohtml,
                'csstemplate' => js_iframe_css(),
                'startercode' => "document.getElementById('info').textContent = 'Siap membangun Todo List!';\n",
                'testcases' => '[]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Atribut untuk hook JavaScript?', ['id', 'lang', 'charset', 'xmlns'], 0, 'getElementById membutuhkan id.'),
                js_mcq('Elemen daftar tugas?', ['ul + li', 'meta', 'link', 'head'], 0, 'ul/li cocok untuk list.'),
                js_mcq('Flexbox pada input-group untuk?', ['Layout horizontal', 'Loop array', 'Database', 'Server'], 0, 'Flex memudahkan baris input+tombol.'),
                js_mcq('Siswa di Aicode iframe mengedit?', ['JavaScript', 'HTML guru', 'CSS guru', 'Database'], 0, 'Template HTML/CSS read-only untuk siswa.'),
            ], $quizextra[8][1] ?? []),
        ],
        [
            'title' => 'ML8.3 - Logika JavaScript Project',
            'intro' => 'Implementasikan array, function, render, dan event handler.',
            'content' => js_ml_wrap(
                '⚙️ Logika JavaScript',
                js_ml_explain(
                    '<p>Pola umum project interaktif:</p>
                    <ol>
                        <li><strong>Data</strong> — <code>let todos = [];</code></li>
                        <li><strong>Render</strong> — function yang membangun ulang tampilan dari data</li>
                        <li><strong>Handler</strong> — event click/input yang mengubah data lalu memanggil render</li>
                    </ol>'
                )
                . js_ml_code(
                    'Pola Tambah Item',
                    "let todos = [];\nfunction render() {\n  // kosongkan list, loop todos, buat li\n}\ndocument.getElementById('btnTambah').addEventListener('click', function() {\n  let teks = document.getElementById('inputTodo').value.trim();\n  if (teks === '') return;\n  todos.push(teks);\n  render();\n});"
                )
                . js_ml_note('Panggil render() setiap kali data berubah agar tampilan selalu sinkron.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML8.3 - Tambah ke Array',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Buat array <code>todos</code>. Saat tombol Tambah diklik, push nilai input (jika tidak kosong) ke array, lalu tampilkan jumlah item di <code>#info</code> sebagai <code>"Total: X tugas"</code>.</p>',
                'htmltemplate' => $todohtml,
                'csstemplate' => js_iframe_css(),
                'startercode' => "let todos = [];\n\n// Event tambah + update #info\n",
                'testcases' => '[]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Setelah data berubah harus?', ['Panggil render()', 'Refresh browser', 'Hapus HTML', 'Logout'], 0, 'Render memperbarui DOM.'),
                js_mcq('Validasi input kosong?', ['if (teks === "") return', 'Selalu push', 'alert wajib', 'throw error'], 0, 'Guard clause mencegah item kosong.'),
                js_mcq('Method tambah ke array?', ['push', 'pop', 'shift', 'sort'], 0, 'push menambah di akhir.'),
                js_mcq('trim() pada input untuk?', ['Hapus spasi ujung', 'Hapus array', 'Sort data', 'Loop'], 0, 'trim membersihkan spasi.'),
            ], $quizextra[8][2] ?? []),
        ],
        [
            'title' => 'ML8.4 - Finalisasi, Testing & Submit',
            'intro' => 'Checklist testing, perbaikan bug, dan cara submit final project.',
            'content' => js_ml_wrap(
                '✅ Finalisasi & Submit',
                js_ml_explain(
                    '<p>Sebelum submit, uji checklist:</p>
                    <ul>
                        <li>Semua tombol berfungsi</li>
                        <li>Input kosong ditolak</li>
                        <li>Hapus item works (jika ada)</li>
                        <li>Tampilan rapi di iframe preview</li>
                        <li>Tidak ada error di console (F12)</li>
                    </ul>'
                )
                . js_ml_points([
                    'Submit via aktivitas Final Project (mode Ujian)',
                    'Bobot final project: 30% nilai course',
                    'Nilai minimal: 70%',
                    'Sertifikat otomatis setelah lulus course',
                ])
                . js_ml_note('Mode ujian: tidak ada AI hint. Pastikan kode sudah diuji di preview sebelum submit.')
            ),
            'estimated_time' => 8,
            'practice' => [
                'name' => 'Praktik ML8.4 - Render List Lengkap',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Lengkapi Todo List mini: array awal <code>["Belajar JS", "Kerjakan project"]</code>, render ke <code>#listTodo</code> sebagai item <code>&lt;li&gt;</code>, update <code>#info</code>.</p>',
                'htmltemplate' => $todohtml,
                'csstemplate' => js_iframe_css(),
                'startercode' => "let todos = ['Belajar JS', 'Kerjakan project'];\n\nfunction render() {\n  // Render li dan update info\n}\nrender();\n",
                'testcases' => '[]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Alat debug di browser?', ['Console F12', 'Notepad', 'Paint', 'Excel'], 0, 'Console menampilkan error JS.'),
                js_mcq('Submit final project via?', ['Aicode Ujian', 'Email guru', 'Kertas', 'WhatsApp'], 0, 'Submit di aktivitas Moodle.'),
                js_mcq('Passing grade project?', ['70%', '30%', '100% wajib sempurna', '0%'], 0, 'Minimal 70% sesuai rubrik.'),
                js_mcq('Setelah lulus course dapat?', ['Sertifikat (jika syarat terpenuhi)', 'Tidak ada', 'Hanya badge', 'Surat izin'], 0, 'Sertifikat otomatis jika completion + grade OK.'),
            ], $quizextra[8][3] ?? []),
        ],
    ],

    'weekly_quiz' => [
        js_mcq('Pola data + render + event disebut?', ['Pemisahan tanggung jawab', 'Hanya CSS', 'Hanya HTML', 'Database'], 0, 'Memisahkan data, tampilan, dan aksi.'),
        js_mcq('createElement("li") untuk?', ['Buat item list', 'Buat tabel', 'Buat form', 'Buat CSS'], 0, 'li untuk bullet/list item.'),
        js_mcq('splice() pada array untuk?', ['Hapus/ubah elemen', 'Sort', 'Reverse', 'Join string'], 0, 'splice memanipulasi array.'),
        js_mcq('addEventListener("keypress") bisa untuk?', ['Enter submit', 'Hanya klik', 'Hanya scroll', 'Tidak valid'], 0, 'Enter mempercepat UX todo list.'),
        js_mcq('innerHTML pada li untuk?', ['Set HTML string', 'Hanya angka', 'Hanya boolean', 'Hapus node'], 0, 'innerHTML bisa set markup tombol hapus.'),
        js_mcq('dataset.i pada tombol hapus menyimpan?', ['Index item', 'Warna', 'Font', 'URL'], 0, 'Index untuk splice array.'),
        js_mcq('Project Todo butuh array?', ['Ya', 'Tidak', 'Hanya string satu', 'Hanya number'], 0, 'Array menyimpan banyak tugas.'),
        js_mcq('Preview iframe di Aicode menampilkan?', ['Hasil visual HTML+JS', 'Hanya console', 'PDF', 'Email'], 0, 'Iframe preview untuk minggu 6-8.'),
        js_mcq('Rubrik final project total?', ['100 poin', '10 poin', '1000 poin', '50 poin'], 0, 'Rubrik 100 poin, pass 70.'),
        js_mcq('Checklist sebelum submit?', ['Uji semua fitur', 'Tidak perlu uji', 'Hanya save', 'Hanya CSS'], 0, 'Testing wajib sebelum submit.'),
    ],

    'weekly_assignment' => [
        'name' => 'Weekly Assignment - Minggu 8: Mini Todo (Latihan)',
        'description' => '<h3>Latihan Pra-Final</h3><p>Lengkapi Todo List: tambah item via tombol, render list, tombol hapus per item. Validasi input kosong.</p>',
        'mode' => 'exam',
        'htmltemplate' => $todohtml,
        'csstemplate' => js_iframe_css(),
        'startercode' => "let todos = [];\n\nfunction render() {\n  // TODO: render ul + tombol hapus\n}\n\n// TODO: event tambah\n",
        'testcases' => '[]',
    ],

    'final_project' => [
        'name' => 'Final Project - JavaScript Fundamental',
        'intro' => 'Tugas akhir — Todo List interaktif lengkap (tambah, tampilkan, hapus). Alternatif: kalkulator/kasir (konsultasi guru).',
        'description' => '<h3>Final Project: Todo List Interaktif</h3>
            <p>Lengkapi JavaScript pada template Todo List:</p>
            <ul>
                <li>Tambah tugas dari input (tombol + Enter)</li>
                <li>Tampilkan semua tugas di list</li>
                <li>Hapus tugas per item</li>
                <li>Tampilkan total tugas di #info</li>
                <li>Validasi input kosong</li>
            </ul>
            <h4>Rubrik (100 poin)</h4>
            <ul>
                <li>Fitur utama berfungsi — 30</li>
                <li>Event handling — 20</li>
                <li>Array/object — 15</li>
                <li>DOM manipulation — 15</li>
                <li>Validasi edge case — 10</li>
                <li>UI rapi — 10</li>
            </ul>
            <p><strong>Passing grade:</strong> 70%</p>',
        'htmltemplate' => $todohtml,
        'csstemplate' => js_iframe_css() . '
.btn-hapus { background: #dc2626; font-size: 12px; padding: 4px 10px; }
li { display: flex; justify-content: space-between; align-items: center; }',
        'startercode' => "let todos = [];\n\nfunction render() {\n  // Kosongkan #listTodo, loop todos, buat li + tombol hapus\n  // Update #info\n}\n\ndocument.getElementById('btnTambah').addEventListener('click', function() {\n  // Ambil input, validasi, push, render\n});\n\n// Opsional: Enter pada input\n",
        'testcases' => '[]',
    ],
];

return $week8_data;
