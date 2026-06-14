# DOKUMENTASI TEKNIS LENGKAP PLUGIN MOODLE AICODE
## Untuk Penulisan BAB 4 Skripsi: Hasil dan Pembahasan

**Judul Penelitian:**  
*Pengembangan Fitur Moodle Latihan Pemrograman Interaktif dengan Umpan Balik Cerdas Berbasis Large Language Model*

**Plugin:** mod_aicode v1.9.0  
**Tanggal Analisis:** 13 May 2026  
**Lokasi Source Code:** c:\laragon\www\moodle\mod\aicode\

---

## DAFTAR ISI

1. [GAMBARAN UMUM PLUGIN](#1-gambaran-umum-plugin)
2. [ARSITEKTUR SISTEM](#2-arsitektur-sistem)
3. [STRUKTUR PROJECT](#3-struktur-project)
4. [IMPLEMENTASI FITUR UTAMA](#4-implementasi-fitur-utama)
5. [DATABASE DAN DATA FLOW](#5-database-dan-data-flow)
6. [ALUR KERJA SISTEM](#6-alur-kerja-sistem)
7. [INTEGRASI DENGAN MOODLE](#7-integrasi-dengan-moodle)
8. [PENGUJIAN SISTEM](#8-pengujian-sistem)
9. [ANALISIS KELEBIHAN DAN KEKURANGAN](#9-analisis-kelebihan-dan-kekurangan)
10. [PEMBAHASAN AKADEMIK](#10-pembahasan-akademik)

---

## 1. GAMBARAN UMUM PLUGIN

### 1.1 Identitas dan Metadata Plugin

**Informasi Dasar:**
- **Nama Plugin:** mod_aicode (AICode — AI Programming Lab)
- **Versi:** 1.9.0 (Build: 2026042920)
- **Status Maturity:** MATURITY_BETA
- **Requirement:** Moodle 5.0+ (Build 20250414)
- **Lokasi:** c:\laragon\www\moodle\mod\aicode\
- **Lisensi:** GNU GPL v3 or later
- **Copyright:** 2025 AICode Team

**File Konfigurasi Utama:**
- version.php — metadata plugin dan versi
- lib.php — fungsi inti Moodle API
- settings.php — konfigurasi admin plugin
- db/install.xml — skema database
- db/access.php — capability definitions
- db/services.php — external web services

### 1.2 Tujuan dan Fungsi Utama Plugin

Plugin AICode adalah **activity module** untuk Moodle yang dirancang khusus untuk pembelajaran pemrograman JavaScript interaktif dengan dukungan **AI-powered intelligent feedback**.

**Tujuan Pedagogis:**
1. Memberikan lingkungan latihan pemrograman yang aman dan terisolasi
2. Menyediakan feedback formatif real-time berbasis AI untuk membantu siswa memahami kesalahan kode
3. Mendukung pembelajaran mandiri dengan hint bertingkat (scaffolding)
4. Memfasilitasi monitoring dan evaluasi guru terhadap progress siswa
5. Mengintegrasikan human-in-the-loop untuk meningkatkan kualitas feedback AI

**Masalah Pembelajaran yang Diselesaikan:**
- **Keterbatasan feedback manual:** Guru tidak dapat memberikan feedback real-time untuk setiap siswa
- **Kesulitan debugging:** Siswa pemula kesulitan memahami error message teknis
- **Kurangnya scaffolding:** Tidak ada panduan bertahap saat siswa stuck
- **Monitoring terbatas:** Guru sulit melacak aktivitas dan progress siswa secara detail
- **Keamanan eksekusi kode:** Risiko kode berbahaya dari siswa

### 1.3 Jenis Plugin dan Posisi dalam Moodle

**Jenis:** Activity Module (mod)

**Integrasi dengan Moodle:**
- Muncul sebagai activity yang dapat ditambahkan guru ke course
- Terintegrasi dengan Moodle gradebook untuk penilaian
- Menggunakan Moodle capability system untuk kontrol akses
- Memanfaatkan Moodle event system untuk logging
- Mendukung Moodle Privacy API (GDPR compliance)

**Fitur Moodle yang Didukung:**
\\\php
// Dari lib.php - aicode_supports()
FEATURE_MOD_INTRO          => true  // Deskripsi aktivitas
FEATURE_SHOW_DESCRIPTION   => true  // Tampil di course page
FEATURE_BACKUP_MOODLE2     => true  // Backup/restore
FEATURE_COMPLETION_TRACKS_VIEWS => true  // Activity completion
FEATURE_GRADE_HAS_GRADE    => true  // Integrasi gradebook
FEATURE_GRADE_OUTCOMES     => false // Tidak support outcomes
\\\

### 1.4 Role Pengguna dan Workflow Umum

**Role yang Didukung:**

**1. Student (mod/aicode:submit)**
- Menulis dan menjalankan kode JavaScript
- Melihat preview output (jika ada HTML/CSS template)
- Meminta AI Hint untuk mendapat feedback
- Melihat riwayat percobaan (session-based)
- Submit kode ke guru (mode exam)

**2. Teacher (mod/aicode:viewattempts)**
- Membuat soal pemrograman dengan deskripsi, starter code, test cases
- Melihat laporan semua siswa (status, percobaan, hint usage)
- Melihat detail kode yang disubmit siswa
- Memberi nilai manual
- Menambahkan catatan untuk siswa

**3. Editing Teacher (mod/aicode:overridefeedback)**
- Semua capability teacher
- Mengoreksi feedback AI yang kurang tepat
- Menandai koreksi sebagai few-shot example untuk meningkatkan AI

**4. Manager/Admin**
- Konfigurasi global plugin (API key, executor URL, security settings)
- Export activity log untuk penelitian
- Manage data retention dan privacy

---

## 2. ARSITEKTUR SISTEM

### 2.1 Arsitektur Keseluruhan

Plugin AICode menggunakan **arsitektur modular tiga-tier** dengan pemisahan jelas antara presentation, business logic, dan data layer.

**Diagram Arsitektur:**

\\\
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  view.php    │  │  report.php  │  │ mod_form.php │      │
│  │  (Student)   │  │  (Teacher)   │  │  (Settings)  │      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
│         │                  │                  │              │
│         └──────────────────┴──────────────────┘              │
│                            │                                 │
│                    ┌───────▼────────┐                        │
│                    │  renderer.php  │                        │
│                    │  (HTML Output) │                        │
│                    └───────┬────────┘                        │
└────────────────────────────┼──────────────────────────────────┘
                             │
┌────────────────────────────┼──────────────────────────────────┐
│                    BUSINESS LOGIC LAYER                       │
│                            │                                  │
│  ┌─────────────────────────▼──────────────────────────┐      │
│  │           External Web Services (AJAX)             │      │
│  │  ┌──────────────┐  ┌──────────────┐  ┌─────────┐ │      │
│  │  │  run_code    │  │analyze_code  │  │  hint   │ │      │
│  │  └──────┬───────┘  └──────┬───────┘  └────┬────┘ │      │
│  └─────────┼──────────────────┼───────────────┼──────┘      │
│            │                  │               │              │
│  ┌─────────▼──────┐  ┌────────▼────────┐  ┌──▼──────────┐  │
│  │ security_      │  │   ai_prompt     │  │ activity_   │  │
│  │ checker.php    │  │   .php          │  │ log.php     │  │
│  │ (Validation)   │  │ (Prompt Build)  │  │ (Logging)   │  │
│  └────────────────┘  └─────────────────┘  └─────────────┘  │
└────────────────────────────────────────────────────────────┘
                             │
┌────────────────────────────┼──────────────────────────────────┐
│                       DATA LAYER                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │   aicode     │  │aicode_       │  │aicode_       │       │
│  │   (Problems) │  │attempts      │  │cache         │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
│  ┌──────────────┐  ┌──────────────┐                         │
│  │aicode_       │  │aicode_       │                         │
│  │teacher_      │  │activity_log  │                         │
│  │overrides     │  │              │                         │
│  └──────────────┘  └──────────────┘                         │
└────────────────────────────────────────────────────────────┘
                             │
┌────────────────────────────┼──────────────────────────────────┐
│                    EXTERNAL SERVICES                          │
│  ┌──────────────────┐           ┌──────────────────┐         │
│  │  Executor Service│           │  Gemini AI API   │         │
│  │  (Node.js vm2)   │           │  (Google)        │         │
│  │  Port 3001       │           │  REST API        │         │
│  └──────────────────┘           └──────────────────┘         │
└────────────────────────────────────────────────────────────┘
\\\

**Komponen Utama:**

1. **Frontend (Client-side)**
   - amd/src/editor.js — JavaScript module untuk editor interaktif
   - Textarea dengan syntax highlighting overlay
   - Preview iframe dengan sandbox
   - AJAX calls ke Moodle web services

2. **Backend (Server-side)**
   - PHP classes di classes/external/ — web service handlers
   - PHP classes di classes/local/ — business logic helpers
   - lib.php — Moodle API implementations

3. **Database**
   - 5 tabel utama
   - Relasi foreign key ke Moodle core tables

4. **External Services**
   - **Executor:** Microservice Node.js untuk run kode (isolated)
   - **Gemini AI:** Google Generative AI API untuk feedback

---

Dokumentasi ini akan dilanjutkan dengan bagian-bagian berikutnya...

