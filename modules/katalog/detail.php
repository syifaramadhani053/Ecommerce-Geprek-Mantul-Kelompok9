<?php
/**
 * FILE INI: DETAIL PRODUK + FORM TAMBAH KE KERANJANG
 */
require_once __DIR__ . '/../../config/config.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori FROM produk p JOIN kategori k ON p.kategori_id=k.id WHERE p.id = ? AND p.status='aktif'");
$stmt->execute([$id]);
$produk = $stmt->fetch();

if (!$produk) {
    redirect(BASE_URL . '/modules/katalog/index.php');
}

$pageTitle = $produk['nama_produk'];

$stmt2 = $pdo->prepare("SELECT * FROM produk WHERE kategori_id = ? AND id != ? AND status='aktif' LIMIT 4");
$stmt2->execute([$produk['kategori_id'], $produk['id']]);
$terkait = $stmt2->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="detail-wrap">
  <div class="detail-img">🍗</div>
  <div class="detail-info">
    <div class="product-cat"><?= clean($produk['nama_kategori']) ?></div>
    <h1><?= clean($produk['nama_produk']) ?></h1>
    <div class="price"><?= rupiah($produk['harga_jual']) ?></div>
    <p class="desc"><?= nl2br(clean($produk['deskripsi'])) ?></p>

    <?php if ($produk['stok'] <= 0): ?>
      <div class="note-box" style="background:#fdeaea;border-color:#f5c6c6;">😔 Maaf, stok menu ini sedang habis.</div>
    <?php elseif ($produk['stok'] <= $produk['stok_minimum']): ?>
      <div class="note-box">⚠️ Stok tersisa <?= $produk['stok'] ?>, segera pesan sebelum kehabisan!</div>
    <?php endif; ?>

    <?php if (is_login() && $produk['stok'] > 0): ?>
    <form action="<?= BASE_URL ?>/modules/cart/add.php" method="POST">
      <input type="hidden" name="produk_id" value="<?= $produk['id'] ?>">
      <div class="form-group">
        <label>Jumlah</label>
        <input type="number" name="kuantitas" value="1" min="1" max="<?= $produk['stok'] ?>" required>
        <small>Stok tersedia: <?= $produk['stok'] ?></small>
      </div>
      <div class="form-group">
        <label>Catatan (opsional)</label>
        <textarea name="catatan" rows="2" placeholder="Contoh: level pedas 3, tanpa nasi, dsb."></textarea>
      </div>
      <button type="submit" class="btn-primary">Tambah ke Keranjang</button>
    </form>
    <?php elseif (!is_login()): ?>
      <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn-primary">Masuk untuk Memesan</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($terkait): ?>
<h2 class="section-title">Menu Lainnya</h2>
<div class="product-grid">
  <?php foreach ($terkait as $p): ?>
    <a href="<?= BASE_URL ?>/modules/katalog/detail.php?id=<?= $p['id'] ?>" class="product-card">
      <div class="product-thumb">🍗</div>
      <div class="product-info">
        <h3><?= clean($p['nama_produk']) ?></h3>
        <div class="product-price"><?= rupiah($p['harga_jual']) ?></div>
      </div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
