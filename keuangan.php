<?php
include 'config.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Proses tambah pengeluaran untuk semua role (kasir dan admin)
if($_POST && isset($_POST['tambah_pengeluaran'])) {
    $tanggal = $_POST['tanggal'];
    $keperluan = $_POST['keperluan'];
    $jumlah = (float)$_POST['jumlah'];
    $keterangan = $_POST['keterangan'] ?? '';
    $stmt = $conn->prepare("INSERT INTO pengeluaran (tanggal, user_id, keperluan, jumlah, keterangan) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisds", $tanggal, $user_id, $keperluan, $jumlah, $keterangan);
    $stmt->execute();
    header("Location: keuangan.php");
    exit;
}

// Admin bisa edit/hapus semua pengeluaran, kasir tidak
if($role == 'admin') {
    if(isset($_GET['hapus_pengeluaran'])) {
        $id = (int)$_GET['hapus_pengeluaran'];
        $conn->query("DELETE FROM pengeluaran WHERE id = $id");
        header("Location: keuangan.php");
        exit;
    }
    if(isset($_POST['edit_pengeluaran'])) {
        $id = (int)$_POST['id'];
        $tanggal = $_POST['tanggal'];
        $keperluan = $_POST['keperluan'];
        $jumlah = (float)$_POST['jumlah'];
        $keterangan = $_POST['keterangan'];
        $stmt = $conn->prepare("UPDATE pengeluaran SET tanggal=?, keperluan=?, jumlah=?, keterangan=? WHERE id=?");
        $stmt->bind_param("ssdsi", $tanggal, $keperluan, $jumlah, $keterangan, $id);
        $stmt->execute();
        header("Location: keuangan.php");
        exit;
    }
}

// ========== UNTUK ADMIN: ambil data terpisah ==========
if($role == 'admin') {
    // Pemasukan dari admin
    $pemasukanAdmin = $conn->query("SELECT pem.tanggal, p.nama_pembeli, GROUP_CONCAT(CONCAT(d.rasa, '(', d.jumlah_porsi, ')') SEPARATOR ', ') AS daftar_rasa, SUM(pem.jumlah) AS total, pem.keterangan FROM pemasukan pem JOIN pesanan p ON pem.pesanan_id = p.id JOIN detail_pesanan d ON p.id = d.pesanan_id JOIN users u ON p.user_id = u.id WHERE u.role = 'admin' GROUP BY pem.pesanan_id ORDER BY pem.tanggal DESC, p.id DESC");
    $totalPemasukanAdmin = $conn->query("SELECT SUM(pem.jumlah) as total FROM pemasukan pem JOIN pesanan p ON pem.pesanan_id = p.id JOIN users u ON p.user_id = u.id WHERE u.role = 'admin'")->fetch_assoc()['total'] ?? 0;
    // Pemasukan dari kasir
    $pemasukanKasir = $conn->query("SELECT pem.tanggal, p.nama_pembeli, GROUP_CONCAT(CONCAT(d.rasa, '(', d.jumlah_porsi, ')') SEPARATOR ', ') AS daftar_rasa, SUM(pem.jumlah) AS total, pem.keterangan FROM pemasukan pem JOIN pesanan p ON pem.pesanan_id = p.id JOIN detail_pesanan d ON p.id = d.pesanan_id JOIN users u ON p.user_id = u.id WHERE u.role = 'kasir' GROUP BY pem.pesanan_id ORDER BY pem.tanggal DESC, p.id DESC");
    $totalPemasukanKasir = $conn->query("SELECT SUM(pem.jumlah) as total FROM pemasukan pem JOIN pesanan p ON pem.pesanan_id = p.id JOIN users u ON p.user_id = u.id WHERE u.role = 'kasir'")->fetch_assoc()['total'] ?? 0;
    // Pengeluaran admin
    $pengeluaranAdmin = $conn->query("SELECT p.*, u.username FROM pengeluaran p JOIN users u ON p.user_id = u.id WHERE u.role = 'admin' ORDER BY p.tanggal DESC, p.id DESC");
    $totalPengeluaranAdmin = $conn->query("SELECT SUM(jumlah) as total FROM pengeluaran WHERE user_id IN (SELECT id FROM users WHERE role='admin')")->fetch_assoc()['total'] ?? 0;
    // Pengeluaran kasir
    $pengeluaranKasir = $conn->query("SELECT p.*, u.username FROM pengeluaran p JOIN users u ON p.user_id = u.id WHERE u.role = 'kasir' ORDER BY p.tanggal DESC, p.id DESC");
    $totalPengeluaranKasir = $conn->query("SELECT SUM(jumlah) as total FROM pengeluaran WHERE user_id IN (SELECT id FROM users WHERE role='kasir')")->fetch_assoc()['total'] ?? 0;
    // Total
    $totalPemasukan = $totalPemasukanAdmin + $totalPemasukanKasir;
    $totalPengeluaran = $totalPengeluaranAdmin + $totalPengeluaranKasir;
    $saldo = $totalPemasukan - $totalPengeluaran;
}

// ========== UNTUK KASIR: ambil pengeluaran sendiri ==========
else {
    $pengeluaranSaya = $conn->query("SELECT * FROM pengeluaran WHERE user_id = $user_id ORDER BY tanggal DESC, id DESC");
    $totalPengeluaranSaya = $conn->query("SELECT SUM(jumlah) as total FROM pengeluaran WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keuangan - Cilung Malfi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; }
        .navbar { background: #1e2a3a; }
        .card { border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .badge-rasa { background: #ffc107; padding: 4px 10px; border-radius: 20px; margin: 2px; display: inline-block; }
        .table th { background: #2c3e50; color: white; }
        .section-title { border-left: 5px solid; padding-left: 15px; margin: 20px 0; }
        .admin-title { border-left-color: #0d6efd; }
        .kasir-title { border-left-color: #ffc107; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Cilung Malfi</a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="pesanan.php">Pesanan</a></li>
            <li class="nav-item"><a class="nav-link active" href="keuangan.php">Keuangan</a></li>
            <li class="nav-item"><a class="nav-link" href="logout.php">Logout (<?= $_SESSION['nama_lengkap'] ?>)</a></li>
        </ul>
    </div>
</nav>

<div class="container mt-4">
    <?php if($role == 'admin'): ?>
        <!-- ================== TAMPILAN ADMIN ================== -->
        <h2 class="mb-4"><i class="fas fa-chart-line"></i> Laporan Keuangan (Pisah Admin & Kasir)</h2>

        <!-- Ringkasan total -->
        <div class="row mb-4">
            <div class="col-md-4"><div class="card bg-primary text-white p-3"><h5>Total Pemasukan</h5><h3>Rp <?= number_format($totalPemasukan,0,',','.') ?></h3><small>Admin: Rp <?= number_format($totalPemasukanAdmin,0,',','.') ?> | Kasir: Rp <?= number_format($totalPemasukanKasir,0,',','.') ?></small></div></div>
            <div class="col-md-4"><div class="card bg-danger text-white p-3"><h5>Total Pengeluaran</h5><h3>Rp <?= number_format($totalPengeluaran,0,',','.') ?></h3><small>Admin: Rp <?= number_format($totalPengeluaranAdmin,0,',','.') ?> | Kasir: Rp <?= number_format($totalPengeluaranKasir,0,',','.') ?></small></div></div>
            <div class="col-md-4"><div class="card bg-success text-white p-3"><h5>Saldo</h5><h3>Rp <?= number_format($saldo,0,',','.') ?></h3></div></div>
        </div>

        <!-- Form tambah pengeluaran untuk admin -->
        <div class="card">
            <div class="card-header bg-danger text-white">Tambah Pengeluaran (Sebagai Admin)</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="tambah_pengeluaran" value="1">
                    <div class="row">
                        <div class="col-md-3"><input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-md-3"><input type="text" name="keperluan" class="form-control" placeholder="Keperluan" required></div>
                        <div class="col-md-2"><input type="number" name="jumlah" class="form-control" placeholder="Jumlah (Rp)" required></div>
                        <div class="col-md-3"><input type="text" name="keterangan" class="form-control" placeholder="Keterangan"></div>
                        <div class="col-md-1"><button type="submit" class="btn btn-primary">Simpan</button></div>
                    </div>
                </form>
            </div>
        </div>

        <!-- PEMASUKAN DARI ADMIN -->
        <div class="section-title admin-title mt-4"><h4><i class="fas fa-user-tie text-primary"></i> Pemasukan dari Admin (Owner)</h4></div>
        <div class="card"><div class="card-body p-0"><div class="table-responsive" style="max-height: 300px;"><table class="table table-sm table-bordered mb-0"><thead class="table-secondary"><tr><th>Tanggal</th><th>Pembeli</th><th>Rasa & Jumlah</th><th>Total</th><th>Keterangan</th></tr></thead><tbody><?php while($row=$pemasukanAdmin->fetch_assoc()): ?><td><?= $row['tanggal'] ?></td><td><?= htmlspecialchars($row['nama_pembeli']) ?></td><td><?= $row['daftar_rasa'] ?><td><td class="text-success">Rp <?= number_format($row['total'],0,',','.') ?></td><td><?= htmlspecialchars($row['keterangan']) ?></td><?php endwhile; if($pemasukanAdmin->num_rows==0) echo '<td colspan="5">Belum ada'; ?></tbody></table></div></div></div>

        <!-- PEMASUKAN DARI KASIR -->
        <div class="section-title kasir-title"><h4><i class="fas fa-user-friends text-warning"></i> Pemasukan dari Kasir</h4></div>
        <div class="card"><div class="card-body p-0"><div class="table-responsive" style="max-height: 300px;"><table class="table table-sm table-bordered mb-0"><thead class="table-secondary"><tr><th>Tanggal</th><th>Pembeli</th><th>Rasa & Jumlah</th><th>Total</th><th>Keterangan</th></tr></thead><tbody><?php while($row=$pemasukanKasir->fetch_assoc()): ?><td><?= $row['tanggal'] ?></td><td><?= htmlspecialchars($row['nama_pembeli']) ?></td><td><?= $row['daftar_rasa'] ?><td><td class="text-success">Rp <?= number_format($row['total'],0,',','.') ?></td><td><?= htmlspecialchars($row['keterangan']) ?></td><?php endwhile; if($pemasukanKasir->num_rows==0) echo '<td colspan="5">Belum ada'; ?></tbody></table></div></div></div>

        <!-- PENGELUARAN DARI ADMIN -->
        <div class="section-title admin-title"><h4><i class="fas fa-user-tie text-primary"></i> Pengeluaran oleh Admin</h4></div>
        <div class="card"><div class="card-body p-0"><div class="table-responsive" style="max-height: 300px;"><table class="table table-sm table-bordered mb-0"><thead class="table-secondary"><tr><th>Tanggal</th><th>Keperluan</th><th>Jumlah</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody><?php while($row=$pengeluaranAdmin->fetch_assoc()): ?><tr><td><?= $row['tanggal'] ?></td><td><?= $row['keperluan'] ?></td><td class="text-danger">Rp <?= number_format($row['jumlah'],0,',','.') ?></td><td><?= $row['keterangan'] ?></td><td><a href="keuangan.php?hapus_pengeluaran=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')">Hapus</a> <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editAdmin<?= $row['id'] ?>">Edit</button><div class="modal fade" id="editAdmin<?= $row['id'] ?>"><div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-body"><input type="hidden" name="id" value="<?= $row['id'] ?>"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" value="<?= $row['tanggal'] ?>"><label>Keperluan</label><input type="text" name="keperluan" class="form-control" value="<?= $row['keperluan'] ?>"><label>Jumlah</label><input type="number" name="jumlah" class="form-control" value="<?= $row['jumlah'] ?>"><label>Keterangan</label><input type="text" name="keterangan" class="form-control" value="<?= $row['keterangan'] ?>"></div><div class="modal-footer"><button type="submit" name="edit_pengeluaran" class="btn btn-primary">Update</button></div></form></div></div></div></td></tr><?php endwhile; if($pengeluaranAdmin->num_rows==0) echo '<tr><td colspan="5">Belum ada</td></tr>'; ?></tbody></table></div></div></div>

        <!-- PENGELUARAN DARI KASIR -->
        <div class="section-title kasir-title"><h4><i class="fas fa-user-friends text-warning"></i> Pengeluaran oleh Kasir</h4></div>
        <div class="card"><div class="card-body p-0"><div class="table-responsive" style="max-height: 300px;"><table class="table table-sm table-bordered mb-0"><thead class="table-secondary"><tr><th>Tanggal</th><th>Keperluan</th><th>Jumlah</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody><?php while($row=$pengeluaranKasir->fetch_assoc()): ?><tr><td><?= $row['tanggal'] ?></td><td><?= $row['keperluan'] ?></td><td class="text-danger">Rp <?= number_format($row['jumlah'],0,',','.') ?></td><td><?= $row['keterangan'] ?></td><td><a href="keuangan.php?hapus_pengeluaran=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')">Hapus</a> <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editKasir<?= $row['id'] ?>">Edit</button><div class="modal fade" id="editKasir<?= $row['id'] ?>"><div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-body"><input type="hidden" name="id" value="<?= $row['id'] ?>"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" value="<?= $row['tanggal'] ?>"><label>Keperluan</label><input type="text" name="keperluan" class="form-control" value="<?= $row['keperluan'] ?>"><label>Jumlah</label><input type="number" name="jumlah" class="form-control" value="<?= $row['jumlah'] ?>"><label>Keterangan</label><input type="text" name="keterangan" class="form-control" value="<?= $row['keterangan'] ?>"></div><div class="modal-footer"><button type="submit" name="edit_pengeluaran" class="btn btn-primary">Update</button></div></form></div></div></div></td></tr><?php endwhile; if($pengeluaranKasir->num_rows==0) echo '<tr><td colspan="5">Belum ada</td></tr>'; ?></tbody></table></div></div></div>

    <?php else: ?>
        <!-- ================== TAMPILAN KASIR ================== -->
        <h2 class="mb-4"><i class="fas fa-coins"></i> Keuangan Kasir</h2>

        <!-- Total pengeluaran kasir -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-danger text-white p-3">
                    <h5>Total Pengeluaran Saya</h5>
                    <h3>Rp <?= number_format($totalPengeluaranSaya,0,',','.') ?></h3>
                </div>
            </div>
        </div>

        <!-- Form tambah pengeluaran untuk kasir -->
        <div class="card">
            <div class="card-header bg-danger text-white">Tambah Pengeluaran (Kasir)</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="tambah_pengeluaran" value="1">
                    <div class="row">
                        <div class="col-md-3"><input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-md-3"><input type="text" name="keperluan" class="form-control" placeholder="Keperluan" required></div>
                        <div class="col-md-2"><input type="number" name="jumlah" class="form-control" placeholder="Jumlah (Rp)" required></div>
                        <div class="col-md-3"><input type="text" name="keterangan" class="form-control" placeholder="Keterangan"></div>
                        <div class="col-md-1"><button type="submit" class="btn btn-primary">Simpan</button></div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Daftar pengeluaran kasir -->
        <div class="card mt-4">
            <div class="card-header bg-warning text-dark">Daftar Pengeluaran Saya</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-secondary">
                            <tr><th>Tanggal</th><th>Keperluan</th><th>Jumlah</th><th>Keterangan</th></tr>
                        </thead>
                        <tbody>
                            <?php while($row = $pengeluaranSaya->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['tanggal'] ?></td>
                                <td><?= htmlspecialchars($row['keperluan']) ?></td>
                                <td class="text-danger">Rp <?= number_format($row['jumlah'],0,',','.') ?></td>
                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($pengeluaranSaya->num_rows == 0) echo '<td><td colspan="4" class="text-center">Belum ada pengeluaran</td></tr>'; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>