
<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama']);
    $hp = trim($_POST['no_hp']);
    $alamat = trim($_POST['alamat']);

    $stmt = mysqli_prepare($conn,
        "INSERT INTO pelanggan (nama, no_hp, alamat) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $nama, $hp, $alamat);
    mysqli_stmt_execute($stmt);

    header("Location: pelanggan.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    $stmt = mysqli_prepare($conn,
        "DELETE FROM pelanggan WHERE id_pelanggan = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    header("Location: pelanggan.php");
    exit;
}

if (isset($_POST['edit'])) {
    $id = (int) $_POST['id'];
    $nama = trim($_POST['nama']);
    $hp = trim($_POST['no_hp']);
    $alamat = trim($_POST['alamat']);

    $stmt = mysqli_prepare($conn,
        "UPDATE pelanggan SET nama=?, no_hp=?, alamat=? WHERE id_pelanggan=?");
    mysqli_stmt_bind_param($stmt, "sssi", $nama, $hp, $alamat, $id);
    mysqli_stmt_execute($stmt);

    header("Location: pelanggan.php");
    exit;
}

$edit = null;

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM pelanggan WHERE id_pelanggan=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$data = mysqli_query($conn, "SELECT * FROM pelanggan ORDER BY id_pelanggan DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Pelanggan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h2>🧺 Data Pelanggan LaundryKu</h2>
    <a href="dashboard.php" class="btn btn-secondary mb-3">Kembali</a>

    <div class="card p-3 mb-4">
        <h5><?= $edit ? 'Edit Pelanggan' : 'Tambah Pelanggan' ?></h5>

        <form method="POST">
            <input type="hidden" name="id" value="<?= $edit['id_pelanggan'] ?? '' ?>">

            <input class="form-control mb-2" name="nama"
                placeholder="Nama pelanggan"
                value="<?= htmlspecialchars($edit['nama'] ?? '') ?>" required>

            <input class="form-control mb-2" name="no_hp"
                placeholder="Nomor HP"
                value="<?= htmlspecialchars($edit['no_hp'] ?? '') ?>" required>

            <textarea class="form-control mb-2" name="alamat"
                placeholder="Alamat" required><?= htmlspecialchars($edit['alamat'] ?? '') ?></textarea>

            <button class="btn btn-primary" name="<?= $edit ? 'edit' : 'tambah' ?>">
                <?= $edit ? 'Simpan Perubahan' : 'Tambah Pelanggan' ?>
            </button>

            <?php if ($edit): ?>
                <a href="pelanggan.php" class="btn btn-secondary">Batal</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card p-3">
        <h5>Daftar Pelanggan</h5>
        <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>No HP</th>
                    <th>Alamat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= $row['id_pelanggan'] ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['no_hp']) ?></td>
                    <td><?= htmlspecialchars($row['alamat']) ?></td>
                    <td>
                        <a href="?edit=<?= $row['id_pelanggan'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="?hapus=<?= $row['id_pelanggan'] ?>"
                           onclick="return confirm('Hapus pelanggan ini?')"
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
                                                                    