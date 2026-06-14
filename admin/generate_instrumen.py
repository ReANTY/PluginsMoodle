import os
try:
    import docx
except ImportError:
    print("Mengunduh library dependency... Silakan tunggu.")
    os.system('pip install python-docx')
    import docx

from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import parse_xml, OxmlElement
from docx.oxml.ns import nsdecls, qn

def set_cell_background(cell, fill_hex):
    """Mengatur warna latar belakang tabel (header shading)."""
    tcPr = cell._tc.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>')
    tcPr.append(shd)

def set_cell_margins(cell, top=100, bottom=100, left=150, right=150):
    """Mengatur padding di dalam sel tabel agar teks tidak terlalu mepet garis."""
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = OxmlElement('w:tcMar')
    for m, val in [('top', top), ('bottom', bottom), ('left', left), ('right', right)]:
        node = OxmlElement(f'w:{m}')
        node.set(qn('w:w'), str(val))
        node.set(qn('w:type'), 'dxa')
        tcMar.append(node)
    tcPr.append(tcMar)

def create_document():
    doc = Document()
    
    # Konfigurasi Margin Halaman (Standard Skripsi: Top 3, Bottom 3, Left 4, Right 3 cm)
    sections = doc.sections
    for section in sections:
        section.top_margin = Inches(1.18)
        section.bottom_margin = Inches(1.18)
        section.left_margin = Inches(1.57)
        section.right_margin = Inches(1.18)

    # Set Font Utama ke Calibri
    style = doc.styles['Normal']
    font = style.font
    font.name = 'Calibri'
    font.size = Pt(11)
    font.color.rgb = RGBColor(0, 0, 0)

    # --- JUDUL DOKUMEN ---
    p_title = doc.add_paragraph()
    p_title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_title.paragraph_format.space_after = Pt(2)
    run_title = p_title.add_run("LEMBAR VALIDASI DOSEN AHLI\n(INTEGRASI MEDIA DAN MATERI)")
    run_title.bold = True
    run_title.font.size = Pt(14)

    p_sub = doc.add_paragraph()
    p_sub.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_sub.paragraph_format.space_after = Pt(24)
    run_sub = p_sub.add_run("Pengembangan Plugin Moodle: Latihan Pemrograman Interaktif JavaScript dengan Umpan Balik Cerdas Berbasis Large Language Model (LLM)")
    run_sub.italic = True
    run_sub.font.size = Pt(11)

    # --- A. IDENTITAS VALIDATOR ---
    doc.add_heading("A. IDENTITAS VALIDATOR", level=2).paragraph_format.space_before = Pt(12)
    table_id = doc.add_table(rows=6, cols=2)
    table_id.alignment = WD_TABLE_ALIGNMENT.CENTER
    table_id.autofit = False
    
    id_labels = [
        "Nama Dosen Ahli", "NIDN / NIP", "Jabatan Akademik", 
        "Program Studi / Bidang Keahlian", "Instansi / Universitas", "Tanggal Validasi"
    ]
    
    for i, label in enumerate(id_labels):
        row = table_id.rows[i]
        row.cells[0].text = label
        row.cells[0].paragraphs[0].runs[0].bold = True
        row.cells[1].text = " : ...................................................................................................."
        row.cells[0].width = Inches(2.5)
        row.cells[1].width = Inches(4.0)
        set_cell_margins(row.cells[0])
        set_cell_margins(row.cells[1])

    # --- B. OBJEK YANG DIVALIDASI ---
    doc.add_heading("B. OBJEK YANG DIVALIDASI", level=2).paragraph_format.space_before = Pt(18)
    table_obj = doc.add_table(rows=5, cols=2)
    table_obj.alignment = WD_TABLE_ALIGNMENT.CENTER
    table_obj.autofit = False
    
    obj_data = [
        ("Judul Penelitian", "Pengembangan Plugin Moodle: Latihan Pemrograman Interaktif JavaScript dengan Umpan Balik Cerdas Berbasis Large Language Model (LLM)"),
        ("Nama Produk", "Plugin Moodle mod_aicode (AICode Activity Module) v1.9.0"),
        ("Platform Sistem", "Moodle 5.0+"),
        ("Sasaran Pengguna", "Siswa SMK pada mata pelajaran pemrograman JavaScript dasar"),
        ("Bentuk Produk", "Perangkat lunak pembelajaran yang terintegrasi di dalam LMS")
    ]
    
    for i, (label, desc) in enumerate(obj_data):
        row = table_obj.rows[i]
        row.cells[0].text = label
        row.cells[0].paragraphs[0].runs[0].bold = True
        row.cells[1].text = f" : {desc}"
        row.cells[0].width = Inches(2.5)
        row.cells[1].width = Inches(4.0)
        set_cell_margins(row.cells[0])
        set_cell_margins(row.cells[1])

    # --- C. PETUNJUK PENGISIAN ---
    doc.add_heading("C. PETUNJUK PENGISIAN", level=2).paragraph_format.space_before = Pt(18)
    instructions = [
        "Mohon Bapak/Ibu memberikan penilaian berdasarkan pengamatan langsung terhadap fungsionalitas plugin yang dijalankan di lingkungan Moodle.",
        "Penilaian dilakukan dengan memberikan tanda centang (\u2713) pada satu kolom skor (1-5) yang paling sesuai dengan kriteria: 1 = Sangat Kurang (SK), 2 = Kurang (K), 3 = Cukup (C), 4 = Baik (B), 5 = Sangat Baik (SB).",
        "Validasi ini hanya menilai kesesuaian produk dengan ruang lingkup pengerjaan JavaScript dasar dan tidak menggunakan penjenjangan/level kemampuan kemampuan siswa."
    ]
    for ins in instructions:
        p = doc.add_paragraph(ins, style='List Number')
        p.paragraph_format.space_after = Pt(4)

    # --- D. MATRIKS BUTIR PENILAIAN ---
    doc.add_heading("D. MATRIKS BUTIR PENILAIAN UTAMA", level=2).paragraph_format.space_before = Pt(18)

    aspek_sections = [
        ("1. Aspek Pendahuluan dan Persiapan Sistem\n(Kejelasan instruksi awal, kesiapan aktivitas oleh guru, dan ruang kerja)", [
            "Deskripsi soal pada panel Deskripsi Soal (renderer.php) disajikan jelas dan memandu siswa memahami tugas JavaScript.",
            "Form pembuatan aktivitas guru (mod_form.php) memudahkan guru menyusun soal: nama, deskripsi, starter code, dan test case JSON.",
            "Pemberitahuan 'Kode HTML & CSS sudah disediakan oleh soal' membantu siswa memahami bahwa fokus hanya pada JavaScript.",
            "Template HTML/CSS bersifat read-only dan dapat disembuyenkan via tombol, sehingga siswa tidak bingung area edit.",
            "Starter code JavaScript yang disediakan guru cukup sebagai titik awal yang sesuai untuk latihan JavaScript dasar.",
            "Pengaturan mode aktivitas (Latihan/Ujian) pada form guru dijelaskan cukup jelas untuk memilih konteks pengerjaan.",
            "Petunjuk penggunaan tombol kontrol (Jalankan, Riwayat, Bantuan, Reset, Kirim) dapat dipahami siswa secara intuitif.",
            "Persiapan lingkungan kerja siswa tersusun rapi sehingga siswa siap menulis kode sebelum menekan tombol Jalankan."
        ], "Subtotal Aspek D (Jumlah Skor / 40):"),
        
        ("2. Aspek Fungsionalitas dan Antarmuka Editor\n(Kemampuan mengetik koding, kejelasan tata letak, dan kontrol interaksi)", [
            "Editor JavaScript dilengkapi nomor baris dan syntax highlighting yang mendukung pembacaan kode dasar.",
            "Area pengetikan JavaScript cukup luas, kontras warna memadai, dan nyaman digunakan untuk loop/fungsi/kondisi.",
            "Panel PROBLEMS (gaya VS Code) menampilkan pesan error runtime/syntax dengan indikasi lokasi baris secara akurat.",
            "Panel OUTPUT menampilkan hasil console.log / console.info secara terbaca saat kode dieksekusi.",
            "Panel PREVIEW (iframe sandbox) menampilkan hasil visual DOM/HTML dengan benar untuk manipulasi halaman web.",
            "Tombol Jalankan berfungsi andal mengirim kode ke backend (mod_aicode_run_code) and memproses test case.",
            "Tombol Reset mengembalikan editor ke starter code awal tanpa menghapus struktur atau deskripsi soal.",
            "Fitur Riwayat (drawer sesi, maks. 20 entri) membantu siswa meninjau versi kode JavaScript sebelumnya.",
            "Pemisahan panel (Deskripsi -> Editor -> Kontrol -> Output) membantu alur belajar secara kronologis.",
            "Antarmuka responsif and tetap dapat digunakan pada layar yang lebih kecil secara proporsional.",
            "Mekanisme keamanan (prefilter klien + pemeriksaan server security_checker) tidak mengganggu latihan JavaScript yang sah.",
            "Pada mode Ujian, tombol Bantuan (AI Hint) otomatis disembunyikan and tombol Kirim menjadi penyerahan jawaban akhir."
        ], "Subtotal Aspek E (Jumlah Skor / 60):"),

        ("3. Aspek Evaluasi dan Kecerdasan Umpan Balik LLM\n(Kualitas analisis kode, prompt engineering, anti-spoiler, dan latency)", [
            "Umpan balik LLM dihasilkan dalam Bahasa Indonesia yang kontekstual and mudah dipahami siswa SMK.",
            "Diagnosis AI mampu mengidentifikasi jenis kesalahan JavaScript dasar secara tepat (syntax, runtime, logic).",
            "Umpan balik secara akurat menyebutkan lokasi error (baris, kolom, atau potongan snippet) yang salah.",
            "Petunjuk (hints) bersifat pedagogis: memandu siswa memperbaiki kesalahan tanpa langsung memberikan kunci jawaban.",
            "Bagian Saran Perbaikan (suggested_fix) memberikan bimbingan bernomor, bukan sekadar jawaban instan.",
            "Rekomendasi materi (recommended_materials) sangat relevan dengan konsep JavaScript dasar yang bermasalah (misal: MDN).",
            "Analisis AI baru dipicu setelah siswa menekan Jalankan dan kode gagal, mendorong siswa mencoba mandiri.",
            "Tombol Bantuan hanya aktif menampilkan umpan balik setelah kode pernah dieksekusi untuk mencegah ketergantungan.",
            "Waktu respons umpan balik LLM (termasuk status 'AI is analyzing...') masih dalam batas toleransi pengerjaan kelas.",
            "Mekanisme caching and batas panggilan harian (max_calls_per_day) efektif mencegah penyalahgunaan kuota API.",
            "Pesan kegagalan AI (confidence score rendah atau API terputus) disampaikan jelas tanpa memicu kebingungan.",
            "Umpan balik AI dinonaktifkan secara total pada mode aktivitas Ujian demi menjaga integritas penilaian."
        ], "Subtotal Aspek F (Jumlah Skor / 60):"),

        ("4. Aspek Penutup dan Output Sistem\n(Penyimpanan database, gradebook, activity logs, dan reports)", [
            "Sistem menyimpan percobaan siswa (attempts) ke database dengan aman and terstruktur (aicode_attempts).",
            "Aktivitas penggunaan bantuan AI terekam secara detail (ai_requested_at) untuk menganalisis dependensi siswa.",
            "Hasil analisis umpan balik AI berhasil disimpan (ai_feedback_json) and dapat ditinjau ulang pada report.php.",
            "Tombol Kirim pada mode Ujian sukses menyimpan jawaban akhir siswa serta mengunci akses editor dari perubahan.",
            "Log aktivitas penelitian aicode_activity_log mencatat metadata kejadian penting secara anonim sesuai kode etik.",
            "Integrasi penuh dengan Gradebook Moodle memungkinkan guru memberikan penilaian kuantitatif (rentang skor 0-100).",
            "Fitur activity completion bawaan Moodle berfungsi dengan baik sebagai indikator pemenuhan kelulusan aktivitas.",
            "Dosen/Guru dapat melakukan evaluasi manual atau mengoreksi ketidakakuratan AI via aicode_teacher_overrides.",
            "Output akhir pembelajaran (status penyerahan, total percobaan, nilai) konsisten sebagai bukti rekam akademis.",
            "Keseluruhan alur penutupan sistem terbukti mendukung capaian pembelajaran koding secara berkelanjutan."
        ], "Subtotal Aspek G (Jumlah Skor / 50):")
    ]

    item_counter = 1
    for title, items, subtotal_label in aspek_sections:
        p_asp = doc.add_paragraph()
        p_asp.paragraph_format.space_before = Pt(14)
        p_asp.paragraph_format.space_after = Pt(6)
        run_asp = p_asp.add_run(title)
        run_asp.bold = True
        
        table = doc.add_table(rows=1 + len(items) + 1, cols=7)
        table.alignment = WD_TABLE_ALIGNMENT.CENTER
        table.autofit = False
        
        hdr_cells = table.rows[0].cells
        headers = ["No", "Butir Penilaian Indikator", "1", "2", "3", "4", "5"]
        widths = [Inches(0.4), Inches(4.3), Inches(0.35), Inches(0.35), Inches(0.35), Inches(0.35), Inches(0.35)]
        
        for idx, text in enumerate(headers):
            hdr_cells[idx].text = text
            hdr_cells[idx].paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
            hdr_cells[idx].paragraphs[0].runs[0].bold = True
            set_cell_background(hdr_cells[idx], "F2F2F2")
            set_cell_margins(hdr_cells[idx])
            hdr_cells[idx].width = widths[idx]
            
        for i, item_text in enumerate(items):
            row_cells = table.rows[i + 1].cells
            row_cells[0].text = str(item_counter)
            row_cells[0].paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
            row_cells[1].text = item_text
            
            for idx in range(7):
                row_cells[idx].width = widths[idx]
                set_cell_margins(row_cells[idx])
                if idx >= 2:
                    row_cells[idx].paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
            item_counter += 1
            
        sub_cells = table.rows[-1].cells
        sub_cells[1].text = subtotal_label
        sub_cells[1].paragraphs[0].runs[0].bold = True
        for idx in range(7):
            sub_cells[idx].width = widths[idx]
            set_cell_margins(sub_cells[idx])
            set_cell_background(sub_cells[idx], "FAFAFA")

    # --- E. REKAPITULASI PENILAIAN ---
    doc.add_heading("E. REKAPITULASI PENILAIAN", level=2).paragraph_format.space_before = Pt(20)
    rekap_table = doc.add_table(rows=6, cols=6)
    rekap_table.alignment = WD_TABLE_ALIGNMENT.CENTER
    rekap_table.autofit = False
    
    rekap_hdrs = ["No", "Komponen Aspek Penilaian", "Jumlah Butir", "Skor Maks", "Skor Diperoleh", "Rata-rata Skor"]
    rekap_widths = [Inches(0.4), Inches(3.1), Inches(1.0), Inches(0.8), Inches(1.2), Inches(1.0)]
    
    for idx, text in enumerate(rekap_hdrs):
        cell = rekap_table.rows[0].cells[idx]
        cell.text = text
        cell.paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
        cell.paragraphs[0].runs[0].bold = True
        set_cell_background(cell, "F2F2F2")
        set_cell_margins(cell)
        cell.width = rekap_widths[idx]
        
    rekap_rows = [
        ("1", "D. Pendahuluan & Persiapan Sistem", "8 butir", "40"),
        ("2", "E. Fungsionalitas & Antarmuka Editor", "12 butir", "60"),
        ("3", "F. Evaluasi & Umpan Balik LLM", "12 butir", "60"),
        ("4", "G. Penutup & Output Sistem", "10 butir", "50"),
    ]
    
    for i, data in enumerate(rekap_rows):
        cells = rekap_table.rows[i + 1].cells
        for idx in range(4):
            cells[idx].text = data[idx]
            if idx != 1:
                cells[idx].paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
        for idx in range(6):
            cells[idx].width = rekap_widths[idx]
            set_cell_margins(cells[idx])
            
    tot_cells = rekap_table.rows[-1].cells
    tot_cells[1].text = "TOTAL KESELURUHAN"
    tot_cells[1].paragraphs[0].runs[0].bold = True
    tot_cells[2].text = "42 butir"
    tot_cells[2].paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
    tot_cells[2].paragraphs[0].runs[0].bold = True
    tot_cells[3].text = "210"
    tot_cells[3].paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
    tot_cells[3].paragraphs[0].runs[0].bold = True
    
    for idx in range(6):
        tot_cells[idx].width = rekap_widths[idx]
        set_cell_margins(tot_cells[idx])
        set_cell_background(tot_cells[idx], "F2F2F2")

    p_calc = doc.add_paragraph()
    p_calc.paragraph_format.space_before = Pt(8)
    p_calc.add_run("Rumus Penghitungan Skor Rata-rata Akhir:\n").bold = True
    p_calc.add_run("Rata-rata Keseluruhan = (Total Skor yang Diperoleh) / 42")

    # --- F. KONVERSI NILAI KELAYAKAN ---
    doc.add_heading("F. KONVERSI NILAI KELAYAKAN", level=2).paragraph_format.space_before = Pt(18)
    conv_table = doc.add_table(rows=6, cols=2)
    conv_table.alignment = WD_TABLE_ALIGNMENT.CENTER
    conv_table.autofit = False
    
    conv_hdrs = ["Rentang Skor Rata-rata", "Kategori Kelayakan Sistem & Materi"]
    conv_widths = [Inches(3.0), Inches(4.0)]
    
    for idx, text in enumerate(conv_hdrs):
        cell = conv_table.rows[0].cells[idx]
        cell.text = text
        cell.paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
        cell.paragraphs[0].runs[0].bold = True
        set_cell_background(cell, "F2F2F2")
        set_cell_margins(cell)
        cell.width = conv_widths[idx]
        
    conv_data = [
        ("4,21 \u2013 5,00", "Sangat Baik (Sangat Layak diuji coba)"),
        ("3,41 \u2013 4,20", "Baik (Layak diuji coba)"),
        ("2,61 \u2013 3,40", "Cukup (Cukup Layak diuji coba)"),
        ("1,81 \u2013 2,60", "Kurang (Kurang Layak diuji coba)"),
        ("1,00 \u2013 1,80", "Sangat Kurang (Sangat Tidak Layak)")
    ]
    
    for i, (rentang, kat) in enumerate(conv_data):
        cells = conv_table.rows[i + 1].cells
        cells[0].text = rentang
        cells[0].paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
        cells[1].text = kat
        cells[0].width = conv_widths[0]
        cells[1].width = conv_widths[1]
        set_cell_margins(cells[0])
        set_cell_margins(cells[1])

    p_skor1 = doc.add_paragraph()
    p_skor1.paragraph_format.space_before = Pt(14)
    run_skor1 = p_skor1.add_run("Skor Rata-rata Akhir yang Diperoleh: ............................")
    run_skor1.bold = True

    p_skor2 = doc.add_paragraph()
    run_skor2 = p_skor2.add_run("Kesimpulan Kategori Kelayakan: ........................................................")
    run_skor2.bold = True

    # --- G. KOMENTAR DAN SARAN ---
    doc.add_heading("G. KOMENTAR DAN SARAN PERBAIKAN UMUM", level=2).paragraph_format.space_before = Pt(18)
    kom_table = doc.add_table(rows=6, cols=3)
    kom_table.alignment = WD_TABLE_ALIGNMENT.CENTER
    kom_table.autofit = False
    kom_widths = [Inches(0.4), Inches(2.6), Inches(4.0)]
    
    kom_hdrs = ["No", "Komponen Aspek Evaluasi", "Kolom Catatan / Saran Perbaikan dari Dosen Validator"]
    for idx, text in enumerate(kom_hdrs):
        cell = kom_table.rows[0].cells[idx]
        cell.text = text
        cell.paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
        cell.paragraphs[0].runs[0].bold = True
        set_cell_background(cell, "F2F2F2")
        set_cell_margins(cell)
        cell.width = kom_widths[idx]
        
    kom_rows = [
        ("1", "Kesesuaian materi inti JavaScript Dasar"),
        ("2", "Antarmuka, tata letak, & usability editor koding"),
        ("3", "Kualitas isi & nilai pedagogis umpan balik AI"),
        ("4", "Keandalan pencatatan log data & halaman laporan guru"),
        ("5", "Saran pengembangan lainnya (aspek media/teknis)")
    ]
    for i, (no, asp) in enumerate(kom_rows):
        cells = kom_table.rows[i + 1].cells
        cells[0].text = no
        cells[0].paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
        cells[1].text = asp
        cells[2].text = "\n\n\n"
        for idx in range(3):
            cells[idx].width = kom_widths[idx]
            set_cell_margins(cells[idx])

    # --- H. KESIMPULAN KELAYAKAN AKHIR ---
    doc.add_heading("H. KESIMPULAN KELAYAKAN AKHIR", level=2).paragraph_format.space_before = Pt(18)
    doc.add_paragraph("Berdasarkan hasil penilaian terpadu terhadap keseluruhan 42 butir indikator di atas, maka produk pengembangan plugin Moodle mod_aicode dinyatakan:")
    doc.add_paragraph("[   ] Layak untuk digunakan/diujicobakan tanpa revisi")
    doc.add_paragraph("[   ] Layak untuk digunakan/diujicobakan dengan revisi sesuai saran")
    doc.add_paragraph("[   ] Tidak layak untuk digunakan/diujicobakan")
    
    p_rev = doc.add_paragraph()
    p_rev.paragraph_format.space_before = Pt(6)
    p_rev.add_run("Catatan revisi wajib dari validator (jika ada):\n").italic = True
    p_rev.add_run("__________________________________________________________________________________________________\n\n__________________________________________________________________________________________________")

    # --- I. TANDA TANGAN VALIDATOR ---
    p_sign = doc.add_paragraph()
    p_sign.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    p_sign.paragraph_format.space_before = Pt(30)
    p_sign.add_run("Semarang, .................................... 2026\nDosen Ahli Validator Pembelajaran,\n\n\n\n\n(......................................................................)\n")
    p_sign.add_run("Nama Jelas: ......................................................\nNIP / NIDN: ......................................................").font.size = Pt(10.5)

    p_foot = doc.add_paragraph()
    p_foot.paragraph_format.space_before = Pt(40)
    run_foot = p_foot.add_run("Catatan: Instrumen penilaian ini dikompilasi secara otomatis berdasarkan peninjauan arsitektur kode sumber mod_aicode v1.9.0 (meliputi: mod_form.php, renderer.php, ai_prompt.php, dan install.xml) untuk kesesuaian standar kelayakan skripsi di Indonesia.")
    run_foot.font.size = Pt(8.5)
    run_foot.font.color.rgb = RGBColor(100, 100, 100)
    run_foot.italic = True

    filename = "Lembar_Validasi_Dosen_Ahli_AICode.docx"
    doc.save(filename)
    print(f"\n[SUKSES] File berhasil dibuat: '{os.path.abspath(filename)}'")

if __name__ == "__main__":
    create_document()