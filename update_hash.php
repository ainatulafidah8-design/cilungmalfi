<?php
include 'config.php';

// Ambil semua user
$users = $conn->query("SELECT id, password FROM users");

while($row = $users->fetch_assoc()) {
    // Jika password masih belum di-hash (panjangnya < 60 atau tidak diawali $2y$)
    if(strlen($row['password']) < 60 || substr($row['password'], 0, 3) != '$2y') {
        $hash = password_hash($row['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $row['id']);
        $stmt->execute();
        echo "User ID {$row['id']} diupdate.<br>";
    }
}
echo "Selesai. Sekarang password sudah dalam bentuk hash.<br>";
echo '<a href="login.php">Kembali ke login</a>';
?>