<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * English strings for aicode
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'AICode — AI Programming Lab';
$string['modulenameplural'] = 'AICode Activities';
$string['modulename_help'] = 'The AICode activity module enables students to write, run, and debug code in an interactive editor with AI-powered intelligent feedback.';
$string['pluginname'] = 'AICode';
$string['pluginadministration'] = 'AICode administration';

// Capabilities.
$string['aicode:addinstance'] = 'Add a new AICode activity';
$string['aicode:view'] = 'View AICode activity';
$string['aicode:submit'] = 'Submit code for execution';
$string['aicode:viewattempts'] = 'View student attempts';
$string['aicode:overridefeedback'] = 'Override AI feedback';

// Form fields.
$string['aicoproblemname'] = 'Name';
$string['description'] = 'Question';
$string['description_help'] = 'Detailed description of the programming problem. Include requirements, constraints, and examples.

You can use HTML tags to format the content visually:

* **Headings:** &lt;h3&gt;Title&lt;/h3&gt;
* **Paragraph:** &lt;p&gt;Text&lt;/p&gt;
* **Bold:** &lt;strong&gt;bold text&lt;/strong&gt;
* **Italic:** &lt;em&gt;italic text&lt;/em&gt;
* **Inline code:** &lt;code&gt;console.log()&lt;/code&gt;
* **Unordered list:** &lt;ul&gt;&lt;li&gt;item&lt;/li&gt;&lt;/ul&gt;
* **Ordered list:** &lt;ol&gt;&lt;li&gt;item&lt;/li&gt;&lt;/ol&gt;
* **Line break:** &lt;br&gt;';
$string['language'] = 'Programming language';
$string['mode'] = 'Mode';
$string['mode_help'] = 'Choose how this activity behaves for students. Training mode enables AI feedback and hints. Exam mode only records student answers without showing AI feedback; only teachers can see detailed evaluation.';
$string['mode_training'] = 'Training (latihan, AI aktif)';
$string['mode_exam'] = 'Exam (ulangan, tanpa feedback AI untuk siswa)';
$string['aiprompttemplate'] = 'AI prompt template override (teacher)';
$string['aiprompttemplate_help'] = 'Optional prompt template for this activity only. Use [[CODE]], [[STDERR]], and [[TRACE]] placeholders. Leave empty to use the global plugin prompt template.';
$string['testcases'] = 'Test cases (JSON)';
$string['testcases_help'] = 'JSON array of test cases. Example: [{"input": "5", "expected": "120"}]';
$string['startercode'] = 'Starter code template';
$string['htmltemplate'] = 'HTML template (read-only for students)';
$string['htmltemplate_help'] = 'HTML provided by the teacher. Students can only edit JavaScript.';
$string['csstemplate'] = 'CSS template (read-only for students)';
$string['csstemplate_help'] = 'CSS provided by the teacher. Students can only edit JavaScript.';
$string['invalidjson'] = 'Invalid JSON format';

// Privacy.
$string['privacy'] = 'Privacy and AI settings';
$string['allowtraining'] = 'Allow anonymized data for model training';
$string['allowtraining_desc'] = 'Allow anonymized student code to be used for improving AI models (opt-in)';

// Admin settings.
$string['executorurl'] = 'Executor service URL';
$string['executorurl_desc'] = 'URL of the code execution microservice (e.g., http://127.0.0.1:3001)';
$string['aifeedbackprompttemplate'] = 'Default AI feedback prompt template';
$string['aifeedbackprompttemplate_desc'] = 'Global prompt template used for AI feedback generation. Use [[CODE]], [[STDERR]], and [[TRACE]] placeholders. Teachers can override this per activity in activity settings.';
$string['confidencethreshold'] = 'Confidence threshold';
$string['confidencethreshold_desc'] = 'Minimum confidence score (0.0-1.0) to accept AI feedback';
$string['cachettl'] = 'Cache TTL (seconds)';
$string['cachettl_desc'] = 'Time to live for cached AI responses';
$string['maxcallsperday'] = 'Max AI calls per student per day';
$string['maxcallsperday_desc'] = 'Rate limit for AI analysis requests per student';
$string['executiontimeout'] = 'Execution timeout (seconds)';
$string['executiontimeout_desc'] = 'Maximum time allowed for code execution';

// View page.
$string['run'] = 'Run';
$string['hint'] = 'AI Hint';
$string['reset'] = 'Reset';
$string['sendtoteacher'] = 'Send to Teacher';
$string['output'] = 'Console';
$string['errors'] = 'Errors';
$string['hints'] = 'Hints';
$string['feedback'] = 'AI Feedback';
$string['preview'] = 'Preview';
$string['htmlreadonly'] = 'HTML';
$string['cssreadonly'] = 'CSS';
$string['history'] = 'History';
$string['submit'] = 'Submit';
$string['erroraifeedback'] = 'Error & AI Feedback';

// Teacher report page.
$string['reporttitle']             = 'Laporan Guru';
$string['reportstudentdetail']     = 'Detail Siswa';
$string['backtoproblem']           = 'Kembali ke Aktivitas';
$string['setgrade']                = 'Beri Nilai';
$string['gradesaved']              = 'Nilai berhasil disimpan.';
$string['gradeemptyreset']         = 'Kosongkan untuk menghapus/reset nilai.';
$string['notessaved']              = 'Catatan berhasil disimpan.';
$string['correctionsaved']         = 'Koreksi feedback AI berhasil disimpan.';
$string['submittedcode']           = 'Kode Terakhir Dikumpulkan';
$string['nocodesubmitted']         = 'Kode tidak tersedia. Siswa belum menggunakan tombol "Submit" atau "Run" setelah pembaruan plugin.';
$string['teachernotes']            = 'Catatan Guru';
$string['teachernotesplaceholder'] = 'Tambahkan catatan atau koreksi untuk siswa ini...';
$string['savenotes']               = 'Simpan Catatan';
$string['aifeedback']              = 'Feedback AI Terakhir';
$string['noaifeedback']            = 'Belum ada feedback AI untuk siswa ini.';
$string['attempthistory']          = 'Riwayat Percobaan';
$string['noattempts']              = 'Siswa belum melakukan percobaan apapun.';

// AI feedback correction panel.
$string['aicorrectiontitle']       = 'Evaluasi & Koreksi Feedback AI';
$string['aicorrectiondesc']        = 'Nilai kualitas feedback AI dan koreksi bagian yang kurang tepat. Koreksi yang disetujui akan dipakai sebagai contoh untuk meningkatkan feedback AI berikutnya (few-shot learning).';
$string['feedbackratinglabel']     = 'Nilai kualitas feedback AI ini';
$string['feedbackratingdesc']      = '1 = sangat buruk, 5 = sangat baik';
$string['correcteddiagnosistitle'] = 'Koreksi Diagnosis';
$string['correctedshort']          = 'Pesan singkat yang lebih tepat';
$string['correctedshortph']        = 'Contoh: Variabel belum didefinisikan sebelum digunakan';
$string['correctedlong']           = 'Penjelasan panjang yang lebih tepat';
$string['correctedlongph']         = 'Jelaskan kesalahan siswa secara lebih detail dan tepat...';
$string['correctedhintstitle']     = 'Koreksi Petunjuk (Hints)';
$string['correctedhintsph']        = 'Satu petunjuk per baris. Contoh:\nPeriksa apakah variabel sudah dideklarasikan dengan let/const/var\nCek urutan deklarasi dan pemanggilan fungsi';
$string['correctedfixtitle']       = 'Koreksi Saran Perbaikan';
$string['correctedfixph']          = 'Tuliskan saran perbaikan yang lebih akurat...';
$string['useasexample']            = 'Jadikan contoh untuk meningkatkan AI (few-shot learning)';
$string['useasexampledesc']        = 'Jika dicentang, koreksi ini akan diinjeksikan ke prompt Gemini sebagai contoh saat menganalisis kode serupa di masa mendatang.';
$string['savecorrection']          = 'Simpan Koreksi';
$string['aicorrection_existing']   = 'Koreksi tersimpan';
$string['aicorrection_ratedon']    = 'Dinilai oleh guru pada';
$string['aicorrection_usedas']     = 'Digunakan sebagai contoh few-shot';
$string['aicorrection_notused']    = 'Belum dijadikan contoh few-shot';
$string['noaifeedbacktocorrect']   = 'Belum ada feedback AI yang bisa dikoreksi untuk siswa ini. Feedback AI akan muncul setelah siswa menggunakan tombol AI Hint.';

// Security Check Module.
$string['securityheading'] = 'Security Check Module';
$string['securityheading_desc'] = 'Server-side static analysis that scans student code for dangerous patterns before forwarding it to the executor. Violations are recorded in the attempt log and visible to teachers in the report.';
$string['securitycheckenabled'] = 'Enable server-side security check';
$string['securitycheckenabled_desc'] = 'When enabled, student code is analysed for dangerous operations (OS commands, filesystem access, network requests, code injection, etc.) before execution. Blocked attempts are still recorded so teachers can review them.';
$string['securityblocklevel'] = 'Minimum risk level to block';
$string['securityblocklevel_desc'] = 'Code with an aggregate risk at or above this level will be blocked and not sent to the executor. "High" is recommended for most environments.';
$string['securitylevel_low'] = 'Low — block any violation (strictest)';
$string['securitylevel_medium'] = 'Medium — block medium, high, and critical';
$string['securitylevel_high'] = 'High — block high and critical only (recommended)';
$string['securitylevel_critical'] = 'Critical — block only critical violations (most permissive)';

// Events.
$string['eventcodesubmitted'] = 'Code submitted';
$string['eventhintviewed'] = 'Hint viewed';

// Privacy API.
$string['privacy:metadata:aicode_attempts'] = 'Information about student code submissions';
$string['privacy:metadata:aicode_attempts:userid'] = 'User ID of the student';
$string['privacy:metadata:aicode_attempts:code_hash'] = 'Hash of submitted code';
$string['privacy:metadata:aicode_attempts:timecreated'] = 'Time when the attempt was made';
$string['privacy:metadata:external:moodleai'] = 'AICode sends anonymized code snippets and execution errors to the Moodle AI provider (for example Ollama) for analysis.';
$string['privacy:metadata:external:moodleai:code'] = 'Anonymized code snippet';
$string['privacy:metadata:external:moodleai:errors'] = 'Execution errors (anonymized)';

