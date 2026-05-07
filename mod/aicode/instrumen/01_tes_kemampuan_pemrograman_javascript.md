# Instrumen 1 — Tes kemampuan pemrograman (JavaScript)

**Konteks penelitian:** Siswa SMK · fokus JavaScript · aktivitas Moodle **AICode** · kelompok eksperimen (umpan balik LLM) vs kontrol (tanpa LLM), keduanya memakai plugin yang sama.

**Catatan etika:** informasikan bahwa kode dapat diproses sistem; pada kelompok tertentu juga oleh layanan AI. Patuhi izin sekolah dan orang tua.

---

## Tujuan

Mengukur kemampuan dasar algoritma dan pemrograman JavaScript sebelum dan sesudah intervensi (*pretest* / *posttest*), selaras dengan variabel kemampuan pemrograman dan debugging.

---

## Spesifikasi tes

| Aspek | Ketentuan |
|--------|-----------|
| Waktu | 60–90 menit (sesuaikan jam pelajaran) |
| Lingkungan | Kertas atau editor teks tanpa AI; **boleh** `console.log` pada kertas ditulis sebagai komentar penjelasan |
| Cakupan | Variabel, tipe data, operator, percabangan, perulangan, fungsi, array dasar |
| Tidak wajib | DOM, framework, modul ES |

---

## Rubrik penilaian (disarankan)

Skor per butir 0–4 (total dinormalisasi ke 0–100 jika diperlukan).

| Skor | Deskripsi |
|------|-----------|
| 4 | Solusi benar, logika tepat, sintaks wajar |
| 3 | Solusi hampir benar, kesalahan kecil (mis. off-by-one) |
| 2 | Solusi sebagian, menunjukkan pemahaman parsial |
| 1 | Usaha minimal, banyak kesalahan konsep |
| 0 | Kosong atau tidak relevan |

**Indikator debugging (tambahan opsional):** beri skor +1 maksimum per soal jika siswa menuliskan *langkah menemukan kesalahan* atau *tes kasus* pada soal perbaikan kode.

---

## Set A — Pretest

**Soal 1 (20 poin)**  
Jelaskan perbedaan `let` dan `const` dalam JavaScript. Berikan satu contoh penggunaan masing-masing (boleh pseudo-kode singkat).

**Soal 2 (20 poin)**  
Tuliskan fungsi `jumlahGanjil(n)` yang mengembalikan jumlah bilangan ganjil dari 1 sampai `n` (inklusif). Contoh: `jumlahGanjil(5)` → `9` (1+3+5).

**Soal 3 (20 poin)**  
Diberikan potongan kode berikut. Temukan kesalahannya dan tuliskan kode yang sudah diperbaiki.

```javascript
function cekDiskon(total) {
  if total > 100000 {
    return total * 0.1;
  }
  return 0;
}
```

**Soal 4 (20 poin)**  
Tuliskan program yang mencetak (atau mengembalikan string) deret: `2, 4, 8, 16, 32` menggunakan perulangan (boleh `for` atau `while`).

**Soal 5 (20 poin)**  
Diberikan array `let nilai = [70, 85, 60, 90];`  
Tuliskan kode untuk menghitung **rata-rata** nilai tersebut dan menampilkan hasilnya (boleh `console.log` atau `return` dari fungsi).

---

## Set B — Posttest (isi setara)

**Soal 1 (20 poin)**  
Jelaskan kapan sebaiknya memakai `const` dibanding `let` untuk variabel yang menampung array. Apakah isi array masih bisa diubah? Jelaskan singkat.

**Soal 2 (20 poin)**  
Tuliskan fungsi `jumlahGenap(n)` yang mengembalikan jumlah bilangan genap dari 1 sampai `n` (inklusif). Contoh: `jumlahGenap(5)` → `6` (2+4).

**Soal 3 (20 poin)**  
Perbaiki kode berikut agar valid dan logis.

```javascript
function sapa(nama) {
  console.log("Halo, " nama);
}
```

**Soal 4 (20 poin)**  
Dengan perulangan, cetak deret: `3, 6, 12, 24, 48`.

**Soal 5 (20 poin)**  
Dari array `let harga = [10000, 25000, 15000];`  
Hitung total harga, terapkan diskon **15%** jika total > 30000, lalu tampilkan total akhir.

---

## Penggunaan bersama course Moodle "JavaScript"

- Soal tes diselaraskan dengan **capaian pembelajaran** pada course tersebut (bukan harus sama persis dengan satu aktivitas AICode).
- Aktivitas AICode di course dipakai untuk **perlakuan**; tes ini untuk **pengukuran** terpisah agar validitas isi terjaga.
