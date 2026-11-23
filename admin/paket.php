
<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// Tambah paket
if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama_paket']);
    $harga = (float) $_POST['harga_per_kg'];
    $estimasi = (int) $_POST['estimasi_hari'];

    $stmt = mysqli_prepare($conn,
        "INSERT INTO paket_laundry (nama_paket, harga_per_kg, estimasi_hari)
         VALUES (?, ?, ?)");

    mysqli_stmt_bind_param($stmt, "sdi", $nama, $harga, $estimasi);
    mysqli_stmt_execute($stmt);

    header("Location: paket.php");
    exit;
}

// Edit paket
if (isset($_POST['edit'])) {
    $id = (int) $_POST['id'];
    $nama = trim($_POST['nama_paket']);
    $harga = (float) $_POST['harga_per_kg'];
    $estimasi = (int) $_POST['estimasi_hari'];

    $stmt = mysqli_prepare($conn,
        "UPDATE paket_laundry
         SET nama_paket=?, harga_per_kg=?, estimasi_hari=?
         WHERE id_paket=?");

    mysqli_stmt_bind_param($stmt, "sdii", $nama, $harga, $estimasi, $id);
    mysqli_stmt_execute($stmt);

    header("Location: paket.php");
    exit;
}

// Hapus paket
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    $stmt = mysqli_prepare($conn,
        "DELETE FROM paket_laundry WHERE id_paket=?");

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    header("Location: paket.php");
    exit;
}

// Ambil data untuk edit
$edit = null;

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    $stmt = mysqli_prepare($conn,
        "SELECT * FROM paket_laundry WHERE id_paket=?");

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

// Tampilkan semua paket
$data = mysqli_query($conn,
    "SELECT * FROM paket_laundry ORDER BY id_paket DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Paket Laundry</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

    <h2>🧺 Paket LaundryKu</h2>

    <a href="dashboard.php" class="btn btn-secondary mb-3">
        Kembali
    </a>

    <div class="card p-3 mb-4 shadow-sm">

        <h5><?= $edit ? 'Edit Paket' : 'Tambah Paket' ?></h5>

        <form method="POST">

            <input type="hidden" name="id"
                value="<?= $edit['id_paket'] ?? '' ?>">

            <input type="text" name="nama_paket"
                class="form-control mb-2"
                placeholder="Nama paket"
                value="<?= htmlspecialchars($edit['nama_paket'] ?? '') ?>"
                required>

            <input type="number" name="harga_per_kg"
                class="form-control mb-2"
                placeholder="Harga per KG"
                min="1" step="0.01"
                value="<?= $edit['harga_per_kg'] ?? '' ?>"
                required>

            <input type="number" name="estimasi_hari"
                class="form-control mb-2"
                placeholder="Estimasi hari"
                min="1"
                value="<?= $edit['estimasi_hari'] ?? '' ?>"
                required>

            <button class="btn btn-primary"
                name="<?= $edit ? 'edit' : 'tambah' ?>">
                <?= $edit ? 'Simpan Perubahan' : 'Tambah Paket' ?>
            </button>

            <?php if ($edit): ?>
                <a href="paket.php" class="btn btn-secondary">Batal</a>
            <?php endif; ?>

        </form>
    </div>

    <div class="card p-3 shadow-sm">

        <h5>Daftar Paket Laundry</h5>

        <div class="table-responsive">
        <table class="table table-bordered table-striped">

            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Nama Paket</th>
                    <th>Harga/KG</th>
                    <th>Estimasi</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

            <?php while ($row = mysqli_fetch_assoc($data)): ?>

                <tr>
                    <td><?= $row['id_paket'] ?></td>

                    <td><?= htmlspecialchars($row['nama_paket']) ?></td>

                    <td>Rp <?= number_format($row['harga_per_kg'], 0, ',', '.') ?></td>

                    <td><?= $row['estimasi_hari'] ?> hari</td>

                    <td>
                        <a href="?edit=<?= $row['id_paket'] ?>"
                            class="btn btn-warning btn-sm">Edit</a>

                        <a href="?hapus=<?= $row['id_paket'] ?>"
                            onclick="return confirm('Hapus paket ini?')"
                            class="btn btn-danger btn-sm">Hapus</a>
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
