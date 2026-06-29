
# AGENT.md — Aturan untuk Semua Model AI

Dokumen ini **wajib dibaca dan dipatuhi** oleh setiap AI agent yang bekerja pada proyek **Cilung Malfi**. Tujuan dokumen ini: menjaga konsistensi, keamanan, dan kualitas kode secara berkelanjutan.

---

## 1. Prinsip Dasar

1.1 **Baca AGENT.md dulu** — Sebelum mengedit file apa pun, baca dokumen ini secara utuh.

1.2 **Jangan hapus/mengubah AGENT.md** tanpa persetujuan eksplisit dari user.

1.3 **Gunakan Bahasa Indonesia** untuk komentar, log, dan komunikasi dengan user.

1.4 **Prioritaskan keamanan**: hindari SQL injection, XSS, CSRF. Selalu gunakan prepared statement (`bind_param`) untuk query database, bukan interpolasi string.

1.5 **Ikuti pola kode yang sudah ada**: jangan perkenalkan library/framework baru jika tidak diperlukan.

1.6 **Jangan commit secara otomatis** — tunggu instruksi eksplisit dari user untuk commit.

---

## 2. Workflow Pengembangan & Pemeliharaan

### 2.1 Update-Lokal
Setiap perubahan kode WAJIB mengikuti urutan ini:

```
[EDIT KODE] → [TEST LOKAL] → [PUSH GITHUB] → [AUTO-TEST] → [ANALISA & FIX]
```

### 2.2 Langkah Detail

#### A. EDIT KODE
- Pahami kode yang ada sebelum mengubah.
- Gunakan `bind_param` untuk semua query SQL — jangan pernah menggabungkan variabel langsung ke query.
- Gunakan `htmlspecialchars()` untuk semua output yang berasal dari input user / database.
- Periksa role user (`$_SESSION['role']`) pada setiap halaman yang membutuhkan otorisasi.
- Jika membuat fitur baru, pastikan mendukung **dua role**: `admin` dan `kasir`.

#### B. TEST DI LOCALHOST
Setelah selesai edit, jalankan pengujian:
1. **Test login**: admin/admin123 dan kasir/kasir123.
2. **Test akses per role**: pastikan admin bisa melihat semua data, kasir hanya data sendiri.
3. **Test CRUD**: buat pesanan, lihat pesanan, hapus pesanan (admin), tambah pengeluaran.
4. **Test keuangan**: pastikan angka pemasukan/pengeluaran/saldo sesuai.
5. **Test SQL injection**: coba input karakter khusus di form.
6. **Test XSS**: coba input `<script>` di form, pastikan ter-escape.

Jika semua test lolos → lanjut ke step C.

#### C. PUSH COMMIT KE GITHUB
```
git add <file-file yang relevan>
git commit -m "deskripsi perubahan yang jelas"
git push origin <branch>
```

#### D. AUTO-TEST GITHUB (CI/CD)
Setelah push, pantau hasil GitHub Actions / test otomatis:
- Jika semua test **PASS** → selesai.
- Jika ada test **FAIL** → lanjut ke step E.

#### E. ANALISA KEGAGALAN & TEST ULANG
1. Baca log error dari GitHub.
2. Identifikasi penyebab kegagalan.
3. Perbaiki kode.
4. Ulangi dari **step B** (test lokal).
5. Setelah lolos lokal, ulangi **step C** (push ulang).

### 2.3 Aturan Wajib Setelah Update di Localhost
**SETIAP KALI SELESAI UPDATE DI LOCALHOST, WAJIB MENGIKUTI URUTAN INI:**

1. **Test Lokal Lengkap** — Jalankan seluruh test case di section 2.2.B (login, akses role, CRUD, keuangan, SQL injection, XSS). Tidak boleh melewati langkah ini.
2. **Semua Test Lolos** — Hanya jika SEMUA test lolos 100%, barulah lanjut ke push GitHub.
3. **Push Commit ke GitHub** — Commit dengan pesan yang jelas dan deskriptif.
4. **Auto-Test GitHub (CI/CD)** — Sistem otomatis menjalankan test (GitHub Actions / pipeline lain).
5. **Jika Auto-Test GAGAL** — WAJIB:
   a. Baca log error lengkap dari GitHub Actions
   b. Identifikasi akar masalah (root cause), bukan hanya gejala
   c. Perbaiki kode di lokal
   d. **Kembali ke langkah 1** (Test Lokal Lengkap) — ULANGI SELURUH TEST CASE
   e. Setelah semua lolos lokal, push ulang ke GitHub
   f. Ulangi sampai auto-test **PASS**
6. **DILARANG** push ke GitHub tanpa test lokal lolos terlebih dahulu.
7. **DILARANG** mengabaikan auto-test yang gagal — harus dianalisis dan diperbaiki sampai lolos.

---

## 3. Aturan Kode

### 3.1 Backend (PHP)
- Semua file PHP dimulai dengan `<?php` — tanpa spasi sebelumnya.
- Gunakan `include 'config.php'` untuk koneksi DB dan session.
- Gunakan **prepared statement** (`$conn->prepare` + `bind_param`), **JANGAN** interpolasi string seperti `"WHERE id = $id"`.
- Simpan session: `$_SESSION['user_id']`, `$_SESSION['role']`, `$_SESSION['username']`, `$_SESSION['nama_lengkap']`.
- Cek login di setiap halaman: `if(!isset($_SESSION['user_id'])) header("Location: login.php");`.
- Cek role untuk aksi sensitif (misal hapus pesanan hanya admin).

### 3.2 Frontend (HTML/CSS/JS)
- Gunakan Bootstrap 5.3 via CDN.
- Gunakan Font Awesome 6 via CDN.
- Tampilkan Rupiah dengan format: `Rp number_format($angka, 0, ',', '.')`.
- Beri efek visual berbeda untuk data admin vs kasir (warna biru vs kuning).
- Gunakan `htmlspecialchars()` di setiap tempat yang menampilkan data dari DB.

### 3.3 Database
- Tabel: `users`, `pesanan`, `detail_pesanan`, `pemasukan`, `pengeluaran`.
- Jangan ubah struktur tabel tanpa persetujuan user.
- Semua perubahan skema DB harus disertai file SQL update.
- Gunakan transaction (`begin_transaction`/`commit`/`rollback`) untuk operasi multi-tabel.

### 3.4 Logika Sistem
- Harga per porsi = Rp 1.000 (hardcoded di `proses_pesanan.php`).
- Rasa: Gurih, Pedas, BBQ, Rumput Laut, Acak (random pilih 1 dari 4).
- Saat pesanan dibuat → data masuk ke `pesanan` + `detail_pesanan` + `pemasukan`.
- Role `admin` bisa melihat dan menghapus semua data.
- Role `kasir` hanya bisa melihat data miliknya sendiri (berdasarkan `user_id`), dan hanya bisa menambah pengeluaran (tidak bisa edit/hapus).

### 3.5 UI/UX
- Halaman dashboard admin menampilkan ringkasan keuangan (pemasukan, pengeluaran, saldo) + pemisahan admin/kasir.
- Halaman dashboard kasir hanya menampilkan daftar pesanan miliknya.
- Navigasi konsisten di semua halaman: Dashboard, Pesanan, Keuangan, Logout.
- Tabel menggunakan class Bootstrap `table table-bordered` dengan header `table-dark` atau `table-secondary`.

---

## 4. Keselarasan Backend-Frontend-DB-Logika-UI/UX

### 4.1 Role & Otorisasi
| Role | Lihat Pesanan | Buat Pesanan | Hapus Pesanan | Lihat Keuangan | Tambah Pengeluaran | Edit/Hapus Pengeluaran |
|------|--------------|-------------|--------------|---------------|-------------------|----------------------|
| Admin | Semua | Ya | Ya | Semua data | Ya | Ya |
| Kasir | Milik sendiri | Ya | Tidak | Pengeluaran sendiri saja | Ya | Tidak |

### 4.2 Relasi Database
```
users 1──N pesanan 1──N detail_pesanan
pesanan 1──N pemasukan
users 1──N pengeluaran
```

### 4.3 Aturan Sinkronisasi
- Setiap pesanan WAJIB menghasilkan minimal 1 record di `detail_pesanan` dan 1+ record di `pemasukan` (per rasa).
- Total `pemasukan` harus = SUM(subtotal) dari `detail_pesanan` untuk pesanan terkait.
- `pengeluaran` tidak terkait langsung dengan `pesanan`.
- Halaman keuangan admin menampilkan pemasukan & pengeluaran yang dipisah per role pembuat.
- Halaman keuangan kasir hanya menampilkan pengeluaran yang dibuat kasir tersebut.

---

## 5. Catatan Penting untuk AI Agent

5.1 **Jangan asumsi**: Jika tidak yakin dengan struktur atau logika, baca file terkait dulu.

5.2 **Konsistensi navigasi**: Pastikan menu navigasi sama di semua halaman yang memiliki akses.

5.3 **Error handling**: Gunakan try-catch untuk operasi database. Tampilkan pesan error yang informatif.

5.4 **Jangan simpan kredensial di kode**: Gunakan file `.env` atau minta user mengisi `config.php`.

5.5 **Jika menambah fitur**:
   - Tambahkan menu di navigasi semua halaman yang relevan.
   - Tambahkan query untuk role admin (semua data) dan kasir (data sendiri).
   - Update halaman index dashboard jika perlu menampilkan ringkasan.

5.6 **Jika memperbaiki bug**:
   - Identifikasi akar masalah, bukan hanya gejalanya.
   - Perbaiki di semua tempat yang memiliki pola sama.
   - Test pada kedua role.

---

## 6. Workflow CI/CD (Ringkasan)

```
┌──────────────┐
│  EDIT KODE   │
└──────┬───────┘
       ▼
┌──────────────┐
│ TEST LOKAL   │ ◄── jika gagal, kembali ke EDIT
└──────┬───────┘
       ▼ (lolos)
┌──────────────┐
│ PUSH GITHUB  │
└──────┬───────┘
       ▼
┌──────────────────┐
│ AUTO-TEST (CI/CD)│
└──────┬───────────┘
       │
  ┌────┴────┐
  ▼         ▼
 PASS     FAIL ──► ANALISA ──► EDIT ──► TEST LOKAL ──► PUSH
  │
  ▼
 SELESAI
```
