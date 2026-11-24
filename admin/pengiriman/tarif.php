<?php
/**
 * FILE: admin/pengiriman/tarif.php -- BIAYA PENGIRIMAN (daftar tarif ongkir, tambah/edit/hapus)
 * Tarif dipakai sebagai acuan saat mengisi pengiriman. Agar checkout situs user memakai tarif ini,
 * ganti konstanta ONGKIR_DEFAULT di config/config.php atau baca tabel tarif_pengiriman dari checkout.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
need('pengiriman');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['aksi'] ?? '') === 'hapus') { $pdo->prepare("DELETE FROM tarif_pengiriman WHERE id=?")->execute([(int)$_POST['id']]); set_flash('success', 'Tarif dihapus.'); }
    else {
        $d = [trim($_POST['nama']), (float)$_POST['biaya'], trim($_POST['keterangan']), (int)$_POST['aktif']];
        if (!empty($_POST['id'])) $pdo->prepare("UPDATE tarif_pengiriman SET nama=?,biaya=?,keterangan=?,aktif=? WHERE id=?")->execute([...$d, (int)$_POST['id']]);
        else $pdo->prepare("INSERT INTO tarif_pengiriman (nama,biaya,keterangan,aktif) VALUES (?,?,?,?)")->execute($d);
        set_flash('success', 'Tarif disimpan.');
    }
    redirect(ADMIN_URL . '/pengiriman/tarif.php');
}
$e = null; if (isset($_GET['edit'])) { $s = $pdo->prepare("SELECT * FROM tarif_pengiriman WHERE id=?"); $s->execute([(int)$_GET['edit']]); $e = $s->fetch(); }
$list = $pdo->query("SELECT * FROM tarif_pengiriman ORDER BY biaya")->fetchAll();
page_start('Biaya Pengiriman', 'pengiriman', 'Atur daftar tarif ongkos kirim.');
?>
<div class="tab"><a href="index.php">Daftar Pengiriman</a><a class="on" href="tarif.php">Biaya Pengiriman</a></div>
<div class="grid2" style="grid-template-columns:minmax(300px,420px) 1fr">
<div class="panel"><h2><?= $e ? 'Edit Tarif' : 'Tambah Tarif' ?></h2><form method="POST"><?= csrf() ?><?php if ($e): ?><input type="hidden" name="id" value="<?= $e['id'] ?>"><?php endif; ?>
  <div class="form-group"><label>Nama Tarif / Zona</label><input name="nama" value="<?= clean($e['nama'] ?? '') ?>" required></div>
  <div class="form-group"><label>Biaya (Rp)</label><input type="number" min="0" name="biaya" value="<?= (int)($e['biaya'] ?? 0) ?>" required></div>
  <div class="form-group"><label>Keterangan</label><input name="keterangan" value="<?= clean($e['keterangan'] ?? '') ?>"></div>
  <div class="form-group"><label>Status</label><select name="aktif"><option value="1" <?= ($e['aktif'] ?? 1) ? 'selected' : '' ?>>Aktif</option><option value="0" <?= isset($e) && !$e['aktif'] ? 'selected' : '' ?>>Nonaktif</option></select></div>
  <button class="btn btn-merah">Simpan</button> <?php if ($e): ?><a class="btn btn-abu" href="tarif.php">Batal</a><?php endif; ?></form></div>
<div class="panel"><h2>Daftar Tarif</h2><div class="tbl"><table><tr><th>Nama</th><th class="r">Biaya</th><th>Keterangan</th><th class="c">Status</th><th class="c">Aksi</th></tr>
<?php foreach ($list as $t): ?><tr><td><strong><?= clean($t['nama']) ?></strong></td><td class="r"><?= rupiah($t['biaya']) ?></td><td><?= clean($t['keterangan']) ?></td><td class="c"><?= pill_status($t['aktif'] ? 'aktif' : 'nonaktif') ?></td>
  <td class="c"><div class="aksi-grup"><a class="btn btn-abu btn-sm" href="?edit=<?= $t['id'] ?>">Edit</a><?= aksi('', 'Hapus', 'btn-bahaya', ['aksi' => 'hapus', 'id' => $t['id']], 'Hapus tarif ini?') ?></div></td></tr><?php endforeach; ?></table></div></div></div>
<?php page_end();
