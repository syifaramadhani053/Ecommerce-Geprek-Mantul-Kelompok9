<?php
/**
 * FILE: admin/kategori/index.php -- KELOLA KATEGORI + GAMBAR KATEGORI (tambah/edit/hapus 1 halaman)
 */
require_once __DIR__ . '/../includes/bootstrap.php';
need('kategori');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['aksi'] ?? '') === 'hapus') {
            $c = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE kategori_id=?");
            $c->execute([$_POST['id']]);
            if ($c->fetchColumn() > 0) throw new Exception('Kategori masih dipakai oleh produk, tidak bisa dihapus.');
            $g = $pdo->prepare("SELECT gambar FROM kategori WHERE id=?");
            $g->execute([$_POST['id']]);
            @hapus_gambar('kategori', $g->fetchColumn());
            $pdo->prepare("DELETE FROM kategori WHERE id=?")->execute([$_POST['id']]);
            set_flash('success', 'Kategori dihapus.');
        } else {
            $nama = trim($_POST['nama_kategori']);
            if ($nama === '') throw new Exception('Nama kategori wajib diisi.');
            $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($nama)), '-');
            $gambar = @upload_gambar('gambar', 'kategori');
            if (!empty($_POST['id'])) {
                $o = $pdo->prepare("SELECT gambar FROM kategori WHERE id=?");
                $o->execute([$_POST['id']]);
                $lama = $o->fetchColumn();
                $pdo->prepare("UPDATE kategori SET nama_kategori=?, slug=?, deskripsi=?, gambar=? WHERE id=?")
                    ->execute([$nama, $slug, trim($_POST['deskripsi']), $gambar ?: $lama, $_POST['id']]);
                if ($gambar) @hapus_gambar('kategori', $lama);
                set_flash('success', 'Kategori diperbarui.');
            } else {
                $pdo->prepare("INSERT INTO kategori (nama_kategori,slug,deskripsi,gambar) VALUES (?,?,?,?)")
                    ->execute([$nama, $slug, trim($_POST['deskripsi']), $gambar]);
                set_flash('success', 'Kategori ditambahkan.');
            }
        }
    } catch (PDOException $e) {
        set_flash('danger', 'Gagal menyimpan: nama kategori sudah ada.');
    } catch (Exception $e) {
        set_flash('danger', $e->getMessage());
    }
    redirect(ADMIN_URL . '/kategori/index.php');
}
$e = null;
if (!empty($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM kategori WHERE id=?");
    $s->execute([(int) $_GET['edit']]);
    $e = $s->fetch();
}
$list = $pdo->query("SELECT k.*, (SELECT COUNT(*) FROM produk p WHERE p.kategori_id=k.id) jml FROM kategori k ORDER BY k.id")->fetchAll();
page_start('Kelola Kategori', 'kategori', 'Kelompokkan menu agar mudah dicari pelanggan.');
?>
<div class="grid2" style="grid-template-columns:minmax(300px,420px) 1fr">
  <div class="panel">
    <h2><?= $e ? 'Edit Kategori' : 'Tambah Kategori' ?></h2>
    <form method="POST" action="<?= ADMIN_URL ?>/kategori/index.php" enctype="multipart/form-data">
      <?= csrf() ?>
      <?php if ($e): ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>"><?php endif; ?>
      <div class="form-group">
        <label>Nama Kategori</label>
        <input name="nama_kategori" value="<?= clean($e['nama_kategori'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="deskripsi" rows="3"><?= clean($e['deskripsi'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label>Gambar Kategori</label>
        <?php if ($e && gambar_url('kategori', $e['gambar'])): ?>
          <div style="margin-bottom:8px"><?= img_tag('kategori', $e['gambar']) ?></div>
        <?php endif; ?>
        <input type="file" name="gambar" accept="image/*">
      </div>
      <button type="submit" class="btn btn-merah">Simpan</button>
      <?php if ($e): ?><a class="btn btn-abu" href="<?= ADMIN_URL ?>/kategori/index.php">Batal</a><?php endif; ?>
    </form>
  </div>
  <div class="panel">
    <h2>Daftar Kategori</h2>
    <div class="tbl">
      <table>
        <tr>
          <th class="c">Gambar</th>
          <th>Nama</th>
          <th>Deskripsi</th>
          <th class="c">Produk</th>
          <th class="c">Aksi</th>
        </tr>
        <?php foreach ($list as $k): ?>
          <tr>
            <td class="c"><div style="display:inline-block"><?= img_tag('kategori', $k['gambar']) ?></div></td>
            <td><strong><?= clean($k['nama_kategori']) ?></strong></td>
            <td><?= clean($k['deskripsi']) ?></td>
            <td class="c"><?= $k['jml'] ?></td>
            <td class="c">
              <div class="aksi-grup">
                <a class="btn btn-abu btn-sm" href="<?= ADMIN_URL ?>/kategori/index.php?edit=<?= (int) $k['id'] ?>">Edit</a>
                <?= aksi('', 'Hapus', 'btn-bahaya', ['aksi' => 'hapus', 'id' => $k['id']], 'Hapus kategori ini?') ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
</div>
<?php page_end(); ?>
