# Contoh Penggunaan AICode untuk Quiz JavaScript di Moodle

## Daftar Isi

1. [Cara Membuat Aktivitas AICode](#cara-membuat-aktivitas-aicode)
2. [Contoh 1: Quiz Dasar - Fungsi Penjumlahan](#contoh-1-quiz-dasar---fungsi-penjumlahan)
3. [Contoh 2: Quiz Menengah - Manipulasi Array](#contoh-2-quiz-menengah---manipulasi-array)
4. [Contoh 3: Quiz Lanjutan - Validasi Form](#contoh-3-quiz-lanjutan---validasi-form)
5. [Contoh 4: Quiz Algorithm - Fibonacci](#contoh-4-quiz-algorithm---fibonacci)
6. [Contoh 5: Quiz String Manipulation](#contoh-5-quiz-string-manipulation)
7. [Tips untuk Guru](#tips-untuk-guru)
8. [Panduan untuk Siswa](#panduan-untuk-siswa)

---

## Cara Membuat Aktivitas AICode

### Langkah-langkah untuk Guru:

1. **Aktifkan Mode Edit** di halaman course Anda
2. Klik **"Add an activity or resource"**
3. Pilih **"AICode — AI Programming Lab"**
4. Isi form dengan:
   - **Name**: Judul quiz/problem
   - **Description**: Deskripsi masalah yang jelas
   - **Language**: Pilih "JavaScript"
   - **Test Cases**: Array JSON dengan input/output yang diharapkan
   - **Starter Code**: Template kode awal untuk siswa
5. Klik **"Save and display"**

---

## Contoh 1: Quiz Dasar - Fungsi Penjumlahan

### Pengaturan Aktivitas:

**Name:**

```
Quiz JavaScript 1: Fungsi Penjumlahan
```

**Description:**

```html
<h3>Tujuan Pembelajaran</h3>
<p>Memahami cara membuat fungsi JavaScript sederhana dengan parameter dan return value.</p>

<h3>Problem</h3>
<p>Buatlah sebuah fungsi bernama <code>jumlahkan</code> yang menerima dua parameter (a dan b), kemudian mengembalikan hasil penjumlahan kedua angka tersebut.</p>

<h3>Persyaratan</h3>
<ul>
  <li>Fungsi harus bernama <code>jumlahkan</code></li>
  <li>Fungsi menerima 2 parameter: <code>a</code> dan <code>b</code></li>
  <li>Fungsi mengembalikan hasil penjumlahan a + b</li>
  <li>Gunakan <code>return</code> untuk mengembalikan nilai</li>
</ul>

<h3>Contoh Output</h3>
<pre>
console.log(jumlahkan(5, 3));    // Output: 8
console.log(jumlahkan(10, 20));  // Output: 30
console.log(jumlahkan(-5, 5));   // Output: 0
</pre>
```

**Language:**

```
javascript
```

**Starter Code:**

```javascript
// Lengkapi fungsi di bawah ini
function jumlahkan(a, b) {
  // Tulis kode Anda di sini
}

// Test cases (jangan dihapus)
console.log(jumlahkan(5, 3));
console.log(jumlahkan(10, 20));
console.log(jumlahkan(-5, 5));
```

**Test Cases:**

    ```json
    [
    { "input": "5, 3", "expected": "8" },
    { "input": "10, 20", "expected": "30" },
    { "input": "-5, 5", "expected": "0" },
    { "input": "100, 200", "expected": "300" }
    ]
    ```

---

## Contoh 2: Quiz Menengah - Manipulasi Array

### Pengaturan Aktivitas:

**Name:**

```
Quiz JavaScript 2: Filter Bilangan Genap
```

**Description:**

```html
<h3>Tujuan Pembelajaran</h3>
<p>Memahami cara bekerja dengan array dan loop dalam JavaScript.</p>

<h3>Problem</h3>
<p>Buatlah fungsi <code>filterGenap</code> yang menerima sebuah array berisi angka, kemudian mengembalikan array baru yang hanya berisi bilangan genap.</p>

<h3>Persyaratan</h3>
<ul>
  <li>Fungsi bernama <code>filterGenap</code></li>
  <li>Parameter: <code>arr</code> (array of numbers)</li>
  <li>Return: array baru berisi hanya bilangan genap</li>
  <li>Gunakan loop (for/forEach) atau method array (.filter)</li>
  <li>Bilangan genap: angka yang habis dibagi 2</li>
</ul>

<h3>Hint</h3>
<ul>
  <li>Gunakan operator modulo (%) untuk cek bilangan genap</li>
  <li>Angka % 2 === 0 berarti angka genap</li>
</ul>

<h3>Contoh Output</h3>
<pre>
console.log(filterGenap([1, 2, 3, 4, 5, 6]));  // Output: [2, 4, 6]
console.log(filterGenap([10, 15, 20, 25]));    // Output: [10, 20]
</pre>
```

**Starter Code:**

```javascript
function filterGenap(arr) {
  // Tulis kode Anda di sini
}

// Test cases
console.log(JSON.stringify(filterGenap([1, 2, 3, 4, 5, 6])));
console.log(JSON.stringify(filterGenap([10, 15, 20, 25])));
console.log(JSON.stringify(filterGenap([7, 9, 11])));
```

**Test Cases:**

```json
[
  { "input": "[1, 2, 3, 4, 5, 6]", "expected": "[2,4,6]" },
  { "input": "[10, 15, 20, 25]", "expected": "[10,20]" },
  { "input": "[7, 9, 11]", "expected": "[]" },
  { "input": "[2, 4, 6, 8]", "expected": "[2,4,6,8]" }
]
```

---

## Contoh 3: Quiz Lanjutan - Validasi Form

### Pengaturan Aktivitas:

**Name:**

```
Quiz JavaScript 3: Validasi Email
```

**Description:**

```html
<h3>Tujuan Pembelajaran</h3>
<p>Memahami string manipulation, regular expressions, dan conditional logic.</p>

<h3>Problem</h3>
<p>Buatlah fungsi <code>validasiEmail</code> yang menerima sebuah string email dan mengembalikan <code>true</code> jika email valid, atau <code>false</code> jika tidak.</p>

<h3>Persyaratan Email Valid</h3>
<ul>
  <li>Harus mengandung karakter @</li>
  <li>Harus ada karakter sebelum @</li>
  <li>Harus ada domain setelah @ (contoh: gmail.com)</li>
  <li>Domain harus mengandung titik (.)</li>
  <li>Tidak boleh ada spasi</li>
</ul>

<h3>Contoh</h3>
<pre>
Valid:
  - user@example.com ✓
  - test.user@gmail.com ✓
  
Invalid:
  - userexample.com ✗ (tidak ada @)
  - @example.com ✗ (tidak ada username)
  - user@example ✗ (tidak ada .com)
  - user @example.com ✗ (ada spasi)
</pre>
```

**Starter Code:**

```javascript
function validasiEmail(email) {
  // Tulis kode Anda di sini
  // Hint: gunakan indexOf(), includes(), atau regex
}

// Test cases
console.log(validasiEmail("user@example.com"));
console.log(validasiEmail("test@gmail.com"));
console.log(validasiEmail("invalid.email"));
console.log(validasiEmail("@example.com"));
```

**Test Cases:**

```json
[
  { "input": "user@example.com", "expected": "true" },
  { "input": "test.user@gmail.com", "expected": "true" },
  { "input": "admin@company.co.id", "expected": "true" },
  { "input": "invalid.email", "expected": "false" },
  { "input": "@example.com", "expected": "false" },
  { "input": "user@example", "expected": "false" },
  { "input": "user @example.com", "expected": "false" }
]
```

---

## Contoh 4: Quiz Algorithm - Fibonacci

### Pengaturan Aktivitas:

**Name:**

```
Quiz JavaScript 4: Deret Fibonacci
```

**Description:**

```html
<h3>Tujuan Pembelajaran</h3>
<p>Memahami algoritma rekursif atau iteratif untuk menyelesaikan masalah matematika.</p>

<h3>Problem</h3>
<p>Buatlah fungsi <code>fibonacci</code> yang menerima sebuah angka <code>n</code> dan mengembalikan angka Fibonacci ke-n.</p>

<h3>Apa itu Deret Fibonacci?</h3>
<p>Deret Fibonacci dimulai dari 0, 1, dan setiap angka berikutnya adalah penjumlahan dari dua angka sebelumnya:</p>
<pre>
Posisi: 0  1  2  3  4  5  6   7   8   ...
Nilai:   0  1  1  2  3  5  8  13  21   ...
</pre>

<h3>Persyaratan</h3>
<ul>
  <li>fibonacci(0) harus return 0</li>
  <li>fibonacci(1) harus return 1</li>
  <li>fibonacci(n) = fibonacci(n-1) + fibonacci(n-2)</li>
  <li>Boleh menggunakan loop atau recursion</li>
</ul>

<h3>Contoh</h3>
<pre>
fibonacci(0)  // 0
fibonacci(1)  // 1
fibonacci(5)  // 5
fibonacci(10) // 55
</pre>
```

**Starter Code:**

```javascript
function fibonacci(n) {
  // Tulis kode Anda di sini
  // Hint 1: Gunakan loop dari 0 sampai n
  // Hint 2: Atau gunakan recursion (tapi perhatikan performance)
}

// Test cases
console.log(fibonacci(0));
console.log(fibonacci(1));
console.log(fibonacci(5));
console.log(fibonacci(10));
```

**Test Cases:**

```json
[
  { "input": "0", "expected": "0" },
  { "input": "1", "expected": "1" },
  { "input": "5", "expected": "5" },
  { "input": "10", "expected": "55" },
  { "input": "15", "expected": "610" }
]
```

---

## Contoh 5: Quiz String Manipulation

### Pengaturan Aktivitas:

**Name:**

```
Quiz JavaScript 5: Palindrome Checker
```

**Description:**

```html
<h3>Tujuan Pembelajaran</h3>
<p>Memahami string manipulation dan algorithm thinking.</p>

<h3>Problem</h3>
<p>Buatlah fungsi <code>isPalindrome</code> yang mengecek apakah sebuah kata adalah palindrome (dibaca sama dari depan dan belakang).</p>

<h3>Persyaratan</h3>
<ul>
  <li>Return <code>true</code> jika palindrome, <code>false</code> jika tidak</li>
  <li>Abaikan case sensitivity (huruf besar/kecil)</li>
  <li>Abaikan spasi dan tanda baca</li>
</ul>

<h3>Contoh Palindrome</h3>
<ul>
  <li>"katak" ✓ (k-a-t-a-k)</li>
  <li>"kasur rusak" ✓ (kasurrusak)</li>
  <li>"Ibu Ratna antar ubi" ✓</li>
  <li>"hello" ✗</li>
</ul>

<h3>Tips</h3>
<pre>
- Gunakan .toLowerCase() untuk case-insensitive
- Gunakan .replace() untuk hapus spasi
- Bandingkan string dengan reverse-nya
</pre>
```

**Starter Code:**

```javascript
function isPalindrome(str) {
  // Tulis kode Anda di sini
  // Step 1: Bersihkan string (lowercase, hapus spasi)
  // Step 2: Reverse string
  // Step 3: Bandingkan dengan original
}

// Test cases
console.log(isPalindrome("katak"));
console.log(isPalindrome("kasur rusak"));
console.log(isPalindrome("hello"));
console.log(isPalindrome("A man a plan a canal Panama"));
```

**Test Cases:**

```json
[
  { "input": "katak", "expected": "true" },
  { "input": "kasur rusak", "expected": "true" },
  { "input": "hello", "expected": "false" },
  { "input": "A man a plan a canal Panama", "expected": "true" },
  { "input": "race car", "expected": "true" },
  { "input": "javascript", "expected": "false" }
]
```

---

## Tips untuk Guru

### 1. Membuat Test Cases yang Baik

```json
[
  { "input": "normal_case", "expected": "expected_output" },
  { "input": "edge_case_empty", "expected": "handle_empty" },
  { "input": "edge_case_negative", "expected": "handle_negative" },
  { "input": "complex_case", "expected": "complex_output" }
]
```

**Prinsip Test Cases:**

- **Normal case**: Input yang umum/standar
- **Edge case**: Input ekstrem (empty, null, very large)
- **Error case**: Input yang mungkin menyebabkan error
- **Boundary case**: Batas-batas nilai (0, -1, max value)

### 2. Menulis Starter Code yang Efektif

```javascript
// ✓ BAIK: Berikan struktur jelas
function namaFungsi(parameter) {
  // TODO: Tulis logika di sini
}

// ✗ BURUK: Terlalu kosong
// ... kode Anda ...

// ✗ BURUK: Terlalu lengkap (tidak ada challenge)
function namaFungsi(parameter) {
  return parameter.map((x) => x * 2); // sudah lengkap
}
```

### 3. Mengatur Tingkat Kesulitan

**Level 1 - Beginner:**

- Fungsi sederhana dengan 1-2 parameter
- Operasi dasar (penjumlahan, perkalian)
- String/array manipulation sederhana

**Level 2 - Intermediate:**

- Multiple conditions (if-else)
- Loop dan array methods
- Object manipulation

**Level 3 - Advanced:**

- Algoritma kompleks
- Recursion
- Data structures (stack, queue)
- Performance optimization

### 4. Melihat Attempt Siswa

1. Buka aktivitas AICode
2. Klik tab **"View attempts"** (klik kanan pada aktivitas)
3. Lihat:
   - Berapa kali siswa mencoba
   - Error apa yang sering muncul
   - Hints yang digunakan
   - Code hash (anonymized)

### 5. Memberikan Feedback Manual

Jika AI feedback kurang memuaskan:

1. Lihat attempt siswa
2. Klik **"Override feedback"**
3. Berikan feedback manual Anda
4. Siswa akan menerima notifikasi

---

## Panduan untuk Siswa

### Cara Mengerjakan Quiz AICode

#### 1. Baca Deskripsi dengan Teliti

- Pahami apa yang diminta
- Perhatikan persyaratan (nama fungsi, parameter, return value)
- Lihat contoh input/output

#### 2. Analisis Starter Code

```javascript
function jumlahkan(a, b) {
  // Tulis kode Anda di sini
}
```

- Sudah ada struktur fungsi
- Fokus pada logic di dalam fungsi
- Jangan ubah nama fungsi atau parameter (kecuali diminta)

#### 3. Tulis Kode Anda

- Mulai dengan logika sederhana
- Test satu case dulu
- Baru tambahkan handling untuk case lain

#### 4. Klik "Run" untuk Testing

- Lihat output di panel Output
- Jika ada error, baca pesan error dengan teliti
- Error message menunjukkan line number dan tipe error

#### 5. Gunakan AI Feedback

Jika ada error:

- AI akan memberikan:
  - **Diagnosis**: Jenis error (SyntaxError, ReferenceError, dll)
  - **Location**: Baris mana yang error
  - **Suggested Fix**: Cara memperbaikinya

#### 6. Gunakan Hints dengan Bijak

Ada 3 level hints:

- **Level 1**: Petunjuk umum (gunakan dulu ini)
- **Level 2**: Petunjuk spesifik
- **Level 3**: Hampir lengkap solusi (last resort)

⚠️ Semakin banyak hints, semakin rendah nilai otomatis Anda!

#### 7. Send to Teacher

Jika masih stuck setelah:

- 5+ attempts
- Menggunakan semua hints
- Membaca AI feedback

Klik **"Send to Teacher"** untuk bantuan personal.

---

## Contoh Workflow Siswa

### Scenario: Mengerjakan Quiz Fibonacci

**Attempt 1:**

```javascript
function fibonacci(n) {
  return n; // Coba langsung return n
}
```

❌ **Output:** 0, 1, 5, 10 (salah untuk n > 1)

**Attempt 2 (setelah baca hint level 1):**

```javascript
function fibonacci(n) {
  if (n <= 1) return n;
  return fibonacci(n - 1) + fibonacci(n - 2); // recursion
}
```

✅ **Output:** Benar! Tapi mungkin lambat untuk n besar

**Attempt 3 (optimasi):**

```javascript
function fibonacci(n) {
  if (n <= 1) return n;

  let a = 0,
    b = 1;
  for (let i = 2; i <= n; i++) {
    let temp = a + b;
    a = b;
    b = temp;
  }
  return b;
}
```

✅ **Output:** Benar dan lebih efisien!

---

## Troubleshooting

### Error: "Service unavailable"

**Solusi:** Hubungi admin/guru. Microservice executor belum jalan.

### Error: "Monaco editor not loading"

**Solusi:**

- Refresh halaman
- Clear browser cache
- Coba browser lain

### AI Feedback tidak muncul

**Solusi:**

- Pastikan ada error (AI hanya muncul kalau ada error)
- Check rate limit (max 50 calls/day default)
- Tunggu beberapa detik

### Code tidak jalan sama sekali

**Check:**

- Syntax error (missing brackets, semicolon)
- Typo di nama fungsi
- Console log ada di code test?

---

## Resources Tambahan

### JavaScript Reference:

- [MDN JavaScript Guide](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide)
- [JavaScript.info](https://javascript.info/)
- [W3Schools JavaScript](https://www.w3schools.com/js/)

### Practice Sites:

- [Codecademy JavaScript](https://www.codecademy.com/learn/introduction-to-javascript)
- [freeCodeCamp](https://www.freecodecamp.org/)
- [LeetCode](https://leetcode.com/) (untuk algorithm)

---

## FAQ

**Q: Berapa kali saya boleh mencoba?**
A: Tidak terbatas! Tapi semakin banyak attempt, bisa mempengaruhi penilaian guru.

**Q: Apakah kode saya dilihat guru?**
A: Tidak langsung. Guru hanya melihat hash code dan statistik (berapa kali attempt, hints dipakai).

**Q: Boleh copy-paste dari internet?**
A: Lebih baik tidak. Tujuannya belajar, bukan cari jawaban. Plus, AI bisa detect pattern umum.

**Q: AI feedback salah, bagaimana?**
A: Click "Send to Teacher" untuk feedback manual dari guru Anda.

**Q: Timeout error terus**
A: Code Anda mungkin infinite loop atau terlalu lambat. Check loop condition!

---

## Lisensi

GPL-3.0 or later

## Support

Untuk pertanyaan atau issue:

- Email: support@aicode.edu
- Moodle forum: [AICode Discussion](https://moodle.org/mod/forum/)

---

**Selamat Belajar! 🚀**
