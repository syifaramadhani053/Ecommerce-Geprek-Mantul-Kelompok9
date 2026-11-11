<?php
require __DIR__ . '/partials/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    try {
        $stmt = $conn->prepare('DELETE FROM produk WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $_SESSION['flash'] = 'Produk berhasil dihapus.';
    } catch (mysqli_sql_exception $e) {
        // Produk sudah pernah dipesan -> tidak bisa dihapus, cukup dinonaktifkan
        $stmt = $conn->prepare("UPDATE produk SET status = 'nonaktif' WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $_SESSION['flash'] = 'Produk sudah pernah dipesan, jadi tidak dihapus tapi dinonaktifkan.';
    }
}
header('Location: produk.php');
exit;
