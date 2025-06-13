<?php
require_once 'auth_check.php';
// requireAdmin(); // Aktifkan jika hanya admin yang boleh menghapus
require_once '../config/database.php';
$user_id_to_delete = $_GET['id'] ?? null;
if (!$user_id_to_delete || !filter_var(
    $user_id_to_delete,
    FILTER_VALIDATE_INT
)) {
    $_SESSION['message'] = "ID User tidak valid untuk dihapus.";
    $_SESSION['message_type'] = "error";
    header("Location: index.php");
    exit();
}
// Sangat penting: Jangan biarkan user menghapus dirinya sendiri!
if (
    isset($_SESSION['user_id']) && $_SESSION['user_id'] ==
    $user_id_to_delete
) {
    $_SESSION['message'] = "Anda tidak dapat menghapus akun Anda sendiri.";
    $_SESSION['message_type'] = "warning"; // atau "error"
    header("Location: index.php");
    exit();
}
// Opsional: Jika ada aturan lain, misalnya admin terakhir tidak bolehdihapus.
// try {
// $stmt_count_admin = $conn->prepare("SELECT COUNT(*) as admin_countFROM users WHERE role = 'admin'");
// $stmt_count_admin->execute();
// $admin_info = $stmt_count_admin->fetch(PDO::FETCH_ASSOC);
// $stmt_user_to_delete_role = $conn->prepare("SELECT role FROM usersWHERE id = :id");
// $stmt_user_to_delete_role->bindParam(':id', $user_id_to_delete,PDO::PARAM_INT);
// $stmt_user_to_delete_role->execute();
// $user_to_delete_info = $stmt_user_to_delete_role->fetch(PDO::FETCH_ASSOC);
// if ($user_to_delete_info && $user_to_delete_info['role'] === 'admin'&& $admin_info['admin_count'] <= 1) {
// $_SESSION['message'] = "Tidak dapat menghapus admin terakhir.";
// $_SESSION['message_type'] = "error";
// header("Location: index.php");
// exit();
// }
// } catch (PDOException $e) {
// $_SESSION['message'] = "Error saat memeriksa status admin: " . $e->getMessage();
// $_SESSION['message_type'] = "error";
// header("Location: index.php");
// exit();
// }
try {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id_to_delete, PDO::PARAM_INT);
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            $_SESSION['message'] = "User berhasil dihapus!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "User tidak ditemukan atau sudah
dihapus.";
            $_SESSION['message_type'] = "warning";
        }
    } else {
        $_SESSION['message'] = "Gagal menghapus user.";
        $_SESSION['message_type'] = "error";
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Error database: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}
header("Location: index.php");
exit();
