<?php
require_once __DIR__ . '/../../config/config.php';
require_login();
$pageTitle = "Pesanan Saya";

$stmt = $pdo->prepare("SELECT * FROM pesanan WHERE pengguna_id = ? ORDER BY dibuat_pada DESC");
$stmt->execute([$_SESSION['pengguna_id']]);
$pesananList = $stmt->fetchAll();

$detailId = $_GET['detail'] ?? null;
$detailItems = [];
$detailPesanan = null;
if ($detailId) {
    $stmt2 = $pdo->prepare("SELECT * FROM pesanan WHERE id=? AND pengguna_id=?");
    $stmt2->execute([$detailId, $_SESSION['pengguna_id']]);
    $detailPesanan = $stmt2->fetch();
    if ($detailPesanan) {
        $stmt3 = $pdo->prepare("SELECT dp.*, p.nama_produk FROM detail_pesanan dp JOIN produk p ON dp.produk_id=p.id WHERE dp.pesanan_id = ?");
        $stmt3->execute([$detailId]);
        $detailItems = $stmt3->fetchAll();
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-title">Pesanan Saya</h1>

<?php if ($detailPesanan): ?>
  <a href="<?= BASE_URL ?>/modules/akun/riwayat_pesanan.php">&larr; Kembali ke daftar pesanan</a>
  <div class="table-wrap" style="margin-top:16px;padding:20px;">
    <?php [$label, $color] = status_label($detailPesanan['status_pesanan']); ?>
    <h3>Pesanan <?= clean($detailPesanan['no_pesanan']) ?> <span class="badge-status bg-<?= $color ?>"><?= $label ?></span></h3>
    <p style="margin-top:10px;">Tanggal: <?= date('d M Y H:i', strtotime($detailPesanan['tanggal_pesanan'])) ?></p>
    <p>Alamat: <?= clean($detailPesanan['alamat_pengiriman']) ?></p>
    <p>Metode Pembayaran: <?= $detailPesanan['metode_pembayaran'] === 'COD' ? 'COD (Bayar di Tempat)' : 'Transfer Bank' ?></p>
    <?php if ($detailPesanan['kode_promo']): ?>
      <p>Kode Promo: <strong><?= clean($detailPesanan['kode_promo']) ?></strong> (potongan <?= rupiah($detailPesanan['potongan_promo']) ?>)</p>
    <?php endif; ?>

    <table style="margin-top:14px;">
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

    <div style="text-align:right;margin-top:14px;">
      <p>Subtotal: <?= rupiah($detailPesanan['total_belanja']) ?></p>
      <?php if ($detailPesanan['potongan_promo'] > 0): ?><p>Potongan Promo: - <?= rupiah($detailPesanan['potongan_promo']) ?></p><?php endif; ?>
      <p>Ongkir: <?= rupiah($detailPesanan['ongkir']) ?></p>
      <p style="font-size:18px;font-weight:800;color:var(--merah-dark);">Total: <?= rupiah($detailPesanan['total_bayar']) ?></p>
    </div>
  </div>

<?php else: ?>

  <?php if (empty($pesananList)): ?>
    <p>Belum ada pesanan. <a href="<?= BASE_URL ?>/modules/katalog/index.php">Mulai pesan sekarang</a></p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>No. Pesanan</th><th>Tanggal</th><th>Total Bayar</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($pesananList as $p): [$label, $color] = status_label($p['status_pesanan']); ?>
          <tr>
            <td><?= clean($p['no_pesanan']) ?></td>
            <td><?= date('d M Y', strtotime($p['tanggal_pesanan'])) ?></td>
            <td><?= rupiah($p['total_bayar']) ?></td>
            <td><span class="badge-status bg-<?= $color ?>"><?= $label ?></span></td>
            <td><a href="?detail=<?= $p['id'] ?>" class="btn btn-gray btn-sm">Detail</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
