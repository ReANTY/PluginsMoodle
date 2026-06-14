# DOKUMENTASI TEKNIS LENGKAP PLUGIN MOODLE AICODE
## Untuk Penulisan BAB 4 Skripsi: Hasil dan Pembahasan

**Judul Penelitian:**  
*Pengembangan Fitur Moodle Latihan Pemrograman Interaktif dengan Umpan Balik Cerdas Berbasis Large Language Model*

**Plugin:** mod_aicode v1.9.0  
**Tanggal Analisis:** 13 Mei 2026  
**Lokasi Source Code:** `c:\laragon\www\moodle\mod\aicode\`  
**Penulis Dokumentasi:** AI Assistant (Analisis Source Code)

---

## RINGKASAN EKSEKUTIF

Plugin AICode adalah activity module Moodle yang mengintegrasikan editor kode JavaScript interaktif dengan sistem feedback cerdas berbasis Large Language Model (Gemini AI). Plugin ini dikembangkan untuk mengatasi keterbatasan feedback manual guru dalam pembelajaran pemrograman dengan menyediakan umpan balik formatif real-time, sandbox execution yang aman, hint bertingkat, dan dashboard analytics untuk monitoring progress siswa.

**Fitur Utama yang Diimplementasikan:**
1. ✅ Editor kode JavaScript dengan syntax highlighting
2. ✅ Executor sandbox berbasis Node.js vm2 (microservice terpisah)
3. ✅ AI Analyzer menggunakan Gemini 2.0 Flash API
4. ✅ Hint bertingkat dengan tracking usage
5. ✅ Dashboard guru dengan laporan detail per siswa
6. ✅ Teacher override untuk koreksi feedback AI (few-shot learning)
7. ✅ Anonymization sebelum request ke LLM
8. ✅ Activity logging untuk penelitian
9. ✅ Autosave dan riwayat kode (session-based)
10. ✅ Security checker server-side (static analysis)
11. ✅ Mode Training dan Exam
12. ✅ Integrasi Moodle gradebook

**Statistik Implementasi:**
- Total baris kode: ~20,000+ baris
- File PHP: 25+ files
- File JavaScript: 1 file utama (13,000+ baris)
- Database tables: 5 tabel
- External services: 5 AJAX endpoints
- Capabilities: 5 permissions
- Language strings: 100+ strings (EN + ID)

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
- **Nama Plugin:** `mod_aicode` (AICode — AI Programming Lab)
- **Versi:** 1.9.0 (Build: 2026042920)
- **Status Maturity:** MATURITY_BETA
- **Requirement:** Moodle 5.0+ (Build 20250414)
- **Lokasi:** `c:\laragon\www\moodle\mod\aicode\`
- **Lisensi:** GNU GPL v3 or later
- **Copyright:** 2025 AICode Team

**File Konfigurasi Utama:**
- `version.php` — metadata plugin dan versi
- `lib.php` — fungsi inti Moodle API (500+ baris)
- `settings.php` — konfigurasi admin plugin
- `db/install.xml` — skema database (5 tabel)
- `db/access.php` — capability definitions (5 capabilities)
- `db/services.php` — external web services (5 services)

**Bukti Implementasi:**
```php
// File: version.php (baris 24-29)
$plugin->version   = 2026042920;        // Build timestamp
$plugin->requires  = 2025041400;        // Minimum Moodle 5.0
$plugin->component = 'mod_aicode';      // Full component name
$plugin->maturity  = MATURITY_BETA;     // Development status
$plugin->release   = '1.9.0';           // Human-readable version
```

### 1.2 Tujuan dan Fungsi Utama Plugin

Plugin AICode adalah **activity module** untuk Moodle yang dirancang khusus untuk pembelajaran pemrograman JavaScript interaktif dengan dukungan **AI-powered intelligent feedback**.

**Tujuan Pedagogis:**

1. **Memberikan lingkungan latihan pemrograman yang aman dan terisolasi**
   - Kode siswa dijalankan di sandbox terpisah (Node.js vm2)
   - Mencegah akses ke sistem file, network, atau OS commands
   - Timeout protection untuk infinite loops
   - Resource limitation

2. **Menyediakan feedback formatif real-time berbasis AI**
   - Gemini AI menganalisis error dan memberikan diagnosis
   - Hints dalam Bahasa Indonesia yang mudah dipahami siswa SMA/SMK
   - Suggested fix dengan penjelasan step-by-step
   - Confidence scoring untuk validasi kualitas feedback

3. **Mendukung pembelajaran mandiri dengan hint bertingkat**
   - Cognitive scaffolding melalui hints yang progressively revealing
   - Tracking hint usage untuk analytics
   - Tidak ada penalty untuk menggunakan hint (formative assessment)

4. **Memfasilitasi monitoring dan evaluasi guru**
   - Dashboard dengan metrics: jumlah percobaan, hint usage, status pengerjaan
   - Melihat kode terakhir yang disubmit siswa
   - Riwayat semua percobaan dengan timestamp
   - Export activity log untuk penelitian

5. **Mengintegrasikan human-in-the-loop**
   - Guru dapat mengoreksi feedback AI yang kurang tepat
   - Rating kualitas feedback AI (1-5)
   - Few-shot learning: koreksi guru diinjeksikan ke prompt AI berikutnya

**Masalah Pembelajaran yang Diselesaikan:**

| Masalah | Solusi Plugin AICode | Implementasi Teknis |
|---------|---------------------|---------------------|
| **Keterbatasan feedback manual** | AI memberikan feedback instant 24/7 | `classes/external/analyze_code.php` - Gemini API integration |
| **Kesulitan debugging** | AI menerjemahkan error teknis ke Bahasa Indonesia | `classes/local/ai_prompt.php` - Prompt engineering dengan contoh few-shot |
| **Kurangnya scaffolding** | Hint bertingkat dari general ke specific | `classes/external/record_hint.php` - Tracking hint usage |
| **Monitoring terbatas** | Activity log mencatat setiap interaksi | `classes/local/activity_log.php` - Metadata logging |
| **Keamanan eksekusi kode** | Security checker + sandbox executor | `classes/local/security_checker.php` + Executor service |

### 1.3 Jenis Plugin dan Posisi dalam Moodle

**Jenis:** Activity Module (`mod`)

Plugin AICode adalah **activity module**, artinya:
- Muncul di menu "Add an activity or resource" saat guru mengedit course
- Memiliki instance tersendiri per aktivitas (bukan global seperti block atau theme)
- Terintegrasi penuh dengan Moodle course structure dan gradebook
- Mendukung backup/restore Moodle

**Integrasi dengan Moodle Core:**

```php
// File: lib.php - aicode_supports() (baris 29-47)
function aicode_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;  // Deskripsi aktivitas
        case FEATURE_SHOW_DESCRIPTION:
            return true;  // Tampil di course page
        case FEATURE_BACKUP_MOODLE2:
            return true;  // Backup/restore
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;  // Activity completion
        case FEATURE_GRADE_HAS_GRADE:
            return true;  // Integrasi gradebook
        case FEATURE_GRADE_OUTCOMES:
            return false; // Tidak support outcomes
        default:
            return null;
    }
}
```

**Capability System:**

Plugin mendefinisikan 5 capabilities untuk kontrol akses:

```php
// File: db/access.php (baris 20-68)
$capabilities = [
    'mod/aicode:addinstance' => [
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    'mod/aicode:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'guest' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    'mod/aicode:submit' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'student' => CAP_ALLOW,
        ],
    ],
    'mod/aicode:viewattempts' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    'mod/aicode:overridefeedback' => [
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];
```

**Penjelasan Capabilities:**

1. **`mod/aicode:addinstance`**
   - **Fungsi:** Membuat aktivitas AICode baru di course
   - **Role:** Editing teacher, Manager
   - **Risk:** RISK_XSS (karena bisa input HTML di deskripsi)

2. **`mod/aicode:view`**
   - **Fungsi:** Melihat aktivitas AICode
   - **Role:** Semua role termasuk guest
   - **Risk:** Tidak ada

3. **`mod/aicode:submit`**
   - **Fungsi:** Menjalankan kode dan request AI feedback
   - **Role:** Student
   - **Risk:** RISK_SPAM (rate limiting diterapkan)

4. **`mod/aicode:viewattempts`**
   - **Fungsi:** Melihat laporan semua siswa
   - **Role:** Teacher, Editing teacher, Manager
   - **Risk:** Tidak ada

5. **`mod/aicode:overridefeedback`**
   - **Fungsi:** Mengoreksi feedback AI
   - **Role:** Editing teacher, Manager
   - **Risk:** RISK_SPAM

### 1.4 Role Pengguna dan Workflow Umum

**Role yang Didukung:**

#### 1. Student (`mod/aicode:submit`)

**Aktivitas yang dapat dilakukan:**
- Menulis dan menjalankan kode JavaScript di editor
- Melihat preview output (jika guru menyediakan HTML/CSS template)
- Meminta AI Hint untuk mendapat feedback saat ada error
- Melihat riwayat percobaan dalam sesi (session-based, tidak persisten)
- Submit kode ke guru (mode exam)

**Implementasi Teknis:**
- Editor: `amd/src/editor.js` (13,000+ baris)
- Run code: `classes/external/run_code.php`
- AI Hint: `classes/external/analyze_code.php`
- Submit: `classes/external/send_to_teacher.php`

#### 2. Teacher (`mod/aicode:viewattempts`)

**Aktivitas yang dapat dilakukan:**
- Membuat soal pemrograman dengan deskripsi HTML-formatted
- Mengatur starter code, HTML/CSS template (opsional)
- Memilih mode: Training (AI aktif) atau Exam (tanpa AI untuk siswa)
- Melihat laporan semua siswa (status, jumlah percobaan, hint usage)
- Melihat detail kode yang disubmit siswa
- Memberi nilai manual (0-100) yang tersinkron ke gradebook
- Menambahkan catatan untuk siswa

**Implementasi Teknis:**
- Form settings: `mod_form.php`
- Teacher report: `report.php` (1,000+ baris)
- Grading: `lib.php` - `aicode_set_user_grade()`

#### 3. Editing Teacher (`mod/aicode:overridefeedback`)

**Semua capability teacher, plus:**
- Mengoreksi feedback AI yang kurang tepat
- Memberi rating kualitas feedback AI (1-5)
- Menandai koreksi sebagai few-shot example untuk meningkatkan AI

**Implementasi Teknis:**
- Correction form: `report.php` (action=savecorrection)
- Storage: `aicode_teacher_overrides` table
- Few-shot injection: `classes/external/analyze_code.php` - `get_teacher_correction_examples()`

#### 4. Manager/Admin

**Aktivitas yang dapat dilakukan:**
- Konfigurasi global plugin:
  - Gemini API key
  - Executor service URL
  - AI prompt template default
  - Security checker settings
  - Rate limiting
  - Cache TTL
- Export activity log untuk penelitian (CSV format)
- Purge activity log (GDPR compliance)

**Implementasi Teknis:**
- Admin settings: `settings.php`
- Activity log management: `activity_log_manage.php`

**Workflow Umum Sistem:**

```
┌─────────────────────────────────────────────────────────────┐
│                         GURU                                 │
└─────────────────────────────────────────────────────────────┘
  1. Buat aktivitas AICode di course
  2. Tulis deskripsi soal (HTML formatted)
  3. Set starter code, HTML/CSS template (opsional)
  4. Pilih mode: Training (AI aktif) atau Exam (tanpa AI)
  5. Publish aktivitas
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                    SISWA - Mode Training                     │
└─────────────────────────────────────────────────────────────┘
  1. Buka aktivitas, baca deskripsi soal
  2. Tulis kode JavaScript di editor
  3. Klik "Run" → kode dikirim ke executor service
  4. Lihat output/error di panel Problems dan Preview
  5. Jika ada error → klik "AI Hint" → dapat feedback dari Gemini
  6. Perbaiki kode berdasarkan hint
  7. Ulangi sampai berhasil
  8. (Opsional) Klik "Submit" untuk kirim ke guru
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     SISWA - Mode Exam                        │
└─────────────────────────────────────────────────────────────┘
  1. Buka aktivitas, baca deskripsi soal
  2. Tulis kode JavaScript di editor
  3. Klik "Run" → hanya preview lokal, tidak ada AI feedback
  4. Klik "Submit" → kode tersimpan untuk review guru
  5. Tidak bisa submit ulang setelah submit pertama
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                      GURU - Review                           │
└─────────────────────────────────────────────────────────────┘
  1. Buka "Laporan Guru" dari menu aktivitas
  2. Lihat tabel semua siswa (status, jumlah percobaan, hint usage)
  3. Klik nama siswa → lihat detail:
     - Kode terakhir yang disubmit
     - Output console
     - Feedback AI terakhir (jika ada)
     - Riwayat semua percobaan
  4. Beri nilai manual (0-100)
  5. Tambahkan catatan guru
  6. (Opsional) Koreksi feedback AI jika kurang tepat
  7. Centang "use as example" → koreksi jadi few-shot learning
```

---


## 2. ARSITEKTUR SISTEM

### 2.1 Arsitektur Keseluruhan

Plugin AICode menggunakan **arsitektur modular tiga-tier** dengan pemisahan jelas antara presentation layer, business logic layer, dan data layer. Arsitektur ini mengikuti prinsip **separation of concerns** dan **single responsibility principle**.

**Diagram Arsitektur:**

```
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
```

**Penjelasan Layer:**

**1. Presentation Layer**
- **Fungsi:** Menangani interaksi user dan rendering HTML
- **Komponen:**
  - `view.php` — Halaman utama siswa (editor + preview)
  - `report.php` — Dashboard guru (laporan + grading)
  - `mod_form.php` — Form settings aktivitas
  - `renderer.php` — HTML renderer dengan inline CSS
- **Teknologi:** PHP, Moodle Output API, HTML5, CSS3

**2. Business Logic Layer**
- **Fungsi:** Memproses request, validasi, dan orchestration
- **Komponen:**
  - **External Services** (AJAX endpoints):
    - `run_code.php` — Eksekusi kode via executor
    - `analyze_code.php` — AI feedback via Gemini
    - `record_hint.php` — Logging hint usage
    - `send_to_teacher.php` — Submit untuk review
    - `get_run_history.php` — Retrieve history
  - **Helper Classes**:
    - `security_checker.php` — Static code analysis
    - `ai_prompt.php` — Prompt template engine
    - `activity_log.php` — Research logging
- **Teknologi:** PHP, Moodle External API, cURL

**3. Data Layer**
- **Fungsi:** Persistensi data dan query optimization
- **Komponen:**
  - 5 tabel database (detail di bagian 5)
  - Moodle Database API (`$DB`)
  - Foreign key constraints
  - Indexes untuk performance
- **Teknologi:** MySQL/PostgreSQL, XMLDB

**4. External Services**
- **Executor Service:**
  - Microservice Node.js terpisah
  - Port 3001 (configurable)
  - Sandbox: vm2 library
  - Timeout: 2 detik (configurable)
- **Gemini AI API:**
  - Google Generative AI
  - Model: gemini-2.0-flash (configurable)
  - Temperature: 0.2 (deterministic)
  - Max tokens: 2048

### 2.2 Arsitektur Frontend

**Teknologi Stack:**
- **AMD Module Pattern** (Moodle standard)
- **jQuery 3.x** untuk DOM manipulation dan AJAX
- **Vanilla JavaScript** untuk editor logic
- **CSS3** untuk styling (inline di renderer.php)
- **PostMessage API** untuk iframe communication

**Komponen Frontend:**

```javascript
// File: amd/src/editor.js (13,000+ baris)

// ═══════════════════════════════════════════════════════════
// STATE MANAGEMENT
// ═══════════════════════════════════════════════════════════
let editor = null;                    // Textarea element
let config = {};                      // Configuration object
let cachedFeedback = null;            // AI feedback cache
let lastAnalysisInput = null;         // Last error payload
let hintRequested = false;            // Hint button state
let previewFrame = null;              // Iframe element
let historyItems = [];                // Session history
let problemStats = {error:0, warning:0, info:0}; // Problem counts

// ═══════════════════════════════════════════════════════════
// CORE FUNCTIONS
// ═══════════════════════════════════════════════════════════
initEditor()                          // Initialize all components
setupEventHandlers()                  // Attach button listeners
handleRun()                           // Run button logic
handleHint()                          // AI Hint button logic
handleReset()                         // Reset to starter code
handleSendToTeacher()                 // Submit to teacher
runPreview(html, css, js)             // Render iframe preview
addProblem(type, msg, line, col)      // Add to Problems panel
startAIFeedbackAnalysis(payload)      // Request AI feedback
```

**Editor Implementation:**

Plugin menggunakan **textarea overlay technique** untuk syntax highlighting tanpa library eksternal:

```
┌─────────────────────────────────────┐
│  Line Numbers Pane (48px width)    │
│  - Generated dynamically            │
│  - Synced scroll with editor        │
│  - User-select: none                │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│  Highlight Overlay (absolute)       │
│  - <pre> with colored tokens        │
│  - Pointer-events: none             │
│  - Synced scroll with editor        │
│  ┌───────────────────────────────┐  │
│  │  Textarea (transparent text)  │  │
│  │  - Actual input element       │  │
│  │  - Color: transparent         │  │
│  │  - Caret-color: visible       │  │
│  │  - Z-index: 1                 │  │
│  └───────────────────────────────┘  │
└─────────────────────────────────────┘
```

**Implementasi Teknis:**

```javascript
// Syntax Highlighting (regex-based tokenization)
function highlightCode(code) {
    let html = code
        // Comments
        .replace(/\/\/.*$/gm, '<span class="tok-comment">$&</span>')
        .replace(/\/\*[\s\S]*?\*\//g, '<span class="tok-comment">$&</span>')
        // Strings
        .replace(/(["'`])(?:(?!\1)[^\\]|\\.)*?\1/g, '<span class="tok-string">$&</span>')
        // Keywords
        .replace(/\b(function|const|let|var|if|else|for|while|return|class|new|this|async|await)\b/g, 
                 '<span class="tok-keyword">$&</span>')
        // Numbers
        .replace(/\b\d+\.?\d*\b/g, '<span class="tok-number">$&</span>')
        // Booleans
        .replace(/\b(true|false|null|undefined)\b/g, '<span class="tok-boolean">$&</span>')
        // Built-ins
        .replace(/\b(console|document|window|Math|Array|Object|String|Number)\b/g, 
                 '<span class="tok-builtin">$&</span>');
    
    return html;
}

// Scroll Sync
editor.addEventListener('scroll', function() {
    lineNumberPane.scrollTop = editor.scrollTop;
    highlightPane.scrollTop = editor.scrollTop;
    highlightPane.scrollLeft = editor.scrollLeft;
});
```

**Preview Iframe:**

```javascript
// Sandbox Attributes
<iframe 
    id="aicode-preview-iframe"
    sandbox="allow-scripts"
    referrerpolicy="no-referrer"
    srcdoc="..."
></iframe>

// PostMessage Communication
window.addEventListener("message", function(event) {
    if (event.source !== previewFrame.contentWindow) return;
    
    const data = event.data || {};
    if (data.source !== "aicode-preview") return;
    
    if (data.type === "console") {
        // Handle console.log/warn/error
        addOutput(data.payload.level, data.payload.args.join(" "));
    }
    
    if (data.type === "error") {
        // Handle runtime errors
        addProblem("error", data.payload.message, null, null);
    }
});
```

**Loop Protection:**

```javascript
// Injected ke iframe sebelum user code
var __lpStart = Date.now();
window.__loopProtect = function() {
    if (Date.now() - __lpStart > 500) {
        throw new Error("Loop protector: kemungkinan infinite loop (stop setelah 500ms).");
    }
};

// Inserted before every loop (regex-based)
for(...) { __loopProtect(); ... }
while(...) { __loopProtect(); ... }
do { __loopProtect(); ... } while(...);
```

### 2.3 Arsitektur Backend

**Service Layer Pattern:**

Plugin menggunakan **service layer pattern** untuk memisahkan business logic dari controller:

```php
// External Web Services (classes/external/)
// Semua extend external_api dan implement execute()

run_code.php          → execute($problemid, $code, $language, $sesskey)
analyze_code.php      → execute($problemid, $code, $stderr, $trace, $sesskey)
record_hint.php       → execute($problemid, $sesskey)
send_to_teacher.php   → execute($problemid, $code, $consoleoutput, $sesskey)
get_run_history.php   → execute($problemid, $sesskey)

// Helper Classes (classes/local/)
security_checker.php  → analyze($code, $language, $blocklevel): array
ai_prompt.php         → render_template($template, $code, $stderr, $trace): string
activity_log.php      → record($context, $problemid, $userid, $action, $meta): void
```

**Request Flow (Run Code):**

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Frontend: User clicks "Run"                              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. AJAX call: mod_aicode_run_code                           │
│    - problemid, code, language, sesskey                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. run_code::execute()                                      │
│    ├─ Validate parameters (external_api)                    │
│    ├─ Check capability (mod/aicode:submit)                  │
│    ├─ Validate input length (max 50KB)                      │
│    ├─ Security check (security_checker::analyze)            │
│    │  ├─ If blocked → return error + log attempt            │
│    │  └─ If safe → continue                                 │
│    ├─ HTTP POST to executor service                         │
│    │  URL: http://127.0.0.1:3001/run                        │
│    │  Payload: {code, language, testcase}                   │
│    │  Timeout: 5 seconds                                    │
│    ├─ Parse executor response (JSON)                        │
│    ├─ Store attempt in aicode_attempts table                │
│    │  - code_hash (SHA256)                                  │
│    │  - result_json (exitCode, stdout, stderr, code)        │
│    │  - security_flags (if any)                             │
│    │  - is_anonymous (based on allow_training)              │
│    ├─ Log activity (activity_log::record)                   │
│    │  - ACTION_CODE_RUN                                     │
│    │  - metadata: {exitcode, language, anonymous_attempt}   │
│    └─ Return JSON result to frontend                        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Frontend: Display output/errors                          │
│    - Parse result JSON                                      │
│    - If exitCode !== 0 → show in Problems panel             │
│    - If stdout → show in Output panel                       │
│    - If security_blocked → show security warning            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. If error → Frontend can request AI Hint                  │
└─────────────────────────────────────────────────────────────┘
```

**Implementasi run_code.php (excerpt):**

```php
// File: classes/external/run_code.php (baris 60-150)

public static function execute($problemid, $code, $language, $sesskey) {
    global $DB, $USER, $CFG, $PAGE;

    // Validate parameters
    $params = self::validate_parameters(self::execute_parameters(), [
        'problemid' => $problemid,
        'code' => $code,
        'language' => $language,
        'sesskey' => $sesskey,
    ]);

    // Validate session key
    if (!empty($params['sesskey']) && !confirm_sesskey($params['sesskey'])) {
        throw new \moodle_exception('invalidsesskey');
    }

    // Get problem and validate access
    $problem = $DB->get_record('aicode', ['id' => $params['problemid']], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('aicode', $problem->id);
    $context = \context_module::instance($cm->id);
    self::validate_context($context);
    $PAGE->set_context($context);

    require_capability('mod/aicode:submit', $context);

    // Validate input length
    if (strlen($params['code']) > 50000) {
        throw new \moodle_exception('Code too long (max 50KB)');
    }

    // Server-side security check
    $securityresult = null;
    if ((bool) get_config('aicode', 'security_check_enabled')) {
        $blocklevel = get_config('aicode', 'security_block_level') ?: 'high';
        $securityresult = \mod_aicode\local\security_checker::analyze(
            $params['code'],
            $params['language'],
            $blocklevel
        );

        if ($securityresult['blocked']) {
            // Record blocked attempt
            $blockedattempt = new \stdClass();
            $blockedattempt->problemid = $params['problemid'];
            $blockedattempt->userid = $USER->id;
            $blockedattempt->is_anonymous = 0;
            $blockedattempt->code_hash = hash('sha256', $params['code']);
            $blockedattempt->result_json = json_encode([
                'exitCode' => -2,
                'security_blocked' => true,
                'risk_level' => $securityresult['risk_level'],
            ]);
            $blockedattempt->security_flags = json_encode($securityresult);
            $blockedattempt->timecreated = time();
            $DB->insert_record('aicode_attempts', $blockedattempt);

            // Log activity
            \mod_aicode\local\activity_log::record(
                $context,
                (int) $params['problemid'],
                (int) $USER->id,
                \mod_aicode\local\activity_log::ACTION_CODE_RUN_BLOCKED,
                [
                    'risk_level' => (string) ($securityresult['risk_level'] ?? ''),
                    'violations_count' => count($securityresult['violations']),
                    'language' => (string) $params['language'],
                ],
                $problem
            );

            // Return error with violation details
            $messages = array_map(
                static function (array $v): string {
                    return "[Baris {$v['line']}] {$v['message']}";
                },
                $securityresult['violations']
            );

            return [
                'result' => json_encode([
                    'stdout' => '',
                    'stderr' => implode("\n", $messages),
                    'exitCode' => -2,
                    'trace' => '',
                    'security_blocked' => true,
                    'risk_level' => $securityresult['risk_level'],
                    'violations' => $securityresult['violations'],
                ]),
            ];
        }
    }

    // Get executor URL from config
    $executorurl = get_config('aicode', 'executor_url');
    if (empty($executorurl)) {
        $executorurl = 'http://127.0.0.1:3001';
    }

    // Prepare request payload
    $payload = [
        'code' => $params['code'],
        'language' => $params['language'],
        'testcase' => json_decode($problem->testcases ?? '[]', true),
    ];

    // Send to executor service
    $curl = new \curl(['ignoresecurity' => true]);
    $curl->setHeader(['Content-Type: application/json']);
    $response = $curl->post($executorurl . '/run', json_encode($payload));

    if ($curl->get_errno()) {
        throw new \moodle_exception('Executor service unavailable: ' . $curl->error);
    }

    $result = json_decode($response, true);
    if (!is_array($result)) {
        $result = [
            'stdout' => '',
            'stderr' => 'Executor returned empty or invalid response.',
            'exitCode' => 1,
            'trace' => '',
        ];
    }

    // Store attempt
    $attempt = new \stdClass();
    $attempt->problemid = $params['problemid'];
    $attempt->userid = $problem->allow_training ? null : $USER->id;
    $attempt->is_anonymous = $problem->allow_training ? 1 : 0;
    $attempt->code_hash = hash('sha256', $params['code']);

    $resultdata = [
        'exitCode' => $result['exitCode'] ?? -1,
        'stdout' => substr($result['stdout'] ?? '', 0, 1000),
        'stderr' => substr($result['stderr'] ?? '', 0, 1000),
    ];
    
    // Store code for identified attempts
    if (!$problem->allow_training) {
        $resultdata['code'] = substr($params['code'], 0, 20000);
    }

    $attempt->result_json = json_encode($resultdata);
    $attempt->security_flags = ($securityresult !== null && !$securityresult['safe'])
        ? json_encode($securityresult)
        : null;
    $attempt->timecreated = time();
    $DB->insert_record('aicode_attempts', $attempt);

    // Log activity
    \mod_aicode\local\activity_log::record(
        $context,
        (int) $params['problemid'],
        (int) $USER->id,
        \mod_aicode\local\activity_log::ACTION_CODE_RUN,
        [
            'exitcode' => (int) ($result['exitCode'] ?? -1),
            'language' => (string) $params['language'],
            'anonymous_attempt' => $problem->allow_training ? 1 : 0,
        ],
        $problem
    );

    return [
        'result' => json_encode($result),
    ];
}
```

---



**Request Flow (AI Hint):**

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Frontend: User clicks "AI Hint"                          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. AJAX call: mod_aicode_analyze_code                       │
│    - problemid, code, stderr, trace, sesskey                │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. analyze_code::execute()                                  │
│    ├─ Validate parameters                                   │
│    ├─ Check capability (mod/aicode:submit)                  │
│    ├─ Rate limiting check (max 50 calls/day/user)           │
│    │  Query: COUNT where userid=X AND ai_requested_at >= today│
│    ├─ Find latest attempt for this user+problem             │
│    ├─ Resolve AI prompt template                            │
│    │  Priority: activity override > global setting > default│
│    ├─ Check cache (SHA256 hash of prompt+code+error)        │
│    │  └─ If cached & fresh (TTL) → return cached feedback   │
│    ├─ Anonymize code (strip comments, emails)               │
│    ├─ Fetch teacher correction examples (few-shot)          │
│    │  Query: teacher_overrides WHERE use_as_example=1       │
│    ├─ Build AI prompt                                       │
│    │  - Inject teacher examples                             │
│    │  - Replace [[CODE]], [[STDERR]], [[TRACE]] tokens      │
│    ├─ HTTP POST to Gemini API                               │
│    │  URL: https://generativelanguage.googleapis.com/...    │
│    │  Headers: API key                                      │
│    │  Body: {contents, generationConfig}                    │
│    │  Temperature: 0.2, maxOutputTokens: 2048               │
│    ├─ Parse Gemini response                                 │
│    │  ├─ Extract JSON from markdown code block              │
│    │  ├─ Normalize feedback schema                          │
│    │  ├─ Validate confidence threshold (default 0.6)        │
│    │  └─ Build error payload if validation fails            │
│    ├─ Cache successful feedback (TTL: 3600s)                │
│    ├─ Update attempt record                                 │
│    │  - ai_requested_at = NOW()                             │
│    │  - ai_feedback_json = feedback (if success)            │
│    ├─ Log activity (activity_log::record)                   │
│    │  - ACTION_AI_ANALYZE                                   │
│    │  - metadata: {from_cache, outcome, error_code}         │
│    └─ Return JSON feedback to frontend                      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Frontend: Display feedback in panel                      │
│    - Parse feedback JSON                                    │
│    - Show diagnosis (category, confidence, message)         │
│    - Show hints (progressive reveal)                        │
│    - Show suggested fix (if available)                      │
│    - Show recommended materials (links)                     │
└─────────────────────────────────────────────────────────────┘
```

### 2.4 Pola Arsitektur yang Digunakan

Plugin AICode mengimplementasikan beberapa design pattern yang well-established:

**1. MVC (Model-View-Controller)**
- **Model:** Database tables + Moodle $DB ORM
  - `aicode`, `aicode_attempts`, `aicode_cache`, dll.
- **View:** `renderer.php` + inline HTML templates
  - `render_problem_view()` untuk student view
  - `report.php` untuk teacher dashboard
- **Controller:** PHP files + external services
  - `view.php`, `report.php` sebagai entry points
  - `classes/external/*.php` sebagai AJAX controllers

**2. Service Layer Pattern**
- Business logic dipisahkan ke service classes
- Reusable services:
  - `security_checker::analyze()` — Static code analysis
  - `ai_prompt::render_template()` — Prompt engineering
  - `activity_log::record()` — Research logging
- Benefits: testability, reusability, separation of concerns

**3. Repository Pattern (Implicit via Moodle $DB)**
- Abstraksi database access melalui Moodle Database API
- Methods: `get_record()`, `insert_record()`, `update_record()`, `delete_records()`
- Query builder untuk complex queries
- Automatic parameter binding (SQL injection prevention)

**4. Observer Pattern**
- Moodle event system untuk decoupled logging
- Events:
  - `course_module_viewed::create()` — Activity view
  - Custom events untuk tracking
- Observers: `activity_log::record()` dipanggil setelah setiap action

**5. Strategy Pattern**
- Security checker: berbeda rules per language
  - `rules_javascript()` — JavaScript-specific patterns
  - `rules_python()` — Python-specific patterns
  - `rules_java()` — Java-specific patterns
  - `rules_cpp()` — C/C++-specific patterns
- AI prompt: template bisa di-override per activity
  - Global template (settings.php)
  - Activity-specific template (mod_form.php)
  - Default template (ai_prompt.php)

**6. Facade Pattern**
- `lib.php` sebagai facade untuk Moodle core APIs
  - `aicode_add_instance()` — Wraps DB insert + grade item creation
  - `aicode_update_instance()` — Wraps DB update + grade sync
  - `aicode_delete_instance()` — Wraps cascade delete
- External web services sebagai facade untuk complex operations
  - `run_code::execute()` — Orchestrates security check + executor + logging
  - `analyze_code::execute()` — Orchestrates cache + AI + few-shot + logging

**7. Template Method Pattern**
- `external_api` base class dari Moodle
- Semua external services extend dan implement:
  - `execute_parameters()` — Define input schema
  - `execute()` — Business logic
  - `execute_returns()` — Define output schema
- Framework handles: validation, authentication, error handling

**8. Factory Pattern (Implicit)**
- Moodle core: `ModalFactory::create()` untuk modals
- Plugin: JSON decoding untuk dynamic object creation

---

## 3. STRUKTUR PROJECT

### 3.1 Struktur Folder Lengkap

```
mod/aicode/
├── amd/                          # AMD JavaScript modules
│   ├── build/                    # Compiled/minified JS
│   │   ├── editor.min.js         # Production build (minified)
│   │   └── editor.min.js.map     # Source map untuk debugging
│   └── src/                      # Source JS
│       └── editor.js             # Main editor module (13,000+ baris)
│
├── classes/                      # PHP classes (PSR-4 autoloaded)
│   ├── event/                    # Moodle events
│   │   ├── course_module_instance_list_viewed.php
│   │   └── course_module_viewed.php
│   ├── external/                 # Web service implementations
│   │   ├── analyze_code.php      # AI feedback service (600+ baris)
│   │   ├── get_run_history.php   # History retrieval
│   │   ├── record_hint.php       # Hint logging
│   │   ├── run_code.php          # Code execution service (200+ baris)
│   │   └── send_to_teacher.php   # Submit service
│   ├── local/                    # Helper classes
│   │   ├── activity_log.php      # Research logging (200+ baris)
│   │   ├── ai_prompt.php         # Prompt template engine (150+ baris)
│   │   └── security_checker.php  # Static code analysis (600+ baris)
│   └── privacy/                  # GDPR compliance
│       └── provider.php          # Privacy API implementation
│
├── db/                           # Database definitions
│   ├── access.php                # Capability definitions (5 capabilities)
│   ├── aicode_dump.sql           # Sample data (development only)
│   ├── install.xml               # Database schema (5 tables, XMLDB format)
│   ├── services.php              # External service definitions (5 services)
│   └── upgrade.php               # Database upgrade scripts
│
├── docs/                         # Documentation (HTML exports)
│   ├── architecture-diagram.html
│   ├── blackbox-testing.html
│   ├── component-diagram.puml
│   ├── figjam-plugin.html
│   ├── figma-export.html
│   └── instrumen-penelitian-lampiran.html
│
├── examples/                     # Sample problems
│   ├── javascript_quiz_examples.json
│   ├── quiz_gallery.html
│   ├── README.md
│   └── sample_problems.json
│
├── instrumen/                    # Research instruments
│   ├── 01_tes_kemampuan_pemrograman_javascript.md
│   ├── 02_kuesioner_persepsi_siswa.md
│   ├── 03_pedoman_wawancara_siswa.md
│   ├── 04_log_aktivitas_sistem_aicode.md
│   └── 05_evaluasi_usability_sus.md
│
├── javascript/                   # Third-party JS libraries
│   └── chart.min.js              # Chart.js for analytics (optional)
│
├── lang/                         # Language strings
│   ├── en/
│   │   └── aicode.php            # English strings (100+ strings)
│   └── id/
│       └── aicode.php            # Indonesian strings (100+ strings)
│
├── pix/                          # Icons
│   ├── icon.svg                  # Activity icon (24x24)
│   └── monologo.svg              # Monochrome logo
│
├── activity_log_manage.php       # Admin page for log export/purge (300+ baris)
├── index.php                     # Course activity list
├── lib.php                       # Core Moodle API functions (500+ baris)
├── mod_form.php                  # Activity settings form (200+ baris)
├── renderer.php                  # HTML output renderer (1,500+ baris)
├── report.php                    # Teacher report page (1,000+ baris)
├── settings.php                  # Admin settings page (100+ baris)
├── version.php                   # Plugin metadata
├── view.php                      # Student view page (50+ baris)
│
└── [Documentation files]
    ├── README.md
    ├── QUICK_START_INDONESIAN.md
    ├── PENJELASAN_PLUGIN_AICODE_SANGAT_DETAIL.md
    ├── INDEX_DOKUMENTASI.md
    ├── MULAI_DISINI.md
    ├── CONTOH_LESSON_PLAN.md
    ├── CONTOH_PENGGUNAAN_JAVASCRIPT.md
    ├── INSTRUMEN_PENELITIAN_SMK_JAVASCRIPT.md
    ├── REFERENCE_CARD.md
    ├── FIX_SUMMARY.md
    ├── FIGMA_IMPORT_AICODE_ARCHITECTURE.md
    ├── aicode-system-architecture.mmd
    ├── aicode-system-architecture.svg
    ├── aicode-ltsa-architecture.mmd
    ├── aicode-ltsa-architecture.svg
    ├── aicode-ltsa-architecture-learning-performance.mmd
    ├── aicode-ltsa-architecture-learning-performance.svg
    ├── architecture-flowchart.mmd
    ├── architecture-flowchart.png
    ├── architecture-flowchart.jpg
    └── architecture-flowchart-simple.mmd
```

**Statistik Project:**
- **Total files:** 80+ files
- **Total lines of code:** ~20,000+ baris
- **PHP files:** 25+ files (~8,000 baris)
- **JavaScript files:** 1 file utama (~13,000 baris)
- **Database tables:** 5 tabel
- **External services:** 5 AJAX endpoints
- **Capabilities:** 5 permissions
- **Language strings:** 100+ strings (EN + ID)
- **Documentation files:** 20+ files

### 3.2 File-File Inti dan Fungsinya

#### 3.2.1 version.php

**Fungsi:** Metadata plugin untuk Moodle plugin manager

**Lokasi:** `mod/aicode/version.php`

**Isi Penting:**
```php
$plugin->version   = 2026042920;        // Build timestamp (YYYYMMDDXX)
$plugin->requires  = 2025041400;        // Minimum Moodle 5.0
$plugin->component = 'mod_aicode';      // Full component name
$plugin->maturity  = MATURITY_BETA;     // Development status
$plugin->release   = '1.9.0';           // Human-readable version
```

**Kapan Digunakan:**
- Saat install/upgrade plugin via admin interface
- Moodle plugin manager checks compatibility
- Dependency resolution antar plugins
- Backup/restore operations

**Penjelasan Fields:**
- `version`: Build number, increment setiap update
- `requires`: Minimum Moodle version required
- `component`: Unique identifier (mod_pluginname)
- `maturity`: MATURITY_ALPHA, BETA, RC, atau STABLE
- `release`: Version string untuk display ke user

#### 3.2.2 lib.php

**Fungsi:** Implementasi Moodle API callbacks yang wajib ada

**Lokasi:** `mod/aicode/lib.php` (500+ baris)

**Functions Utama:**

```php
// ═══════════════════════════════════════════════════════════
// FEATURE SUPPORT DECLARATION
// ═══════════════════════════════════════════════════════════
aicode_supports($feature)
// Returns: true/false/null untuk FEATURE_* constants
// Mendeklarasikan fitur Moodle yang didukung plugin

// ═══════════════════════════════════════════════════════════
// CRUD OPERATIONS
// ═══════════════════════════════════════════════════════════
aicode_add_instance($aicode, $mform)
// Called when teacher creates new activity
// Returns: new activity ID
// Actions:
//   1. Validate and sanitize input
//   2. Insert record to aicode table
//   3. Create grade item in gradebook
//   4. Return new ID

aicode_update_instance($aicode, $mform)
// Called when teacher edits activity settings
// Returns: boolean success
// Actions:
//   1. Validate and sanitize input
//   2. Update record in aicode table
//   3. Update grade item in gradebook
//   4. Return success status

aicode_delete_instance($id)
// Called when activity is deleted
// Returns: boolean success
// Actions:
//   1. Delete all attempts for this problem
//   2. Delete teacher overrides (cascade)
//   3. Delete cache entries (cascade)
//   4. Delete activity log entries (cascade)
//   5. Delete grade item
//   6. Delete aicode record
//   7. Return success status

// ═══════════════════════════════════════════════════════════
// GRADING INTEGRATION
// ═══════════════════════════════════════════════════════════
aicode_grade_item_update($aicode, $grades)
// Sync with Moodle gradebook
// Returns: GRADE_UPDATE_OK or error code
// Called by: add_instance, update_instance

aicode_update_grades($aicode, $userid, $nullifnone)
// Batch grade update for all students or specific user
// Called by: cron, manual grade sync

aicode_set_user_grade($aicode, $userid, $rawgrade)
// Set individual student grade (0-100)
// Called by: report.php when teacher assigns grade
// Actions:
//   1. Create grade object
//   2. Call grade_update() with user-specific data
//   3. Return update status

// ═══════════════════════════════════════════════════════════
// NAVIGATION EXTENSION
// ═══════════════════════════════════════════════════════════
aicode_extend_settings_navigation($settingsnav, $activitynode)
// Add "Laporan Guru" link to activity settings menu
// Only visible to users with mod/aicode:viewattempts capability
// Appears next to "Settings" link in activity page
```

**Implementasi Detail:**

```php
// File: lib.php (baris 50-90)
function aicode_add_instance(stdClass $aicode, mod_aicode_mod_form $mform = null) {
    global $DB;

    $aicode->timecreated = time();
    $aicode->timemodified = time();

    // Ensure testcases are properly encoded
    if (isset($aicode->testcases)) {
        if (is_array($aicode->testcases)) {
            $aicode->testcases = json_encode($aicode->testcases);
        } else if (is_string($aicode->testcases) && !empty($aicode->testcases)) {
            // Validate it's valid JSON
            $decoded = json_decode($aicode->testcases);
            if (json_last_error() !== JSON_ERROR_NONE) {
                debugging('Invalid JSON in testcases: ' . json_last_error_msg(), DEBUG_DEVELOPER);
                $aicode->testcases = '[]'; // Default to empty array
            }
        }
    }

    // Only insert fields that exist in the database table
    $record = new stdClass();
    $record->course = $aicode->course;
    $record->name = $aicode->name;
    $record->intro = $aicode->intro ?? '';
    $record->introformat = $aicode->introformat ?? FORMAT_HTML;
    $record->description = $aicode->description ?? '';
    $record->language = $aicode->language ?? 'javascript';
    $record->testcases = $aicode->testcases ?? '';
    $record->startercode = $aicode->startercode ?? '';
    $record->htmltemplate = $aicode->htmltemplate ?? '';
    $record->csstemplate = $aicode->csstemplate ?? '';
    $record->allow_training = $aicode->allow_training ?? 0;
    $record->mode = $aicode->mode ?? 'training';
    $record->aiprompttemplate = $aicode->aiprompttemplate ?? '';
    $record->timecreated = $aicode->timecreated;
    $record->timemodified = $aicode->timemodified;

    $aicode->id = $DB->insert_record('aicode', $record);

    // Create grade item
    $record->id = $aicode->id;
    try {
        aicode_grade_item_update($record);
    } catch (Exception $e) {
        debugging('Failed to create grade item: ' . $e->getMessage(), DEBUG_DEVELOPER);
        // Continue anyway - the activity is created, just no grade item
    }

    return $aicode->id;
}
```

#### 3.2.3 settings.php

**Fungsi:** Konfigurasi admin plugin (site-wide settings)

**Lokasi:** `mod/aicode/settings.php` (100+ baris)

**Settings yang Dikonfigurasi:**

```php
// ═══════════════════════════════════════════════════════════
// GEMINI AI CONFIGURATION
// ═══════════════════════════════════════════════════════════
'aicode/gemini_api_key'
// Type: password (masked input)
// Default: '' (empty)
// Description: Google Gemini API key untuk AI feedback

'aicode/gemini_model'
// Type: text
// Default: 'gemini-2.0-flash'
// Description: Model name (gemini-2.0-flash, gemini-1.5-pro, dll)

// ═══════════════════════════════════════════════════════════
// EXECUTOR SERVICE CONFIGURATION
// ═══════════════════════════════════════════════════════════
'aicode/executor_url'
// Type: URL
// Default: 'http://127.0.0.1:3001'
// Description: URL of the code execution microservice

'aicode/execution_timeout'
// Type: int
// Default: 2
// Description: Maximum execution time in seconds

// ═══════════════════════════════════════════════════════════
// AI FEEDBACK CONFIGURATION
// ═══════════════════════════════════════════════════════════
'aicode/ai_feedback_prompt_template'
// Type: textarea
// Default: (default template dari ai_prompt.php)
// Description: Global prompt template, bisa di-override per activity

'aicode/confidence_threshold'
// Type: float
// Default: 0.6
// Description: Minimum confidence score (0.0-1.0) to accept AI feedback

'aicode/cache_ttl'
// Type: int
// Default: 3600
// Description: Cache time-to-live in seconds (1 hour)

'aicode/max_calls_per_day'
// Type: int
// Default: 50
// Description: Rate limit for AI analysis requests per student per day

// ═══════════════════════════════════════════════════════════
// SECURITY CHECK MODULE
// ═══════════════════════════════════════════════════════════
'aicode/security_check_enabled'
// Type: checkbox
// Default: 1 (enabled)
// Description: Enable/disable server-side security check

'aicode/security_block_level'
// Type: select
// Options: low, medium, high, critical
// Default: 'high'
// Description: Minimum risk level that triggers code blocking
```

**Implementasi:**

```php
// File: settings.php (baris 20-80)
if ($ADMIN->fulltree) {
    // Gemini API key
    $settings->add(new admin_setting_configpasswordunmask('aicode/gemini_api_key',
        get_string('geminiapikey', 'aicode'),
        get_string('geminiapikey_desc', 'aicode'),
        ''));

    // Gemini model
    $settings->add(new admin_setting_configtext('aicode/gemini_model',
        get_string('geminimodel', 'aicode'),
        get_string('geminimodel_desc', 'aicode'),
        'gemini-2.0-flash',
        PARAM_TEXT));

    // Executor service URL
    $settings->add(new admin_setting_configtext('aicode/executor_url',
        get_string('executorurl', 'aicode'),
        get_string('executorurl_desc', 'aicode'),
        'http://127.0.0.1:3001',
        PARAM_URL));

    // ... (settings lainnya)

    // Security Check Module
    $settings->add(new admin_setting_heading('aicode/security_heading',
        get_string('securityheading', 'aicode'),
        get_string('securityheading_desc', 'aicode')));

    $settings->add(new admin_setting_configcheckbox('aicode/security_check_enabled',
        get_string('securitycheckenabled', 'aicode'),
        get_string('securitycheckenabled_desc', 'aicode'),
        '1'));

    $blockleveloptions = [
        'low'      => get_string('securitylevel_low', 'aicode'),
        'medium'   => get_string('securitylevel_medium', 'aicode'),
        'high'     => get_string('securitylevel_high', 'aicode'),
        'critical' => get_string('securitylevel_critical', 'aicode'),
    ];
    $settings->add(new admin_setting_configselect('aicode/security_block_level',
        get_string('securityblocklevel', 'aicode'),
        get_string('securityblocklevel_desc', 'aicode'),
        'high',
        $blockleveloptions));
}
```

#### 3.2.4 db/install.xml

**Fungsi:** Database schema definition (XMLDB format)

**Lokasi:** `mod/aicode/db/install.xml`

**5 Tabel yang Didefinisikan:**

1. **`aicode`** — Problems/Activities (soal pemrograman)
2. **`aicode_attempts`** — Student submissions (percobaan siswa)
3. **`aicode_cache`** — AI response cache
4. **`aicode_teacher_overrides`** — Teacher corrections (koreksi guru)
5. **`aicode_activity_log`** — Research metadata log

**Detail akan dijelaskan di Bagian 5: DATABASE DAN DATA FLOW**

#### 3.2.5 db/access.php

**Fungsi:** Capability definitions untuk access control

**Sudah dijelaskan di Bagian 1.3**

#### 3.2.6 db/services.php

**Fungsi:** External web service definitions untuk AJAX

**Lokasi:** `mod/aicode/db/services.php`

**5 Services yang Didefinisikan:**

```php
$functions = [
    'mod_aicode_run_code' => [
        'classname'   => 'mod_aicode\external\run_code',
        'methodname'  => 'execute',
        'description' => 'Execute student code',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_aicode_analyze_code' => [
        'classname'   => 'mod_aicode\external\analyze_code',
        'methodname'  => 'execute',
        'description' => 'Analyze code with AI',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_aicode_record_hint' => [
        'classname'   => 'mod_aicode\external\record_hint',
        'methodname'  => 'execute',
        'description' => 'Record hint usage',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_aicode_send_to_teacher' => [
        'classname'   => 'mod_aicode\external\send_to_teacher',
        'methodname'  => 'execute',
        'description' => 'Send code to teacher',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_aicode_get_run_history' => [
        'classname'   => 'mod_aicode\external\get_run_history',
        'methodname'  => 'execute',
        'description' => 'Get student run history from database',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
    ],
];
```

**Penjelasan Services:**

| Service | Type | Fungsi | Capability Required |
|---------|------|--------|---------------------|
| `run_code` | write | Eksekusi kode via executor | mod/aicode:submit |
| `analyze_code` | write | Request AI feedback | mod/aicode:submit |
| `record_hint` | write | Log hint usage | mod/aicode:submit |
| `send_to_teacher` | write | Submit untuk review | mod/aicode:submit |
| `get_run_history` | read | Retrieve history | mod/aicode:submit |

**AJAX Call dari Frontend:**

```javascript
// File: amd/src/editor.js
Ajax.call([{
    methodname: 'mod_aicode_run_code',
    args: {
        problemid: problemId,
        code: code,
        language: language,
        sesskey: sesskey
    }
}])[0].then(function(response) {
    // Handle response
}).catch(Notification.exception);
```

---



## 4. IMPLEMENTASI FITUR UTAMA

### 4.1 Editor Kode JavaScript

#### 4.1.1 Gambaran Umum

Editor kode adalah komponen frontend utama yang memungkinkan siswa menulis, mengedit, dan menjalankan kode JavaScript secara interaktif. Editor diimplementasikan menggunakan **textarea overlay technique** dengan syntax highlighting real-time tanpa menggunakan library eksternal seperti CodeMirror atau Monaco Editor.

**Alasan Implementasi Custom:**
1. **Lightweight** — Tidak ada dependency eksternal, ukuran bundle lebih kecil
2. **Full control** — Kontrol penuh atas behavior dan styling
3. **Moodle compatibility** — Mengikuti AMD module pattern Moodle
4. **Performance** — Optimized untuk use case spesifik (JavaScript only)

**Lokasi Implementasi:**
- **File:** `amd/src/editor.js` (13,000+ baris)
- **Build:** `amd/build/editor.min.js` (minified)
- **Renderer:** `renderer.php` (HTML structure + inline CSS)

#### 4.1.2 Arsitektur Editor

**Komponen Editor:**

```
┌─────────────────────────────────────────────────────────────┐
│                    EDITOR CONTAINER                          │
│  ┌────────────┐  ┌────────────────────────────────────┐     │
│  │   Line     │  │      Editor Stack (relative)       │     │
│  │  Numbers   │  │  ┌──────────────────────────────┐  │     │
│  │   Pane     │  │  │  Highlight Overlay (absolute)│  │     │
│  │            │  │  │  - <pre> with syntax colors  │  │     │
│  │  1         │  │  │  - pointer-events: none      │  │     │
│  │  2         │  │  │  - z-index: 0                │  │     │
│  │  3         │  │  └──────────────────────────────┘  │     │
│  │  4         │  │  ┌──────────────────────────────┐  │     │
│  │  5         │  │  │  Textarea (transparent text) │  │     │
│  │  ...       │  │  │  - Actual input element      │  │     │
│  │            │  │  │  - color: transparent        │  │     │
│  │            │  │  │  - caret-color: #212529      │  │     │
│  │            │  │  │  - z-index: 1                │  │     │
│  │            │  │  └──────────────────────────────┘  │     │
│  └────────────┘  └────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────┘
```

**Implementasi HTML:**

```html
<!-- File: renderer.php (baris 400-450) -->
<div class="aicode-textarea-wrap">
    <!-- Line numbers pane -->
    <div class="aicode-line-numbers" id="aicode-line-numbers" aria-hidden="true"></div>
    
    <!-- Editor stack -->
    <div class="aicode-editor-stack">
        <!-- Highlight overlay -->
        <div class="aicode-highlight" id="aicode-highlight">
            <div id="aicode-active-line" class="aicode-active-line" style="display:none"></div>
            <pre id="aicode-highlight-code" class="aicode-highlight-code"></pre>
        </div>
        
        <!-- Actual textarea -->
        <textarea 
            id="aicode-fallback-editor" 
            class="aicode-fallback-editor"
            spellcheck="false"
            wrap="off"
        ></textarea>
    </div>
</div>
```

#### 4.1.3 Syntax Highlighting

**Implementasi:**

```javascript
// File: amd/src/editor.js (baris 1500-1700)

/**
 * Tokenize and highlight JavaScript code
 * @param {string} code - Raw JavaScript code
 * @return {string} HTML with syntax highlighting
 */
function highlightCode(code) {
    if (!code) return '';
    
    let html = escapeHtml(code);
    
    // 1. Comments (must be first to avoid false positives)
    html = html.replace(/\/\/.*$/gm, '<span class="tok-comment">$&</span>');
    html = html.replace(/\/\*[\s\S]*?\*\//g, '<span class="tok-comment">$&</span>');
    
    // 2. Strings (single, double, template literals)
    html = html.replace(/(["'`])(?:(?!\1)[^\\]|\\.)*?\1/g, '<span class="tok-string">$&</span>');
    
    // 3. Keywords
    const keywords = 'function|const|let|var|if|else|for|while|do|switch|case|break|continue|return|class|extends|new|this|super|async|await|try|catch|finally|throw|typeof|instanceof|delete|void|yield|import|export|from|default|static|get|set';
    html = html.replace(new RegExp('\\b(' + keywords + ')\\b', 'g'), '<span class="tok-keyword">$&</span>');
    
    // 4. Numbers
    html = html.replace(/\b\d+\.?\d*\b/g, '<span class="tok-number">$&</span>');
    
    // 5. Booleans and null
    html = html.replace(/\b(true|false|null|undefined|NaN|Infinity)\b/g, '<span class="tok-boolean">$&</span>');
    
    // 6. Built-in objects
    const builtins = 'console|document|window|Math|Array|Object|String|Number|Boolean|Date|RegExp|Error|JSON|Promise|Set|Map|Symbol|Proxy|Reflect';
    html = html.replace(new RegExp('\\b(' + builtins + ')\\b', 'g'), '<span class="tok-builtin">$&</span>');
    
    // 7. Function calls (word followed by opening paren)
    html = html.replace(/\b([a-zA-Z_$][a-zA-Z0-9_$]*)\s*(?=\()/g, '<span class="tok-function">$&</span>');
    
    return html;
}

/**
 * Update highlight overlay when code changes
 */
function updateHighlight() {
    const code = editor.value;
    const highlighted = highlightCode(code);
    highlightCode.innerHTML = highlighted;
}

// Attach to input event with debounce
let highlightTimeout = null;
editor.addEventListener('input', function() {
    clearTimeout(highlightTimeout);
    highlightTimeout = setTimeout(updateHighlight, 50); // 50ms debounce
});
```

**CSS untuk Syntax Colors:**

```css
/* File: renderer.php (inline CSS, baris 800-850) */
.aicode-textarea-wrap {
    --editor-bg: #f8f9fa;
    --editor-fg: #212529;
    --editor-comment: #6c757d;
    --editor-keyword: #0d6efd;
    --editor-string: #198754;
    --editor-number: #d63384;
    --editor-boolean: #d63384;
    --editor-null: #d63384;
    --editor-builtin: #6f42c1;
    --editor-function: #6f42c1;
    --editor-operator: #dc3545;
    --editor-line-number: #868e96;
    --editor-border: #dee2e6;
}

.aicode-highlight .tok-comment { color: var(--editor-comment); font-style: italic; }
.aicode-highlight .tok-keyword { color: var(--editor-keyword); font-weight: 600; }
.aicode-highlight .tok-string { color: var(--editor-string); }
.aicode-highlight .tok-number { color: var(--editor-number); }
.aicode-highlight .tok-boolean { color: var(--editor-boolean); }
.aicode-highlight .tok-null { color: var(--editor-null); }
.aicode-highlight .tok-builtin { color: var(--editor-builtin); }
.aicode-highlight .tok-function { color: var(--editor-function); }
```

#### 4.1.4 Line Numbers

**Implementasi:**

```javascript
// File: amd/src/editor.js (baris 800-900)

/**
 * Generate line numbers based on code content
 */
function updateLineNumbers() {
    if (!lineNumberPane || !editor) return;
    
    const code = editor.value;
    const lines = code.split('\n');
    const lineCount = lines.length;
    
    // Generate line numbers HTML
    let html = '';
    for (let i = 1; i <= lineCount; i++) {
        html += i + '\n';
    }
    
    lineNumberPane.textContent = html;
}

// Update on input
editor.addEventListener('input', updateLineNumbers);

// Initial render
updateLineNumbers();
```

#### 4.1.5 Scroll Synchronization

**Implementasi:**

```javascript
// File: amd/src/editor.js (baris 900-950)

/**
 * Sync scroll between textarea, highlight overlay, and line numbers
 */
function syncScroll() {
    if (!editor || !highlightPane || !lineNumberPane) return;
    
    const scrollTop = editor.scrollTop;
    const scrollLeft = editor.scrollLeft;
    
    // Sync vertical scroll
    highlightPane.scrollTop = scrollTop;
    lineNumberPane.scrollTop = scrollTop;
    
    // Sync horizontal scroll (only highlight, not line numbers)
    highlightPane.scrollLeft = scrollLeft;
}

// Attach to scroll event
editor.addEventListener('scroll', syncScroll);
```

#### 4.1.6 Autosave dan History

**Implementasi:**

```javascript
// File: amd/src/editor.js (baris 2000-2200)

/**
 * Get storage key for history – scoped to the current exercise
 * Uses problemId as primary key, cmId as secondary, URL param as fallback
 */
const getHistoryKey = function() {
    const pid = Number.parseInt(config.problemId, 10);
    const cid = Number.parseInt(config.cmId, 10);
    if (Number.isInteger(pid) && pid > 0) {
        return `aicode_history_p${pid}`;
    }
    if (Number.isInteger(cid) && cid > 0) {
        return `aicode_history_cm${cid}`;
    }
    const urlCmId = getCmIdFromUrl();
    if (urlCmId) {
        return `aicode_history_cm${urlCmId}`;
    }
    return 'aicode_history_unknown';
};

/**
 * Load history from sessionStorage
 */
const loadHistory = function() {
    try {
        const raw = sessionStorage.getItem(getHistoryKey());
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        return [];
    }
};

/**
 * Save history to sessionStorage
 */
const saveHistory = function(items) {
    try {
        sessionStorage.setItem(getHistoryKey(), JSON.stringify(items));
    } catch (e) {
        // Ignore storage errors
    }
};

/**
 * Add a history entry for the current run
 */
const addHistoryEntry = function(code) {
    const trimmed = String(code || '').trim();
    if (!trimmed) return;
    
    const last = historyItems.length ? historyItems[historyItems.length - 1] : '';
    if (last === trimmed) return; // Avoid duplicates
    
    historyItems.push(trimmed);
    if (historyItems.length > 20) {
        historyItems = historyItems.slice(-20); // Keep last 20
    }
    saveHistory(historyItems);
    renderHistory();
};

/**
 * Load run history from DB and merge with sessionStorage
 */
const loadHistoryFromDB = function() {
    const problemId = getProblemId();
    if (!problemId) return;
    
    Ajax.call([{
        methodname: 'mod_aicode_get_run_history',
        args: {
            problemid: problemId,
            sesskey: getSesskey() || ''
        }
    }])[0].then(function(response) {
        var dbHistory;
        try {
            dbHistory = JSON.parse(response.history);
        } catch (e) {
            return true;
        }
        if (!Array.isArray(dbHistory) || !dbHistory.length) {
            return true;
        }
        
        // Extract code strings from DB history
        var dbCodes = dbHistory
            .map(function(h) { return String(h.code || '').trim(); })
            .filter(Boolean);
        
        // Merge: DB history is authoritative
        var merged = dbCodes.slice();
        historyItems.forEach(function(item) {
            var trimmed = String(item || '').trim();
            if (trimmed && merged.indexOf(trimmed) === -1) {
                merged.push(trimmed);
            }
        });
        
        merged = merged.slice(-20);
        historyItems = merged;
        saveHistory(historyItems);
        renderHistory();
        return true;
    }).catch(function() {
        // Silently ignore — sessionStorage history still works
    });
};
```

**Penjelasan:**
- **SessionStorage** — History disimpan per session browser (hilang saat tab ditutup)
- **Database** — History persisten disimpan di `aicode_attempts` table
- **Merge Strategy** — DB history sebagai authoritative source, session history sebagai supplement
- **Limit** — Maksimal 20 entries untuk performa

#### 4.1.7 Keyboard Shortcuts

**Implementasi:**

```javascript
// File: amd/src/editor.js (baris 1000-1100)

/**
 * Handle keyboard shortcuts
 */
editor.addEventListener('keydown', function(e) {
    // Tab key: insert 2 spaces instead of losing focus
    if (e.key === 'Tab') {
        e.preventDefault();
        const start = editor.selectionStart;
        const end = editor.selectionEnd;
        const value = editor.value;
        
        if (e.shiftKey) {
            // Shift+Tab: unindent (not implemented yet)
            return;
        }
        
        // Insert 2 spaces
        editor.value = value.substring(0, start) + '  ' + value.substring(end);
        editor.selectionStart = editor.selectionEnd = start + 2;
        
        // Trigger input event for highlight update
        editor.dispatchEvent(new Event('input', {bubbles: true}));
    }
    
    // Ctrl+Enter or Cmd+Enter: Run code
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        handleRun();
    }
    
    // Ctrl+/ or Cmd+/: Toggle comment (not implemented yet)
    if ((e.ctrlKey || e.metaKey) && e.key === '/') {
        e.preventDefault();
        // TODO: Toggle line comment
    }
});
```

### 4.2 Executor Sandbox

#### 4.2.1 Gambaran Umum

Executor adalah **microservice terpisah** berbasis Node.js yang bertanggung jawab menjalankan kode siswa dalam lingkungan sandbox yang aman dan terisolasi. Executor berjalan di port 3001 (configurable) dan berkomunikasi dengan Moodle via HTTP REST API.

**Alasan Microservice Terpisah:**
1. **Security** — Isolasi penuh dari Moodle server
2. **Scalability** — Bisa di-scale horizontal (multiple instances)
3. **Technology** — Node.js lebih cocok untuk JavaScript execution
4. **Resource Management** — Timeout dan memory limit per execution
5. **Fault Tolerance** — Crash di executor tidak affect Moodle

**Teknologi:**
- **Runtime:** Node.js 18+
- **Sandbox Library:** vm2 (isolated V8 context)
- **Framework:** Express.js (HTTP server)
- **Port:** 3001 (default, configurable)

**Lokasi Konfigurasi:**
- **Moodle:** `settings.php` — `aicode/executor_url`
- **Executor:** Environment variable atau config file (tidak termasuk dalam plugin)

#### 4.2.2 Arsitektur Executor

**Request Flow:**

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Moodle: run_code.php sends HTTP POST                     │
│    URL: http://127.0.0.1:3001/run                           │
│    Payload: {code, language, testcase}                      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. Executor: Express.js receives request                    │
│    - Validate payload                                       │
│    - Check language support (currently: javascript only)    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Executor: Create vm2 sandbox                             │
│    - Isolated V8 context                                    │
│    - No access to require(), fs, net, child_process         │
│    - Timeout: 2 seconds (configurable)                      │
│    - Memory limit: 128MB (configurable)                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Executor: Run code in sandbox                            │
│    - Capture stdout (console.log)                           │
│    - Capture stderr (errors)                                │
│    - Capture exit code                                      │
│    - Capture stack trace (if error)                         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. Executor: Return JSON response                           │
│    {                                                        │
│      "stdout": "...",                                       │
│      "stderr": "...",                                       │
│      "exitCode": 0,                                         │
│      "trace": "..."                                         │
│    }                                                        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. Moodle: run_code.php receives response                   │
│    - Parse JSON                                             │
│    - Store in aicode_attempts table                         │
│    - Return to frontend                                     │
└─────────────────────────────────────────────────────────────┘
```

#### 4.2.3 Implementasi Moodle Side

**File:** `classes/external/run_code.php` (baris 100-150)

```php
// Get executor URL from config
$executorurl = get_config('aicode', 'executor_url');
if (empty($executorurl)) {
    $executorurl = 'http://127.0.0.1:3001';
}

// Prepare request payload
$payload = [
    'code' => $params['code'],
    'language' => $params['language'],
    'testcase' => json_decode($problem->testcases ?? '[]', true),
];

// Send to executor service
$curl = new \curl(['ignoresecurity' => true]);
$curl->setHeader(['Content-Type: application/json']);
$response = $curl->post($executorurl . '/run', json_encode($payload));

if ($curl->get_errno()) {
    throw new \moodle_exception('Executor service unavailable: ' . $curl->error);
}

$result = json_decode($response, true);
if (!is_array($result)) {
    $result = [
        'stdout' => '',
        'stderr' => 'Executor returned empty or invalid response.',
        'exitCode' => 1,
        'trace' => '',
    ];
}
```

#### 4.2.4 Sandbox Security

**Implementasi Executor (Pseudocode):**

```javascript
// Executor service (NOT included in plugin, separate microservice)
const {VM} = require('vm2');
const express = require('express');

app.post('/run', (req, res) => {
    const {code, language, testcase} = req.body;
    
    if (language !== 'javascript') {
        return res.json({
            stdout: '',
            stderr: 'Unsupported language',
            exitCode: 1,
            trace: ''
        });
    }
    
    // Create sandbox
    const vm = new VM({
        timeout: 2000, // 2 seconds
        sandbox: {
            console: {
                log: (...args) => stdout.push(args.join(' ')),
                error: (...args) => stderr.push(args.join(' ')),
                warn: (...args) => stderr.push(args.join(' ')),
                info: (...args) => stdout.push(args.join(' '))
            }
        },
        eval: false,
        wasm: false
    });
    
    let stdout = [];
    let stderr = [];
    let exitCode = 0;
    let trace = '';
    
    try {
        vm.run(code);
    } catch (error) {
        exitCode = 1;
        stderr.push(error.message);
        trace = error.stack || '';
    }
    
    res.json({
        stdout: stdout.join('\n'),
        stderr: stderr.join('\n'),
        exitCode: exitCode,
        trace: trace
    });
});
```

**Security Features:**
1. **No require()** — Tidak bisa import module eksternal
2. **No fs** — Tidak bisa akses filesystem
3. **No net** — Tidak bisa buat network connection
4. **No child_process** — Tidak bisa spawn process
5. **Timeout** — Automatic termination setelah 2 detik
6. **Memory limit** — Maksimal 128MB per execution
7. **Isolated context** — Tidak bisa akses global Node.js objects

#### 4.2.5 Timeout Protection

**Client-side (Frontend):**

```javascript
// File: amd/src/editor.js (baris 3000-3100)

/**
 * Add basic loop protector for common patterns
 */
const addBasicLoopProtector = function(js) {
    const src = String(js ?? '');
    return src
        .replace(/for\s*\([^)]*\)\s*\{/g, (m) => `${m}\n__loopProtect();`)
        .replace(/while\s*\([^)]*\)\s*\{/g, (m) => `${m}\n__loopProtect();`)
        .replace(/do\s*\{/g, (m) => `${m}\n__loopProtect();`);
};

/**
 * Build iframe srcdoc for preview
 */
const buildSrcDoc = function(html, css, js) {
    const safeJs = escapeClosingScriptTags(addBasicLoopProtector(js));
    
    return `<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <style>${css ?? ""}</style>
  </head>
  <body>
    ${html ?? ""}
    <script>
      (function() {
        var __lpStart = Date.now();
        window.__loopProtect = function() {
          if (Date.now() - __lpStart > 500) {
            throw new Error("Loop protector: kemungkinan infinite loop (stop setelah 500ms).");
          }
        };
        
        try {
          ${safeJs}
        } catch (err) {
          parent.postMessage({
            source: "aicode-preview",
            type: "error",
            payload: {message: err.message, stack: err.stack}
          }, "*");
        }
      })();
    </script>
  </body>
</html>`;
};
```

**Server-side (Executor):**

```javascript
// Executor service
const vm = new VM({
    timeout: 2000, // 2 seconds - hard limit
    // ...
});
```

**Penjelasan:**
- **Client-side:** 500ms limit untuk preview iframe (soft limit, bisa di-bypass)
- **Server-side:** 2000ms limit untuk executor (hard limit, tidak bisa di-bypass)
- **Dual protection:** Defense in depth strategy

---



### 4.3 AI Analyzer (Gemini Integration)

#### 4.3.1 Gambaran Umum

AI Analyzer adalah komponen inti yang mengintegrasikan Google Gemini AI untuk memberikan feedback cerdas kepada siswa. Sistem ini menganalisis kode siswa, error message, dan stack trace, kemudian menghasilkan diagnosis, hints, dan suggested fix dalam Bahasa Indonesia yang mudah dipahami.

**Lokasi Implementasi:**
- **Main Service:** `classes/external/analyze_code.php` (600+ baris)
- **Prompt Engine:** `classes/local/ai_prompt.php` (150+ baris)
- **Frontend:** `amd/src/editor.js` (handleHint function)

**Model AI:**
- **Default:** gemini-2.0-flash
- **Configurable:** Via admin settings
- **Alternative:** gemini-1.5-pro, gemini-1.5-flash

#### 4.3.2 Request Flow

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Student clicks "AI Hint" button                          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. Frontend: handleHint()                                   │
│    - Get last error from lastAnalysisInput                  │
│    - AJAX call: mod_aicode_analyze_code                     │
│    - Show loading spinner                                   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Backend: analyze_code::execute()                         │
│    ├─ Validate parameters                                   │
│    ├─ Check capability                                      │
│    ├─ Rate limiting (max 50/day/user)                       │
│    ├─ Find latest attempt                                   │
│    ├─ Resolve prompt template                               │
│    ├─ Check cache (SHA256 hash)                             │
│    │  └─ If hit → return cached + stamp ai_requested_at     │
│    ├─ Anonymize code                                        │
│    ├─ Fetch teacher corrections (few-shot)                  │
│    ├─ Build prompt                                          │
│    ├─ Call Gemini API                                       │
│    ├─ Parse & normalize response                            │
│    ├─ Validate confidence threshold                         │
│    ├─ Cache successful feedback                             │
│    ├─ Update attempt (ai_requested_at, ai_feedback_json)    │
│    ├─ Log activity                                          │
│    └─ Return feedback JSON                                  │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Frontend: Display feedback                               │
│    - Parse JSON                                             │
│    - Show diagnosis with confidence badge                   │
│    - Show hints (progressive reveal)                        │
│    - Show suggested fix                                     │
│    - Show recommended materials                             │
└─────────────────────────────────────────────────────────────┘
```

#### 4.3.3 Prompt Engineering

**Default Prompt Template:**

```
// File: classes/local/ai_prompt.php (baris 30-150)

Anda adalah tutor JavaScript untuk siswa SMA/SMK di Indonesia.
Tugas Anda: menganalisis kode siswa dan error eksekusi, lalu memberikan 
feedback yang akurat, detail, dan mudah dipahami.

Keluaran HARUS hanya satu objek JSON valid (tanpa markdown, tanpa backticks).

Gunakan skema JSON berikut:
{
  "diagnosis": {
    "category": "syntax|runtime|logic|style|security|performance",
    "confidence": 0.0,
    "message_short": "kalimat ringkas",
    "message_long": "analisis lengkap yang mudah dipahami siswa"
  },
  "location": {
    "line": 0,
    "column": 0,
    "snippet": "potongan kode yang relevan"
  },
  "hints": [
    "panduan aksi konkret yang bisa langsung dilakukan siswa",
    "penjelasan singkat agar kesalahan tidak terulang"
  ],
  "suggested_fix": {
    "explanation": "langkah bernomor yang jelas",
    "code_patch": "potongan kode perbaikan minimal"
  },
  "recommended_materials": [
    {"title": "judul", "url": "https://...", "reason": "alasan"}
  ],
  "explainability": "alasan singkat yang merujuk bukti error"
}

Aturan kualitas:
- Semua teks WAJIB Bahasa Indonesia baku yang sederhana
- Hindari jargon; jika harus, jelaskan artinya
- diagnosis.message_short: 1 kalimat, maksimal 18 kata
- diagnosis.message_long: 4-6 kalimat (akar masalah, bukti, dampak, pencegahan)
- Hints: 1-2 butir panduan spesifik yang actionable
- suggested_fix.explanation: langkah bernomor 1), 2), 3)
- Confidence: 0-1, gunakan nilai rendah jika bukti kurang kuat
- Jangan menyalahkan siswa; gunakan nada membimbing

Few-shot contoh:
[Contoh 1]
Input:
- kode: function hitungTotal(arr) { let total = 0; for (let i = 0; i < arr.length; i++) { total += arr[i]; } return totals; }
- stderr: ReferenceError: totals is not defined
- trace: at hitungTotal (main.js:6:10)

Output:
{
  "diagnosis": {
    "category": "runtime",
    "confidence": 0.95,
    "message_short": "Variabel yang dikembalikan salah nama sehingga memicu ReferenceError.",
    "message_long": "Akar masalahnya ada pada baris return yang memakai nama variabel totals, padahal variabel yang dibuat adalah total. Bukti terlihat dari stderr ReferenceError: totals is not defined dan trace yang menunjuk fungsi hitungTotal. Dampaknya, program berhenti saat mengembalikan hasil sehingga nilai total tidak pernah keluar dengan benar. Agar tidak terulang, pastikan penamaan variabel konsisten dari deklarasi sampai pengembalian nilai."
  },
  "location": {
    "line": 6,
    "column": 10,
    "snippet": "return totals;"
  },
  "hints": [
    "Samakan nama variabel pada return dengan variabel yang sudah dideklarasikan.",
    "Setelah ubah, jalankan ulang dan cek apakah output menjadi 6 untuk input [1,2,3]."
  ],
  "suggested_fix": {
    "explanation": "1) Cari baris return totals;. 2) Ganti totals menjadi total agar sesuai deklarasi. 3) Jalankan ulang untuk memastikan error ReferenceError hilang.",
    "code_patch": "return total;"
  },
  "recommended_materials": [
    {"title": "MDN - ReferenceError", "url": "https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/ReferenceError", "reason": "Membantu memahami penyebab variabel tidak terdefinisi dan cara memperbaikinya."}
  ],
  "explainability": "Bukti utama berasal dari stderr ReferenceError dan trace pada fungsi hitungTotal."
}

Kode siswa:
[[CODE]]

stderr:
[[STDERR]]

trace:
[[TRACE]]
```

**Token Replacement:**

```php
// File: classes/local/ai_prompt.php (baris 100-130)

public static function render_template($template, $code, $stderr, $trace) {
    $template = trim((string)$template);
    if ($template === '') {
        $template = self::get_default_template();
    }

    $prompt = strtr($template, [
        self::CODE_TOKEN => (string)$code,
        self::STDERR_TOKEN => (string)$stderr,
        self::TRACE_TOKEN => (string)$trace,
    ]);

    // Safety: append data if tokens were omitted
    $hastokens = strpos($template, self::CODE_TOKEN) !== false
        || strpos($template, self::STDERR_TOKEN) !== false
        || strpos($template, self::TRACE_TOKEN) !== false;

    if (!$hastokens) {
        $prompt .= "\n\nKode siswa:\n" . (string)$code
            . "\n\nstderr:\n" . (string)$stderr
            . "\n\ntrace:\n" . (string)$trace . "\n";
    }

    return $prompt;
}
```

#### 4.3.4 Few-Shot Learning (Teacher Corrections)

**Implementasi:**

```php
// File: classes/external/analyze_code.php (baris 400-500)

/**
 * Fetch approved teacher correction examples for the given problem.
 * Returns a formatted string to prepend to the AI prompt.
 */
private static function get_teacher_correction_examples(int $problemid): string {
    global $DB;

    $sql = "SELECT o.corrected_feedback_json, a.ai_feedback_json
              FROM {aicode_teacher_overrides} o
              JOIN {aicode_attempts} a ON a.id = o.attemptid
             WHERE a.problemid = :pid
               AND o.use_as_example = 1
             ORDER BY o.timecreated DESC";

    $rows = $DB->get_records_sql($sql, ['pid' => $problemid], 0, 3); // Max 3 examples
    if (empty($rows)) {
        return '';
    }

    $parts = [];
    $idx   = 1;
    foreach ($rows as $row) {
        $correction = json_decode($row->corrected_feedback_json ?? '{}', true);
        $originalfb = json_decode($row->ai_feedback_json ?? '{}', true);
        if (!is_array($correction)) {
            continue;
        }

        $block = "=== Contoh Koreksi Guru #{$idx} ===\n";

        // Show what the AI originally said (for contrast)
        if (is_array($originalfb) && ($originalfb['status'] ?? '') === 'success') {
            $origshort = trim((string)($originalfb['diagnosis']['message_short'] ?? ''));
            if ($origshort !== '') {
                $block .= "Feedback AI awal: \"{$origshort}\"\n";
            }
        }

        // Teacher corrected diagnosis
        $cd        = $correction['corrected_diagnosis'] ?? [];
        $corrshort = trim((string)($cd['message_short'] ?? ''));
        $corrlong  = trim((string)($cd['message_long'] ?? ''));
        if ($corrshort !== '') {
            $block .= "Koreksi guru (ringkas): \"{$corrshort}\"\n";
        }
        if ($corrlong !== '') {
            $block .= "Koreksi guru (lengkap): \"{$corrlong}\"\n";
        }

        // Teacher corrected hints
        $hints = $correction['corrected_hints'] ?? [];
        if (!empty($hints) && is_array($hints)) {
            $block .= "Petunjuk yang lebih baik:\n";
            foreach (array_slice($hints, 0, 3) as $hi => $hint) {
                $block .= ($hi + 1) . '. ' . trim((string)$hint) . "\n";
            }
        }

        // Teacher corrected fix
        $fix = trim((string)($correction['corrected_suggested_fix'] ?? ''));
        if ($fix !== '') {
            $block .= "Saran perbaikan yang tepat: \"{$fix}\"\n";
        }

        // Teacher notes
        $notes = trim((string)($correction['notes'] ?? ''));
        if ($notes !== '') {
            $block .= "Catatan guru: \"{$notes}\"\n";
        }

        $parts[] = $block;
        $idx++;
    }

    if (empty($parts)) {
        return '';
    }

    $header = "PENTING: Guru telah memberikan koreksi pada feedback AI sebelumnya untuk soal ini. "
        . "Gunakan contoh-contoh di bawah sebagai panduan untuk menghasilkan feedback yang lebih akurat:\n\n";

    return $header . implode("\n", $parts) . "\n";
}
```

**Workflow:**
1. Guru mengoreksi feedback AI yang kurang tepat
2. Guru centang "use as example" checkbox
3. Koreksi disimpan di `aicode_teacher_overrides` table
4. Saat AI request berikutnya, koreksi diinjeksikan ke prompt
5. Gemini belajar dari contoh koreksi (in-context learning)

#### 4.3.5 Anonymization

**Implementasi:**

```php
// File: classes/external/analyze_code.php (baris 100-120)

/**
 * Anonymize code by removing PII
 */
private static function anonymize_code($code) {
    // Remove single-line comments
    $code = preg_replace('/\/\/.*$/m', '', $code);
    
    // Remove multi-line comments
    $code = preg_replace('/\/\*.*?\*\//s', '', $code);
    
    // Remove email addresses
    $code = preg_replace('/[\w\.-]+@[\w\.-]+\.\w+/', '[EMAIL]', $code);
    
    return $code;
}
```

**Alasan:**
- **Privacy** — Mencegah PII (nama, email) dikirim ke Gemini
- **GDPR Compliance** — Sesuai regulasi perlindungan data
- **Focus** — AI fokus pada logic, bukan identitas siswa

#### 4.3.6 Caching Strategy

**Implementasi:**

```php
// File: classes/external/analyze_code.php (baris 150-200)

// Check cache
$payloadhash = hash(
    'sha256',
    'feedback-v5|' . hash('sha256', $prompttemplate) . '|' . $params['code'] . $params['stderr'] . $params['trace']
);
$cachettl = get_config('aicode', 'cache_ttl') ?: 3600;
$cached = $DB->get_record('aicode_cache', ['payload_hash' => $payloadhash]);

if ($cached && ($cached->timecreated + $cachettl) > time() && !empty($cached->ai_response_json)) {
    $cachedfeedback = json_decode($cached->ai_response_json, true);
    if (self::is_success_feedback($cachedfeedback)) {
        // Stamp ai_requested_at so this cached hit is counted against quota
        if ($latestattempt) {
            $DB->set_field('aicode_attempts', 'ai_requested_at', time(), ['id' => $latestattempt->id]);
        }
        
        // Log activity
        \mod_aicode\local\activity_log::record(
            $context,
            (int) $params['problemid'],
            (int) $USER->id,
            \mod_aicode\local\activity_log::ACTION_AI_ANALYZE,
            ['from_cache' => true, 'outcome' => 'success'],
            $problem
        );
        
        return ['feedback' => $cached->ai_response_json];
    }
}
```

**Cache Key:**
- **Version:** `feedback-v5` (increment saat schema berubah)
- **Prompt Hash:** SHA256 of prompt template
- **Code:** Raw code
- **Stderr:** Error message
- **Trace:** Stack trace

**Benefits:**
1. **Cost Reduction** — Mengurangi API calls ke Gemini (berbayar)
2. **Performance** — Response instant untuk error yang sama
3. **Consistency** — Feedback konsisten untuk error identik
4. **Rate Limiting** — Cached hits tetap dihitung untuk quota

#### 4.3.7 Rate Limiting

**Implementasi:**

```php
// File: classes/external/analyze_code.php (baris 70-90)

// Check rate limiting — only count rows where student actually clicked AI Hint
$maxcalls = get_config('aicode', 'max_calls_per_day') ?: 50;
$daystart = strtotime('today');
$count = $DB->count_records_select(
    'aicode_attempts',
    'userid = :userid AND ai_requested_at IS NOT NULL AND ai_requested_at >= :daystart',
    ['userid' => $USER->id, 'daystart' => $daystart]
);

if ($count >= $maxcalls) {
    throw new \moodle_exception('Rate limit exceeded. Please try again tomorrow.');
}
```

**Penjelasan:**
- **Limit:** 50 AI calls per student per day (configurable)
- **Reset:** Midnight (00:00) setiap hari
- **Scope:** Per user, bukan per activity
- **Counting:** Hanya AI Hint clicks, bukan plain code runs

#### 4.3.8 Response Validation

**Implementasi:**

```php
// File: classes/external/analyze_code.php (baris 500-600)

/**
 * Determine whether payload contains successful AI feedback
 */
private static function is_success_feedback($feedback) {
    if (!is_array($feedback)) {
        return false;
    }

    $status = (string)($feedback['status'] ?? 'success');
    if ($status !== 'success') {
        return false;
    }

    if (empty($feedback['diagnosis']) || !is_array($feedback['diagnosis'])) {
        return false;
    }

    $diagnosis = $feedback['diagnosis'];
    $shortmessage = trim((string)($diagnosis['message_short'] ?? ''));
    $longmessage = trim((string)($diagnosis['message_long'] ?? ''));
    if ($shortmessage === '' || $longmessage === '') {
        return false;
    }

    $confidence = (float)($diagnosis['confidence'] ?? -1);
    if ($confidence < 0 || $confidence > 1) {
        return false;
    }

    return true;
}

/**
 * Normalize model feedback into stable schema for frontend
 */
private static function normalize_feedback($feedback) {
    $normalized = [
        'status' => 'success',
        'diagnosis' => [
            'category' => 'runtime',
            'confidence' => 0.0,
            'message_short' => '',
            'message_long' => '',
        ],
        'location' => [
            'line' => 0,
            'column' => 0,
            'snippet' => '',
        ],
        'hints' => [],
        'suggested_fix' => null,
        'recommended_materials' => [],
        'explainability' => 'Dihasilkan oleh Gemini AI.',
    ];

    // Normalize diagnosis
    if (!empty($feedback['diagnosis']) && is_array($feedback['diagnosis'])) {
        $diagnosis = $feedback['diagnosis'];
        $normalized['diagnosis']['category'] = self::normalize_diagnosis_category(
            (string)($diagnosis['category'] ?? 'runtime')
        );
        $normalized['diagnosis']['confidence'] = max(0.0, min(1.0, (float)($diagnosis['confidence'] ?? 0.0)));
        $normalized['diagnosis']['message_short'] = trim((string)($diagnosis['message_short'] ?? ''));
        $normalized['diagnosis']['message_long'] = trim((string)($diagnosis['message_long'] ?? ''));
    }

    // Normalize location
    if (!empty($feedback['location']) && is_array($feedback['location'])) {
        $location = $feedback['location'];
        $normalized['location']['line'] = max(0, (int)($location['line'] ?? 0));
        $normalized['location']['column'] = max(0, (int)($location['column'] ?? 0));
        $normalized['location']['snippet'] = (string)($location['snippet'] ?? '');
    }

    // Normalize hints (max 3)
    if (!empty($feedback['hints']) && is_array($feedback['hints'])) {
        $hints = [];
        foreach ($feedback['hints'] as $hint) {
            $hinttext = '';
            if (is_string($hint)) {
                $hinttext = trim($hint);
            } else if (is_array($hint)) {
                $hinttext = trim((string)($hint['hint'] ?? $hint['text'] ?? $hint['message'] ?? ''));
            }
            if ($hinttext === '') {
                continue;
            }
            $hints[] = $hinttext;
            if (count($hints) >= 3) {
                break;
            }
        }
        if (!empty($hints)) {
            $normalized['hints'] = $hints;
        }
    }

    // Normalize suggested_fix
    if (array_key_exists('suggested_fix', $feedback)) {
        $suggestedfix = $feedback['suggested_fix'];
        if (is_array($suggestedfix) && !empty($suggestedfix['explanation'])) {
            $normalized['suggested_fix'] = [
                'explanation' => (string)$suggestedfix['explanation'],
                'code_patch' => (string)($suggestedfix['code_patch'] ?? ''),
            ];
        } else {
            $normalized['suggested_fix'] = null;
        }
    }

    // Normalize recommended_materials (max 3)
    if (!empty($feedback['recommended_materials']) && is_array($feedback['recommended_materials'])) {
        $materials = [];
        foreach ($feedback['recommended_materials'] as $material) {
            if (!is_array($material) || empty($material['title'])) {
                continue;
            }
            $materials[] = [
                'title' => (string)$material['title'],
                'url' => (string)($material['url'] ?? ''),
                'reason' => (string)($material['reason'] ?? ''),
            ];
            if (count($materials) >= 3) {
                break;
            }
        }
        $normalized['recommended_materials'] = $materials;
    }

    return $normalized;
}
```

**Validation Steps:**
1. **Schema Validation** — Cek required fields ada
2. **Type Validation** — Cek tipe data sesuai
3. **Range Validation** — Confidence 0-1, line >= 0
4. **Normalization** — Convert ke format konsisten
5. **Sanitization** — Trim whitespace, escape HTML

---

