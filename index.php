<?php
include 'config.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");
$role = $_SESSION['role'];
$nama_user = $_SESSION['nama_lengkap'];

// Query untuk daftar pesanan
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role == 'admin') {
    // Admin melihat semua pesanan
    $query = "SELECT p.*, 
              (SELECT GROUP_CONCAT(CONCAT(d.rasa, '(', d.jumlah_porsi, ')') SEPARATOR ', ') 
               FROM detail_pesanan d WHERE d.pesanan_id = p.id) AS daftar_rasa
              FROM pesanan p ORDER BY p.tanggal DESC, p.id DESC";
} else {
    // Kasir hanya melihat pesanan miliknya sendiri
    $query = "SELECT p.*, 
              (SELECT GROUP_CONCAT(CONCAT(d.rasa, '(', d.jumlah_porsi, ')') SEPARATOR ', ') 
               FROM detail_pesanan d WHERE d.pesanan_id = p.id) AS daftar_rasa
              FROM pesanan p WHERE p.user_id = $user_id ORDER BY p.tanggal DESC, p.id DESC";
}
$pesanan = $conn->query($query);

// Hanya admin yang perlu hitung keuangan khusus
if($role == 'admin') {
    // Total pemasukan & pengeluaran umum
    $total_pemasukan = $conn->query("SELECT SUM(jumlah) as total FROM pemasukan")->fetch_assoc()['total'] ?? 0;
    $total_pengeluaran = $conn->query("SELECT SUM(jumlah) as total FROM pengeluaran")->fetch_assoc()['total'] ?? 0;
    $saldo = $total_pemasukan - $total_pengeluaran;
    
    // Pemasukan dari transaksi yang dibuat KASIR
    $kasir_sql = "SELECT SUM(pemasukan.jumlah) as total FROM pemasukan 
                  INNER JOIN pesanan ON pemasukan.pesanan_id = pesanan.id 
                  INNER JOIN users ON pesanan.user_id = users.id 
                  WHERE users.role = 'kasir'";
    $pemasukan_kasir = $conn->query($kasir_sql)->fetch_assoc()['total'] ?? 0;
    
    // Pemasukan dari transaksi yang dibuat ADMIN
    $admin_sql = "SELECT SUM(pemasukan.jumlah) as total FROM pemasukan 
                  INNER JOIN pesanan ON pemasukan.pesanan_id = pesanan.id 
                  INNER JOIN users ON pesanan.user_id = users.id 
                  WHERE users.role = 'admin'";
    $pemasukan_admin = $conn->query($admin_sql)->fetch_assoc()['total'] ?? 0;
    
    // Hari ini
    $hari_ini = date('Y-m-d');
    $pemasukan_hari = $conn->query("SELECT SUM(jumlah) as total FROM pemasukan WHERE tanggal = '$hari_ini'")->fetch_assoc()['total'] ?? 0;
    $pengeluaran_hari = $conn->query("SELECT SUM(jumlah) as total FROM pengeluaran WHERE tanggal = '$hari_ini'")->fetch_assoc()['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Cilung Malfi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        *{font-family:'Inter',sans-serif;}
        body{background:linear-gradient(135deg,#f5f7fa 0%,#e9edf2 100%);}
        .navbar-custom{background:linear-gradient(90deg,#1e2a3a,#0f1724);box-shadow:0 4px 20px rgba(0,0,0,0.1);}
        .navbar-brand{font-weight:700;font-size:1.5rem;}
        .card-stats{border:none;border-radius:20px;transition:all 0.3s ease;overflow:hidden;}
        .card-stats:hover{transform:translateY(-8px);box-shadow:0 15px 30px rgba(0,0,0,0.1);}
        .bg-gradient-primary{background:linear-gradient(135deg,#4e73df,#224abe);}
        .bg-gradient-danger{background:linear-gradient(135deg,#e74a3b,#be2617);}
        .bg-gradient-success{background:linear-gradient(135deg,#1cc88a,#13855c);}
        .stats-icon{position:absolute;right:20px;bottom:20px;font-size:70px;opacity:0.2;}
        .table-custom{border-radius:15px;overflow:hidden;box-shadow:0 5px 15px rgba(0,0,0,0.05);}
        .table-custom th{background-color:#2c3e50;color:white;font-weight:500;}
        .badge-rasa{background:linear-gradient(135deg,#f6c23e,#f4b619);color:#000;border-radius:30px;padding:5px 12px;font-size:0.8rem;margin:2px;display:inline-block;}
        .footer{background:#1e2a3a;color:#aaa;border-radius:20px 20px 0 0;margin-top:50px;}
        .btn-primary-custom{background:linear-gradient(45deg,#4e73df,#224abe);border:none;border-radius:50px;padding:10px 20px;transition:0.3s;}
        .btn-primary-custom:hover{transform:scale(1.02);box-shadow:0 5px 10px rgba(0,0,0,0.2);}
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-custom navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-store me-2"></i>Cilung Malfi</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="pesanan.php">Pesanan</a></li>
                <li class="nav-item"><a class="nav-link" href="keuangan.php">Keuangan</a></li>
                <?php if($role == 'admin'): ?>
                <!-- tambahan menu jika perlu -->
                <?php endif; ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($nama_user) ?></a>
                    <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="logout.php">Logout</a></li></ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-chart-pie me-2 text-primary"></i>Dashboard <?= ($role == 'admin') ? 'Owner' : 'Kasir' ?></h2>
        <span class="badge bg-secondary p-2"><i class="fas fa-calendar-alt me-1"></i> <?= date('d F Y') ?></span>
    </div>

    <?php if($role == 'admin'): ?>
    <!-- 3 kartu utama -->
    <div class="row g-4 mb-4">
        <div class="col-md-4"><div class="card card-stats bg-gradient-primary text-white shadow"><div class="card-body position-relative"><i class="fas fa-money-bill-wave stats-icon"></i><h5>Total Pemasukan</h5><h2 class="fw-bold">Rp <?= number_format($total_pemasukan,0,',','.') ?></h2></div></div></div>
        <div class="col-md-4"><div class="card card-stats bg-gradient-danger text-white shadow"><div class="card-body position-relative"><i class="fas fa-receipt stats-icon"></i><h5>Total Pengeluaran</h5><h2 class="fw-bold">Rp <?= number_format($total_pengeluaran,0,',','.') ?></h2></div></div></div>
        <div class="col-md-4"><div class="card card-stats bg-gradient-success text-white shadow"><div class="card-body position-relative"><i class="fas fa-coins stats-icon"></i><h5>Saldo Saat Ini</h5><h2 class="fw-bold">Rp <?= number_format($saldo,0,',','.') ?></h2></div></div></div>
    </div>
    
    <!-- Kartu pemisah pemasukan berdasarkan role -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-info text-white">
                <div class="card-body">
                    <h5><i class="fas fa-user-tie"></i> Pemasukan dari Admin (Owner)</h5>
                    <h3>Rp <?= number_format($pemasukan_admin,0,',','.') ?></h3>
                    <small>Transaksi yang dibuat oleh Anda sendiri</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 bg-warning text-dark">
                <div class="card-body">
                    <h5><i class="fas fa-user-friends"></i> Pemasukan dari Kasir (Pegawai)</h5>
                    <h3>Rp <?= number_format($pemasukan_kasir,0,',','.') ?></h3>
                    <small>Transaksi yang dibuat oleh kasir</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Ringkasan harian -->
    <div class="row g-4 mb-5">
        <div class="col-md-6"><div class="card border-0 shadow-sm rounded-4"><div class="card-body"><h6><i class="fas fa-calendar-day text-primary"></i> Pemasukan Hari Ini (<?= date('d/m/Y') ?>)</h6><h3 class="text-success">Rp <?= number_format($pemasukan_hari,0,',','.') ?></h3></div></div></div>
        <div class="col-md-6"><div class="card border-0 shadow-sm rounded-4"><div class="card-body"><h6><i class="fas fa-calendar-day text-danger"></i> Pengeluaran Hari Ini</h6><h3 class="text-danger">Rp <?= number_format($pengeluaran_hari,0,',','.') ?></h3></div></div></div>
    </div>
    <?php endif; ?>

    <!-- Daftar Pesanan (sama untuk semua role) -->
    <div class="card shadow-sm rounded-4 border-0">
        <div class="card-header bg-white border-0 pt-4 pb-0">
            <h5 class="fw-bold"><i class="fas fa-clipboard-list text-info me-2"></i>Daftar Pesanan</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle">
                    <thead>
                        <tr><th>Tanggal</th><th>Pembeli</th><th>Rasa & Jumlah</th><th>Total</th><th>Keterangan</th><?php if($role=='admin') echo '<th>Aksi</th>'; ?></tr>
                    </thead>
                    <tbody>
                        <?php if($pesanan->num_rows == 0): ?>
                        <tr><td colspan="<?= ($role=='admin')?6:5 ?>" class="text-center text-muted py-4">Belum ada pesanan</td></tr>
                        <?php else: while($row = $pesanan->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                            <td><i class="fas fa-user-circle text-secondary me-1"></i> <?= htmlspecialchars($row['nama_pembeli']) ?></td>
                            <td><?php $rasa_list = explode(', ', $row['daftar_rasa']); foreach($rasa_list as $r) echo "<span class='badge-rasa'>$r</span> "; ?></td>
                            <td class="fw-bold text-success">Rp <?= number_format($row['total_harga'],0,',','.') ?></td>
                            <td><?= htmlspecialchars($row['keterangan']) ?: '-' ?></td>
                            <?php if($role == 'admin'): ?>
                            <td><a href="hapus_pesanan.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Hapus seluruh pesanan ini?')"><i class="fas fa-trash-alt"></i> Hapus</a></td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3"><a href="pesanan.php" class="btn btn-primary-custom text-white"><i class="fas fa-plus-circle me-2"></i>Tambah Pesanan Baru</a></div>
        </div>
    </div>
</div>
<div class="footer text-center py-3 mt-5"><small>&copy; 2025 Cilung Malfi - Nikmati 4 Rasa + Acak</small></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>