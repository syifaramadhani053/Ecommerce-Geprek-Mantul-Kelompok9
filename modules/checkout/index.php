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

<!-- <h1 class="section-title">Checkout Pesanan</h1> -->
<div class="checkout-page">
  <div class="checkout-heading">
    <div>
      <h1>Checkout Pesanan</h1>
      <p class="checkout-description"> Periksa pesanan dan lengkapi data pengiriman sebelum memesan. </p>
    </div>
  </div>

  <div class="checkout-layout">
  <!-- Bagian kiri: untuk pesanan dan data pengiriman -->
    <div class="checkout-main">
      <section class="checkout-card">
        <div class="checkout-card-heading">
          <span class="checkout-step">1</span>
        <div>
          <h3>Ringkasan Pesanan</h3>
          <p>Periksa menu yang kamu pesan.</p>
        </div>
        </div>
        
        <div class="checkout-table-wrap">
          <table class="checkout-table">
            <thead>
              <tr>
                <th>Menu</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td>
                    <strong><?= clean($it['nama_produk']) ?></strong>
                    <?php if (!empty($it['varian'])): ?>
                      <small class="checkout-variant"> Pilihan: <?= clean($it['varian']) ?> </small>
                    <?php endif; ?>
                    
                    <?php if (!empty($it['catatan'])): ?>
                      <small class="checkout-note"> Catatan: <?= clean($it['catatan']) ?> </small>
                    <?php endif; ?>
                  </td>
                  <td><?= (int) $it['kuantitas'] ?></td>
                  <td><?= rupiah($it['harga_jual']) ?></td>
                  <td class="checkout-item-subtotal">
                    <?= rupiah($it['harga_jual'] * $it['kuantitas']) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <a href="<?= BASE_URL ?>/modules/cart/index.php" class="checkout-back-link"> ← Kembali ke Keranjang </a>
      </section>
      
      <section class="checkout-card">
        <div class="checkout-card-heading">
          <span class="checkout-step">2</span>
          <div>
            <h3>Kode Promo</h3>
            <p>Gunakan kode promo jika tersedia.</p>
          </div>
        </div>
        <form method="GET" class="checkout-promo-form">
          <input type="text" name="kode_promo" placeholder="Contoh: HEMAT10" value="<?= clean($kodePromo) ?>" maxlength="50">
          <button type="submit" class="checkout-promo-button"> Terapkan </button>
        </form>
        <?php if ($pesanPromo): ?>
          <div class="checkout-promo-message <?= $promo ? 'promo-success' : 'promo-error' ?>">
            <?= clean($pesanPromo) ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="checkout-card">
        <div class="checkout-card-heading">
          <span class="checkout-step">3</span>
          <div>
            <h3>Data Pengiriman</h3>
            <p>Pastikan data penerima sudah benar.</p>
          </div>
        </div>
        <form action="<?= BASE_URL ?>/modules/checkout/proses.php" method="POST" id="checkout-form">
          <input type="hidden" name="kode_promo" value="<?= clean($promo ? $kodePromo : '') ?>">
          <div class="checkout-form-grid">
            <div class="form-group">
              <label for="nama_penerima">Nama Penerima</label>
              <input type="text" id="nama_penerima" name="nama_penerima" value="<?= clean($user['nama_lengkap']) ?>" placeholder="Nama penerima" required >
            </div>
            <div class="form-group">
              <label for="no_telp_penerima">Nomor Telepon</label>
              <input type="tel" id="no_telp_penerima" name="no_telp_penerima" value="<?= clean($user['no_telp']) ?>" placeholder="Nomor yang bisa dihubungi" required >
            </div>
          </div>
          <div class="form-group">
            <label for="alamat_pengiriman">Alamat Pengiriman</label>
            <textarea id="alamat_pengiriman" name="alamat_pengiriman" rows="3" placeholder="Masukkan alamat lengkap, nama jalan, nomor rumah, RT/RW, dan patokan" required><?= clean($user['alamat']) ?></textarea>
          </div>
          <div class="form-group">
      <label>Metode Pembayaran</label>
      <select name="metode_pembayaran" id="metodePembayaran">
        <option value="online">Pembayaran Online (QRIS &amp; Bank)</option>
        <option value="transfer_bank">Transfer Bank Manual</option>
        <option value="COD">COD (Bayar di Tempat)</option>
      </select>
      <small id="hintOnline" style="display:block;margin-top:6px;color:#666;">
        Anda akan diarahkan ke halaman pembayaran aman Midtrans: QRIS (GoPay, ShopeePay, dan e-wallet lain) atau Virtual Account / Transfer Bank.
      </small>
    </div>
          <div class="form-group">
            <label for="catatan">Catatan Tambahan <span>(Opsional)</span></label>
            <textarea id="catatan" name="catatan" rows="2" placeholder="Contoh: Tolong hubungi saya saat sudah sampai."></textarea>
          </div>
        </form>
      </section>
    </div>

  <!-- Bagian kanan: untuk ringkasan pembayaran -->
  <aside class="checkout-sidebar">
    <section class="checkout-card checkout-payment-card">
      <h3>Ringkasan Pembayaran</h3>
      <p class="payment-caption">Rincian biaya pesananmu.</p>
      <div class="payment-line">
        <span>Subtotal Pesanan</span>
        <strong><?= rupiah($subtotal) ?></strong>
      </div>
      <?php if ($potongan > 0): ?>
        <div class="payment-line payment-discount">
          <span>Potongan Promo</span>
          <strong>- <?= rupiah($potongan) ?></strong>
        </div>
      <?php endif; ?>
      <div class="payment-line">
        <span>Ongkos Kirim</span>
        <strong><?= rupiah(ONGKIR_DEFAULT) ?></strong>
      </div>
      <div class="payment-divider"></div>
      <div class="payment-total">
        <span>Total Pembayaran</span>
        <strong><?= rupiah($totalBayar) ?></strong>
      </div>
      <p class="payment-info"> Pastikan menu, alamat, dan metode pembayaran sudah sesuai sebelum membuat pesanan. </p>
      <!-- <button type="submit" form="checkout-form" class="checkout-submit"> Buat Pesanan <span>→</span> </button> -->
       <button type="submit" form="checkout-form" class="btn-primary" id="btnBuatPesanan" style="width:100%"> Buat Pesanan &amp; Bayar
</button>

      <div class="checkout-secure-note">
        <span>✓</span> Periksa kembali detail pesananmu
      </div>
    </section>
  </aside>
  </div>
</div>

<script>
(function () {
  var select = document.getElementById('metodePembayaran');
  var hint   = document.getElementById('hintOnline');
  var tombol = document.getElementById('btnBuatPesanan');

  function perbarui() {
    var online = select.value === 'online';
    hint.style.display = online ? 'block' : 'none';
    tombol.innerHTML = online ? 'Buat Pesanan &amp; Bayar' : 'Buat Pesanan';
  }

  select.addEventListener('change', perbarui);
  perbarui();
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
