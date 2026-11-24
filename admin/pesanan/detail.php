<?php
/**
 * =====================================================================
 * FILE: admin/pesanan/detail.php -- DETAIL & PROGRESS 1 PESANAN
 * =====================================================================
 * Di sini admin mengatur progress pesanan: tombol langkah berikutnya,
 * batalkan, dan konfirmasi pembayaran. Semua lewat fungsi di logika.php
 * sehingga stok, pengiriman, dan keuangan otomatis ikut terbarui.
 * =====================================================================
 */
require_once __DIR__ . '/../includes/bootstrap.php';
need('pesanan');
$id = (int)($_GET['id'] ?? 0);
$s = $pdo->prepare("SELECT p.*, u.nama_lengkap, u.email, u.no_telp FROM pesanan p JOIN pengguna u ON p.pengguna_id=u.id WHERE p.id=?"); $s->execute([$id]); $p = $s->fetch();
if (!$p) { set_flash('danger', 'Pesanan tidak ditemukan.'); redirect(ADMIN_URL . '/pesanan/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['aksi'] ?? '') === 'status') { [$ok, $m] = ubah_status_pesanan($pdo, $id, $_POST['status'], $_SESSION['admin_id']); set_flash($ok ? 'success' : 'danger', $m); }
        if (($_POST['aksi'] ?? '') === 'bayar') {
            $jml = (float)$_POST['jumlah']; if ($jml <= 0) throw new Exception('Jumlah pembayaran harus lebih dari 0.');
            $bukti = upload_gambar('bukti', 'bukti');
            catat_pembayaran($pdo, $id, $_POST['metode'], $jml, $_SESSION['admin_id'], 'diterima', $bukti, trim($_POST['catatan'] ?? '') ?: null);
            set_flash('success', 'Pembayaran dicatat dan keuangan otomatis diperbarui.');
        }
    } catch (Exception $e) { set_flash('danger', $e->getMessage()); }
    redirect(ADMIN_URL . '/pesanan/detail.php?id=' . $id);
}
$items = $pdo->prepare("SELECT d.*, pr.nama_produk, pr.gambar FROM detail_pesanan d JOIN produk pr ON d.produk_id=pr.id WHERE d.pesanan_id=?"); $items->execute([$id]); $items = $items->fetchAll();
$bayar = $pdo->prepare("SELECT * FROM pembayaran WHERE pesanan_id=? ORDER BY id"); $bayar->execute([$id]); $bayar = $bayar->fetchAll();
$kirim = $pdo->prepare("SELECT * FROM pengiriman WHERE pesanan_id=?"); $kirim->execute([$id]); $kirim = $kirim->fetch();
$diterima = array_sum(array_map(fn($b) => $b['status'] === 'diterima' ? $b['jumlah'] : 0, $bayar));
$sisa = max(0, $p['total_bayar'] - $diterima);
$urut = ['menunggu_pembayaran' => 1, 'diproses' => 2, 'dikirim' => 3, 'selesai' => 4];
$posisi = $urut[$p['status_pesanan']] ?? 0;
$berikut = [1 => ['diproses', 'Proses Pesanan'], 2 => ['dikirim', 'Kirim Pesanan'], 3 => ['selesai', 'Tandai Selesai']][$posisi] ?? null;
$metodeList = $pdo->query("SELECT kode, nama FROM metode_pembayaran WHERE aktif=1 ORDER BY id")->fetchAll();
page_start('Detail Pesanan', 'pesanan', '#' . $p['no_pesanan']);
?>
<a href="index.php" class="btn btn-abu btn-sm" style="margin-bottom:16px">&larr; Kembali ke daftar</a>
<div class="panel"><div class="panel-head"><h2>Progress Pesanan <?= pill_status($p['status_pesanan']) ?></h2></div>
  <?php if ($p['status_pesanan'] === 'dibatalkan'): ?><div class="alert alert-danger">Pesanan ini dibatalkan. Stok sudah dikembalikan.</div>
  <?php else: ?>
  <div class="langkah"><?php foreach (['Menunggu Pembayaran', 'Diproses', 'Dikirim', 'Selesai'] as $i => $l): ?><div data-n="<?= $i + 1 ?>" class="<?= $posisi >= $i + 1 ? 'on' : '' ?> <?= $posisi === $i + 1 ? 'now' : '' ?>"><?= $l ?></div><?php endforeach; ?></div>
  <?php endif; ?>
  <?php if ($berikut || !in_array($p['status_pesanan'], ['dibatalkan', 'selesai'])): ?><div class="aksi-grup" style="justify-content:flex-start">
    <?php if ($berikut) echo aksi('', $berikut[1], 'btn-merah', ['aksi' => 'status', 'status' => $berikut[0]]); ?>
    <?php if (!in_array($p['status_pesanan'], ['dibatalkan', 'selesai'])) echo aksi('', 'Batalkan Pesanan', 'btn-bahaya', ['aksi' => 'status', 'status' => 'dibatalkan'], 'Batalkan pesanan ini? Stok akan dikembalikan dan tidak bisa diurungkan.'); ?>
  </div><?php endif; ?></div>

<div class="grid2">
  <div class="panel"><h2>Informasi Pesanan</h2><dl class="ket">
    <dt>Pelanggan</dt><dd><?= clean($p['nama_lengkap']) ?> (<?= clean($p['email']) ?>)</dd><dt>Penerima</dt><dd><?= clean($p['nama_penerima']) ?> — <?= clean($p['no_telp_penerima']) ?></dd>
    <dt>Alamat</dt><dd><?= clean($p['alamat_pengiriman']) ?></dd><dt>Tanggal</dt><dd><?= tgl($p['tanggal_pesanan'], true) ?></dd>
    <dt>Metode Bayar</dt><dd><?= $p['metode_pembayaran'] === 'COD' ? 'COD (Bayar di Tempat)' : 'Transfer Bank' ?></dd><dt>Catatan</dt><dd><?= clean($p['catatan'] ?: '-') ?></dd></dl></div>
  <div class="panel"><div class="panel-head"><h2>Pengiriman</h2><?php if (can('pengiriman')): ?><a class="btn btn-gelap btn-sm" href="<?= ADMIN_URL ?>/pengiriman/form.php?pesanan_id=<?= $id ?>">Atur</a><?php endif; ?></div>
    <?php if ($kirim): ?><dl class="ket"><dt>Status</dt><dd><?= pill_status($kirim['status']) ?></dd><dt>Kurir</dt><dd><?= clean($kirim['kurir']) ?></dd><dt>No. Resi</dt><dd><?= clean($kirim['nomor_resi'] ?: '-') ?></dd>
      <dt>Dikirim</dt><dd><?= tgl($kirim['tanggal_kirim'], true) ?></dd><dt>Diterima</dt><dd><?= tgl($kirim['tanggal_terima'], true) ?></dd></dl>
    <?php else: ?><div class="kosong">Belum ada data pengiriman. Otomatis dibuat saat status "Dikirim".</div><?php endif; ?></div>
</div>

<div class="panel"><h2>Item Pesanan</h2><div class="tbl"><table>
  <tr><th>Produk</th><th class="c">Qty</th><th class="r">Harga</th><th class="r">Subtotal</th></tr>
  <?php foreach ($items as $it): ?><tr><td><?= clean($it['nama_produk']) ?></td><td class="c"><?= $it['kuantitas'] ?></td><td class="r"><?= rupiah($it['harga_satuan']) ?></td><td class="r"><?= rupiah($it['subtotal']) ?></td></tr><?php endforeach; ?>
</table></div>
<div style="text-align:right;margin-top:14px;line-height:2">Subtotal: <?= rupiah($p['total_belanja']) ?><br>
  <?php if ($p['potongan_promo'] > 0): ?>Promo (<?= clean($p['kode_promo']) ?>): − <?= rupiah($p['potongan_promo']) ?><br><?php endif; ?>
  Ongkir: <?= rupiah($p['ongkir']) ?><br><strong style="font-size:20px;color:var(--merah-tua)">Total: <?= rupiah($p['total_bayar']) ?></strong></div></div>

<div class="grid2">
  <div class="panel"><h2>Pembayaran <?= pill_status($p['status_pembayaran']) ?></h2>
    <p style="margin-bottom:12px">Diterima: <strong><?= rupiah($diterima) ?></strong> dari <?= rupiah($p['total_bayar']) ?><?= $sisa > 0 ? ' — sisa <strong>' . rupiah($sisa) . '</strong>' : '' ?></p>
    <?php if ($bayar): ?><div class="tbl" style="margin-bottom:14px"><table><tr><th>Tanggal</th><th>Metode</th><th class="r">Jumlah</th><th>Status</th></tr>
      <?php foreach ($bayar as $b): ?><tr><td><?= tgl($b['tanggal_bayar'], true) ?></td><td><?= clean($b['metode']) ?></td><td class="r"><?= rupiah($b['jumlah']) ?></td><td><?= pill_status($b['status']) ?></td></tr><?php endforeach; ?></table></div><?php endif; ?>
    <?php if ($p['status_pesanan'] !== 'dibatalkan' && $sisa > 0): ?>
    <form method="POST" enctype="multipart/form-data"><?= csrf() ?><input type="hidden" name="aksi" value="bayar">
      <div class="baris"><div class="form-group"><label>Jumlah (Rp)</label><input type="number" name="jumlah" value="<?= (int)$sisa ?>" min="1" required></div>
      <div class="form-group"><label>Metode</label><select name="metode"><?php foreach ($metodeList as $m): ?><option value="<?= clean($m['kode']) ?>" <?= $m['kode'] === $p['metode_pembayaran'] ? 'selected' : '' ?>><?= clean($m['nama']) ?></option><?php endforeach; ?></select></div></div>
      <div class="form-group"><label>Bukti pembayaran (opsional)</label><input type="file" name="bukti" accept="image/*"></div>
      <div class="form-group"><label>Catatan</label><input name="catatan"></div>
      <button class="btn btn-merah">Konfirmasi Pembayaran Diterima</button></form><?php endif; ?></div>
  <div class="panel"><h2>Keuangan Terkait</h2>
    <?php $jr = $pdo->prepare("SELECT no_jurnal, tanggal, kode_akun, debit, kredit FROM jurnal_keuangan WHERE referensi_transaksi=? ORDER BY id"); $jr->execute([$p['no_pesanan']]); $jr = $jr->fetchAll(); ?>
    <?php if ($jr): ?><div class="tbl"><table><tr><th>Jurnal</th><th>Akun</th><th class="r">Debit</th><th class="r">Kredit</th></tr>
      <?php foreach ($jr as $j): ?><tr><td><small><?= clean($j['no_jurnal']) ?></small></td><td><?= clean($j['kode_akun']) ?></td><td class="r"><?= $j['debit'] > 0 ? rupiah($j['debit']) : '' ?></td><td class="r"><?= $j['kredit'] > 0 ? rupiah($j['kredit']) : '' ?></td></tr><?php endforeach; ?></table></div>
    <?php else: ?><div class="kosong">Belum ada jurnal. Jurnal penjualan tercatat otomatis setelah pesanan lunas.</div><?php endif; ?></div>
</div>
<?php page_end();
