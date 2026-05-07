# Instrumen 4 — Log aktivitas sistem (plugin AICode)

**Konteks penelitian:** Siswa SMK · JavaScript · Moodle **mod_aicode** · pencatatan perilaku untuk analisis perilaku/objektif.

**Tabel basis data:** Dengan prefiks Moodle standar **`mdl_`**, nama fisik lengkap **`mdl_aicode_activity_log`**. Pada kueri Moodle dan laporan lain di plugin, nama logisnya **`prefix_aicode_activity_log`** (mis. `mdl_` diturunkan ke `{aicode_activity_log}`).

**Catatan penting:** Tabel ini hanya menyimpan **metadata kejadian**; **tidak menyimpan source code**.

---

## 1. Skema kolom (sesuai `db/install.xml` / `upgrade.php`)

| Kolom DB | Tipe umum | Keterangan |
|----------|-----------|------------|
| **id** | INT, PK auto | Nomor urut baris log |
| **id_kursus** | INT, NOT NULL | ID kursus Moodle (`course.id`) |
| **id_modul** | INT, NOT NULL | ID *course module* (`course_modules.id`) — tautan aktivitas di kursus |
| **id_aktivitas_aicode** | INT, NOT NULL | ID baris aktivitas pada tabel **`mdl_aicode`** (= `aicode.id`) |
| **id_pengguna** | INT, NOT NULL | ID pengguna Moodle (`user.id`) |
| **kode_kejadian** | CHAR(32) | Kode jenis kejadian (internal); lihat §3 |
| **metadata_json** | TEXT, boleh NULL | Data tambahan kecil sebagai JSON (**tanpa** kode sumber) |
| **nama_lengkap** | CHAR(255), boleh NULL | *Snapshot* nama lengkap pengguna pada saat dicatat |
| **mode_aktivitas** | CHAR(16), default `training` | Mode aktivitas pada saat kejadian: **`training`** (latihan) atau **`exam`** (ujian) |
| **jumlah_ai_hint** | INT, default 0 | **Kumulatif** jumlah kejadian **permintaan analisis AI** (`ai_analyze`) hingga dan termasuk baris ini |
| **jumlah_run** | INT, default 0 | **Kumulatif** estimasi jumlah eksekusi *run* (maksimum antara hitungan percobaan di `mdl_aicode_attempts` dan log kejadian `code_run` / `code_run_blocked`) |
| **jumlah_kirim_guru** | INT, default 0 | **Kumulatif** jumlah pengiriman ke guru berdasarkan data percobaan |
| **nilai_snapshot** | NUMBER, boleh NULL | Cuplikan nilai **buku nilai** Moodle (nilai berskala biasanya **0–100**) jika sudah ada saat dicatat; jika tidak ada = NULL |
| **waktu_dicatat** | INT, UNIX time | **Waktu pencatatan** dalam detik UNIX (timezone server) |

Referensi penyisipan pada kode: `classes/local/activity_log.php` (`record()` dan konstanta `ACTION_*`).

---

## 2. Mengambil data untuk penelitian

1. **Ekspor CSV (disarankan):** administrator situs Moodle membuka **`/mod/aicode/activity_log_manage.php`**, pakai filter kursus/tanggal, unduh CSV. Ekspor menambahkan kolom bergabung antara lain: nama pengguna (*username*), *email*, nama/singkat kursus, **nama aktivitas AICode** dari `mdl_aicode.name`, serta label kejadian dalam bahasa paket bahasa halaman tersebut.
2. **Kueri SQL langsung:** pilih kolom sesuai tabel **`mdl_aicode_activity_log`**, bisa di-*JOIN* `mdl_user`, `mdl_course`, `mdl_course_modules`, `mdl_aicode` seperti pada skrip ekspor di `activity_log_manage.php`.

---

## 3. Daftar **`kode_kejadian`** (nilai kolom **`kode_kejadian`**)

Sesuai konstanta di `classes/local/activity_log.php` dan label Indonesia di `lang/id/aicode.php`:

| kode_kejadian | Deskripsi (Bahasa Indonesia) |
|---------------|------------------------------|
| `activity_view` | Membuka halaman aktivitas |
| `code_run` | Menjalankan kode (eksekutor) |
| `code_run_blocked` | Eksekusi dicekal (keamanan) |
| `ai_analyze` | Meminta AI Hint / analisis AI |
| `hint_recorded` | Mencatat penggunaan petunjuk |
| `send_to_teacher` | Mengirim pekerjaan ke guru |
| `run_history_view` | Membuka riwayat run |

---

## 4. Lembar dokumentasi sampel satu baris = satu rekaman di `mdl_aicode_activity_log`

*(Salin ke spreadsheet; satu baris = satu sisipan baru ke dalam tabel tersebut. Untuk pengisian besar, lebih praktis memakai unduhan CSV administrator.)*

| id | id_kursus | id_modul | id_aktivitas_aicode | id_pengguna | kode_kejadian | metadata_json | nama_lengkap | mode_aktivitas | jumlah_ai_hint | jumlah_run | jumlah_kirim_guru | nilai_snapshot | waktu_dicatat |
|----|-----------|----------|---------------------|-------------|---------------|---------------|--------------|----------------|----------------|------------|-------------------|----------------|---------------|
| | | | | | | | | | | | | | |
| | | | | | | | | | | | | | |
| | | | | | | | | | | | | | |
| | | | | | | | | | | | | | |
| | | | | | | | | | | | | | |

**Legenda cepat:**
- **`waktu_dicatat`** di DB = bilangan UNIX; untuk laporan bisa dikonversi ke tanggal jam lokal (*userdate* Moodle atau Excel).
- **`jumlah_ai_hint`**, **`jumlah_run`**, **`jumlah_kirim_guru`** pada tiap baris adalah **snapshot kumulatif** pada saat kejadian itu dicatat (cocok untuk melihat trajektori aktivitas siswa rekursif ke waktu).

---

## 5. Agregat untuk analisis (contoh yang selaras struktur ini)

- Frekuensi per **`kode_kejadian`** per siswa (**`id_pengguna`**) per **`id_aktivitas_aicode`** atau seluruh kursus (**`id_kursus`**).
- Rata-rata atau median **`jumlah_run`** dan **`jumlah_ai_hint`** pada baris akhir sesi/periode untuk membandingkan kelompok E vs K (**tambahkan kode kelompok di luar DB** atau lembar kode paralel siswa–kelompok).
- **`nilai_snapshot`** pada rekaman terbaru mendekati waktu tugas/submit sebagai perkiraan konsistensi dengan buku nilai.
- Analisis isi **`metadata_json`** (jika diisi) secara tematik — tanpa kode sumber; patuhi kebijakan privasi.
