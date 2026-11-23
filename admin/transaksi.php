
<?php
session_start();
include '../config/koneksi.php';

// CEK LOGIN ADMIN
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$pesan = "";

// TAMBAH TRANSAKSI
if (isset($_POST['tambah'])) {

    $id_pelanggan = (int) $_POST['id_pelanggan'];
    $id_paket = (int) $_POST['id_paket'];
    $berat = (float) $_POST['berat'];

    // AMBIL HARGA PAKET
    $stmt = mysqli_prepare($conn, "
        SELECT harga_per_kg
        FROM paket_laundry
        WHERE id_paket = ?
    ");

    mysqli_stmt_bind_param($stmt, "i", $id_paket);
    mysqli_stmt_execute($stmt);

    $paket_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($id_pelanggan <= 0 || $berat <= 0 || !$paket_data) {

        $pesan = "Data transaksi tidak valid!";

    } else {

        $total = $berat * $paket_data['harga_per_kg'];
        $tanggal = date('Y-m-d');

        $stmt = mysqli_prepare($conn, "
            INSERT INTO transaksi
            (
                id_pelanggan,
                id_paket,
                tanggal,
                berat,
                total_harga,
                status
            )
            VALUES (?, ?, ?, ?, ?, 'Diproses')
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "iisdd",
            $id_pelanggan,
            $id_paket,
            $tanggal,
            $berat,
            $total
        );

        if (mysqli_stmt_execute($stmt)) {

            header("Location: transaksi.php?berhasil=tambah");
            exit;

        } else {

            $pesan = "Gagal menyimpan transaksi!";
        }
    }
}

// UBAH STATUS PENGERJAAN LAUNDRY
if (isset($_POST['ubah_status'])) {

    $id = (int) $_POST['id_transaksi'];
    $status = $_POST['status'];

    $daftar_status = [
        'Diproses',
        'Dicuci',
        'Siap Diambil',
        'Selesai'
    ];

    if ($id > 0 && in_array($status, $daftar_status, true)) {

        $stmt = mysqli_prepare($conn, "
            UPDATE transaksi
            SET status = ?
            WHERE id_transaksi = ?
        ");

        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        mysqli_stmt_execute($stmt);

        header("Location: transaksi.php?berhasil=ubah");
        exit;

    } else {

        $pesan = "Status transaksi tidak valid!";
    }
}

// AMBIL DATA PELANGGAN
$pelanggan = mysqli_query($conn, "
    SELECT *
    FROM pelanggan
    ORDER BY nama ASC
");

// AMBIL DATA PAKET
$paket = mysqli_query($conn, "
    SELECT *
    FROM paket_laundry
    ORDER BY nama_paket ASC
");

// AMBIL TRANSAKSI DAN HITUNG PEMBAYARAN
$data = mysqli_query($conn, "

    SELECT
        t.*,
        p.nama,
        pk.nama_paket,

        COALESCE(pb.dibayar, 0) AS dibayar,

        CASE
            WHEN COALESCE(pb.dibayar, 0) <= 0
                THEN 'Belum Dibayar'

            WHEN COALESCE(pb.dibayar, 0) < t.total_harga
                THEN 'Belum Lunas'

            ELSE 'Lunas'
        END AS status_pembayaran

    FROM transaksi t

    JOIN pelanggan p
        ON t.id_pelanggan = p.id_pelanggan

    JOIN paket_laundry pk
        ON t.id_paket = pk.id_paket

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

    ORDER BY t.id_transaksi DESC

");

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Transaksi LaundryKu</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body {
    background: #f4f6f9;
}

.card {
    border: none;
    border-radius: 12px;
}

.table th,
.table td {
    vertical-align: middle;
}

.status-badge {
    font-size: 12px;
    padding: 7px 10px;
}

</style>

</head>

<body>

<div class="container py-4">

    <h2>🧺 Transaksi LaundryKu</h2>

    <a href="dashboard.php" class="btn btn-secondary mb-3">
        Kembali
    </a>

    <!-- PESAN -->
    <?php if (isset($_GET['berhasil'])): ?>

        <div class="alert alert-success">
            <?php if ($_GET['berhasil'] == 'tambah'): ?>
                Transaksi berhasil ditambahkan.
            <?php elseif ($_GET['berhasil'] == 'ubah'): ?>
                Status laundry berhasil diperbarui.
            <?php endif; ?>
        </div>

    <?php endif; ?>

    <?php if ($pesan): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($pesan) ?>
        </div>

    <?php endif; ?>


    <!-- FORM TAMBAH TRANSAKSI -->
    <div class="card p-3 mb-4 shadow-sm">

        <h5>Tambah Transaksi</h5>

        <form method="POST">

            <label class="form-label">
                Pilih Pelanggan
            </label>

            <select name="id_pelanggan" class="form-select mb-3" required>

                <option value="">
                    -- Pilih Pelanggan --
                </option>

                <?php while ($p = mysqli_fetch_assoc($pelanggan)): ?>

                    <option value="<?= $p['id_pelanggan'] ?>">
                        <?= htmlspecialchars($p['nama']) ?>
                    </option>

                <?php endwhile; ?>

            </select>


            <label class="form-label">
                Pilih Paket Laundry
            </label>

            <select name="id_paket" class="form-select mb-3" required>

                <option value="">
                    -- Pilih Paket --
                </option>

                <?php while ($pk = mysqli_fetch_assoc($paket)): ?>

                    <option value="<?= $pk['id_paket'] ?>">

                        <?= htmlspecialchars($pk['nama_paket']) ?>

                        - Rp<?= number_format(
                            $pk['harga_per_kg'],
                            0,
                            ',',
                            '.'
                        ) ?>/KG

                    </option>

                <?php endwhile; ?>

            </select>


            <label class="form-label">
                Berat Cucian (KG)
            </label>

            <input
                type="number"
                name="berat"
                class="form-control mb-3"
                min="0.1"
                step="0.1"
                required
            >

            <button
                type="submit"
                name="tambah"
                class="btn btn-primary"
            >
                Tambah Transaksi
            </button>

        </form>

    </div>


    <!-- DAFTAR TRANSAKSI -->
    <div class="card p-3 shadow-sm">

        <h5>Daftar Transaksi</h5>

        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead class="table-primary">

                    <tr>
                        <th>ID</th>
                        <th>Pelanggan</th>
                        <th>Paket</th>
                        <th>Tanggal</th>
                        <th>Berat</th>
                        <th>Total Tagihan</th>
                        <th>Sudah Dibayar</th>
                        <th>Status Laundry</th>
                        <th>Status Pembayaran</th>
                        <th>Aksi</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($data && mysqli_num_rows($data) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($data)): ?>

                    <tr>

                        <td>
                            #<?= $row['id_transaksi'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['nama']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['nama_paket']) ?>
                        </td>

                        <td>
                            <?= $row['tanggal'] ?>
                        </td>

                        <td>
                            <?= $row['berat'] ?> KG
                        </td>

                        <td>
                            Rp<?= number_format(
                                $row['total_harga'],
                                0,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td class="text-success fw-bold">
                            Rp<?= number_format(
                                $row['dibayar'],
                                0,
                                ',',
                                '.'
                            ) ?>
                        </td>


                        <!-- STATUS LAUNDRY -->
                        <td>

                            <?php
                            $warna_laundry = [
                                'Diproses' => 'primary',
                                'Dicuci' => 'info',
                                'Siap Diambil' => 'warning',
                                'Selesai' => 'success'
                            ];

                            $warna = $warna_laundry[$row['status']] ?? 'secondary';
                            ?>

                            <span class="badge bg-<?= $warna ?> status-badge">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>

                        </td>


                        <!-- STATUS PEMBAYARAN -->
                        <td>

                            <?php if ($row['status_pembayaran'] == 'Lunas'): ?>

                                <span class="badge bg-success status-badge">
                                    ✓ Lunas
                                </span>

                            <?php elseif ($row['status_pembayaran'] == 'Belum Lunas'): ?>

                                <span class="badge bg-warning text-dark status-badge">
                                    Belum Lunas
                                </span>

                            <?php else: ?>

                                <span class="badge bg-danger status-badge">
                                    Belum Dibayar
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- AKSI -->
                        <td>

                            <form method="POST" class="mb-2">

                                <input
                                    type="hidden"
                                    name="id_transaksi"
                                    value="<?= $row['id_transaksi'] ?>"
                                >

                                <select
                                    name="status"
                                    class="form-select form-select-sm mb-2"
                                >

                                    <?php foreach (
                                        ['Diproses', 'Dicuci', 'Siap Diambil', 'Selesai']
                                        as $s
                                    ): ?>

                                        <option
                                            value="<?= $s ?>"
                                            <?= $row['status'] == $s ? 'selected' : '' ?>
                                        >
                                            <?= $s ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <button
                                    type="submit"
                                    name="ubah_status"
                                    class="btn btn-success btn-sm w-100"
                                >
                                    Ubah Status
                                </button>

                            </form>

                            <a
                                href="nota.php?id=<?= $row['id_transaksi'] ?>"
                                class="btn btn-primary btn-sm w-100"
                            >
                                Cetak Nota
                            </a>

                        </td>

                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="10" class="text-center">
                            Belum ada transaksi.
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
