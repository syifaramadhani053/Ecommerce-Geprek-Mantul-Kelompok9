<?php
require_once __DIR__ . '/../../config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $kuantitas = max(1, (int)$_POST['kuantitas']);

    $stmt = $pdo->prepare("UPDATE keranjang SET kuantitas = ? WHERE id = ? AND pengguna_id = ?");
    $stmt->execute([$kuantitas, $id, $_SESSION['pengguna_id']]);

    set_flash('success', 'Keranjang diperbarui.');
}
redirect(BASE_URL . '/modules/cart/index.php');
