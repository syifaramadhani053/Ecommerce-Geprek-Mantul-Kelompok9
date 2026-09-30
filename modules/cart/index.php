<?php
/**
 * FILE INI: HALAMAN KERANJANG
 */
require_once __DIR__ . '/../../config/config.php';
require_login();
$pageTitle = "Keranjang";

$stmt = $pdo->prepare("SELECT c.*, p.nama_produk, p.harga_jual, p.stok
    FROM keranjang c JOIN produk p ON c.produk_id = p.id
    WHERE c.pengguna_id = ? ORDER BY c.dibuat_pada DESC");
$stmt->execute([$_SESSION['pengguna_id']]);
$items = $stmt->fetchAll();

$total = 0;
foreach ($items as $it) $total += $it['harga_jual'] * $it['kuantitas'];

include __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-title">Keranjang Saya</h1>

<?php if (empty($items)): ?>
  <p>Keranjang kamu masih kosong. <a href="<?= BASE_URL ?>/modules/katalog/index.php">Yuk pilih menu!</a></p>
<?php else: ?>

  <?php foreach ($items as $it): ?>
    <div class="cart-item">
      <div class="thumb">🍗</div>
      <div class="info">
        <strong><?= clean($it['nama_produk']) ?></strong><br>
        <span><?= rupiah($it['harga_jual']) ?> x <?= $it['kuantitas'] ?> = <strong><?= rupiah($it['harga_jual'] * $it['kuantitas']) ?></strong></span><br>
        <?php if ($it['catatan']): ?><small>Catatan: <?= clean($it['catatan']) ?></small><?php endif; ?>
      </div>
      <form action="<?= BASE_URL ?>/modules/cart/update.php" method="POST" style="display:flex;gap:6px;align-items:center;">
        <input type="hidden" name="id" value="<?= $it['id'] ?>">
        <input type="number" name="kuantitas" value="<?= $it['kuantitas'] ?>" min="1" max="<?= $it['stok'] ?>" style="width:60px;padding:6px;border:1px solid #f3dede;border-radius:6px;">
        <button type="submit" class="btn btn-gray btn-sm">Update</button>
      </form>
      <a href="<?= BASE_URL ?>/modules/cart/remove.php?id=<?= $it['id'] ?>" class="btn btn-danger btn-sm btn-confirm-delete">Hapus</a>
    </div>
  <?php endforeach; ?>

  <div class="cart-summary">
    <div class="total-row"><span>Subtotal</span><span><?= rupiah($total) ?></span></div>
    <a href="<?= BASE_URL ?>/modules/checkout/index.php" class="btn-primary" style="display:block;text-align:center;">Checkout</a>
  </div>

<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
