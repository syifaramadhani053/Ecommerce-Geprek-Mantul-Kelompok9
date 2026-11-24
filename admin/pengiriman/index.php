<?php
/**
 * FILE: admin/pengiriman/index.php -- MANAJEMEN PENGIRIMAN (LOGISTIK)
 * Mengubah status di sini ikut mengubah status pesanan (Dikirim -> pesanan Dikirim, Terkirim -> pesanan Selesai).
 */
require_once __DIR__ . '/../includes/bootstrap.php';
need('pengiriman');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['aksi'] ?? '') === 'status') { [$ok, $m] = set_status_pengiriman($pdo, (int)$_POST['id'], $_POST['status'], $_SESSION['admin_id']); set_flash($ok ? 'success' : 'danger', $m); }
    if (($_POST['aksi'] ?? '') === 'hapus') { $pdo->prepare("DELETE FROM pengiriman WHERE id=?")->execute([(int)$_POST['id']]); set_flash('success', 'Data pengiriman dihapus.'); }
    redirect(ADMIN_URL . '/pengiriman/index.php');
}
$w = '1=1'; $par = [];
if (($_GET['q'] ?? '') !== '') { $w .= " AND (p.no_pesanan LIKE ? OR p.nama_penerima LIKE ? OR k.nomor_resi LIKE ?)"; $par = array_fill(0, 3, '%' . $_GET['q'] . '%'); }
if (($_GET['status'] ?? '') !== '') { $w .= " AND k.status=?"; $par[] = $_GET['status']; }
$s = $pdo->prepare("SELECT k.*, p.no_pesanan, p.nama_penerima, p.alamat_pengiriman, p.status_pesanan FROM pengiriman k JOIN pesanan p ON k.pesanan_id=p.id WHERE $w ORDER BY k.dibuat_pada DESC"); $s->execute($par); $list = $s->fetchAll();
$q = fn($sql) => $pdo->query($sql)->fetchColumn();
$belum = $pdo->query("SELECT p.id, p.no_pesanan, p.nama_penerima FROM pesanan p LEFT JOIN pengiriman k ON k.pesanan_id=p.id WHERE k.id IS NULL AND p.status_pesanan IN ('diproses','dikirim') ORDER BY p.dibuat_pada")->fetchAll();
page_start('Manajemen Pengiriman', 'pengiriman', 'Kelola proses pengiriman hingga pesanan sampai ke tangan pelanggan.');
?>
<div class="tab"><a class="on" href="index.php">Daftar Pengiriman</a><a href="tarif.php">Biaya Pengiriman</a></div>
<div class="kartu-grid">
  <?= kartu('Disiapkan', $q("SELECT COUNT(*) FROM pengiriman WHERE status='disiapkan'"), 'clip') ?><?= kartu('Dalam Pengiriman', $q("SELECT COUNT(*) FROM pengiriman WHERE status='dikirim'"), 'truck') ?>
  <?= kartu('Terkirim', $q("SELECT COUNT(*) FROM pengiriman WHERE status='terkirim'"), 'box') ?><?= kartu('Gagal', $q("SELECT COUNT(*) FROM pengiriman WHERE status='gagal'"), 'alert', '', $q("SELECT COUNT(*) FROM pengiriman WHERE status='gagal'") ? 'bahaya' : '') ?>
  <?= kartu('Biaya Kirim Bulan Ini', rupiah($q("SELECT COALESCE(SUM(biaya),0) FROM pengiriman WHERE YEAR(dibuat_pada)=YEAR(CURDATE()) AND MONTH(dibuat_pada)=MONTH(CURDATE())")), 'dollar') ?></div>
<?php if ($belum): ?><div class="alert alert-warn"><?= icon('alert') ?><div><strong><?= count($belum) ?> pesanan sedang diproses tapi belum punya data pengiriman:</strong>
  <?php foreach ($belum as $b): ?><a href="form.php?pesanan_id=<?= $b['id'] ?>">#<?= clean($b['no_pesanan']) ?></a> <?php endforeach; ?></div></div><?php endif; ?>
<form class="cari" method="GET"><?= icon('search') ?><input name="q" placeholder="Cari no pesanan, penerima, resi..." value="<?= clean($_GET['q'] ?? '') ?>">
  <select name="status" style="width:auto;border:none;background:#fff;padding:9px 12px;border-radius:8px"><option value="">Semua Status</option><?php foreach (['disiapkan','dikirim','terkirim','gagal'] as $st): ?><option value="<?= $st ?>" <?= ($_GET['status'] ?? '') === $st ? 'selected' : '' ?>><?= status_info($st)[0] ?></option><?php endforeach; ?></select><button class="btn btn-merah btn-sm">Cari</button></form>
<div class="panel"><div class="panel-head"><h2>Daftar Pengiriman</h2><a class="btn btn-merah" href="form.php">+ Tambah Pengiriman</a></div>
<div class="tbl"><table><tr><th>No Pesanan</th><th>Penerima</th><th>Kurir / Resi</th><th class="r">Biaya</th><th>Dikirim</th><th class="c">Status</th><th class="c">Aksi</th></tr>
<?php foreach ($list as $k): ?><tr><td><a href="<?= ADMIN_URL ?>/pesanan/detail.php?id=<?= $k['pesanan_id'] ?>"><strong>#<?= clean($k['no_pesanan']) ?></strong></a></td>
  <td><?= clean($k['nama_penerima']) ?><br><small style="color:var(--abu)"><?= clean(potong($k['alamat_pengiriman'], 40)) ?></small></td><td><?= clean($k['kurir']) ?><br><small><?= clean($k['nomor_resi'] ?: '-') ?></small></td>
  <td class="r"><?= rupiah($k['biaya']) ?></td><td><?= tgl($k['tanggal_kirim'], true) ?></td>
  <td class="c"><form method="POST" style="display:inline"><?= csrf() ?><input type="hidden" name="aksi" value="status"><input type="hidden" name="id" value="<?= $k['id'] ?>"><select name="status" class="inline-status" data-auto="1"><?php foreach (['disiapkan','dikirim','terkirim','gagal'] as $st): ?><option value="<?= $st ?>" <?= $k['status'] === $st ? 'selected' : '' ?>><?= status_info($st)[0] ?></option><?php endforeach; ?></select></form></td>
  <td class="c"><div class="aksi-grup"><a class="btn btn-abu btn-sm" href="form.php?id=<?= $k['id'] ?>">Edit</a><?= aksi('', 'Hapus', 'btn-bahaya', ['aksi' => 'hapus', 'id' => $k['id']], 'Hapus data pengiriman ini?') ?></div></td></tr>
<?php endforeach; if (!$list) echo '<tr><td colspan="7" class="kosong">Belum ada data pengiriman.</td></tr>'; ?></table></div></div>
<?php page_end();
