<?php
/**
 * FILE: admin/produk/hapus.php -- HAPUS PRODUK
 * produk yang sudah ada di riwayat pesanan tidak bisa dihapus (dilindungi database agar riwayat & laporan tetap utuh). Solusinya ubah statusnya jadi Nonaktif lewat Edit.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
need('produk');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $g = $pdo->prepare("SELECT gambar FROM produk WHERE id=?"); $g->execute([$id]); $gambar = $g->fetchColumn();
    try { $pdo->prepare("DELETE FROM produk WHERE id=?")->execute([$id]); hapus_gambar('produk', $gambar); set_flash('success', 'Produk dihapus.'); }
    catch (PDOException $e) { set_flash('danger', 'Produk tidak bisa dihapus karena sudah ada di riwayat pesanan. Ubah statusnya menjadi Nonaktif saja.'); }
}
redirect(ADMIN_URL . '/produk/index.php');
