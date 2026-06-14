<?php
/**
 * JavaScript Fundamental Course - Configuration
 */

$course_config = [
    'fullname' => 'JavaScript Fundamental',
    'shortname' => 'JS-FUND-2026',
    'category' => 1,
    'summary' => '<div class="course-summary">
        <h3>Selamat Datang di Course JavaScript Fundamental</h3>
        <p>Course ini dirancang untuk <strong>pemula total</strong> dengan metode <strong>microlearning</strong> (5–10 menit per lesson).</p>
        <h4>CPMK</h4>
        <ol>
            <li>Memahami konsep dasar JavaScript dan sintaksnya</li>
            <li>Menggunakan variabel dan tipe data dengan benar</li>
            <li>Menerapkan operator dan struktur kontrol</li>
            <li>Membuat dan menggunakan function</li>
            <li>Bekerja dengan array dan object dasar</li>
            <li>Membuat program JavaScript sederhana yang interaktif</li>
        </ol>
        <h4>Capaian Akhir</h4>
        <p>Peserta mampu menulis program JavaScript dasar, menyelesaikan tugas coding, lulus kuis mingguan, dan menyelesaikan final project untuk memperoleh sertifikat internal.</p>
        <h4>Sertifikat</h4>
        <p>Sertifikat diberikan jika completion course 100%, nilai akhir minimal 70%, dan final project selesai.</p>
        <h4>Penilaian</h4>
        <ul>
            <li>Micro Lesson Quiz: 20%</li>
            <li>Weekly Quiz: 25%</li>
            <li>Weekly Assignment: 25%</li>
            <li>Final Project: 30%</li>
        </ul>
    </div>',
    'format' => 'topics',
    'numsections' => 8,
    'startdate' => time(),
    'visible' => 1,
    'enablecompletion' => 1,
    'showgrades' => 1,
];

$grading_config = [
    'micro_quiz_weight' => 20,
    'weekly_quiz_weight' => 25,
    'weekly_assignment_weight' => 25,
    'final_project_weight' => 30,
    'passing_grade' => 70,
];

return [
    'course' => $course_config,
    'grading' => $grading_config,
];
