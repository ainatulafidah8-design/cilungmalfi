<?php
include 'config.php';
$conn->query("DELETE FROM users");
$admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
$kasir_hash = password_hash('kasir123', PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, password, role, nama_lengkap) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $user, $pass, $role, $nama);
$user='admin'; $pass=$admin_hash; $role='admin'; $nama='Pemilik Warung'; $stmt->execute();
$user='kasir'; $pass=$kasir_hash; $role='kasir'; $nama='Kasir 1'; $stmt->execute();
echo "✅ Akun berhasil dibuat!<br>Admin: admin / admin123<br>Kasir: kasir / kasir123<br><a href='login.php'>Login</a>";
?>