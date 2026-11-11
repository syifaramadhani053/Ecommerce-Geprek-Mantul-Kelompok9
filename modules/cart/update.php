<?php
require_once __DIR__ . '/../../config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $pengguna_id = (int) $_SESSION['pengguna_id'];

    // Pastikan item keranjang milik pengguna yang sedang login
    $stmt = $pdo->prepare("
        SELECT c.id, c.produk_id, c.kuantitas, c.varian, p.stok, k.nama_kategori
        FROM keranjang c
        JOIN produk p ON c.produk_id = p.id
        JOIN kategori k ON p.kategori_id = k.id
        WHERE c.id = ? AND c.pengguna_id = ?
    ");
    $stmt->execute([$id, $pengguna_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        set_flash('danger', 'Item keranjang tidak ditemukan.');
        redirect(BASE_URL . '/modules/cart/index.php');
    }

    // Jika form mengirim kuantitas, perbarui kuantitas
    if (isset($_POST['kuantitas'])) {
        $kuantitas = max(1, (int) $_POST['kuantitas']);
        if ($kuantitas > (int) $item['stok']) {
            set_flash('danger', 'Kuantitas melebihi stok tersedia: ' . $item['stok']);
            redirect(BASE_URL . '/modules/cart/index.php');
        }
        $stmt = $pdo->prepare("
            UPDATE keranjang
            SET kuantitas = ?
            WHERE id = ? AND pengguna_id = ?
        ");
        $stmt->execute([$kuantitas, $id, $pengguna_id]);
    }

    // Jika form mengirim varian, perbarui pilihan ayam
    if (isset($_POST['varian'])) {
        $varian = trim($_POST['varian']);
        $kategoriAyam = ['Menu Utama', 'Paket Hemat'];
        $pilihanValid = ['Dada', 'Paha Atas', 'Paha Bawah', 'Sayap'];

        if (
            !in_array($item['nama_kategori'], $kategoriAyam, true) ||
            !in_array($varian, $pilihanValid, true)
        ) {
            set_flash('danger', 'Pilihan ayam tidak valid.');
            redirect(BASE_URL . '/modules/cart/index.php');
        }

        $stmt = $pdo->prepare("
            UPDATE keranjang
            SET varian = ?
            WHERE id = ? AND pengguna_id = ?
        ");
        $stmt->execute([$varian, $id, $pengguna_id]);
    }

    set_flash('success', 'Keranjang berhasil diperbarui.');
    redirect(BASE_URL . '/modules/cart/index.php');
}
redirect(BASE_URL . '/modules/cart/index.php');
