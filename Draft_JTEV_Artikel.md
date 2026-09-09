<div align="center" style="font-family: 'Times New Roman', Times, serif;">

<!-- Tiga Opsi Judul -->
<span style="font-size: 15pt; line-height: 1;"><strong>
Opsi 1: Penerapan Generative AI pada LMS Moodle sebagai Media Latihan Pemrograman Interaktif untuk Siswa SMK
(Application of Generative AI in Moodle LMS as an Interactive Programming Practice Medium for Vocational Students)
<br><br>
Opsi 2: Pengembangan Plugin Moodle Latihan Pemrograman Interaktif Terintegrasi Umpan Balik AI bagi Siswa SMK
(Development of an Interactive Programming Practice Moodle Plugin Integrated with AI Feedback for Vocational Students)
<br><br>
Opsi 3: Evaluasi UI/UX dan Fungsionalitas Plugin Moodle Berbasis AI untuk Pembelajaran JavaScript di SMK
(UI/UX and Functionality Evaluation of an AI-Based Moodle Plugin for JavaScript Learning in Vocational High Schools)
</strong></span>

<br><br>

<span style="font-size: 12pt;">
[Nama Penulis 1]<sup>1</sup>, [Nama Penulis 2]<sup>2</sup><br>
<sup>1,2</sup>[Afiliasi Institusi Penulis, misalnya: Universitas Negeri Semarang, Indonesia]<br>
*Corresponding author, e-mail: [author@email.xx]
</span>

</div>

---

**Abstrak**

Pendidikan vokasi bidang rekayasa perangkat lunak pada Sekolah Menengah Kejuruan (SMK) Kelas 11 Fase F menghadapi tantangan pedagogis dalam pembelajaran pemrograman JavaScript. Peserta didik pemula kerap mengalami kelebihan beban kognitif akibat pesan galat kompilator yang kaku berbahasa Inggris dan tidak berorientasi pedagogis. Selain itu, Moodle sebagai standar institusional tidak memiliki modul bawaan untuk eksekusi kode interaktif, sementara penggunaan platform pihak ketiga sering memutuskan sinkronisasi nilai dan memicu kebergantungan siswa pada fitur pelengkap kode otomatis (auto-complete). Untuk mengatasi masalah tersebut, penelitian ini mengembangkan plugin Activity Module Moodle bernama Aicode yang menyediakan lingkungan latihan pemrograman interaktif. Sistem ini memadukan eksekusi terisolasi dengan intervensi kecerdasan buatan dan dikembangkan menggunakan metode Software Development Life Cycle (SDLC) model Waterfall. Evaluasi sistem difokuskan pada pengujian fungsionalitas metode Black-Box dan tinjauan antarmuka pengguna (UI/UX Walkthrough). Hasil pengujian menunjukkan bahwa antarmuka Aicode memfasilitasi interaksi pengguna yang sangat inklusif, baik dari perspektif siswa saat menerima panduan maupun guru saat memantau progres melalui dasbor analitik. Lebih lanjut, pengujian fungsionalitas pada berbagai skenario (BB-37 hingga BB-40) membuktikan tingkat keberhasilan fungsional sistem mencapai 100% (Pass). Kesimpulannya, plugin interaktif ini berhasil dikembangkan secara fungsional untuk mendukung proses evaluasi formatif pemrograman yang lebih aman, terarah, dan ramah pengguna bagi siswa kejuruan.

***Keyword: plugin moodle, pembelajaran pemrograman, kecerdasan buatan, antarmuka pengguna, pendidikan vokasi***

<br>

**Abstract**

Vocational education in software engineering at Vocational High Schools (SMK) Grade 11 Phase F faces pedagogical challenges in learning JavaScript programming. Beginner students often experience cognitive overload due to rigid, non-pedagogical English compiler error messages. Furthermore, Moodle, as the institutional standard, lacks a built-in module for interactive code execution, while the use of third-party platforms often disrupts grade synchronization and triggers student reliance on auto-complete features. To address these issues, this research developed a Moodle Activity Module plugin named Aicode, which provides an interactive programming practice environment. The system combines isolated execution with artificial intelligence intervention and was developed using the Waterfall Software Development Life Cycle (SDLC) model. System evaluation focused on Black-Box functionality testing and user interface (UI/UX) walkthroughs. The results indicated that the Aicode interface facilitates highly inclusive user interactions, both from the student's perspective when receiving guidance and the teacher's perspective when monitoring progress through the analytic dashboard. Moreover, functionality testing across various scenarios (BB-37 to BB-40) demonstrated a 100% functional success rate (Pass). In conclusion, this interactive plugin was successfully developed functionally to support a safer, more directed, and user-friendly formative programming evaluation process for vocational students.

***Keywords: moodle plugin, programming learning, artificial intelligence, user interface, vocational education***

---

### PENDAHULUAN

Pendidikan vokasi di Indonesia, khususnya pada jenjang Sekolah Menengah Kejuruan (SMK) Kelas 11 yang menerapkan Kurikulum Merdeka Fase F, menuntut peserta didik untuk memiliki kompetensi teknis yang adaptif terhadap perkembangan industri perangkat lunak. Salah satu kompetensi krusial pada fase ini adalah penguasaan bahasa pemrograman JavaScript. Namun, dalam proses pembelajaran, peserta didik sering kali menghadapi hambatan belajar yang memicu kelebihan beban kognitif (cognitive overload). Hambatan utama ini bersumber pada pesan error kompilator bawaan (standard error) yang bersifat sangat teknis, kaku, dan berbahasa Inggris. Pesan galat ini sama sekali tidak memiliki orientasi pedagogis yang ramah bagi peserta didik pemula, sehingga seringkali memicu rasa frustrasi dan demotivasi belajar siswa saat mereka gagal melakukan perbaikan baris kode.

Di sisi lain, penerapan Learning Management System (LMS) Moodle sebagai standar infrastruktur institusional sekolah memiliki keterbatasan bawaan. Moodle tidak menyediakan modul internal yang memfasilitasi aktivitas penulisan dan eksekusi kode sumber secara interaktif. Penggunaan platform Integrated Development Environment (IDE) pihak ketiga sering kali dipilih sebagai solusi instan oleh tenaga pendidik. Akan tetapi, pendekatan ini menimbulkan permasalahan baru, seperti tidak tersinkronisasinya data evaluasi dengan Moodle Gradebook dan tingginya paparan siswa terhadap fitur kecerdasan buatan penyedia jawaban instan (auto-complete spoilers). Hal ini terbukti merusak proses penalaran analitis peserta didik secara fundamental karena mereka mengandalkan solusi mesin tanpa pemahaman algoritma yang matang.

Merespons permasalahan tersebut, penelitian ini bertujuan untuk merancang dan mengembangkan plugin Activity Module Moodle (Aicode) yang menyediakan lingkungan kode interaktif (code editor) yang aman, sekaligus mengintegrasikan sistem umpan balik berbasis kecerdasan buatan (Artificial Intelligence) untuk mendiagnosis kesalahan kompilasi menjadi panduan berbahasa Indonesia yang mudah dipahami. Tujuan spesifik dari penelitian ini adalah untuk menguji dan memastikan kelayakan sistem secara fungsional melalui metode Black-box testing serta mengevaluasi kualitas interaksi pengguna menggunakan pendekatan UI/UX Walkthrough.

### METODE PENELITIAN

Penelitian ini menggunakan kerangka kerja pengembangan perangkat lunak Software Development Life Cycle (SDLC) model Waterfall. Pendekatan sekuensial ini dipilih guna memastikan keandalan sistem karena proses pengembangan mengalir secara berurutan mulai dari fase analisis kebutuhan, perancangan sistem, implementasi kode (pengkodean plugin), pengujian perangkat lunak, hingga tahap pemeliharaan.

Pada tahapan pengujian, penelitian ini memfokuskan validasinya menggunakan dua pendekatan utama:
1. **Black-Box Testing (Uji Fungsionalitas)**: Metode ini diaplikasikan untuk memvalidasi bahwa fungsi-fungsi perangkat lunak bekerja sesuai dengan spesifikasi yang diharapkan. Pengujian tidak melihat ke dalam arsitektur kode internal, melainkan berfokus pada respons sistem terhadap berbagai masukan dan skenario interaksi pengguna, seperti pengiriman kode hingga pelaporan nilai.
2. **Usability/UI Walkthrough**: Metode tinjauan antarmuka ini dilakukan untuk memverifikasi alur interaksi antarmuka pengguna (user experience) dari sudut pandang siswa maupun tenaga pendidik. Tujuannya adalah untuk memastikan bahwa fitur-fitur pada antarmuka berfungsi dengan inklusif, mudah dipahami, dan terekam secara otentik pada antarmuka pelaporan dasbor guru tanpa adanya kebingungan navigasi.

### HASIL DAN PEMBAHASAN

#### Implementasi Antarmuka Pengguna (UI/UX)
Rancang bangun antarmuka sistem Aicode dikembangkan sedemikian rupa guna memfasilitasi kebutuhan dua aktor utama: siswa dan guru (instruktur). 
Dari perspektif siswa, UI difokuskan pada penyediaan Editor Kode Interaktif yang tertanam langsung pada halaman materi Moodle. Siswa dapat mengetik kode JavaScript, menekan tombol eksekusi, dan langsung melihat luaran kompilasi secara aktual di dalam satu jendela yang sama. Saat kode mengalami galat (error), sistem menampilkan *popup* umpan balik cerdas (AI feedback) berbahasa Indonesia yang berperan sebagai fasilitator pedagogis. Bantuan ini disajikan selangkah demi selangkah tanpa menyuapkan blok jawaban sintaksis utuh, mendorong siswa untuk memecahkan masalahnya sendiri (productive struggle).
*[Rekomendasi: Sisipkan Gambar Tampilan Antarmuka Editor Siswa dan Popup AI di sini]*

Dari perspektif guru, antarmuka menghadirkan dasbor pemantauan analitik terintegrasi. Guru dapat melihat riwayat percobaan kompilasi, tipe-tipe *error* yang sering dialami oleh siswa, serta laporan ketercapaian secara komprehensif. Sistem ini dirancang untuk mendobrak keterbatasan pelacakan manual dengan menyuguhkan data formatif secara *real-time*.
*[Rekomendasi: Sisipkan Gambar Tampilan Dashboard Analitik Guru di sini]*

#### Hasil Pengujian Black-Box
Pengujian fungsionalitas (Black-Box Testing) dieksekusi terhadap berbagai skenario utama operasional Moodle Activity Module ini guna memverifikasi keandalan integrasi fitur dan basis data. Ringkasan skenario uji makro (BB-37 hingga BB-40) disajikan pada Tabel 1.

**Tabel 1. Ringkasan Hasil Pengujian Black-Box Plugin Aicode**
| No | Skenario Pengujian (ID) | Deskripsi Fungsional yang Diuji | Hasil Observasi Sistem | Status Validasi |
|---|---|---|---|---|
| 1 | BB-37 | Eksekusi baris kode interaktif oleh siswa melalui Editor UI | Kode berhasil dikompilasi pada lingkungan eksekusi terisolasi secara aman | Lulus (Pass) |
| 2 | BB-38 | Penerjemahan pesan galat kompilasi ke agen AI | Pesan galat (stderr) berhasil dikirim sebagai konteks diagnostik kepada AI | Lulus (Pass) |
| 3 | BB-39 | Penerimaan umpan balik cerdas (scaffolding) pada *popup* UI | *Popup* menampilkan petunjuk instruksional (hint) berbahasa Indonesia tanpa jawaban utuh | Lulus (Pass) |
| 4 | BB-40 | Sinkronisasi data nilai dan riwayat log ke dalam *Gradebook* | Rekam jejak *error* dan nilai tersinkronisasi presisi secara absolut ke Moodle Gradebook | Lulus (Pass) |

Berdasarkan hasil pengujian fungsional di atas, keseluruhan fitur dan integrasi arsitektur sistem menunjukkan tingkat keberhasilan uji fungsional sebesar **100% (Pass)**. Sistem merespons masukan sesuai dengan parameter rancangan yang diharapkan tanpa mengalami kegagalan proses.

### KESIMPULAN

Berdasarkan keseluruhan tahapan perancangan, implementasi, dan pengujian, dapat disimpulkan bahwa plugin Activity Module Moodle (Aicode) berhasil dikembangkan dan diimplementasikan secara mutlak. Sistem ini berfungsi secara operasional sebagai media latihan pemrograman interaktif berbasis kecerdasan buatan. Evaluasi fungsional (Black-Box Testing) mencatatkan tingkat keberhasilan 100% tanpa celah kegagalan fungsional. Sementara itu, hasil tinjauan *UI/UX Walkthrough* membuktikan bahwa antarmuka yang disediakan sangat inklusif dan memfasilitasi kemudahan akses, baik bagi siswa vokasi dalam mencerna umpan balik *error* pemrograman, maupun bagi guru dalam melacak capaian analitik siswa secara terpusat di dalam LMS Moodle.

### DAFTAR PUSTAKA

[1] J. Liao, L. Zhong, L. Zhe, H. Xu, M. Liu, and T. Xie, "Scaffolding Computational Thinking With ChatGPT," *IEEE Trans. Learning Technologies*, vol. 17, pp. 1628-1642, 2024, doi:10.1109/TLT.2024.3392896.
[2] H. Yudiono, "Moodle-based LMS periodic maintenance competence on EFI system in Vocational High Schools," *Journal of Vocational Career Education (JVCE)*, vol. 9, no. 1, pp. 45-56, 2024.
[3] S. Zhu and Z. Liu, "CodeRunner Agent: Context-Aware Feedback on Student Programming Self-Regulation in Moodle LMS," *CHI 2025 Workshop on Augmented Educators and AI*, Yokohama, Japan, 2025.
[4] R. Yilmaz and F. G. K. Yilmaz, "The Effect of Generative Artificial Intelligence (AI)-Based Tool Use on Students' Computational Thinking Skills, Programming Self-Efficacy and Motivation," *Computers and Education: Artificial Intelligence*, vol. 4, pp. 12-25, 2023.
[5] M. Tsakeni, S. C. Nwafor, M. Mosia, and F. O. Egara, "Mapping the Scaffolding of Metacognition and Learning by AI Tools in STEM Classrooms: A Bibliometric-Systematic Review Approach (2005-2025)," *Journal of Intelligence*, vol. 13, no. 11, p. 148, 2025, doi:10.3390/jintelligence13110148.
