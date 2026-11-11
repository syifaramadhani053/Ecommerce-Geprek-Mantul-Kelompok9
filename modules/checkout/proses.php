<?php
/**
 * =====================================================================
 * FILE INI: PROSES CHECKOUT (BAGIAN PALING PENTING - INTI ERP)
 * =====================================================================
 * Mendukung 3 metode pembayaran:
 *   - online        : Midtrans Snap (QRIS & Virtual Account/Bank)
 *   - transfer_bank : transfer manual
 *   - COD           : bayar di tempat
 */
require_once __DIR__ . '/../../config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/modules/cart/index.php');
}

$pengguna_id = $_SESSION['pengguna_id'];

// Mode debug: jika MIDTRANS_DEBUG bernilai true di config.php, pesan error asli
// dari Midtrans / PHP ikut ditampilkan di layar. WAJIB false saat produksi.
$debug = defined('MIDTRANS_DEBUG') && MIDTRANS_DEBUG;

// 0) Bersihkan pesanan online yang sudah kedaluwarsa agar stoknya kembali tersedia
//    sebelum validasi stok di bawah. Kegagalan di sini tidak boleh menghalangi checkout.
try {
    midtrans_expire_stale_orders($pdo, null, 5);
} catch (Throwable $e) {
    error_log('[MIDTRANS] expire_stale_orders gagal: ' . $e->getMessage());
}

// $pengguna_id = $_SESSION['pengguna_id'];

$stmt = $pdo->prepare("SELECT c.*, p.nama_produk, p.harga_jual, p.harga_beli, p.stok
    FROM keranjang c JOIN produk p ON c.produk_id = p.id WHERE c.pengguna_id = ?");
$stmt->execute([$pengguna_id]);
$items = $stmt->fetchAll();

if (empty($items)) {
    redirect(BASE_URL . '/modules/cart/index.php');
}

// Validasi ulang stok (jaga-jaga jika stok berubah sejak halaman checkout dibuka)
foreach ($items as $it) {
    if ($it['stok'] < $it['kuantitas']) {
        set_flash('danger', 'Stok "' . $it['nama_produk'] . '" tidak mencukupi (sisa ' . $it['stok'] . ').');
        redirect(BASE_URL . '/modules/cart/index.php');
    }
}

// Validasi metode pembayaran (whitelist)
$metode = $_POST['metode_pembayaran'] ?? 'transfer_bank';
if (!in_array($metode, ['online', 'transfer_bank', 'COD'], true)) {
    $metode = 'transfer_bank';
}
$isOnline = ($metode === 'online');

$subtotal = 0;
foreach ($items as $it) $subtotal += $it['harga_jual'] * $it['kuantitas'];

// Validasi ulang kode promo di server (jangan percaya potongan dari sisi klien)
$kodePromo = trim($_POST['kode_promo'] ?? '');
$potongan = 0;
$promo = null;
if ($kodePromo !== '') {
    $stmtPromo = $pdo->prepare("SELECT * FROM promo WHERE kode_promo = ? AND status='aktif' AND CURDATE() BETWEEN tanggal_mulai AND tanggal_berakhir");
    $stmtPromo->execute([$kodePromo]);
    $promo = $stmtPromo->fetch();
    if ($promo && $subtotal >= $promo['minimal_belanja']) {
        $potongan = hitung_potongan_promo($promo, $subtotal);
    } else {
        $kodePromo = null; // kode tidak valid / minimal belanja tidak terpenuhi, abaikan
    }
}

$ongkir = ONGKIR_DEFAULT;
$totalBayar = $subtotal - $potongan + $ongkir;
$noPesanan = generate_no_pesanan();

// Data pelanggan untuk Midtrans (email diambil dari akun)
$stmtUser = $pdo->prepare("SELECT nama_lengkap, email, no_telp FROM pengguna WHERE id = ?");
$stmtUser->execute([$pengguna_id]);
$user = $stmtUser->fetch();

// order_id Midtrans dibuat unik di setiap percobaan checkout
$orderIdMidtrans = $isOnline ? midtrans_make_order_id($noPesanan) : null;

// // Ambil dan validasi metode pembayaran
// $metodePembayaran = $_POST['metode_pembayaran'] ?? '';

// if (!in_array($metodePembayaran, ['transfer_bank', 'COD'], true)) {
//     set_flash('danger', 'Metode pembayaran tidak valid.');
//     redirect(BASE_URL . '/modules/cart/index.php');
// }

// // Tentukan status berdasarkan metode pembayaran
// if ($metodePembayaran === 'COD') {
//     $statusPesanan = 'diproses';
// } else {
//     $statusPesanan = 'menunggu_pembayaran';
// }

// $statusPembayaran = 'belum_lunas';


try {
    $pdo->beginTransaction();

    // 1) Simpan header pesanan
    
$stmtPesanan = $pdo->prepare("INSERT INTO pesanan
        (no_pesanan, pengguna_id, total_belanja, kode_promo, potongan_promo, ongkir, total_bayar,
         metode_pembayaran, status_midtrans, order_id_midtrans,
         nama_penerima, no_telp_penerima, alamat_pengiriman, catatan)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmtPesanan->execute([
        $noPesanan, $pengguna_id, $subtotal, $kodePromo, $potongan, $ongkir, $totalBayar,
        $metode, ($isOnline ? 'pending' : null), $orderIdMidtrans,
        clean($_POST['nama_penerima']), clean($_POST['no_telp_penerima']),
        clean($_POST['alamat_pengiriman']), clean($_POST['catatan'] ?? '')
    ]);

    $pesanan_id = $pdo->lastInsertId();

    $stmtDetail = $pdo->prepare("INSERT INTO detail_pesanan
        (pesanan_id, produk_id, kuantitas, harga_satuan, hpp_satuan, subtotal) VALUES (?,?,?,?,?,?)");

    // $stmtUpdateStok = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
    // Guard "AND stok >= ?" mencegah stok menjadi minus jika ada 2 checkout bersamaan
    $stmtUpdateStok = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ? AND stok >= ?");

    $stmtMutasi = $pdo->prepare("INSERT INTO mutasi_stok
        (produk_id, jenis_mutasi, jumlah, stok_sebelum, stok_sesudah, nomor_referensi, keterangan, pengguna_id)
        VALUES (?, 'keluar_penjualan', ?, ?, ?, ?, ?, ?)");

    foreach ($items as $it) {
        // 2) Simpan rincian item pesanan
        $stmtDetail->execute([
            $pesanan_id, $it['produk_id'], $it['kuantitas'],
            $it['harga_jual'], $it['harga_beli'], $it['harga_jual'] * $it['kuantitas']
        ]);

        // 3) Kurangi stok produk
        $stokSebelum = $it['stok'];
        $stokSesudah = $stokSebelum - $it['kuantitas'];
        $stmtUpdateStok->execute([$it['kuantitas'], $it['produk_id'], $it['kuantitas']]);
        if ($stmtUpdateStok->rowCount() === 0) {
            throw new Exception('Stok "' . $it['nama_produk'] . '" tidak mencukupi.');
        }

        // 4) Catat mutasi stok (audit trail ERP)
        $keterangan = $isOnline ? 'Penjualan online (menunggu pembayaran Midtrans)' : 'Penjualan online melalui checkout';
        $stmtMutasi->execute([
            $it['produk_id'], $it['kuantitas'], $stokSebelum, $stokSesudah,
            $noPesanan, $keterangan, $pengguna_id
        ]);
    }

    // 5) Khusus metode online: buat Snap Token di Midtrans & simpan ke database.
    //    Jika Midtrans gagal, exception dilempar dan SEMUA langkah di atas di-rollback
    //    (pesanan tidak jadi dibuat, stok tidak berkurang, keranjang tetap utuh).
    if ($isOnline) {
        $itemsMidtrans = [];
        foreach ($items as $it) {
            $itemsMidtrans[] = [
                'id'       => 'P' . $it['produk_id'],
                'price'    => (int) round($it['harga_jual']),
                'quantity' => (int) $it['kuantitas'],
                'name'     => mb_substr($it['nama_produk'], 0, 50),
            ];
        }
        if ($potongan > 0) {
            $itemsMidtrans[] = [
                'id'       => 'PROMO',
                'price'    => -((int) round($potongan)),
                'quantity' => 1,
                'name'     => mb_substr('Potongan Promo ' . $kodePromo, 0, 50),
            ];
        }
        $itemsMidtrans[] = [
            'id'       => 'ONGKIR',
            'price'    => (int) $ongkir,
            'quantity' => 1,
            'name'     => 'Ongkos Kirim',
        ];

        $params = midtrans_build_snap_params(
            $orderIdMidtrans,
            (int) round($totalBayar),
            $itemsMidtrans,
            [
                'nama'  => mb_substr(trim($_POST['nama_penerima'] ?? $user['nama_lengkap']), 0, 100),
                'email' => $user['email'],
                'telp'  => trim($_POST['no_telp_penerima'] ?? $user['no_telp']),
            ]
        );

        $snap = midtrans_create_snap_token($params);

        $pdo->prepare("UPDATE pesanan SET snap_token = ? WHERE id = ?")
            ->execute([$snap['token'], $pesanan_id]);
    }

    // 5) Kosongkan keranjang
    $pdo->prepare("DELETE FROM keranjang WHERE pengguna_id = ?")->execute([$pengguna_id]);

    $pdo->commit();
    
if ($isOnline) {
        // Arahkan ke halaman pembayaran (popup Snap)
        redirect(BASE_URL . '/modules/checkout/bayar.php?id=' . $pesanan_id);
    }

    set_flash('success', "Pesanan berhasil dibuat dengan nomor $noPesanan. Silakan lakukan pembayaran sesuai metode yang dipilih.");
    redirect(BASE_URL . '/modules/akun/riwayat_pesanan.php');

} catch (MidtransException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[MIDTRANS] Gagal membuat Snap Token: ' . $e->getMessage());
    if ($debug) {
        // Contoh isi: "MIDTRANS_SERVER_KEY belum diisi...", "cURL #60: SSL certificate problem...",
        // "Midtrans menolak permintaan (HTTP 401): ...", dst.
        set_flash('danger', '[DEBUG Midtrans] ' . $e->getMessage());
    } else {
        set_flash('danger', 'Gagal membuat transaksi pembayaran online. Silakan coba lagi beberapa saat atau pilih metode pembayaran lain.');
    }
    redirect(BASE_URL . '/modules/checkout/index.php');

} catch (Throwable $e) {
    // Throwable menangkap Exception DAN Error PHP (mis. fungsi mb_substr tidak ada,
    // TypeError, dsb.) yang tidak tertangkap oleh catch (Exception).
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[CHECKOUT] ' . get_class($e) . ': ' . $e->getMessage()
        . ' di ' . $e->getFile() . ':' . $e->getLine());

    $pesan = 'Gagal memproses pesanan: ' . $e->getMessage();
    if ($debug) {
        $pesan .= ' (' . get_class($e) . ' di ' . basename($e->getFile()) . ':' . $e->getLine() . ')';
    }
    set_flash('danger', $pesan);
    redirect(BASE_URL . '/modules/cart/index.php');
}
