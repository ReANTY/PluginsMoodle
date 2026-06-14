<?php
/**
 * [KHUSUS GURU] Kode solusi praktik, assignment, dan final project.
 * Key = nama aktivitas (harus sama dengan field 'name' di week content).
 */

defined('MOODLE_INTERNAL') || die();

return [
    // ---- MINGGU 1 ----
    'Praktik ML1.1 - Hello World' => [
        'code' => "console.log('Hello World');",
        'note' => 'Output console harus persis: Hello World',
    ],
    'Praktik ML1.2 - Script di HTML' => [
        'code' => "console.log('Program dimulai');\nalert('Selamat Datang');",
        'note' => 'Testcase Aicode hanya memeriksa baris console pertama.',
    ],
    'Praktik ML1.3 - Sintaks Dasar' => [
        'code' => "let nama = 'budi';\nlet Nama = 'BUDI';\nconsole.log(nama);\nconsole.log(Nama);",
    ],
    'Praktik ML1.4 - Output JavaScript' => [
        'code' => "console.log('Belajar JavaScript itu menyenangkan');",
    ],
    'Weekly Assignment - Minggu 1' => [
        'code' => "console.log('=== PROFIL SAYA ===');\nconsole.log('Nama: Rina Wijaya');\nconsole.log('Umur: 17');\nconsole.log('Hobi 1: Coding');\nconsole.log('Hobi 2: Membaca');\nconsole.log('Hobi 3: Musik');",
        'rubric' => 'Kode jalan 30% | console.log benar 40% | format rapi 30%',
    ],

    // ---- MINGGU 2 ----
    'Praktik ML2.1 - Profil Variabel' => [
        'code' => "function tampilkanProfil(nama, umur, kota) {\n    return 'Nama: ' + nama + ', Umur: ' + umur + ', Kota: ' + kota;\n}",
    ],
    'Praktik ML2.2 - Gabung Nama' => [
        'code' => "function gabungNama(depan, belakang) {\n    return depan + ' ' + belakang;\n}",
    ],
    'Praktik ML2.3 - Hitung Luas' => [
        'code' => "function hitungLuas(panjang, lebar) {\n    return panjang * lebar;\n}",
    ],
    'Praktik ML2.4 - Cek Usia Dewasa' => [
        'code' => "function isDewasa(usia) {\n    return usia >= 17;\n}",
    ],
    'Weekly Assignment - Minggu 2: Kartu Data Siswa' => [
        'code' => "function buatKartuSiswa(nama, umur, aktif, nilai) {\n    return 'Kartu: ' + nama + ' | Umur: ' + umur + ' | Aktif: ' + aktif + ' | Nilai: ' + nilai;\n}",
    ],

    // ---- MINGGU 3 ----
    'Praktik ML3.1 - Kalkulator Sederhana' => [
        'code' => "function kalkulator(a, b, operasi) {\n    if (operasi === '+') return a + b;\n    if (operasi === '-') return a - b;\n    if (operasi === '*') return a * b;\n    if (operasi === '/') return b === 0 ? 'Error' : a / b;\n    return 'Error';\n}",
    ],
    'Praktik ML3.2 - Tambah Skor Game' => [
        'code' => "function tambahSkor(skorAwal, poinTambah) {\n    skorAwal += poinTambah;\n    return skorAwal;\n}",
    ],
    'Praktik ML3.3 - Sapa Pengguna' => [
        'code' => "function sapaPengguna(nama, umurStr) {\n    let umur = Number(umurStr);\n    return 'Halo, ' + nama + '! Tahun depan umur Anda ' + (umur + 1) + '.';\n}",
    ],
    'Praktik ML3.4 - Konversi Input' => [
        'code' => "function hitungTotal(hargaStr, qtyStr) {\n    let h = Number(hargaStr);\n    let q = Number(qtyStr);\n    if (isNaN(h) || isNaN(q)) return 'Input tidak valid';\n    return h * q;\n}",
    ],
    'Weekly Assignment - Minggu 3: Kalkulator Belanja' => [
        'code' => "function hitungBelanja(namaBarang, hargaStr, jumlahStr, diskonPersen) {\n    let harga = Number(hargaStr);\n    let jumlah = Number(jumlahStr);\n    let subtotal = harga * jumlah;\n    let total = Math.round(subtotal - (subtotal * diskonPersen / 100));\n    return namaBarang + ': Rp' + total + ' (diskon ' + diskonPersen + '%)';\n}",
    ],

    // ---- MINGGU 4 ----
    'Praktik ML4.1 - Cek Genap' => [
        'code' => "function cekGenap(n) {\n    return n % 2 === 0;\n}",
    ],
    'Praktik ML4.2 - Grade Nilai' => [
        'code' => "function gradeNilai(nilai) {\n    if (nilai >= 85) return 'A';\n    if (nilai >= 70) return 'B';\n    if (nilai >= 60) return 'C';\n    return 'D';\n}",
    ],
    'Praktik ML4.3 - Nama Hari' => [
        'code' => "function namaHari(n) {\n    switch (n) {\n        case 1: return 'Senin';\n        case 2: return 'Selasa';\n        case 3: return 'Rabu';\n        case 4: return 'Kamis';\n        case 5: return 'Jumat';\n        case 6: return 'Sabtu';\n        case 7: return 'Minggu';\n        default: return 'Tidak valid';\n    }\n}",
    ],
    'Praktik ML4.4 - Diskon Member' => [
        'code' => "function hargaFinal(harga, isMember) {\n    if (isMember) return harga * 0.9;\n    return harga;\n}",
    ],
    'Weekly Assignment - Minggu 4: Sistem Tiket Bioskop' => [
        'code' => "function hargaTiket(umur, hari, isMember) {\n    let harga;\n    if (umur < 12) harga = 25000;\n    else if (umur <= 17) harga = 35000;\n    else if (umur <= 59) harga = 50000;\n    else harga = 30000;\n    if (hari === 'sabtu' || hari === 'minggu') harga += 5000;\n    if (isMember) harga = Math.round(harga * 0.85);\n    return 'Tiket: Rp' + harga;\n}",
    ],

    // ---- MINGGU 5 ----
    'Praktik ML5.1 - Jumlah dengan for' => [
        'code' => "function jumlahSampai(n) {\n    let total = 0;\n    for (let i = 1; i <= n; i++) total += i;\n    return total;\n}",
    ],
    'Praktik ML5.2 - Jumlah array dengan while' => [
        'code' => "function jumlahArray(arr) {\n    let i = 0, total = 0;\n    while (i < arr.length) {\n        total += arr[i];\n        i++;\n    }\n    return total;\n}",
    ],
    'Praktik ML5.3 - Hitung mundur (do-while)' => [
        'code' => "function hitungMundur(n) {\n    if (n <= 0) return [];\n    let hasil = [], x = n;\n    do {\n        hasil.push(x);\n        x--;\n    } while (x > 0);\n    return hasil;\n}",
    ],
    'Praktik ML5.4 - Cari indeks (break)' => [
        'code' => "function cariIndeks(arr, target) {\n    for (let i = 0; i < arr.length; i++) {\n        if (arr[i] === target) return i;\n    }\n    return -1;\n}",
    ],
    'Weekly Assignment - Minggu 5: Bilangan Genap dengan Loop' => [
        'code' => "function kumpulkanGenap(arr) {\n    let hasil = [];\n    for (let i = 0; i < arr.length; i++) {\n        if (arr[i] % 2 === 0) hasil.push(arr[i]);\n    }\n    return hasil;\n}",
    ],

    // ---- MINGGU 6 (Iframe) ----
    'Praktik ML6.1 - Tombol Sapa' => [
        'code' => "function sapa() {\n  document.getElementById('output').textContent = 'Halo, JavaScript!';\n}\ndocument.getElementById('btnSapa').addEventListener('click', sapa);",
    ],
    'Praktik ML6.2 - Kalkulator Tambah' => [
        'code' => "function tambah(a, b) { return a + b; }\ndocument.getElementById('btnHitung').addEventListener('click', function() {\n  let a = Number(document.getElementById('angka1').value);\n  let b = Number(document.getElementById('angka2').value);\n  document.getElementById('hasil').textContent = 'Hasil: ' + tambah(a, b);\n});",
    ],
    'Praktik ML6.3 - Counter Klik' => [
        'code' => "let count = 0;\ndocument.getElementById('btnPlus').addEventListener('click', function() {\n  count++;\n  document.getElementById('counter').textContent = count;\n});",
    ],
    'Praktik ML6.4 - Live Preview Nama' => [
        'code' => "document.getElementById('namaInput').addEventListener('input', function() {\n  let nama = this.value;\n  document.getElementById('preview').textContent = nama ? 'Halo, ' + nama + '!' : 'Ketik nama...';\n});",
    ],
    'Weekly Assignment - Minggu 6: Kalkulator Web' => [
        'code' => "function hitung(a, b, op) {\n  if (op === '+') return a + b;\n  if (op === '-') return a - b;\n  if (op === '*') return a * b;\n  if (op === '/') return b === 0 ? 'Error' : a / b;\n  return 'Error';\n}\ndocument.querySelectorAll('button[data-op]').forEach(function(btn) {\n  btn.addEventListener('click', function() {\n    let a = Number(document.getElementById('a').value);\n    let b = Number(document.getElementById('b').value);\n    document.getElementById('hasil').textContent = 'Hasil: ' + hitung(a, b, btn.dataset.op);\n  });\n});",
    ],

    // ---- MINGGU 7 (Iframe) ----
    'Praktik ML7.1 - List Buah' => [
        'code' => "let buah = ['Apel', 'Mangga', 'Jeruk'];\nlet list = document.getElementById('listBuah');\nbuah.forEach(function(item) {\n  let li = document.createElement('li');\n  li.textContent = item;\n  list.appendChild(li);\n});",
    ],
    'Praktik ML7.2 - Kartu Profil' => [
        'code' => "let siswa = { nama: 'Budi', kelas: 'XI PPLG' };\ndocument.getElementById('profil').textContent = 'Nama: ' + siswa.nama + ' | Kelas: ' + siswa.kelas;",
    ],
    'Praktik ML7.3 - Ubah Teks & Warna' => [
        'code' => "document.getElementById('btnUbah').addEventListener('click', function() {\n  let el = document.getElementById('teks');\n  el.textContent = 'Teks diubah!';\n  el.style.color = 'red';\n});",
    ],
    'Praktik ML7.4 - Tabel Nilai' => [
        'code' => "let data = [{ nama: 'Ani', nilai: 90 }, { nama: 'Budi', nilai: 85 }];\nlet tbody = document.getElementById('tbodyNilai');\ndata.forEach(function(s) {\n  let tr = document.createElement('tr');\n  tr.innerHTML = '<td>' + s.nama + '</td><td>' + s.nilai + '</td>';\n  tbody.appendChild(tr);\n});",
    ],
    'Weekly Assignment - Minggu 7: Daftar Siswa Interaktif' => [
        'code' => "let siswa = [];\nfunction render() {\n  let tbody = document.getElementById('tbody');\n  tbody.innerHTML = '';\n  let total = 0;\n  siswa.forEach(function(s) {\n    total += s.nilai;\n    let tr = document.createElement('tr');\n    tr.innerHTML = '<td>' + s.nama + '</td><td>' + s.nilai + '</td>';\n    tbody.appendChild(tr);\n  });\n  document.getElementById('rata').textContent = siswa.length\n    ? 'Rata-rata: ' + (total / siswa.length).toFixed(1)\n    : 'Rata-rata: -';\n}\ndocument.getElementById('btnTambah').addEventListener('click', function() {\n  let nama = document.getElementById('nama').value.trim();\n  let nilai = Number(document.getElementById('nilai').value);\n  if (!nama || isNaN(nilai)) return;\n  siswa.push({ nama: nama, nilai: nilai });\n  document.getElementById('nama').value = '';\n  document.getElementById('nilai').value = '';\n  render();\n});",
    ],

    // ---- MINGGU 8 ----
    'Praktik ML8.1 - Pseudocode Project' => [
        'code' => "console.log('1. Buat array todos');\nconsole.log('2. Buat function render()');\nconsole.log('3. Event tambah — push ke array');\nconsole.log('4. Event hapus — splice dari array');\nconsole.log('5. Update counter di #info');",
    ],
    'Praktik ML8.2 - Siapkan DOM Todo' => [
        'code' => "document.getElementById('info').textContent = 'Siap membangun Todo List!';",
    ],
    'Praktik ML8.3 - Tambah ke Array' => [
        'code' => "let todos = [];\ndocument.getElementById('btnTambah').addEventListener('click', function() {\n  let teks = document.getElementById('inputTodo').value.trim();\n  if (!teks) return;\n  todos.push(teks);\n  document.getElementById('info').textContent = 'Total: ' + todos.length + ' tugas';\n});",
    ],
    'Praktik ML8.4 - Render List Lengkap' => [
        'code' => "let todos = ['Belajar JS', 'Kerjakan project'];\nfunction render() {\n  let list = document.getElementById('listTodo');\n  list.innerHTML = '';\n  todos.forEach(function(t) {\n    let li = document.createElement('li');\n    li.textContent = t;\n    list.appendChild(li);\n  });\n  document.getElementById('info').textContent = 'Total: ' + todos.length + ' tugas';\n}\nrender();",
    ],
    'Weekly Assignment - Minggu 8: Mini Todo (Latihan)' => [
        'code' => "let todos = [];\nfunction render() {\n  let list = document.getElementById('listTodo');\n  list.innerHTML = '';\n  todos.forEach(function(item, index) {\n    let li = document.createElement('li');\n    li.innerHTML = '<span>' + item + '</span><button class=\"btn-hapus\" data-i=\"' + index + '\">Hapus</button>';\n    list.appendChild(li);\n  });\n  document.getElementById('info').textContent = 'Total: ' + todos.length + ' tugas';\n  document.querySelectorAll('.btn-hapus').forEach(function(btn) {\n    btn.addEventListener('click', function() {\n      todos.splice(Number(btn.dataset.i), 1);\n      render();\n    });\n  });\n}\ndocument.getElementById('btnTambah').addEventListener('click', function() {\n  let teks = document.getElementById('inputTodo').value.trim();\n  if (!teks) return;\n  todos.push(teks);\n  document.getElementById('inputTodo').value = '';\n  render();\n});",
    ],
    'Final Project - JavaScript Fundamental' => [
        'code' => "let todos = [];\n\nfunction render() {\n  let list = document.getElementById('listTodo');\n  list.innerHTML = '';\n  todos.forEach(function(item, index) {\n    let li = document.createElement('li');\n    li.innerHTML = '<span>' + item + '</span><button class=\"btn-hapus\" data-i=\"' + index + '\">Hapus</button>';\n    list.appendChild(li);\n  });\n  document.getElementById('info').textContent = 'Total: ' + todos.length + ' tugas';\n  document.querySelectorAll('.btn-hapus').forEach(function(btn) {\n    btn.addEventListener('click', function() {\n      todos.splice(Number(btn.dataset.i), 1);\n      render();\n    });\n  });\n}\n\ndocument.getElementById('btnTambah').addEventListener('click', function() {\n  let input = document.getElementById('inputTodo');\n  let teks = input.value.trim();\n  if (teks === '') return;\n  todos.push(teks);\n  input.value = '';\n  render();\n});\n\ndocument.getElementById('inputTodo').addEventListener('keypress', function(e) {\n  if (e.key === 'Enter') document.getElementById('btnTambah').click();\n});\n\nrender();",
        'rubric' => 'Fitur 30 | Event 20 | Array/object 15 | DOM 15 | Validasi 10 | UI 10 — pass 70%',
    ],
];
