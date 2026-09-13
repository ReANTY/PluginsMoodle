<?php
/**
 * CLI script to update startercode for mod_aicode activities in Moodle.
 * Ensures the code editor only contains the starter code skeleton/comments,
 * removing any leaked answer keys or solutions.
 *
 * Usage:
 *   php admin/cli/update_aicode_startercode.php
 *   (or on Linux VM: sudo -u www-data php admin/cli/update_aicode_startercode.php)
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');

global $DB, $CFG;

echo "========================================================\n";
echo " AICODE: SINKRONISASI STARTER CODE TEMPLATE KE DATABASE\n";
echo "========================================================\n\n";

$datasetfile = $CFG->dirroot . '/Bank_Soal_dan_Latihan_JS_FUND_2026/master_dataset.json';
if (!file_exists($datasetfile)) {
    echo "ERROR: File dataset {$datasetfile} tidak ditemukan!\n";
    exit(1);
}

$dataset = json_decode(file_get_contents($datasetfile), true);
if (!$dataset) {
    echo "ERROR: Gagal membaca format JSON dari {$datasetfile}!\n";
    exit(1);
}

// 1. Temukan Course JS-FUND-2026
$course = $DB->get_record('course', ['shortname' => 'JS-FUND-2026']);
if (!$course) {
    $course = $DB->get_record('course', ['id' => 22]);
}

$courseid = $course ? $course->id : null;
if ($course) {
    echo "Course Target: {$course->fullname} (ID: {$course->id}, Shortname: {$course->shortname})\n\n";
} else {
    echo "Target course JS-FUND-2026 tidak ditemukan, akan mencocokkan berdasarkan ID dan Nama modul secara global.\n\n";
}

// Gabungkan latihan dan ujian aicode dari dataset
$allitems = array_merge($dataset['latihan_aicode'] ?? [], $dataset['ujian_aicode'] ?? []);
$updatedcount = 0;
$unchangedcount = 0;

foreach ($allitems as $item) {
    $aicodeid = (int)$item['aicode_id'];
    $name = trim($item['name']);
    $startercode = $item['starter_code'];

    // Cari record aicode
    $rec = null;
    if ($aicodeid > 0) {
        $rec = $DB->get_record('aicode', ['id' => $aicodeid]);
    }

    // Fallback pencarian by name & course
    if (!$rec && $courseid) {
        $rec = $DB->get_record('aicode', ['course' => $courseid, 'name' => $name]);
    }

    if (!$rec) {
        $rec = $DB->get_record('aicode', ['name' => $name]);
    }

    if (!$rec) {
        echo "[-] Tidak ditemukan di DB: [{$aicodeid}] {$name}\n";
        continue;
    }

    $current_starter = $rec->startercode ?? '';

    // Cek apakah perlu diupdate
    if ($current_starter !== $startercode) {
        $DB->set_field('aicode', 'startercode', $startercode, ['id' => $rec->id]);
        $DB->set_field('aicode', 'timemodified', time(), ['id' => $rec->id]);
        echo "[UPDATED] ID {$rec->id}: {$rec->name}\n";
        $firstline = strtok(trim($startercode), "\n");
        echo "          Starter baru: {$firstline}\n";
        $updatedcount++;
    } else {
        $unchangedcount++;
    }
}

// Tambahan: jika ada record Course 2 (ID 32 & 33) yang masih menyimpan jawaban langsung
$course2_fixes = [
    32 => "// 🎯 Latihan 5.1: Ubah Konten DOM\n\n// 1. Ubah teks #judul menjadi \"Selamat Datang\"\n\n// 2. Ubah teks #deskripsi menjadi \"Belajar DOM JavaScript\"\n\n// 3. Ubah warna teks #warna menjadi \"blue\"\n\n// 4. Tampilkan pesan \"DOM berhasil dimanipulasi!\" di #output\n",
    33 => "// 🎯 Latihan 5.2: Event Handling\n\n// 1. Tombol Ubah Warna Latar (#btnUbah)\n// Saat diklik: ubah document.body.style.backgroundColor ke \"lightyellow\", tampilkan \"Warna berubah!\" di #pesan\n\n// 2. Tombol Reset (#btnReset)\n// Saat diklik: kembalikan document.body.style.backgroundColor ke \"\", tampilkan \"Reset berhasil\" di #pesan\n\n// 3. Tombol Sapa (#btnSapa)\n// Saat diklik: baca nilai dari #inputNama, tampilkan \"Halo, [nama]!\" di #hasil\n",
];

foreach ($course2_fixes as $c2id => $c2starter) {
    $rec2 = $DB->get_record('aicode', ['id' => $c2id]);
    if ($rec2 && $rec2->startercode !== $c2starter) {
        $DB->set_field('aicode', 'startercode', $c2starter, ['id' => $c2id]);
        $DB->set_field('aicode', 'timemodified', time(), ['id' => $c2id]);
        echo "[UPDATED] ID {$rec2->id}: {$rec2->name} (Course {$rec2->course})\n";
        $updatedcount++;
    }
}

echo "\n--------------------------------------------------------\n";
echo "Hasil: {$updatedcount} aktivitas diperbarui, {$unchangedcount} sudah sesuai.\n";

// Membersihkan cache Moodle
echo "Membersihkan cache Moodle...\n";
purge_all_caches();

if ($courseid) {
    rebuild_course_cache($courseid, true);
}

echo "SELESAI! Semua aktivitas AICode sekarang menggunakan starter code template.\n";
