
<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$mulai = $_GET['mulai'] ?? '';
$akhir = $_GET['akhir'] ?? '';

// Filter tanggal pembayaran
$sql = "SELECT b.*, p.nama, t.id_transaksi
        FROM pembayaran b
        JOIN transaksi t ON b.id_transaksi=t.id_transaksi
        JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan
        WHERE 1=1";

if ($mulai != '') {
    $sql .= " AND b.tanggal_bayar >= '" . mysqli_real_escape_string($conn, $mulai) . "'";
}

if ($akhir != '') {
    $sql .= " AND b.tanggal_bayar <= '" . mysqli_real_escape_string($conn, $akhir) . "'";
}

$sql .= " ORDER BY b.tanggal_bayar DESC";

$data = mysqli_query($conn, $sql);

if (!$data) {
    die("Gagal mengambil laporan: " . mysqli_error($conn));
}

// Hitung pendapatan
$total_pendapatan = 0;
$total_pembayaran = 0;

while ($row = mysqli_fetch_assoc($data)) {
    $total_pendapatan += $row['jumlah_bayar'];
    $total_pembayaran++;
}

mysqli_data_seek($data, 0);

// Hitung transaksi berdasarkan tanggal transaksi
$sql_transaksi = "SELECT COUNT(*) AS total FROM transaksi WHERE 1=1";

if ($mulai != '') {
    $sql_transaksi .= " AND tanggal >= '" . mysqli_real_escape_string($conn, $mulai) . "'";
}

if ($akhir != '') {
    $sql_transaksi .= " AND tanggal <= '" . mysqli_real_escape_string($conn, $akhir) . "'";
}

$q = mysqli_query($conn, $sql_transaksi);
$total_transaksi = mysqli_fetch_assoc($q)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Laporan LaundryKu</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #f4f6f9;
}

.card {
    border: none;
    border-radius: 12px;
}

@media print {
    .no-print {
        display: none !important;
    }
}
</style>
</head>

<body>

<div class="container py-4">

<h2>📊 Laporan LaundryKu</h2>

<a href="dashboard.php" class="btn btn-secondary mb-3 no-print">
Kembali
</a>

<!-- FILTER TANGGAL -->

<div class="card shadow-sm p-3 mb-4 no-print">

<h5>Filter Laporan</h5>

<form method="GET" class="row g-3">

<div class="col-md-4">
<label class="form-label">Tanggal Mulai</label>
<input type="date" name="mulai"
class="form-control"
value="<?= htmlspecialchars($mulai) ?>">
</div>

<div class="col-md-4">
<label class="form-label">Tanggal Akhir</label>
<input type="date" name="akhir"
class="form-control"
value="<?= htmlspecialchars($akhir) ?>">
</div>

<div class="col-md-4 d-flex align-items-end gap-2">

<button type="submit" class="btn btn-primary">
Filter
</button>

<a href="laporan.php" class="btn btn-secondary">
Reset
</a>

</div>

</form>
</div>

<!-- RINGKASAN -->

<div class="row g-3 mb-4">

<div class="col-md-4">
<div class="card shadow-sm p-4">
<h6>Total Pendapatan</h6>
<h3 class="text-success">
Rp<?= number_format($total_pendapatan, 0, ',', '.') ?>
</h3>
</div>
</div>

<div class="col-md-4">
<div class="card shadow-sm p-4">
<h6>Total Pembayaran</h6>
<h3 class="text-primary">
<?= $total_pembayaran ?>
</h3>
</div>
</div>

<div class="col-md-4">
<div class="card shadow-sm p-4">
<h6>Total Transaksi</h6>
<h3 class="text-warning">
<?= $total_transaksi ?>
</h3>
</div>
</div>

</div>

<!-- TABEL LAPORAN -->

<div class="card shadow-sm p-3">

<div class="d-flex justify-content-between mb-3">

<h5>Riwayat Pendapatan</h5>

<button onclick="window.print()"
class="btn btn-primary no-print">
Cetak Laporan
</button>

</div>

<div class="table-responsive">

<table class="table table-bordered table-striped">

<thead class="table-primary">
<tr>
<th>ID Pembayaran</th>
<th>ID Transaksi</th>
<th>Nama Pelanggan</th>
<th>Tanggal Bayar</th>
<th>Jumlah</th>
<th>Metode</th>
</tr>
</thead>

<tbody>

<?php if (mysqli_num_rows($data) > 0): ?>

<?php while ($row = mysqli_fetch_assoc($data)): ?>

<tr>
<td><?= $row['id_pembayaran'] ?></td>
<td><?= $row['id_transaksi'] ?></td>
<td><?= htmlspecialchars($row['nama']) ?></td>
<td><?= $row['tanggal_bayar'] ?></td>
<td>
Rp<?= number_format($row['jumlah_bayar'], 0, ',', '.') ?>
</td>
<td><?= htmlspecialchars($row['metode']) ?></td>
</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="6" class="text-center">
Belum ada pembayaran pada periode ini.
</td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>
</div>

</div>

</body>
</html>
