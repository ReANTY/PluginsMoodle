<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Indonesian strings — mod_aicode (fokus log aktivitas & antarmuka utama).
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AICode';
$string['pluginadministration'] = 'Administrasi AICode';
$string['modulename'] = 'AICode — Lab pemrograman ber-AI';

// Kapabilitas ringkas (untuk admin UI).
$string['aicode:addinstance'] = 'Tambah aktivitas AICode baru';
$string['aicode:view'] = 'Lihat aktivitas AICode';
$string['aicode:submit'] = 'Kirim kode untuk dieksekusi';
$string['aicode:viewattempts'] = 'Lihat percobaan siswa';
$string['aicode:overridefeedback'] = 'Ubah umpan balik AI';

$string['activitylog_pagetitle'] = 'Log metadata aktivitas AICode';
$string['activitylog_exportheading'] = 'Ekspor log aktivitas (CSV)';
$string['activitylog_exportdesc'] = 'Unduh CSV UTF-8 berisi kejadian sisi-server. Ada kolom snapshot: nama siswa saat itu, mode latihan/ujian, jumlah AI Hint kumulatif, jumlah run kumulatif, jumlah kirim ke guru, dan nilai jika sudah ada di buku nilai. Kode siswa tidak disimpan di tabel ini.';
$string['activitylog_filterheading'] = 'Penyaringan';
$string['activitylog_course'] = 'Kursus';
$string['activitylog_datefrom'] = 'Dari tanggal (opsional, YYYY-MM-DD)';
$string['activitylog_dateto'] = 'Sampai tanggal (opsional, YYYY-MM-DD)';
$string['activitylog_applyfilters'] = 'Terapkan filter';
$string['activitylog_exportfiltered'] = 'Unduh dengan filter saat ini:';
$string['activitylog_downloadcsv'] = 'Unduh CSV';
$string['activitylog_purgeheading'] = 'Hapus baris log';
$string['activitylog_purgedesc'] = 'Menghapus baris di log yang cocok dengan filter di atas. Percobaan siswa di tabel attempts tidak dihapus.';
$string['activitylog_purge_checkbox'] = 'Saya mengonfirmasi ingin menghapus permanen baris log yang cocok.';
$string['activitylog_purge_submit'] = 'Hapus baris log yang cocok';
$string['activitylog_purged'] = 'Baris log yang cocok telah dihapus.';
$string['activitylog_purge_needconfirm'] = 'Centang kotak konfirmasi sebelum menghapus baris log.';
$string['activitylog_manage_heading'] = 'Log aktivitas (penelitian)';
$string['activitylog_manage_heading_desc'] = 'Administrator dapat mengekspor atau menghapus log metadata (buka halaman, run, AI hint, kirim ke guru, buka riwayat).';
$string['activitylog_manage_link'] = 'Buka pengelolaan log aktivitas…';
$string['activitylog_allcourses'] = 'Semua kursus';

// Judul kolom CSV (tabel untuk spreadsheet).
$string['activitylog_csv_col_id'] = 'ID';
$string['activitylog_csv_col_time_iso'] = 'Waktu';
$string['activitylog_csv_col_userid'] = 'ID pengguna';
$string['activitylog_csv_col_username'] = 'Nama pengguna';
$string['activitylog_csv_col_email'] = 'Email';
$string['activitylog_csv_col_userfullname'] = 'Nama lengkap (snapshot)';
$string['activitylog_csv_col_activitymode'] = 'Mode aktivitas';
$string['activitylog_csv_col_aihint'] = 'Jumlah AI Hint (kumulatif)';
$string['activitylog_csv_col_runs'] = 'Jumlah run (kumulatif)';
$string['activitylog_csv_col_teacher_submit'] = 'Jumlah kirim ke guru (kumulatif)';
$string['activitylog_csv_col_grade'] = 'Nilai (snapshot)';
$string['activitylog_csv_col_courseid'] = 'ID kursus';
$string['activitylog_csv_col_courseshort'] = 'Nama singkat kursus';
$string['activitylog_csv_col_coursefull'] = 'Nama lengkap kursus';
$string['activitylog_csv_col_cmid'] = 'ID modul';
$string['activitylog_csv_col_problemid'] = 'ID aktivitas AICode';
$string['activitylog_csv_col_activityname'] = 'Nama aktivitas';
$string['activitylog_csv_col_action_code'] = 'Kode kejadian';
$string['activitylog_csv_col_action_label'] = 'Uraian kejadian';
$string['activitylog_csv_col_meta_json'] = 'Metadata tambahan (JSON)';

$string['activitylog_mode_training_label'] = 'Latihan';
$string['activitylog_mode_exam_label'] = 'Ujian';

$string['activitylog_action_activity_view'] = 'Membuka halaman aktivitas';
$string['activitylog_action_code_run'] = 'Menjalankan kode (eksekutor)';
$string['activitylog_action_code_run_blocked'] = 'Eksekusi dicekal (keamanan)';
$string['activitylog_action_ai_analyze'] = 'Meminta AI Hint / analisis AI';
$string['activitylog_action_hint_recorded'] = 'Mencatat penggunaan petunjuk';
$string['activitylog_action_send_to_teacher'] = 'Mengirim pekerjaan ke guru';
$string['activitylog_action_run_history_view'] = 'Membuka riwayat run';

