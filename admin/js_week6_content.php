<?php
/**
 * MINGGU 6: FUNCTION DASAR & EVENT HANDLING (Iframe Preview)
 */

require_once(__DIR__ . '/js_course_week_helpers.php');

$quizextra = require(__DIR__ . '/js_quiz_supplements.php');

$sapahtml = js_iframe_html(
    '<h1>Belajar Function</h1>
<button id="btnSapa">Klik Saya</button>
<p id="output"></p>',
    'Function & Event'
);

$kalkhtml = js_iframe_html(
    '<h2>Kalkulator Tambah</h2>
<input type="number" id="angka1" placeholder="Angka 1">
<input type="number" id="angka2" placeholder="Angka 2">
<button id="btnHitung">Hitung</button>
<p id="hasil"></p>',
    'Kalkulator'
);

$counterhtml = js_iframe_html(
    '<button id="btnPlus">+1</button>
<p>Counter: <span id="counter">0</span></p>',
    'Counter'
);

$previewhtml = js_iframe_html(
    '<input type="text" id="namaInput" placeholder="Ketik nama...">
<p id="preview">Ketik nama...</p>',
    'Live Preview'
);

$week6_data = [
    'section_name' => 'Minggu 6: Function Dasar & Event Handling',
    'section_summary' => js_week_summary(
        '<p>Setelah menyelesaikan minggu ini, Anda akan mampu:</p>
        <ul>
            <li>Membuat dan memanggil function JavaScript</li>
            <li>Menggunakan parameter dan return value</li>
            <li>Menangani event click dan input pada elemen HTML</li>
            <li>Memanipulasi DOM sederhana via Iframe Preview</li>
        </ul>',
        4,
        70
    ),

    'micro_lessons' => [
        [
            'title' => 'ML6.1 - Pengenalan Function & Event Click',
            'intro' => 'Pelajari function dan hubungkan ke tombol HTML dengan event click.',
            'content' => js_ml_wrap(
                'Function & Event Click',
                js_ml_explain(
                    '<p><strong>Function</strong> adalah blok kode reusable. Di web interaktif, function sering dipanggil saat user melakukan <strong>event</strong> (misalnya klik tombol).</p>'
                )
                . js_ml_points([
                    'Deklarasi: <code>function nama() { ... }</code>',
                    'Event: <code>element.addEventListener("click", fn)</code>',
                    'DOM: <code>document.getElementById("id")</code>',
                    'Ubah teks: <code>element.textContent = "..."</code>',
                ])
                . js_ml_code(
                    'Contoh',
                    "function sapa() {\n  document.getElementById('output').textContent = 'Halo, JavaScript!';\n}\ndocument.getElementById('btnSapa').addEventListener('click', sapa);"
                )
                . js_ml_info('Mulai minggu ini, latihan menggunakan <strong>Iframe Preview</strong> — Anda melihat hasil visual di halaman.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML6.1 - Tombol Sapa',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Buat function <code>sapa()</code> yang mengubah teks <code>#output</code> menjadi <code>"Halo, JavaScript!"</code>. Panggil saat <code>#btnSapa</code> diklik.</p>',
                'htmltemplate' => $sapahtml,
                'csstemplate' => js_iframe_css(),
                'startercode' => "function sapa() {\n  // Ubah #output\n}\n\n// addEventListener click\n",
                'testcases' => '[]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Function adalah?', ['Blok kode reusable', 'Tipe data', 'Loop', 'CSS'], 0, 'Function dipanggil berulang.'),
                js_mcq('addEventListener("click") saat?', ['Tombol diklik', 'Page load', 'Error', 'Scroll'], 0, 'Click event.'),
                js_mcq('getElementById membutuhkan?', ['id', 'class', 'tag', 'name'], 0, 'Select by id.'),
                js_mcq('textContent untuk?', ['Set teks elemen', 'Hapus elemen', 'Loop', 'Sort'], 0, 'Text node content.'),
            ], $quizextra[6][0] ?? []),
        ],
        [
            'title' => 'ML6.2 - Parameter, Return & Kalkulator',
            'intro' => 'Gunakan parameter, return, dan event untuk kalkulator sederhana.',
            'content' => js_ml_wrap(
                'Parameter & Return',
                js_ml_explain(
                    '<p><strong>Parameter</strong> adalah input function. <strong>return</strong> mengirim hasil kembali ke pemanggil.</p>'
                )
                . js_ml_code(
                    'Contoh',
                    "function tambah(a, b) {\n  return a + b;\n}\n// Ambil value input, panggil tambah, tampilkan di #hasil"
                )
                . js_ml_note('Gunakan <code>Number(input.value)</code> untuk mengonversi input angka.')
            ),
            'estimated_time' => 12,
            'practice' => [
                'name' => 'Praktik ML6.2 - Kalkulator Tambah',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Buat <code>tambah(a,b)</code> dengan return. Saat tombol Hitung diklik, tampilkan <code>"Hasil: [jumlah]"</code> di <code>#hasil</code>.</p>',
                'htmltemplate' => $kalkhtml,
                'csstemplate' => js_iframe_css(),
                'startercode' => "function tambah(a, b) {\n  return a + b;\n}\n\ndocument.getElementById('btnHitung').addEventListener('click', function() {\n  // Ambil angka1, angka2, tampilkan hasil\n});\n",
                'testcases' => '[]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('return berfungsi?', ['Mengembalikan nilai', 'Stop browser', 'Loop', 'CSS'], 0, 'Return value to caller.'),
                js_mcq('Parameter adalah?', ['Input function', 'Output', 'Event', 'HTML'], 0, 'Parameters are inputs.'),
                js_mcq('Number("10") + 5 = ?', ['15', '"105"', 'NaN', 'Error'], 0, 'Konversi lalu tambah.'),
                js_mcq('input.value memberikan?', ['String dari input', 'Boolean', 'Array', 'Function'], 0, 'Value selalu string.'),
            ], $quizextra[6][1] ?? []),
        ],
        [
            'title' => 'ML6.3 - Event Handling Click (Counter)',
            'intro' => 'Buat counter yang naik setiap tombol diklik.',
            'content' => js_ml_wrap(
                'Counter dengan Event Click',
                js_ml_explain(
                    '<p>Simpan state (misalnya <code>let count = 0</code>) di luar handler. Setiap klik, increment dan update DOM.</p>'
                )
                . js_ml_code(
                    'Pola Counter',
                    "let count = 0;\nbtn.addEventListener('click', function() {\n  count++;\n  document.getElementById('counter').textContent = count;\n});"
                )
            ),
            'estimated_time' => 10,
            'practice' => [
                'name' => 'Praktik ML6.3 - Counter Klik',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Setiap klik <code>#btnPlus</code>, counter di <code>#counter</code> naik 1 (mulai dari 0).</p>',
                'htmltemplate' => $counterhtml,
                'csstemplate' => js_iframe_css(),
                'startercode' => "let count = 0;\n\ndocument.getElementById('btnPlus').addEventListener('click', function() {\n  // count++ dan update #counter\n});\n",
                'testcases' => '[]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Counter perlu variabel?', ['Ya, di luar handler', 'Tidak', 'Hanya di dalam', 'Hanya const'], 0, 'State persists across clicks.'),
                js_mcq('count++ artinya?', ['count = count + 1', 'count + 1 saja', 'count--', 'count * 2'], 0, 'Increment operator.'),
                js_mcq('Event "click" dari?', ['Mouse klik', 'Keyboard', 'Load', 'Error'], 0, 'Mouse click.'),
                js_mcq('Multiple click = counter naik?', ['Ya', 'Tidak', 'Reset', 'Error'], 0, 'Each click increments.'),
            ], $quizextra[6][2] ?? []),
        ],
        [
            'title' => 'ML6.4 - Event Input & DOM Sederhana',
            'intro' => 'Tampilkan preview nama secara live saat user mengetik.',
            'content' => js_ml_wrap(
                'Event Input & Live Preview',
                js_ml_explain(
                    '<p>Event <code>input</code> terpicu saat nilai input berubah. Gunakan <code>this.value</code> atau <code>element.value</code> untuk membaca teks.</p>'
                )
                . js_ml_code(
                    'Contoh',
                    "input.addEventListener('input', function() {\n  let nama = this.value;\n  preview.textContent = nama ? 'Halo, ' + nama + '!' : 'Ketik nama...';\n});"
                )
            ),
            'estimated_time' => 10,
            'practice' => [
                'name' => 'Praktik ML6.4 - Live Preview Nama',
                'mode' => 'training',
                'description' => '<h3>Tugas</h3><p>Saat user mengetik di <code>#namaInput</code>, <code>#preview</code> menampilkan <code>"Halo, [nama]!"</code> atau <code>"Ketik nama..."</code> jika kosong.</p>',
                'htmltemplate' => $previewhtml,
                'csstemplate' => js_iframe_css(),
                'startercode' => "document.getElementById('namaInput').addEventListener('input', function() {\n  // Update #preview\n});\n",
                'testcases' => '[]',
            ],
            'micro_quiz' => js_micro_quiz_full([
                js_mcq('Event input terpicu saat?', ['User mengetik', 'Klik', 'Load', 'Close'], 0, 'On value change.'),
                js_mcq('DOM adalah?', ['Representasi HTML di JS', 'Database', 'CSS', 'PHP'], 0, 'Document Object Model.'),
                js_mcq('this.value di handler input?', ['Nilai input saat ini', 'Id', 'Class', 'Loop'], 0, 'Current input value.'),
                js_mcq('Live preview memakai event?', ['input', 'only click', 'only load', 'error'], 0, 'Real-time on input.'),
            ], $quizextra[6][3] ?? []),
        ],
    ],

    'weekly_quiz' => [
        js_mcq('Function keyword deklarasi?', ['function', 'fun', 'def', 'func'], 0, 'function nama() {}'),
        js_mcq('return tanpa value mengembalikan?', ['undefined', 'null', '0', 'false'], 0, 'Implicit undefined.'),
        js_mcq('addEventListener untuk?', ['Pasang event handler', 'Loop', 'Array', 'CSS'], 0, 'Attach event listener.'),
        js_mcq('getElementById return jika tidak ada?', ['null', 'undefined', 'Error', 'false'], 0, 'Returns null.'),
        js_mcq('textContent vs innerHTML untuk teks aman?', ['textContent', 'innerHTML', 'Sama', 'Tidak ada'], 0, 'textContent safer for text.'),
        js_mcq('Parameter vs argumen?', ['Param di definisi, argumen saat panggil', 'Sama', 'Argumen di definisi', 'Tidak ada beda'], 0, 'Definition vs call.'),
        js_mcq('Arrow function syntax?', ['() => {}', 'function=>', '->', '::'], 0, 'ES6 arrow.'),
        js_mcq('Event click dipicu oleh?', ['Mouse', 'Keyboard only', 'Server', 'Database'], 0, 'Mouse click.'),
        js_mcq('Number(input.value) untuk?', ['Konversi ke angka', 'Hapus input', 'Sort', 'Filter'], 0, 'Parse input to number.'),
        js_mcq('Minggu 6 output utama?', ['Iframe Preview', 'Console only', 'PDF', 'Email'], 0, 'Visual web output.'),
    ],

    'weekly_assignment' => [
        'name' => 'Weekly Assignment - Minggu 6: Kalkulator Web',
        'description' => '<h3>Tugas Mingguan — Iframe</h3>
            <p>Lengkapi kalkulator 4 operasi (+, −, ×, ÷). Buat function <code>hitung(a,b,op)</code> dan hubungkan ke 4 tombol operasi.</p>
            <p>Tampilkan <code>"Hasil: X"</code> di #hasil. Untuk bagi, jika pembagi 0 tampilkan <code>"Error"</code>.</p>
            <h4>Rubrik</h4><ul><li>Function hitung benar (25)</li><li>4 operasi works (40)</li><li>Event listener (20)</li><li>UI rapi (15)</li></ul>',
        'mode' => 'exam',
        'htmltemplate' => js_iframe_html(
            '<h2>Kalkulator (+, -, ×, ÷)</h2>
<input type="number" id="a" placeholder="Angka 1">
<input type="number" id="b" placeholder="Angka 2">
<br>
<button data-op="+">+</button>
<button data-op="-">-</button>
<button data-op="*">×</button>
<button data-op="/">÷</button>
<p id="hasil">Hasil: -</p>',
            'Kalkulator Web'
        ),
        'csstemplate' => js_iframe_css(),
        'startercode' => "function hitung(a, b, op) {\n  // return hasil atau 'Error' untuk bagi 0\n}\n\ndocument.querySelectorAll('button[data-op]').forEach(function(btn) {\n  btn.addEventListener('click', function() {\n    // Ambil a, b, panggil hitung, tampilkan\n  });\n});\n",
        'testcases' => '[]',
    ],
];

return $week6_data;
