<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    global $CFG, $OUTPUT;

    $ajaxendpoint = (new moodle_url('/local/llmmotivation/ajax_executor.php'))->out();
    $huburl = (new moodle_url('/admin/settings.php', ['section' => 'ai_central_settings']))->out();
    $adaptiveurl = (new moodle_url('/admin/settings.php', ['section' => 'blocksettingadaptive_learning_ai']))->out();
    $aicodeurl = (new moodle_url('/admin/settings.php', ['section' => 'modsettingaicode']))->out();
    $motivationurl = (new moodle_url('/admin/settings.php', ['section' => 'local_llmmotivation']))->out();

    // -------------------------------------------------------------------------
    // 1. HALAMAN PENGATURAN MANDIRI: LLM Motivation & Emotion System
    // -------------------------------------------------------------------------
    $settings = new admin_settingpage('local_llmmotivation', get_string('pluginname', 'local_llmmotivation'));

    $settings->add(new admin_setting_description(
        'local_llmmotivation/central_hub_banner',
        '',
        '<div class="alert alert-info d-flex align-items-center mb-3" style="border-left: 4px solid #0f6cbf; border-radius: 8px;">
            <div style="font-size: 1.5rem; margin-right: 12px;">💡</div>
            <div>
                <strong>Pusat Pengaturan AI Terpadu:</strong> Anda juga dapat mengelola pengaturan 
                <em>LLM Motivation</em>, <em>AICode</em>, <em>Adaptive Learning AI</em>, dan mengontrol status <em>Microservice Executor</em> 
                di satu tempat pada <a href="' . $huburl . '" class="alert-link font-weight-bold" style="text-decoration: underline;">Pusat Pengaturan AI</a>.
            </div>
        </div>'
    ));

    $settings->add(new admin_setting_heading(
        'local_llmmotivation_heading',
        get_string('settings_heading', 'local_llmmotivation'),
        ''
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_llmmotivation/gemini_apikey',
        get_string('settings_apikey', 'local_llmmotivation'),
        get_string('settings_apikey_desc', 'local_llmmotivation'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_llmmotivation/gemini_model',
        get_string('settings_model', 'local_llmmotivation'),
        get_string('settings_model_desc', 'local_llmmotivation'),
        'gemini-1.5-flash',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_llmmotivation/admin_preview',
        get_string('settings_admin_preview', 'local_llmmotivation'),
        get_string('settings_admin_preview_desc', 'local_llmmotivation'),
        0
    ));

    $ADMIN->add('localplugins', $settings);

    // -------------------------------------------------------------------------
    // 2. HALAMAN PUSAT PENGATURAN AI TERPADU (Central AI Settings Hub)
    // -------------------------------------------------------------------------
    $centralpage = new admin_settingpage('ai_central_settings', 'Pusat Pengaturan AI');

    // Navigation & Overview Header Card
    $headerhtml = '
    <div class="card mb-4 shadow-sm" style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
        <div class="card-header py-3" style="background: linear-gradient(135deg, #0f6cbf 0%, #1e40af 100%); color: #ffffff;">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <h4 class="mb-1 text-white" style="font-weight: 700;"><i class="fa fa-sliders mr-2"></i>Pusat Pengaturan AI Terpadu</h4>
                    <p class="mb-0 text-white-50" style="font-size: 0.95rem;">Kelola seluruh konfigurasi kecerdasan buatan (Adaptive Learning, AICode, LLM Motivation) dan status Microservice Executor dalam satu tempat.</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge badge-light px-3 py-2" style="font-size: 0.85rem; font-weight: 600; color: #0f6cbf;">
                        <i class="fa fa-shield mr-1"></i> Mode Admin Terverifikasi
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body bg-light py-2 px-3 border-bottom">
            <div class="d-flex align-items-center justify-content-between flex-wrap" style="font-size: 0.9rem;">
                <span class="text-muted"><i class="fa fa-external-link mr-1"></i><strong>Tautan Cepat ke Halaman Mandiri:</strong></span>
                <div class="d-inline-flex gap-2 flex-wrap mt-1 mt-md-0">
                    <a href="' . $adaptiveurl . '" class="btn btn-sm btn-outline-primary" style="border-radius: 6px;"><i class="fa fa-graduation-cap mr-1"></i>Adaptive Learning AI</a>
                    <a href="' . $aicodeurl . '" class="btn btn-sm btn-outline-info" style="border-radius: 6px;"><i class="fa fa-code mr-1"></i>AICode</a>
                    <a href="' . $motivationurl . '" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px;"><i class="fa fa-heartbeat mr-1"></i>LLM Motivation</a>
                </div>
            </div>
        </div>
    </div>';
    $centralpage->add(new admin_setting_description('ai_central/header', '', $headerhtml));

    // -------------------------------------------------------------------------
    // EXECUTOR CONTROLLER & STATUS WIDGET
    // -------------------------------------------------------------------------
    $executorhtml = '
    <div class="card mb-4 shadow-sm" style="border: 1px solid #cbd5e1; border-radius: 12px; background: #ffffff;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap mb-3 border-bottom pb-3">
                <div class="d-flex align-items-center">
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: #eff6ff; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #2563eb; margin-right: 14px;">
                        <i class="fa fa-server"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-weight: 700; color: #1e293b;">Microservice Executor (Node.js)</h5>
                        <p class="text-muted mb-0" style="font-size: 0.88rem;">Eksekutor sandbox mandiri untuk menjalankan dan menguji kode program siswa di plugin AICode.</p>
                    </div>
                </div>
                <div class="mt-2 mt-sm-0">
                    <span id="ai-executor-badge" class="badge badge-secondary px-3 py-2" style="font-size: 0.9rem; font-weight: 600; border-radius: 20px;">
                        <i class="fa fa-spinner fa-spin mr-1"></i> Memeriksa Status...
                    </span>
                </div>
            </div>

            <div class="row align-items-center mb-3">
                <div class="col-md-7 mb-3 mb-md-0">
                    <div class="p-3 bg-light rounded" style="border: 1px solid #e2e8f0; font-size: 0.88rem;">
                        <div class="row mb-1">
                            <div class="col-4 text-muted">Endpoint URL:</div>
                            <div class="col-8 font-weight-bold" id="ai-executor-url-display">http://127.0.0.1:3001/health</div>
                        </div>
                        <div class="row mb-1">
                            <div class="col-4 text-muted">Mode Eksekusi:</div>
                            <div class="col-8 font-weight-bold text-primary" id="ai-executor-mode-display">-</div>
                        </div>
                        <div class="row mb-1">
                            <div class="col-4 text-muted">Waktu Aktif (Uptime):</div>
                            <div class="col-8" id="ai-executor-uptime-display">-</div>
                        </div>
                        <div class="row">
                            <div class="col-4 text-muted">Sistem Operasi Server:</div>
                            <div class="col-8 font-weight-bold text-dark" id="ai-executor-os-display">' . PHP_OS_FAMILY . '</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="d-flex flex-column gap-2">
                        <button type="button" id="btn-start-executor" class="btn btn-success py-2 px-3 mb-2" style="font-weight: 600; border-radius: 8px;">
                            <i class="fa fa-play mr-2"></i> Jalankan Executor Service (Dev Mode)
                        </button>
                        <button type="button" id="btn-refresh-executor" class="btn btn-outline-secondary py-2 px-3" style="font-weight: 600; border-radius: 8px;">
                            <i class="fa fa-refresh mr-2"></i> Periksa Ulang Status
                        </button>
                        <div id="ai-executor-feedback" class="mt-2 text-center" style="font-size: 0.88rem; font-weight: 500;"></div>
                    </div>
                </div>
            </div>

            <!-- Petunjuk Azure VM & Cloud Deployment -->
            <div class="accordion" id="accordionExecutorVM">
                <div class="card" style="border: 1px dashed #94a3b8; background: #f8fafc; border-radius: 8px;">
                    <div class="card-header p-2 bg-transparent border-0" id="headingVMGuide">
                        <button class="btn btn-link text-decoration-none btn-block text-left py-1 px-2 d-flex justify-content-between align-items-center text-secondary" type="button" data-toggle="collapse" data-target="#collapseVMGuide" aria-expanded="false" style="font-size: 0.88rem; font-weight: 600;">
                            <span><i class="fa fa-cloud mr-2 text-primary"></i>Panduan untuk Azure VM / Production Server (Linux)</span>
                            <i class="fa fa-chevron-down"></i>
                        </button>
                    </div>
                    <div id="collapseVMGuide" class="collapse" data-parent="#accordionExecutorVM">
                        <div class="card-body pt-0 px-3 pb-3" style="font-size: 0.85rem; color: #475569; line-height: 1.6;">
                            <p class="mb-2">Tombol di atas menjalankan perintah lokal Windows/Laragon di latar belakang. Saat aplikasi Anda dimigrasikan ke <strong>Azure VM (Linux / Ubuntu)</strong>, disarankan mengaktifkan service 24/7 menggunakan <strong>PM2</strong> agar otomatis hidup kembali jika VM di-restart:</p>
                            <pre class="bg-dark text-light p-2 rounded mb-2" style="font-family: Consolas, monospace; font-size: 0.82rem;"><code># Masuk ke folder executor di Azure VM:
cd /var/www/html/moodle/services/executor

# Jalankan 24/7 dengan PM2:
pm2 start server.js --name "moodle-executor" --env dev
pm2 startup
pm2 save</code></pre>
                            <span class="text-muted"><i class="fa fa-check-circle text-success mr-1"></i>Dengan PM2, status di atas akan selalu terbaca <strong>Online</strong> secara otomatis tanpa perlu diklik manual.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const ajaxUrl = "' . $ajaxendpoint . '";
        const sesskey = M.cfg.sesskey;
        const badge = document.getElementById("ai-executor-badge");
        const modeDisp = document.getElementById("ai-executor-mode-display");
        const uptimeDisp = document.getElementById("ai-executor-uptime-display");
        const osDisp = document.getElementById("ai-executor-os-display");
        const btnStart = document.getElementById("btn-start-executor");
        const btnRefresh = document.getElementById("btn-refresh-executor");
        const feedback = document.getElementById("ai-executor-feedback");

        function formatUptime(seconds) {
            if (!seconds || seconds <= 0) return "Baru dimulai";
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            if (m === 0) return s + " detik";
            const h = Math.floor(m / 60);
            return (h > 0 ? h + " jam " : "") + (m % 60) + " menit " + s + " detik";
        }

        function checkStatus(silent) {
            if (!silent) {
                badge.className = "badge badge-secondary px-3 py-2";
                badge.innerHTML = \'<i class="fa fa-spinner fa-spin mr-1"></i> Memeriksa...\';
            }

            fetch(ajaxUrl + "?action=status&sesskey=" + sesskey)
                .then(r => r.json())
                .then(data => {
                    if (data.online) {
                        badge.className = "badge badge-success px-3 py-2";
                        badge.style.background = "#16a34a";
                        badge.innerHTML = \'<i class="fa fa-check-circle mr-1"></i> Online (Port 3001)\';
                        modeDisp.textContent = data.mode ? (data.mode.toUpperCase() + " (vm2 sandbox)") : "dev";
                        uptimeDisp.textContent = formatUptime(data.uptime);
                        if (data.os) osDisp.textContent = data.os;

                        btnStart.className = "btn btn-outline-success py-2 px-3 mb-2";
                        btnStart.innerHTML = \'<i class="fa fa-check mr-2"></i> Service Aktif (Klik untuk Restart)\';
                    } else {
                        badge.className = "badge badge-danger px-3 py-2";
                        badge.style.background = "#dc2626";
                        badge.innerHTML = \'<i class="fa fa-times-circle mr-1"></i> Offline (Belum Aktif)\';
                        modeDisp.textContent = "Offline";
                        uptimeDisp.textContent = "-";
                        if (data.os) osDisp.textContent = data.os;

                        btnStart.className = "btn btn-success py-2 px-3 mb-2";
                        btnStart.innerHTML = \'<i class="fa fa-play mr-2"></i> Jalankan Executor Service (Dev Mode)\';
                    }
                })
                .catch(err => {
                    badge.className = "badge badge-danger px-3 py-2";
                    badge.innerHTML = \'<i class="fa fa-exclamation-triangle mr-1"></i> Gagal Cek\';
                });
        }

        btnRefresh.addEventListener("click", function() {
            feedback.textContent = "";
            checkStatus(false);
        });

        btnStart.addEventListener("click", function() {
            btnStart.disabled = true;
            btnStart.innerHTML = \'<i class="fa fa-spinner fa-spin mr-2"></i> Menjalankan Service...\';
            feedback.className = "mt-2 text-info";
            feedback.textContent = "Mengirimkan perintah ke latar belakang server...";

            fetch(ajaxUrl + "?action=start&sesskey=" + sesskey)
                .then(r => r.json())
                .then(data => {
                    btnStart.disabled = false;
                    feedback.className = data.online ? "mt-2 text-success font-weight-bold" : "mt-2 text-warning";
                    feedback.textContent = data.message || "Perintah berhasil diproses.";
                    checkStatus(false);
                })
                .catch(err => {
                    btnStart.disabled = false;
                    feedback.className = "mt-2 text-danger";
                    feedback.textContent = "Terjadi kesalahan saat memanggil controller executor.";
                });
        });

        // Initial check
        checkStatus(false);
    })();
    </script>';
    $centralpage->add(new admin_setting_description('ai_central/executor_widget', '', $executorhtml));

    // -------------------------------------------------------------------------
    // SECTION 1: ADAPTIVE LEARNING AI SETTINGS
    // -------------------------------------------------------------------------
    $centralpage->add(new admin_setting_heading(
        'ai_central/sec_adaptive',
        '🧠 1. Pengaturan Adaptive Learning AI',
        'Konfigurasi model AI Gemini/OpenRouter dan ambang batas level kognitif untuk rekomendasi materi otomatis kursus.'
    ));

    $centralpage->add(new admin_setting_configpasswordunmask(
        'block_adaptive_learning_ai/gemini_apikey',
        'Kunci API OpenRouter / Gemini (Adaptive Learning)',
        'Masukkan API Key OpenRouter (sk-or-v1-...) atau Google AI Studio API Key.',
        ''
    ));

    $centralpage->add(new admin_setting_configselect(
        'block_adaptive_learning_ai/gemini_model',
        'Model AI Rekomendasi (Adaptive Learning)',
        'Pilih model kecerdasan buatan yang digunakan untuk menganalisis performa siswa dan merekomendasikan materi kursus.',
        'google/gemini-2.5-flash',
        [
            'google/gemini-2.5-flash'     => 'OpenRouter: Google Gemini 2.5 Flash (Sangat Direkomendasikan)',
            'google/gemini-2.0-flash-001' => 'OpenRouter: Google Gemini 2.0 Flash',
            'google/gemini-flash-1.5'     => 'OpenRouter: Google Gemini 1.5 Flash',
            'gemini-2.5-flash'            => 'Google AI Studio: Gemini 2.5 Flash',
            'gemini-2.0-flash'            => 'Google AI Studio: Gemini 2.0 Flash',
            'gemini-1.5-flash'            => 'Google AI Studio: Gemini 1.5 Flash',
        ]
    ));

    $centralpage->add(new admin_setting_configtext(
        'block_adaptive_learning_ai/primary_threshold',
        'Batas Ambang Level Dasar / Primary (Skor < nilai ini)',
        'Siswa dengan nilai kuis di bawah nilai ini dikategorikan Dasar (Primary) dan disarankan materi remedial/penguatan konsep dasar.',
        70,
        PARAM_INT
    ));

    $centralpage->add(new admin_setting_configtext(
        'block_adaptive_learning_ai/expert_threshold',
        'Batas Ambang Level Mahir / Expert (Skor >= nilai ini)',
        'Siswa dengan nilai kuis mencapai atau di atas nilai ini dikategorikan Mahir (Expert) dan disarankan materi pengayaan tingkat lanjut.',
        85,
        PARAM_INT
    ));

    // -------------------------------------------------------------------------
    // SECTION 2: AICODE PLUGIN SETTINGS
    // -------------------------------------------------------------------------
    $centralpage->add(new admin_setting_heading(
        'ai_central/sec_aicode',
        '💻 2. Pengaturan AICode (Modul Praktik Pemrograman AI)',
        'Konfigurasi provider AI umpan balik instruksional, endpoint executor, dan proteksi sandbox kode.'
    ));

    $provideroptions = [
        'openrouter' => 'OpenRouter (Rekomendasi untuk Fleksibilitas Model)',
        'gemini'     => 'Google Gemini API Langsung',
    ];
    $centralpage->add(new admin_setting_configselect(
        'aicode/ai_provider',
        'Penyedia AI (AI Provider AICode)',
        'Pilih penyedia API yang digunakan untuk memberikan analisis dan umpan balik kode siswa.',
        'openrouter',
        $provideroptions
    ));

    $centralpage->add(new admin_setting_configpasswordunmask(
        'aicode/openrouter_api_key',
        'Kunci API OpenRouter (AICode)',
        'API Key dari https://openrouter.ai/keys untuk fitur evaluasi kode program.',
        ''
    ));

    $centralpage->add(new admin_setting_configtext(
        'aicode/openrouter_model',
        'Model OpenRouter (AICode)',
        'Model yang digunakan pada OpenRouter (contoh: google/gemma-2-9b-it:free atau google/gemini-2.5-flash).',
        'google/gemma-2-9b-it:free',
        PARAM_TEXT
    ));

    $centralpage->add(new admin_setting_configpasswordunmask(
        'aicode/gemini_api_key',
        'Kunci API Google Gemini Langsung (AICode)',
        'Diperlukan jika penyedia AI AICode dipilih Google Gemini langsung.',
        ''
    ));

    $centralpage->add(new admin_setting_configtext(
        'aicode/gemini_model',
        'Model Gemini Langsung (AICode)',
        'Model yang digunakan untuk Google Gemini langsung (contoh: gemini-2.5-flash).',
        'gemini-2.5-flash',
        PARAM_TEXT
    ));

    $centralpage->add(new admin_setting_configtext(
        'aicode/executor_url',
        'URL Microservice Executor',
        'Alamat endpoint service Node.js sandbox eksekusi kode (default lokal: http://127.0.0.1:3001).',
        'http://127.0.0.1:3001',
        PARAM_URL
    ));

    $centralpage->add(new admin_setting_configtext(
        'aicode/execution_timeout',
        'Batas Waktu Eksekusi Kode / Timeout (detik)',
        'Maksimal waktu eksekusi kode sebelum dihentikan otomatis.',
        '2',
        PARAM_INT
    ));

    $centralpage->add(new admin_setting_configtext(
        'aicode/max_calls_per_day',
        'Batas Maksimum Panggilan AI per Siswa / Hari',
        'Membatasi frekuensi siswa meminta umpan balik AI dalam 24 jam untuk menghemat kuota API.',
        '50',
        PARAM_INT
    ));

    $centralpage->add(new admin_setting_configcheckbox(
        'aicode/security_check_enabled',
        'Aktifkan Pemeriksaan Keamanan Kode Server-side',
        'Memeriksa kode siswa dari sintaks mencurigakan sebelum dieksekusi di Node.js.',
        '1'
    ));

    // -------------------------------------------------------------------------
    // SECTION 3: LLM MOTIVATION & EMOTION SYSTEM SETTINGS
    // -------------------------------------------------------------------------
    $centralpage->add(new admin_setting_heading(
        'ai_central/sec_motivation',
        '💬 3. Pengaturan LLM Motivation & Emotion System',
        'Konfigurasi pesan dorongan motivasi adaptif dan check-in kesiapan emosi belajar siswa.'
    ));

    $centralpage->add(new admin_setting_configpasswordunmask(
        'local_llmmotivation/gemini_apikey',
        'Kunci API Gemini (LLM Motivation)',
        'API Key Google Gemini untuk pembuatan kalimat dorongan semangat personal.',
        ''
    ));

    $centralpage->add(new admin_setting_configtext(
        'local_llmmotivation/gemini_model',
        'Model Gemini (LLM Motivation)',
        'Model Gemini yang digunakan untuk motivasi (contoh: gemini-1.5-flash).',
        'gemini-1.5-flash',
        PARAM_TEXT
    ));

    $centralpage->add(new admin_setting_configcheckbox(
        'local_llmmotivation/admin_preview',
        'Mode Pengujian Admin & Guru (Preview Mode)',
        'Jika diaktifkan, administrator dan pengajar dapat menguji popup emosi dan dialog motivasi tanpa akun siswa.',
        0
    ));

    // Add Central AI Settings to Local Plugins Category
    $ADMIN->add('localplugins', $centralpage);
}
