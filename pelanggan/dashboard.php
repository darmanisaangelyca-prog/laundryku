
<?php
session_start();
include '../config/koneksi.php';

// CEK LOGIN
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

// AMBIL DATA PELANGGAN
$stmt = mysqli_prepare($conn, "
    SELECT *
    FROM pelanggan
    WHERE id_user = ?
");

mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);

$pelanggan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$pelanggan) {
    die("Data pelanggan tidak ditemukan.");
}

$id_pelanggan = (int) $pelanggan['id_pelanggan'];

// STATISTIK PESANAN DAN PEMBAYARAN
$stmt = mysqli_prepare($conn, "
    SELECT
        COUNT(t.id_transaksi) AS total,

        COALESCE(SUM(
            CASE
                WHEN t.status != 'Selesai' THEN 1
                ELSE 0
            END
        ), 0) AS proses,

        COALESCE(SUM(t.total_harga), 0) AS tagihan,

        COALESCE(SUM(pb.dibayar), 0) AS dibayar,

        COALESCE(SUM(
            CASE
                WHEN t.status = 'Siap Diambil' THEN 1
                ELSE 0
            END
        ), 0) AS siap_diambil

    FROM transaksi t

    LEFT JOIN (
        SELECT
            id_transaksi,
            SUM(
                CASE
                    WHEN status_verifikasi = 'Diterima'
                    THEN jumlah_bayar
                    ELSE 0
                END
            ) AS dibayar
        FROM pembayaran
        GROUP BY id_transaksi
    ) pb ON t.id_transaksi = pb.id_transaksi

    WHERE t.id_pelanggan = ?
");

mysqli_stmt_bind_param($stmt, "i", $id_pelanggan);
mysqli_stmt_execute($stmt);

$stat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$total_tagihan = (float) $stat['tagihan'];
$total_dibayar = (float) $stat['dibayar'];

$sisa = max(0, $total_tagihan - $total_dibayar);

// RIWAYAT TRANSAKSI
$stmt = mysqli_prepare($conn, "
    SELECT
        t.*,
        p.nama_paket,

        COALESCE(pb.dibayar, 0) AS dibayar,

        COALESCE(pb.pending, 0) AS pending,

        COALESCE(pb.ditolak, 0) AS ditolak

    FROM transaksi t

    JOIN paket_laundry p
        ON t.id_paket = p.id_paket

    LEFT JOIN (
        SELECT
            id_transaksi,

            SUM(
                CASE
                    WHEN status_verifikasi = 'Diterima'
                    THEN jumlah_bayar
                    ELSE 0
                END
            ) AS dibayar,

            SUM(
                CASE
                    WHEN status_verifikasi = 'Menunggu Verifikasi'
                    THEN 1
                    ELSE 0
                END
            ) AS pending,

            SUM(
                CASE
                    WHEN status_verifikasi = 'Ditolak'
                    THEN 1
                    ELSE 0
                END
            ) AS ditolak

        FROM pembayaran
        GROUP BY id_transaksi

    ) pb ON t.id_transaksi = pb.id_transaksi

    WHERE t.id_pelanggan = ?

    ORDER BY t.id_transaksi DESC
");

mysqli_stmt_bind_param($stmt, "i", $id_pelanggan);
mysqli_stmt_execute($stmt);

$riwayat = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Dashboard Pelanggan - LaundryKu</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body {
    background: #f4f6f9;
    font-family: Arial, sans-serif;
}

.navbar {
    background: #0d6efd;
}

.main {
    padding-top: 35px;
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,.07);
}

.stat {
    font-size: 27px;
    font-weight: bold;
}

.menu-card {
    transition: .2s;
}

.menu-card:hover {
    transform: translateY(-4px);
}

.table th,
.table td {
    vertical-align: middle;
}

@media(max-width: 768px) {

    .main {
        padding-top: 20px;
    }

    .stat {
        font-size: 23px;
    }

}

</style>

</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark p-3">

    <div class="container">

        <a class="navbar-brand" href="dashboard.php">
            🧺 LaundryKu
        </a>

        <div>
            <span class="text-white me-3">
                Pelanggan
            </span>

            <a href="../logout.php" class="btn btn-light btn-sm">
                Logout
            </a>
        </div>

    </div>

</nav>


<div class="container main">

    <!-- SAPAAN -->
    <h2>
        Halo, <?= htmlspecialchars($pelanggan['nama']) ?>!
    </h2>

    <p class="text-secondary">
        Selamat datang kembali di layanan LaundryKu.
    </p>


    <!-- NOTIFIKASI SIAP DIAMBIL -->
    <?php if ($stat['siap_diambil'] > 0): ?>

        <div class="alert alert-success">
            <strong>🎉 Laundry siap diambil!</strong>

            Anda memiliki
            <?= $stat['siap_diambil'] ?>
            pesanan yang sudah siap diambil.
        </div>

    <?php endif; ?>


    <!-- STATISTIK -->
    <div class="row g-3 mt-3">

        <div class="col-md-4">

            <div class="card p-4">

                <h6>Total Pesanan</h6>

                <div class="stat text-primary">
                    <?= $stat['total'] ?>
                </div>

                <small class="text-secondary">
                    Seluruh pesanan Anda
                </small>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card p-4">

                <h6>Dalam Proses</h6>

                <div class="stat text-warning">
                    <?= $stat['proses'] ?>
                </div>

                <small class="text-secondary">
                    Pesanan belum selesai
                </small>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card p-4">

                <h6>Sisa Tagihan</h6>

                <div class="stat text-danger">
                    Rp<?= number_format($sisa, 0, ',', '.') ?>
                </div>

                <small class="text-secondary">
                    Belum dibayar
                </small>

            </div>

        </div>

    </div>


    <!-- MENU -->
    <div class="row g-3 mt-4">

        <div class="col-md-4">

            <div class="card p-4 menu-card h-100">

                <h5>🧺 Pesan Laundry</h5>

                <p class="text-secondary">
                    Buat pesanan laundry baru.
                </p>

                <a href="pesan.php" class="btn btn-primary mt-auto">
                    Pesan Sekarang
                </a>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card p-4 menu-card h-100">

                <h5>📋 Status Cucian</h5>

                <p class="text-secondary">
                    Pantau proses pengerjaan cucian Anda.
                </p>

                <a href="riwayat.php" class="btn btn-primary mt-auto">
                    Lihat Status
                </a>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card p-4 menu-card h-100">

                <h5>💰 Pembayaran</h5>

                <p class="text-secondary">
                    Periksa tagihan dan riwayat pembayaran.
                </p>

                <a href="pembayaran.php" class="btn btn-primary mt-auto">
                    Lihat Pembayaran
                </a>

            </div>

        </div>

    </div>


    <!-- RIWAYAT PESANAN -->
    <div class="card p-4 mt-4 mb-5">

        <h5>📋 Riwayat Pesanan Saya</h5>

        <p class="text-secondary">
            Berikut daftar transaksi dan status pembayaran Anda.
        </p>

        <div class="table-responsive">

            <table class="table table-bordered table-striped mt-3">

                <thead class="table-primary">

                    <tr>
                        <th>ID</th>
                        <th>Paket</th>
                        <th>Tanggal</th>
                        <th>Berat</th>
                        <th>Total</th>
                        <th>Dibayar</th>
                        <th>Status Cucian</th>
                        <th>Status Bayar</th>
                        <th>Aksi</th>
                    </tr>

                </thead>

                <tbody>

                <?php if (mysqli_num_rows($riwayat) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($riwayat)): ?>

                    <?php

                    $total = (float) $row['total_harga'];
                    $dibayar = (float) $row['dibayar'];

                    $sisa_transaksi = max(0, $total - $dibayar);

                    if ($sisa_transaksi <= 0) {

                        $status_bayar = 'Lunas';

                    } elseif ($dibayar > 0) {

                        $status_bayar = 'Belum Lunas';

                    } else {

                        $status_bayar = 'Belum Dibayar';

                    }

                    ?>

                    <tr>

                        <td>
                            #<?= $row['id_transaksi'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['nama_paket']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['tanggal']) ?>
                        </td>

                        <td>
                            <?= $row['berat'] ?> KG
                        </td>

                        <td>
                            Rp<?= number_format($total, 0, ',', '.') ?>
                        </td>

                        <td class="text-success fw-bold">
                            Rp<?= number_format($dibayar, 0, ',', '.') ?>
                        </td>


                        <!-- STATUS CUCIAN -->
                        <td>

                            <?php

                            $warna_status = [
                                'Diproses' => 'primary',
                                'Dicuci' => 'info',
                                'Siap Diambil' => 'warning',
                                'Selesai' => 'success'
                            ];

                            $warna = $warna_status[$row['status']] ?? 'secondary';

                            ?>

                            <span class="badge bg-<?= $warna ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>

                        </td>


                        <!-- STATUS PEMBAYARAN -->
                        <td>

                            <?php if ($status_bayar == 'Lunas'): ?>

                                <span class="badge bg-success">
                                    ✓ Lunas
                                </span>

                            <?php elseif ($row['pending'] > 0): ?>

                                <span class="badge bg-info text-dark">
                                    Menunggu Verifikasi
                                </span>

                            <?php elseif ($status_bayar == 'Belum Lunas'): ?>

                                <span class="badge bg-warning text-dark">
                                    Belum Lunas
                                </span>

                            <?php elseif ($row['ditolak'] > 0): ?>

                                <span class="badge bg-danger">
                                    Pembayaran Ditolak
                                </span>

                            <?php else: ?>

                                <span class="badge bg-danger">
                                    Belum Dibayar
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- AKSI -->
                        <td>

                            <?php if ($sisa_transaksi <= 0): ?>

                                <span class="text-success fw-bold">
                                    ✓ Selesai
                                </span>

                            <?php elseif ($row['pending'] > 0): ?>

                                <span class="text-warning">
                                    Menunggu Admin
                                </span>

                            <?php else: ?>

                                <a
                                    href="form_pembayaran.php?id=<?= $row['id_transaksi'] ?>"
                                    class="btn btn-success btn-sm"
                                >
                                    Bayar Sekarang
                                </a>

                            <?php endif; ?>

                        </td>

                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="9" class="text-center">
                            Belum ada pesanan.
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
