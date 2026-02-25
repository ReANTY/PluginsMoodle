# Quick Start Guide - AICode untuk Quiz JavaScript

## Untuk Guru (Teachers)

### 🚀 Langkah Cepat Membuat Quiz

1. **Login ke Moodle** sebagai teacher/admin
2. **Buka course** yang ingin ditambahkan quiz
3. **Turn editing ON**
4. Klik **"Add an activity or resource"**
5. Pilih **"AICode — AI Programming Lab"**
6. Copy-paste salah satu contoh dari file `javascript_quiz_examples.json`
7. **Save and display**

### 📋 Template Singkat

**Format Minimal untuk Membuat Quiz:**

```
Name: [Judul Quiz]
Description: [Deskripsi problem dalam HTML]
Language: javascript
Starter Code: [Template code untuk siswa]
Test Cases: [{"input": "...", "expected": "..."}, ...]
```

### 💡 Contoh Tercepat - Copy Paste Ini:

**Name:**

```
Quiz: Fungsi Perkalian
```

**Description:**

```html
<h3>Problem</h3>
<p>Buat fungsi <code>kali</code> yang mengalikan dua angka.</p>
```

**Language:**

```
javascript
```

**Starter Code:**

```javascript
function kali(a, b) {
  // Kode Anda di sini
}

console.log(kali(3, 4));
console.log(kali(5, 6));
```

**Test Cases:**

```json
[
  { "input": "3, 4", "expected": "12" },
  { "input": "5, 6", "expected": "30" }
]
```

✅ Selesai! Quiz siap digunakan.

---

## Untuk Siswa (Students)

### 📝 Cara Mengerjakan

1. **Buka aktivitas AICode** di course Anda
2. **Baca deskripsi** - pahami apa yang diminta
3. **Tulis kode** di editor (Monaco)
4. **Klik "Run"** untuk test
5. Jika error → lihat **AI Feedback**
6. Jika stuck → klik **"Hint"**
7. Masih stuck → **"Send to Teacher"**

### ⚡ Workflow Cepat

```
Read Problem → Write Code → Run →
   ↓
   Error? → AI Feedback → Fix → Run again
   ↓
   Success! ✅
```

### 🎯 Tips Cepat

- **Baca persyaratan teliti** (nama fungsi, parameter)
- **Jangan ubah** nama fungsi yang sudah ada
- **Test dengan console.log** untuk debug
- **Hints ada 3 level** - pakai level 1 dulu
- **Error message penting** - baca baik-baik

---

## 12 Quiz Siap Pakai

File `javascript_quiz_examples.json` berisi 12 quiz:

1. ✅ **Penjumlahan** (Beginner, 10 min)
2. ✅ **Filter Genap** (Beginner, 15 min)
3. ✅ **Validasi Email** (Intermediate, 20 min)
4. ✅ **Fibonacci** (Intermediate, 25 min)
5. ✅ **Palindrome** (Intermediate, 25 min)
6. ✅ **Hitung Kata** (Beginner, 15 min)
7. ✅ **Find Maximum** (Beginner, 15 min)
8. ✅ **Reverse String** (Beginner, 10 min)
9. ✅ **FizzBuzz** (Intermediate, 20 min)
10. ✅ **Remove Duplicates** (Intermediate, 20 min)
11. ✅ **Faktorial** (Intermediate, 20 min)
12. ✅ **Capitalize Words** (Intermediate, 20 min)

---

## Struktur Test Cases

### Format JSON:

```json
[{ "input": "input_value", "expected": "output_value" }]
```

### Untuk Array Output:

```json
[{ "input": "[1,2,3]", "expected": "[2,4,6]" }]
```

⚠️ Gunakan `JSON.stringify()` di console.log untuk array!

### Untuk Boolean:

```json
[
  { "input": "test@email.com", "expected": "true" },
  { "input": "invalid", "expected": "false" }
]
```

---

## Common Errors & Fixes

### ❌ SyntaxError: missing )

```javascript
// SALAH
console.log("Hello";

// BENAR
console.log("Hello");
```

### ❌ ReferenceError: variable not defined

```javascript
// SALAH - typo
let height = 10;
return width * heigth; // typo: heigth

// BENAR
let height = 10;
return width * height;
```

### ❌ TypeError: undefined is not a function

```javascript
// SALAH - array method pada non-array
let x = 5;
x.map((i) => i * 2);

// BENAR
let arr = [1, 2, 3];
arr.map((i) => i * 2);
```

---

## Advanced: Custom Test Cases

### Test dengan Multiple Inputs:

```javascript
// Fungsi: hitungLuas(panjang, lebar)
function hitungLuas(p, l) {
  return p * l;
}

// Test cases JSON:
[
  { input: "5, 10", expected: "50" },
  { input: "3, 7", expected: "21" },
];
```

### Test dengan Object/Array:

```javascript
// Fungsi: getTotalPrice(items)
function getTotalPrice(items) {
  return items.reduce((sum, item) => sum + item.price, 0);
}

// Test call:
console.log(
  getTotalPrice([
    { name: "Book", price: 100 },
    { name: "Pen", price: 50 },
  ])
);

// Test case:
[
  {
    input: '[{"name":"Book","price":100},{"name":"Pen","price":50}]',
    expected: "150",
  },
];
```

---

## Monitoring Student Progress

### Sebagai Guru, Anda Bisa Lihat:

1. **Jumlah attempts** per siswa
2. **Hints yang digunakan** (level 1/2/3)
3. **Berapa kali request AI feedback**
4. **Code hash** (privacy-friendly)
5. **Timestamp** setiap attempt

### Cara Melihat:

1. Buka aktivitas AICode
2. Menu → **"View attempts"**
3. Pilih siswa untuk detail

---

## Troubleshooting

| Problem                  | Solution                                       |
| ------------------------ | ---------------------------------------------- |
| Service unavailable      | Check executor di port 3001 + AI provider Moodle (Ollama) |
| Monaco editor tidak load | Clear cache, refresh browser                   |
| AI feedback tidak muncul | Check AI provider Moodle sudah aktif & model Ollama tersedia |
| Timeout error            | Code infinite loop? Check loop condition       |
| Test case tidak pass     | Perhatikan format output (string/number/array) |

---

## Resources

📚 **Dokumentasi Lengkap:** `CONTOH_PENGGUNAAN_JAVASCRIPT.md`

📦 **Quiz Library:** `examples/javascript_quiz_examples.json`

🔧 **Main README:** `README.md`

🌐 **JavaScript Reference:**

- [MDN JavaScript](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
- [JavaScript.info](https://javascript.info/)

---

## Support

💬 **Issues/Questions:** GitHub Issues
📧 **Email:** support@aicode.edu
🎓 **Moodle Forum:** https://moodle.org/mod/forum/

---

**Selamat Mengajar & Belajar! 🎓✨**

---

## Changelog

- **2025-10-12**: Initial Indonesian quick start guide
- **2025-10-12**: Added 12 ready-to-use JavaScript quiz examples
