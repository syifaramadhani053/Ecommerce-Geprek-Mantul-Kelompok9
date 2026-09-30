<?php
/**
 * FILE INI: PROSES TAMBAH KE KERANJANG
 */
require_once __DIR__ . '/../../config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produk_id = $_POST['produk_id'];
    $kuantitas = max(1, (int)$_POST['kuantitas']);
    $catatan = trim($_POST['catatan'] ?? '');

    $stmtProduk = $pdo->prepare("SELECT stok FROM produk WHERE id = ? AND status='aktif'");
    $stmtProduk->execute([$produk_id]);
    $produk = $stmtProduk->fetch();

    if (!$produk) {
        set_flash('danger', 'Menu tidak ditemukan.');
        redirect(BASE_URL . '/modules/katalog/index.php');
    }

    if ($produk['stok'] < $kuantitas) {
        set_flash('danger', 'Stok tidak mencukupi. Sisa stok: ' . $produk['stok']);
        redirect(BASE_URL . '/modules/katalog/detail.php?id=' . $produk_id);
    }

    $stmt = $pdo->prepare("INSERT INTO keranjang (pengguna_id, produk_id, kuantitas, catatan) VALUES (?,?,?,?)");
    $stmt->execute([$_SESSION['pengguna_id'], $produk_id, $kuantitas, $catatan]);

    set_flash('success', 'Menu berhasil ditambahkan ke keranjang.');
    redirect(BASE_URL . '/modules/cart/index.php');
}
redirect(BASE_URL . '/modules/katalog/index.php');
