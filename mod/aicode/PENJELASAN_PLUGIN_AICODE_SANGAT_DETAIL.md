# Penjelasan Sangat Detail Plugin AICode (mod_aicode)

Dokumen ini dibuat sebagai referensi tunggal agar AI lain dapat memahami plugin `mod_aicode` tanpa membaca source code secara langsung.

Fokus dokumen ini adalah **implementasi aktual** plugin saat ini, termasuk:
- fitur yang benar-benar berjalan,
- fitur yang masih parsial,
- batasan teknis,
- alur data end-to-end.

---

## 1) Identitas Plugin

- Nama plugin: `AICode`
- Komponen Moodle: `mod_aicode` (activity module)
- Jenis plugin: activity module untuk latihan coding berbasis AI
- Versi rilis: `1.2.0`
- Versi internal plugin: `2026021200`
- Minimal Moodle: `5.0` (build `2025041400`)
- Maturity: `BETA`

Tujuan utama plugin:
- menyediakan aktivitas pemrograman interaktif di Moodle,
- memungkinkan siswa menulis dan menjalankan kode,
- memberi umpan balik AI saat terjadi error,
- mendukung mode latihan dan mode ujian.

---

## 2) Ringkasan Fungsi Utama Plugin

Plugin ini menggabungkan 3 lapisan:

1. **Editor + UI pembelajaran** di halaman aktivitas Moodle.
2. **Eksekusi kode** lewat microservice executor eksternal (`POST /run`).
3. **Analisis AI** lewat Moodle AI subsystem (`core_ai`, action `generate_text`), dengan fallback rule-based.

Hasil akhirnya:
- siswa menulis kode JavaScript,
- klik `Run` untuk menjalankan kode terhadap test case,
- jika error, siswa bisa minta `Hint` (AI diagnosis),
- siswa bisa kirim jawaban ke guru dengan tombol `Submit`.

---

## 3) Peran Pengguna dan Hak Akses (Capabilities)

Capability yang didefinisikan:

- `mod/aicode:addinstance`
  - role default: `editingteacher`, `manager`
  - fungsi: menambah aktivitas AICode
- `mod/aicode:view`
  - role default: `guest`, `student`, `teacher`, `editingteacher`, `manager`
  - fungsi: melihat halaman aktivitas
- `mod/aicode:submit`
  - role default: `student`
  - fungsi: menjalankan kode, meminta analisis, rekam hint, kirim ke guru
- `mod/aicode:viewattempts`
  - role default: `teacher`, `editingteacher`, `manager`
  - fungsi: dipakai sebagai indikator role guru di frontend
- `mod/aicode:overridefeedback`
  - role default: `editingteacher`, `manager`
  - fungsi: capability sudah ada, implementasi fitur override belum aktif di alur utama

---

## 4) Konfigurasi Aktivitas (Saat Guru Membuat Soal)

Field utama di form aktivitas:

- `Name` (nama aktivitas)
- `Intro` (field standar Moodle)
- `Question` (`description`)
- `Language` (saat ini hanya opsi `javascript`)
- `Mode`:
  - `training` (AI feedback aktif)
  - `exam` (AI feedback untuk siswa dinonaktifkan)
- `Test cases` (JSON string)
- `Starter code` (template awal siswa)
- `HTML template` (read-only bagi siswa)
- `CSS template` (read-only bagi siswa)
- `Allow anonymized data for model training` (`allow_training`)

Validasi form:
- `testcases` harus JSON valid jika diisi.

---

## 5) Konfigurasi Admin Plugin

Setting level site:

- `executor_url` (default `http://127.0.0.1:3001`)
- `confidence_threshold` (default `0.6`)
- `cache_ttl` dalam detik (default `3600`)
- `max_calls_per_day` (default `50`)
- `execution_timeout` (default `2`)

Catatan penting:
- `execution_timeout` tersedia di setting, tetapi pada implementasi saat ini belum dipakai langsung di request endpoint eksekusi.

---

## 6) Arsitektur Sistem (Konseptual ke Implementasi)

### Frontend (Browser)
- halaman aktivitas: `view.php` + `renderer.php`
- modul JS: `amd/src/editor.js`
- AJAX Moodle: `core/ajax`
- preview lokal: iframe sandbox (`srcdoc`)
- riwayat kode: `sessionStorage` (per problem)

### Backend (Moodle plugin)
- registry service: `db/services.php`
- endpoint:
  - `mod_aicode_run_code`
  - `mod_aicode_analyze_code`
  - `mod_aicode_record_hint`
  - `mod_aicode_send_to_teacher`
- security gate:
  - `require_login`
  - validasi context modul
  - validasi sesskey (jika dikirim)
  - `require_capability`

### External dependency
- Executor service eksternal (wajib untuk run code).
- Moodle AI provider (misalnya Ollama via sistem AI Moodle) untuk feedback AI.

---

## 7) Alur End-to-End yang Sebenarnya Terjadi

### 7.1 Saat halaman aktivitas dibuka
1. Moodle load instance `aicode` + course module.
2. Event `course_module_viewed` dipicu.
3. Renderer membangun UI:
   - deskripsi soal,
   - panel HTML/CSS read-only,
   - editor JavaScript siswa,
   - tombol `Run`, `History`, `Hint`, `Reset`, `Submit`,
   - panel error + AI feedback,
   - preview iframe.
4. Data metadata (`problemid`, `language`, `mode`, `isTeacher`, `sesskey`) ditanam di DOM untuk fallback.

### 7.2 Saat siswa klik `Run`
1. Frontend ambil kode dari editor.
2. Frontend jalankan pre-filter sederhana untuk pattern berisiko (mis. `child_process`, `execSync`, dll).
3. Kode ditambahkan ke riwayat lokal (session-only, maksimum 20 entri).
4. Kode dijalankan juga di iframe preview lokal (HTML+CSS+JS).
5. AJAX memanggil `mod_aicode_run_code` ke backend.
6. Backend kirim payload ke executor (`/run`) dengan:
   - code,
   - language,
   - testcase (hasil decode JSON testcases dari aktivitas).
7. Backend simpan attempt (hash kode + ringkasan output).
8. Frontend menampilkan stderr/stdout yang relevan.
9. Jika gagal dan mode bukan exam-siswa, frontend memulai proses analisis AI asynchronous.

### 7.3 Saat siswa klik `Hint`
1. Jika feedback AI sudah tersedia (cache frontend), langsung ditampilkan.
2. Jika AI masih berjalan, UI tampilkan status loading.
3. Jika belum pernah run, hint ditolak (minta run dulu).
4. Setelah feedback tampil, frontend memanggil `mod_aicode_record_hint` untuk mencatat penggunaan hint.

### 7.4 Saat siswa klik `Submit` (Send to Teacher)
1. Frontend kirim `problemid + code` ke endpoint `mod_aicode_send_to_teacher`.
2. Backend simpan attempt khusus dengan marker `teacher_review_requested`.
3. Saat ini belum ada notifikasi otomatis ke guru (masih TODO).

---

## 8) Detail Endpoint API (Web Service Internal Moodle)

## `mod_aicode_run_code`

Input:
- `problemid` (int)
- `code` (raw, max 50KB)
- `language` (alpha)
- `sesskey` (opsional)

Validasi:
- context modul valid
- capability `mod/aicode:submit`
- sesskey jika dikirim harus valid

Proses:
- ambil soal dari tabel `aicode`
- kirim ke executor URL (`/run`)
- fallback error jika response executor kosong/tidak valid
- simpan attempt ke `aicode_attempts`:
  - `code_hash` (SHA-256),
  - `result_json` (ringkasan stdout/stderr dipotong 1000 char),
  - `userid` bisa `null` jika anonymized training diaktifkan

Output:
- JSON string `result`

---

## `mod_aicode_analyze_code`

Input:
- `problemid`
- `code`
- `stderr`
- `trace`
- `sesskey` (opsional)

Validasi:
- context + capability submit
- sesskey
- rate limit harian berdasarkan jumlah record attempt user (`max_calls_per_day`)

Proses inti:
1. cek cache `aicode_cache` via hash payload (`feedback-v3|code|stderr|trace`)
2. anonymize code (hapus komentar dan email)
3. panggil Moodle AI manager (`generate_text`) dengan prompt JSON ketat
4. parse/normalisasi hasil AI
5. jika confidence < threshold -> fallback rule-based
6. cache hasil jika bukan kategori kegagalan provider

Fallback:
- rule-based diagnosis untuk pola error umum:
  - `SyntaxError`
  - `ReferenceError`
  - `TypeError`
  - generic runtime

Output:
- JSON string `feedback` dengan struktur:
  - `diagnosis`
  - `location`
  - `hints`
  - `suggested_fix`
  - `recommended_materials`
  - `explainability`

---

## `mod_aicode_record_hint`

Input:
- `problemid`
- `level` (int)
- `sesskey` (opsional)

Proses:
- cari attempt user untuk problem tersebut
- append metadata hint ke `used_hints_json`

Output:
- `success: true`

Catatan:
- frontend saat ini selalu mengirim level `1` (single hint mode detail).

---

## `mod_aicode_send_to_teacher`

Input:
- `problemid`
- `code`
- `sesskey` (opsional)

Proses:
- simpan record baru ke `aicode_attempts`
- `result_json` berisi `teacher_review_requested` dan **kode penuh**

Output:
- `success: true`

Catatan:
- notifikasi ke guru belum diimplementasi (TODO).

---

## 9) Perilaku UI yang Penting

### Editor
- Implementasi aktif menggunakan textarea custom + syntax highlight sederhana + line numbers.
- Container Monaco ada, tetapi disembunyikan. Jadi pengalaman saat ini bukan Monaco penuh.

### Preview
- Iframe sandbox (`allow-scripts`) untuk render HTML/CSS/JS.
- Console/error dari iframe dipostMessage ke parent dan ditampilkan di panel error.
- Ada loop protector dasar (abort loop > ~500ms) dengan injeksi fungsi checker.

### History
- Disimpan di `sessionStorage`, key per problem.
- Menyimpan snapshot kode saat `Run`.
- Maksimum 20 item.
- Klik item:
  - klik pertama: expand isi,
  - klik lagi: apply ke editor.

### Reset
- Konfirmasi dulu.
- Mengembalikan ke starter code.
- membersihkan error panel, feedback, marker error, dan preview.

---

## 10) Mode Aktivitas: Training vs Exam

## Training mode
- Semua fitur normal aktif:
  - run,
  - analisis AI,
  - hint,
  - submit ke guru.

## Exam mode (untuk siswa non-guru)
- Tombol hint disembunyikan.
- Run tetap bisa dipakai untuk eksekusi/check error.
- Analisis AI otomatis setelah error tidak ditampilkan ke siswa.
- Submit tetap tersedia.

## Exam mode untuk guru
- Guru tetap dikenali via capability `viewattempts` sehingga pembatasan siswa exam tidak diterapkan penuh.

---

## 11) Model Data Database (Lengkap)

## Tabel `aicode` (definisi aktivitas/soal)
Field penting:
- `course`, `name`, `intro`, `description`
- `language` (default javascript)
- `testcases` (JSON text)
- `startercode`
- `htmltemplate`, `csstemplate`
- `allow_training` (0/1)
- `mode` (`training`/`exam`)
- timestamps

## Tabel `aicode_attempts` (jejak percobaan siswa)
Field penting:
- `problemid`
- `userid` (nullable untuk anonymized run)
- `code_hash`
- `is_anonymous`
- `result_json`
- `used_hints_json`
- `ai_feedback_json` (field tersedia, belum aktif dipakai pada alur utama)
- `timecreated`

## Tabel `aicode_cache` (cache feedback AI)
Field:
- `payload_hash` (unik)
- `ai_response_json`
- `timecreated`

## Tabel `aicode_teacher_overrides`
Tujuan desain:
- menyimpan koreksi guru atas feedback AI.

Status:
- tabel sudah ada, alur override belum terhubung penuh ke UI/API utama saat ini.

## Tabel `aicode_materials`
Tujuan desain:
- katalog materi rekomendasi.

Status:
- tabel sudah ada, belum dipakai aktif oleh endpoint utama (rekomendasi saat ini datang dari AI/fallback hardcoded).

---

## 12) Privasi, Anonimisasi, dan GDPR

Plugin menyediakan provider Privacy API Moodle:
- deklarasi metadata data personal di `aicode_attempts`,
- deklarasi external location ke Moodle AI provider,
- export data user,
- delete data user per context/modul.

Kebijakan teknis implementasi:
- run attempt normal menyimpan `code_hash` (bukan kode penuh),
- jika `allow_training` aktif:
  - attempt run disimpan anonymized (`userid = null`, `is_anonymous = 1`),
- analisis AI melakukan anonymize ringan:
  - hapus komentar,
  - mask email.

Pengecualian penting:
- endpoint `send_to_teacher` menyimpan kode penuh di `result_json` agar guru bisa review.

---

## 13) Integrasi Gradebook

Yang sudah ada:
- saat add/update aktivitas, plugin membuat/memperbarui grade item (0..100).

Yang belum lengkap:
- kalkulasi nilai siswa berbasis attempts belum diimplementasi (masih TODO).
- akibatnya, integrasi gradebook ada di level item, tetapi belum ada engine penilaian otomatis final.

---

## 14) Fitur Dokumentasi & Konten Siap Pakai

Di dalam plugin sudah tersedia paket konten edukasi:
- contoh soal JSON (`sample_problems.json`)
- bank 12 quiz JavaScript (`javascript_quiz_examples.json`)
- gallery interaktif HTML (`examples/quiz_gallery.html`)
- dokumen panduan Indonesia (quick start, lesson plan, reference card, dll)
- diagram arsitektur (Mermaid + SVG)

Manfaat:
- guru dapat langsung copy/paste quiz tanpa menyusun dari nol.

---

## 15) Status Fitur (Aktif vs Parsial vs Belum)

## Aktif dan berjalan
- create/update/delete aktivitas AICode
- UI editor JS + preview iframe
- run code via executor eksternal
- AI analysis via Moodle AI + fallback
- caching respons AI
- rate limit AI call harian
- tracking hint usage
- submit code ke teacher review
- mode training/exam
- privacy export/delete data

## Parsial / terbatas
- exam mode masih mengizinkan run; fokusnya menonaktifkan AI/hint untuk siswa
- hint level secara konsep ada, tetapi implementasi frontend saat ini hanya kirim level 1
- teacher detection di frontend berbasis capability `viewattempts` (bukan role object terpisah)

## Belum lengkap / belum terhubung
- notifikasi otomatis ke guru saat submit (TODO)
- view attempts UI khusus plugin tidak terlihat sebagai halaman terpisah di implementasi saat ini
- override feedback (`aicode_teacher_overrides`) belum terpakai end-to-end
- tabel `aicode_materials` belum terintegrasi aktif ke flow rekomendasi
- field `ai_feedback_json` belum dipakai untuk persist feedback AI di endpoint analyze
- setting `execution_timeout` belum dipakai langsung pada request run
- kalkulasi nilai akhir otomatis belum ada

---

## 16) Batasan Teknis Penting yang Wajib Diketahui AI Lain

1. **Bahasa efektif saat ini JavaScript-only**
   - form hanya menyediakan pilihan JavaScript.

2. **Executor adalah dependency kritikal**
   - tanpa service executor, fitur run gagal.

3. **AI provider harus aktif di Moodle AI subsystem**
   - jika tidak, sistem fallback ke rule-based feedback.

4. **Pre-filter keamanan di frontend bukan pengaman final**
   - validasi pattern berbahaya di JS client bisa diakali jika API dipanggil langsung.

5. **Rate limit bergantung tabel attempts**
   - implementasi hitung berdasarkan record `userid` di attempts.

6. **Konsistensi dokumentasi lama vs implementasi**
   - beberapa dokumen menyebut fitur (mis. 3-level hints, view attempts, override feedback) yang secara implementasi saat ini belum sepenuhnya aktif.

7. **Interaksi `allow_training` vs rate limit**
   - run attempt anonymized menyimpan `userid = null`, sementara rate limit cek berdasarkan `userid`, sehingga perilaku limit bisa tidak sepenuhnya merepresentasikan semua run pada mode anonymized.

8. **Deskripsi soal ditampilkan sebagai plain text**
   - walaupun banyak contoh memakai HTML di dokumentasi konten, renderer aktivitas memproses deskripsi sebagai teks biasa pada implementasi saat ini.

9. **Pencatatan hint belum benar-benar "latest attempt aware"**
   - endpoint hint mencari attempt berdasarkan pasangan problem-user tanpa mekanisme urutan eksplisit terbaru, sehingga pada kondisi tertentu update hint bisa tidak selalu menempel ke run terakhir.

---

## 17) Checklist Operasional Cepat

Agar plugin benar-benar berfungsi:

- Install plugin `mod_aicode`.
- Pastikan tabel database terbuat.
- Set `executor_url` valid.
- Pastikan executor service hidup dan reachable.
- Aktifkan Moodle AI provider untuk text generation.
- Buat aktivitas AICode dengan testcases JSON valid.
- Uji sebagai siswa:
  - run sukses,
  - error memicu analisis AI/fallback (training mode),
  - hint bisa dipanggil,
  - submit ke teacher tersimpan.

---

## 18) Ringkasan Singkat untuk AI (Machine-Oriented)

- Domain: Moodle coding activity.
- Core entity: `aicode` (problem), `aicode_attempts` (run/submit records).
- External runtime: executor microservice `/run`.
- AI layer: Moodle AI `generate_text` + strict JSON schema + fallback heuristics.
- Frontend mode:
  - training: AI feedback/hint aktif,
  - exam student: hint hidden, AI feedback tidak ditampilkan.
- Data privacy:
  - hash-based attempt storage for run,
  - optional anonymization for training data,
  - full code stored only on teacher submission path.
- Maturity note:
  - beberapa komponen data model disiapkan untuk roadmap (materials/teacher overrides) namun belum terhubung penuh.

---

## 19) Kesimpulan

`mod_aicode` adalah plugin activity Moodle untuk pembelajaran coding berbasis JavaScript dengan kombinasi:
- eksekusi kode eksternal,
- diagnosis kesalahan berbasis AI,
- fallback pedagogis saat AI gagal,
- pelacakan aktivitas belajar siswa.

Secara arsitektur plugin sudah kuat untuk use case latihan coding, namun masih menyisakan area penguatan di:
- otomasi penilaian,
- review workflow guru,
- penyelarasan dokumentasi fitur dengan implementasi aktual.

Dokumen ini dapat dipakai sebagai basis bagi AI lain untuk:
- memahami perilaku plugin,
- menjawab pertanyaan pengguna non-teknis,
- merancang enhancement berikutnya tanpa harus membaca source code.
