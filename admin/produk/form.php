<?php
/**
 * FILE: admin/produk/form.php -- TAMBAH & EDIT PRODUK (satu form untuk keduanya)
 */
require_once __DIR__ . '/../includes/bootstrap.php';
need('produk');
$id = (int)($_GET['id'] ?? 0); $p = null;
if ($id) { $s = $pdo->prepare("SELECT * FROM produk WHERE id=?"); $s->execute([$id]); $p = $s->fetch(); if (!$p) redirect(ADMIN_URL . '/produk/index.php'); }
$kat = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nama = trim($_POST['nama_produk']); if ($nama === '') throw new Exception('Nama produk wajib diisi.');
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($nama)), '-');
        $kode = trim($_POST['kode_produk']) ?: 'GM-' . str_pad((int)$pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM produk")->fetchColumn(), 3, '0', STR_PAD_LEFT);
        $stok = max(0, (int)$_POST['stok']); $gambar = upload_gambar('gambar', 'produk');
        $pdo->beginTransaction();
        if ($p) {
            $g = $gambar ?: $p['gambar'];
            $pdo->prepare("UPDATE produk SET kategori_id=?,kode_produk=?,nama_produk=?,slug=?,deskripsi=?,harga_beli=?,harga_jual=?,stok_minimum=?,berat_gram=?,gambar=?,status=? WHERE id=?")
                ->execute([$_POST['kategori_id'], $kode, $nama, $slug, trim($_POST['deskripsi']), (float)$_POST['harga_beli'], (float)$_POST['harga_jual'], (int)$_POST['stok_minimum'], (int)$_POST['berat_gram'], $g, $_POST['status'], $id]);
            if ($stok !== (int)$p['stok']) mutasi_stok($pdo, $id, 'penyesuaian_opname', $stok, 'EDIT-PRODUK', 'Stok diubah dari form edit produk', $_SESSION['admin_id'], 'set');
            if ($gambar) hapus_gambar('produk', $p['gambar']);
        } else {
            $pdo->prepare("INSERT INTO produk (kategori_id,kode_produk,nama_produk,slug,deskripsi,harga_beli,harga_jual,stok,stok_minimum,berat_gram,gambar,status) VALUES (?,?,?,?,?,?,?,0,?,?,?,?)")
                ->execute([$_POST['kategori_id'], $kode, $nama, $slug, trim($_POST['deskripsi']), (float)$_POST['harga_beli'], (float)$_POST['harga_jual'], (int)$_POST['stok_minimum'], (int)$_POST['berat_gram'], $gambar ?: 'default_produk.jpg', $_POST['status']]);
            $nid = $pdo->lastInsertId();
            if ($stok > 0) mutasi_stok($pdo, $nid, 'masuk_restock', $stok, 'STOK-AWAL', 'Stok awal saat produk dibuat', $_SESSION['admin_id']);
        }
        $pdo->commit();
        set_flash('success', $p ? 'Produk berhasil diperbarui.' : 'Produk berhasil ditambahkan.');
        redirect(ADMIN_URL . '/produk/index.php');
    } catch (PDOException $e) { if ($pdo->inTransaction()) $pdo->rollBack(); set_flash('danger', 'Gagal menyimpan: kode atau nama produk sudah dipakai produk lain.');
    } catch (Exception $e) { if ($pdo->inTransaction()) $pdo->rollBack(); set_flash('danger', $e->getMessage()); }
    redirect(ADMIN_URL . '/produk/form.php' . ($id ? "?id=$id" : ''));
}
$v = fn($k, $d = '') => clean($p[$k] ?? $d);
page_start($p ? 'Edit Produk' : 'Tambah Produk', 'produk');
?>
<div class="panel" style="max-width:860px"><form method="POST" enctype="multipart/form-data"><?= csrf() ?>
  <div class="baris"><div class="form-group"><label>Nama Produk</label><input name="nama_produk" value="<?= $v('nama_produk') ?>" required></div>
    <div class="form-group"><label>Kode Produk</label><input name="kode_produk" value="<?= $v('kode_produk') ?>" placeholder="Kosongkan = otomatis"></div></div>
  <div class="baris"><div class="form-group"><label>Kategori</label><select name="kategori_id"><?php foreach ($kat as $k): ?><option value="<?= $k['id'] ?>" <?= ($p['kategori_id'] ?? '') == $k['id'] ? 'selected' : '' ?>><?= clean($k['nama_kategori']) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label>Status</label><select name="status"><option value="aktif" <?= ($p['status'] ?? 'aktif') === 'aktif' ? 'selected' : '' ?>>Aktif (tampil di katalog)</option><option value="nonaktif" <?= ($p['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif (disembunyikan)</option></select></div></div>
  <div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" rows="3"><?= $v('deskripsi') ?></textarea></div>
  <div class="baris"><div class="form-group"><label>Harga Jual (Rp)</label><input type="number" min="0" name="harga_jual" value="<?= $v('harga_jual', '0') ?>" required></div>
    <div class="form-group"><label>Harga Beli / HPP (Rp)</label><input type="number" min="0" name="harga_beli" value="<?= $v('harga_beli', '0') ?>" required></div></div>
  <div class="baris"><div class="form-group"><label>Stok</label><input type="number" min="0" name="stok" value="<?= $v('stok', '0') ?>"><small>Perubahan stok otomatis tercatat di riwayat mutasi.</small></div>
    <div class="form-group"><label>Stok Minimum (batas peringatan)</label><input type="number" min="0" name="stok_minimum" value="<?= $v('stok_minimum', '5') ?>"></div>
    <div class="form-group"><label>Berat (gram)</label><input type="number" min="0" name="berat_gram" value="<?= $v('berat_gram', '500') ?>"></div></div>
  <div class="form-group"><label>Gambar Produk</label><?php if ($p && gambar_url('produk', $p['gambar'])): ?><div style="margin-bottom:8px"><?= img_tag('produk', $p['gambar']) ?></div><?php endif; ?>
    <input type="file" name="gambar" accept="image/*"><small>JPG/PNG/WEBP maks 2 MB<?= $p ? '. Kosongkan jika tidak ingin mengganti.' : '.' ?></small></div>
  <button class="btn btn-merah">Simpan</button> <a class="btn btn-abu" href="index.php">Batal</a>
</form></div>
<?php page_end();
