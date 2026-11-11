<?php
/**
 * =====================================================================
 * FILE INI: HALAMAN PEMBAYARAN ONLINE (Midtrans Snap Pop-up)
 * =====================================================================
 * Dibuka dari:
 *   - proses.php            (setelah pesanan online dibuat)
 *   - riwayat_pesanan.php   (tombol "Bayar Sekarang")
 */
require_once __DIR__ . '/../../config/config.php';
require_login();
$pageTitle = "Pembayaran";

$pesananId  = (int) ($_GET['id'] ?? 0);
$penggunaId = $_SESSION['pengguna_id'];
$urlRiwayat = BASE_URL . '/modules/akun/riwayat_pesanan.php';

// Batalkan pesanan online milik pengguna ini yang sudah melewati batas waktu bayar
try {
    midtrans_expire_stale_orders($pdo, (int) $penggunaId, 3);
} catch (Throwable $e) {
    error_log('[MIDTRANS] expire_stale_orders gagal: ' . $e->getMessage());
}

$stmt = $pdo->prepare("SELECT * FROM pesanan WHERE id = ? AND pengguna_id = ?");
$stmt->execute([$pesananId, $penggunaId]);
$pesanan = $stmt->fetch();

if (!$pesanan || $pesanan['metode_pembayaran'] !== 'online') {
    set_flash('danger', 'Pesanan tidak ditemukan atau bukan pesanan pembayaran online.');
    redirect($urlRiwayat);
}

// Sinkronkan dulu dengan Midtrans (berguna jika webhook belum/tidak masuk, mis. di localhost)
if (in_array($pesanan['status_midtrans'], ['pending', 'deny'], true)) {
    if (midtrans_sync_order($pdo, $pesanan) === 'updated') {
        $stmt->execute([$pesananId, $penggunaId]);
        $pesanan = $stmt->fetch();
    }
}

if ($pesanan['status_midtrans'] === 'settlement') {
    set_flash('success', 'Pesanan ' . $pesanan['no_pesanan'] . ' sudah dibayar. Terima kasih!');
    redirect($urlRiwayat . '?detail=' . $pesanan['id']);
}

if (in_array($pesanan['status_midtrans'], ['cancel', 'expire'], true) || $pesanan['status_pesanan'] === 'dibatalkan') {
    set_flash('danger', 'Pesanan ' . $pesanan['no_pesanan'] . ' sudah dibatalkan / kedaluwarsa, sehingga tidak bisa dibayar lagi. Silakan buat pesanan baru.');
    redirect($urlRiwayat);
}

if (empty($pesanan['snap_token'])) {
    set_flash('danger', 'Token pembayaran untuk pesanan ini tidak tersedia. Silakan buat pesanan baru atau hubungi admin.');
    redirect($urlRiwayat);
}

$stmtItems = $pdo->prepare("SELECT dp.*, p.nama_produk
    FROM detail_pesanan dp JOIN produk p ON dp.produk_id = p.id
    WHERE dp.pesanan_id = ?");
$stmtItems->execute([$pesanan['id']]);
$detailItems = $stmtItems->fetchAll();

$batasBayar = strtotime($pesanan['dibuat_pada']) + (MIDTRANS_EXPIRY_MINUTES * 60);

include __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-title">Pembayaran Pesanan</h1>

<div id="statusBox" role="status" style="display:none;padding:14px 18px;border-radius:10px;margin-bottom:16px;font-size:14px;font-weight:600;"></div>

<div class="panel" style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:20px;">
  <h3>Pesanan <?= clean($pesanan['no_pesanan']) ?></h3>
  <p style="margin-top:10px;">Tanggal: <?= date('d M Y H:i', strtotime($pesanan['tanggal_pesanan'])) ?></p>
  <p>Penerima: <?= clean($pesanan['nama_penerima']) ?> (<?= clean($pesanan['no_telp_penerima']) ?>)</p>
  <p>Alamat: <?= clean($pesanan['alamat_pengiriman']) ?></p>
  <p>Metode: <?= clean(midtrans_nama_metode($pesanan['metode_pembayaran'])) ?></p>
  <p>Status Pembayaran: <?= midtrans_badge_pembayaran($pesanan) ?></p>
  <p style="margin-top:8px;color:#c0392b;font-weight:600;">
    Selesaikan pembayaran sebelum <?= date('d M Y H:i', $batasBayar) ?> WIB, atau pesanan akan dibatalkan otomatis.
  </p>
</div>

<div class="table-wrap">
  <table>
    <thead><tr><th>Menu</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
    <tbody>
      <?php foreach ($detailItems as $it): ?>
      <tr>
        <td><?= clean($it['nama_produk']) ?></td>
        <td><?= $it['kuantitas'] ?></td>
        <td><?= rupiah($it['harga_satuan']) ?></td>
        <td><?= rupiah($it['subtotal']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="cart-summary">
  <div class="row-line"><span>Subtotal</span><span><?= rupiah($pesanan['total_belanja']) ?></span></div>
  <?php if ($pesanan['potongan_promo'] > 0): ?>
    <div class="row-line" style="color:#1c7c41;"><span>Potongan Promo<?= $pesanan['kode_promo'] ? ' (' . clean($pesanan['kode_promo']) . ')' : '' ?></span><span>- <?= rupiah($pesanan['potongan_promo']) ?></span></div>
  <?php endif; ?>
  <div class="row-line"><span>Ongkos Kirim</span><span><?= rupiah($pesanan['ongkir']) ?></span></div>
  <div class="total-row"><span>Total Bayar</span><span><?= rupiah($pesanan['total_bayar']) ?></span></div>
  <button type="button" id="btnBayar" class="btn-primary" style="width:100%">Bayar Sekarang</button>
  <p style="text-align:center;margin-top:12px;">
    <a href="<?= $urlRiwayat ?>">Bayar nanti, lihat Pesanan Saya</a>
  </p>
</div>

<script src="<?= clean(midtrans_snap_js_url()) ?>" data-client-key="<?= clean(MIDTRANS_CLIENT_KEY) ?>"></script>
<script>
(function () {
  var SNAP_TOKEN  = <?= json_encode($pesanan['snap_token'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var URL_RIWAYAT = <?= json_encode($urlRiwayat . '?detail=' . $pesanan['id'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  var tombol = document.getElementById('btnBayar');
  var kotak  = document.getElementById('statusBox');
  var sedangBuka = false;

  var WARNA = {
    success: ['#d4f4dd', '#1c7c41'],
    info:    ['#e1f0fb', '#1a5f8f'],
    warning: ['#fff3cd', '#8a6d00'],
    danger:  ['#fde2e1', '#c0392b']
  };

  function tampil(tipe, teks) {
    kotak.style.display = 'block';
    kotak.style.background = WARNA[tipe][0];
    kotak.style.color = WARNA[tipe][1];
    kotak.textContent = teks;
  }

  function aktifkanTombol(aktif) {
    tombol.disabled = !aktif;
    tombol.style.opacity = aktif ? '1' : '0.6';
    tombol.style.cursor = aktif ? 'pointer' : 'not-allowed';
  }

  function keRiwayat(param, tunda) {
    setTimeout(function () {
      window.location.href = URL_RIWAYAT + '&bayar=' + param;
    }, tunda);
  }

  function bayarSekarang() {
    if (sedangBuka) return;

    if (typeof window.snap === 'undefined') {
      tampil('danger', 'Gagal memuat sistem pembayaran Midtrans. Periksa koneksi internet lalu muat ulang halaman ini.');
      return;
    }

    sedangBuka = true;
    aktifkanTombol(false);

    window.snap.pay(SNAP_TOKEN, {
      onSuccess: function (result) {
        tampil('success', 'Pembayaran berhasil! Mengalihkan ke halaman pesanan Anda...');
        keRiwayat('sukses', 1500);
      },
      onPending: function (result) {
        tampil('info', 'Pesanan dibuat. Silakan selesaikan pembayaran sesuai instruksi yang diberikan. Mengalihkan...');
        keRiwayat('pending', 1500);
      },
      onError: function (result) {
        sedangBuka = false;
        aktifkanTombol(true);
        tampil('danger', 'Pembayaran gagal diproses. Silakan klik "Bayar Sekarang" untuk mencoba lagi atau pilih metode lain.');
      },
      onClose: function () {
        sedangBuka = false;
        aktifkanTombol(true);
        tampil('warning', 'Anda menutup jendela pembayaran sebelum selesai. Klik "Bayar Sekarang" untuk melanjutkan, atau selesaikan nanti lewat menu Pesanan Saya.');
      }
    });
  }

  tombol.addEventListener('click', bayarSekarang);

  // Buka popup otomatis saat halaman selesai dimuat
  window.addEventListener('load', bayarSekarang);
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
