<?php
require __DIR__ . '/partials/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare("UPDATE pengguna SET status_aktif = 1 - status_aktif WHERE id = ? AND peran = 'pelanggan'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $_SESSION['flash'] = 'Status pelanggan diperbarui.';
}
header('Location: pelanggan.php');
exit;