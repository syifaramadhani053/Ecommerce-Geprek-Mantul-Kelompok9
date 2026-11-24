<?php
/**
 * FILE: admin/pengiriman/form.php -- TAMBAH & EDIT PENGIRIMAN
 */
require_once __DIR__ . '/../includes/bootstrap.php';
need('pengiriman');
$id = (int)($_GET['id'] ?? 0); $k = null;
if (!$id && !empty($_GET['pesanan_id'])) { $c = $pdo->prepare("SELECT id FROM pengiriman WHERE pesanan_id=?"); $c->execute([(int)$_GET['pesanan_id']]); $id = (int)$c->fetchColumn(); }
if ($id) { $s = $pdo->prepare("SELECT k.*, p.no_pesanan FROM pengiriman k JOIN pesanan p ON k.pesanan_id=p.id WHERE k.id=?"); $s->execute([$id]); $k = $s->fetch(); }
$pre = $k ? null : (int)($_GET['pesanan_id'] ?? 0);
$kurirList = ['Kurir Internal', 'Ojek Online', 'Ambil di Tempat'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pid = $k ? (int)$k['pesanan_id'] : (int)$_POST['pesanan_id'];
        $pdo->beginTransaction();
        $ps = $pdo->prepare("SELECT * FROM pesanan WHERE id=?"); $ps->execute([$pid]); if (!$ps->fetch()) throw new Exception('Pesanan tidak ditemukan.');
        if ($k) $pdo->prepare("UPDATE pengiriman SET kurir=?,nomor_resi=?,biaya=?,catatan=? WHERE id=?")->execute([$_POST['kurir'], trim($_POST['nomor_resi']) ?: null, (float)$_POST['biaya'], trim($_POST['catatan']) ?: null, $k['id']]);
        else { $pdo->prepare("INSERT INTO pengiriman (pesanan_id,kurir,nomor_resi,biaya,status,catatan) VALUES (?,?,?,?, 'disiapkan', ?)")->execute([$pid, $_POST['kurir'], trim($_POST['nomor_resi']) ?: null, (float)$_POST['biaya'], trim($_POST['catatan']) ?: null]); $kid = (int)$pdo->lastInsertId(); }
        $pdo->prepare("UPDATE pesanan SET kurir=?, nomor_resi=? WHERE id=?")->execute([$_POST['kurir'], trim($_POST['nomor_resi']) ?: null, $pid]);
        $kid = $k ? (int)$k['id'] : $kid;
        if ($_POST['status'] !== ($k['status'] ?? 'disiapkan')) { [$ok, $m] = set_status_pengiriman($pdo, $kid, $_POST['status'], $_SESSION['admin_id']); if (!$ok) throw new Exception($m); }
        $pdo->commit(); set_flash('success', 'Data pengiriman disimpan.'); redirect(ADMIN_URL . '/pengiriman/index.php');
    } catch (PDOException $e) { if ($pdo->inTransaction()) $pdo->rollBack(); set_flash('danger', 'Pesanan tersebut sudah punya data pengiriman.'); }
    catch (Exception $e) { if ($pdo->inTransaction()) $pdo->rollBack(); set_flash('danger', $e->getMessage()); }
    redirect(ADMIN_URL . '/pengiriman/form.php' . ($k ? '?id=' . $k['id'] : ''));
}
$opsi = $k ? [] : $pdo->query("SELECT p.id, p.no_pesanan, p.nama_penerima, p.ongkir, p.kurir FROM pesanan p LEFT JOIN pengiriman x ON x.pesanan_id=p.id WHERE x.id IS NULL AND p.status_pesanan NOT IN ('dibatalkan','selesai') ORDER BY p.dibuat_pada DESC")->fetchAll();
$tarif = $pdo->query("SELECT * FROM tarif_pengiriman WHERE aktif=1 ORDER BY biaya")->fetchAll();
page_start($k ? 'Edit Pengiriman' : 'Tambah Pengiriman', 'pengiriman');
?>
<div class="panel" style="max-width:720px"><form method="POST"><?= csrf() ?>
  <?php if ($k): ?><p style="margin-bottom:14px">Pesanan: <strong>#<?= clean($k['no_pesanan']) ?></strong></p>
  <?php else: ?><div class="form-group"><label>Pesanan</label><select name="pesanan_id" required><?php foreach ($opsi as $o): ?><option value="<?= $o['id'] ?>" <?= $pre === (int)$o['id'] ? 'selected' : '' ?>>#<?= clean($o['no_pesanan']) ?> — <?= clean($o['nama_penerima']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
  <div class="baris"><div class="form-group"><label>Kurir</label><select name="kurir"><?php foreach ($kurirList as $kr): ?><option <?= ($k['kurir'] ?? '') === $kr ? 'selected' : '' ?>><?= $kr ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label>No. Resi / Keterangan</label><input name="nomor_resi" value="<?= clean($k['nomor_resi'] ?? '') ?>"></div></div>
  <div class="baris"><div class="form-group"><label>Biaya Pengiriman (Rp)</label><input type="number" min="0" name="biaya" value="<?= (int)($k['biaya'] ?? ONGKIR_DEFAULT) ?>"><small>Tarif acuan: <?= implode(' | ', array_map(fn($t) => clean($t['nama']) . ' ' . rupiah($t['biaya']), $tarif)) ?></small></div>
    <div class="form-group"><label>Status Pengiriman</label><select name="status"><?php foreach (['disiapkan','dikirim','terkirim','gagal'] as $st): ?><option value="<?= $st ?>" <?= ($k['status'] ?? 'disiapkan') === $st ? 'selected' : '' ?>><?= status_info($st)[0] ?></option><?php endforeach; ?></select><small>"Dikirim" / "Terkirim" otomatis menyelaraskan status pesanan.</small></div></div>
  <div class="form-group"><label>Catatan</label><input name="catatan" value="<?= clean($k['catatan'] ?? '') ?>"></div>
  <button class="btn btn-merah">Simpan</button> <a class="btn btn-abu" href="index.php">Batal</a></form></div>
<?php page_end();
