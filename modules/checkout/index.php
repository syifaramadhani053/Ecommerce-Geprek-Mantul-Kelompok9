<?php
/**
 * FILE INI: HALAMAN CHECKOUT (review pesanan + input kode promo)
 */
require_once __DIR__ . '/../../config/config.php';
require_login();
$pageTitle = "Checkout";

$stmt = $pdo->prepare("SELECT c.*, p.nama_produk, p.harga_jual, p.stok
    FROM keranjang c JOIN produk p ON c.produk_id = p.id
    WHERE c.pengguna_id = ?");
$stmt->execute([$_SESSION['pengguna_id']]);
$items = $stmt->fetchAll();

if (empty($items)) {
    redirect(BASE_URL . '/modules/cart/index.php');
}

$subtotal = 0;
foreach ($items as $it) $subtotal += $it['harga_jual'] * $it['kuantitas'];

// Cek kode promo yang mungkin sedang dicoba (lewat GET agar bisa live-preview)
$kodePromo = trim($_GET['kode_promo'] ?? $_POST['kode_promo'] ?? '');
$promo = null;
$potongan = 0;
$pesanPromo = '';

if ($kodePromo !== '') {
    $stmtPromo = $pdo->prepare("SELECT * FROM promo WHERE kode_promo = ? AND status='aktif' AND CURDATE() BETWEEN tanggal_mulai AND tanggal_berakhir");
    $stmtPromo->execute([$kodePromo]);
    $promo = $stmtPromo->fetch();

    if (!$promo) {
        $pesanPromo = 'Kode promo tidak valid atau sudah tidak berlaku.';
    } elseif ($subtotal < $promo['minimal_belanja']) {
        $pesanPromo = 'Minimal belanja untuk promo ini adalah ' . rupiah($promo['minimal_belanja']) . '.';
        $promo = null;
    } else {
        $potongan = hitung_potongan_promo($promo, $subtotal);
        $pesanPromo = 'Promo "' . $promo['judul'] . '" berhasil diterapkan!';
    }
}

$totalBayar = $subtotal - $potongan + ONGKIR_DEFAULT;

$stmtUser = $pdo->prepare("SELECT * FROM pengguna WHERE id = ?");
$stmtUser->execute([$_SESSION['pengguna_id']]);
$user = $stmtUser->fetch();

include __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-title">Checkout Pesanan</h1>

<div class="table-wrap">
  <table>
    <thead><tr><th>Menu</th><th>Qty</th><th>Harga</th><th>Subtotal</th><th>Catatan</th></tr></thead>
    <tbody>
      <?php foreach ($items as $it): ?>
      <tr>
        <td><?= clean($it['nama_produk']) ?></td>
        <td><?= $it['kuantitas'] ?></td>
        <td><?= rupiah($it['harga_jual']) ?></td>
        <td><?= rupiah($it['harga_jual'] * $it['kuantitas']) ?></td>
        <td><?= clean($it['catatan'] ?: '-') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="panel" style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:20px;">
  <h3>Punya Kode Promo?</h3>
  <form method="GET" style="display:flex;gap:10px;max-width:400px;margin-top:10px;">
    <input type="text" name="kode_promo" placeholder="Masukkan kode promo" value="<?= clean($kodePromo) ?>" style="flex:1;padding:10px 12px;border:1px solid var(--border);border-radius:8px;text-transform:uppercase;">
    <button type="submit" class="btn btn-gray">Terapkan</button>
  </form>
  <?php if ($pesanPromo): ?>
    <p style="margin-top:10px;color:<?= $promo ? '#1c7c41' : '#c0392b' ?>;font-size:14px;"><?= clean($pesanPromo) ?></p>
  <?php endif; ?>
</div>

<form action="<?= BASE_URL ?>/modules/checkout/proses.php" method="POST">
  <input type="hidden" name="kode_promo" value="<?= clean($promo ? $kodePromo : '') ?>">

  <div class="panel" style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:20px;">
    <h3>Data Pengiriman</h3>
    <div class="form-group">
      <label>Nama Penerima</label>
      <input type="text" name="nama_penerima" value="<?= clean($user['nama_lengkap']) ?>" required>
    </div>
    <div class="form-group">
      <label>No. Telepon Penerima</label>
      <input type="text" name="no_telp_penerima" value="<?= clean($user['no_telp']) ?>" required>
    </div>
    <div class="form-group">
      <label>Alamat Pengiriman</label>
      <textarea name="alamat_pengiriman" rows="3" required><?= clean($user['alamat']) ?></textarea>
    </div>
    <div class="form-group">
      <label>Metode Pembayaran</label>
      <select name="metode_pembayaran">
        <option value="transfer_bank">Transfer Bank</option>
        <option value="COD">COD (Bayar di Tempat)</option>
      </select>
    </div>
    <div class="form-group">
      <label>Catatan Tambahan (opsional)</label>
      <textarea name="catatan" rows="2"></textarea>
    </div>
  </div>

  <div class="cart-summary">
    <div class="row-line"><span>Subtotal</span><span><?= rupiah($subtotal) ?></span></div>
    <?php if ($potongan > 0): ?>
      <div class="row-line" style="color:#1c7c41;"><span>Potongan Promo</span><span>- <?= rupiah($potongan) ?></span></div>
    <?php endif; ?>
    <div class="row-line"><span>Ongkos Kirim</span><span><?= rupiah(ONGKIR_DEFAULT) ?></span></div>
    <div class="total-row"><span>Total Bayar</span><span><?= rupiah($totalBayar) ?></span></div>
    <button type="submit" class="btn-primary" style="width:100%">Buat Pesanan</button>
  </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
