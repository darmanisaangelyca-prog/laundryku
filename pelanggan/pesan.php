
<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'pelanggan') {
    header("Location: ../login.php");
    exit;
}

// Cari data pelanggan berdasarkan akun login
$id_user = $_SESSION['id_user'];

$stmt = mysqli_prepare($conn,
    "SELECT * FROM pelanggan WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);

$pelanggan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$pelanggan) {
    die("Data pelanggan belum terhubung dengan akun Anda.");
}

$pesan = "";

// Proses pemesanan
if (isset($_POST['pesan'])) {
    $id_paket = (int) $_POST['id_paket'];
    $berat = (float) $_POST['berat'];

    // Ambil harga paket dari database
    $stmt = mysqli_prepare($conn,
        "SELECT harga_per_kg FROM paket_laundry WHERE id_paket = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_paket);
    mysqli_stmt_execute($stmt);

    $paket = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$paket || $berat <= 0 || $berat > 100) {
        $pesan = "Pilihan paket atau berat cucian tidak valid!";
    } else {
        $total = $berat * $paket['harga_per_kg'];
        $tanggal = date('Y-m-d');
        $id_pelanggan = $pelanggan['id_pelanggan'];

        $stmt = mysqli_prepare($conn,
            "INSERT INTO transaksi
            (id_pelanggan, id_paket, tanggal, berat, total_harga, status)
            VALUES (?, ?, ?, ?, ?, 'Diproses')");

        mysqli_stmt_bind_param(
            $stmt, "iisdd",
            $id_pelanggan, $id_paket, $tanggal, $berat, $total
        );

        if (mysqli_stmt_execute($stmt)) {
            header("Location: riwayat.php?berhasil=1");
            exit;
        } else {
            $pesan = "Pesanan gagal disimpan!";
        }
    }
}

// Ambil paket laundry
$paket = mysqli_query($conn,
    "SELECT * FROM paket_laundry ORDER BY nama_paket ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pesan Laundry</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-primary p-3">
    <div class="container">
        <a href="dashboard.php" class="navbar-brand">🧺 LaundryKu</a>
        <a href="dashboard.php" class="btn btn-light btn-sm">Kembali</a>
    </div>
</nav>

<div class="container py-5">

<div class="card shadow-sm p-4 mx-auto" style="max-width:600px">

<h3 class="mb-2">Pesan Laundry</h3>

<p class="text-muted">
Pelanggan: <?= htmlspecialchars($pelanggan['nama']) ?>
</p>

<?php if ($pesan): ?>
<div class="alert alert-danger">
<?= htmlspecialchars($pesan) ?>
</div>
<?php endif; ?>

<form method="POST">

<label class="form-label">Pilih Paket Laundry</label>

<select name="id_paket" id="paket"
class="form-select mb-3" required>

<option value="">-- Pilih Paket --</option>

<?php while ($p = mysqli_fetch_assoc($paket)): ?>

<option value="<?= $p['id_paket'] ?>"
data-harga="<?= $p['harga_per_kg'] ?>">

<?= htmlspecialchars($p['nama_paket']) ?>
- Rp<?= number_format($p['harga_per_kg'], 0, ',', '.') ?>/KG

</option>

<?php endwhile; ?>

</select>

<label class="form-label">Berat Cucian (KG)</label>

<input type="number" name="berat" id="berat"
class="form-control mb-3"
min="0.1" max="100" step="0.1" required>

<div class="alert alert-info">
    <strong>Estimasi Harga:</strong>
    <h4 id="total">Rp0</h4>
</div>

<button type="submit" name="pesan"
class="btn btn-primary w-100">

Pesan Sekarang

</button>

</form>

</div>
</div>

<script>
const paket = document.getElementById('paket');
const berat = document.getElementById('berat');
const total = document.getElementById('total');

function hitungHarga() {
    const pilihan = paket.options[paket.selectedIndex];
    const harga = Number(pilihan.dataset.harga || 0);
    const kg = Number(berat.value || 0);

    const hasil = harga * kg;

    total.innerText = 'Rp' +
        hasil.toLocaleString('id-ID');
}

paket.addEventListener('change', hitungHarga);
berat.addEventListener('input', hitungHarga);
</script>

</body>
</html>
