# Cilung Malfi

Aplikasi manajemen pesanan cilung (cilok + indomie) berbasis web dengan PHP & MySQL.

## Fitur

- **Multi-role**: Admin & Kasir
- **Manajemen Pesanan**: Buat, proses, dan hapus pesanan
- **Detail Pesanan**: Multi-rasa per pesanan (Gurih, BBQ, Rumput Laut, dll)
- **Keuangan**: Catat pemasukan & pengeluaran, lihat laba rugi
- **Laporan**: Rekap harian dan tanggal custom

## Teknologi

- PHP 8.0+
- MySQL / MariaDB
- Bootstrap (UI)
- Font Awesome (Icons)

## Instalasi

1. Clone repositori ini
2. Import `db_cilung.sql` ke database MySQL
3. Sesuaikan koneksi database di `config.php`
4. Jalankan di web server (XAMPP/Laragon dll)

## Struktur Database

- `users` - Data pengguna (admin/kasir)
- `pesanan` - Data pesanan
- `detail_pesanan` - Item per pesanan (rasa & porsi)
- `pemasukan` - Catatan pemasukan
- `pengeluaran` - Catatan pengeluaran
