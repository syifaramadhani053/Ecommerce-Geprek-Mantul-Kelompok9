<?php
/** FILE: admin/pelanggan/detail.php -- RIWAYAT PESANAN & INTERAKSI 1 PELANGGAN. */
require_once __DIR__ . '/../includes/bootstrap.php';
need('pelanggan');
$s = $pdo->prepare("SELECT * FROM pengguna WHERE id=? AND peran='pelanggan'"); $s->execute([(int)($_GET['id'] ?? 0)]); $u = $s->fetch();
if (!$u) redirect(ADMIN_URL . '/pelanggan/index.php');
$r = $pdo->prepare("SELECT * FROM pesanan WHERE pengguna_id=? ORDER BY dibuat_pada DESC"); $r->execute([$u['id']]); $riwayat = $r->fetchAll();
$total = array_sum(array_map(fn($x) => $x['status_pesanan'] !== 'dibatalkan' ? $x['total_bayar'] : 0, $riwayat));
page_start('Riwayat Pelanggan', 'pelanggan', $u['nama_lengkap']);
?>
<a href="index.php" class="btn btn-abu btn-sm" style="margin-bottom:16px">&larr; Kembali</a>
<div class="kartu-grid"><?= kartu('Total Pesanan', count($riwayat), 'file') ?><?= kartu('Total Belanja', rupiah($total), 'dollar') ?><?= kartu('Bergabung', tgl($u['dibuat_pada']), 'user') ?></div>
<div class="panel"><h2>Data Pelanggan</h2><dl class="ket"><dt>Email</dt><dd><?= clean($u['email']) ?></dd><dt>No. Telp</dt><dd><?= clean($u['no_telp'] ?: '-') ?></dd><dt>Alamat</dt><dd><?= clean($u['alamat'] ?: '-') ?></dd></dl></div>
<div class="panel"><h2>Riwayat Pesanan</h2><div class="tbl"><table><tr><th>No Pesanan</th><th>Tanggal</th><th class="r">Total</th><th class="c">Pembayaran</th><th class="c">Status</th></tr>
<?php foreach ($riwayat as $x): ?><tr><td><a href="<?= ADMIN_URL ?>/pesanan/detail.php?id=<?= $x['id'] ?>"><strong>#<?= clean($x['no_pesanan']) ?></strong></a></td><td><?= tgl($x['tanggal_pesanan'], true) ?></td><td class="r"><?= rupiah($x['total_bayar']) ?></td><td class="c"><?= pill_status($x['status_pembayaran']) ?></td><td class="c"><?= pill_status($x['status_pesanan']) ?></td></tr>
<?php endforeach; if (!$riwayat) echo '<tr><td colspan="5" class="kosong">Belum ada pesanan.</td></tr>'; ?></table></div></div>
<?php page_end();
