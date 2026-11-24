<?php
/**
 * FILE: admin/pelanggan/index.php -- KELOLA PELANGGAN (daftar, cari, tambah/edit/hapus, riwayat)
 */
require_once __DIR__ . '/../includes/bootstrap.php';
need('pelanggan');
$w = "u.peran='pelanggan'"; $par = [];
if (($_GET['q'] ?? '') !== '') { $w .= " AND (u.nama_lengkap LIKE ? OR u.email LIKE ? OR u.no_telp LIKE ?)"; $par = array_fill(0, 3, '%' . $_GET['q'] . '%'); }
$s = $pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM pesanan x WHERE x.pengguna_id=u.id) jml, (SELECT COALESCE(SUM(total_bayar),0) FROM pesanan x WHERE x.pengguna_id=u.id AND x.status_pesanan!='dibatalkan') belanja FROM pengguna u WHERE $w ORDER BY u.dibuat_pada DESC");
$s->execute($par); $list = $s->fetchAll();
page_start('Kelola Pelanggan', 'pelanggan', 'Data pelanggan beserta riwayat pesanannya.');
?>
<form class="cari" method="GET"><?= icon('search') ?><input name="q" placeholder="Cari nama, email, no telp..." value="<?= clean($_GET['q'] ?? '') ?>"><button class="btn btn-merah btn-sm">Cari</button></form>
<div class="panel"><div class="panel-head"><h2>Daftar Pelanggan (<?= count($list) ?>)</h2><a class="btn btn-merah" href="form.php">+ Tambah Pelanggan</a></div>
<div class="tbl"><table><tr><th>Nama</th><th>Email</th><th>No Telp</th><th class="c">Pesanan</th><th class="r">Total Belanja</th><th class="c">Status</th><th class="c">Aksi</th></tr>
<?php foreach ($list as $u): ?><tr><td><strong><?= clean($u['nama_lengkap']) ?></strong></td><td><?= clean($u['email']) ?></td><td><?= clean($u['no_telp']) ?></td><td class="c"><?= $u['jml'] ?></td><td class="r"><?= rupiah($u['belanja']) ?></td>
  <td class="c"><?= $u['status_aktif'] ? pill('Aktif', 'hijau') : pill('Diblokir', 'merah') ?></td>
  <td class="c"><div class="aksi-grup"><a class="btn btn-abu btn-sm" href="detail.php?id=<?= $u['id'] ?>">Riwayat</a><a class="btn btn-abu btn-sm" href="form.php?id=<?= $u['id'] ?>">Edit</a><?= aksi('hapus.php', 'Hapus', 'btn-bahaya', ['id' => $u['id']], 'Hapus pelanggan ini?') ?></div></td></tr>
<?php endforeach; if (!$list) echo '<tr><td colspan="7" class="kosong">Belum ada pelanggan.</td></tr>'; ?></table></div></div>
<?php page_end();
