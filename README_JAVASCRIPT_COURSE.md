# 📚 JavaScript Fundamental Course - Installation Guide

## 🎯 Overview

Ini adalah panduan lengkap untuk membuat course **JavaScript Fundamental** di Moodle Anda. Course ini dirancang dengan metode **microlearning** untuk pemula total.

## 📦 Files Yang Sudah Dibuat

```
moodle/
├── admin/
│   ├── test_create_course_web.php          # Test script (sudah berhasil)
│   ├── course_builder_helpers.php          # Helper functions
│   ├── js_week1_content.php                # Konten lengkap Minggu 1
│   ├── js_course_data.php                  # Course configuration
│   ├── create_js_course_main.php           # Main course creator (simple)
│   └── build_js_course_full.php            # Full course builder ⭐
├── COURSE_CONTENT_COMPLETE.md              # Dokumentasi lengkap semua materi
└── README_JAVASCRIPT_COURSE.md             # File ini
```

## 🚀 Cara Menggunakan

### Option 1: Automatic (Recommended) ⭐

**Langkah 1:** Buka browser dan akses:
```
http://localhost/moodle/admin/build_js_course_full.php
```

**Langkah 2:** Login sebagai admin

**Langkah 3:** Tunggu proses selesai (1-2 menit)

**Langkah 4:** Klik tombol "Open JavaScript Fundamental Course"

**Status:** 
- ✅ Minggu 1–8: LENGKAP (32 micro lessons, praktik Aicode, kuis ~10 soal/ML, weekly quiz, assignment)
- ✅ Minggu 6–8: Mode Iframe Preview (HTML/CSS template + JS)
- ✅ Final Project: Todo List interaktif (Minggu 8)

### Option 2: Manual (Lengkap Tapi Butuh Waktu)

Jika Anda ingin course yang 100% lengkap dengan semua materi detail, ikuti dokumentasi di `COURSE_CONTENT_COMPLETE.md` dan input manual via Moodle UI.

## 📋 Yang Sudah Dibuat Otomatis

### ✅ Minggu 1 (LENGKAP)
- [x] 4 Micro Lessons dengan materi lengkap
- [x] 4 Praktik Coding (Aicode)
- [x] 4 Kuis Singkat (3 soal each)
- [x] 1 Weekly Quiz (10 soal)
- [x] 1 Weekly Assignment

### ✅ Minggu 2-8 (OTOMATIS VIA BUILDER)
- [x] Materi lengkap per micro lesson (`js_week2_content.php` … `js_week8_content.php`)
- [x] Praktik Aicode (console M1–5, iframe M6–8)
- [x] Kuis micro lesson (~10 soal via `js_quiz_supplements.php`)
- [x] Weekly quiz & weekly assignment
- [x] Final project Todo List (Minggu 8)

## 📁 File Konten Course

| File | Isi |
|------|-----|
| `js_week1_content.php` + `js_week1_enrichment.php` | Minggu 1 |
| `js_week2_content.php` … `js_week7_content.php` | Minggu 2–7 lengkap |
| `js_week8_content.php` | Minggu 8 + final project |
| `js_quiz_supplements.php` | Soal tambahan kuis ML (7 per lesson) |
| `js_teacher_solutions_data.php` | Kode solusi praktik, assignment, final project |
| `js_course_teacher_keys.php` | Generator Page kunci jawaban guru |
| `admin/cli/add_teacher_keys_page.php` | Tambah kunci jawaban ke course yang sudah ada |
| `JAVASCRIPT_FUNDAMENTAL_MOODLE_CONTENT.md` | Dokumentasi copy-paste manual |

### 🔒 Kunci Jawaban Guru

Saat menjalankan `build_js_course_full.php`, otomatis dibuat Page di **section General**:

- **Judul:** `🔒 [KHUSUS GURU] Kunci Jawaban Lengkap`
- **Isi:** Kunci praktik, kuis ML, weekly quiz, assignment, final project
- **Akses:** `visible = 0` (tersembunyi) — hanya Guru/Admin dengan capability `moodle/course:viewhiddenactivities`

Untuk course yang **sudah dibuat** tanpa rebuild:

```bash
php admin/cli/add_teacher_keys_page.php --shortname=NAMA-SHORTNAME-COURSE-ANDA
```

**Minggu 7: Array dan Object Dasar**
- ML7.1: Array Dasar
- ML7.2: Array Methods
- ML7.3: Object Dasar
- ML7.4: Object Methods

**Minggu 8: Final Project**
- Final Project (3 options)

## 📊 Gradebook Configuration

Setelah course dibuat, configure gradebook:

1. **Masuk ke course** → **Grades** → **Setup** → **Categories and items**

2. **Buat kategori:**
   ```
   JavaScript Fundamental (100%)
   ├── Micro Quizzes (20%)
   ├── Weekly Quizzes (25%)
   ├── Weekly Assignments (25%)
   └── Final Project (30%)
   ```

3. **Set passing grade:** 70% untuk semua items

4. **Set aggregation:** Weighted mean of grades

## 🎓 Certificate Setup

### Install Custom Certificate Plugin

1. Download dari: https://moodle.org/plugins/mod_customcert
2. Install via **Site administration** → **Plugins** → **Install plugins**
3. Upload ZIP file

### Configure Certificate

1. **Masuk ke course** → **Add an activity** → **Custom Certificate**
2. **Name:** Sertifikat JavaScript Fundamental
3. **Add elements:**
   - Student name
   - Course name
   - Grade
   - Date
   - Instructor signature

4. **Set availability:**
   - Completion: Course completion = 100%
   - Grade: Final grade >= 70%

## ✅ Testing Checklist

Setelah course selesai dibuat:

- [ ] Test sebagai student
- [ ] Coba semua micro lessons
- [ ] Kerjakan semua kuis
- [ ] Submit semua assignments
- [ ] Test Aicode activities
- [ ] Verify gradebook calculation
- [ ] Test certificate generation
- [ ] Check completion tracking
- [ ] Test restriction access

## 🐛 Troubleshooting

### Problem: Quiz error "invalid types" / siswa tidak bisa membuka kuis
**Penyebab:** Course dibuat dengan builder lama sebelum Moodle 5 memakai tabel `question_references`, atau course dengan shortname selain `JS-FUND-2026` belum diperbaiki.

**Solution (CLI, sebagai admin):**
```bash
php admin/cli/repair_quiz_questions.php --all
php admin/cli/fix_quiz_attempts.php --all
```
Lalu siswa harus **mulai attempt baru** (bukan melanjutkan preview lama).

Perbaiki satu course saja:
```bash
php admin/cli/repair_quiz_questions.php JS-FUND-20260515-175353
php admin/cli/fix_quiz_attempts.php JS-FUND-20260515-175353
```

### Problem: Aicode activity tidak muncul
**Solution:** Pastikan plugin Aicode sudah terinstall dan enabled

### Problem: Quiz tidak bisa dibuat
**Solution:** Check permissions, pastikan Anda login sebagai admin

### Problem: Certificate tidak muncul
**Solution:** 
1. Check completion criteria
2. Verify grade >= 70%
3. Check certificate availability settings

### Problem: Script timeout
**Solution:** 
1. Increase PHP max_execution_time
2. Increase PHP memory_limit
3. Run script via CLI instead of web

## 📞 Support

Jika ada masalah:
1. Check error log di Moodle
2. Review `COURSE_CONTENT_COMPLETE.md` untuk referensi
3. Test dengan test course terlebih dahulu

## 📝 Notes

- **Minggu 1 sudah lengkap** dengan semua materi detail
- **Minggu 2-8 perlu dilengkapi** dengan copy-paste dari dokumentasi
- **Semua struktur sudah siap**, tinggal isi konten
- **Aicode activities sudah dibuat**, tinggal configure test cases
- **Gradebook perlu di-setup manual** setelah course dibuat

## 🎉 Quick Start

**Untuk mulai cepat:**

```bash
1. Buka: http://localhost/moodle/admin/build_js_course_full.php
2. Login sebagai admin
3. Tunggu selesai
4. Buka course yang dibuat
5. Review Minggu 1 (sudah lengkap)
6. Lengkapi Minggu 2-8 dengan copy-paste dari COURSE_CONTENT_COMPLETE.md
```

## 📚 Resources

- **Full Documentation:** `COURSE_CONTENT_COMPLETE.md`
- **Helper Functions:** `admin/course_builder_helpers.php`
- **Week 1 Content:** `admin/js_week1_content.php`
- **Main Builder:** `admin/build_js_course_full.php`

---

**Created by:** AI Assistant  
**Date:** 2026-05-14  
**Version:** 1.0  
**Status:** Ready to use (Minggu 1 complete, Minggu 2-8 need content)

---

## 🚀 NEXT STEPS

1. ✅ Run `build_js_course_full.php`
2. ✅ Review Minggu 1
3. ⏳ Complete Minggu 2-8 content
4. ⏳ Configure gradebook
5. ⏳ Setup certificate
6. ⏳ Test as student
7. ⏳ Enroll real students

**Estimated time to complete:** 
- Automatic creation: 2 minutes
- Manual content completion: 2-3 hours
- Testing: 30 minutes
- **Total: ~3-4 hours**
