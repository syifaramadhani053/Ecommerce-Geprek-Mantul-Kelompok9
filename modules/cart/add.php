<?php
/**
 * FILE INI: PROSES TAMBAH KE KERANJANG
 */
require_once __DIR__ . '/../../config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produk_id = (int) ($_POST['produk_id'] ?? 0);
    $kuantitas = max(1, (int) ($_POST['kuantitas'] ?? 1));
    $catatan = trim($_POST['catatan'] ?? '');
    $varian = trim($_POST['varian'] ?? '');

    $stmtProduk = $pdo->prepare("
        SELECT p.stok, k.nama_kategori
        FROM produk p
        JOIN kategori k ON p.kategori_id = k.id
        WHERE p.id = ? AND p.status = 'aktif'
    ");
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

    $kategoriAyam = ['Menu Utama', 'Paket Hemat'];
    if (in_array($produk['nama_kategori'], $kategoriAyam, true)) {
        $varianValid = ['Dada', 'Paha Atas', 'Paha Bawah', 'Sayap'];

        if (!in_array($varian, $varianValid, true)) {
            set_flash('danger', 'Silakan pilih bagian ayam terlebih dahulu.');
            redirect(BASE_URL . '/modules/katalog/detail.php?id=' . $produk_id);
        }
    } else {
        $varian = '';
    }

    // Simpan produk beserta pilihan ayam dan catatan
    $stmt = $pdo->prepare("
    INSERT INTO keranjang
        (pengguna_id, produk_id, kuantitas, varian, catatan)
    VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['pengguna_id'],
        $produk_id,
        $kuantitas,
        $varian !== '' ? $varian : null,
        $catatan
    ]);


    set_flash('success', 'Menu berhasil ditambahkan ke keranjang.');
    redirect(BASE_URL . '/modules/cart/index.php');
}
redirect(BASE_URL . '/modules/katalog/index.php');
