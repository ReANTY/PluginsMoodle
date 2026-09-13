<?php
/**
 * CLI Script to automatically setup or update the Remedial System (Weeks 1 to 5)
 * for course JS-FUND-2026 on any Moodle server (Local or Azure VM).
 *
 * Usage:
 *   php blocks/adaptive_learning_ai/cli/sync_remedial.php
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

global $DB, $CFG;

echo "========================================================\n";
echo " ADAPTIVE LEARNING: SYNC REMEDIAL SYSTEM (WEEKS 1 - 5)\n";
echo "========================================================\n\n";

// 1. Temukan Course JS-FUND-2026
$course = $DB->get_record('course', ['shortname' => 'JS-FUND-2026']);
if (!$course) {
    $course = $DB->get_record('course', ['id' => 22]);
}

if (!$course) {
    echo "ERROR: Course dengan shortname 'JS-FUND-2026' atau id 22 tidak ditemukan!\n";
    exit(1);
}

$courseid = $course->id;
$coursecontext = context_course::instance($courseid);
$admin = get_admin();
$adminid = $admin ? $admin->id : 2;

echo "Target Course: {$course->fullname} (id: {$courseid}, shortname: {$course->shortname})\n";
echo "Course Context ID: {$coursecontext->id}\n\n";

// 2. Set Config Thresholds
set_config('primary_threshold', 70, 'block_adaptive_learning_ai');
set_config('expert_threshold', 90, 'block_adaptive_learning_ai');
echo "Thresholds updated: primary=70, expert=90\n";

// Helper create category
function get_or_create_category($contextid, $name, $info = '') {
    global $DB;
    $cat = $DB->get_record('question_categories', ['contextid' => $contextid, 'name' => $name]);
    if (!$cat) {
        $cat = new stdClass();
        $cat->name = $name;
        $cat->contextid = $contextid;
        $cat->info = $info;
        $cat->infoformat = FORMAT_HTML;
        $cat->stamp = make_unique_id_code();
        $cat->parent = 0;
        $cat->sortorder = 999;
        $cat->idnumber = null;
        $cat->id = $DB->insert_record('question_categories', $cat);
        echo "  [+] Category created: {$name} (id: {$cat->id})\n";
    }
    return $cat;
}

// Helper create question
function create_or_get_multichoice_question($catid, $name, $text, $answers, $adminid) {
    global $DB;
    $existing = $DB->get_record_sql("
        SELECT q.id, qbe.id as qbeid 
        FROM {question} q
        JOIN {question_versions} qv ON qv.questionid = q.id
        JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
        WHERE qbe.questioncategoryid = ? AND q.name = ?
    ", [$catid, $name]);

    if ($existing) {
        return ['questionid' => $existing->id, 'qbeid' => $existing->qbeid];
    }

    $q = new stdClass();
    $q->category = $catid;
    $q->parent = 0;
    $q->name = $name;
    $q->questiontext = $text;
    $q->questiontextformat = FORMAT_HTML;
    $q->generalfeedback = '';
    $q->generalfeedbackformat = FORMAT_HTML;
    $q->defaultmark = 10.0;
    $q->penalty = 0.3333333;
    $q->qtype = 'multichoice';
    $q->length = 1;
    $q->stamp = make_unique_id_code();
    $q->timecreated = time();
    $q->timemodified = time();
    $q->createdby = $adminid;
    $q->modifiedby = $adminid;
    $qid = $DB->insert_record('question', $q);

    $qbe = new stdClass();
    $qbe->questioncategoryid = $catid;
    $qbe->idnumber = null;
    $qbe->ownerid = $adminid;
    $qbeid = $DB->insert_record('question_bank_entries', $qbe);

    $qv = new stdClass();
    $qv->questionbankentryid = $qbeid;
    $qv->version = 1;
    $qv->questionid = $qid;
    $qv->status = 'ready';
    $DB->insert_record('question_versions', $qv);

    $opt = new stdClass();
    $opt->questionid = $qid;
    $opt->layout = 0;
    $opt->single = 1;
    $opt->shuffleanswers = 1;
    $opt->correctfeedback = '<p>Jawaban Anda benar.</p>';
    $opt->correctfeedbackformat = FORMAT_HTML;
    $opt->partiallycorrectfeedback = '';
    $opt->partiallycorrectfeedbackformat = FORMAT_HTML;
    $opt->incorrectfeedback = '<p>Jawaban Anda salah.</p>';
    $opt->incorrectfeedbackformat = FORMAT_HTML;
    $opt->answernumbering = 'ABCD';
    $opt->shownumcorrect = 0;
    $opt->showstandardinstruction = 1;
    $DB->insert_record('qtype_multichoice_options', $opt);

    foreach ($answers as $ans) {
        $a = new stdClass();
        $a->question = $qid;
        $a->answer = $ans['text'];
        $a->answerformat = FORMAT_HTML;
        $a->fraction = $ans['fraction'];
        $a->feedback = !empty($ans['feedback']) ? $ans['feedback'] : '';
        $a->feedbackformat = FORMAT_HTML;
        $DB->insert_record('question_answers', $a);
    }

    return ['questionid' => $qid, 'qbeid' => $qbeid];
}

// 3. Question Bank Data
$questions_data = [
    1 => [
        'category' => 'Minggu 1: Remedial Sintaks & Output (10Q)',
        'quiz_name' => 'Kuis Remedial Minggu 1: Sintaks & Output JavaScript',
        'intro' => '<p>Kuis penguatan dan remedial materi Minggu 1 (Pengenalan, Sintaks Dasar, dan Output JavaScript). Kerjakan dengan teliti untuk memantapkan pemahaman fondasi pemrograman Anda!</p>',
        'questions' => [
            [
                'name' => 'W1-REM-01: Peran JavaScript di Web (C1)',
                'text' => '<p>Manakah fungsi utama dari JavaScript dalam ekosistem pengembangan halaman web?</p>',
                'answers' => [
                    ['text' => 'Memberikan interaktivitas dinamis dan logika pemrograman pada halaman web.', 'fraction' => 1.0, 'feedback' => 'Tepat! JavaScript bertindak sebagai otak yang memberikan interaktivitas dinamis.'],
                    ['text' => 'Mengatur tata letak visual, jenis font, dan warna pada elemen dokumen.', 'fraction' => 0.0, 'feedback' => 'Kurang tepat. Mengatur tampilan visual adalah tugas utama CSS.'],
                    ['text' => 'Menyediakan struktur kerangka dokumen dan konten dasar halaman web.', 'fraction' => 0.0, 'feedback' => 'Kurang tepat. Struktur dokumen adalah tugas utama HTML.'],
                    ['text' => 'Mengelola penyimpanan basis data relasional secara offline di sisi server.', 'fraction' => 0.0, 'feedback' => 'Salah. Database sisi server biasanya dikelola oleh DBMS seperti MySQL/PostgreSQL.']
                ]
            ],
            [
                'name' => 'W1-REM-02: Tag Penulisan Script (C1)',
                'text' => '<p>Tag HTML apa yang digunakan untuk menyisipkan kode JavaScript internal ke dalam dokumen HTML?</p>',
                'answers' => [
                    ['text' => '&lt;script&gt;', 'fraction' => 1.0, 'feedback' => 'Benar! Tag &lt;script&gt; digunakan untuk menampung kode JavaScript.'],
                    ['text' => '&lt;javascript&gt;', 'fraction' => 0.0, 'feedback' => 'Salah. Tag &lt;javascript&gt; tidak valid dalam standar HTML.'],
                    ['text' => '&lt;js&gt;', 'fraction' => 0.0, 'feedback' => 'Salah. Tidak ada tag &lt;js&gt; dalam HTML.'],
                    ['text' => '&lt;link rel="javascript"&gt;', 'fraction' => 0.0, 'feedback' => 'Salah. Tag &lt;link&gt; biasanya digunakan untuk menghubungkan file CSS.']
                ]
            ],
            [
                'name' => 'W1-REM-03: Menghubungkan File External JS (C2)',
                'text' => '<p>Sintaks HTML yang benar untuk memuat berkas JavaScript eksternal bernama <code>app.js</code> adalah ....</p>',
                'answers' => [
                    ['text' => '&lt;script src="app.js"&gt;&lt;/script&gt;', 'fraction' => 1.0, 'feedback' => 'Benar! Atribut src digunakan untuk memuat file eksternal.'],
                    ['text' => '&lt;script href="app.js"&gt;&lt;/script&gt;', 'fraction' => 0.0, 'feedback' => 'Salah. href digunakan pada tag &lt;link&gt; dan &lt;a&gt;, bukan &lt;script&gt;.'],
                    ['text' => '&lt;script file="app.js"&gt;&lt;/script&gt;', 'fraction' => 0.0, 'feedback' => 'Salah. Atribut file tidak dikenal dalam tag &lt;script&gt;.'],
                    ['text' => '&lt;link src="app.js"&gt;&lt;/link&gt;', 'fraction' => 0.0, 'feedback' => 'Salah. Tag link tidak menggunakan atribut src dan tidak ditutup &lt;/link&gt;.']
                ]
            ],
            [
                'name' => 'W1-REM-04: Output ke Console Browser (C1)',
                'text' => '<p>Perintah JavaScript standar yang paling sering digunakan oleh programmer untuk mencetak pesan informasi atau proses debugging ke konsol Web Developer Tools adalah ....</p>',
                'answers' => [
                    ['text' => 'console.log("Halo Dunia");', 'fraction' => 1.0, 'feedback' => 'Tepat! console.log() adalah perintah standar mencetak ke konsol.'],
                    ['text' => 'print("Halo Dunia");', 'fraction' => 0.0, 'feedback' => 'Salah. Di browser, window.print() akan membuka dialog pencetakan dokumen ke printer.'],
                    ['text' => 'echo "Halo Dunia";', 'fraction' => 0.0, 'feedback' => 'Salah. echo adalah perintah bahasa PHP/Bash, bukan JavaScript.'],
                    ['text' => 'system.out.println("Halo Dunia");', 'fraction' => 0.0, 'feedback' => 'Salah. Itu adalah sintaks bahasa Java.']
                ]
            ],
            [
                'name' => 'W1-REM-05: Kotak Dialog Alert (C2)',
                'text' => '<p>Perintah JavaScript yang digunakan untuk memunculkan kotak dialog popup pesan interaktif sederhana yang memerlukan pengguna menekan tombol OK adalah ....</p>',
                'answers' => [
                    ['text' => 'alert("Peringatan!");', 'fraction' => 1.0, 'feedback' => 'Benar! alert() menampilkan dialog modal peringatan sederhana.'],
                    ['text' => 'popup("Peringatan!");', 'fraction' => 0.0, 'feedback' => 'Salah. Tidak ada fungsi bawaan bernama popup() di JavaScript.'],
                    ['text' => 'dialog("Peringatan!");', 'fraction' => 0.0, 'feedback' => 'Salah. dialog() bukan fungsi bawaan JavaScript standar.'],
                    ['text' => 'msgBox("Peringatan!");', 'fraction' => 0.0, 'feedback' => 'Salah. MsgBox adalah fungsi di VBScript/VBA, bukan JavaScript.']
                ]
            ],
            [
                'name' => 'W1-REM-06: Sifat Case-Sensitivity (C2)',
                'text' => '<p>JavaScript bersifat <em>case-sensitive</em>. Jika Anda mendeklarasikan <code>let nilai = 80;</code>, bagaimanakah cara memanggil variabel tersebut dengan benar?</p>',
                'answers' => [
                    ['text' => 'Menggunakan nama "nilai" persis dengan huruf kecil semua.', 'fraction' => 1.0, 'feedback' => 'Tepat! Karena huruf kapital dan kecil dianggap variabel yang sama sekali berbeda.'],
                    ['text' => 'Bebas menggunakan "Nilai" atau "NILAI" karena kapitalisasi diabaikan.', 'fraction' => 0.0, 'feedback' => 'Salah. JavaScript membedakan huruf besar dan kecil secara ketat.'],
                    ['text' => 'Wajib menggunakan "NILAI" dengan huruf kapital semua.', 'fraction' => 0.0, 'feedback' => 'Salah. Nama variabel harus sesuai dengan cara dideklarasikannya.'],
                    ['text' => 'Bisa menggunakan "$nilai" secara otomatis tanpa deklarasi ulang.', 'fraction' => 0.0, 'feedback' => 'Salah. Tanda $ di depan mengubah nama identifier.']
                ]
            ],
            [
                'name' => 'W1-REM-07: Komentar Satu Baris (C1)',
                'text' => '<p>Simbol yang digunakan untuk membuat komentar satu baris (single-line comment) di JavaScript adalah ....</p>',
                'answers' => [
                    ['text' => '//', 'fraction' => 1.0, 'feedback' => 'Benar! Dua garis miring // digunakan untuk komentar satu baris.'],
                    ['text' => '&lt;!-- --&gt;', 'fraction' => 0.0, 'feedback' => 'Salah. Itu adalah sintaks komentar di file HTML.'],
                    ['text' => '#', 'fraction' => 0.0, 'feedback' => 'Salah. Tanda pagar # digunakan di Python atau Bash.'],
                    ['text' => '--', 'fraction' => 0.0, 'feedback' => 'Salah. Dua strip -- digunakan untuk komentar di bahasa SQL.']
                ]
            ],
            [
                'name' => 'W1-REM-08: Komentar Multi-Baris (C1)',
                'text' => '<p>Simbol yang digunakan untuk membuat komentar yang mencakup lebih dari satu baris (multi-line comment) di JavaScript adalah ....</p>',
                'answers' => [
                    ['text' => '/* komentar */', 'fraction' => 1.0, 'feedback' => 'Benar! Diawali dengan /* dan diakhiri dengan */.'],
                    ['text' => '&lt;!-- komentar --&gt;', 'fraction' => 0.0, 'feedback' => 'Salah. Itu komentar HTML.'],
                    ['text' => '{{ komentar }}', 'fraction' => 0.0, 'feedback' => 'Salah. Itu sintaks templating (seperti Mustache/Blade).'],
                    ['text' => '// komentar //', 'fraction' => 0.0, 'feedback' => 'Salah. Garis miring ganda hanya berlaku untuk satu baris saja.']
                ]
            ],
            [
                'name' => 'W1-REM-09: Urutan Eksekusi Kode (C2)',
                'text' => '<p>Perhatikan baris kode berikut:<br><code>console.log("Apel");</code><br><code>console.log("Jeruk");</code><br><code>console.log("Mangga");</code><br>Bagaimanakah urutan eksekusi dan penampilan teks tersebut di konsol?</p>',
                'answers' => [
                    ['text' => 'Apel, Jeruk, Mangga (dieksekusi berurutan dari atas ke bawah).', 'fraction' => 1.0, 'feedback' => 'Benar! JavaScript mengeksekusi instruksi baris demi baris dari atas ke bawah.'],
                    ['text' => 'Mangga, Jeruk, Apel (dieksekusi terbalik dari bawah ke atas).', 'fraction' => 0.0, 'feedback' => 'Salah. Program membaca dari atas ke bawah secara sekuensial.'],
                    ['text' => 'Muncul secara acak tergantung kecepatan memori browser.', 'fraction' => 0.0, 'feedback' => 'Salah. Eksekusi kode sinkron selalu deterministik dan urut.'],
                    ['text' => 'Hanya "Apel" yang tampil karena instruksi berikutnya dibatalkan.', 'fraction' => 0.0, 'feedback' => 'Salah. Semua baris instruksi akan dijalankan.']
                ]
            ],
            [
                'name' => 'W1-REM-10: Output Langsung ke Dokumen (C2)',
                'text' => '<p>Perintah yang menuliskan teks langsung ke badan dokumen HTML saat halaman sedang dimuat (parsing) adalah ....</p>',
                'answers' => [
                    ['text' => 'document.write(...)', 'fraction' => 1.0, 'feedback' => 'Benar! document.write() mencetak langsung ke aliran dokumen HTML.'],
                    ['text' => 'document.print(...)', 'fraction' => 0.0, 'feedback' => 'Salah. Fungsi document.print tidak ada di DOM standard.'],
                    ['text' => 'window.insertText(...)', 'fraction' => 0.0, 'feedback' => 'Salah. Tidak ada fungsi tersebut di objek window.'],
                    ['text' => 'body.appendText(...)', 'fraction' => 0.0, 'feedback' => 'Salah. Metode manipulasi DOM modern adalah element.append() atau appendChild().']
                ]
            ]
        ]
    ],
    2 => [
        'category' => 'Minggu 2: Remedial Variabel & Tipe Data (10Q)',
        'quiz_name' => 'Kuis Remedial Minggu 2: Variabel & Tipe Data Dasar',
        'intro' => '<p>Kuis penguatan dan remedial materi Minggu 2 (let, const, var, dan tipe data dasar). Kerjakan dengan teliti untuk memantapkan pemahaman memori dan tipe data di JavaScript!</p>',
        'questions' => [
            [
                'name' => 'W2-REM-01: Keyword const (C1)',
                'text' => '<p>Kata kunci JavaScript modern yang digunakan untuk mendeklarasikan variabel yang nilainya konstan (tidak boleh di-assign ulang) adalah ....</p>',
                'answers' => [
                    ['text' => 'const', 'fraction' => 1.0, 'feedback' => 'Benar! const digunakan untuk nilai yang tidak akan di-reassign.'],
                    ['text' => 'let', 'fraction' => 0.0, 'feedback' => 'Salah. let nilainya dapat di-assign ulang.'],
                    ['text' => 'var', 'fraction' => 0.0, 'feedback' => 'Salah. var nilainya dapat diubah sewaktu-waktu.'],
                    ['text' => 'static', 'fraction' => 0.0, 'feedback' => 'Salah. static digunakan pada class/method di OOP.']
                ]
            ],
            [
                'name' => 'W2-REM-02: Keunggulan let vs var (C2)',
                'text' => '<p>Manakah keunggulan utama penggunaan <code>let</code> dibandingkan <code>var</code> dalam penulisan kode JavaScript modern?</p>',
                'answers' => [
                    ['text' => 'let memiliki block scope ({}) sehingga mencegah variabel bocor ke luar blok.', 'fraction' => 1.0, 'feedback' => 'Tepat! let terikat pada scope blok kurung kurawal tempat ia dideklarasikan.'],
                    ['text' => 'let hanya dapat digunakan untuk menyimpan data angka bulat.', 'fraction' => 0.0, 'feedback' => 'Salah. let dapat menyimpan semua tipe data.'],
                    ['text' => 'let tidak memerlukan alokasi memori di browser.', 'fraction' => 0.0, 'feedback' => 'Salah. Semua variabel membutuhkan alokasi memori.'],
                    ['text' => 'let otomatis mengubah semua nilai variabel menjadi huruf besar.', 'fraction' => 0.0, 'feedback' => 'Salah. let tidak mengubah format nilai.']
                ]
            ],
            [
                'name' => 'W2-REM-03: Reassigning Nilai const (C3)',
                'text' => '<p>Perhatikan potongan kode berikut:<br><code>const skorMaksimal = 100;</code><br><code>skorMaksimal = 120;</code><br>Apa yang akan terjadi ketika kode tersebut dijalankan?</p>',
                'answers' => [
                    ['text' => 'Terjadi TypeError karena variabel const tidak boleh di-assign ulang.', 'fraction' => 1.0, 'feedback' => 'Benar! JavaScript melempar TypeError: Assignment to constant variable.'],
                    ['text' => 'skorMaksimal berhasil diperbarui nilainya menjadi 120.', 'fraction' => 0.0, 'feedback' => 'Salah. const melindungi nilai variabel dari re-assignment.'],
                    ['text' => 'Nilai skorMaksimal berubah menjadi nilai rata-rata yaitu 110.', 'fraction' => 0.0, 'feedback' => 'Salah. Tidak ada perhitungan otomatis semacam itu.'],
                    ['text' => 'Browser secara otomatis mengabaikan baris kedua tanpa error.', 'fraction' => 0.0, 'feedback' => 'Salah. Error akan dilempar dan eksekusi skrip terhenti.']
                ]
            ],
            [
                'name' => 'W2-REM-04: Operator typeof (C1)',
                'text' => '<p>Operator yang digunakan di JavaScript untuk memeriksa dan mengetahui tipe data dari sebuah nilai atau variabel adalah ....</p>',
                'answers' => [
                    ['text' => 'typeof', 'fraction' => 1.0, 'feedback' => 'Benar! typeof mengembalikan tipe data dalam bentuk string (misal: "string", "number").'],
                    ['text' => 'typeOf()', 'fraction' => 0.0, 'feedback' => 'Salah. typeof adalah operator unary, bukan fungsi berkepala kapital.'],
                    ['text' => 'checkType', 'fraction' => 0.0, 'feedback' => 'Salah. checkType bukan kata kunci bawaan JavaScript.'],
                    ['text' => 'isType', 'fraction' => 0.0, 'feedback' => 'Salah. Tidak ada operator isType di JavaScript.']
                ]
            ],
            [
                'name' => 'W2-REM-05: Tipe Data String (C2)',
                'text' => '<p>Nilai yang dituliskan di antara tanda kutip tunggal (\'...\'), kutip ganda ("..."), atau tanda backtick (`...`), seperti <code>"JavaScript 2026"</code>, memiliki tipe data ....</p>',
                'answers' => [
                    ['text' => 'string', 'fraction' => 1.0, 'feedback' => 'Benar! Teks diapit tanda petik selalu bertipe string.'],
                    ['text' => 'number', 'fraction' => 0.0, 'feedback' => 'Salah. Tipe number ditulis tanpa tanda kutip.'],
                    ['text' => 'boolean', 'fraction' => 0.0, 'feedback' => 'Salah. boolean bernilai true atau false tanpa petik.'],
                    ['text' => 'object', 'fraction' => 0.0, 'feedback' => 'Salah. Teks literal langsung adalah tipe data primitif string.']
                ]
            ],
            [
                'name' => 'W2-REM-06: Tipe Data Boolean (C1)',
                'text' => '<p>Tipe data logika yang hanya dapat menampung dua kemungkinan nilai, yaitu <code>true</code> atau <code>false</code>, disebut ....</p>',
                'answers' => [
                    ['text' => 'boolean', 'fraction' => 1.0, 'feedback' => 'Benar! Boolean mewakili kebenaran logika (true/false).'],
                    ['text' => 'binary', 'fraction' => 0.0, 'feedback' => 'Salah. Binary adalah sistem bilangan basis dua, bukan nama tipe data JS.'],
                    ['text' => 'logic', 'fraction' => 0.0, 'feedback' => 'Salah. Nama tipe data resminya adalah boolean.'],
                    ['text' => 'condition', 'fraction' => 0.0, 'feedback' => 'Salah. Condition adalah istilah konsep logika, bukan tipe data.']
                ]
            ],
            [
                'name' => 'W2-REM-07: Nilai Default undefined (C2)',
                'text' => '<p>Jika seorang siswa mendeklarasikan variabel dengan kode <code>let namaSiswa;</code> tanpa memberikan nilai apa pun, apakah nilai dan tipe data awal dari variabel tersebut?</p>',
                'answers' => [
                    ['text' => 'undefined', 'fraction' => 1.0, 'feedback' => 'Benar! Variabel yang dideklarasikan tanpa inisialisasi bernilai undefined.'],
                    ['text' => 'null', 'fraction' => 0.0, 'feedback' => 'Salah. null harus diberikan secara sengaja/eksplisit oleh programmer.'],
                    ['text' => '0', 'fraction' => 0.0, 'feedback' => 'Salah. JavaScript tidak menginisialisasi angka nol secara otomatis.'],
                    ['text' => '"" (string kosong)', 'fraction' => 0.0, 'feedback' => 'Salah. String kosong adalah string berkarakter nol, bukan undefined.']
                ]
            ],
            [
                'name' => 'W2-REM-08: Makna Nilai null (C2)',
                'text' => '<p>Nilai <code>null</code> dalam JavaScript paling tepat didefinisikan sebagai ....</p>',
                'answers' => [
                    ['text' => 'Nilai khusus yang sengaja diberikan untuk menunjukkan bahwa variabel tersebut bernilai kosong / tidak memiliki referensi.', 'fraction' => 1.0, 'feedback' => 'Tepat! null adalah representasi eksplisit dari ketiadaan nilai.'],
                    ['text' => 'Pesan kegagalan error dari compiler bahwa kode mengalami crash.', 'fraction' => 0.0, 'feedback' => 'Salah. null adalah nilai yang sah, bukan error.'],
                    ['text' => 'Angka nol pada tipe data number.', 'fraction' => 0.0, 'feedback' => 'Salah. 0 adalah number, sedangkan null adalah tipe primitif khusus.'],
                    ['text' => 'Teks string yang bertuliskan kata "null".', 'fraction' => 0.0, 'feedback' => 'Salah. Jika berupa string, penulisannya menggunakan tanda kutip "null".']
                ]
            ],
            [
                'name' => 'W2-REM-09: Aturan Penamaan Variabel (C2)',
                'text' => '<p>Manakah nama variabel berikut yang VALID menurut aturan sintaks penamaan identifier di JavaScript?</p>',
                'answers' => [
                    ['text' => 'totalHarga', 'fraction' => 1.0, 'feedback' => 'Benar! Diawali huruf dan mengikuti konvensi camelCase yang valid.'],
                    ['text' => '1totalHarga', 'fraction' => 0.0, 'feedback' => 'Salah. Nama variabel tidak boleh diawali dengan angka.'],
                    ['text' => 'total-harga', 'fraction' => 0.0, 'feedback' => 'Salah. Karakter tanda hubung (-) dianggap sebagai operator pengurangan.'],
                    ['text' => 'let', 'fraction' => 0.0, 'feedback' => 'Salah. let adalah reserved keyword bahasa JavaScript.']
                ]
            ],
            [
                'name' => 'W2-REM-10: Template Literals (C2)',
                'text' => '<p>Fitur penulisan string modern menggunakan tanda kutip backtick (<code>`</code>) yang memungkinkan interpolasi variabel dengan sintaks <code>${nama}</code> disebut ....</p>',
                'answers' => [
                    ['text' => 'Template Literals', 'fraction' => 1.0, 'feedback' => 'Benar! Template Literals memudahkan penggabungan teks dan ekspresi variabel.'],
                    ['text' => 'String Concatenator', 'fraction' => 0.0, 'feedback' => 'Salah. Concatenation biasanya merujuk pada operator +.'],
                    ['text' => 'Variable Embedder', 'fraction' => 0.0, 'feedback' => 'Salah. Istilah teknis resminya adalah Template Literals.'],
                    ['text' => 'Dynamic Text Format', 'fraction' => 0.0, 'feedback' => 'Salah. Bukan nama fitur resmi di JavaScript.']
                ]
            ]
        ]
    ],
    3 => [
        'category' => 'Minggu 3: Remedial Operator & Konversi (10Q)',
        'quiz_name' => 'Kuis Remedial Minggu 3: Operator & Konversi Tipe Data',
        'intro' => '<p>Kuis penguatan dan remedial materi Minggu 3 (Operator Aritmatika, Perbandingan, Input prompt, dan Konversi Tipe Data). Kerjakan dengan teliti!</p>',
        'questions' => [
            [
                'name' => 'W3-REM-01: Operator Modulus (C2)',
                'text' => '<p>Operator yang digunakan untuk menghasilkan sisa hasil bagi dari pembagian dua bilangan bulat di JavaScript adalah ....</p>',
                'answers' => [
                    ['text' => '% (Modulus)', 'fraction' => 1.0, 'feedback' => 'Benar! Misal 10 % 3 menghasilkan sisa 1.'],
                    ['text' => '/ (Pembagian)', 'fraction' => 0.0, 'feedback' => 'Salah. Simbol / menghasilkan hasil bagi pembagian riil.'],
                    ['text' => '^ (XOR / Eksponen)', 'fraction' => 0.0, 'feedback' => 'Salah. Simbol ^ di JS adalah operator Bitwise XOR.'],
                    ['text' => '// (Div)', 'fraction' => 0.0, 'feedback' => 'Salah. // adalah simbol penulisan komentar.']
                ]
            ],
            [
                'name' => 'W3-REM-02: Perbedaan === vs == (C2)',
                'text' => '<p>Apa perbedaan mendasar antara operator kesetaraan ketat (<code>===</code>) dan kesetaraan longgar (<code>==</code>)?</p>',
                'answers' => [
                    ['text' => '=== membandingkan nilai DAN tipe data tanpa konversi paksa.', 'fraction' => 1.0, 'feedback' => 'Tepat! === memeriksa nilai dan tipe data sekaligus (strict equality).'],
                    ['text' => '== lebih ketat karena mengubah semua nilai menjadi teks sebelum diperiksa.', 'fraction' => 0.0, 'feedback' => 'Salah. == melakukan type coercion sehingga justru lebih longgar.'],
                    ['text' => '=== hanya dapat digunakan untuk data angka saja.', 'fraction' => 0.0, 'feedback' => 'Salah. === dapat digunakan untuk semua tipe data.'],
                    ['text' => 'Kedua operator tersebut berfungsi identik tanpa perbedaan sama sekali.', 'fraction' => 0.0, 'feedback' => 'Salah. Memahami perbedaannya sangat krusial dalam JavaScript.']
                ]
            ],
            [
                'name' => 'W3-REM-03: Evaluasi Kesetaraan 5 == "5" vs 5 === "5" (C3)',
                'text' => '<p>Apakah hasil evaluasi dari dua baris ekspresi berikut secara berurutan?<br><code>5 == "5"</code><br><code>5 === "5"</code></p>',
                'answers' => [
                    ['text' => 'true dan false', 'fraction' => 1.0, 'feedback' => 'Benar! == menghasilkan true (karena nilai sama setelah coercion), sedangkan === false (tipe number !== string).'],
                    ['text' => 'false dan true', 'fraction' => 0.0, 'feedback' => 'Salah. Terbalik.'],
                    ['text' => 'true dan true', 'fraction' => 0.0, 'feedback' => 'Salah. === akan menghasilkan false karena tipenya berbeda.'],
                    ['text' => 'false dan false', 'fraction' => 0.0, 'feedback' => 'Salah. == akan menghasilkan true.']
                ]
            ],
            [
                'name' => 'W3-REM-04: Sifat Nilai Kembalian prompt() (C2)',
                'text' => '<p>Fungsi browser <code>prompt("Berapa usia Anda?");</code> akan selalu mengembalikan nilai input dari pengguna dalam bentuk tipe data ....</p>',
                'answers' => [
                    ['text' => 'string', 'fraction' => 1.0, 'feedback' => 'Benar! Meskipun pengguna mengetikkan angka 17, prompt() selalu mengembalikan "17" (string).'],
                    ['text' => 'number', 'fraction' => 0.0, 'feedback' => 'Salah. Angka yang diketik pengguna tetap dibungkus sebagai string.'],
                    ['text' => 'boolean', 'fraction' => 0.0, 'feedback' => 'Salah. prompt() tidak menghasilkan boolean.'],
                    ['text' => 'array', 'fraction' => 0.0, 'feedback' => 'Salah. prompt() mengembalikan string tunggal.']
                ]
            ],
            [
                'name' => 'W3-REM-05: Konversi String ke Number (C2)',
                'text' => '<p>Jika variabel <code>let usia = "20";</code> bertipe string, fungsi bawaan apa yang dapat digunakan untuk mengubahnya menjadi tipe data number murni?</p>',
                'answers' => [
                    ['text' => 'Number(usia) atau parseInt(usia)', 'fraction' => 1.0, 'feedback' => 'Benar! Number() dan parseInt() digunakan untuk konversi ke angka.'],
                    ['text' => 'String(usia)', 'fraction' => 0.0, 'feedback' => 'Salah. String() justru mengubah nilai menjadi string.'],
                    ['text' => 'toInteger(usia)', 'fraction' => 0.0, 'feedback' => 'Salah. toInteger bukan fungsi global bawaan JS.'],
                    ['text' => 'Math.convert(usia)', 'fraction' => 0.0, 'feedback' => 'Salah. Objek Math tidak memiliki metode convert.']
                ]
            ],
            [
                'name' => 'W3-REM-06: Operator + dengan String dan Angka (C3)',
                'text' => '<p>Perhatikan operasi berikut:<br><code>let hasil = "10" + 5;</code><br>Berapakah nilai dari variabel <code>hasil</code> dan apa tipe datanya?</p>',
                'answers' => [
                    ['text' => '"105" bertipe string (terjadi penggabungan teks / string concatenation).', 'fraction' => 1.0, 'feedback' => 'Benar! Jika ada salah satu operand bertipe string, operator + melakukan konkatenasi teks.'],
                    ['text' => '15 bertipe number (terjadi penjumlahan aritmatika).', 'fraction' => 0.0, 'feedback' => 'Salah. Tanda + tidak otomatis mengonversi string ke angka jika posisinya sebagai operand.'],
                    ['text' => '50 bertipe number.', 'fraction' => 0.0, 'feedback' => 'Salah. Tidak terjadi perkalian.'],
                    ['text' => 'NaN (Not a Number).', 'fraction' => 0.0, 'feedback' => 'Salah. NaN terjadi pada operasi matematis lain seperti "10" * "halo".']
                ]
            ],
            [
                'name' => 'W3-REM-07: Operator Logika AND (&&) (C2)',
                'text' => '<p>Kapan ekspresi logika menggunakan operator <code>&&</code> (AND), misalnya <code>kondisiA && kondisiB</code>, akan menghasilkan nilai <code>true</code>?</p>',
                'answers' => [
                    ['text' => 'Hanya jika kedua kondisi (kondisiA dan kondisiB) bernilai true.', 'fraction' => 1.0, 'feedback' => 'Benar! Logika AND mensyaratkan kedua operand bernilai benar.'],
                    ['text' => 'Jika salah satu dari kondisiA atau kondisiB bernilai true.', 'fraction' => 0.0, 'feedback' => 'Salah. Itu adalah aturan untuk operator OR (||).'],
                    ['text' => 'Ketika kedua kondisi bernilai false.', 'fraction' => 0.0, 'feedback' => 'Salah. Jika keduanya false, hasilnya false.'],
                    ['text' => 'Ketika kondisiA bernilai true dan kondisiB bernilai false.', 'fraction' => 0.0, 'feedback' => 'Salah. Jika salah satu false, hasil akhir AND adalah false.']
                ]
            ],
            [
                'name' => 'W3-REM-08: Operator Logika OR (||) (C2)',
                'text' => '<p>Kapan ekspresi logika menggunakan operator <code>||</code> (OR), misalnya <code>kondisiA || kondisiB</code>, akan menghasilkan nilai <code>true</code>?</p>',
                'answers' => [
                    ['text' => 'Jika minimal salah satu dari kondisi bernilai true.', 'fraction' => 1.0, 'feedback' => 'Benar! Logika OR cukup membutuhkan minimal 1 kondisi benar untuk menghasilkan true.'],
                    ['text' => 'Hanya jika kedua kondisi bernilai false secara bersamaan.', 'fraction' => 0.0, 'feedback' => 'Salah. Jika keduanya false, hasilnya false.'],
                    ['text' => 'Hanya jika kedua kondisi bertipe number.', 'fraction' => 0.0, 'feedback' => 'Salah. Tipe data tidak membatasi evaluasi boolean.'],
                    ['text' => 'Hanya jika kondisi pertama bernilai false.', 'fraction' => 0.0, 'feedback' => 'Salah. Jika kondisi pertama true, OR langsung mengembalikan true.']
                ]
            ],
            [
                'name' => 'W3-REM-09: Operator Logika NOT (!) (C2)',
                'text' => '<p>Jika nilai <code>let sudahMakan = false;</code>, berapakah hasil evaluasi dari ekspresi <code>!sudahMakan</code>?</p>',
                'answers' => [
                    ['text' => 'true', 'fraction' => 1.0, 'feedback' => 'Benar! Tanda ! (NOT) membalikkan nilai boolean false menjadi true.'],
                    ['text' => 'false', 'fraction' => 0.0, 'feedback' => 'Salah. Operator ! membalikkan nilai aslinya.'],
                    ['text' => 'null', 'fraction' => 0.0, 'feedback' => 'Salah. Operator logika menghasilkan nilai boolean.'],
                    ['text' => 'undefined', 'fraction' => 0.0, 'feedback' => 'Salah. Hasilnya tetap boolean murni.']
                ]
            ],
            [
                'name' => 'W3-REM-10: Nilai Falsy dalam JavaScript (C2)',
                'text' => '<p>Manakah kelompok nilai di bawah ini yang seluruhnya dianggap sebagai nilai <em>falsy</em> (dievaluasi sebagai false saat dimasukkan dalam pengkondisian logika) di JavaScript?</p>',
                'answers' => [
                    ['text' => '0, "" (string kosong), null, undefined, dan NaN', 'fraction' => 1.0, 'feedback' => 'Tepat! Ini adalah nilai-nilai falsy standar di JavaScript.'],
                    ['text' => '"0", "false", dan array kosong []', 'fraction' => 0.0, 'feedback' => 'Salah. String "0" dan array [] bernilai truthy di JavaScript.'],
                    ['text' => '1, "Halo", dan spasi " "', 'fraction' => 0.0, 'feedback' => 'Salah. Seluruhnya adalah nilai truthy.'],
                    ['text' => 'true, 100, dan objek {}', 'fraction' => 0.0, 'feedback' => 'Salah. Seluruhnya adalah nilai truthy.']
                ]
            ]
        ]
    ],
    4 => [
        'category' => 'Minggu 4: Remedial Percabangan (10Q)',
        'quiz_name' => 'Kuis Remedial Minggu 4: Struktur Kontrol Percabangan',
        'intro' => '<p>Kuis penguatan dan remedial materi Minggu 4 (Percabangan if, else if, else, dan switch-case). Kerjakan dengan teliti untuk menguasai logika alur keputusan!</p>',
        'questions' => [
            [
                'name' => 'W4-REM-01: Sintaks Pernyataan if (C1)',
                'text' => '<p>Manakah sintaks penulisan struktur percabangan <code>if</code> yang benar dan sesuai aturan bahasa JavaScript?</p>',
                'answers' => [
                    ['text' => 'if (skor &gt;= 70) { console.log("Lulus"); }', 'fraction' => 1.0, 'feedback' => 'Benar! Kondisi wajib berada di dalam tanda kurung biasa ().'],
                    ['text' => 'if skor &gt;= 70 then { console.log("Lulus"); }', 'fraction' => 0.0, 'feedback' => 'Salah. JavaScript tidak menggunakan kata kunci then.'],
                    ['text' => 'if [skor &gt;= 70] { console.log("Lulus"); }', 'fraction' => 0.0, 'feedback' => 'Salah. Kurung siku [] digunakan untuk array, bukan kondisi if.'],
                    ['text' => 'check if (skor &gt;= 70) -&gt; { console.log("Lulus"); }', 'fraction' => 0.0, 'feedback' => 'Salah. Bukan sintaks JavaScript yang valid.']
                ]
            ],
            [
                'name' => 'W4-REM-02: Peran Blok else (C2)',
                'text' => '<p>Dalam struktur <code>if - else</code>, kapan baris kode yang berada di dalam blok <code>else { ... }</code> akan dieksekusi?</p>',
                'answers' => [
                    ['text' => 'Ketika kondisi pada blok if menghasilkan nilai false (tidak terpenuhi).', 'fraction' => 1.0, 'feedback' => 'Tepat! else adalah jalur alternatif ketika kondisi if tidak terpenuhi.'],
                    ['text' => 'Ketika kondisi pada blok if bernilai true.', 'fraction' => 0.0, 'feedback' => 'Salah. Jika if bernilai true, maka blok if yang dieksekusi dan else dilewati.'],
                    ['text' => 'Selalu dieksekusi bersamaan setelah blok if selesai berjalan.', 'fraction' => 0.0, 'feedback' => 'Salah. Blok if dan else bersifat mutually exclusive (hanya salah satu yang berjalan).'],
                    ['text' => 'Hanya ketika terjadi syntax error pada program.', 'fraction' => 0.0, 'feedback' => 'Salah. Error penanganan menggunakan try-catch, bukan else.']
                ]
            ],
            [
                'name' => 'W4-REM-03: Evaluasi Percabangan Bertingkat else if (C3)',
                'text' => '<p>Perhatikan potongan kode berikut:<br><code>let nilai = 85;</code><br><code>if (nilai &gt;= 90) { console.log("A"); }</code><br><code>else if (nilai &gt;= 80) { console.log("B"); }</code><br><code>else { console.log("C"); }</code><br>Apakah huruf yang akan tercetak di konsol?</p>',
                'answers' => [
                    ['text' => 'B', 'fraction' => 1.0, 'feedback' => 'Benar! 85 tidak memenuhi &gt;= 90, tetapi memenuhi kondisi kedua &gt;= 80.'],
                    ['text' => 'A', 'fraction' => 0.0, 'feedback' => 'Salah. Nilai 85 kurang dari 90.'],
                    ['text' => 'C', 'fraction' => 0.0, 'feedback' => 'Salah. Karena kondisi kedua sudah terpenuhi, blok else diabaikan.'],
                    ['text' => 'B dan C', 'fraction' => 0.0, 'feedback' => 'Salah. Hanya satu cabang yang akan dieksekusi.']
                ]
            ],
            [
                'name' => 'W4-REM-04: Kapan Menggunakan switch-case (C2)',
                'text' => '<p>Pernyataan percabangan <code>switch - case</code> paling tepat digunakan ketika ....</p>',
                'answers' => [
                    ['text' => 'Ingin mencocokkan satu variabel dengan banyak kemungkinan nilai pasti / diskrit (misal: menu 1, 2, 3 atau nama hari).', 'fraction' => 1.0, 'feedback' => 'Benar! switch sangat rapi dan optimal untuk pengujian nilai tunggal yang diskrit.'],
                    ['text' => 'Ingin menguji rentang nilai angka yang sangat rumit dan acak (misal: &gt; 10 dan &lt; 25).', 'fraction' => 0.0, 'feedback' => 'Salah. Pengujian rentang lebih cocok menggunakan if - else if.'],
                    ['text' => 'Ingin melakukan perulangan kode sebanyak 100 kali.', 'fraction' => 0.0, 'feedback' => 'Salah. Perulangan menggunakan for atau while.'],
                    ['text' => 'Ingin menghapus variabel dari memori browser.', 'fraction' => 0.0, 'feedback' => 'Salah. switch adalah struktur percabangan alur logika.']
                ]
            ],
            [
                'name' => 'W4-REM-05: Peran Keyword break pada switch (C2)',
                'text' => '<p>Apa yang akan terjadi jika kita tidak menyertakan keyword <code>break;</code> di akhir sebuah blok <code>case</code> pada pernyataan <code>switch</code>?</p>',
                'answers' => [
                    ['text' => 'Program akan mengalami fall-through (mengeksekusi case di bawahnya secara otomatis meskipun nilainya tidak cocok).', 'fraction' => 1.0, 'feedback' => 'Tepat! Tanpa break, eksekusi akan terus "jatuh" ke case berikutnya.'],
                    ['text' => 'Program akan langsung memunculkan fatal error dan crash.', 'fraction' => 0.0, 'feedback' => 'Salah. Fall-through adalah fitur yang valid di JS, namun sering memicu bug logika jika tidak disengaja.'],
                    ['text' => 'Nilai variabel pembanding otomatis direset menjadi null.', 'fraction' => 0.0, 'feedback' => 'Salah. Nilai variabel tidak terpengaruh.'],
                    ['text' => 'Browser akan langsung menutup halaman web.', 'fraction' => 0.0, 'feedback' => 'Salah. Browser tetap berjalan normal.']
                ]
            ],
            [
                'name' => 'W4-REM-06: Klausa default pada switch (C1)',
                'text' => '<p>Bagian apa yang berfungsi sebagai penampung aksi terakhir pada struktur <code>switch</code> jika tidak ada satu pun <code>case</code> yang cocok?</p>',
                'answers' => [
                    ['text' => 'default:', 'fraction' => 1.0, 'feedback' => 'Benar! default: bertindak serupa dengan blok else pada percabangan if.'],
                    ['text' => 'else:', 'fraction' => 0.0, 'feedback' => 'Salah. Pada switch digunakan kata kunci default, bukan else.'],
                    ['text' => 'catch:', 'fraction' => 0.0, 'feedback' => 'Salah. catch digunakan pada penanganan error try-catch.'],
                    ['text' => 'finally:', 'fraction' => 0.0, 'feedback' => 'Salah. finally digunakan pada blok try-catch-finally.']
                ]
            ],
            [
                'name' => 'W4-REM-07: Operator Ternary (C2)',
                'text' => '<p>Perhatikan baris kode ternary operator berikut:<br><code>let status = (usia &gt;= 17) ? "Dewasa" : "Anak-anak";</code><br>Jika nilai <code>let usia = 15;</code>, apakah isi dari variabel <code>status</code>?</p>',
                'answers' => [
                    ['text' => '"Anak-anak"', 'fraction' => 1.0, 'feedback' => 'Benar! Karena (15 &gt;= 17) bernilai false, ekspresi setelah titik dua (:) yang dipilih.'],
                    ['text' => '"Dewasa"', 'fraction' => 0.0, 'feedback' => 'Salah. "Dewasa" hanya dipilih jika kondisinya bernilai true.'],
                    ['text' => 'true', 'fraction' => 0.0, 'feedback' => 'Salah. Operator ternary mengembalikan nilai teks hasil ekspresi.'],
                    ['text' => 'undefined', 'fraction' => 0.0, 'feedback' => 'Salah. Kedua alternatif nilai telah ditentukan.']
                ]
            ],
            [
                'name' => 'W4-REM-08: Sifat Mutually Exclusive if-else (C2)',
                'text' => '<p>Dalam sebuah rantai pengkondisian <code>if - else if - else if - else</code>, berapa banyak blok kode yang dapat dieksekusi secara maksimal?</p>',
                'answers' => [
                    ['text' => 'Maksimal 1 blok kode (blok pertama yang kondisinya bernilai true).', 'fraction' => 1.0, 'feedback' => 'Tepat! Begitu salah satu blok terpenuhi, seluruh percabangan berikutnya dilewati.'],
                    ['text' => 'Semua blok yang kondisinya bernilai true akan dieksekusi seluruhnya.', 'fraction' => 0.0, 'feedback' => 'Salah. Rantai else if hanya mengeksekusi satu blok yang pertama cocok.'],
                    ['text' => 'Minimal harus 2 blok kode.', 'fraction' => 0.0, 'feedback' => 'Salah. Hanya satu blok.'],
                    ['text' => 'Tergantung pada tipe browser yang digunakan.', 'fraction' => 0.0, 'feedback' => 'Salah. Ini adalah aturan dasar spesifikasi ECMAScript di semua browser.']
                ]
            ],
            [
                'name' => 'W4-REM-09: Kondisi if dengan Variabel Boolean (C3)',
                'text' => '<p>Perhatikan kode berikut:<br><code>let isMember = false;</code><br><code>if (isMember) { console.log("Diskon 20%"); } else { console.log("Harga Normal"); }</code><br>Pesan apakah yang akan dicetak?</p>',
                'answers' => [
                    ['text' => 'Harga Normal', 'fraction' => 1.0, 'feedback' => 'Benar! Karena isMember bernilai false, blok else yang dijalankan.'],
                    ['text' => 'Diskon 20%', 'fraction' => 0.0, 'feedback' => 'Salah. isMember bernilai false, sehingga blok if tidak jalan.'],
                    ['text' => 'Diskon 20% kemudian Harga Normal', 'fraction' => 0.0, 'feedback' => 'Salah. Blok if dan else tidak pernah berjalan bersamaan.'],
                    ['text' => 'Tidak menampilkan teks apa pun ke konsol.', 'fraction' => 0.0, 'feedback' => 'Salah. Blok else pasti dieksekusi.']
                ]
            ],
            [
                'name' => 'W4-REM-10: Percabangan Bersarang / Nested if (C2)',
                'text' => '<p>Apa yang dimaksud dengan <em>nested if</em> dalam pemrograman JavaScript?</p>',
                'answers' => [
                    ['text' => 'Struktur pernyataan if yang diletakkan di dalam blok if atau else lainnya.', 'fraction' => 1.0, 'feedback' => 'Benar! Nested if adalah percabangan bersarang untuk keputusan berlapis.'],
                    ['text' => 'Dua percabangan if yang digabungkan menggunakan tanda titik koma (;).', 'fraction' => 0.0, 'feedback' => 'Salah. Itu hanya dua pernyataan if berurutan, bukan bersarang.'],
                    ['text' => 'Pernyataan if yang ditulis tanpa menggunakan tanda kurung kurawal.', 'fraction' => 0.0, 'feedback' => 'Salah. Itu adalah style penulisan single-line.'],
                    ['text' => 'Struktur if yang dimasukkan ke dalam loop tak terhingga.', 'fraction' => 0.0, 'feedback' => 'Salah. Bersarang berarti blok berada di dalam blok sejenis lainnya.']
                ]
            ]
        ]
    ],
    5 => [
        'category' => 'Minggu 5: Remedial Perulangan (10Q)',
        'quiz_name' => 'Kuis Remedial Minggu 5: Struktur Kontrol Perulangan',
        'intro' => '<p>Kuis penguatan dan remedial materi Minggu 5 (Perulangan for, while, do-while, break, dan continue). Kerjakan dengan teliti untuk memantapkan pemahaman otomatisasi loop!</p>',
        'questions' => [
            [
                'name' => 'W5-REM-01: Tujuan Perulangan / Looping (C1)',
                'text' => '<p>Manakah fungsi utama dari pembuatan struktur perulangan (<em>looping</em>) dalam sebuah program komputer?</p>',
                'answers' => [
                    ['text' => 'Mengeksekusi blok kode yang sama secara berulang-ulang secara otomatis tanpa perlu menulis ulang instruksi.', 'fraction' => 1.0, 'feedback' => 'Tepat! Looping mengotomatiskan eksekusi berulang.'],
                    ['text' => 'Membuat kode program menjadi lebih panjang dan sulit dibaca.', 'fraction' => 0.0, 'feedback' => 'Salah. Justru looping membuat kode jauh lebih ringkas (prinsip DRY).'],
                    ['text' => 'Menyimpan berkas data secara permanen ke server database.', 'fraction' => 0.0, 'feedback' => 'Salah. Looping adalah struktur kontrol alur di memori kerja.'],
                    ['text' => 'Menghubungkan halaman web dengan jaringan internet eksternal.', 'fraction' => 0.0, 'feedback' => 'Salah. Itu tugas protokol komunikasi jaringan seperti Fetch/XHR.']
                ]
            ],
            [
                'name' => 'W5-REM-02: Anatomi Perulangan for (C2)',
                'text' => '<p>Struktur penulisan perulangan for adalah <code>for (Bagian 1; Bagian 2; Bagian 3) { ... }</code>. Bagian 1, 2, dan 3 secara berturut-turut adalah ....</p>',
                'answers' => [
                    ['text' => 'Inisialisasi nilai awal; Kondisi batas perulangan; Pengubahan nilai (increment/decrement).', 'fraction' => 1.0, 'feedback' => 'Benar! Contoh: for (let i = 0; i &lt; 5; i++).'],
                    ['text' => 'Kondisi perulangan; Inisialisasi awal; Penutupan blok program.', 'fraction' => 0.0, 'feedback' => 'Salah. Urutannya terbalik.'],
                    ['text' => 'Pengubahan nilai; Kondisi perulangan; Inisialisasi nilai awal.', 'fraction' => 0.0, 'feedback' => 'Salah. Inisialisasi selalu berada di urutan pertama.'],
                    ['text' => 'Nama fungsi; Parameter fungsi; Tipe nilai kembalian.', 'fraction' => 0.0, 'feedback' => 'Salah. Itu adalah struktur deklarasi fungsi, bukan perulangan for.']
                ]
            ],
            [
                'name' => 'W5-REM-03: Menghitung Iterasi for Loop (C3)',
                'text' => '<p>Perhatikan perulangan berikut:<br><code>for (let i = 1; i &lt;= 5; i++) { console.log("Belajar"); }</code><br>Berapa kalikah kata "Belajar" akan dicetak ke konsol?</p>',
                'answers' => [
                    ['text' => '5 kali (saat i bernilai 1, 2, 3, 4, dan 5).', 'fraction' => 1.0, 'feedback' => 'Benar! Mulai dari 1 hingga 5 (inklusif &lt;= 5) berjumlah tepat 5 kali.'],
                    ['text' => '4 kali', 'fraction' => 0.0, 'feedback' => 'Salah. Tanda &lt;= membuat nilai 5 ikut dieksekusi.'],
                    ['text' => '6 kali', 'fraction' => 0.0, 'feedback' => 'Salah. Pada saat i = 6, kondisi 6 &lt;= 5 bernilai false sehingga perulangan berhenti.'],
                    ['text' => 'Tak terhingga (infinite loop)', 'fraction' => 0.0, 'feedback' => 'Salah. Variabel i bertambah 1 setiap putaran hingga mencapai batas kondisi.']
                ]
            ],
            [
                'name' => 'W5-REM-04: Cara Kerja while Loop (C2)',
                'text' => '<p>Kapan perulangan <code>while (kondisi) { ... }</code> akan terus menjalankan blok instruksi di dalamnya?</p>',
                'answers' => [
                    ['text' => 'Selama kondisi yang diuji masih bernilai true.', 'fraction' => 1.0, 'feedback' => 'Benar! Begitu kondisi menjadi false, perulangan while langsung berhenti.'],
                    ['text' => 'Tepat satu kali saja tanpa memperdulikan nilai kondisi.', 'fraction' => 0.0, 'feedback' => 'Salah. while menguji kondisi terlebih dahulu sebelum putaran pertama dimulai.'],
                    ['text' => 'Ketika kondisi bernilai false.', 'fraction' => 0.0, 'feedback' => 'Salah. Kondisi false adalah pemicu penghentian perulangan.'],
                    ['text' => 'Hanya saat waktu timer di sistem sudah habis.', 'fraction' => 0.0, 'feedback' => 'Salah. while tidak terikat pada waktu jam sistem secara langsung.']
                ]
            ],
            [
                'name' => 'W5-REM-05: Ciri Khas do - while Loop (C2)',
                'text' => '<p>Apa keistimewaan utama dari perulangan <code>do - while</code> dibandingkan dengan <code>while</code> biasa?</p>',
                'answers' => [
                    ['text' => 'do - while pasti mengeksekusi blok instruksinya minimal 1 kali, baru kemudian memeriksa kondisi di akhir.', 'fraction' => 1.0, 'feedback' => 'Tepat! Karena kondisi baru diuji di bagian paling bawah (post-condition).'],
                    ['text' => 'do - while tidak membutuhkan kondisi penghenti perulangan.', 'fraction' => 0.0, 'feedback' => 'Salah. Tetap memerlukan kondisi while(kondisi); di akhir.'],
                    ['text' => 'do - while hanya dapat digunakan untuk memproses data teks string.', 'fraction' => 0.0, 'feedback' => 'Salah. do-while dapat digunakan untuk segala operasi logika.'],
                    ['text' => 'do - while selalu berjalan lebih cepat 2 kali lipat dari for loop.', 'fraction' => 0.0, 'feedback' => 'Salah. Efisiensi perulangan tergantung pada operasi di dalamnya.']
                ]
            ],
            [
                'name' => 'W5-REM-06: Mengapa Infinite Loop Terjadi (C2)',
                'text' => '<p>Apa penyebab paling umum yang mengakibatkan perulangan macet tanpa henti (<em>infinite loop</em>) hingga menyebabkan browser tidak responsif?</p>',
                'answers' => [
                    ['text' => 'Kondisi perulangan selalu bernilai true karena variabel pengontrol tidak pernah diubah / dinaikkan nilainya.', 'fraction' => 1.0, 'feedback' => 'Benar! Jika variabel pengontrol (seperti counter i) lupa di-increment, kondisi tidak akan pernah mencapai batas berhenti.'],
                    ['text' => 'Menggunakan tanda kurung kurawal ganda {{ }} pada blok kode.', 'fraction' => 0.0, 'feedback' => 'Salah. Itu masalah gaya penulisan / syntax, bukan penyebab infinite loop.'],
                    ['text' => 'Menulis perintah console.log di dalam blok perulangan.', 'fraction' => 0.0, 'feedback' => 'Salah. console.log adalah operasi standar yang aman.'],
                    ['text' => 'Menjalankan kode di browser Google Chrome.', 'fraction' => 0.0, 'feedback' => 'Salah. Semua browser akan hang jika program masuk ke infinite loop.']
                ]
            ],
            [
                'name' => 'W5-REM-07: Peran Keyword break dalam Perulangan (C2)',
                'text' => '<p>Apa dampak dari pemanggilan perintah <code>break;</code> di tengah-tengah perulangan?</p>',
                'answers' => [
                    ['text' => 'Menghentikan perulangan saat itu juga dan langsung melompat ke baris kode setelah blok perulangan.', 'fraction' => 1.0, 'feedback' => 'Benar! break langsung memutus dan mengakhiri loop.'],
                    ['text' => 'Melompati putaran saat ini dan melanjutkan ke putaran berikutnya.', 'fraction' => 0.0, 'feedback' => 'Salah. Itu adalah fungsi dari continue, bukan break.'],
                    ['text' => 'Memulai kembali perulangan dari iterasi pertama (indeks 0).', 'fraction' => 0.0, 'feedback' => 'Salah. break tidak me-restart loop.'],
                    ['text' => 'Menghentikan seluruh browser secara paksa.', 'fraction' => 0.0, 'feedback' => 'Salah. break hanya memutus blok perulangan lokal.']
                ]
            ],
            [
                'name' => 'W5-REM-08: Peran Keyword continue dalam Perulangan (C2)',
                'text' => '<p>Apa dampak dari pemanggilan perintah <code>continue;</code> di dalam perulangan?</p>',
                'answers' => [
                    ['text' => 'Mengabaikan sisa instruksi pada putaran (iterasi) saat ini dan langsung melompat ke putaran berikutnya.', 'fraction' => 1.0, 'feedback' => 'Benar! continue melompati satu iterasi dan lanjut ke iterasi berikutnya.'],
                    ['text' => 'Menghentikan seluruh proses perulangan secara permanen.', 'fraction' => 0.0, 'feedback' => 'Salah. Itu adalah fungsi dari break.'],
                    ['text' => 'Menghapus nilai variabel perulangan dari memori.', 'fraction' => 0.0, 'feedback' => 'Salah. continue hanya mengontrol alur iterasi.'],
                    ['text' => 'Mengubah nilai variabel menjadi bernilai negatif.', 'fraction' => 0.0, 'feedback' => 'Salah. continue tidak memanipulasi nilai variabel secara langsung.']
                ]
            ],
            [
                'name' => 'W5-REM-09: Akumulator Nilai dalam Loop (C3)',
                'text' => '<p>Perhatikan potongan kode akumulasi berikut:<br><code>let total = 0;</code><br><code>for (let i = 1; i &lt;= 3; i++) { total = total + i; }</code><br><code>console.log(total);</code><br>Berapakah nilai akhir <code>total</code> yang dicetak ke konsol?</p>',
                'answers' => [
                    ['text' => '6 (karena 0 + 1 = 1, 1 + 2 = 3, 3 + 3 = 6).', 'fraction' => 1.0, 'feedback' => 'Benar! Akumulator menjumlahkan nilai i (1, 2, dan 3) hingga total menjadi 6.'],
                    ['text' => '3', 'fraction' => 0.0, 'feedback' => 'Salah. 3 adalah nilai akhir i, bukan jumlah akumulasinya.'],
                    ['text' => '5', 'fraction' => 0.0, 'feedback' => 'Salah. Perhitungannya kurang tepat.'],
                    ['text' => '123', 'fraction' => 0.0, 'feedback' => 'Salah. Karena total bertipe number, terjadi penjumlahan matematis, bukan penggabungan teks.']
                ]
            ],
            [
                'name' => 'W5-REM-10: Perulangan Mundur / Decrement (C3)',
                'text' => '<p>Perhatikan perulangan for berikut:<br><code>for (let i = 3; i &gt; 0; i--) { console.log(i); }</code><br>Deret angka berapakah yang akan tercetak di konsol?</p>',
                'answers' => [
                    ['text' => '3, 2, 1', 'fraction' => 1.0, 'feedback' => 'Benar! Dimulai dari 3, berkurang 1 setiap putaran, dan berhenti saat i mencapai 0 (karena kondisi i &gt; 0).'],
                    ['text' => '1, 2, 3', 'fraction' => 0.0, 'feedback' => 'Salah. Perulangan bergerak mundur (i--).'],
                    ['text' => '3, 2, 1, 0', 'fraction' => 0.0, 'feedback' => 'Salah. 0 tidak ikut karena kondisinya adalah i &gt; 0 (strict greater than).'],
                    ['text' => '2, 1, 0', 'fraction' => 0.0, 'feedback' => 'Salah. Nilai awal adalah 3.']
                ]
            ]
        ]
    ]
];

// 4. Deteksi otomatis evaluation quizzes di Course untuk mapping grade items
$modinfo = get_fast_modinfo($courseid);
$eval_gradeitem_ids = [];

for ($secnum = 1; $secnum <= 5; $secnum++) {
    $eval_gradeitem_ids[$secnum] = [];
    if (!empty($modinfo->sections[$secnum])) {
        foreach ($modinfo->sections[$secnum] as $cmid) {
            $cm = $modinfo->cms[$cmid];
            if ($cm->modname === 'quiz' && stripos($cm->name, 'remedial') === false) {
                $gi = $DB->get_record('grade_items', ['itemmodule' => 'quiz', 'iteminstance' => $cm->instance, 'courseid' => $courseid]);
                if ($gi) {
                    $eval_gradeitem_ids[$secnum][] = (int)$gi->id;
                }
            }
        }
    }
    echo "Section {$secnum} Evaluation GradeItems: " . implode(', ', $eval_gradeitem_ids[$secnum]) . "\n";
}

$remedial_quiz_cmids = [];

// 5. Proses Pembuatan / Update Remedial Quiz Tiap Minggu
for ($week = 1; $week <= 5; $week++) {
    echo "\n--------------------------------------------------------\n";
    echo "MEMPROSES MINGGU {$week} (SECTION {$week})\n";
    echo "--------------------------------------------------------\n";

    $wdata = $questions_data[$week];
    $cat = get_or_create_category($coursecontext->id, $wdata['category'], "Kategori 10 soal remedial untuk Minggu {$week}");

    // Buat/Ambil 10 butir soal
    $created_questions = [];
    foreach ($wdata['questions'] as $qitem) {
        $res = create_or_get_multichoice_question($cat->id, $qitem['name'], $qitem['text'], $qitem['answers'], $adminid);
        $created_questions[] = $res;
    }
    echo "  [OK] 10 butir soal remedial siap di category {$cat->name}.\n";

    $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $week], '*', MUST_EXIST);

    // Cek apakah kuis remedial sudah ada
    $existing_quiz = $DB->get_record_sql("
        SELECT q.id, cm.id as cmid 
        FROM {quiz} q 
        JOIN {course_modules} cm ON cm.instance = q.id 
        JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
        WHERE q.course = ? AND cm.section = ? AND (q.name LIKE '%Remedial%' OR q.name LIKE '%remidial%')
    ", [$courseid, $section->id]);

    if ($existing_quiz) {
        $quizid = $existing_quiz->id;
        $cmid = $existing_quiz->cmid;
        echo "  [INFO] Kuis Remedial sudah ada: id={$quizid}, cmid={$cmid}\n";
    } else {
        $quiz = new stdClass();
        $quiz->course = $courseid;
        $quiz->name = $wdata['quiz_name'];
        $quiz->intro = $wdata['intro'];
        $quiz->introformat = FORMAT_HTML;
        $quiz->timeopen = 0;
        $quiz->timeclose = 0;
        $quiz->timelimit = 0;
        $quiz->overduehandling = 'autosubmit';
        $quiz->graceperiod = 0;
        $quiz->preferredbehaviour = 'deferredfeedback';
        $quiz->canredoquestions = 0;
        $quiz->attempts = 0;
        $quiz->attemptonlast = 0;
        $quiz->grademethod = QUIZ_GRADEHIGHEST;
        $quiz->decimalpoints = 2;
        $quiz->questiondecimalpoints = -1;
        $quiz->reviewattempt = 69904;
        $quiz->reviewcorrectness = 69904;
        $quiz->reviewmaxmarks = 0;
        $quiz->reviewmarks = 69904;
        $quiz->reviewspecificfeedback = 69904;
        $quiz->reviewgeneralfeedback = 69904;
        $quiz->reviewrightanswer = 69904;
        $quiz->reviewoverallfeedback = 69904;
        $quiz->questionsperpage = 1;
        $quiz->navmethod = 'free';
        $quiz->shuffleanswers = 1;
        $quiz->sumgrades = 100.0;
        $quiz->grade = 100.0;
        $quiz->timecreated = time();
        $quiz->timemodified = time();
        $quiz->password = '';
        $quiz->subnet = '';
        $quiz->browsersecurity = '';
        $quiz->delay1 = 0;
        $quiz->delay2 = 0;
        $quiz->showuserpicture = 0;
        $quiz->showblocks = 0;
        $quiz->completionattemptsexhausted = 0;
        $quiz->completionminattempts = 1;
        $quiz->allowofflineattempts = 0;
        $quizid = $DB->insert_record('quiz', $quiz);
        $quiz->id = $quizid;

        $quizmodule = $DB->get_field('modules', 'id', ['name' => 'quiz']);
        $cm = new stdClass();
        $cm->course = $courseid;
        $cm->module = $quizmodule;
        $cm->instance = $quizid;
        $cm->section = $section->id;
        $cm->added = time();
        $cm->score = 0;
        $cm->indent = 0;
        $cm->visible = 1;
        $cm->visibleoncoursepage = 1;
        $cm->visibleold = 1;
        $cm->groupmode = 0;
        $cm->groupingid = 0;
        $cm->completion = 2;
        $cm->completiongradeitemnumber = 0;
        $cm->completionview = 0;
        $cm->completionexpected = 0;
        $cm->completionpassgrade = 0;
        $cm->showdescription = 0;
        $cm->availability = null;
        $cmid = $DB->insert_record('course_modules', $cm);

        course_add_cm_to_section($course, $cmid, $week);
        quiz_update_grades($quiz, 0, false);
        echo "  [+] Kuis Remedial berhasil dibuat: {$wdata['quiz_name']} (id={$quizid}, cmid={$cmid})\n";
    }

    $remedial_quiz_cmids[$week] = $cmid;

    // Pasang 10 slot soal jika belum terpasang
    $quiz_obj = $DB->get_record('quiz', ['id' => $quizid]);
    $quiz_obj->cmid = $cmid;
    foreach ($created_questions as $slotidx => $qinfo) {
        $slotnum = $slotidx + 1;
        $exists_slot = $DB->get_record('quiz_slots', ['quizid' => $quizid, 'slot' => $slotnum]);
        if (!$exists_slot) {
            quiz_add_quiz_question($qinfo['questionid'], $quiz_obj, $slotnum, 10.0);
        }
    }

    // Restriksi kuis remedial: Hanya aktif jika kuis evaluasi < 70%
    $gradeitems = $eval_gradeitem_ids[$week];
    $conds = [];
    foreach ($gradeitems as $gi_id) {
        $conds[] = (object)[
            'type' => 'grade',
            'id' => (int)$gi_id,
            'max' => 70.0
        ];
    }

    if (count($conds) === 1) {
        $avail_struct = (object)[
            'op' => '&',
            'c' => $conds,
            'showc' => [false]
        ];
    } else {
        $avail_struct = (object)[
            'op' => '|',
            'c' => $conds,
            'show' => false
        ];
    }

    $avail_json = json_encode($avail_struct);
    $DB->set_field('course_modules', 'availability', $avail_json, ['id' => $cmid]);
    echo "  [OK] Restriksi Kuis Remedial terpasang (Grade < 70%).\n";
}

// 6. Update Restriksi Pembuka Section Minggu Berikutnya (Section 2 s.d. 6)
echo "\n========================================================\n";
echo " MENGONFIGURASI RESTRIKSI SECTION MINGGU BERIKUTNYA\n";
echo "========================================================\n";

for ($next_sec = 2; $next_sec <= 6; $next_sec++) {
    $prev_week = $next_sec - 1;
    $prev_gradeitems = $eval_gradeitem_ids[$prev_week];
    $prev_rem_cmid = $remedial_quiz_cmids[$prev_week];

    $conds = [];
    foreach ($prev_gradeitems as $gi_id) {
        $conds[] = (object)[
            'type' => 'grade',
            'id' => (int)$gi_id,
            'min' => 70.0
        ];
    }
    $conds[] = (object)[
        'type' => 'completion',
        'cm' => (int)$prev_rem_cmid,
        'e' => 1
    ];

    $sec_avail_struct = (object)[
        'op' => '|',
        'c' => $conds,
        'show' => false
    ];

    $sec_avail_json = json_encode($sec_avail_struct);
    $sec_record = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $next_sec]);
    if ($sec_record) {
        $DB->set_field('course_sections', 'availability', $sec_avail_json, ['id' => $sec_record->id]);
        echo "  [OK] Section {$next_sec} availability diatur: (Evaluasi >= 70% OR Remedial Selesai)\n";
    }
}

// 7. Update Halaman Kunci Jawaban Guru jika ada
$teacher_page = $DB->get_record_sql("
    SELECT p.id, p.content 
    FROM {page} p 
    JOIN {course_modules} cm ON cm.instance = p.id 
    JOIN {modules} m ON m.id = cm.module AND m.name = 'page'
    WHERE p.course = ? AND (p.name LIKE '%Kunci Jawaban%' OR p.name LIKE '%KHUSUS GURU%')
", [$courseid]);

if ($teacher_page) {
    $content = $teacher_page->content;
    if (strpos($content, 'REMEDIAL MINGGU 1') === false) {
        // Function to generate remedial HTML block
        function get_rem_block_html($week, $title, $items) {
            $li_items = '';
            foreach ($items as $q => $a) {
                $li_items .= "                    <li>{$q}: <strong>{$a}</strong></li>\n";
            }
            return <<<HTML
            <!-- REMEDIAL MINGGU {$week} -->
            <div style="margin-top: 1.25rem; padding: 1rem 1.25rem; background: #fffbeb; border: 1.5px solid #fde68a; border-left: 5px solid #f59e0b; border-radius: 8px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <strong style="color: #b45309; font-size: 0.98rem;">🟠 [Remedial] {$title} (10 Soal Penguatan &amp; Diagnostik):</strong>
                    <span style="background: #f59e0b; color: #ffffff; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">Wajib jika Nilai &lt; 70</span>
                </div>
                <ol style="margin: 0.4rem 0 0; padding-left: 1.3rem; font-size: 0.92rem; line-height: 1.7; color: #78350f;">
{$li_items}                </ol>
            </div>
HTML;
        }

        $rem_w1 = get_rem_block_html(1, 'Kuis Remedial Minggu 1: Sintaks &amp; Output', [
            'Fungsi utama JavaScript di web' => 'Memberikan interaktivitas dinamis dan logika pemrograman pada halaman web',
            'Tag penulisan script JS di HTML' => '&lt;script&gt;',
            'Atribut memuat berkas JS eksternal' => '&lt;script src="app.js"&gt;&lt;/script&gt;',
            'Perintah output ke console browser' => 'console.log("Halo Dunia");',
            'Menampilkan kotak dialog alert' => 'alert("Peringatan!");',
            'Sifat case-sensitivity JS' => 'Menggunakan nama "nilai" persis dengan huruf kecil semua',
            'Penulisan komentar satu baris' => '//',
            'Penulisan komentar multi-baris' => '/* komentar */',
            'Urutan eksekusi kode' => 'Apel, Jeruk, Mangga (dieksekusi berurutan dari atas ke bawah)',
            'Output langsung ke dokumen HTML' => 'document.write(...)'
        ]);

        $rem_w2 = get_rem_block_html(2, 'Kuis Remedial Minggu 2: Variabel &amp; Tipe Data', [
            'Kata kunci variabel nilai konstan' => 'const',
            'Keunggulan let vs var' => 'let memiliki block scope ({}) sehingga mencegah variabel bocor ke luar blok',
            'Reassigning nilai variabel const' => 'Terjadi TypeError karena variabel const tidak boleh di-assign ulang',
            'Operator cek tipe data' => 'typeof',
            'Tipe data teks diapit tanda petik' => 'string',
            'Nilai tipe data logika' => 'boolean (true dan false)',
            'Nilai default variabel tanpa inisialisasi' => 'undefined',
            'Definisi nilai null' => 'Nilai khusus yang sengaja diberikan untuk menandakan nilai kosong / tanpa referensi',
            'Aturan penamaan identifier valid' => 'totalHarga',
            'Interpolasi teks dengan backtick dan ${}' => 'Template Literals'
        ]);

        $rem_w3 = get_rem_block_html(3, 'Kuis Remedial Minggu 3: Operator &amp; Konversi', [
            'Operator sisa hasil bagi' => '% (Modulus)',
            'Perbedaan === vs ==' => '=== membandingkan nilai DAN tipe data tanpa konversi paksa (strict equality)',
            'Evaluasi 5 == "5" vs 5 === "5"' => 'true dan false',
            'Tipe data hasil kembalian prompt()' => 'string',
            'Konversi string ke angka' => 'Number(usia) atau parseInt(usia)',
            'Operasi string dan angka "10" + 5' => '"105" bertipe string (string concatenation)',
            'Operator logika AND (&&)' => 'Hanya jika kedua kondisi (kondisiA dan kondisiB) sama-sama bernilai true',
            'Operator logika OR (||)' => 'Jika minimal salah satu dari kondisi bernilai true',
            'Operator logika NOT (!)' => 'true (membalikkan nilai boolean false menjadi true)',
            'Kelompok nilai falsy' => '0, "" (string kosong), null, undefined, dan NaN'
        ]);

        $rem_w4 = get_rem_block_html(4, 'Kuis Remedial Minggu 4: Struktur Kontrol Percabangan', [
            'Sintaks pernyataan if yang benar' => 'if (skor &gt;= 70) { console.log("Lulus"); }',
            'Kapan blok else dieksekusi' => 'Ketika kondisi pada blok if bernilai false (tidak terpenuhi)',
            'Tracing else if bertingkat (skor 85)' => 'B',
            'Kapan menggunakan switch-case' => 'Mencocokkan satu variabel dengan banyak kemungkinan nilai pasti / diskrit',
            'Akibat lupa keyword break pada switch' => 'Mengalami fall-through (mengeksekusi case di bawahnya secara beruntun)',
            'Klausa alternatif terakhir switch' => 'default:',
            'Operator ternary (15 &gt;= 17) ? "Dewasa" : "Anak-anak"' => '"Anak-anak"',
            'Sifat mutually exclusive if-else' => 'Maksimal 1 blok kode (blok pertama yang kondisinya bernilai true)',
            'Evaluasi if(isMember) dengan isMember = false' => 'Harga Normal',
            'Definisi nested if' => 'Struktur pernyataan if yang diletakkan di dalam blok if atau else lainnya'
        ]);

        $rem_w5 = get_rem_block_html(5, 'Kuis Remedial Minggu 5: Struktur Kontrol Perulangan', [
            'Tujuan utama perulangan (looping)' => 'Mengeksekusi blok kode yang sama secara berulang-ulang tanpa perlu menulis ulang instruksi',
            'Tiga bagian utama for loop' => 'Inisialisasi nilai awal; Kondisi batas perulangan; Pengubahan nilai (increment/decrement)',
            'Hitung iterasi for (let i = 1; i &lt;= 5; i++)' => '5 kali (saat i bernilai 1, 2, 3, 4, dan 5)',
            'Cara kerja while loop' => 'Selama kondisi yang diuji masih bernilai true',
            'Keistimewaan do - while loop' => 'do - while pasti mengeksekusi blok instruksinya minimal 1 kali sebelum kondisi diuji',
            'Penyebab utama infinite loop' => 'Kondisi perulangan selalu bernilai true karena variabel pengontrol tidak pernah diubah nilainya',
            'Peran keyword break' => 'Menghentikan perulangan saat itu juga dan langsung keluar dari blok perulangan',
            'Peran keyword continue' => 'Mengabaikan sisa instruksi pada putaran saat ini dan langsung melompat ke putaran berikutnya',
            'Akumulator loop total += i (1 sampai 3)' => '6 (karena 0 + 1 + 2 + 3 = 6)',
            'Perulangan mundur for (let i = 3; i &gt; 0; i--)' => '3, 2, 1'
        ]);

        $content = preg_replace('/(B\. Quiz Evaluasi Minggu 1.*?<\/table>)/s', "$1\n\n{$rem_w1}", $content);
        $content = preg_replace('/(id="minggu2".*?\[Expert\] Quiz Evaluasi Minggu 2.*?<\/ol>\s*<\/div>)/s', "$1\n{$rem_w2}", $content);
        $content = preg_replace('/(id="minggu3".*?\[Expert\] Quiz Evaluasi Minggu 3.*?<\/ol>\s*<\/div>)/s', "$1\n{$rem_w3}", $content);
        $content = preg_replace('/(id="minggu4".*?\[Expert\] Quiz Evaluasi Minggu 4.*?<\/ol>\s*<\/div>)/s', "$1\n{$rem_w4}", $content);
        $content = preg_replace('/(id="minggu5".*?\[Expert\] Quiz Evaluasi Minggu 5.*?<\/ol>\s*<\/div>)/s', "$1\n{$rem_w5}", $content);

        $teacher_page->content = $content;
        $teacher_page->timemodified = time();
        $DB->update_record('page', $teacher_page);
        echo "  [OK] Halaman Kunci Jawaban Guru berhasil disinkronkan dengan 5 Kunci Jawaban Remedial!\n";
    } else {
        echo "  [INFO] Halaman Kunci Jawaban Guru sudah memiliki data remedial.\n";
    }
}

// 8. Rebuild Course Cache
rebuild_course_cache($courseid, true);
echo "\n========================================================\n";
echo " SYNC SELESAI! Course cache berhasil di-rebuild.\n";
echo "========================================================\n";
