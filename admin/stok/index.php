<?php
/**
 * =====================================================================
 * FILE: admin/stok/index.php -- MANAJEMEN STOK (daftar, tambah/edit/hapus, peringatan stok habis)
 * =====================================================================
 *  Tambah stok  = restock / retur masuk       Edit stok = opname (set angka fisik)
 *  Hapus stok   = kosongkan stok jadi 0       Semua tercatat di mutasi_stok (kartu stok).
 * =====================================================================
 */
require_once __DIR__ . '/../includes/bootstrap.php';
need('stok');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pid = (int)$_POST['produk_id'];
        if (($_POST['aksi'] ?? '') === 'kosongkan') { mutasi_stok($pdo, $pid, 'penyesuaian_opname', 0, 'KOSONGKAN', 'Stok dikosongkan oleh admin', $_SESSION['admin_id'], 'set'); set_flash('success', 'Stok dikosongkan.'); }
        else {
            $jenis = $_POST['jenis_mutasi']; $jml = (int)$_POST['jumlah'];
            if (!in_array($jenis, ['masuk_restock', 'retur_masuk', 'penyesuaian_opname'], true)) throw new Exception('Jenis mutasi tidak valid.');
            if ($jenis === 'penyesuaian_opname') { if ($jml < 0) throw new Exception('Stok tidak boleh negatif.'); [$a, $b] = mutasi_stok($pdo, $pid, $jenis, $jml, trim($_POST['nomor_referensi']), trim($_POST['keterangan']), $_SESSION['admin_id'], 'set'); }
            else { if ($jml <= 0) throw new Exception('Jumlah harus lebih dari 0.'); [$a, $b] = mutasi_stok($pdo, $pid, $jenis, $jml, trim($_POST['nomor_referensi']), trim($_POST['keterangan']), $_SESSION['admin_id']); }
            set_flash('success', "Stok diperbarui: $a → $b.");
        }
    } catch (Exception $e) { set_flash('danger', $e->getMessage()); }
    redirect(ADMIN_URL . '/stok/index.php');
}
$produk = $pdo->query("SELECT * FROM produk ORDER BY (stok<=0) DESC, (stok<=stok_minimum) DESC, nama_produk")->fetchAll();
$habis = array_filter($produk, fn($p) => $p['status'] === 'aktif' && $p['stok'] <= 0);
$menipis = array_filter($produk, fn($p) => $p['status'] === 'aktif' && $p['stok'] > 0 && $p['stok'] <= $p['stok_minimum']);
$riwayat = $pdo->query("SELECT m.*, p.nama_produk, u.nama_lengkap FROM mutasi_stok m JOIN produk p ON m.produk_id=p.id LEFT JOIN pengguna u ON m.pengguna_id=u.id ORDER BY m.tanggal DESC, m.id DESC LIMIT 40")->fetchAll();
$jl = ['masuk_restock' => ['Restock', 'hijau'], 'keluar_penjualan' => ['Penjualan', 'biru'], 'penyesuaian_opname' => ['Opname', 'kuning'], 'retur_masuk' => ['Retur', 'abu']];
$pre = (int)($_GET['produk'] ?? 0); $preJenis = $_GET['jenis'] ?? 'masuk_restock';
page_start('Manajemen Stok', 'stok', 'Pantau persediaan dan catat setiap perubahan stok.');
?>
<?php if ($habis): ?><div class="alert alert-danger"><?= icon('alert') ?><div><strong>PERINGATAN STOK HABIS:</strong> <?= clean(implode(', ', array_map(fn($p) => $p['nama_produk'], $habis))) ?>. Segera restock agar tidak kehilangan penjualan.</div></div><?php endif; ?>
<?php if ($menipis): ?><div class="alert alert-warn"><?= icon('alert') ?><div><strong>Stok menipis:</strong> <?= clean(implode(', ', array_map(fn($p) => $p['nama_produk'] . ' (' . $p['stok'] . ')', $menipis))) ?>.</div></div><?php endif; ?>

<div class="grid2" style="grid-template-columns:minmax(320px,430px) 1fr">
<div class="panel"><h2>Catat Perubahan Stok</h2><form method="POST"><?= csrf() ?>
  <div class="form-group"><label>Produk</label><select name="produk_id" required><?php foreach ($produk as $p): ?><option value="<?= $p['id'] ?>" <?= $pre === (int)$p['id'] ? 'selected' : '' ?>><?= clean($p['nama_produk']) ?> (stok: <?= $p['stok'] ?>)</option><?php endforeach; ?></select></div>
  <div class="form-group"><label>Jenis</label><select name="jenis_mutasi"><option value="masuk_restock" <?= $preJenis === 'masuk_restock' ? 'selected' : '' ?>>Tambah Stok (restock masuk)</option><option value="retur_masuk">Tambah Stok (retur masuk)</option><option value="penyesuaian_opname" <?= $preJenis === 'penyesuaian_opname' ? 'selected' : '' ?>>Edit Stok (opname: set angka fisik)</option></select></div>
  <div class="form-group"><label>Jumlah</label><input type="number" name="jumlah" min="0" required><small>Untuk opname: isi stok hasil hitung fisik.</small></div>
  <div class="form-group"><label>No. Referensi (opsional)</label><input name="nomor_referensi" placeholder="Contoh: FAKTUR-001"></div>
  <div class="form-group"><label>Keterangan (opsional)</label><input name="keterangan"></div>
  <button class="btn btn-merah">Simpan Mutasi</button></form></div>
<div class="panel"><h2>Daftar Stok</h2><div class="tbl"><table>
  <tr><th>Produk</th><th class="c">Stok</th><th class="c">Min.</th><th class="c">Kondisi</th><th class="c">Aksi</th></tr>
  <?php foreach ($produk as $p): ?><tr><td><strong><?= clean($p['nama_produk']) ?></strong><br><small style="color:var(--abu)"><?= clean($p['kode_produk']) ?></small></td>
    <td class="c"><strong><?= $p['stok'] ?></strong></td><td class="c"><?= $p['stok_minimum'] ?></td>
    <td class="c"><?= $p['stok'] <= 0 ? pill('Habis', 'merah') : ($p['stok'] <= $p['stok_minimum'] ? pill('Menipis', 'kuning') : pill('Aman', 'hijau')) ?></td>
    <td class="c"><div class="aksi-grup"><a class="btn btn-merah btn-sm" href="?produk=<?= $p['id'] ?>&jenis=masuk_restock">Tambah</a><a class="btn btn-abu btn-sm" href="?produk=<?= $p['id'] ?>&jenis=penyesuaian_opname">Edit</a>
      <?= $p['stok'] > 0 ? aksi('', 'Hapus', 'btn-bahaya', ['aksi' => 'kosongkan', 'produk_id' => $p['id']], 'Kosongkan stok ' . $p['nama_produk'] . ' menjadi 0?') : '' ?></div></td></tr><?php endforeach; ?>
</table></div></div></div>
<div class="panel"><h2>Riwayat Mutasi Stok (40 terakhir)</h2><div class="tbl"><table>
  <tr><th>Waktu</th><th>Produk</th><th>Jenis</th><th class="c">Jumlah</th><th class="c">Sebelum</th><th class="c">Sesudah</th><th>Referensi</th><th>Oleh</th></tr>
  <?php foreach ($riwayat as $m): [$l, $c] = $jl[$m['jenis_mutasi']]; $tanda = $m['jenis_mutasi'] === 'keluar_penjualan' ? '−' : ($m['jumlah'] > 0 ? '+' : ''); ?>
  <tr><td><?= tgl($m['tanggal'], true) ?></td><td><?= clean($m['nama_produk']) ?></td><td><?= pill($l, $c) ?></td><td class="c"><strong><?= $tanda . $m['jumlah'] ?></strong></td><td class="c"><?= $m['stok_sebelum'] ?></td><td class="c"><?= $m['stok_sesudah'] ?></td><td><?= clean($m['nomor_referensi'] ?: '-') ?></td><td><?= clean($m['nama_lengkap'] ?: 'Pelanggan/Sistem') ?></td></tr>
  <?php endforeach; if (!$riwayat) echo '<tr><td colspan="8" class="kosong">Belum ada mutasi.</td></tr>'; ?>
</table></div></div>
<?php page_end();
