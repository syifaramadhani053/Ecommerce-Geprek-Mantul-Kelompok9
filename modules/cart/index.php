<?php
/**
 * FILE INI: HALAMAN KERANJANG
 */
require_once __DIR__ . '/../../config/config.php';
require_login();
$pageTitle = "Keranjang";

$stmt = $pdo->prepare("
    SELECT c.*, p.nama_produk, p.harga_jual, p.stok, p.gambar, k.nama_kategori
    FROM keranjang c
    JOIN produk p ON c.produk_id = p.id
    JOIN kategori k ON p.kategori_id = k.id
    WHERE c.pengguna_id = ?
    ORDER BY c.dibuat_pada DESC
");

$stmt->execute([$_SESSION['pengguna_id']]);
$items = $stmt->fetchAll();

$total = 0;
foreach ($items as $it) $total += $it['harga_jual'] * $it['kuantitas'];

include __DIR__ . '/../../includes/header.php';
?>

<div class="cart-add-menu">
  <a href="<?= BASE_URL ?>/modules/katalog/index.php" class="btn-add-menu"> + Tambah Menu Lain </a>
</div>

<?php if (empty($items)): ?>
  <div class="cart-empty">
    <h1>Keranjang Saya</h1>
    
    <p class="cart-subtitle"> Periksa kembali pesanan Anda sebelum checkout. </p>
    <div class="cart-empty-icon">🛒</div>
      <p class="cart-empty-message"> Keranjang Anda masih kosong.</p>
      <a href="<?= BASE_URL ?>/modules/katalog/index.php" class="cart-empty-button">Pilih Menu</a>
    </div>
<?php else: ?>

<?php foreach ($items as $it): ?>
  <div class="cart-item">
    <div class="thumb">
      <?php if (!empty($it['gambar'])): ?>
        <img src="<?= BASE_URL ?>/assets/img/<?= clean($it['gambar']) ?>" alt="<?= clean($it['nama_produk']) ?>">
      <?php else: ?>
        <span>🍗</span>
      <?php endif; ?>
    </div>
    
    <!-- Informasi produk -->
    <div class="info">
      <strong><?= clean($it['nama_produk']) ?></strong>
      <p class="cart-price">
        <?= rupiah($it['harga_jual']) ?>
      </p>
    
      <!-- Pilihan ayam hanya untuk kategori ayam -->
      <?php if (
        in_array($it['nama_kategori'], ['Menu Utama', 'Paket Hemat'], true)
      ): ?>
      <form
        action="<?= BASE_URL ?>/modules/cart/update.php"
        method="POST"
        class="cart-variant-form" >
        <input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
        <label for="varian-<?= (int) $it['id'] ?>"> Pilihan ayam: </label>

        <select id="varian-<?= (int) $it['id'] ?>" name="varian" onchange="this.form.requestSubmit()">
          <?php foreach (['Dada', 'Paha Atas', 'Paha Bawah', 'Sayap'] as $pilihan): ?>
            <option value="<?= clean($pilihan) ?>"
              <?= ($it['varian'] ?? '') === $pilihan ? 'selected' : '' ?>
              >
              <?= clean($pilihan) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
      <?php endif; ?>
      
      <!-- Catatan pesanan -->
      <?php if (!empty($it['catatan'])): ?>
        <p class="cart-note"> Catatan: <?= clean($it['catatan']) ?> </p>
      <?php endif; ?>
      <p class="cart-subtotal"> Total: <strong> <?= rupiah($it['harga_jual'] * $it['kuantitas']) ?> </strong> </p>
  </div>
  
  <!-- Quantity otomatis diperbarui -->
  <div class="cart-actions">
    <form
      action="<?= BASE_URL ?>/modules/cart/update.php"
      method="POST"
      class="cart-quantity-form">
        <input type="hidden" name="id" value="<?= (int) $it['id'] ?>">

        <input
            type="number"
            name="kuantitas"
            value="<?= (int) $it['kuantitas'] ?>"
            min="1"
            max="<?= (int) $it['stok'] ?>"
            aria-label="Kuantitas produk"
            required
            onchange="this.form.requestSubmit()"
        >
    </form>

    <a href="<?= BASE_URL ?>/modules/cart/remove.php?id=<?= (int) $it['id'] ?>"
      class="cart-delete-icon btn-confirm-delete"
      aria-label="Hapus produk"
      title="Hapus produk"
    >
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width="21"
      height="21"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="1.8"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <polyline points="3 6 5 6 21 6"></polyline>
      <path d="M19 6l-1 14H6L5 6"></path>
      <path d="M10 11v6"></path>
      <path d="M14 11v6"></path>
      <path d="M9 6V4h6v2"></path>
    </svg>
    </a>
  </div>
  </div>
<?php endforeach; ?>

  <div class="cart-summary">
    <div class="total-row"><span>Subtotal</span><span><?= rupiah($total) ?></span></div>
    <a href="<?= BASE_URL ?>/modules/checkout/index.php" class="btn-primary" style="display:block;text-align:center;">Checkout</a>
  </div>

<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
