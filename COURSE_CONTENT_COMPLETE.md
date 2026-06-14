# JAVASCRIPT FUNDAMENTAL - COMPLETE COURSE CONTENT

## 📚 COURSE OVERVIEW

**Nama Course:** JavaScript Fundamental  
**Level:** Beginner  
**Durasi:** 8 Minggu  
**Metode:** Microlearning  
**Bahasa:** Indonesia  

### 🎯 CPMK (Capaian Pembelajaran Mata Kuliah)

Setelah menyelesaikan course ini, peserta mampu:
1. Memahami konsep dasar JavaScript dan sintaksnya
2. Menggunakan variabel dan tipe data dengan benar
3. Menerapkan operator dan struktur kontrol (percabangan & perulangan)
4. Membuat dan menggunakan function
5. Bekerja dengan array dan object dasar
6. Membuat program JavaScript sederhana yang interaktif

### 📊 GRADING CONFIGURATION

| Komponen | Bobot | Passing Grade |
|----------|-------|---------------|
| Micro Lesson Quiz | 20% | 70% |
| Weekly Quiz | 25% | 70% |
| Weekly Assignment | 25% | 70% |
| Final Project | 30% | 70% |

**Nilai Akhir Minimum:** 70%

### 🎓 CERTIFICATE REQUIREMENTS

Sertifikat akan diberikan jika:
- ✅ Menyelesaikan semua micro lessons (completion tracking)
- ✅ Nilai akhir minimal 70%
- ✅ Menyelesaikan final project
- ✅ Semua weekly assignments submitted

---

## 📅 WEEKLY BREAKDOWN

### MINGGU 1: PENGENALAN JAVASCRIPT

**Tujuan Mingguan:**
- Memahami apa itu JavaScript
- Menjalankan kode JavaScript
- Memahami sintaks dasar
- Menampilkan output

**Estimasi Waktu:** 60 menit

#### Micro Lesson 1.1: Apa itu JavaScript?

**Tipe:** Page Resource  
**Estimasi:** 8 menit  
**Completion:** Manual

**Materi:**
```
🌟 APA ITU JAVASCRIPT?

JavaScript adalah bahasa pemrograman yang digunakan untuk membuat website 
menjadi interaktif dan dinamis.

POIN PENTING:
✅ JavaScript adalah bahasa pemrograman untuk web
✅ JavaScript berjalan di browser (client-side)
✅ JavaScript membuat website menjadi interaktif
✅ JavaScript mudah dipelajari untuk pemula

CONTOH KODE:
// Menampilkan pesan di console browser
console.log("Hello, JavaScript!");

// Menampilkan popup alert
alert("Selamat datang di JavaScript!");

// Mengubah isi halaman web
document.write("JavaScript itu mudah!");

KENAPA BELAJAR JAVASCRIPT?
1. Populer - Digunakan oleh jutaan developer
2. Versatile - Bisa untuk web, mobile, desktop, server
3. Mudah dipelajari - Sintaks sederhana
4. Karir - Banyak lowongan pekerjaan
```

**Praktik 1.1:** (Aicode - Mode Latihan, AI Hint: ON)
```
Nama: Praktik Hello World
Instruksi: 
1. Buka browser console (F12)
2. Ketik: console.log("Hello World")
3. Tekan Enter
4. Lihat hasilnya

Output yang diharapkan:
Hello World
```

**Kuis 1.1:** (3 soal pilihan ganda)
```
1. Apa fungsi utama JavaScript?
   a. Membuat website menjadi interaktif ✓
   b. Mendesain tampilan website
   c. Menyimpan data di database
   d. Membuat server website
   Feedback: JavaScript digunakan untuk membuat website interaktif

2. Siapa yang menciptakan JavaScript?
   a. Bill Gates
   b. Brendan Eich ✓
   c. Mark Zuckerberg
   d. Steve Jobs
   Feedback: JavaScript diciptakan oleh Brendan Eich pada 1995

3. Apakah JavaScript sama dengan Java?
   a. Ya, keduanya sama
   b. Tidak, keduanya berbeda ✓
   c. Java adalah versi lama JavaScript
   d. JavaScript adalah versi baru Java
   Feedback: JavaScript dan Java adalah bahasa yang berbeda
```

#### Micro Lesson 1.2: Menjalankan JavaScript

**Tipe:** Page Resource  
**Estimasi:** 10 menit

**Materi:**
```
🚀 CARA MENJALANKAN JAVASCRIPT

1. BROWSER CONSOLE (Cara Termudah)
   Windows/Linux: F12 atau Ctrl + Shift + J
   Mac: Cmd + Option + J

2. FILE HTML DENGAN TAG <script>
   <!DOCTYPE html>
   <html>
   <body>
       <h1>Hello World</h1>
       <script>
           console.log("JavaScript berjalan!");
       </script>
   </body>
   </html>

3. FILE JAVASCRIPT TERPISAH (.js)
   <!-- File: index.html -->
   <script src="script.js"></script>
   
   // File: script.js
   console.log("JavaScript dari file terpisah!");

POIN PENTING:
✅ Console browser untuk testing cepat
✅ Tag <script> diletakkan sebelum </body>
✅ File .js terpisah untuk project besar
✅ Gunakan console.log() untuk debugging
```

**Praktik 1.2:** (Aicode)
```
Instruksi:
Buat file HTML sederhana dengan JavaScript yang menampilkan:
1. Alert "Selamat Datang"
2. Console log "Program dimulai"
3. Ubah judul halaman menjadi "Belajar JS"
```

**Kuis 1.2:** (3 soal)

#### Micro Lesson 1.3: Sintaks Dasar JavaScript

**Materi:**
```
📝 SINTAKS DASAR JAVASCRIPT

1. STATEMENT
   Setiap statement diakhiri titik koma (;)
   console.log("Hello");
   console.log("World");

2. CASE SENSITIVE
   let nama = "Budi";  // berbeda dengan
   let Nama = "Ani";   // berbeda dengan
   let NAMA = "Citra";

3. KOMENTAR
   // Komentar satu baris
   /* Komentar
      multi baris */

4. IDENTIFIER (Penamaan)
   ✅ Valid: nama, _private, $jquery, nama123, namaLengkap
   ❌ Invalid: 123nama, nama-lengkap, for

POIN PENTING:
✅ Setiap statement diakhiri titik koma (;)
✅ JavaScript case sensitive
✅ Gunakan komentar untuk dokumentasi
✅ Gunakan camelCase untuk penamaan
```

#### Micro Lesson 1.4: Output di JavaScript

**Materi:**
```
📤 MENAMPILKAN OUTPUT

1. console.log() - Untuk Debugging
   console.log("Hello World");
   console.log(123);
   console.log("Nilai:", 100);

2. alert() - Popup Dialog
   alert("Selamat datang!");

3. innerHTML - Mengubah Elemen HTML (Recommended)
   document.getElementById("output").innerHTML = "Hello";

4. document.write() - TIDAK DIREKOMENDASIKAN
   document.write("Hello"); // Menghapus konten halaman

KAPAN MENGGUNAKAN APA?
- console.log() → Debugging, melihat nilai variabel
- alert() → Notifikasi penting
- innerHTML → Menampilkan hasil ke halaman
- document.write() → Jangan digunakan!
```

#### Weekly Quiz - Minggu 1

**Format:** 10 soal pilihan ganda  
**Passing Grade:** 70%  
**Attempts:** Unlimited  
**Time Limit:** None

**Soal:**
1. JavaScript adalah bahasa pemrograman yang berjalan di?
2. Apa output dari: console.log("Hello" + " " + "World");
3. Manakah komentar multi-baris yang benar?
4. Apa fungsi dari console.log()?
5. Manakah nama variabel yang TIDAK valid?
6. Apa yang dimaksud dengan case sensitive?
7. Tag HTML untuk menyisipkan JavaScript adalah?
8. Apa ekstensi file JavaScript?
9. Method apa yang mengubah konten elemen HTML?
10. Apa yang terjadi jika lupa menulis titik koma?

#### Weekly Assignment - Minggu 1

**Tipe:** Aicode (Mode Ujian, AI Hint: OFF)  
**Bobot:** 25% dari nilai mingguan

**Instruksi:**
```
Buat program JavaScript sederhana yang:
1. Menampilkan informasi pribadi Anda:
   - Nama lengkap
   - Umur
   - Hobi (minimal 3)
2. Gunakan console.log() untuk menampilkan di console
3. Gunakan alert() untuk menampilkan ringkasan
4. Gunakan innerHTML untuk menampilkan di halaman

Format Output Console:
=== PROFIL SAYA ===
Nama: [nama Anda]
Umur: [umur Anda]
Hobi:
- [hobi 1]
- [hobi 2]
- [hobi 3]

Rubrik Penilaian:
- Kode berjalan tanpa error (30%)
- Menggunakan console.log() dengan benar (20%)
- Menggunakan alert() dengan benar (20%)
- Menggunakan innerHTML dengan benar (20%)
- Kode rapi dan terstruktur (10%)
```

---

### MINGGU 2: VARIABEL DAN TIPE DATA

**Tujuan Mingguan:**
- Memahami konsep variabel
- Mengenal tipe data di JavaScript
- Menggunakan string, number, dan boolean
- Konversi tipe data

**Estimasi Waktu:** 60 menit

#### Micro Lesson 2.1: Variabel di JavaScript

**Materi:**
```
📦 VARIABEL DI JAVASCRIPT

PENGERTIAN:
Variabel adalah wadah untuk menyimpan data yang bisa berubah-ubah.

CARA DEKLARASI:
1. let - untuk variabel yang bisa diubah
   let nama = "Budi";
   nama = "Ani"; // bisa diubah

2. const - untuk variabel yang tidak bisa diubah
   const PI = 3.14;
   PI = 3.15; // ERROR!

3. var - cara lama (tidak direkomendasikan)
   var umur = 20;

ATURAN PENAMAAN:
✅ Harus dimulai dengan huruf, _, atau $
✅ Bisa mengandung huruf, angka, _, $
✅ Case sensitive
✅ Tidak boleh menggunakan kata reserved
❌ Tidak boleh dimulai dengan angka
❌ Tidak boleh mengandung spasi

CONTOH:
let namaLengkap = "Budi Santoso";
let umur = 20;
let sudahMenikah = false;
const NEGARA = "Indonesia";

console.log(namaLengkap); // Output: Budi Santoso
console.log(umur);        // Output: 20

POIN PENTING:
✅ Gunakan let untuk variabel yang berubah
✅ Gunakan const untuk konstanta
✅ Hindari var (cara lama)
✅ Gunakan nama yang deskriptif
✅ Gunakan camelCase
```

**Praktik 2.1:**
```
Instruksi:
1. Buat variabel untuk menyimpan:
   - Nama Anda (let)
   - Umur Anda (let)
   - Tempat lahir (const)
2. Tampilkan semua variabel dengan console.log()
3. Coba ubah nilai nama dan umur
4. Coba ubah nilai tempat lahir (akan error)
```

**Kuis 2.1:**
```
1. Keyword apa yang digunakan untuk variabel yang bisa diubah?
   a. var
   b. let ✓
   c. const
   d. variable

2. Manakah nama variabel yang VALID?
   a. 123nama
   b. nama-siswa
   c. nama_siswa ✓
   d. nama siswa

3. Apa yang terjadi jika mengubah nilai const?
   a. Berhasil diubah
   b. Muncul warning
   c. Error ✓
   d. Tidak terjadi apa-apa
```

#### Micro Lesson 2.2: Tipe Data String

**Materi:**
```
📝 TIPE DATA STRING

PENGERTIAN:
String adalah tipe data untuk menyimpan teks.

CARA MEMBUAT STRING:
let nama1 = "Budi";      // Double quotes
let nama2 = 'Ani';       // Single quotes
let nama3 = `Citra`;     // Backticks (template literal)

MENGGABUNGKAN STRING:
// Cara 1: Operator +
let namaDepan = "Budi";
let namaBelakang = "Santoso";
let namaLengkap = namaDepan + " " + namaBelakang;
console.log(namaLengkap); // Budi Santoso

// Cara 2: Template Literal (Recommended)
let umur = 20;
let pesan = `Nama saya ${namaLengkap}, umur ${umur} tahun`;
console.log(pesan); // Nama saya Budi Santoso, umur 20 tahun

STRING METHODS:
let teks = "JavaScript";
console.log(teks.length);        // 10
console.log(teks.toUpperCase()); // JAVASCRIPT
console.log(teks.toLowerCase()); // javascript
console.log(teks.charAt(0));     // J
console.log(teks.substring(0,4));// Java

ESCAPE CHARACTERS:
let quote = "Dia berkata \"Hello\"";  // Dia berkata "Hello"
let newline = "Baris 1\nBaris 2";     // Baris 1
                                       // Baris 2

POIN PENTING:
✅ String bisa menggunakan " atau ' atau `
✅ Template literal (`) untuk string dinamis
✅ String memiliki banyak method berguna
✅ Gunakan \ untuk escape character
```

**Praktik 2.2:**
```
Instruksi:
1. Buat variabel untuk nama depan dan belakang
2. Gabungkan menjadi nama lengkap (2 cara)
3. Ubah nama lengkap menjadi UPPERCASE
4. Hitung panjang nama lengkap
5. Ambil 3 huruf pertama dari nama
```

#### Micro Lesson 2.3: Tipe Data Number

**Materi:**
```
🔢 TIPE DATA NUMBER

PENGERTIAN:
Number adalah tipe data untuk angka (integer dan float).

JENIS NUMBER:
let integer = 100;        // Bilangan bulat
let float = 3.14;         // Bilangan desimal
let negative = -50;       // Bilangan negatif
let scientific = 2e3;     // 2000 (scientific notation)

OPERASI MATEMATIKA:
let a = 10;
let b = 3;

console.log(a + b);  // 13 (penjumlahan)
console.log(a - b);  // 7  (pengurangan)
console.log(a * b);  // 30 (perkalian)
console.log(a / b);  // 3.333... (pembagian)
console.log(a % b);  // 1  (modulus/sisa bagi)
console.log(a ** b); // 1000 (pangkat)

MATH OBJECT:
console.log(Math.PI);           // 3.141592653589793
console.log(Math.round(4.7));   // 5
console.log(Math.ceil(4.1));    // 5
console.log(Math.floor(4.9));   // 4
console.log(Math.sqrt(16));     // 4
console.log(Math.random());     // 0.xxx (random 0-1)
console.log(Math.max(1,5,3));   // 5
console.log(Math.min(1,5,3));   // 1

NUMBER METHODS:
let angka = 123.456;
console.log(angka.toFixed(2));      // "123.46"
console.log(angka.toString());      // "123.456"
console.log(parseInt("123"));       // 123
console.log(parseFloat("123.45"));  // 123.45

SPECIAL VALUES:
let infinity = 1 / 0;        // Infinity
let notNumber = "abc" / 2;   // NaN (Not a Number)

POIN PENTING:
✅ JavaScript hanya punya 1 tipe number
✅ Gunakan Math object untuk operasi kompleks
✅ Hati-hati dengan pembagian dengan 0
✅ NaN = Not a Number (hasil operasi invalid)
```

**Praktik 2.3:**
```
Instruksi:
1. Buat program kalkulator sederhana:
   - Input: 2 angka
   - Hitung: +, -, *, /, %
   - Tampilkan semua hasil
2. Buat program untuk menghitung luas lingkaran
   - Input: jari-jari
   - Rumus: π × r²
   - Gunakan Math.PI
```

#### Micro Lesson 2.4: Tipe Data Boolean dan Null/Undefined

**Materi:**
```
✅ TIPE DATA BOOLEAN

PENGERTIAN:
Boolean hanya punya 2 nilai: true atau false

CONTOH:
let sudahLogin = true;
let sudahMenikah = false;
let dewasa = umur >= 18;  // true jika umur >= 18

COMPARISON OPERATORS:
let a = 10;
let b = 5;

console.log(a > b);   // true
console.log(a < b);   // false
console.log(a >= 10); // true
console.log(a <= 5);  // false
console.log(a == b);  // false (sama dengan)
console.log(a != b);  // true (tidak sama dengan)

STRICT COMPARISON:
console.log(5 == "5");   // true (hanya nilai)
console.log(5 === "5");  // false (nilai dan tipe)
console.log(5 != "5");   // false
console.log(5 !== "5");  // true

LOGICAL OPERATORS:
let umur = 20;
let punyaKTP = true;

// AND (&&) - semua harus true
console.log(umur >= 17 && punyaKTP);  // true

// OR (||) - salah satu true
console.log(umur >= 17 || punyaKTP);  // true

// NOT (!) - membalik nilai
console.log(!punyaKTP);  // false

NULL DAN UNDEFINED:
let kosong = null;        // Sengaja dikosongkan
let belumDiisi;           // undefined (belum diberi nilai)

console.log(kosong);      // null
console.log(belumDiisi);  // undefined

TYPEOF OPERATOR:
console.log(typeof "Hello");  // "string"
console.log(typeof 123);      // "number"
console.log(typeof true);     // "boolean"
console.log(typeof undefined);// "undefined"
console.log(typeof null);     // "object" (bug JavaScript!)

POIN PENTING:
✅ Boolean untuk kondisi true/false
✅ Gunakan === untuk perbandingan strict
✅ && = AND, || = OR, ! = NOT
✅ null = sengaja kosong
✅ undefined = belum diberi nilai
```

**Praktik 2.4:**
```
Instruksi:
1. Buat program cek kelayakan pemilih:
   - Input: umur, punyaKTP
   - Syarat: umur >= 17 DAN punya KTP
   - Output: "Boleh memilih" atau "Tidak boleh memilih"
2. Cek tipe data dari berbagai variabel
```

#### Weekly Quiz - Minggu 2

**10 Soal tentang:**
- Variabel (let, const, var)
- String dan operasinya
- Number dan Math object
- Boolean dan operator logika
- Null dan undefined

#### Weekly Assignment - Minggu 2

**Instruksi:**
```
Buat program "Biodata Lengkap" dengan:
1. Variabel untuk:
   - Nama lengkap (string)
   - Umur (number)
   - Tinggi badan dalam cm (number)
   - Berat badan dalam kg (number)
   - Sudah menikah (boolean)
2. Hitung BMI = berat / (tinggi/100)²
3. Tentukan kategori BMI:
   - < 18.5: Kurus
   - 18.5-24.9: Normal
   - 25-29.9: Gemuk
   - >= 30: Obesitas
4. Tampilkan semua data dengan format rapi

Rubrik:
- Variabel benar (20%)
- Perhitungan BMI benar (30%)
- Kategori BMI benar (30%)
- Format output rapi (20%)
```

---

### MINGGU 3: OPERATOR DAN INPUT DATA

**Tujuan:**
- Memahami berbagai operator
- Mengambil input dari user
- Konversi tipe data
- Membuat program interaktif

#### Micro Lesson 3.1: Operator Aritmatika
#### Micro Lesson 3.2: Operator Assignment
#### Micro Lesson 3.3: Input dengan prompt()
#### Micro Lesson 3.4: Konversi Tipe Data

---

### MINGGU 4: PERCABANGAN

**Tujuan:**
- Memahami if statement
- Menggunakan else dan else if
- Memahami switch case
- Membuat program dengan kondisi

#### Micro Lesson 4.1: If Statement
#### Micro Lesson 4.2: Else dan Else If
#### Micro Lesson 4.3: Switch Case
#### Micro Lesson 4.4: Ternary Operator

---

### MINGGU 5: PERULANGAN

**Tujuan:**
- Memahami konsep loop
- Menggunakan for loop
- Menggunakan while loop
- Break dan continue

#### Micro Lesson 5.1: For Loop
#### Micro Lesson 5.2: While Loop
#### Micro Lesson 5.3: Do While Loop
#### Micro Lesson 5.4: Break dan Continue

---

### MINGGU 6: FUNCTION DASAR

**Tujuan:**
- Memahami konsep function
- Membuat function dengan parameter
- Return value
- Scope variabel

#### Micro Lesson 6.1: Pengenalan Function
#### Micro Lesson 6.2: Parameter dan Argument
#### Micro Lesson 6.3: Return Value
#### Micro Lesson 6.4: Scope Variabel

---

### MINGGU 7: ARRAY DAN OBJECT DASAR

**Tujuan:**
- Memahami array
- Operasi array dasar
- Memahami object
- Akses property object

#### Micro Lesson 7.1: Array Dasar
#### Micro Lesson 7.2: Array Methods
#### Micro Lesson 7.3: Object Dasar
#### Micro Lesson 7.4: Object Methods

---

### MINGGU 8: FINAL PROJECT

**Tujuan:**
- Mengintegrasikan semua materi
- Membuat aplikasi JavaScript lengkap
- Presentasi project

#### Final Project Options:

**Option 1: Kalkulator Lengkap**
```
Buat kalkulator dengan fitur:
- Operasi: +, -, *, /, %, pangkat
- Input dari user (prompt)
- Validasi input
- Tampilan hasil yang rapi
- History perhitungan (array)
```

**Option 2: To-Do List**
```
Buat aplikasi to-do list dengan:
- Tambah task
- Hapus task
- Tandai task selesai
- Tampilkan semua task
- Simpan di array
```

**Option 3: Quiz Game**
```
Buat quiz game dengan:
- Minimal 10 soal
- Hitung skor
- Tampilkan hasil akhir
- Kategori nilai (A, B, C, D, E)
```

**Rubrik Final Project:**
- Kode berjalan tanpa error (20%)
- Menggunakan variabel dengan benar (10%)
- Menggunakan function (20%)
- Menggunakan array/object (20%)
- Menggunakan percabangan dan perulangan (15%)
- User interface dan UX (10%)
- Kode rapi dan terstruktur (5%)

**Passing Grade:** 70%

---

## 🎓 CERTIFICATE CONFIGURATION

### Plugin: Custom Certificate

**Certificate Name:** "Sertifikat JavaScript Fundamental"

**Certificate Text:**
```
Sertifikat ini diberikan kepada

{STUDENT_NAME}

Telah berhasil menyelesaikan course

JAVASCRIPT FUNDAMENTAL

dengan nilai akhir {GRADE}

Diberikan pada tanggal {DATE}

{INSTRUCTOR_NAME}
Instructor
```

**Requirements:**
- Course completion: 100%
- Final grade: >= 70%
- All assignments submitted
- Final project completed

---

## 📋 MOODLE IMPLEMENTATION NOTES

### Course Format
- **Format:** Topics
- **Number of sections:** 8
- **Completion tracking:** Enabled
- **Show grades:** Yes

### Activity Sequence per Week
1. Section summary (intro)
2. Micro Lesson 1 (Page)
3. Praktik 1 (Aicode - practice mode)
4. Kuis 1 (Quiz - 3 questions)
5. Micro Lesson 2 (Page)
6. Praktik 2 (Aicode - practice mode)
7. Kuis 2 (Quiz - 3 questions)
8. Micro Lesson 3 (Page)
9. Praktik 3 (Aicode - practice mode)
10. Kuis 3 (Quiz - 3 questions)
11. Micro Lesson 4 (Page)
12. Praktik 4 (Aicode - practice mode)
13. Kuis 4 (Quiz - 3 questions)
14. Weekly Quiz (Quiz - 10 questions)
15. Weekly Assignment (Aicode - exam mode)

### Completion Criteria
- **Page resources:** View to complete
- **Aicode practice:** Complete activity
- **Micro quizzes:** Pass with 70%
- **Weekly quiz:** Pass with 70%
- **Weekly assignment:** Submit and pass

### Restriction Access
- Week 2 unlocked after Week 1 completion
- Week 3 unlocked after Week 2 completion
- And so on...
- Final Project unlocked after Week 7 completion

### Gradebook Categories
```
JavaScript Fundamental (100%)
├── Micro Quizzes (20%)
│   ├── Week 1 Micro Quizzes
│   ├── Week 2 Micro Quizzes
│   └── ...
├── Weekly Quizzes (25%)
│   ├── Weekly Quiz 1
│   ├── Weekly Quiz 2
│   └── ...
├── Weekly Assignments (25%)
│   ├── Assignment 1
│   ├── Assignment 2
│   └── ...
└── Final Project (30%)
    └── Final Project
```

---

## ✅ CHECKLIST AFTER COURSE CREATION

- [ ] Review all course materials
- [ ] Test all quizzes
- [ ] Test all Aicode activities
- [ ] Configure Custom Certificate plugin
- [ ] Set up completion criteria
- [ ] Configure restriction access
- [ ] Test gradebook calculation
- [ ] Create course backup
- [ ] Enroll test student
- [ ] Test complete student journey
- [ ] Adjust based on feedback

---

**END OF DOCUMENTATION**

*Course created by: AI Assistant*  
*Date: 2026-05-14*  
*Version: 1.0*
