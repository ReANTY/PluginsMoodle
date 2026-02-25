# AICode Quick Reference Card

> **Print this page for quick reference! 📄**

---

## Untuk Guru: Membuat Quiz dalam 5 Menit

### Step 1: Add Activity

```
Course → Turn editing ON → Add activity → AICode
```

### Step 2: Form Minimal

```
✓ Name: [Judul quiz]
✓ Description: [Problem statement]
✓ Language: javascript
✓ Starter Code: [Template untuk siswa]
✓ Test Cases: [JSON array]
```

### Step 3: Save

```
Save and display → Done! ✅
```

---

## Template Copy-Paste Tercepat

```javascript
// NAME:
Quiz: [Nama Quiz Anda]

// DESCRIPTION:
<h3>Problem</h3>
<p>Buat fungsi yang...</p>

// LANGUAGE:
javascript

// STARTER CODE:
function namaFungsi(param) {
    // Kode Anda di sini

}
console.log(namaFungsi(input));

// TEST CASES:
[
  {"input": "test1", "expected": "output1"},
  {"input": "test2", "expected": "output2"}
]
```

---

## Format Test Cases - Cheat Sheet

### Simple Number/String:

```json
[
  { "input": "5", "expected": "10" },
  { "input": "hello", "expected": "HELLO" }
]
```

### Multiple Parameters:

```json
[{ "input": "5, 10", "expected": "15" }]
```

### Array Output (IMPORTANT!):

```javascript
// Di starter code, HARUS pakai JSON.stringify:
console.log(JSON.stringify(filterGenap([1, 2, 3])));

// Test case:
[{ input: "[1,2,3]", expected: "[2]" }];
```

### Boolean:

```json
[
  { "input": "test@email.com", "expected": "true" },
  { "input": "invalid", "expected": "false" }
]
```

---

## Level Kesulitan - Panduan

| Level            | Waktu     | Topik                       | Contoh                      |
| ---------------- | --------- | --------------------------- | --------------------------- |
| **Beginner**     | 10-15 min | Function, loop, array dasar | Penjumlahan, reverse string |
| **Intermediate** | 20-25 min | Algorithm, validation       | Fibonacci, palindrome       |
| **Advanced**     | 30+ min   | Complex logic, optimization | Sorting, recursion          |

---

## Error Common & Solusi

### ❌ Service Unavailable

**Cause:** Executor service / Moodle AI provider tidak aktif  
**Fix:** Check executor port 3001 dan konfigurasi AI provider Moodle (Ollama)

### ❌ Invalid JSON

**Cause:** Format test cases salah  
**Fix:** Validate di jsonlint.com

### ❌ Output Mismatch

**Cause:** Format output beda  
**Fix:** Check type (string/number/array)

### ❌ Timeout

**Cause:** Infinite loop  
**Fix:** Review loop conditions

---

## Untuk Siswa: 5 Langkah Sukses

```
1. 📖 BACA deskripsi teliti
   ↓
2. ✍️ TULIS kode di editor
   ↓
3. ▶️ RUN code
   ↓
4. 🔍 CEK output/error
   ↓
5. 🔄 FIX & repeat
```

---

## AI Feedback - Cara Pakai

### Kapan Muncul?

- ✓ Ketika ada error
- ✓ Ketika test case gagal
- ✗ Tidak muncul jika semua benar

### 3 Level Hints:

1. **Level 1 (Gentle)**: "Coba cek loop condition"
2. **Level 2 (Direct)**: "Array index dimulai dari 0"
3. **Level 3 (Explicit)**: "Ganti i <= length menjadi i < length"

⚠️ **Warning:** Semakin banyak hints → nilai lebih rendah!

---

## JavaScript Must-Know

### Arrays

```javascript
let arr = [1, 2, 3];
arr.length; // 3
arr.push(4); // [1,2,3,4]
arr.filter((x) => x > 1); // [2,3]
arr.map((x) => x * 2); // [2,4,6]
```

### Strings

```javascript
let str = "Hello";
str.length; // 5
str.toLowerCase(); // "hello"
str.toUpperCase(); // "HELLO"
str.split(" "); // ["Hello"]
str.includes("ell"); // true
```

### Loops

```javascript
for (let i = 0; i < 10; i++) {
  console.log(i);
}

arr.forEach((item) => {
  console.log(item);
});
```

---

## File Resources

| File                                     | Deskripsi                              |
| ---------------------------------------- | -------------------------------------- |
| `CONTOH_PENGGUNAAN_JAVASCRIPT.md`        | Dokumentasi lengkap (Bahasa Indonesia) |
| `QUICK_START_INDONESIAN.md`              | Quick start guide                      |
| `examples/javascript_quiz_examples.json` | 12 quiz siap pakai                     |
| `examples/quiz_gallery.html`             | Visual gallery (buka di browser)       |
| `REFERENCE_CARD.md`                      | File ini (print untuk referensi)       |

---

## Quick Links

### Open Gallery:

```
file:///path/to/moodle/mod/aicode/examples/quiz_gallery.html
```

### Moodle Admin Settings:

```
Site administration → Plugins → Activity modules → AICode
```

### View Student Attempts:

```
Activity → Settings → View attempts
```

---

## Configuration Checklist

### Required Settings:

- [ ] Executor URL: `http://127.0.0.1:3001`
- [ ] Moodle AI Provider: `aiprovider_ollama` sudah dikonfigurasi
- [ ] Ollama endpoint: `http://127.0.0.1:11434` (via Site administration → AI)

### Optional Settings:

- [ ] Confidence Threshold: `0.6` (default)
- [ ] Cache TTL: `3600` (1 hour)
- [ ] Max Calls per Day: `50` (per student)
- [ ] Execution Timeout: `2` seconds

---

## Debug Mode

### Check Services:

```bash
# Port 3001 (Executor)
curl http://127.0.0.1:3001/health

# Ollama API (jika pakai Ollama provider)
curl http://127.0.0.1:11434/api/tags
```

### Check Logs:

```
Moodle Admin → Reports → Logs
Filter by: mod_aicode
```

---

## Tips Pro

### Untuk Guru:

✅ Mulai dengan quiz beginner  
✅ Test quiz sendiri sebelum assign  
✅ Lihat student attempts regularly  
✅ Gunakan hints untuk adjust difficulty  
✅ Berikan feedback manual untuk AI yang kurang

### Untuk Siswa:

✅ Baca error message dengan teliti  
✅ Console.log untuk debug  
✅ Test dengan input sederhana dulu  
✅ Gunakan hint level 1 dulu  
✅ Jangan copy-paste dari Google 😉

---

## Support Contacts

📧 **Email:** support@aicode.edu  
🌐 **Docs:** /mod/aicode/README.md  
💬 **Forum:** Moodle.org AICode Forum  
🐛 **Issues:** GitHub Issues

---

## Version Info

- **Plugin Version:** 1.0.0
- **Moodle Required:** 5.0+
- **Language Support:** JavaScript
- **Maturity:** Beta

---

## License & Credits

**License:** GPL-3.0 or later  
**Copyright:** 2025 AICode Team  
**Powered by:** Moodle AI provider (recommended: Ollama)

---

## Quick Quiz Ideas

1. **Warmup:** Penjumlahan, perkalian
2. **Arrays:** Filter, map, find max
3. **Strings:** Reverse, capitalize, validate
4. **Logic:** FizzBuzz, palindrome
5. **Math:** Fibonacci, faktorial
6. **Real-world:** Email validation, kata count

---

**📌 Bookmark this page for quick reference!**

**🖨️ Print for desk reference!**

**🚀 Happy Teaching & Learning!**
