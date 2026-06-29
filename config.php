<?php
session_start();

// Data dari hosting (contoh, ganti dengan milik Anda)
$host = 'localhost';      // cek dari hosting, biasanya 'localhost'
$user = 'username_anda';  // ganti
$password = 'password_anda'; // ganti
$dbname = 'nama_db_anda';  // ganti

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
date_default_timezone_set('Asia/Jakarta');
?>