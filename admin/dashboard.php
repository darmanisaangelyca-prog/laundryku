
<?php
session_start();
include '../config/koneksi.php';


if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// Menghitung jumlah pelanggan
$q1 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pelanggan");
$pelanggan = mysqli_fetch_assoc($q1)['total'];

// Menghitung jumlah transaksi
$q2 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi");
$transaksi = mysqli_fetch_assoc($q2)['total'];

// Menghitung total pendapatan
$q3 = mysqli_query($conn, "SELECT COALESCE(SUM(jumlah_bayar),0) AS total FROM pembayaran");
$pendapatan = mysqli_fetch_assoc($q3)['total'];

// Menghitung cucian yang belum selesai
$q4 = mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM transaksi WHERE status != 'Selesai'");
$proses = mysqli_fetch_assoc($q4)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Admin LaundryKu</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #f4f6f9;
    font-family: Arial, sans-serif;
}

.sidebar {
    background: #172554;
    min-height: 100vh;
    color: white;
    padding: 25px 15px;
}

.sidebar a {
    display: block;
    color: #dbeafe;
    text-decoration: none;
    padding: 12px;
    margin-bottom: 8px;
    border-radius: 8px;
}

.sidebar a:hover {
    background: #2563eb;
    color: white;
}

.main {
    padding: 30px;
}

.stat-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,.06);
    padding: 22px;
}

.stat-number {
    font-size: 27px;
    font-weight: bold;
}

@media(max-width: 768px) {
    .sidebar {
        min-height: auto;
    }
    .main {
        padding: 18px;
    }
}
</style>
</head>

<body>

<div class="container-fluid">
<div class="row">

<!-- SIDEBAR -->
<div class="col-md-3 col-lg-2 sidebar">

    <h4 class="mb-4">🧺 LaundryKu</h4>

    <p class="text-white-50">MENU ADMIN</p>

    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="pelanggan.php">👥 Data Pelanggan</a>
    <a href="paket.php">🧺 Paket Laundry</a>
    <a href="transaksi.php">📋 Transaksi</a>
    <a href="cari_transaksi.php">🔎 Cari Transaksi</a>
    <a href="pembayaran.php">💰 Pembayaran</a>
    <a href="laporan.php">📊 Laporan Pendapatan</a>

    <hr>

    <a href="../logout.php">🚪 Logout</a>

</div>

<!-- KONTEN -->
<div class="col-md-9 col-lg-10 main">

    <h3>Dashboard Admin</h3>

    <p class="text-secondary">
        Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>!
        Berikut ringkasan LaundryKu.
    </p>

    <div class="row g-3 mt-3">

        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <h6 class="text-secondary">Total Pelanggan</h6>
                <div class="stat-number text-primary">
                    <?= $pelanggan ?>
                </div>
                <small>Orang terdaftar</small>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <h6 class="text-secondary">Total Transaksi</h6>
                <div class="stat-number text-success">
                    <?= $transaksi ?>
                </div>
                <small>Pesanan tercatat</small>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <h6 class="text-secondary">Pendapatan</h6>
                <div class="stat-number text-warning">
                    Rp<?= number_format($pendapatan, 0, ',', '.') ?>
                </div>
                <small>Total uang diterima</small>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <h6 class="text-secondary">Dalam Proses</h6>
                <div class="stat-number text-danger">
                    <?= $proses ?>
                </div>
                <small>Cucian belum selesai</small>
            </div>
        </div>

    </div>

    <div class="card border-0 shadow-sm mt-4 p-4">
        <h5>Selamat Bekerja! 🧺</h5>
        <p class="text-secondary mb-0">
            Kelola pelanggan, paket laundry, transaksi,
            dan pembayaran melalui menu di sebelah kiri.
        </p>
    </div>

    <div class="card border-0 shadow-sm mt-4 p-4">
        <h5>Informasi Sistem</h5>
        <p class="mb-1">Nama Sistem: LaundryKu</p>
        <p class="mb-1">Pengguna: Administrator</p>
        <p class="mb-0">Status: Aktif</p>
    </div>

</div>
</div>
</div>

</body>
</html>
