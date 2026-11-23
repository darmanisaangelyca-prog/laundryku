
<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("ID transaksi tidak ditemukan.");
}

$id = (int) $_GET['id'];

$stmt = mysqli_prepare($conn,
    "SELECT t.*, p.nama, p.no_hp, p.alamat,
    pk.nama_paket, pk.harga_per_kg
    FROM transaksi t
    JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan
    JOIN paket_laundry pk ON t.id_paket=pk.id_paket
    WHERE t.id_transaksi=?");

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$data) {
    die("Transaksi tidak ditemukan.");
}

// Hitung pembayaran
$stmt = mysqli_prepare($conn,
    "SELECT COALESCE(SUM(jumlah_bayar),0) AS dibayar
    FROM pembayaran WHERE id_transaksi=?");

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$bayar = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$sisa = max(0, $data['total_harga'] - $bayar['dibayar']);
$status_bayar = $sisa <= 0 ? 'LUNAS' : 'BELUM LUNAS';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Nota LaundryKu</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #eee;
    font-family: Arial, sans-serif;
}

.nota {
    max-width: 650px;
    margin: 40px auto;
    background: white;
    padding: 35px;
    border-radius: 10px;
}

.garis {
    border-top: 2px dashed #333;
    margin: 20px 0;
}

@media print {
    body {
        background: white;
    }

    .nota {
        margin: 0 auto;
        box-shadow: none !important;
    }

    .no-print {
        display: none !important;
    }
}
</style>
</head>

<body>

<div class="nota shadow">

<div class="text-center">

<h2>🧺 LaundryKu</h2>

<p>Layanan Laundry Profesional</p>

<h4>NOTA TRANSAKSI</h4>

</div>

<div class="garis"></div>

<table class="table table-borderless">

<tr>
<td>ID Transaksi</td>
<td>: <?= $data['id_transaksi'] ?></td>
</tr>

<tr>
<td>Tanggal</td>
<td>: <?= $data['tanggal'] ?></td>
</tr>

<tr>
<td>Nama Pelanggan</td>
<td>: <?= htmlspecialchars($data['nama']) ?></td>
</tr>

<tr>
<td>Nomor HP</td>
<td>: <?= htmlspecialchars($data['no_hp']) ?></td>
</tr>

<tr>
<td>Alamat</td>
<td>: <?= htmlspecialchars($data['alamat']) ?></td>
</tr>

</table>

<div class="garis"></div>

<h5>Detail Laundry</h5>

<table class="table table-bordered">

<tr>
<th>Paket</th>
<td><?= htmlspecialchars($data['nama_paket']) ?></td>
</tr>

<tr>
<th>Harga / KG</th>
<td>Rp<?= number_format($data['harga_per_kg'], 0, ',', '.') ?></td>
</tr>

<tr>
<th>Berat</th>
<td><?= $data['berat'] ?> KG</td>
</tr>

<tr>
<th>Total Tagihan</th>
<td>
<strong>Rp<?= number_format($data['total_harga'], 0, ',', '.') ?></strong>
</td>
</tr>

<tr>
<th>Sudah Dibayar</th>
<td>Rp<?= number_format($bayar['dibayar'], 0, ',', '.') ?></td>
</tr>

<tr>
<th>Sisa Pembayaran</th>
<td>Rp<?= number_format($sisa, 0, ',', '.') ?></td>
</tr>

<tr>
<th>Status Cucian</th>
<td><?= htmlspecialchars($data['status']) ?></td>
</tr>

<tr>
<th>Status Pembayaran</th>
<td><?= $status_bayar ?></td>
</tr>

</table>

<div class="garis"></div>

<div class="text-center">
<p>Terima kasih telah menggunakan LaundryKu!</p>
<p>Jaga nota ini sebagai bukti transaksi.</p>
</div>

<div class="d-flex justify-content-center gap-2 no-print">

<button onclick="window.print()" class="btn btn-primary">
Cetak Nota
</button>

<a href="transaksi.php" class="btn btn-secondary">
Kembali
</a>

</div>

</div>

</body>
</html>
