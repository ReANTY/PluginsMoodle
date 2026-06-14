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
$string['paneloutput'] = 'Output';
$string['panelproblems'] = 'Problems';
$string['panelfeedback'] = 'Feedback';
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
$string['gradesavefailed']         = 'Gagal menyimpan nilai ke buku nilai (kode kesalahan: {$a}).';
$string['gradeemptyreset']         = 'Kosongkan untuk menghapus/reset nilai.';
$string['viewingradebook']         = 'Lihat di buku nilai kursus';
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
$string['privacy:metadata:aicode_activity_log'] = 'Metadata-only log of actions in AICode (no source code stored here)';
$string['privacy:metadata:aicode_activity_log:id_pengguna'] = 'User who performed the action';
$string['privacy:metadata:aicode_activity_log:id_kursus'] = 'Course id of the activity';
$string['privacy:metadata:aicode_activity_log:id_aktivitas_aicode'] = 'AICode activity instance id';
$string['privacy:metadata:aicode_activity_log:kode_kejadian'] = 'Event type code (internal)';
$string['privacy:metadata:aicode_activity_log:metadata_json'] = 'Optional JSON metadata without source code';
$string['privacy:metadata:aicode_activity_log:nama_lengkap'] = 'Full name copied at event time';
$string['privacy:metadata:aicode_activity_log:mode_aktivitas'] = 'Whether the activity was in training or exam mode when logged';
$string['privacy:metadata:aicode_activity_log:jumlah_ai_hint'] = 'Cumulative AI Hint requests logged up to and including this event';
$string['privacy:metadata:aicode_activity_log:jumlah_run'] = 'Cumulative run executions (estimated from attempts and log)';
$string['privacy:metadata:aicode_activity_log:jumlah_kirim_guru'] = 'Cumulative submissions to teacher recorded in attempts';
$string['privacy:metadata:aicode_activity_log:nilai_snapshot'] = 'Numeric gradebook value (0–100) at log time when present';
$string['privacy:metadata:aicode_activity_log:waktu_dicatat'] = 'When the row was logged';
$string['privacy:metadata:external:moodleai'] = 'AICode sends anonymized code snippets and execution errors to the Moodle AI provider (for example Ollama) for analysis.';
$string['privacy:metadata:external:moodleai:code'] = 'Anonymized code snippet';
$string['privacy:metadata:external:moodleai:errors'] = 'Execution errors (anonymized)';

// Metadata activity log (site admin CSV export / GDPR).
$string['activitylog_pagetitle'] = 'AICode metadata activity log';
$string['activitylog_exportheading'] = 'Export activity log (CSV)';
$string['activitylog_exportdesc'] = 'Download a UTF-8 CSV of server-side events. Includes snapshot columns such as student name (at event time), training/exam mode, cumulative AI Hint uses, cumulative runs (Run + blocked counted), cumulative teacher submits, and the score if already set in the gradebook. No student source code is stored.';
$string['activitylog_filterheading'] = 'Filters';
$string['activitylog_course'] = 'Course';
$string['activitylog_datefrom'] = 'From date (optional, YYYY-MM-DD)';
$string['activitylog_dateto'] = 'To date (optional, YYYY-MM-DD)';
$string['activitylog_applyfilters'] = 'Apply filters';
$string['activitylog_exportfiltered'] = 'Download using current filters:';
$string['activitylog_downloadcsv'] = 'Download CSV';
$string['activitylog_purgeheading'] = 'Delete log rows';
$string['activitylog_purgedesc'] = 'Deletes rows in the activity log that match the filters above. Student attempts in the attempts table are not removed.';
$string['activitylog_purge_checkbox'] = 'I confirm I want to permanently delete the matching log rows.';
$string['activitylog_purge_submit'] = 'Delete matching log rows';
$string['activitylog_purged'] = 'Matching activity log rows were deleted.';
$string['activitylog_purge_needconfirm'] = 'You must tick the confirmation box before log rows can be deleted.';
$string['activitylog_manage_heading'] = 'Research activity log';
$string['activitylog_manage_heading_desc'] = 'Administrators can export or delete metadata-only activity logs for research (page views, runs, AI hints, send to teacher, run history requests).';
$string['activitylog_manage_link'] = 'Open activity log management…';

// Indonesian activity-log UI/CSV falls back here if lang id is unavailable.
$string['activitylog_allcourses'] = 'All courses';

// CSV column headings (Bahasa Indonesia in lang/id via same keys).
$string['activitylog_csv_col_id'] = 'ID';
$string['activitylog_csv_col_time_iso'] = 'Time';
$string['activitylog_csv_col_userid'] = 'User ID';
$string['activitylog_csv_col_username'] = 'Username';
$string['activitylog_csv_col_email'] = 'Email';
$string['activitylog_csv_col_userfullname'] = 'Full name (snapshot)';
$string['activitylog_csv_col_activitymode'] = 'Activity mode';
$string['activitylog_csv_col_aihint'] = 'AI Hint count (cumulative)';
$string['activitylog_csv_col_runs'] = 'Run count (cumulative)';
$string['activitylog_csv_col_teacher_submit'] = 'Send-to-teacher count (cumulative)';
$string['activitylog_csv_col_grade'] = 'Grade (snapshot)';
$string['activitylog_csv_col_courseid'] = 'Course ID';
$string['activitylog_csv_col_courseshort'] = 'Course short name';
$string['activitylog_csv_col_coursefull'] = 'Course full name';
$string['activitylog_csv_col_cmid'] = 'Module ID';
$string['activitylog_csv_col_problemid'] = 'AICode activity ID';
$string['activitylog_csv_col_activityname'] = 'Activity name';
$string['activitylog_csv_col_action_code'] = 'Event code';
$string['activitylog_csv_col_action_label'] = 'Event description';
$string['activitylog_csv_col_meta_json'] = 'Additional metadata (JSON)';

$string['activitylog_mode_training_label'] = 'Training';
$string['activitylog_mode_exam_label'] = 'Exam';

$string['activitylog_action_activity_view'] = 'Opened activity page';
$string['activitylog_action_code_run'] = 'Ran code (executor)';
$string['activitylog_action_code_run_blocked'] = 'Run blocked (security)';
$string['activitylog_action_ai_analyze'] = 'Requested AI Hint / AI analysis';
$string['activitylog_action_hint_recorded'] = 'Recorded built-in hint use';
$string['activitylog_action_send_to_teacher'] = 'Sent work to teacher';
$string['activitylog_action_run_history_view'] = 'Opened run history';

