<?php
include 'config.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// ========== QUERY MENGGUNAKAN GROUP_CONCAT UNTUK MENGGABUNGKAN RASA ==========
// Satu baris per pesanan (header), rasa dari detail_pesanan digabung jadi satu string
if ($role == 'admin') {
    // Admin melihat semua pesanan
    $query = "SELECT p.id, p.tanggal, p.nama_pembeli, p.total_harga, p.keterangan,
                     GROUP_CONCAT(CONCAT(d.rasa, '(', d.jumlah_porsi, ')') SEPARATOR ', ') AS daftar_rasa
              FROM pesanan p
              LEFT JOIN detail_pesanan d ON p.id = d.pesanan_id
              GROUP BY p.id
              ORDER BY p.tanggal DESC, p.id DESC";
} else {
    // Kasir hanya melihat pesanan yang dibuatnya sendiri
    $query = "SELECT p.id, p.tanggal, p.nama_pembeli, p.total_harga, p.keterangan,
                     GROUP_CONCAT(CONCAT(d.rasa, '(', d.jumlah_porsi, ')') SEPARATOR ', ') AS daftar_rasa
              FROM pesanan p
              LEFT JOIN detail_pesanan d ON p.id = d.pesanan_id
              WHERE p.user_id = $user_id
              GROUP BY p.id
              ORDER BY p.tanggal DESC, p.id DESC";
}
$pesanan = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pesanan - Cilung Malfi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; }
        .navbar { background: #1e2a3a; }
        .card { border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .badge-rasa { background: #ffc107; padding: 4px 10px; border-radius: 20px; margin: 2px; display: inline-block; }
        .table th { background: #2c3e50; color: white; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-store"></i> Cilung Malfi</a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link active" href="pesanan.php">Pesanan</a></li>
            <?php if($role == 'admin') echo '<li class="nav-item"><a class="nav-link" href="keuangan.php">Keuangan</a></li>'; ?>
            <li class="nav-item"><a class="nav-link" href="logout.php">Logout (<?= $_SESSION['nama_lengkap'] ?>)</a></li>
        </ul>
    </div>
</nav>

<div class="container mt-4">
    <!-- Form Tambah Pesanan (Multi Rasa) -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Tambah Pesanan Baru (Multi Rasa)</div>
        <div class="card-body">
            <form action="proses_pesanan.php" method="POST">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" name="nama_pembeli" class="form-control" placeholder="Nama Pembeli" required>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="keterangan" class="form-control" placeholder="Keterangan (opsional)">
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="tambahRasa" class="btn btn-secondary">+ Tambah Rasa</button>
                    </div>
                </div>
                <div id="daftarRasa">
                    <div class="row row-rasa mb-2">
                        <div class="col-md-4">
                            <select name="rasa[]" class="form-select" required>
                                <option value="Gurih">Gurih</option>
                                <option value="Pedas">Pedas</option>
                                <option value="BBQ">BBQ</option>
                                <option value="Rumput Laut">Rumput Laut</option>
                                <option value="Acak">Acak (Random)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" name="jumlah_porsi[]" class="form-control" placeholder="Jumlah porsi" min="1" required>
                        </div>
                        <div class="col-md-1">
                            <i class="fas fa-trash-alt remove-row" style="cursor:pointer; color:red;"></i>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-success mt-2">Simpan Pesanan</button>
            </form>
        </div>
    </div>

    <!-- Daftar Pesanan (Satu Baris per Nama) -->
    <div class="card">
        <div class="card-header bg-info text-white">Daftar Pesanan (1 baris = 1 pembeli)</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Pembeli</th>
                            <th>Rasa & Jumlah</th>   <!-- Kolom ini berisi gabungan semua rasa -->
                            <th>Total Harga</th>
                            <th>Keterangan</th>
                            <?php if($role == 'admin') echo '<th>Aksi</th>'; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($pesanan->num_rows == 0): ?>
                            <tr><td colspan="<?= ($role=='admin')?6:5 ?>" class="text-center">Belum ada pesanan</td></tr>
                        <?php else: ?>
                            <?php while($row = $pesanan->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($row['nama_pembeli']) ?></td>
                                <!-- Di sini langsung tampilkan daftar_rasa yang sudah digabung -->
                                <td><?= $row['daftar_rasa'] ?></td>
                                <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars($row['keterangan']) ?: '-' ?></td>
                                <?php if($role == 'admin'): ?>
                                <td><a href="hapus_pesanan.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus pesanan ini?')">Hapus</a></td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Menambah baris rasa baru
    document.getElementById('tambahRasa').onclick = function(){
        let container = document.getElementById('daftarRasa');
        let original = document.querySelector('.row-rasa');
        let clone = original.cloneNode(true);
        clone.querySelector('input').value = '';
        clone.querySelector('select').selectedIndex = 0;
        clone.querySelector('.remove-row').onclick = function() { clone.remove(); };
        container.appendChild(clone);
    };
    // Event untuk hapus baris pertama
    document.querySelectorAll('.remove-row').forEach(btn => {
        btn.onclick = function() { this.closest('.row-rasa').remove(); };
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>