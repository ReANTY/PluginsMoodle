<?php
/**
 * MINGGU 1: PENGENALAN JAVASCRIPT
 * Tujuan: Peserta memahami apa itu JavaScript, cara menjalankannya, dan sintaks dasar
 * Jumlah Micro Lesson: 4
 * Estimasi waktu total: 40 menit
 */

$week1_data = [
    'section_name' => 'Minggu 1: Pengenalan JavaScript',
    'section_summary' => '<div class="week-summary">
        <h4>🎯 Tujuan Mingguan</h4>
        <p>Setelah menyelesaikan minggu ini, Anda akan mampu:</p>
        <ul>
            <li>Memahami apa itu JavaScript dan kegunaannya</li>
            <li>Menjalankan kode JavaScript di browser</li>
            <li>Memahami sintaks dasar JavaScript</li>
            <li>Menulis program JavaScript sederhana</li>
        </ul>
        <h4>📚 Materi</h4>
        <p>4 Micro Lessons | 4 Praktik Coding | 4 Kuis Singkat | 1 Weekly Quiz | 1 Weekly Assignment</p>
        <h4>⏱️ Estimasi Waktu</h4>
        <p>Total: ±60 menit</p>
    </div>',
    
    'micro_lessons' => [
        // ========================================
        // MICRO LESSON 1.1
        // ========================================
        [
            'title' => 'ML1.1 - Apa itu JavaScript?',
            'type' => 'page',
            'intro' => 'Pelajari apa itu JavaScript, sejarahnya, dan mengapa JavaScript penting untuk dipelajari.',
            'content' => '<div class="micro-lesson">
                <h3>🌟 Apa itu JavaScript?</h3>
                
                <h4>Penjelasan</h4>
                <p><strong>JavaScript</strong> adalah bahasa pemrograman yang digunakan untuk membuat website menjadi interaktif dan dinamis. JavaScript berjalan di browser (seperti Chrome, Firefox, Safari) dan memungkinkan kita membuat fitur-fitur seperti:</p>
                <ul>
                    <li>Tombol yang bisa diklik</li>
                    <li>Form yang bisa divalidasi</li>
                    <li>Animasi dan efek visual</li>
                    <li>Aplikasi web yang kompleks</li>
                </ul>
                
                <p>JavaScript diciptakan oleh <strong>Brendan Eich</strong> pada tahun 1995 dan sekarang menjadi salah satu bahasa pemrograman paling populer di dunia.</p>
                
                <div class="alert alert-info">
                    <strong>💡 Tahukah Anda?</strong><br>
                    JavaScript berbeda dengan Java! Meskipun namanya mirip, keduanya adalah bahasa pemrograman yang berbeda.
                </div>
                
                <h4>Poin Penting</h4>
                <ul>
                    <li>✅ JavaScript adalah bahasa pemrograman untuk web</li>
                    <li>✅ JavaScript berjalan di browser (client-side)</li>
                    <li>✅ JavaScript membuat website menjadi interaktif</li>
                    <li>✅ JavaScript mudah dipelajari untuk pemula</li>
                </ul>
                
                <h4>Contoh Penggunaan JavaScript</h4>
                <p>Berikut adalah contoh sederhana JavaScript yang menampilkan pesan:</p>
                <pre><code class="language-javascript">// Menampilkan pesan di console browser
console.log("Hello, JavaScript!");

// Menampilkan popup alert
alert("Selamat datang di JavaScript!");

// Mengubah isi halaman web
document.write("JavaScript itu mudah!");</code></pre>
                
                <div class="alert alert-warning">
                    <strong>📝 Catatan:</strong><br>
                    Kode di atas menggunakan tiga cara berbeda untuk menampilkan output di JavaScript. Kita akan pelajari lebih detail di lesson berikutnya.
                </div>
                
                <h4>Kenapa Belajar JavaScript?</h4>
                <ol>
                    <li><strong>Populer:</strong> Digunakan oleh jutaan developer di seluruh dunia</li>
                    <li><strong>Versatile:</strong> Bisa untuk web, mobile, desktop, bahkan server</li>
                    <li><strong>Mudah dipelajari:</strong> Sintaks yang sederhana dan banyak resource</li>
                    <li><strong>Karir:</strong> Banyak lowongan pekerjaan untuk JavaScript developer</li>
                </ol>
            </div>',
            'completion' => COMPLETION_TRACKING_MANUAL,
            'estimated_time' => 8,
        ],
        
        // ========================================
        // MICRO LESSON 1.2
        // ========================================
        [
            'title' => 'ML1.2 - Menjalankan JavaScript',
            'type' => 'page',
            'intro' => 'Pelajari berbagai cara menjalankan kode JavaScript di browser.',
            'content' => '<div class="micro-lesson">
                <h3>🚀 Cara Menjalankan JavaScript</h3>
                
                <h4>Penjelasan</h4>
                <p>Ada beberapa cara untuk menjalankan kode JavaScript:</p>
                
                <h5>1. Browser Console (Cara Termudah)</h5>
                <p>Console adalah tool bawaan browser untuk menjalankan JavaScript secara langsung.</p>
                <p><strong>Cara membuka Console:</strong></p>
                <ul>
                    <li><strong>Windows/Linux:</strong> Tekan <kbd>F12</kbd> atau <kbd>Ctrl + Shift + J</kbd></li>
                    <li><strong>Mac:</strong> Tekan <kbd>Cmd + Option + J</kbd></li>
                    <li>Atau klik kanan → Inspect → Tab Console</li>
                </ul>
                
                <div class="alert alert-success">
                    <strong>✅ Praktik Sekarang!</strong><br>
                    Buka console browser Anda dan ketik: <code>console.log("Hello World")</code> lalu tekan Enter.
                </div>
                
                <h5>2. File HTML dengan Tag &lt;script&gt;</h5>
                <p>Cara paling umum adalah menyisipkan JavaScript di file HTML:</p>
                <pre><code class="language-html">&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;title&gt;Belajar JavaScript&lt;/title&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;h1&gt;Hello World&lt;/h1&gt;
    
    &lt;script&gt;
        // JavaScript code di sini
        console.log("JavaScript berjalan!");
        alert("Selamat datang!");
    &lt;/script&gt;
&lt;/body&gt;
&lt;/html&gt;</code></pre>
                
                <h5>3. File JavaScript Terpisah (.js)</h5>
                <p>Untuk project yang lebih besar, pisahkan JavaScript ke file terpisah:</p>
                <pre><code class="language-html">&lt;!-- File: index.html --&gt;
&lt;script src="script.js"&gt;&lt;/script&gt;</code></pre>
                <pre><code class="language-javascript">// File: script.js
console.log("JavaScript dari file terpisah!");</code></pre>
                
                <h4>Poin Penting</h4>
                <ul>
                    <li>✅ Console browser adalah cara tercepat untuk testing</li>
                    <li>✅ Tag &lt;script&gt; diletakkan sebelum &lt;/body&gt;</li>
                    <li>✅ File .js terpisah lebih rapi untuk project besar</li>
                    <li>✅ Gunakan console.log() untuk debugging</li>
                </ul>
                
                <h4>Contoh Lengkap</h4>
                <pre><code class="language-html">&lt;!DOCTYPE html&gt;
&lt;html lang="id"&gt;
&lt;head&gt;
    &lt;meta charset="UTF-8"&gt;
    &lt;title&gt;Contoh JavaScript&lt;/title&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;h1 id="judul"&gt;Belajar JavaScript&lt;/h1&gt;
    &lt;button onclick="ubahJudul()"&gt;Klik Saya&lt;/button&gt;
    
    &lt;script&gt;
        function ubahJudul() {
            document.getElementById("judul").innerHTML = "JavaScript Keren!";
            console.log("Judul berhasil diubah!");
        }
    &lt;/script&gt;
&lt;/body&gt;
&lt;/html&gt;</code></pre>
                
                <div class="alert alert-warning">
                    <strong>📝 Catatan:</strong><br>
                    Jangan khawatir jika belum paham semua kode di atas. Kita akan pelajari step by step!
                </div>
            </div>',
            'completion' => COMPLETION_TRACKING_MANUAL,
            'estimated_time' => 10,
        ],
        
        // ========================================
        // MICRO LESSON 1.3
        // ========================================
        [
            'title' => 'ML1.3 - Sintaks Dasar JavaScript',
            'type' => 'page',
            'intro' => 'Pelajari aturan penulisan kode JavaScript yang benar.',
            'content' => '<div class="micro-lesson">
                <h3>📝 Sintaks Dasar JavaScript</h3>
                
                <h4>Penjelasan</h4>
                <p><strong>Sintaks</strong> adalah aturan penulisan kode dalam bahasa pemrograman. JavaScript memiliki aturan yang harus diikuti agar kode bisa berjalan dengan benar.</p>
                
                <h5>1. Statement (Pernyataan)</h5>
                <p>Statement adalah instruksi yang diberikan ke komputer. Setiap statement diakhiri dengan <strong>titik koma (;)</strong></p>
                <pre><code class="language-javascript">console.log("Hello");  // Statement 1
console.log("World");  // Statement 2
alert("Selamat datang");  // Statement 3</code></pre>
                
                <h5>2. Case Sensitive</h5>
                <p>JavaScript membedakan huruf besar dan kecil:</p>
                <pre><code class="language-javascript">let nama = "Budi";  // ✅ Benar
let Nama = "Ani";   // ✅ Benar (variabel berbeda!)
let NAMA = "Citra"; // ✅ Benar (variabel berbeda lagi!)

console.log(nama);  // Output: Budi
console.log(Nama);  // Output: Ani
console.log(NAMA);  // Output: Citra</code></pre>
                
                <h5>3. Komentar (Comments)</h5>
                <p>Komentar adalah catatan dalam kode yang tidak dijalankan:</p>
                <pre><code class="language-javascript">// Ini komentar satu baris

/* 
   Ini komentar
   multi baris
*/

console.log("Hello"); // Komentar bisa di akhir baris</code></pre>
                
                <h5>4. Whitespace dan Indentasi</h5>
                <p>JavaScript mengabaikan spasi berlebih, tapi indentasi membuat kode lebih rapi:</p>
                <pre><code class="language-javascript">// ❌ Sulit dibaca
console.log("A");console.log("B");console.log("C");

// ✅ Mudah dibaca
console.log("A");
console.log("B");
console.log("C");</code></pre>
                
                <h5>5. Identifier (Nama)</h5>
                <p>Aturan penamaan variabel, function, dll:</p>
                <ul>
                    <li>✅ Harus dimulai dengan huruf, underscore (_), atau dollar ($)</li>
                    <li>✅ Bisa mengandung huruf, angka, underscore, dollar</li>
                    <li>❌ Tidak boleh menggunakan kata reserved (if, for, while, dll)</li>
                    <li>❌ Tidak boleh mengandung spasi</li>
                </ul>
                <pre><code class="language-javascript">// ✅ Valid
let nama;
let _private;
let $jquery;
let nama123;
let namaLengkap;  // camelCase (recommended)

// ❌ Invalid
let 123nama;      // Dimulai dengan angka
let nama-lengkap; // Mengandung dash
let for;          // Kata reserved</code></pre>
                
                <h4>Poin Penting</h4>
                <ul>
                    <li>✅ Setiap statement diakhiri titik koma (;)</li>
                    <li>✅ JavaScript case sensitive</li>
                    <li>✅ Gunakan komentar untuk dokumentasi</li>
                    <li>✅ Indentasi membuat kode lebih rapi</li>
                    <li>✅ Gunakan camelCase untuk penamaan</li>
                </ul>
                
                <h4>Contoh Kode Lengkap</h4>
                <pre><code class="language-javascript">// Program sederhana dengan sintaks yang benar

// Deklarasi variabel
let namaSiswa = "Budi Santoso";
let umur = 20;
let sudahLulus = false;

// Menampilkan output
console.log("Nama: " + namaSiswa);
console.log("Umur: " + umur);
console.log("Status: " + (sudahLulus ? "Lulus" : "Belum Lulus"));

/* 
   Catatan:
   - Gunakan nama variabel yang deskriptif
   - Ikuti konvensi camelCase
   - Tambahkan komentar untuk kode yang kompleks
*/</code></pre>
                
                <div class="alert alert-info">
                    <strong>💡 Tips:</strong><br>
                    Biasakan menulis kode yang rapi dan konsisten sejak awal. Ini akan memudahkan Anda dan orang lain membaca kode Anda!
                </div>
            </div>',
            'completion' => COMPLETION_TRACKING_MANUAL,
            'estimated_time' => 10,
        ],
        
        // ========================================
        // MICRO LESSON 1.4
        // ========================================
        [
            'title' => 'ML1.4 - Output di JavaScript',
            'type' => 'page',
            'intro' => 'Pelajari berbagai cara menampilkan output dalam JavaScript.',
            'content' => '<div class="micro-lesson">
                <h3>📤 Menampilkan Output di JavaScript</h3>
                
                <h4>Penjelasan</h4>
                <p>JavaScript menyediakan beberapa cara untuk menampilkan output atau hasil dari program kita:</p>
                
                <h5>1. console.log() - Untuk Debugging</h5>
                <p>Menampilkan output di console browser (F12). Paling sering digunakan untuk debugging.</p>
                <pre><code class="language-javascript">console.log("Hello World");
console.log(123);
console.log(true);
console.log("Nilai:", 100);  // Bisa multiple values</code></pre>
                
                <h5>2. alert() - Popup Dialog</h5>
                <p>Menampilkan popup dialog yang harus ditutup user:</p>
                <pre><code class="language-javascript">alert("Selamat datang!");
alert("Nilai Anda: " + 90);</code></pre>
                <div class="alert alert-warning">
                    <strong>⚠️ Perhatian:</strong> alert() menghentikan eksekusi kode sampai user menutup dialog.
                </div>
                
                <h5>3. document.write() - Menulis ke Halaman</h5>
                <p>Menulis langsung ke halaman HTML:</p>
                <pre><code class="language-javascript">document.write("Hello World");
document.write("&lt;h1&gt;Judul Besar&lt;/h1&gt;");
document.write("&lt;p&gt;Ini paragraf&lt;/p&gt;");</code></pre>
                <div class="alert alert-danger">
                    <strong>❌ Tidak Direkomendasikan:</strong> document.write() akan menghapus semua konten halaman jika dipanggil setelah halaman selesai loading.
                </div>
                
                <h5>4. innerHTML - Mengubah Elemen HTML</h5>
                <p>Cara modern dan direkomendasikan untuk mengubah konten halaman:</p>
                <pre><code class="language-html">&lt;div id="output"&gt;&lt;/div&gt;

&lt;script&gt;
document.getElementById("output").innerHTML = "Hello World";
&lt;/script&gt;</code></pre>
                
                <h5>5. console Methods Lainnya</h5>
                <pre><code class="language-javascript">console.log("Normal log");
console.info("Informasi");
console.warn("Peringatan");
console.error("Error!");
console.table([{nama: "Budi", umur: 20}, {nama: "Ani", umur: 22}]);</code></pre>
                
                <h4>Poin Penting</h4>
                <ul>
                    <li>✅ <strong>console.log()</strong> - Untuk debugging (paling sering digunakan)</li>
                    <li>✅ <strong>alert()</strong> - Untuk notifikasi penting</li>
                    <li>✅ <strong>innerHTML</strong> - Untuk mengubah konten halaman</li>
                    <li>❌ <strong>document.write()</strong> - Hindari penggunaan</li>
                </ul>
                
                <h4>Contoh Lengkap</h4>
                <pre><code class="language-html">&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;title&gt;Output JavaScript&lt;/title&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;h1&gt;Demo Output JavaScript&lt;/h1&gt;
    &lt;div id="hasil"&gt;&lt;/div&gt;
    &lt;button onclick="tampilkanHasil()"&gt;Klik Saya&lt;/button&gt;
    
    &lt;script&gt;
        // 1. Output ke console
        console.log("Program dimulai");
        
        // 2. Function untuk menampilkan hasil
        function tampilkanHasil() {
            let nama = "Budi";
            let nilai = 85;
            
            // Output ke console
            console.log("Nama:", nama);
            console.log("Nilai:", nilai);
            
            // Output ke halaman
            document.getElementById("hasil").innerHTML = 
                "&lt;p&gt;Nama: " + nama + "&lt;/p&gt;" +
                "&lt;p&gt;Nilai: " + nilai + "&lt;/p&gt;";
            
            // Alert
            alert("Data berhasil ditampilkan!");
        }
    &lt;/script&gt;
&lt;/body&gt;
&lt;/html&gt;</code></pre>
                
                <h4>Kapan Menggunakan Apa?</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Kapan Digunakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>console.log()</code></td>
                            <td>Debugging, melihat nilai variabel</td>
                        </tr>
                        <tr>
                            <td><code>alert()</code></td>
                            <td>Notifikasi penting yang harus dilihat user</td>
                        </tr>
                        <tr>
                            <td><code>innerHTML</code></td>
                            <td>Menampilkan hasil ke halaman web</td>
                        </tr>
                        <tr>
                            <td><code>document.write()</code></td>
                            <td>Jangan digunakan (deprecated)</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="alert alert-success">
                    <strong>✅ Best Practice:</strong><br>
                    Gunakan <code>console.log()</code> untuk development/debugging, dan <code>innerHTML</code> untuk menampilkan hasil ke user.
                </div>
            </div>',
            'completion' => COMPLETION_TRACKING_MANUAL,
            'estimated_time' => 10,
        ],
    ],
];

return $week1_data;
