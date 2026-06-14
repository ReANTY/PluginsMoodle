<?php
/**
 * MINGGU 7: ARRAY, OBJECT DASAR & MANIPULASI DOM (Iframe Preview)
 */

require_once(__DIR__ . '/js_course_week_helpers.php');

$quizextra = require(__DIR__ . '/js_quiz_supplements.php');

$listhtml = js_iframe_html('<h2>Daftar Buah</h2><ul id="listBuah"></ul>', 'Array ke List');
$profilhtml = js_iframe_html('<div id="profil"></div>', 'Profil Object');
$ubahhtml = js_iframe_html(
    '<p id="teks">Teks awal</p><button id="btnUbah">Ubah</button>',
    'Ubah DOM'
);
$tabelhtml = js_iframe_html(
    '<h2>Nilai Siswa</h2>
<table>
<thead><tr><th>Nama</th><th>Nilai</th></tr></thead>
<tbody id="tbodyNilai"></tbody>
</table>',
    'Tabel Nilai'
);

$week7_data = [
    'section_name' => 'Minggu 7: Array, Object & Manipulasi DOM',
    'section_summary' => js_week_summary(
        '<p>Setelah menyelesaikan minggu ini, Anda akan mampu:</p>
        <ul>
            <li>Membuat dan mengakses array serta object</li>
            <li>Memilih elemen DOM dengan getElementById dan querySelector</li>
            <li>Merender data array/object ke list dan tabel HTML</li>
            <li>Menggunakan createElement dan appendChild</li>
        </ul>',
        4,
        70
    ),

    'micro_lessons' => [
        [
            'title' => 'ML7.1 - Array Dasar & Render List',
            'intro' => 'Tampilkan array sebagai list HTML dinamis.',
            'content' => js_ml_wrap(
                'Array ke List HTML',
                js_ml_explain(
                    '<p><strong>Array</strong> menyimpan banyak nilai. Indeks dimulai dari 0. Gunakan loop <code>forEach</code> untuk merender ke DOM.</p>'
                )
                . js_ml_points([
                    'Array: <code>["Apel", "Mangga"]</code>',
                    '<code>push()</code> tambah elemen',
                    '<code>createElement("li")</code> buat item',
                    '<code>appendChild</code> tambah ke DOM',
                ])
                . js_ml_code(
                    'Contoh',
                    "let buah = ['Apel', 'Mangga', 'Jeruk'];\nbuah.forEach(function(item) {\n  let li = document.createElement('li');\n  li.textContent = item;\n  list.appendChild(li);\n});"
                )
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML7.1 - List Buah',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Render array <code>["Apel", "Mangga", "Jeruk"]</code> ke <code>#listBuah</code> sebagai elemen <code>&lt;li&gt;</code>.</p>',
                'htmltemplate' => $listhtml,
                'csstemplate' => js_iframe_css(),
                'startercode' => "let buah = ['Apel', 'Mangga', 'Jeruk'];\nlet list = document.getElementById('listBuah');\n\n// forEach + createElement li\n",
                'testcases' => '[]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Indeks array pertama?', ['0', '1', '-1', '2'], 0, 'Zero-indexed.'),
                js_mcq('push() menambah di?', ['Akhir', 'Awal', 'Tengah', 'Hapus'], 0, 'Append end.'),
                js_mcq('forEach untuk?', ['Loop array', 'Loop object only', 'CSS', 'PHP'], 0, 'Iterate array.'),
                js_mcq('createElement("li")?', ['Buat list item', 'Buat row', 'Buat form', 'Buat link'], 0, 'LI element.'),
            ], $quizextra[7][0] ?? []),
        ],
        [
            'title' => 'ML7.2 - Object Dasar & Kartu Profil',
            'intro' => 'Tampilkan data object ke elemen HTML.',
            'content' => js_ml_wrap(
                'Object & Kartu Profil',
                js_ml_explain(
                    '<p><strong>Object</strong> menyimpan pasangan key-value: <code>{ nama: "Budi", kelas: "XI PPLG" }</code>. Akses dengan dot: <code>siswa.nama</code>.</p>'
                )
                . js_ml_code(
                    'Contoh',
                    "let siswa = { nama: 'Budi', kelas: 'XI PPLG' };\ndocument.getElementById('profil').textContent = 'Nama: ' + siswa.nama + ' | Kelas: ' + siswa.kelas;"
                )
            ),
            'estimated_time' => 10,
            'practice' => [
                'name' => 'Praktik ML7.2 - Kartu Profil',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Object <code>{nama:"Budi", kelas:"XI PPLG"}</code> — tampilkan di <code>#profil</code>: <code>"Nama: Budi | Kelas: XI PPLG"</code>.</p>',
                'htmltemplate' => $profilhtml,
                'csstemplate' => js_iframe_css(),
                'startercode' => "let siswa = { nama: 'Budi', kelas: 'XI PPLG' };\n\n// Tampilkan di #profil\n",
                'testcases' => '[]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Object syntax?', ['{}', '[]', '()', '<>'], 0, 'Curly braces.'),
                js_mcq('siswa.nama adalah?', ['Dot notation', 'Loop', 'Event', 'CSS'], 0, 'Property access.'),
                js_mcq('Object menyimpan?', ['Key-value pairs', 'Hanya angka', 'Hanya string', 'Hanya boolean'], 0, 'Key-value structure.'),
                js_mcq('typeof {}?', ['object', 'array', 'function', 'string'], 0, 'Objects are type object.'),
            ], $quizextra[7][1] ?? []),
        ],
        [
            'title' => 'ML7.3 - Memilih & Mengubah Elemen DOM',
            'intro' => 'Selektor DOM dan manipulasi style serta teks.',
            'content' => js_ml_wrap(
                'Selektor & Manipulasi DOM',
                js_ml_explain(
                    '<p><code>getElementById</code> memilih by id. <code>querySelector</code> memilih dengan CSS selector. Ubah teks dengan <code>textContent</code>, style dengan <code>element.style</code>.</p>'
                )
                . js_ml_code(
                    'Contoh',
                    "document.getElementById('btnUbah').addEventListener('click', function() {\n  let el = document.getElementById('teks');\n  el.textContent = 'Teks diubah!';\n  el.style.color = 'red';\n});"
                )
            ),
            'estimated_time' => 10,
            'practice' => [
                'name' => 'Praktik ML7.3 - Ubah Teks & Warna',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Klik <code>#btnUbah</code> → <code>#teks</code> menjadi <code>"Teks diubah!"</code> berwarna merah.</p>',
                'htmltemplate' => $ubahhtml,
                'csstemplate' => js_iframe_css(),
                'startercode' => "document.getElementById('btnUbah').addEventListener('click', function() {\n  // Ubah #teks\n});\n",
                'testcases' => '[]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('getElementById?', ['Pilih by id', 'Pilih by class', 'Loop', 'Sort'], 0, 'ID selector.'),
                js_mcq('querySelector(".btn") . artinya?', ['Class', 'Id', 'Tag', 'Pseudo'], 0, 'Class selector.'),
                js_mcq('style.color = "red"?', ['Inline style', 'External CSS', 'PHP', 'SQL'], 0, 'JS inline style.'),
                js_mcq('id harus unik?', ['Ya per halaman', 'Tidak', 'Tidak perlu', 'Hanya class'], 0, 'Unique id.'),
            ], $quizextra[7][2] ?? []),
        ],
        [
            'title' => 'ML7.4 - Render Data ke Tabel HTML',
            'intro' => 'Loop array of objects ke baris tabel dinamis.',
            'content' => js_ml_wrap(
                'Array of Objects → Tabel',
                js_ml_explain(
                    '<p>Gunakan <code>createElement("tr")</code> dan <code>innerHTML</code> untuk membuat baris tabel dari data array object.</p>'
                )
                . js_ml_code(
                    'Contoh',
                    "let data = [{nama:'Ani', nilai:90}, {nama:'Budi', nilai:85}];\ndata.forEach(function(s) {\n  let tr = document.createElement('tr');\n  tr.innerHTML = '<td>' + s.nama + '</td><td>' + s.nilai + '</td>';\n  tbody.appendChild(tr);\n});"
                )
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML7.4 - Tabel Nilai',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Render data <code>[{nama:"Ani",nilai:90},{nama:"Budi",nilai:85}]</code> ke <code>#tbodyNilai</code> sebagai baris tabel.</p>',
                'htmltemplate' => $tabelhtml,
                'csstemplate' => js_iframe_css(),
                'startercode' => "let data = [{ nama: 'Ani', nilai: 90 }, { nama: 'Budi', nilai: 85 }];\nlet tbody = document.getElementById('tbodyNilai');\n\n// forEach + createElement tr\n",
                'testcases' => '[]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('createElement("tr")?', ['Table row', 'Div', 'Input', 'Link'], 0, 'TR element.'),
                js_mcq('innerHTML?', ['Set HTML string', 'Only text', 'Number', 'Boolean'], 0, 'HTML content.'),
                js_mcq('Array of objects?', ['[{},{}}]', '[] only strings', 'Hanya number', 'Tidak valid'], 0, 'Array containing objects.'),
                js_mcq('Render ulang setelah data berubah?', ['Update DOM', 'Otomatis', 'Tidak perlu', 'Error'], 0, 'Re-render needed.'),
            ], $quizextra[7][3] ?? []),
        ],
    ],

    'weekly_quiz' => [
        js_mcq('Array indeks mulai?', ['0', '1', '-1', 'length'], 0, 'Zero-indexed.'),
        js_mcq('buah.push("X")?', ['Tambah akhir', 'Hapus', 'Sort', 'Reverse'], 0, 'push appends.'),
        js_mcq('Object akses properti?', ['obj.key atau obj["key"]', 'Hanya loop', 'Hanya CSS', 'Tidak bisa'], 0, 'Dot or bracket.'),
        js_mcq('appendChild?', ['Tambah child DOM', 'Hapus parent', 'Sort', 'Filter'], 0, 'Add child node.'),
        js_mcq('querySelector("#x") # artinya?', ['Id', 'Class', 'Tag', 'Attr'], 0, 'ID selector.'),
        js_mcq('forEach pada array?', ['Loop tiap elemen', 'Hanya object', 'Hanya string', 'Error'], 0, 'Array iteration.'),
        js_mcq('[1,2,3].length?', ['3', '2', '1', '0'], 0, 'Three elements.'),
        js_mcq('innerHTML pada tr?', ['Bisa set td', 'Tidak bisa', 'Error', 'Hanya th'], 0, 'Set row cells.'),
        js_mcq('Array of objects untuk tabel?', ['Cocok', 'Tidak cocok', 'Hanya console', 'Hanya CSS'], 0, 'Tabular data pattern.'),
        js_mcq('getElementById tidak ketemu return?', ['null', 'undefined', 'Error', 'false'], 0, 'Returns null.'),
    ],

    'weekly_assignment' => [
        'name' => 'Weekly Assignment - Minggu 7: Daftar Siswa Interaktif',
        'description' => '<h3>Tugas Mingguan — Iframe</h3>
            <p>Form tambah siswa (nama + nilai), tombol Tambah, tabel daftar, dan tampilkan rata-rata nilai di bawah tabel.</p>
            <p>Gunakan array of objects. Validasi input kosong.</p>
            <h4>Rubrik</h4><ul><li>Tambah ke array (25)</li><li>Render tabel (30)</li><li>Hitung rata-rata (25)</li><li>Validasi (20)</li></ul>',
        'mode' => 'exam',
        'htmltemplate' => js_iframe_html(
            '<h2>Daftar Siswa</h2>
<input type="text" id="nama" placeholder="Nama">
<input type="number" id="nilai" placeholder="Nilai">
<button id="btnTambah">Tambah</button>
<table>
<thead><tr><th>Nama</th><th>Nilai</th></tr></thead>
<tbody id="tbody"></tbody>
</table>
<p id="rata">Rata-rata: -</p>',
            'Daftar Siswa'
        ),
        'csstemplate' => js_iframe_css(),
        'startercode' => "let siswa = [];\n\nfunction render() {\n  // Render tbody + hitung rata-rata di #rata\n}\n\ndocument.getElementById('btnTambah').addEventListener('click', function() {\n  // Validasi, push, render\n});\n",
        'testcases' => '[]',
    ],
];

return $week7_data;
