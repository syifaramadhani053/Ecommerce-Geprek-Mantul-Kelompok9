<?php
/**
 * FILE: admin/pesanan/index.php -- KELOLA PESANAN (daftar, cari, filter, ubah status langsung)
 */
require_once __DIR__ . '/../includes/bootstrap.php';
need('pesanan');
$w = '1=1'; $par = [];
if (($_GET['q'] ?? '') !== '') { $w .= " AND (p.no_pesanan LIKE ? OR u.nama_lengkap LIKE ? OR p.nama_penerima LIKE ?)"; $par = ['%' . $_GET['q'] . '%', '%' . $_GET['q'] . '%', '%' . $_GET['q'] . '%']; }
if (($_GET['status'] ?? '') !== '') { $w .= " AND p.status_pesanan = ?"; $par[] = $_GET['status']; }
if (($_GET['bayar'] ?? '') !== '') { $w .= " AND p.status_pembayaran = ?"; $par[] = $_GET['bayar']; }
$c = $pdo->prepare("SELECT COUNT(*) FROM pesanan p JOIN pengguna u ON p.pengguna_id=u.id WHERE $w"); $c->execute($par);
[$hal, $off, $nav] = paginate((int)$c->fetchColumn(), 12);
$s = $pdo->prepare("SELECT p.*, u.nama_lengkap, (SELECT GROUP_CONCAT(pr.nama_produk SEPARATOR ' + ') FROM detail_pesanan d JOIN produk pr ON d.produk_id=pr.id WHERE d.pesanan_id=p.id) produk
  FROM pesanan p JOIN pengguna u ON p.pengguna_id=u.id WHERE $w ORDER BY p.dibuat_pada DESC LIMIT 12 OFFSET $off");
$s->execute($par); $list = $s->fetchAll();
page_start('Kelola Pesanan', 'pesanan', 'Ubah status pesanan langsung dari tabel untuk mengatur progress pesanan pelanggan.');
?>
<form class="cari" method="GET"><?= icon('search') ?><input name="q" placeholder="Cari no pesanan, pelanggan..." value="<?= clean($_GET['q'] ?? '') ?>">
  <select name="status" style="width:auto;border:none;background:#fff;padding:9px 12px;border-radius:8px"><option value="">Semua Status</option>
  <?php foreach (['menunggu_pembayaran','diproses','dikirim','selesai','dibatalkan'] as $st): ?><option value="<?= $st ?>" <?= ($_GET['status'] ?? '') === $st ? 'selected' : '' ?>><?= status_info($st)[0] ?></option><?php endforeach; ?></select>
  <select name="bayar" style="width:auto;border:none;background:#fff;padding:9px 12px;border-radius:8px"><option value="">Semua Pembayaran</option><option value="lunas" <?= ($_GET['bayar'] ?? '') === 'lunas' ? 'selected' : '' ?>>Lunas</option><option value="belum_lunas" <?= ($_GET['bayar'] ?? '') === 'belum_lunas' ? 'selected' : '' ?>>Belum Lunas</option></select>
  <button class="btn btn-merah btn-sm">Cari</button></form>
<div class="panel"><div class="panel-head"><h2>Daftar Pesanan</h2></div>
<div class="tbl"><table>
  <tr><th>No Pesanan</th><th>Tanggal</th><th>Pelanggan</th><th>Produk</th><th class="r">Total</th><th class="c">Pembayaran</th><th class="c">Status</th><th class="c">Aksi</th></tr>
  <?php foreach ($list as $p): ?>
  <tr><td><strong>#<?= clean($p['no_pesanan']) ?></strong></td><td><?= tgl($p['tanggal_pesanan'], true) ?></td><td><?= clean($p['nama_penerima']) ?></td><td><?= clean($p['produk']) ?></td>
    <td class="r"><?= rupiah($p['total_bayar']) ?></td>
    <td class="c"><?= pill_status($p['status_pembayaran']) ?><br><small><?= $p['metode_pembayaran'] === 'COD' ? 'COD' : 'Transfer' ?></small></td>
    <td class="c"><?php include __DIR__ . '/../includes/status_form.php'; ?></td>
    <td class="c"><a class="btn btn-abu btn-sm" href="detail.php?id=<?= $p['id'] ?>">Detail</a></td></tr>
  <?php endforeach; if (!$list) echo '<tr><td colspan="8" class="kosong">Belum ada pesanan.</td></tr>'; ?>
</table></div><?= $nav ?></div>
<?php page_end();
