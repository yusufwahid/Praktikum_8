<?php
require_once 'auth_check.php';
// requireAdmin(); // Aktifkan jika hanya admin yang boleh mengedit user
require_once '../config/database.php';
$errors = [];
$user_id = $_GET['id'] ?? null;
if (!$user_id || !filter_var($user_id, FILTER_VALIDATE_INT)) {
    $_SESSION['message'] = "ID User tidak valid.";
    $_SESSION['message_type'] = "error";
    header("Location: index.php");
    exit();
}
// Ambil data user yang akan diedit
try {
    $stmt = $conn->prepare("SELECT id, username, nama_lengkap, email, role
FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $_SESSION['message'] = "User tidak ditemukan.";
        $_SESSION['message_type'] = "error";
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Error mengambil data user: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header("Location: index.php");
    exit();
}
// Inisialisasi variabel form dengan data yang ada
$username_form = $user['username'];
$nama_lengkap_form = $user['nama_lengkap'];
$email_form = $user['email'];
$role_form = $user['role'];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_form = trim($_POST['username']);
    $password_new = $_POST['password']; // Password baru, opsional
    $konfirmasi_password_new = $_POST['konfirmasi_password'];
    $nama_lengkap_form = trim($_POST['nama_lengkap']);
    $email_form = trim($_POST['email']);
    $role_form = $_POST['role'] ?? 'user';
    // Validasi
    if (empty($username_form)) {
        $errors[] = "Username wajib diisi.";
    }
    if (empty($nama_lengkap_form)) {
        $errors[] = "Nama lengkap wajib diisi.";
    }
    if (empty($email_form) || !filter_var(
        $email_form,
        FILTER_VALIDATE_EMAIL
    )) {
        $errors[] = "Email tidak valid.";
    }
    if (!empty($password_new) && $password_new !== $konfirmasi_password_new) {
        $errors[] = "Konfirmasi password baru tidak cocok.";
    }
    if (!empty($password_new) && strlen($password_new) < 6) {
        $errors[] =
            "Password baru minimal 6 karakter.";
    }
    if (!in_array($role_form, ['admin', 'user'])) {
        $errors[] = "Role tidak
valid.";
    }
    // Cek duplikasi username/email (kecuali untuk user ini sendiri)
    if (empty($errors)) {
        try {
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE
(username = :username OR email = :email) AND id != :id_current LIMIT 1");
            $stmt_check->bindParam(':username', $username_form);
            $stmt_check->bindParam(':email', $email_form);
            $stmt_check->bindParam(':id_current', $user_id, PDO::PARAM_INT);
            $stmt_check->execute();
            if ($stmt_check->rowCount() > 0) {
                $errors[] = "Username atau Email baru sudah digunakan oleh
user lain.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error saat memeriksa duplikasi: " . $e->getMessage();
        }
    }
    if (empty($errors)) {
        try {
            // Bangun query update
            $sql_update = "UPDATE users SET username = :username,
nama_lengkap = :nama_lengkap, email = :email, role = :role";
            $params_update = [
                ':username' => $username_form,
                ':nama_lengkap' => $nama_lengkap_form,
                ':email' => $email_form,
                ':role' => $role_form,
                ':id' => $user_id
            ];
            // Jika password baru diisi, update juga passwordnya
            if (!empty($password_new)) {
                $hashed_password_new = password_hash(
                    $password_new,
                    PASSWORD_BCRYPT
                );
                $sql_update .= ", password = :password";
                $params_update[':password'] = $hashed_password_new;
            }
            $sql_update .= " WHERE id = :id";
            $stmt_update = $conn->prepare($sql_update);
            if ($stmt_update->execute($params_update)) {
                $_SESSION['message'] = "Data user berhasil diperbarui!";
                $_SESSION['message_type'] = "success";
                // Jika user yang sedang login mengedit profilnya sendiri,update session
                if ($_SESSION['user_id'] == $user_id) {
                    $_SESSION['username'] = $username_form;
                    $_SESSION['nama_lengkap'] = $nama_lengkap_form;
                    $_SESSION['role'] = $role_form;
                }
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Gagal memperbarui data user.";
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
    <title>Edit User: <?php echo htmlspecialchars($user['username']);
                        ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>

<body>
    <div class="container">
        <div class="header-nav">
            <h2>Edit User: <?php echo htmlspecialchars($user['username']);
                            ?></h2>
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
        <form action="edit.php?id=<?php echo $user_id; ?>" method="post">
            <div>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php
                                                                        echo htmlspecialchars($username_form); ?>" required>
            </div>
            <div>
                <label for="password">Password Baru (Opsional):</label>
                <input type="password" id="password" name="password">
                <small>Kosongkan jika tidak ingin mengubah password.</small>
            </div>
            <div>
                <label for="konfirmasi_password">Konfirmasi Password Baru
                    (Opsional):</label>
                <input type="password" id="konfirmasi_password" name="konfirmasi_password">
            </div>
            <div>
                <label for="nama_lengkap">Nama Lengkap:</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($nama_lengkap_form); ?>" required>
            </div>
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo
                                                                    htmlspecialchars($email_form); ?>" required>
            </div>
            <div>
                <label for="role">Role:</label>
                <select id="role" name="role">
                    <option value="user" <?php echo ($role_form === 'user' ?
                                                'selected' : ''); ?>>User</option>
                    <option value="admin" <?php echo ($role_form === 'admin'
                                                ? 'selected' : ''); ?>>Admin</option>
                </select>
            </div>
            <div>
                <button type="submit">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</body>

</html>