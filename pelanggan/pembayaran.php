
<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

$stmt = mysqli_prepare($conn, "SELECT * FROM pelanggan WHERE id_user=?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);

$pelanggan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$pelanggan) {
    die("Data pelanggan tidak ditemukan.");
}

$id_pelanggan = $pelanggan['id_pelanggan'];

$stmt = mysqli_prepare($conn,
    "SELECT t.id_transaksi, t.tanggal, t.total_harga,
    p.nama_paket,
    COALESCE(SUM(b.jumlah_bayar),0) AS dibayar
    FROM transaksi t
    JOIN paket_laundry p ON t.id_paket=p.id_paket
    LEFT JOIN pembayaran b ON t.id_transaksi=b.id_transaksi
    WHERE t.id_pelanggan=?
    GROUP BY t.id_transaksi
    ORDER BY t.id_transaksi DESC");

mysqli_stmt_bind_param($stmt, "i", $id_pelanggan);
mysqli_stmt_execute($stmt);

$data = mysqli_stmt_get_result($stmt);

$total_tagihan = 0;
$total_dibayar = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pembayaran Saya - LaundryKu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-primary p-3">
<div class="container">
<a class="navbar-brand" href="dashboard.php">🧺 LaundryKu</a>
<a href="dashboard.php" class="btn btn-light btn-sm">Kembali</a>
</div>
</nav>

<div class="container py-4">

<h2>💰 Pembayaran Saya</h2>

<p class="text-secondary">
Halo, <?= htmlspecialchars($pelanggan['nama']) ?>.
Berikut rincian tagihan Anda.
</p>

<div class="card shadow-sm p-3">
<div class="table-responsive">

<table class="table table-bordered table-striped">

<thead class="table-primary">
<tr>
<th>ID Transaksi</th>
<th>Paket</th>
<th>Tanggal</th>
<th>Total Tagihan</th>
<th>Sudah Dibayar</th>
<th>Sisa</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php if (mysqli_num_rows($data) > 0): ?>

<?php while ($row = mysqli_fetch_assoc($data)): ?>

<?php
$sisa = max(0, $row['total_harga'] - $row['dibayar']);

$status = $sisa <= 0 ? 'Lunas' : 'Belum Lunas';

$total_tagihan += $row['total_harga'];
$total_dibayar += $row['dibayar'];
?>

<tr>
<td><?= $row['id_transaksi'] ?></td>
<td><?= htmlspecialchars($row['nama_paket']) ?></td>
<td><?= $row['tanggal'] ?></td>

<td>
Rp<?= number_format($row['total_harga'], 0, ',', '.') ?>
</td>

<td>
Rp<?= number_format($row['dibayar'], 0, ',', '.') ?>
</td>

<td>
Rp<?= number_format($sisa, 0, ',', '.') ?>
</td>

<td>
<?php if ($status == 'Lunas'): ?>
<span class="badge bg-success">Lunas</span>
<?php else: ?>
<span class="badge bg-warning text-dark">Belum Lunas</span>
<?php endif; ?>
</td>

<td>
<?php if ($sisa > 0): ?>
<a href="form_pembayaran.php?id=<?= $row['id_transaksi'] ?>"
class="btn btn-success btn-sm">
Bayar Sekarang
</a>
<?php else: ?>
<span class="text-success fw-bold">✓ Selesai</span>
<?php endif; ?>
</td>

</tr>

<?php endwhile; ?>

<?php else: ?>
<tr>
<td colspan="8" class="text-center">Belum ada tagihan.</td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>
</div>

<div class="row g-3 mt-3">

<div class="col-md-4">
<div class="card shadow-sm p-3">
<h6>Total Tagihan</h6>
<h4 class="text-primary">
Rp<?= number_format($total_tagihan, 0, ',', '.') ?>
</h4>
</div>
</div>

<div class="col-md-4">
<div class="card shadow-sm p-3">
<h6>Total Dibayar</h6>
<h4 class="text-success">
Rp<?= number_format($total_dibayar, 0, ',', '.') ?>
</h4>
</div>
</div>

<div class="col-md-4">
<div class="card shadow-sm p-3">
<h6>Sisa Pembayaran</h6>
<h4 class="text-danger">
Rp<?= number_format(max(0, $total_tagihan - $total_dibayar), 0, ',', '.') ?>
</h4>
</div>
</div>

</div>
</div>

</body>
</html>
