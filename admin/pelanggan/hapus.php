<?php
/** FILE: admin/pelanggan/hapus.php -- HAPUS PELANGGAN. 
 * pelanggan yang punya pesanan TIDAK boleh dihapus (riwayat transaksi & laporan keuangan akan hilang). Gunakan status Diblokir. */
require_once __DIR__ . '/../includes/bootstrap.php';
need('pelanggan');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id']; $c = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE pengguna_id=?"); $c->execute([$id]);
    if ($c->fetchColumn() > 0) set_flash('danger', 'Pelanggan memiliki riwayat pesanan sehingga tidak bisa dihapus. Ubah statusnya menjadi Diblokir lewat Edit.');
    else { $pdo->prepare("DELETE FROM pengguna WHERE id=? AND peran='pelanggan'")->execute([$id]); set_flash('success', 'Pelanggan dihapus.'); }
}
redirect(ADMIN_URL . '/pelanggan/index.php');
