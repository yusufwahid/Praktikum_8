<?php
require_once 'auth_check.php';
// requireAdmin(); // Aktifkan jika hanya admin yang boleh menambah user
require_once '../config/database.php';
$errors = [];
$username = $nama_lengkap = $email = $role = ""; // Inisialisasi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $role = $_POST['role'] ?? 'user';
    // Validasi serupa dengan register.php
    if (empty($username)) {
        $errors[] = "Username wajib diisi.";
    }
    if (empty($password)) {
        $errors[] = "Password wajib diisi.";
    }
    if ($password !== $konfirmasi_password) {
        $errors[] = "Konfirmasi
password tidak cocok.";
    }
    if (empty($nama_lengkap)) {
        $errors[] = "Nama lengkap wajib diisi.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email tidak valid.";
    }
    if (!in_array($role, ['admin', 'user'])) {
        $errors[] = "Role tidak
valid.";
    }
    // Cek duplikasi username/email
    if (empty($errors)) {
        try {
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE username
= :username OR email = :email LIMIT 1");
            $stmt_check->bindParam(':username', $username);
            $stmt_check->bindParam(':email', $email);
            $stmt_check->execute();
            if ($stmt_check->rowCount() > 0) {
                $errors[] = "Username atau Email sudah terdaftar.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error saat memeriksa data: " . $e->getMessage();
        }
    }
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, password,
nama_lengkap, email, role) VALUES (:username, :password, :nama_lengkap,
:email, :role)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':nama_lengkap', $nama_lengkap);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':role', $role);
            if ($stmt->execute()) {
                $_SESSION['message'] = "User baru berhasil ditambahkan!";
                $_SESSION['message_type'] = "success";
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Gagal menambahkan user.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error database: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User Baru</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>

<body>
    <div class="container">
        <div class="header-nav">
            <h2>Tambah User Baru</h2>
            <div>
                <a href="index.php">Kembali ke Daftar User</a> |
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
        <?php if (!empty($errors)) : ?>
            <div class="errors">
                <?php foreach ($errors as $error) : ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form action="create.php" method="post">
            <div>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php
                                                                        echo htmlspecialchars($username); ?>" required>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <label for="konfirmasi_password">Konfirmasi Password:</label>
                <input type="password" id="konfirmasi_password" name="konfirmasi_password" required>
            </div>
            <div>
                <label for="nama_lengkap">Nama Lengkap:</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($nama_lengkap); ?>" required>
            </div>
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo
                                                                    htmlspecialchars($email); ?>" required>
            </div>
            <div>
                <label for="role">Role:</label>
                <select id="role" name="role">
                    <option value="user" <?php echo ($role === 'user' ?
                                                'selected' : ''); ?>>User</option>
                    <option value="admin" <?php echo ($role === 'admin' ?
                                                'selected' : ''); ?>>Admin</option>
                </select>
            </div>
            <div>
                <button type="submit">Tambah User</button>
            </div>
        </form>
    </div>
</body>

</html>