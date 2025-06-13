<?php
// config/database.php
$host = "localhost"; // Atau alamat IP server database Anda
$db_name = "db_app_user"; // Nama database yang telah Anda buat
$username_db = "root"; // Username database Anda (default XAMPP adalah"root")
$password_db = ""; // Password database Anda (default XAMPP kosong)
try {
    $conn = new PDO(
        "mysql:host={$host};dbname={$db_name}",
        $username_db,
        $password_db
    );
    // Set mode error PDO ke exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Koneksi ke database berhasil!"; // Hapus atau beri komentar setelah tes
} catch (PDOException $exception) {
    // Tampilkan pesan error jika koneksi gagal
    die("Koneksi error: " . $exception->getMessage());
}
