<?php
require_once __DIR__ . '/../../config/config.php';
require_login();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND pengguna_id = ?");
$stmt->execute([$id, $_SESSION['pengguna_id']]);

set_flash('success', 'Item dihapus dari keranjang.');
redirect(BASE_URL . '/modules/cart/index.php');
