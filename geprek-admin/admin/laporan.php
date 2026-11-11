<?php
require __DIR__ . '/partials/auth.php';

// Rentang tanggal (default: 30 hari terakhir)
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$dari   = $_GET['dari']   ?? date('Y-m-d', strtotime($sampai . ' -30 days'));
if ($dari > $sampai) { [$dari, $sampai] = [$sampai, $dari]; }

function tglIndo(string $tgl): string {
    $bln = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $t = strtotime($tgl);
    return date('j', $t) . ' ' . $bln[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
}

// Pesanan yang dihitung sebagai penjualan: sudah diproses / dikirim / selesai
$syarat = "p.status_pesanan IN ('diproses','dikirim','selesai') AND DATE(p.tanggal_pesanan) BETWEEN ? AND ?";

$stmt = $conn->prepare("SELECT COALESCE(SUM(p.total_bayar),0) AS omzet, COUNT(*) AS jml FROM pesanan p WHERE $syarat");
$stmt->bind_param('ss', $dari, $sampai);
$stmt->execute();
$ringkas = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT COALESCE(SUM(d.kuantitas),0) AS terjual FROM detail_pesanan d JOIN pesanan p ON p.id = d.pesanan_id WHERE $syarat");
$stmt->bind_param('ss', $dari, $sampai);
$stmt->execute();
$terjual = (int)$stmt->get_result()->fetch_assoc()['terjual'];

$stmt = $conn->prepare("
    SELECT pr.nama_produk, SUM(d.kuantitas) AS qty
    FROM detail_pesanan d JOIN pesanan p ON p.id = d.pesanan_id JOIN produk pr ON pr.id = d.produk_id
    WHERE $syarat GROUP BY pr.id ORDER BY qty DESC LIMIT 3");
$stmt->bind_param('ss', $dari, $sampai);
$stmt->execute();
$terlaris = $stmt->get_result();

$stmt = $conn->prepare("SELECT p.metode_pembayaran, COUNT(*) AS jml FROM pesanan p WHERE $syarat GROUP BY p.metode_pembayaran");
$stmt->bind_param('ss', $dari, $sampai);
$stmt->execute();
$metode = ['transfer_bank' => 0, 'COD' => 0];
foreach ($stmt->get_result() as $m) { $metode[$m['metode_pembayaran']] = (int)$m['jml']; }
$totalMetode = array_sum($metode);
$pctTransfer = $totalMetode ? round($metode['transfer_bank'] / $totalMetode * 100) : 0;
$pctCod      = $totalMetode ? 100 - $pctTransfer : 0;

$judul = 'Laporan Penjualan';
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="content">
    <header class="topbar"><h1>Laporan Penjualan</h1></header>

    <section class="body body-wide">
      <form class="filter-tgl" method="get">
        <span><?= tglIndo($dari) ?> - <?= tglIndo($sampai) ?></span>
        <div>
          <input type="date" name="dari" value="<?= $dari ?>">
          <input type="date" name="sampai" value="<?= $sampai ?>">
          <button type="submit">Tampilkan</button>
        </div>
      </form>

      <div class="cards cards-lap">
        <div class="card-lap"><span>Total Penjualan</span><strong><?= rupiah($ringkas['omzet']) ?></strong></div>
        <div class="card-lap"><span>Total Pesanan</span><strong><?= (int)$ringkas['jml'] ?></strong></div>
        <div class="card-lap"><span>Produk Terjual</span><strong><?= $terjual ?></strong></div>
      </div>

      <div class="lap-bawah">
        <div class="kotak">
          <h3>Produk Terlaris</h3>
          <?php if ($terlaris->num_rows === 0): ?><p class="kosong">Belum ada penjualan.</p><?php endif; ?>
          <?php $no = 1; while ($t = $terlaris->fetch_assoc()): ?>
            <div class="baris"><span><?= $no++ ?>. <?= htmlspecialchars($t['nama_produk']) ?></span><span><?= (int)$t['qty'] ?> pcs</span></div>
          <?php endwhile; ?>
        </div>
        <div class="kotak">
          <h3>Ringkasan</h3>
          <div class="baris judul-baris"><span>Metode Pembayaran</span></div>
          <div class="baris"><span>Transfer Bank</span><span><?= $pctTransfer ?>%</span></div>
          <div class="baris"><span>COD</span><span><?= $pctCod ?>%</span></div>
        </div>
      </div>
    </section>
  </main>
</div>
</body>
</html>
