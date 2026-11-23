
<?php
include 'config/koneksi.php';

$username = "admin";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$nama = "Administrator";

$sql = "INSERT INTO users (username, password, nama)
        VALUES ('$username', '$password', '$nama')";

if (mysqli_query($conn, $sql)) {
    echo "Admin berhasil dibuat!";
} else {
    echo "Gagal: " . mysqli_error($conn);
}
?>
