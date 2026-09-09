<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Adaptive Learning AI';
$string['adaptive_learning_ai:addinstance'] = 'Tambahkan block Adaptive Learning AI baru';
$string['adaptive_learning_ai:myaddinstance'] = 'Tambahkan block Adaptive Learning AI ke Dashboard';
$string['notenrolled'] = 'Anda tidak terdaftar di kursus ini.';

// Settings
$string['setting_ai_heading'] = 'Pengaturan Google Gemini AI';
$string['setting_ai_heading_desc'] = 'Konfigurasi integrasi Google Gemini API untuk rekomendasi pembelajaran adaptif dan tutor microlearning.';
$string['setting_gemini_apikey'] = 'Kunci API Gemini (API Key)';
$string['setting_gemini_apikey_desc'] = 'Masukkan kunci Google Gemini API dari Google AI Studio. Kosongkan jika ingin menggunakan unit materi offline saja.';
$string['setting_gemini_model'] = 'Model Gemini';
$string['setting_gemini_model_desc'] = 'Pilih model Generative AI yang digunakan untuk rekomendasi dan tutor interaktif.';
$string['setting_threshold_heading'] = 'Ambang Batas Nilai (Thresholds)';
$string['setting_remedial_threshold'] = 'Batas Remedial (%)';
$string['setting_remedial_threshold_desc'] = 'Nilai di bawah angka ini akan dikategorikan sebagai Rendah / Remedial (default: 70).';
$string['setting_advanced_threshold'] = 'Batas Advanced (%)';
$string['setting_advanced_threshold_desc'] = 'Nilai sama dengan atau di atas angka ini akan dikategorikan sebagai Tinggi / Advanced (default: 90).';

// Reports & block UI
$string['report_quiz'] = 'Laporan Quiz';
$string['report_teacher'] = 'Laporan Quiz Guru';
$string['report_student'] = 'Nilai Quiz Saya';
$string['student_name'] = 'Nama Siswa';
$string['student_score'] = 'Nilai';
$string['student_level'] = 'Tingkat';
$string['student_status'] = 'Status';
$string['level_low'] = 'Rendah';
$string['level_medium'] = 'Menengah';
$string['level_high'] = 'Tinggi';
$string['report_student_list'] = 'Daftar Siswa';
$string['report_graph_title'] = 'Nilai Siswa';
$string['report_graph_series'] = 'Nilai (%)';
$string['back_to_list'] = 'Kembali ke Daftar';
$string['quiz_detail'] = 'Detail Quiz';
$string['student_answer'] = 'Jawaban Siswa';
$string['correct_answer'] = 'Jawaban Benar';
$string['question_score'] = 'Skor Pertanyaan';
$string['quiz_date_time'] = 'Tanggal dan Waktu Quiz';
$string['quiz_duration'] = 'Durasi';
$string['quiz_status'] = 'Status Quiz';
$string['question_text'] = 'Pertanyaan';
$string['no_quiz_data'] = 'Tidak ada data quiz yang tersedia';
$string['average_score'] = 'Rata-rata Nilai';
$string['highest_score'] = 'Nilai Tertinggi';
$string['total_quiz'] = 'Total Quiz';
$string['student_progress_chart'] = 'Progress Nilai Siswa';
