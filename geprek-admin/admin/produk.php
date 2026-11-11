<?php
require __DIR__ . '/partials/auth.php';

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';
$stmt = $conn->prepare('SELECT id, nama_produk, harga_jual, stok, gambar, status FROM produk WHERE nama_produk LIKE ? OR kode_produk LIKE ? ORDER BY id');
$stmt->bind_param('ss', $like, $like);
$stmt->execute();
$hasil = $stmt->get_result();

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$judul = 'Kelola Produk';
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="content">
    <header class="topbar"><h1>Kelola Produk</h1></header>

    <section class="body body-wide">
      <?php $placeholder = 'Cari nama produk...'; include __DIR__ . '/partials/search.php'; ?>
      <?php if ($flash): ?><div class="flash"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

      <table class="tabel-produk">
        <thead><tr><th>Foto</th><th>Nama Produk</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if ($hasil->num_rows === 0): ?>
          <tr><td colspan="5" class="kosong">Produk tidak ditemukan.</td></tr>
        <?php endif; ?>
        <?php while ($r = $hasil->fetch_assoc()): ?>
          <tr class="<?= $r['status'] === 'nonaktif' ? 'nonaktif' : '' ?>">
            <td><div class="foto"><img src="../assets/img/produk/<?= rawurlencode($r['gambar']) ?>" alt="" onerror="this.style.visibility='hidden'"></div></td>
            <td><?= htmlspecialchars($r['nama_produk']) ?><?= $r['status'] === 'nonaktif' ? ' <em>(nonaktif)</em>' : '' ?></td>
            <td><?= rupiah($r['harga_jual']) ?></td>
            <td><?= (int)$r['stok'] ?></td>
            <td class="aksi">
              <a class="btn btn-edit" href="produk_edit.php?id=<?= $r['id'] ?>">Edit</a>
              <form method="post" action="produk_hapus.php" onsubmit="return confirm('Hapus produk ini?')">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button class="btn btn-hapus">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </section>
  </main>
</div>
</body>
</html>
