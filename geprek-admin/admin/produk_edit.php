<?php
require __DIR__ . '/partials/auth.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = trim($_POST['nama_produk']);
    $harga  = (float)$_POST['harga_jual'];
    $stok   = (int)$_POST['stok'];
    $status = $_POST['status'] === 'nonaktif' ? 'nonaktif' : 'aktif';
    $stmt = $conn->prepare('UPDATE produk SET nama_produk=?, harga_jual=?, stok=?, status=? WHERE id=?');
    $stmt->bind_param('sdisi', $nama, $harga, $stok, $status, $id);
    $stmt->execute();
    $_SESSION['flash'] = 'Produk berhasil diperbarui.';
    header('Location: produk.php');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM produk WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
if (!$p) { header('Location: produk.php'); exit; }

$judul = 'Edit Produk';
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="content">
    <header class="topbar"><h1>Edit Produk</h1></header>
    <section class="body">
      <form class="form-edit" method="post">
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <label>Nama Produk</label>
        <input type="text" name="nama_produk" value="<?= htmlspecialchars($p['nama_produk']) ?>" required>
        <label>Harga Jual (Rp)</label>
        <input type="number" name="harga_jual" min="0" step="500" value="<?= (int)$p['harga_jual'] ?>" required>
        <label>Stok</label>
        <input type="number" name="stok" min="0" value="<?= (int)$p['stok'] ?>" required>
        <label>Status</label>
        <select name="status">
          <option value="aktif" <?= $p['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
          <option value="nonaktif" <?= $p['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
        <div class="form-aksi">
          <button class="btn btn-hapus" type="submit">Simpan</button>
          <a class="btn btn-edit" href="produk.php">Batal</a>
        </div>
      </form>
    </section>
  </main>
</div>
</body>
</html>
