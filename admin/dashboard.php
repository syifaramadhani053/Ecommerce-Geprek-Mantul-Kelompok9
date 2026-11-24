<?php
/**
 * FILE: admin/dashboard.php -- RINGKASAN USAHA
 */
require_once __DIR__ . '/includes/bootstrap.php';
need('dashboard');
$q = fn($sql, $p = []) => (function () use ($pdo, $sql, $p) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchColumn(); })();
$persen = function ($ini, $lalu) { if ($lalu == 0) return $ini > 0 ? 100 : 0; return round(($ini - $lalu) / $lalu * 100); };
$kartuNaik = function ($p) { return '<span class="' . ($p >= 0 ? 'naik">↑ ' : 'turun">↓ ') . abs($p) . '%</span> dari kemarin'; };

$totalPesanan = $q("SELECT COUNT(*) FROM pesanan");
$psnIni = $q("SELECT COUNT(*) FROM pesanan WHERE DATE(tanggal_pesanan)=CURDATE()");
$psnLalu = $q("SELECT COUNT(*) FROM pesanan WHERE DATE(tanggal_pesanan)=CURDATE()-INTERVAL 1 DAY");
$totalPelanggan = $q("SELECT COUNT(*) FROM pengguna WHERE peran='pelanggan'");
$plgIni = $q("SELECT COUNT(*) FROM pengguna WHERE peran='pelanggan' AND DATE(dibuat_pada)=CURDATE()");
$plgLalu = $q("SELECT COUNT(*) FROM pengguna WHERE peran='pelanggan' AND DATE(dibuat_pada)=CURDATE()-INTERVAL 1 DAY");
$totalProduk = $q("SELECT COUNT(*) FROM produk WHERE status='aktif'");
$omzetHari = $q("SELECT COALESCE(SUM(total_bayar),0) FROM pesanan WHERE status_pesanan!='dibatalkan' AND DATE(tanggal_pesanan)=CURDATE()");
$omzetKemarin = $q("SELECT COALESCE(SUM(total_bayar),0) FROM pesanan WHERE status_pesanan!='dibatalkan' AND DATE(tanggal_pesanan)=CURDATE()-INTERVAL 1 DAY");
$omzetBulan = $q("SELECT COALESCE(SUM(total_bayar),0) FROM pesanan WHERE status_pesanan!='dibatalkan' AND YEAR(tanggal_pesanan)=YEAR(CURDATE()) AND MONTH(tanggal_pesanan)=MONTH(CURDATE())");
$menunggu = $q("SELECT COUNT(*) FROM pesanan WHERE status_pesanan='menunggu_pembayaran'");
$diproses = $q("SELECT COUNT(*) FROM pesanan WHERE status_pesanan IN ('diproses','dikirim')");
$menipis = $q("SELECT COUNT(*) FROM produk WHERE status='aktif' AND stok<=stok_minimum AND stok>0");
$habis = $q("SELECT COUNT(*) FROM produk WHERE status='aktif' AND stok<=0");

// grafik 7 hari
$hari = []; for ($i = 6; $i >= 0; $i--) $hari[date('Y-m-d', strtotime("-$i day"))] = 0;
$r = $pdo->query("SELECT DATE(tanggal_pesanan) d, SUM(total_bayar) t FROM pesanan WHERE status_pesanan!='dibatalkan' AND tanggal_pesanan >= CURDATE()-INTERVAL 6 DAY GROUP BY DATE(tanggal_pesanan)")->fetchAll();
foreach ($r as $x) $hari[$x['d']] = (float)$x['t'];
$maks = max(1, max($hari));
$status = $pdo->query("SELECT status_pesanan s, COUNT(*) n FROM pesanan GROUP BY status_pesanan")->fetchAll(PDO::FETCH_KEY_PAIR);
$terlaris = $pdo->query("SELECT pr.nama_produk, SUM(d.kuantitas) qty FROM detail_pesanan d JOIN pesanan p ON d.pesanan_id=p.id JOIN produk pr ON d.produk_id=pr.id WHERE p.status_pesanan!='dibatalkan' AND p.tanggal_pesanan >= CURDATE()-INTERVAL 30 DAY GROUP BY pr.id,pr.nama_produk ORDER BY qty DESC LIMIT 5")->fetchAll();
$maksT = max(1, (int)($terlaris[0]['qty'] ?? 1));
$terbaru = $pdo->query("SELECT p.*, u.nama_lengkap, (SELECT GROUP_CONCAT(pr.nama_produk SEPARATOR ' + ') FROM detail_pesanan d JOIN produk pr ON d.produk_id=pr.id WHERE d.pesanan_id=p.id) produk FROM pesanan p JOIN pengguna u ON p.pengguna_id=u.id ORDER BY p.dibuat_pada DESC LIMIT 8")->fetchAll();

page_start('Dashboard Admin', 'dashboard', 'Berikut adalah ringkasan aktivitas penjualan.');
?>

<div class="kartu-grid">
  <?= kartu('Total Pesanan', angka($totalPesanan), 'file', $kartuNaik($persen($psnIni, $psnLalu))) ?>
  <?= kartu('Total Pelanggan', angka($totalPelanggan), 'users', $kartuNaik($persen($plgIni, $plgLalu))) ?>
  <?= kartu('Total Produk', angka($totalProduk), 'box', $menipis + $habis ? '<span class="turun">' . ($menipis + $habis) . ' produk</span> perlu restock' : 'Stok semua produk aman') ?>
  <?= kartu('Omzet Hari Ini', rupiah($omzetHari), 'dollar', $kartuNaik($persen($omzetHari, $omzetKemarin))) ?>
  <?= kartu('Omzet Bulan Ini', rupiah($omzetBulan), 'chart', 'Tidak termasuk pesanan batal') ?>
  <?= kartu('Menunggu Konfirmasi', angka($menunggu), 'clip', 'Pesanan baru perlu ditindak', $menunggu ? 'bahaya' : '') ?>
  <?= kartu('Sedang Diproses / Dikirim', angka($diproses), 'truck', 'Pesanan berjalan') ?>
  <?= kartu('Stok Habis', angka($habis), 'alert', $menipis . ' lainnya menipis', $habis ? 'bahaya' : '') ?>
</div>

<div class="grid2">
  <div class="panel"><h2>Penjualan 7 Hari Terakhir</h2>
    <div class="chart"><?php foreach ($hari as $d => $t): ?>
      <div class="b"><span><?= $t ? number_format($t / 1000, 0, ',', '.') . 'rb' : '0' ?></span><i style="height:<?= max(2, round($t / $maks * 150)) ?>px"></i><?= date('d/m', strtotime($d)) ?></div>
    <?php endforeach; ?></div></div>
  <div class="panel"><h2>Produk Terlaris (30 hari)</h2>
    <?php foreach ($terlaris as $t): ?><div class="hbar"><div class="t"><span><?= clean($t['nama_produk']) ?></span><strong><?= $t['qty'] ?> pcs</strong></div><div class="g"><i style="width:<?= round($t['qty'] / $maksT * 100) ?>%"></i></div></div><?php endforeach; ?>
    <?php if (!$terlaris): ?><div class="kosong">Belum ada penjualan.</div><?php endif; ?></div>
</div>

<div class="panel"><div class="panel-head"><h2>Pesanan Terbaru</h2><?php if (can('pesanan')): ?><a class="btn btn-gelap btn-sm" href="<?= ADMIN_URL ?>/pesanan/index.php">Lihat Semua</a><?php endif; ?></div>
  <div class="tbl">
    <table>
      <tr>
        <th>No Pesanan</th>
        <th>Pelanggan</th>
        <th>Produk</th>
        <th class="r">Total</th>
        <th class="c">Status</th>
      </tr>
      <?php foreach ($terbaru as $p): ?>
        <tr>
          <td>
            <a href="<?= ADMIN_URL ?>/pesanan/detail.php?id=<?= $p['id'] ?>">
              <strong>#<?= clean($p['no_pesanan']) ?></strong>
            </a>
          </td>
          <td><?= clean($p['nama_penerima']) ?></td>
          <td><?= clean($p['produk']) ?></td>
          <td class="r"><?= rupiah($p['total_bayar']) ?></td>
          <td class="c"><?php if (can('pesanan')) include __DIR__ . '/includes/status_form.php'; else echo pill_status($p['status_pesanan']); ?></td>
        </tr>
      <?php endforeach; if (!$terbaru) echo '<tr><td colspan="5" class="kosong">Belum ada pesanan.</td></tr>'; ?>
    </table>
  </div>
</div>

<?php page_end();
