# MASTER REFERENSI PROJEK SIMKA (AI RULES)

File ini berisi aturan baku, arsitektur, dan konvensi penulisan kode untuk pengerjaan aplikasi SIMKA. Harap patuhi seluruh instruksi di bawah ini tanpa kecuali saat menghasilkan respons atau kode.

## 1. Aturan Dasar & Bahasa
*   **Bahasa Indonesia Mutlak:** Dilarang keras menggunakan istilah bahasa Melayu ("kerana", "bercadang").
*   **Konteks Surat/Keterangan:** Sekolah tidak pernah melakukan kesalahan teknis, hindari frasa "surat keterangan kesalahan".

## 2. Tech Stack & Lingkungan
*   **Stack:** Laravel, HTMX, Alpine.js, Tailwind CSS murni (tanpa DaisyUI).
*   **Dev Environment:** Windows, Laragon, VS Code GUI untuk Git commit.

## 3. Database, Model & Arsitektur Data
*   **UUID:** Wajib menggunakan trait `HasUuids` di semua model baru.
*   **Pola Vault & Keamanan:** Pisahkan data sensitif (seperti NIK, nomor telepon) ke dalam tabel *Vault* terpisah. Gunakan format `_encrypted` untuk penyimpanan dan pola *Blind Index* (`_hash`) jika kolom tersebut perlu dicari (exact match).
*   **Enum Casting:** Gunakan Enum PHP secara konsisten pada `$casts` untuk status, gender, dll.
*   **Normalisasi Relasi:** Dilarang membuat kolom redundan (seperti `nama` atau `nisn` di tabel turunan) jika data bisa ditarik lewat relasi.

## 4. Backend & Controller
*   **Dependency Injection:** Gunakan arsitektur Service (seperti `StudentStatsService`) pada constructor Controller untuk memisahkan *query logic* dari *request handling*.
*   **Transaksi Database:** Wajib menggunakan `DB::transaction` saat menyimpan atau memperbarui data yang melibatkan lebih dari satu tabel (misal: Model utama + Vault).
*   **Respons HTMX:** Untuk *request* via HTMX, jangan gunakan *redirect* klasik kecuali untuk pindah halaman. Gunakan `response()->noContent()->header('HX-Trigger', json_encode([...]))` untuk memicu aksi *client-side* seperti:
    *   `close-modal`
    *   `showAlert` (konfigurasi SweetAlert: icon, title, text)
*   **Ruang Lingkup:** Modifikasi logika wajib di Controller, JANGAN pernah memodifikasi file Trait global.

## 5. Frontend & UI (Tailwind + Alpine + HTMX)
*   **Mobile-First (Tabel vs Card):** Standar *listing* data menggunakan `<table class="hidden lg:block">` untuk *desktop* dan tampilan kartu (*card*) `<div class="lg:hidden">` untuk *mobile*.
*   **Modal & Alpine.js:** Modal baru harus dibungkus `<x-ui.modal>` dengan state Alpine `x-data="{ open: false }"` yang akan beranimasi saat dibuka. Tombol *submit* wajib memiliki efek *loading* (contoh: `@htmx:before-request="saving = true"`).
*   **Standar Tombol & Badge:** 
    *   Konsisten menggunakan palet warna (contoh: `bg-primary`, `bg-emerald-100 text-emerald-700`).
    *   **Tombol Kembali (Back):** Posisi di kanan (rata dengan judul), HANYA ikon (tanpa teks), dengan efek `hover` menjadi hijau.
*   **Integrasi Ikon:** Selalu tambahkan baris `if (typeof lucide !== 'undefined') lucide.createIcons();` di akhir komponen/modal HTMX yang dimuat secara asinkronus.