<?php
/**
 * =====================================================================
 * FILE INI: PROSES CHECKOUT (BAGIAN PALING PENTING - INTI ERP)
 * =====================================================================
 */
require_once __DIR__ . '/../../config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/modules/cart/index.php');
}

$pengguna_id = $_SESSION['pengguna_id'];

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
    if ($promo) {
        $potongan = hitung_potongan_promo($promo, $subtotal);
    } else {
        $kodePromo = null; // kode tidak valid, abaikan
    }
}

$ongkir = ONGKIR_DEFAULT;
$totalBayar = $subtotal - $potongan + $ongkir;
$noPesanan = generate_no_pesanan();

try {
    $pdo->beginTransaction();

    // 1) Simpan header pesanan
    $stmtPesanan = $pdo->prepare("INSERT INTO pesanan
        (no_pesanan, pengguna_id, total_belanja, kode_promo, potongan_promo, ongkir, total_bayar,
         metode_pembayaran, nama_penerima, no_telp_penerima, alamat_pengiriman, catatan)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmtPesanan->execute([
        $noPesanan, $pengguna_id, $subtotal, $kodePromo, $potongan, $ongkir, $totalBayar,
        clean($_POST['metode_pembayaran']), clean($_POST['nama_penerima']),
        clean($_POST['no_telp_penerima']), clean($_POST['alamat_pengiriman']), clean($_POST['catatan'] ?? '')
    ]);
    $pesanan_id = $pdo->lastInsertId();

    $stmtDetail = $pdo->prepare("INSERT INTO detail_pesanan
        (pesanan_id, produk_id, kuantitas, harga_satuan, hpp_satuan, subtotal) VALUES (?,?,?,?,?,?)");

    $stmtUpdateStok = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");

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
        $stmtUpdateStok->execute([$it['kuantitas'], $it['produk_id']]);

        // 4) Catat mutasi stok (audit trail ERP)
        $stmtMutasi->execute([
            $it['produk_id'], $it['kuantitas'], $stokSebelum, $stokSesudah,
            $noPesanan, 'Penjualan online melalui checkout', $pengguna_id
        ]);
    }

    // 5) Kosongkan keranjang
    $pdo->prepare("DELETE FROM keranjang WHERE pengguna_id = ?")->execute([$pengguna_id]);

    $pdo->commit();
    set_flash('success', "Pesanan berhasil dibuat dengan nomor $noPesanan. Silakan lakukan pembayaran sesuai metode yang dipilih.");
    redirect(BASE_URL . '/modules/akun/riwayat_pesanan.php');

} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('danger', 'Gagal memproses pesanan: ' . $e->getMessage());
    redirect(BASE_URL . '/modules/cart/index.php');
}
