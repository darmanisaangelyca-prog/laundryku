
<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

$stmt = mysqli_prepare($conn,
    "SELECT t.*, p.nama_paket
     FROM transaksi t
     JOIN pelanggan pl ON t.id_pelanggan = pl.id_pelanggan
     JOIN paket_laundry p ON t.id_paket = p.id_paket
     WHERE pl.id_user = ?
     ORDER BY t.id_transaksi DESC");

mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);

$data = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Riwayat Pesanan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

<h2>📋 Riwayat Pesanan Saya</h2>

<a href="dashboard.php" class="btn btn-secondary mb-3">Kembali</a>

<?php if (isset($_GET['berhasil'])): ?>
<div class="alert alert-success">
Pesanan berhasil dibuat!
</div>
<?php endif; ?>

<div class="card p-3">
<div class="table-responsive">

<table class="table table-bordered table-striped">

<thead class="table-primary">
<tr>
<th>ID</th>
<th>Paket</th>
<th>Tanggal</th>
<th>Berat</th>
<th>Total</th>
<th>Status</th>
<th>Pembayaran</th>
</tr>
</thead>

<tbody>

<?php while ($row = mysqli_fetch_assoc($data)): ?>

<tr>
<td><?= $row['id_transaksi'] ?></td>
<td><?= htmlspecialchars($row['nama_paket']) ?></td>
<td><?= $row['tanggal'] ?></td>
<td><?= $row['berat'] ?> KG</td>
<td>Rp<?= number_format($row['total_harga'], 0, ',', '.') ?></td>

<td>
<span class="badge bg-primary">
<?= htmlspecialchars($row['status']) ?>
</span>
</td>

<td>
<a href="pembayaran.php?id=<?= $row['id_transaksi'] ?>"
   class="btn btn-success btn-sm">
   💳 Bayar Sekarang
</a>
</td>

</tr>

<?php endwhile; ?>

</tbody>
</table>
</div>
</div>

</div>
</body>
</html>
