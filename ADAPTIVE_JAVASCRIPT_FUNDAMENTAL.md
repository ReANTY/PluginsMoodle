# Adaptive JavaScript Fundamental — Course Lengkap (Siap Moodle)

**Judul course:** Adaptive JavaScript Fundamental  
**Shortname disarankan:** `ADAPT-JS-FUND-2026`  
**Format:** Topics, 8 section (1 minggu = 1 section)  
**Durasi per minggu:** ±60 menit (self-paced, mobile-friendly)  
**Bahasa:** Indonesia  
**Konteks:** Sekolah (nilai, absensi, jadwal, profil siswa)  
**Prasyarat peserta:** Sudah sedikit HTML/CSS  
**Lingkungan coding:** Bebas (Console browser / HTML+script / Replit / CodePen / VS Code) — course hanya memberi perintah  

---

## CPMK Course (Capaian Akhir)

Setelah menyelesaikan course ini, peserta mampu:

1. Menjelaskan konsep dasar JavaScript dan menjalankan kode sederhana di browser.
2. Menggunakan variabel, tipe data, dan operator untuk memproses data sekolah.
3. Menerapkan percabangan dan perulangan pada skenario kelas.
4. Membuat function dan struktur data (array, object) untuk program kecil.
5. Mengintegrasikan konsep dalam satu mini project pilihan.

---

## Model Adaptif (Tanpa Placement Quiz)

| Komponen | Keterangan |
|----------|------------|
| **Minggu 1** | Semua peserta: **Materi Umum** + **Quiz Umum** (Bloom C3, tanpa label level) |
| **Minggu 2–8** | Tiga jalur tersembunyi (admin: DASAR / INTI / LANJUTAN). Peserta **hanya melihat satu jalur** per minggu |
| **Per jalur** | 1 unit microlearning + 1 quiz |
| **Bloom** | DASAR = C1–C2 · INTI = C3 · LANJUTAN = C4–C6 |
| **Quiz** | 3 attempt, **Highest grade**, time limit 20 menit |
| **Branching** | Nilai quiz jalur minggu ini → jalur minggu depan: **&lt;70 DASAR · 70–80 INTI · &gt;80 LANJUTAN** |

### Label untuk peserta (jangan pakai LOW/MEDIUM/HIGH)

| Internal (admin) | Tampilan ke siswa |
|----------------|-------------------|
| DASAR | Materi + Quiz biasa (tanpa kata “dasar”) |
| INTI | Materi + Quiz biasa |
| LANJUTAN | Materi + Quiz biasa |
| Minggu 1 | **Materi Umum** · **Quiz Pemahaman Minggu 1** |

Gunakan **nama aktivitas sama** per jalur yang terlihat (mis. `Materi Minggu 2`, `Quiz Minggu 2`) — siswa hanya melihat satu pasang karena Restrict Access.

---

## Panduan Implementasi Moodle

### Urutan aktivitas per section (contoh Minggu 2)

1. `Page` — Ringkasan Minggu (semua siswa)
2. `Page` — Materi Minggu 2 **[Jalur DASAR]** — restrict: nilai Quiz Minggu 1 &lt; 70
3. `Quiz` — Quiz Minggu 2 **[Jalur DASAR]** — restrict: selesai materi DASAR; grade → minggu 3
4. `Page` — Materi Minggu 2 **[Jalur INTI]** — restrict: Quiz M1 grade 70–80
5. `Quiz` — Quiz Minggu 2 **[Jalur INTI]** — ...
6. `Page` — Materi Minggu 2 **[Jalur LANJUTAN]** — restrict: Quiz M1 grade &gt; 80
7. `Quiz` — Quiz Minggu 2 **[Jalur LANJUTAN]** — ...

### Restrict Access — Minggu 2 (berdasarkan Quiz Minggu 1)

| Aktivitas | Kondisi (grade Quiz Minggu 1 / Quiz Umum) |
|-----------|-------------------------------------------|
| Materi + Quiz DASAR | **&lt; 70** |
| Materi + Quiz INTI | **≥ 70 dan ≤ 80** |
| Materi + Quiz LANJUTAN | **&gt; 80** |

### Restrict Access — Minggu 3+ (berdasarkan quiz jalur minggu lalu)

Untuk **Materi Minggu 3 — Jalur DASAR**, gunakan **OR** pada tiga grade item quiz minggu 2:

- Grade `Quiz M2 [DASAR]` &lt; 70, **atau**
- Grade `Quiz M2 [INTI]` &lt; 70, **atau**
- Grade `Quiz M2 [LANJUTAN]` &lt; 70  

(Ulangi pola untuk INTI: 70–80 pada quiz mana pun yang dikerjakan; LANJUTAN: &gt;80.)

**Tips Moodle:** Di setiap quiz, centang *Activity completion* → *Student must receive a grade*. Restrict Access → *Grade* → pilih quiz minggu sebelumnya (buat 3 aturan OR per jalur tujuan).

### Activity Completion

| Aktivitas | Completion |
|-----------|------------|
| Page (materi) | View |
| Quiz | Receive grade (min 1 attempt) |

### Pengaturan Quiz (semua)

- Attempts allowed: **3**
- Grading method: **Highest grade**
- Time limit: **20 menit**
- Shuffle questions: Yes
- Passing grade (opsional sertifikat): 70

### Gradebook

- Satu kategori per minggu: `Minggu 1` … `Minggu 8`
- Bobot disarankan: tiap quiz minggu = 12.5% (8 minggu = 100%)
- Minggu 8: quiz + pilihan project (Assignment) bisa 15% quiz + 15% project

---

# MINGGU 1 — Introduction to JavaScript

**SECTION:** Week 1 — Introduction to JavaScript  
**Estimasi:** 60 menit  
**Semua peserta** — tidak ada branching di dalam minggu ini.

## CPMK

"Setelah menyelesaikan minggu ini, peserta mampu menjelaskan peran JavaScript di web sekolah, menjalankan kode sederhana di browser, dan menampilkan output dasar menggunakan `console.log`."

## Ringkasan Minggu (Page — semua siswa)

**Judul aktivitas:** `Ringkasan Minggu 1`  
**Moodle:** Page · 5 menit baca  

Isi ringkas:
- JavaScript membuat halaman web interaktif (tombol, validasi form, animasi).
- Untuk latihan, gunakan **Console browser** (F12 → Console) atau file HTML dengan `<script>`.
- Minggu ini: pengenalan + latihan output. Quiz di akhir menentukan materi minggu depan.
- Target waktu: 60 menit.

---

## MATERI UMUM (Bloom C3 — Applying)

**Judul aktivitas (siswa):** `Materi Umum — Pengenalan JavaScript`  
**Moodle:** Page · **Microlearning 1 unit** · 20 menit  

**Objective:** Menerapkan perintah dasar JavaScript untuk menampilkan informasi terkait sekolah.

**Konten microlearning (salin ke Page):**

```html
<h3>Pengenalan JavaScript untuk Project Sekolah</h3>

<p>JavaScript adalah bahasa yang membuat website <strong>berinteraksi</strong>. Jika HTML membuat struktur halaman, JavaScript membuat halaman itu "hidup": tombol bisa diklik, nilai bisa dihitung, pesan bisa berubah.</p>

<h4>1. Menjalankan JavaScript</h4>
<p><strong>Cara termudah:</strong> Buka situs apa pun di browser → tekan <kbd>F12</kbd> → tab <strong>Console</strong> → ketik kode → Enter.</p>

<h4>2. Output pertama</h4>
<pre><code>console.log("Halo dari kelas 10A!");
console.log("Hari ini pelajaran Informatika");</code></pre>

<h4>3. Menyimpan data sederhana (persiapan minggu depan)</h4>
<pre><code>let namaSekolah = "SMAN 1 Contoh";
let kelas = "10 IPA 2";
console.log(namaSekolah, "-", kelas);</code></pre>

<h4>4. Latihan terapan (15 menit)</h4>
<ol>
  <li>Buka Console browser.</li>
  <li>Tampilkan nama Anda, kelas, dan 1 mapel favorit (3 baris <code>console.log</code>).</li>
  <li>Tambahkan 1 baris yang menampilkan pesan motivasi belajar.</li>
</ol>

<p><em>Catatan:</em> Jika Console tidak tersedia di HP, buat file <code>latihan.html</code> dengan tag <code>&lt;script&gt;</code> di bawah body.</p>
```

**Contoh file HTML opsional (URL/File resource):**

```html
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Latihan JS</title></head>
<body>
  <h1>Latihan JavaScript</h1>
  <script>
    console.log("Website kelas siap belajar JS!");
  </script>
</body>
</html>
```

---

## QUIZ UMUM MINGGU 1 (Bloom C3)

**Judul aktivitas (siswa):** `Quiz Pemahaman Minggu 1`  
**Moodle:** Quiz · 10 soal · 20 menit · 3 attempt · highest grade  

**RULE (menentukan Minggu 2):**
- &lt;70 → Jalur DASAR (Minggu 2)
- 70–80 → Jalur INTI
- &gt;80 → Jalur LANJUTAN

### Soal Quiz Minggu 1

1. **(MCQ C3)** Manakah yang paling tepat menjelaskan fungsi JavaScript di website sekolah?  
   - a) Menyimpan data siswa di server secara permanen  
   - b) Membuat halaman web dapat bereaksi terhadap input pengguna ✓  
   - c) Mengganti fungsi HTML sepenuhnya  
   - d) Hanya untuk membuat desain warna  

2. **(MCQ C1)** Perintah untuk menampilkan teks di Console adalah …  
   - a) `print()`  
   - b) `console.log()` ✓  
   - c) `echo()`  
   - d) `display()`  

3. **(MCQ C2)** Apa output kode berikut?  
   ```javascript
   console.log("A");
   console.log("B");
   ```  
   - a) AB (satu baris)  
   - b) A lalu B di baris terpisah ✓  
   - c) Error  
   - d) Tidak ada output  

4. **(MCQ C3)** Siswa ingin menampilkan `"Selamat belajar!"` di Console. Kode yang benar:  
   - a) `console.log(Selamat belajar!);`  
   - b) `console.log("Selamat belajar!");` ✓  
   - c) `Console.log('Selamat belajar!")`  
   - d) `log.console("Selamat belajar!");`  

5. **(MCQ C2)** Manakah penulisan string yang valid di JavaScript?  
   - a) `'Kelas 10'` ✓  
   - b) `"Kelas 10`  
   - c) `Kelas 10`  
   - d) `<Kelas 10>`  

6. **(MCQ C3)** Untuk project profil kelas, urutan langkah paling logis:  
   - a) Quiz dulu, baru buka Console  
   - b) Buka Console → tulis kode → jalankan → cek output ✓  
   - c) Hapus HTML → tulis JS  
   - d) Install database → tulis JS  

7. **(Coding snippet C3)** Lengkapi agar menampilkan nama sekolah:  
   ```javascript
   let sekolah = "SMAN 1";
   __________(sekolah);
   ```  
   Jawaban: `console.log`  

8. **(MCQ C3)** Hasil yang diharapkan dari kode:  
   ```javascript
   let kelas = "10 IPA";
   console.log("Saya di kelas", kelas);
   ```  
   - a) `Saya di kelas 10 IPA` ✓  
   - b) `kelas`  
   - c) Error karena ada spasi  
   - d) `10 IPA Saya di kelas`  

9. **(Debugging C3)** Baris mana yang salah?  
   ```javascript
   consol.log("Halo");
   ```  
   - a) `consol` seharusnya `console` ✓  
   - b) Tidak boleh pakai tanda kutip  
   - c) `log` harus kapital  
   - d) Tidak ada error  

10. **(MCQ C2)** Komentar satu baris di JavaScript ditulis dengan …  
    - a) `//` ✓  
    - b) `<!--`  
    - c) `**`  
    - d) `#`  

---

## MOODLE ACTIVITIES — Minggu 1

| Urutan | Nama (siswa) | Tipe | Restrict Access |
|--------|--------------|------|-----------------|
| 1 | Ringkasan Minggu 1 | Page | — |
| 2 | Materi Umum — Pengenalan JavaScript | Page | — |
| 3 | Quiz Pemahaman Minggu 1 | Quiz | Setelah view Materi Umum (opsional) |

---

# MINGGU 2 — Variables and Data Types

**SECTION:** Week 2 — Variables and Data Types  
**Estimasi:** 60 menit · **Branching aktif**

## CPMK

"Setelah menyelesaikan minggu ini, peserta mampu menyimpan data siswa menggunakan variabel dan mengenali tipe data dasar JavaScript."

## Ringkasan Minggu (Page — semua)

**Judul:** `Ringkasan Minggu 2`  
Isi: Variabel (`let`, `const`), tipe data `string`, `number`, `boolean`. Satu materi + satu quiz sesuai hasil minggu lalu.

---

## JALUR DASAR (Bloom C1–C2)

**Materi (siswa):** `Materi Minggu 2`  
**Moodle:** Page · 20 menit · Restrict: Quiz M1 &lt; 70  

**Objective (C1–C2):** Mengingat istilah variabel dan memahami tipe data dasar.

**Konten:**

```html
<h3>Variabel: Kotak Penyimpan Data</h3>
<p><strong>Variabel</strong> seperti label di buku catatan: Anda memberi nama, lalu menyimpan nilai.</p>

<h4>let dan const</h4>
<ul>
  <li><code>let</code> — nilai bisa diubah (mis. nilai ulangan)</li>
  <li><code>const</code> — nilai tetap (mis. NIS)</li>
</ul>

<pre><code>let namaSiswa = "Andi";
const nis = "12345";
let nilai = 85;
let sudahHadir = true;

console.log(namaSiswa);
console.log(nilai);
console.log(sudahHadir);</code></pre>

<h4>Tiga tipe data dasar</h4>
<table>
  <tr><th>Tipe</th><th>Contoh</th><th>Untuk</th></tr>
  <tr><td>string</td><td>"10A"</td><td>Teks</td></tr>
  <tr><td>number</td><td>85</td><td>Angka</td></tr>
  <tr><td>boolean</td><td>true / false</td><td>Ya/Tidak</td></tr>
</table>

<h4>Latihan (ingat & pahami)</h4>
<ol>
  <li>Buat 4 variabel: nama, kelas, nilai Matematika, status hadir.</li>
  <li>Tampilkan semua di Console.</li>
  <li>Tebak tipe data tiap variabel sebelum dijalankan.</li>
</ol>
```

**Quiz (siswa):** `Quiz Minggu 2`  
**Moodle:** Quiz · 10 soal · Bloom C1–C2 · Restrict: selesai Materi  

### Soal Quiz Jalur DASAR — Minggu 2

1. **(C1)** Variabel digunakan untuk …  
   - a) Menyimpan data ✓  
   - b) Menghapus website  
   - c) Mengganti browser  
   - d) Membuat gambar  

2. **(C1)** Kata kunci untuk variabel yang nilainya bisa berubah:  
   - a) `let` ✓  
   - b) `fixed`  
   - c) `static`  
   - d) `var only`  

3. **(C2)** Tipe data dari `let kelas = "10B";` adalah …  
   - a) string ✓  
   - b) number  
   - c) boolean  
   - d) array  

4. **(C1)** `const` artinya …  
   - a) Nilai tidak boleh diubah setelah diberi ✓  
   - b) Variabel kosong  
   - c) Hanya untuk angka  
   - d) Hanya untuk teks  

5. **(C2)** Manakah boolean?  
   - a) `true` ✓  
   - b) `"true"`  
   - c) `1`  
   - d) `"false"`  

6. **(C1)** Tipe data angka 90 adalah …  
   - a) number ✓  
   - b) string  
   - c) boolean  
   - d) object  

7. **(C2)** Output: `let x = 5; console.log(x + 2);` →  
   - a) 7 ✓  
   - b) 52  
   - c) x+2  
   - d) Error  

8. **(C1)** Nama variabel yang benar:  
   - a) `nilaiUjian` ✓  
   - b) `2nilai`  
   - c) `nilai-ujian`  
   - d) `nilai ujian`  

9. **(C2)** `let hadir = false;` — `hadir` adalah …  
   - a) boolean ✓  
   - b) string  
   - c) number  
   - d) function  

10. **(C1)** Untuk menyimpan nama mapel, tipe paling tepat:  
    - a) string ✓  
    - b) boolean  
    - c) undefined only  
    - d) tidak perlu variabel  

**RULE:** Nilai quiz ini → jalur Minggu 3 (sama: &lt;70 / 70–80 / &gt;80).

---

## JALUR INTI (Bloom C3)

**Materi:** `Materi Minggu 2` · Restrict: Quiz M1 70–80  

**Objective:** Menerapkan variabel untuk skenario data siswa.

**Konten (ringkas):**

```html
<h3>Menerapkan Variabel di Data Siswa</h3>
<pre><code>const nis = "2024001";
let nama = "Siti";
let tugas1 = 78, tugas2 = 82, tugas3 = 90;
let rata = (tugas1 + tugas2 + tugas3) / 3;

console.log("NIS:", nis);
console.log("Rata-rata:", rata);</code></pre>

<p><strong>Tugas terapan:</strong> Buat variabel untuk 1 siswa (nama, kelas, 3 nilai, rata-rata). Tampilkan laporan 5 baris di Console.</p>
```

**Quiz:** 10 soal C3 (penerapan kode, hitung rata, pilih tipe tepat, lengkapi deklarasi variabel).

1. Rata dari 80, 70, 90 = 80 ✓  
2. `const nis = "A1"; nis = "A2";` → Error ✓  
3. Lengkapi: `let kelas = ___` → `"12 IPS"`  
4. Output `console.log("Nilai:", 85);` → Nilai: 85 ✓  
5. Variabel untuk status lulus (ya/tidak) → boolean ✓  
6. `let a=10, b="10"; console.log(a+b);` → `"1010"` (string concat) — jelaskan di feedback  
7. Pilih kode profil siswa lengkap (MCQ)  
8. Konversi: nilai string `"75"` + 5 → perlu `Number("75")`  
9. Mini task: deklarasi 3 mapel + nilai  
10. Debugging: typo `leet` → `let`  

---

## JALUR LANJUTAN (Bloom C4–C6)

**Materi:** `Materi Minggu 2` · Restrict: Quiz M1 &gt; 80  

**Objective:** Menganalisis pemilihan variabel, mengevaluasi kode, merancang struktur data mini.

**Konten:** Studi kasus data kelas berantakan (nama tidak konsisten, tipe campur). Refactoring ke `const` untuk ID, `let` untuk nilai. Tantangan: desain 3 object siswa dalam pola konsisten.

**Quiz:** 10 soal analisis/evaluasi/debugging (mana desain terbaik, evaluasi `var` vs `let`, temukan bug tipe, refactor snippet).

---

## MOODLE ACTIVITIES — Minggu 2

| Nama (siswa) | Tipe | Restrict |
|--------------|------|----------|
| Ringkasan Minggu 2 | Page | — |
| Materi Minggu 2 (×3 instance) | Page | Grade Quiz M1 |
| Quiz Minggu 2 (×3 instance) | Quiz | Grade Quiz M1 + completion materi |

---

# MINGGU 3 — Operators and Input

**SECTION:** Week 3 — Operators and Input  
**Estimasi:** 60 menit

## CPMK

"Setelah menyelesaikan minggu ini, peserta mampu menggunakan operator aritmatika, perbandingan, dan logika untuk memproses data sederhana terkait sekolah."

## Ringkasan Minggu (Page)

Operator `+ - * / %`, perbandingan (`>`, `<`, `===`), logika (`&&`, `||`). Input: `prompt()` atau nilai tetap jika perangkat mobile tidak mendukung prompt.

---

## JALUR DASAR — Materi + Quiz (C1–C2)

**Materi Minggu 3** (Page, 20 menit):

```html
<h3>Operator untuk Hitung Nilai</h3>
<ul>
  <li><strong>Aritmatika:</strong> + − * /</li>
  <li><strong>Perbandingan:</strong> &gt; &lt; &gt;= &lt;= ===</li>
  <li><strong>Logika:</strong> &amp;&amp; (dan), || (atau)</li>
</ul>
<pre><code>let ujian = 80;
let tugas = 90;
let total = ujian + tugas;
let rata = total / 2;
console.log("Rata:", rata);
console.log("Lulus?", rata >= 75);</code></pre>
```

**Quiz Minggu 3 (10 soal):**

1. `5 + 3` = **8** ✓  
2. Operator persamaan ketat: **`===`** ✓  
3. `10 > 5` = **true** ✓  
4. `&&` = kedua kondisi harus benar ✓  
5. `80 / 2` = **40** ✓  
6. `5 + "5"` → **"55"** (gabung string) ✓  
7. Sisa bagi: **`%`** ✓  
8. `nilai >= 75` artinya nilai minimal 75 ✓  
9. `!true` = **false** ✓  
10. Untuk membandingkan, jangan pakai `=` tunggal — pakai **`===`** ✓  

---

## JALUR INTI — Materi + Quiz (C3)

**Materi:** Input nilai + keputusan sederhana.

```javascript
let nilai = Number(prompt("Nilai (0-100):") || 75);
let lulus = nilai >= 75;
console.log("Status:", lulus ? "Lulus" : "Remedial");
```

**Quiz (10 soal):** Hitung rata 3 nilai; `Number("80")+10`; prediksi `&&`/`||`; diskon buku 10%; debugging `=` vs `===`; lengkapi ekspresi `hadir && nilai>=75`.

---

## JALUR LANJUTAN — Materi + Quiz (C4–C6)

**Materi:** Studi kasus: `(nilai >= 75) && (hadir === true)`. Evaluasi prioritas operator. Desain validasi input 0–100.

**Quiz (10 soal):** Analisis ekspresi kompleks; evaluasi bug `5 + null`; refactoring validasi; pilih desain aturan kelulusan terbaik.

**RULE:** Quiz M3 → jalur M4.

---

# MINGGU 4 — Conditional Statements

**SECTION:** Week 4 — Conditional Statements  
**Estimasi:** 60 menit

## CPMK

"Setelah menyelesaikan minggu ini, peserta mampu menggunakan `if`, `else if`, dan `else` untuk keputusan sederhana (nilai, absensi)."

## Ringkasan Minggu (Page)

Percabangan: lulus/remedial, grade A–D, kehadiran. Satu materi + satu quiz.

---

## JALUR DASAR (C1–C2)

**Materi:**

```javascript
let nilai = 78;
if (nilai >= 75) {
  console.log("Lulus");
} else {
  console.log("Remedial");
}
```

**Quiz (10 soal):** Fungsi `if`; arti `else`; output prediksi; kapan kondisi true; `else if` untuk banyak cabang; error kurung `{}`; nilai 60 → Remedial; `if (hadir)` dengan boolean; pilih struktur untuk 3 kategori; `switch` vs `if` (pengenalan).

---

## JALUR INTI (C3)

**Materi:**

```javascript
let n = 82;
if (n >= 90) console.log("A");
else if (n >= 80) console.log("B");
else if (n >= 70) console.log("C");
else console.log("D");
```

**Quiz:** Kategori nilai 85→B; lengkapi `else if`; absensi + nilai; `switch` hari mapel; debugging urutan `else if` salah.

---

## JALUR LANJUTAN (C4–C6)

**Materi:** Nested if `(nilai>=75 && hadir)`. Evaluasi refactor ke function `getGrade(n)`.

**Quiz:** Analisis flowchart; bug nested salah; evaluasi keterbacaan; desain aturan kompetisi kelas.

---

# MINGGU 5 — Loops

**SECTION:** Week 5 — Loops  

## CPMK

"Setelah menyelesaikan minggu ini, peserta mampu menggunakan `for` dan `while` untuk tugas berulang (daftar siswa, cetak jadwal)."

---

## JALUR DASAR (C1–C2)

**Materi:**

```javascript
for (let i = 1; i <= 5; i++) {
  console.log("Pertemuan ke-" + i);
}
```

**Quiz:** Komponen for (init; kondisi; increment), output loop, kapan loop berhenti.

---

## JALUR INTI (C3)

**Materi:** Hitung total nilai dari array angka dengan loop.

```javascript
let nilai = [80, 75, 90];
let total = 0;
for (let i = 0; i < nilai.length; i++) {
  total += nilai[i];
}
console.log("Total:", total, "Rata:", total / nilai.length);
```

**Quiz:** Penerapan loop + array angka, perbaiki off-by-one.

---

## JALUR LANJUTAN (C4–C6)

**Materi:** Bandingkan `for` vs `while`; deteksi infinite loop; generator daftar piket kelas.

**Quiz:** Debugging loop tak berhenti, evaluasi efisiensi, desain solusi piket.

---

# MINGGU 6 — Functions

**SECTION:** Week 6 — Functions  

## CPMK

"Setelah menyelesaikan minggu ini, peserta mampu membuat function dengan parameter dan `return` untuk kode yang rapi dan reusable."

---

## JALUR DASAR (C1–C2)

**Materi:**

```javascript
function sapa(nama) {
  return "Halo, " + nama + "!";
}
console.log(sapa("Rina"));
```

**Quiz:** Definisi function, parameter, return, panggilan function.

---

## JALUR INTI (C3)

**Materi:**

```javascript
function hitungRata(a, b, c) {
  return (a + b + c) / 3;
}
console.log(hitungRata(80, 90, 70));
```

**Quiz:** Tulis function, prediksi return, mini task kalkulator nilai.

---

## JALUR LANJUTAN (C4–C6)

**Materi:** Refactor kode duplikat menjadi modul function; evaluasi single responsibility.

**Quiz:** Debugging missing return, analisis desain, challenge utility library kelas.

---

# MINGGU 7 — Arrays and Objects

**SECTION:** Week 7 — Arrays and Objects  

## CPMK

"Setelah menyelesaikan minggu ini, peserta mampu menyimpan data terstruktur dengan array dan object untuk daftar siswa atau mapel."

---

## JALUR DASAR (C1–C2)

**Materi:**

```javascript
let mapel = ["Matematika", "IPA", "Bahasa"];
let siswa = { nama: "Budi", kelas: "10A", hadir: true };
console.log(mapel[0]);
console.log(siswa.nama);
```

**Quiz:** Index array, akses properti object, tipe data.

---

## JALUR INTI (C3)

**Materi:** Array of objects — daftar 3 siswa.

```javascript
let kelas = [
  { nama: "Ani", nilai: 85 },
  { nama: "Budi", nilai: 78 }
];
console.log(kelas[1].nama);
```

**Quiz:** Penerapan loop + object, tambah properti, mini task daftar perpustakaan.

---

## JALUR LANJUTAN (C4–C6)

**Materi:** Evaluasi struktur data untuk laporan kelas; refactoring; filter manual dengan loop.

**Quiz:** Analisis skema data, debugging key typo, desain model data project.

---

# MINGGU 8 — Final Project

**SECTION:** Week 8 — Final Project  

## CPMK

"Setelah menyelesaikan minggu ini, peserta mampu mengintegrasikan variabel, operator, kondisi, loop, function, array/object dalam satu mini project pilihan."

## Ringkasan Minggu

Review singkat + pilih 1 dari 3 project. Satu materi + satu quiz per jalur + Assignment project.

---

## MATERI & QUIZ PER JALUR

### DASAR (C1–C2)

**Materi:** Template terpandu — isi bagian TODO (variabel siswa, 1 if nilai, 1 loop daftar mapel).

**Quiz:** Review konsep lintas minggu (ingat & pahami), 10 soal MCQ konsep gabungan ringan.

### INTI (C3)

**Materi:** Panduan implementasi project pilihan dengan checklist fitur wajib.

**Quiz:** Penerapan integrasi (snippet lengkapi function + array).

### LANJUTAN (C4–C6)

**Materi:** Kriteria penilaian project (struktur, validasi, keterbacaan, komentar).

**Quiz:** Analisis kode project, evaluasi bug, refactoring 1 function.

---

## FINAL PROJECT — 3 PILIHAN (Assignment)

**Moodle:** Assignment · Submit teks + link file/zip · Restrict: selesai quiz minggu 8 (jalur yang aktif)

Peserta **pilih 1**:

### Opsi 1: Kalkulator Nilai Siswa
- Input 3 nilai tugas (boleh `prompt` atau nilai contoh).
- Hitung rata-rata.
- Tampilkan grade (A/B/C/D) dan status lulus (≥75).

### Opsi 2: Pencatat Kehadiran Kelas
- Simpan minimal 5 siswa (nama + hadir boolean) dalam array of objects.
- Hitung jumlah hadir dan persentase kehadiran.
- Tampilkan ringkasan di Console.

### Opsi 3: Perencana Tugas Sekolah (To-Do)
- Minimal 5 tugas dalam array.
- Tampilkan daftar ber nomor (loop).
- Hitung berapa tugas dengan status `selesai: true`.

**Instruksi submit:** Screenshot Console + salinan kode (file .js atau .html). Lingkungan coding bebas.

**Starter code (Assignment description):**

```javascript
// Pilih salah satu opsi project di atas.
// Ganti komentar ini dengan solusi Anda.

function main() {
  // TODO: implementasi project
  console.log("=== Final Project ===");
}

main();
```

---

## MOODLE ACTIVITIES — Minggu 8

| Aktivitas | Tipe |
|-----------|------|
| Ringkasan Minggu 8 | Page |
| Materi Minggu 8 (×3) | Page |
| Quiz Minggu 8 (×3) | Quiz |
| Final Project (pilih 1 dari 3 opsi) | Assignment |

---

# LAMPIRAN A — Daftar Aktivitas Lengkap (Import Manual)

## Naming convention (admin / backup)

| Minggu | Instance | Nama internal (admin only) |
|--------|----------|----------------------------|
| 1 | — | `W1-Overview`, `W1-Materi-Umum`, `W1-Quiz-Umum` |
| 2 | DASAR | `W2-DASAR-Materi`, `W2-DASAR-Quiz` |
| 2 | INTI | `W2-INTI-Materi`, `W2-INTI-Quiz` |
| 2 | LANJUTAN | `W2-LANJUTAN-Materi`, `W2-LANJUTAN-Quiz` |
| … | … | Pola sama W3–W8 |
| 8 | + | `W8-Final-Project` |

**Nama tampilan siswa:** `Ringkasan Minggu N`, `Materi Minggu N`, `Quiz Minggu N`, `Final Project`.

Total aktivitas: **1 + 2 + 3×(2)×7 + 1 = 46** aktivitas inti (tanpa sertifikat).

---

# LAMPIRAN B — Bank Soal Quiz INTI & LANJUTAN (Minggu 2–7)

Gunakan pola Bloom yang sama seperti contoh Minggu 2. Setiap quiz: **10 soal**, campuran MCQ + snippet + debugging.

## Minggu 2 — INTI (10 soal)

1. Rata (80+70+90)/3 = **80** ✓  
2. `const nis="A1"; nis="A2"` → **Error** ✓  
3. Lengkapi: `let kelas = "10 IPA";` ✓  
4. `console.log("Nilai:", 85)` → **Nilai: 85** ✓  
5. Status lulus → tipe **boolean** ✓  
6. `let a=10,b="10"; a+b` → **"1010"** ✓  
7. Pilih deklarasi profil siswa lengkap (MCQ) ✓  
8. Ubah `"75"` ke angka: **Number("75")** ✓  
9. Deklarasi 3 mapel + nilai (essay/snippet)  
10. Typo **leet** → **let** ✓  

## Minggu 2 — LANJUTAN (10 soal)

1. Evaluasi: variabel tanpa nama jelas — **buruk** ✓  
2. `var` di loop modern — evaluasi **hindari** ✓  
3. Analisis tipe campur string+number pada total ✓  
4. Refactor ke `const` untuk NIS ✓  
5. Desain object siswa terbaik (MCQ) ✓  
6–10. Debugging akses properti, evaluasi struktur 3 siswa, tantangan konsistensi key.

## Minggu 5 — INTI (10 soal)

1. Total array `[70,80,90]` dengan loop = **240** ✓  
2. Perbaiki `i <= nilai.length` → **`i < nilai.length`** ✓  
3. Cetak nomor absen 1–30: gunakan **for** ✓  
4. `while` cocok untuk kondisi tidak pasti iterasi ✓  
5–10. Off-by-one, akumulasi `total += arr[i]`, mini generator jadwal.

## Minggu 7 — LANJUTAN (10 soal)

1. Skema data laporan kelas terbaik (MCQ) ✓  
2. Bug `siswa.namaa` typo ✓  
3. Loop filter hadir=true manual ✓  
4. Evaluasi: array of objects vs object of arrays ✓  
5–10. Refactor, analisis kompleksitas, desain untuk final project.

---

# LAMPIRAN C — Restrict Access Cheat Sheet

### Minggu 2

| Target | Condition |
|--------|-----------|
| W2 DASAR | Grade `W1 Quiz` &lt; 70% |
| W2 INTI | Grade `W1 Quiz` ≥ 70% AND ≤ 80% |
| W2 LANJUTAN | Grade `W1 Quiz` &gt; 80% |

### Minggu 3 (contoh DASAR)

| Target | Condition (OR) |
|--------|----------------|
| W3 DASAR | `W2 Quiz DASAR` &lt; 70 OR `W2 Quiz INTI` &lt; 70 OR `W2 Quiz LANJUTAN` &lt; 70 |
| W3 INTI | Grade masing-masing quiz W2 antara 70–80 (3 aturan OR) |
| W3 LANJUTAN | Grade masing-masing quiz W2 &gt; 80 (3 aturan OR) |

Ulangi pola untuk W4–W8.

---

# LAMPIRAN D — Course Summary HTML (Moodle course summary)

```html
<div class="course-summary">
  <h3>Adaptive JavaScript Fundamental</h3>
  <p>Course 8 minggu untuk siswa yang sudah mengenal HTML/CSS. Metode <strong>microlearning</strong> (~60 menit/minggu), konteks sekolah, belajar mandiri.</p>
  <h4>Alur adaptif</h4>
  <ul>
    <li>Minggu 1: materi umum + quiz</li>
    <li>Minggu 2–8: materi disesuaikan hasil quiz minggu sebelumnya</li>
    <li>Quiz: 3 percobaan, nilai tertinggi dihitung</li>
  </ul>
  <h4>Minggu</h4>
  <ol>
    <li>Pengenalan JavaScript</li>
    <li>Variabel & Tipe Data</li>
    <li>Operator & Input</li>
    <li>Percabangan</li>
    <li>Perulangan</li>
    <li>Function</li>
    <li>Array & Object</li>
    <li>Final Project</li>
  </ol>
</div>
```

---

**Dokumen ini siap dipindah ke Moodle** (copy-paste Page, buat Quiz dari daftar soal, atur Restrict Access sesuai LAMPIRAN C). Untuk otomasi builder PHP, gunakan shortname `ADAPT-JS-FUND-2026` dan struktur 3 jalur × (materi + quiz) per minggu.

*Versi: 1.0 — Adaptive model tanpa placement quiz · Minggu 1 materi umum · Branching via quiz per jalur*
