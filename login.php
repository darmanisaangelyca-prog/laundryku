
<?php
session_start();
include 'config/koneksi.php';

$error = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn,
        "SELECT * FROM users WHERE username = ?");

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($hasil);

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: pelanggan/dashboard.php");
        }
        exit;

    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login LaundryKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container vh-100 d-flex justify-content-center align-items-center">
    <div class="card shadow p-4" style="width:380px">
        <h3 class="text-center">🧺 LaundryKu</h3>
        <p class="text-center text-muted">Silakan Login</p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label>Username</label>
            <input type="text" name="username" class="form-control mb-3" required>

            <label>Password</label>
            <input type="password" name="password" class="form-control mb-3" required>

            <button name="login" class="btn btn-primary w-100">
                Login
            </button>
        </form>

        <p class="text-center mt-3">
            Belum punya akun?
            <a href="register.php">Daftar di sini</a>
        </p>
    </div>
</div>

</body>
</html>
