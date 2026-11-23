
<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$cari = trim($_GET['cari'] ?? '');

$sql = "SELECT t.*, p.nama, pk.nama_paket
        FROM transaksi t
        JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
        JOIN paket_laundry pk ON t.id_paket = pk.id_paket
        WHERE p.nama LIKE ?
        OR CAST(t.id_transaksi AS CHAR) LIKE ?
        OR t.status LIKE ?
        OR t.tanggal LIKE ?
        ORDER BY t.id_transaksi DESC";

$stmt = mysqli_prepare($conn, $sql);

$keyword = "%$cari%";

mysqli_stmt_bind_param(
    $stmt, "ssss",
    $keyword, $keyword, $keyword, $keyword
);

mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cari Transaksi LaundryKu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

<h2>🔎 Cari Transaksi LaundryKu</h2>

<a href="dashboard.php" class="btn btn-secondary mb-3">
Kembali
</a>

<div class="card p-3 shadow-sm mb-4">

<form method="GET">

<label class="form-label">Cari transaksi</label>

<div class="input-group">
<input type="text" name="cari"
class="form-control"
placeholder="Nama, ID transaksi, status atau tanggal..."
value="<?= htmlspecialchars($cari) ?>">

<button class="btn btn-primary">
Cari
</button>

<a href="cari_transaksi.php" class="btn btn-secondary">
Reset
</a>
</div>

</form>
</div>

<div class="card p-3 shadow-sm">

<h5>Hasil Pencarian</h5>

<div class="table-responsive">

<table class="table table-bordered table-striped">

<thead class="table-primary">
<tr>
<th>ID</th>
<th>Pelanggan</th>
<th>Paket</th>
<th>Tanggal</th>
<th>Berat</th>
<th>Total</th>
<th>Status</th>
<th>Nota</th>
</tr>
</thead>

<tbody>

<?php if (mysqli_num_rows($data) > 0): ?>

<?php while ($row = mysqli_fetch_assoc($data)): ?>

<tr>
<td><?= $row['id_transaksi'] ?></td>
<td><?= htmlspecialchars($row['nama']) ?></td>
<td><?= htmlspecialchars($row['nama_paket']) ?></td>
<td><?= $row['tanggal'] ?></td>
<td><?= $row['berat'] ?> KG</td>
<td>Rp<?= number_format($row['total_harga'], 0, ',', '.') ?></td>
<td><?= htmlspecialchars($row['status']) ?></td>
<td>
<a href="nota.php?id=<?= $row['id_transaksi'] ?>"
class="btn btn-primary btn-sm">
Cetak
</a>
</td>
</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="8" class="text-center">
Data transaksi tidak ditemukan.
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
