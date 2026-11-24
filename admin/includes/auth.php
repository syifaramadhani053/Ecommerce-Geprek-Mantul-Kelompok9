<?php
/**
 * =====================================================================
 * FILE: admin/includes/auth.php -- LOGIN & HAK AKSES ADMIN
 * =====================================================================
 * BOLEH DIEDIT : MENU_LIST (nama menu) dan HAK_DEFAULT (hak akses awal
 *                bila tabel hak_akses masih kosong).
 * JANGAN DIUBAH: need(), can(). Inilah yang mencegah orang tanpa login
 *                atau tanpa izin membuka halaman admin.
 * Hak akses sekarang diatur lewat menu "Pengguna > Hak Akses" (disimpan
 * di tabel hak_akses). Superadmin SELALU punya akses penuh.
 * =====================================================================
 */
const MENU_LIST = [
    'dashboard' => 'Dashboard', 'pesanan' => 'Pesanan', 'produk' => 'Produk', 'pelanggan' => 'Pelanggan',
    'kategori' => 'Kategori', 'stok' => 'Stok', 'pengiriman' => 'Pengiriman', 'pembayaran' => 'Pembayaran',
    'pengembalian' => 'Pengembalian & Penukaran', 'keuangan' => 'Laporan Keuangan', 'analitik' => 'Analitik & Statistik',
    'crm' => 'CRM', 'promo' => 'Promo', 'faq' => 'FAQ', 'kontak' => 'Pesan Masuk',
];
// Menu khusus superadmin (tidak bisa dibagikan): pengguna, pengaturan

const HAK_DEFAULT = [
    'gudang' => ['dashboard', 'produk', 'kategori', 'stok', 'pengiriman', 'pengembalian'],
    'kasir'  => ['dashboard', 'pesanan', 'pelanggan', 'pembayaran', 'pengiriman', 'pengembalian', 'kontak', 'crm'],
];

function admin_login() { return isset($_SESSION['admin_id']); }

function can($menu) {
    global $pdo; static $cache = null;
    // if (!admin_login()) return false;
    if (!admin_login() || !modul_aktif($menu)) return false;
    $peran = $_SESSION['admin_peran'];
    if ($peran === 'superadmin') return true;
    if ($menu === 'pengguna' || $menu === 'pengaturan') return false;
    if ($cache === null) {
        try {
            $s = $pdo->prepare("SELECT menu FROM hak_akses WHERE peran = ?"); $s->execute([$peran]);
            $cache = $s->fetchAll(PDO::FETCH_COLUMN);
            if (!$cache && !$pdo->query("SELECT COUNT(*) FROM hak_akses WHERE peran='$peran'")->fetchColumn()) $cache = HAK_DEFAULT[$peran] ?? [];
        } catch (Exception $e) { $cache = HAK_DEFAULT[$peran] ?? []; }
    }
    return in_array($menu, $cache, true);
}

// Panggil di baris pertama setiap halaman admin: need('pesanan');
function need($menu) {
    if (!admin_login()) redirect(ADMIN_URL . '/login.php');
    if (!modul_aktif($menu)) { set_flash('danger', 'Modul ini belum tersedia.'); redirect(ADMIN_URL . '/dashboard.php'); }
    if (!can($menu)) { set_flash('danger', 'Anda tidak memiliki akses ke menu tersebut.'); redirect(ADMIN_URL . '/dashboard.php'); }
}
