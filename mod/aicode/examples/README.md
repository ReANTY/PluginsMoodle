# 📦 AICode Examples - JavaScript Quizzes

> **12 quiz siap pakai + interactive gallery**

---

## 🚀 Quick Start

### Cara Tercepat (2 menit):

1. **Buka** [`quiz_gallery.html`](quiz_gallery.html) di browser Anda
2. **Browse** 12 quiz dengan filter difficulty
3. **Click** "Lihat Detail & Copy" pada quiz pilihan
4. **Copy** starter code dan test cases
5. **Paste** ke Moodle AICode form

### Cara Import JSON (3 menit):

1. **Buka** [`javascript_quiz_examples.json`](javascript_quiz_examples.json)
2. **Copy** JSON untuk quiz yang diinginkan
3. **Parse** dan paste ke form Moodle
4. Done! ✅

---

## 📂 Files in This Folder

### 🌐 [`quiz_gallery.html`](quiz_gallery.html) - **MULAI DI SINI!**

**Interactive visual gallery of all quizzes**

**Features:**

- ✨ Beautiful UI with card layout
- 🔍 Filter by difficulty (Beginner/Intermediate)
- 📋 One-click copy to clipboard
- 📱 Responsive design
- 💡 Shows starter code, test cases, and skills

**How to use:**

```
1. Double-click quiz_gallery.html
2. Opens in your default browser
3. Browse, filter, click to view details
4. Copy what you need
```

**Best for:** Visual learners, quick browsing, presentations

---

### 📄 [`javascript_quiz_examples.json`](javascript_quiz_examples.json)

**Complete JSON data of all 12 quizzes**

**Structure:**

```json
[
  {
    "name": "Quiz name",
    "description": "HTML description",
    "language": "javascript",
    "startercode": "function...",
    "testcases": "[{...}]",
    "difficulty": "beginner",
    "estimated_time": "10 minutes"
  },
  ...
]
```

**How to use:**

```
1. Open in text editor
2. Find quiz you want (Ctrl+F)
3. Copy entire object {...}
4. Parse fields into Moodle form
```

**Best for:** Bulk import, automation, reference

---

### 📄 [`sample_problems.json`](sample_problems.json)

**Original sample quizzes from plugin**

**Contains:**

- 3 example problems
- Focus on error types (syntax, runtime, logic)
- Educational error examples

**How to use:**
Similar to javascript_quiz_examples.json

**Best for:** Understanding error types, debugging lessons

---

## 📊 Quiz Inventory

### Beginner (4 quizzes) - 45 min total

| #   | Name                  | Time   | Topics                        |
| --- | --------------------- | ------ | ----------------------------- |
| 1   | Fungsi Penjumlahan    | 10 min | Functions, parameters, return |
| 2   | Filter Bilangan Genap | 15 min | Arrays, loops, modulo         |
| 6   | Hitung Kata           | 15 min | String methods, split         |
| 7   | Find Maximum          | 15 min | Array iteration, comparison   |
| 8   | Reverse String        | 10 min | String manipulation           |

### Intermediate (8 quizzes) - 2h 40min total

| #   | Name               | Time   | Topics                          |
| --- | ------------------ | ------ | ------------------------------- |
| 3   | Validasi Email     | 20 min | String validation, conditionals |
| 4   | Deret Fibonacci    | 25 min | Algorithms, recursion/iteration |
| 5   | Palindrome Checker | 25 min | String algorithms               |
| 9   | FizzBuzz           | 20 min | Loops, multiple conditions      |
| 10  | Remove Duplicates  | 20 min | Set, unique values              |
| 11  | Faktorial          | 20 min | Recursion, math operations      |
| 12  | Capitalize Words   | 20 min | String methods, map             |

---

## 🎯 Usage Scenarios

### Scenario 1: Quick Single Quiz

**Goal:** Add one quiz to your course ASAP

**Steps:**

1. Open `quiz_gallery.html`
2. Find quiz (e.g., "Penjumlahan")
3. Click "Lihat Detail"
4. Copy starter code & test cases
5. Paste into Moodle AICode form
6. Save ✅

**Time:** 2-3 minutes

---

### Scenario 2: Plan Full Course

**Goal:** Use multiple quizzes in sequence

**Steps:**

1. Read [`../CONTOH_LESSON_PLAN.md`](../CONTOH_LESSON_PLAN.md)
2. Select quizzes for each week
3. Import using `quiz_gallery.html`
4. Arrange in Moodle course

**Time:** 30 minutes

---

### Scenario 3: Customize Quiz

**Goal:** Modify existing quiz for your needs

**Steps:**

1. Open `javascript_quiz_examples.json`
2. Find base quiz
3. Copy JSON
4. Modify description/test cases/starter code
5. Import to Moodle

**Time:** 10-15 minutes

---

## 💡 Pro Tips

### For Teachers:

**Tip 1: Test First**
Always test quiz yourself before assigning to students!

```
Moodle → Switch role to Student → Try quiz
```

**Tip 2: Sequential Difficulty**
Start with Quiz 1, 2, 8 (easy wins for students)

**Tip 3: Customize Descriptions**
Add your own examples, hints, learning objectives

**Tip 4: Mix & Match**
Combine with non-AICode activities (video, reading)

**Tip 5: Monitor Progress**
Check attempts regularly via "View attempts"

---

### For Quiz Selection:

**Week 1 Course:** Quiz 1, 8 (simple, confidence building)  
**Week 2-3 Course:** Add Quiz 2, 6, 7 (arrays & strings)  
**Week 4-5 Course:** Add Quiz 3, 4, 5 (algorithms)  
**Week 6+ Course:** Add Quiz 9, 10, 11, 12 (advanced)

---

## 🔄 Update This Folder

### Adding New Quiz:

1. **Edit** `javascript_quiz_examples.json`
2. **Add** new quiz object to array
3. **Update** `quiz_gallery.html` JavaScript array
4. **Test** in browser

### Sharing Your Quiz:

1. Create JSON following the format
2. Submit PR or share in forum
3. Help community grow! 🌱

---

## 📚 Related Documentation

| File                                                                       | Description        | Audience           |
| -------------------------------------------------------------------------- | ------------------ | ------------------ |
| [`../INDEX_DOKUMENTASI.md`](../INDEX_DOKUMENTASI.md)                       | Complete index     | Everyone           |
| [`../QUICK_START_INDONESIAN.md`](../QUICK_START_INDONESIAN.md)             | Quick start guide  | Teachers, Students |
| [`../CONTOH_PENGGUNAAN_JAVASCRIPT.md`](../CONTOH_PENGGUNAAN_JAVASCRIPT.md) | Complete examples  | Teachers           |
| [`../REFERENCE_CARD.md`](../REFERENCE_CARD.md)                             | Quick reference    | Everyone           |
| [`../CONTOH_LESSON_PLAN.md`](../CONTOH_LESSON_PLAN.md)                     | 6-week course plan | Teachers           |
| [`../README.md`](../README.md)                                             | Plugin docs (EN)   | Admin, Developers  |

---

## 🎨 Visual Preview

### Quiz Gallery HTML Interface:

```
┌─────────────────────────────────────────┐
│  🚀 AICode JavaScript Quiz Gallery      │
│  12 Quiz Siap Pakai untuk Moodle        │
├─────────────────────────────────────────┤
│  [Semua] [Beginner] [Intermediate]      │
├─────────────────────────────────────────┤
│  ┌──────────┐  ┌──────────┐            │
│  │ Quiz 1   │  │ Quiz 2   │  ...       │
│  │ ⭐ 10min │  │ ⭐ 15min │            │
│  │ [Detail] │  │ [Detail] │            │
│  └──────────┘  └──────────┘            │
└─────────────────────────────────────────┘
```

---

## ⚡ Quick Actions

### I want to...

**...see all quizzes visually**
→ Open `quiz_gallery.html`

**...copy a quiz quickly**
→ Open `quiz_gallery.html` → Find quiz → Click "Lihat Detail"

**...understand JSON structure**
→ Open `javascript_quiz_examples.json`

**...plan my course**
→ Read [`../CONTOH_LESSON_PLAN.md`](../CONTOH_LESSON_PLAN.md)

**...get documentation**
→ Go to [`../INDEX_DOKUMENTASI.md`](../INDEX_DOKUMENTASI.md)

---

## 📈 Statistics

**Total Quizzes:** 12  
**Total Time:** ~3.5 hours  
**Beginner:** 4 quizzes (40%)  
**Intermediate:** 8 quizzes (60%)  
**Languages:** JavaScript (more coming!)  
**Format:** JSON + HTML gallery

---

## 🆘 Troubleshooting

### Quiz gallery not loading?

- Check file path is correct
- Try different browser
- Check console for errors

### JSON format error?

- Validate at jsonlint.com
- Check quote escaping
- Ensure commas are correct

### Test cases not working?

- Check format matches documentation
- Verify JSON.stringify for arrays
- Review REFERENCE_CARD.md

---

## 🎉 You're Ready!

**Start here:**

1. 👉 **Double-click** [`quiz_gallery.html`](quiz_gallery.html)
2. **Browse** the beautiful interface
3. **Select** a quiz
4. **Copy** to Moodle
5. **Done!** ✅

**Questions?**

- Read [`../QUICK_START_INDONESIAN.md`](../QUICK_START_INDONESIAN.md)
- Check [`../INDEX_DOKUMENTASI.md`](../INDEX_DOKUMENTASI.md)

---

**Happy Teaching! 🎓**

**Crafted with ❤️ for Moodle educators**
