
<?php
session_start();
include '../config/koneksi.php';

// CEK LOGIN ADMIN
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$pesan = '';
$jenis_pesan = 'success';

// PROSES VERIFIKASI PEMBAYARAN
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $id_pembayaran = (int)($_POST['id_pembayaran'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';

    if ($id_pembayaran <= 0 || !in_array($aksi, ['terima', 'tolak'], true)) {

        $pesan = "Permintaan tidak valid.";
        $jenis_pesan = 'danger';

    } else {

        mysqli_begin_transaction($conn);

        try {

            // AMBIL DATA PEMBAYARAN DAN PELANGGAN
            $stmt = mysqli_prepare($conn, "
                SELECT
                    b.id_pembayaran,
                    b.id_transaksi,
                    b.jumlah_bayar,
                    b.status_verifikasi,
                    t.total_harga,
                    pl.id_user
                FROM pembayaran b
                JOIN transaksi t
                    ON b.id_transaksi = t.id_transaksi
                JOIN pelanggan pl
                    ON t.id_pelanggan = pl.id_pelanggan
                WHERE b.id_pembayaran = ?
                FOR UPDATE
            ");

            mysqli_stmt_bind_param($stmt, "i", $id_pembayaran);
            mysqli_stmt_execute($stmt);

            $pembayaran = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmt)
            );

            if (!$pembayaran) {
                throw new Exception("Pembayaran tidak ditemukan.");
            }

            if ($pembayaran['status_verifikasi'] != 'Menunggu Verifikasi') {
                throw new Exception("Pembayaran sudah pernah diproses.");
            }

            // PROSES TERIMA PEMBAYARAN
            if ($aksi == 'terima') {

                $stmt = mysqli_prepare($conn, "
                    SELECT COALESCE(SUM(jumlah_bayar), 0) AS dibayar
                    FROM pembayaran
                    WHERE id_transaksi = ?
                    AND status_verifikasi = 'Diterima'
                ");

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $pembayaran['id_transaksi']
                );

                mysqli_stmt_execute($stmt);

                $hasil = mysqli_fetch_assoc(
                    mysqli_stmt_get_result($stmt)
                );

                $sisa = (float)$pembayaran['total_harga']
                      - (float)$hasil['dibayar'];

                if (
                    $pembayaran['jumlah_bayar'] <= 0 ||
                    $pembayaran['jumlah_bayar'] > $sisa
                ) {
                    throw new Exception(
                        "Nominal pembayaran melebihi sisa tagihan."
                    );
                }

                $status_baru = 'Diterima';

                $judul = "Pembayaran Diterima";

                $isi = "Pembayaran transaksi #" .
                    $pembayaran['id_transaksi'] .
                    " sebesar Rp" .
                    number_format(
                        $pembayaran['jumlah_bayar'],
                        0,
                        ',',
                        '.'
                    ) .
                    " telah diterima oleh admin LaundryKu.";

                $pesan = "Pembayaran berhasil diterima.";

            } else {

                // PROSES TOLAK PEMBAYARAN

                $status_baru = 'Ditolak';

                $judul = "Pembayaran Ditolak";

                $isi = "Pembayaran transaksi #" .
                    $pembayaran['id_transaksi'] .
                    " sebesar Rp" .
                    number_format(
                        $pembayaran['jumlah_bayar'],
                        0,
                        ',',
                        '.'
                    ) .
                    " ditolak oleh admin. Silakan periksa kembali pembayaran Anda.";

                $pesan = "Pembayaran berhasil ditolak.";
            }

            // UPDATE STATUS PEMBAYARAN
            $stmt = mysqli_prepare($conn, "
                UPDATE pembayaran
                SET status_verifikasi = ?
                WHERE id_pembayaran = ?
                AND status_verifikasi = 'Menunggu Verifikasi'
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $status_baru,
                $id_pembayaran
            );

            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) != 1) {
                throw new Exception(
                    "Gagal memperbarui status pembayaran."
                );
            }

            // BUAT NOTIFIKASI PELANGGAN
            $stmt_notif = mysqli_prepare($conn, "
                INSERT INTO notifikasi
                (
                    id_user,
                    id_transaksi,
                    judul,
                    pesan
                )
                VALUES (?, ?, ?, ?)
            ");

            mysqli_stmt_bind_param(
                $stmt_notif,
                "iiss",
                $pembayaran['id_user'],
                $pembayaran['id_transaksi'],
                $judul,
                $isi
            );

            if (!mysqli_stmt_execute($stmt_notif)) {
                throw new Exception(
                    "Gagal membuat notifikasi pelanggan."
                );
            }

            // SIMPAN SEMUA PERUBAHAN
            mysqli_commit($conn);

        } catch (Exception $e) {

            mysqli_rollback($conn);

            $pesan = $e->getMessage();
            $jenis_pesan = 'danger';
        }
    }
}

// AMBIL PEMBAYARAN MENUNGGU VERIFIKASI
$menunggu = mysqli_query($conn, "
    SELECT
        b.*,
        p.nama,
        t.total_harga
    FROM pembayaran b
    JOIN transaksi t
        ON b.id_transaksi = t.id_transaksi
    JOIN pelanggan p
        ON t.id_pelanggan = p.id_pelanggan
    WHERE b.status_verifikasi = 'Menunggu Verifikasi'
    ORDER BY b.id_pembayaran DESC
");

// AMBIL RIWAYAT PEMBAYARAN
$riwayat = mysqli_query($conn, "
    SELECT
        b.*,
        p.nama,
        t.total_harga
    FROM pembayaran b
    JOIN transaksi t
        ON b.id_transaksi = t.id_transaksi
    JOIN pelanggan p
        ON t.id_pelanggan = p.id_pelanggan
    WHERE b.status_verifikasi IN ('Diterima', 'Ditolak')
    ORDER BY b.id_pembayaran DESC
");

// TOTAL PEMBAYARAN DITERIMA
$total = mysqli_query($conn, "
    SELECT COALESCE(SUM(jumlah_bayar), 0) AS total
    FROM pembayaran
    WHERE status_verifikasi = 'Diterima'
");

$total_diterima = mysqli_fetch_assoc($total)['total'];

// JUMLAH MENUNGGU
$jumlah_menunggu = mysqli_num_rows($menunggu);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Verifikasi Pembayaran - LaundryKu</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body {
    background: #f4f6f9;
}

.card {
    border: none;
    border-radius: 12px;
}

.bukti {
    width: 85px;
    height: 65px;
    object-fit: cover;
    border-radius: 7px;
    cursor: pointer;
}

.table th,
.table td {
    vertical-align: middle;
}

</style>

</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-primary p-3">

    <div class="container">

        <a href="dashboard.php" class="navbar-brand">
            🧺 LaundryKu Admin
        </a>

        <a href="dashboard.php" class="btn btn-light btn-sm">
            Kembali
        </a>

    </div>

</nav>


<div class="container py-4">

    <h2 class="mb-2">💰 Verifikasi Pembayaran</h2>

    <p class="text-secondary">
        Kelola pembayaran pelanggan LaundryKu.
    </p>


    <!-- PESAN -->
    <?php if ($pesan): ?>

        <div class="alert alert-<?= $jenis_pesan ?> alert-dismissible fade show">

            <?= htmlspecialchars($pesan) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <!-- STATISTIK -->
    <div class="row g-3 mb-4">

        <div class="col-md-6">

            <div class="card shadow-sm p-3">

                <h6>Total Pembayaran Diterima</h6>

                <h3 class="text-success">
                    Rp<?= number_format(
                        $total_diterima,
                        0,
                        ',',
                        '.'
                    ) ?>
                </h3>

            </div>

        </div>

        <div class="col-md-6">

            <div class="card shadow-sm p-3">

                <h6>Pembayaran Menunggu</h6>

                <h3 class="text-warning">
                    <?= $jumlah_menunggu ?>
                </h3>

            </div>

        </div>

    </div>


    <!-- PEMBAYARAN MENUNGGU -->
    <div class="card shadow-sm p-3 mb-4">

        <h5 class="mb-3">
            ⏳ Pembayaran Menunggu Verifikasi
        </h5>

        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead class="table-primary">

                    <tr>
                        <th>ID</th>
                        <th>Transaksi</th>
                        <th>Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Nominal</th>
                        <th>Metode</th>
                        <th>Bukti</th>
                        <th>Aksi</th>
                    </tr>

                </thead>

                <tbody>

                <?php if (mysqli_num_rows($menunggu) > 0): ?>

                    <?php while ($r = mysqli_fetch_assoc($menunggu)): ?>

                    <tr>

                        <td>
                            <?= $r['id_pembayaran'] ?>
                        </td>

                        <td>
                            #<?= $r['id_transaksi'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($r['nama']) ?>
                        </td>

                        <td>
                            <?= $r['tanggal_bayar'] ?>
                        </td>

                        <td class="fw-bold">
                            Rp<?= number_format(
                                $r['jumlah_bayar'],
                                0,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($r['metode']) ?>
                        </td>

                        <td>

                            <?php if (!empty($r['bukti_transfer'])): ?>

                                <a
                                    href="../pelanggan/<?= htmlspecialchars($r['bukti_transfer']) ?>"
                                    target="_blank">

                                    <img
                                        src="../pelanggan/<?= htmlspecialchars($r['bukti_transfer']) ?>"
                                        class="bukti"
                                        alt="Bukti Pembayaran">

                                </a>

                            <?php else: ?>

                                <span class="text-secondary">
                                    Tidak ada bukti (COD)
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <form
                                method="POST"
                                onsubmit="return confirm('Yakin memproses pembayaran ini?')">

                                <input
                                    type="hidden"
                                    name="id_pembayaran"
                                    value="<?= $r['id_pembayaran'] ?>">

                                <button
                                    type="submit"
                                    name="aksi"
                                    value="terima"
                                    class="btn btn-success btn-sm mb-1">

                                    ✓ Terima

                                </button>

                                <button
                                    type="submit"
                                    name="aksi"
                                    value="tolak"
                                    class="btn btn-danger btn-sm mb-1">

                                    ✕ Tolak

                                </button>

                            </form>

                        </td>

                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="8" class="text-center text-secondary">
                            Belum ada pembayaran yang menunggu verifikasi.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- RIWAYAT PEMBAYARAN -->
    <div class="card shadow-sm p-3">

        <h5 class="mb-3">
            📋 Riwayat Pembayaran
        </h5>

        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead class="table-primary">

                    <tr>
                        <th>ID</th>
                        <th>Transaksi</th>
                        <th>Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Nominal</th>
                        <th>Metode</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                <?php if (mysqli_num_rows($riwayat) > 0): ?>

                    <?php while ($r = mysqli_fetch_assoc($riwayat)): ?>

                    <tr>

                        <td>
                            <?= $r['id_pembayaran'] ?>
                        </td>

                        <td>
                            #<?= $r['id_transaksi'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($r['nama']) ?>
                        </td>

                        <td>
                            <?= $r['tanggal_bayar'] ?>
                        </td>

                        <td>
                            Rp<?= number_format(
                                $r['jumlah_bayar'],
                                0,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($r['metode']) ?>
                        </td>

                        <td>

                            <?php if ($r['status_verifikasi'] == 'Diterima'): ?>

                                <span class="badge bg-success">
                                    Diterima
                                </span>

                            <?php else: ?>

                                <span class="badge bg-danger">
                                    Ditolak
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="7" class="text-center text-secondary">
                            Belum ada riwayat pembayaran.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
