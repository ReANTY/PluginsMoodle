# Instrumen penelitian — indeks (SMK, JavaScript, Moodle AICode)

Isi lengkap **dipisah per file** di folder `instrumen/`, plus **satu berkas HTML** berisi kelima instrumen dalam bentuk tabel bermborder (gaya seperti `docs/blackbox-testing.html`):

| Format | Keterangan |
|--------|------------|
| **HTML (cetak / PDF)** | [docs/instrumen-penelitian-lampiran.html](docs/instrumen-penelitian-lampiran.html) — Lampiran 1–5 dalam satu dokumen, pemisah halaman cetak antar instrumen |

### File Markdown (teks sumber)

| No | Instrumen | File |
|----|-----------|------|
| 1 | Tes kemampuan pemrograman (JavaScript) | [instrumen/01_tes_kemampuan_pemrograman_javascript.md](instrumen/01_tes_kemampuan_pemrograman_javascript.md) |
| 2 | Kuesioner persepsi siswa (Likert 1–5) | [instrumen/02_kuesioner_persepsi_siswa.md](instrumen/02_kuesioner_persepsi_siswa.md) |
| 3 | Pedoman wawancara (siswa) | [instrumen/03_pedoman_wawancara_siswa.md](instrumen/03_pedoman_wawancara_siswa.md) |
| 4 | Log aktivitas sistem (AICode) | [instrumen/04_log_aktivitas_sistem_aicode.md](instrumen/04_log_aktivitas_sistem_aicode.md) |
| 5 | Evaluasi usability (SUS) | [instrumen/05_evaluasi_usability_sus.md](instrumen/05_evaluasi_usability_sus.md) |

**Konteks singkat:** siswa SMK · JavaScript · kelompok E = umpan balik LLM, K = tanpa LLM · keduanya plugin **mod_aicode** (mode latihan / ujian sesuai desain Anda).

---

## Daftar periksa sebelum lapangan

- [ ] Izin sekolah dan informed consent (orang tua jika diwajibkan).  
- [ ] Course “JavaScript” berisi aktivitas AICode dengan pengaturan mode konsisten per kelompok.  
- [ ] Kelompok E: fitur umpan balik LLM aktif sesuai desain; Kelompok K: sama tanpa akses LLM ke siswa.  
- [ ] Akun uji guru sudah memverifikasi ekspor log / laporan.  
- [ ] Cadangan jika layanan eksekutor atau AI offline (prosedur pengganti hari tes).
