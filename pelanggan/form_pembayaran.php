
<?php
session_start();
include '../config/koneksi.php';

// CEK LOGIN
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$id_transaksi = (int) ($_GET['id'] ?? $_POST['id_transaksi'] ?? 0);

if ($id_transaksi <= 0) {
    die("ID transaksi tidak valid.");
}

// AMBIL DATA TRANSAKSI
$stmt = mysqli_prepare($conn, "
    SELECT t.*, p.nama_paket
    FROM transaksi t
    JOIN pelanggan pl ON t.id_pelanggan = pl.id_pelanggan
    JOIN paket_laundry p ON t.id_paket = p.id_paket
    WHERE t.id_transaksi = ? AND pl.id_user = ?
");

mysqli_stmt_bind_param($stmt, "ii", $id_transaksi, $id_user);
mysqli_stmt_execute($stmt);

$pesanan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$pesanan) {
    die("Pesanan tidak ditemukan.");
}

// HITUNG PEMBAYARAN
$stmt = mysqli_prepare($conn, "
    SELECT
        COALESCE(SUM(
            CASE
                WHEN status_verifikasi = 'Diterima'
                THEN jumlah_bayar
                ELSE 0
            END
        ), 0) AS dibayar,

        COALESCE(SUM(
            CASE
                WHEN status_verifikasi = 'Menunggu Verifikasi'
                THEN 1
                ELSE 0
            END
        ), 0) AS pending

    FROM pembayaran
    WHERE id_transaksi = ?
");

mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
mysqli_stmt_execute($stmt);

$hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$dibayar = (float) $hasil['dibayar'];
$pending = (int) $hasil['pending'];

$total_tagihan = (float) $pesanan['total_harga'];
$sisa = max(0, $total_tagihan - $dibayar);

$pesan_error = "";

// PROSES PEMBAYARAN
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($sisa <= 0) {
        $pesan_error = "Pesanan sudah lunas.";

    } elseif ($pending > 0) {
        $pesan_error = "Pembayaran sebelumnya masih menunggu verifikasi admin.";

    } else {

        $metode = $_POST['metode'] ?? '';

        $metode_valid = [
            'Transfer Bank',
            'QRIS',
            'COD'
        ];

        if (!in_array($metode, $metode_valid, true)) {

            $pesan_error = "Metode pembayaran tidak valid.";

        } else {

            $bukti = null;

            // PEMBAYARAN COD
            if ($metode == 'COD') {

                $jumlah = $sisa;

            } else {

                // TRANSFER BANK / QRIS
                $jumlah = $_POST['jumlah_bayar'] ?? 0;

                if (!is_numeric($jumlah) || $jumlah <= 0 || $jumlah > $sisa) {

                    $pesan_error = "Nominal pembayaran tidak valid.";

                }

                // VALIDASI BUKTI
                if (empty($pesan_error)) {

                    if (
                        !isset($_FILES['bukti']) ||
                        $_FILES['bukti']['error'] != UPLOAD_ERR_OK
                    ) {

                        $pesan_error = "Silakan upload bukti pembayaran.";

                    } elseif ($_FILES['bukti']['size'] > 2 * 1024 * 1024) {

                        $pesan_error = "Ukuran file maksimal 2 MB.";

                    } else {

                        $mime = mime_content_type($_FILES['bukti']['tmp_name']);

                        $format = [
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/webp' => 'webp'
                        ];

                        if (!isset($format[$mime])) {

                            $pesan_error = "Format bukti harus JPG, PNG, atau WEBP.";

                        } else {

                            $folder = __DIR__ . '/uploads/bukti_pembayaran/';

                            if (!is_dir($folder)) {
                                mkdir($folder, 0755, true);
                            }

                            $nama_file = bin2hex(random_bytes(16)) . '.' . $format[$mime];

                            if (move_uploaded_file(
                                $_FILES['bukti']['tmp_name'],
                                $folder . $nama_file
                            )) {

                                $bukti = 'uploads/bukti_pembayaran/' . $nama_file;

                            } else {

                                $pesan_error = "Gagal menyimpan bukti pembayaran.";
                            }
                        }
                    }
                }
            }

            // SIMPAN KE DATABASE
            if (empty($pesan_error)) {

                $stmt = mysqli_prepare($conn, "
                    INSERT INTO pembayaran (
                        id_transaksi,
                        tanggal_bayar,
                        jumlah_bayar,
                        metode,
                        bukti_transfer,
                        status_verifikasi
                    )
                    VALUES (
                        ?,
                        CURDATE(),
                        ?,
                        ?,
                        ?,
                        'Menunggu Verifikasi'
                    )
                ");

                mysqli_stmt_bind_param(
                    $stmt,
                    "idss",
                    $id_transaksi,
                    $jumlah,
                    $metode,
                    $bukti
                );

                if (mysqli_stmt_execute($stmt)) {

                    header("Location: pembayaran.php?berhasil=1");
                    exit;

                } else {

                    if ($bukti && file_exists(__DIR__ . '/' . $bukti)) {
                        unlink(__DIR__ . '/' . $bukti);
                    }

                    $pesan_error = "Gagal menyimpan pembayaran.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Form Pembayaran - LaundryKu</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #f4f6f9;
}

.card {
    border: none;
    border-radius: 12px;
}

.metode-box {
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 10px;
    cursor: pointer;
    margin-bottom: 10px;
    transition: 0.2s;
}

.metode-box:has(input:checked) {
    border: 2px solid #0d6efd;
    background: #eef5ff;
}

.metode-box:hover {
    background: #f0f5ff;
}
</style>

</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-primary p-3">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            🧺 LaundryKu
        </a>

        <a href="pembayaran.php" class="btn btn-light btn-sm">
            Kembali
        </a>
    </div>
</nav>

<div class="container py-5">

<div class="card shadow p-4 mx-auto" style="max-width:650px">

    <h3 class="mb-3">💳 Form Pembayaran</h3>

    <!-- PESAN ERROR -->
    <?php if ($pesan_error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($pesan_error) ?>
        </div>
    <?php endif; ?>

    <!-- INFORMASI TAGIHAN -->
    <div class="bg-light p-3 rounded mb-4">

        <p>
            ID Transaksi:
            <strong>#<?= $pesanan['id_transaksi'] ?></strong>
        </p>

        <p>
            Paket:
            <strong><?= htmlspecialchars($pesanan['nama_paket']) ?></strong>
        </p>

        <p>
            Total Tagihan:
            <strong>
                Rp<?= number_format($total_tagihan, 0, ',', '.') ?>
            </strong>
        </p>

        <p>
            Sudah Dibayar:
            <strong class="text-success">
                Rp<?= number_format($dibayar, 0, ',', '.') ?>
            </strong>
        </p>

        <hr>

        <h4 class="text-danger">
            Sisa Pembayaran:
            Rp<?= number_format($sisa, 0, ',', '.') ?>
        </h4>

    </div>

    <?php if ($sisa <= 0): ?>

        <div class="alert alert-success">
            Pesanan ini sudah lunas.
        </div>

    <?php elseif ($pending > 0): ?>

        <div class="alert alert-warning">
            Pembayaran sebelumnya sedang menunggu verifikasi admin.
            Silakan tunggu konfirmasi sebelum melakukan pembayaran berikutnya.
        </div>

    <?php else: ?>

    <!-- FORM PEMBAYARAN -->
    <form method="POST" enctype="multipart/form-data">

        <input
            type="hidden"
            name="id_transaksi"
            value="<?= $id_transaksi ?>"
        >

        <label class="form-label fw-bold">
            Pilih Metode Pembayaran
        </label>

        <label class="metode-box d-block">
            <input type="radio" name="metode" value="Transfer Bank" required>
            🏦 Transfer Bank
        </label>

        <label class="metode-box d-block">
            <input type="radio" name="metode" value="QRIS">
            📱 QRIS
        </label>

        <label class="metode-box d-block">
            <input type="radio" name="metode" value="COD">
            💵 COD (Bayar Saat Pengambilan)
        </label>

        <!-- INFORMASI TRANSFER -->
        <div id="transferInfo" class="alert alert-info">
            <strong>Informasi Pembayaran</strong>

            <p class="mb-0">
                Untuk Transfer Bank atau QRIS, gunakan rekening atau kode QRIS resmi yang diberikan oleh admin LaundryKu.
            </p>
        </div>

        <!-- NOMINAL -->
        <div id="nominalBox" class="mb-3">

            <label class="form-label fw-bold">
                Nominal Pembayaran
            </label>

            <input
                type="number"
                name="jumlah_bayar"
                class="form-control"
                min="1"
                max="<?= $sisa ?>"
                placeholder="Masukkan nominal pembayaran"
            >

            <small class="text-secondary">
                Maksimal Rp<?= number_format($sisa, 0, ',', '.') ?>
            </small>

        </div>

        <!-- BUKTI TRANSFER -->
        <div id="buktiBox" class="mb-3">

            <label class="form-label fw-bold">
                Upload Bukti Pembayaran
            </label>

            <input
                type="file"
                name="bukti"
                class="form-control"
                accept=".jpg,.jpeg,.png,.webp"
            >

            <small class="text-secondary">
                Format JPG, PNG, WEBP. Maksimal 2 MB.
            </small>

        </div>

        <!-- INFORMASI COD -->
        <div id="codInfo" class="alert alert-warning d-none">

            Pembayaran COD sebesar

            <strong>
                Rp<?= number_format($sisa, 0, ',', '.') ?>
            </strong>.

            Bayar kepada petugas saat mengambil laundry.

        </div>

        <button type="submit" class="btn btn-primary w-100 mt-3">
            Kirim Pembayaran
        </button>

    </form>

    <?php endif; ?>

</div>
</div>

<script>

const metode = document.querySelectorAll('input[name="metode"]');

const nominalBox = document.getElementById('nominalBox');
const buktiBox = document.getElementById('buktiBox');
const transferInfo = document.getElementById('transferInfo');
const codInfo = document.getElementById('codInfo');

function aturMetode() {

    const pilihan = document.querySelector(
        'input[name="metode"]:checked'
    );

    if (!pilihan) return;

    const cod = pilihan.value === 'COD';

    nominalBox.classList.toggle('d-none', cod);
    buktiBox.classList.toggle('d-none', cod);
    transferInfo.classList.toggle('d-none', cod);
    codInfo.classList.toggle('d-none', !cod);

    document.querySelector('[name="jumlah_bayar"]').required = !cod;
    document.querySelector('[name="bukti"]').required = !cod;
}

metode.forEach(input => {
    input.addEventListener('change', aturMetode);
});

</script>

</body>
</html>
