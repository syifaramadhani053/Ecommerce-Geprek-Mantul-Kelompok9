<?php
/**
 * FILE: admin/includes/bootstrap.php  -- PINTU MASUK SEMUA HALAMAN ADMIN
 */
if (!is_file(__DIR__ . '/../../config/config.php')) {
    die('<h3>Folder admin belum diletakkan dengan benar</h3><p>Folder ini harus berada <b>sejajar</b> dengan folder <code>config/</code>, <code>includes/</code>, dan <code>modules/</code> milik situs user.</p>');
}
require_once __DIR__ . '/../../config/config.php';       // koneksi $pdo, BASE_URL (milik situs user)

define('ADMIN_DIR', basename(dirname(__DIR__)));
define('ADMIN_URL', BASE_URL . '/' . ADMIN_DIR);
// Untuk menyimpan lokasi gambar produk/kategori
define('UPLOAD_FS',  dirname(__DIR__, 2) . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');

// true = pesanan Transfer Bank baru boleh diproses setelah pembayaran dikonfirmasi.
define('WAJIB_LUNAS_TRANSFER', true);

// Pengaman: bila tabel tambahan belum dibuat, tampilkan petunjuk (bukan halaman kosong/error)
if (!defined('ADMIN_SKIP_CHECK')) {
    $wajib = ['hak_akses', 'pengaturan', 'pengiriman', 'pembayaran', 'metode_pembayaran', 'pengembalian', 'crm_leads', 'kunjungan'];
    $ada = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()")->fetchAll(PDO::FETCH_COLUMN);
    $kurang = array_diff($wajib, array_map('strtolower', $ada));
    if ($kurang) {
        die('<div style="font-family:Arial;max-width:640px;margin:60px auto;line-height:1.7"><h2>Database admin belum disiapkan</h2><p>Tabel berikut belum ada: <code>' . implode(', ', $kurang) . '</code>.</p>'
          . '<p>Buka <a href="' . ADMIN_URL . '/setup.php"><b>setup.php</b></a> satu kali, lalu muat ulang halaman ini. Butuh bantuan? Buka <a href="' . ADMIN_URL . '/cek.php">cek.php</a>.</p></div>');
    }
}
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/modul.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/keuangan.php';
require_once __DIR__ . '/logika.php';
require_once __DIR__ . '/layout.php';

// Perlindungan CSRF: semua form POST wajib membawa token dari csrf()
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $t = $_POST['_csrf'] ?? '';
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
        http_response_code(419);
        die('Sesi formulir kedaluwarsa. Silakan kembali, muat ulang halaman, lalu coba lagi.');
    }
}
