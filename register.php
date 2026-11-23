
<?php
include 'config/koneksi.php';

$pesan = "";

if (isset($_POST['daftar'])) {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $hp = trim($_POST['no_hp']);
    $alamat = trim($_POST['alamat']);

    $cek = mysqli_prepare($conn,
        "SELECT id_user FROM users WHERE username=?");
    mysqli_stmt_bind_param($cek, "s", $username);
    mysqli_stmt_execute($cek);

    $hasil = mysqli_stmt_get_result($cek);

    if (mysqli_num_rows($hasil) > 0) {
        $pesan = "Username sudah digunakan!";
    } else {
        mysqli_begin_transaction($conn);

        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn,
                "INSERT INTO users (username,password,nama,role)
                 VALUES (?, ?, ?, 'pelanggan')");

            mysqli_stmt_bind_param(
                $stmt, "sss", $username, $hash, $nama
            );
            mysqli_stmt_execute($stmt);

            $id_user = mysqli_insert_id($conn);

            $stmt2 = mysqli_prepare($conn,
                "INSERT INTO pelanggan
                (id_user,nama,no_hp,alamat)
                VALUES (?, ?, ?, ?)");

            mysqli_stmt_bind_param(
                $stmt2, "isss",
                $id_user, $nama, $hp, $alamat
            );
            mysqli_stmt_execute($stmt2);

            mysqli_commit($conn);

            header("Location: login.php");
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $pesan = "Pendaftaran gagal. Silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar LaundryKu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container vh-100 d-flex justify-content-center align-items-center">

<div class="card shadow p-4" style="width:420px">

<h3 class="text-center">🧺 LaundryKu</h3>
<p class="text-center text-muted">Daftar Akun Pelanggan</p>

<?php if ($pesan): ?>
<div class="alert alert-danger">
<?= htmlspecialchars($pesan) ?>
</div>
<?php endif; ?>

<form method="POST">

<label>Nama Lengkap</label>
<input type="text" name="nama" class="form-control mb-3" required>

<label>Username</label>
<input type="text" name="username" class="form-control mb-3" required>

<label>Password</label>
<input type="password" name="password" class="form-control mb-3" minlength="6" required>

<label>Nomor HP</label>
<input type="text" name="no_hp" class="form-control mb-3" required>

<label>Alamat</label>
<textarea name="alamat" class="form-control mb-3" required></textarea>

<button type="submit" name="daftar" class="btn btn-primary w-100">
Daftar Sekarang
</button>

</form>

<p class="text-center mt-3">
Sudah punya akun? <a href="login.php">Login</a>
</p>

</div>
</div>

</body>
</html>
