<?php
require __DIR__ . '/partials/auth.php';

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

$stmt = $conn->prepare("
    SELECT p.no_pesanan, p.total_bayar, p.status_pesanan, u.nama_lengkap,
           GROUP_CONCAT(pr.nama_produk ORDER BY d.id SEPARATOR ' + ') AS produk
    FROM pesanan p
    JOIN pengguna u ON u.id = p.pengguna_id
    LEFT JOIN detail_pesanan d ON d.pesanan_id = p.id
    LEFT JOIN produk pr ON pr.id = d.produk_id
    WHERE p.no_pesanan LIKE ? OR u.nama_lengkap LIKE ?
    GROUP BY p.id
    ORDER BY p.tanggal_pesanan DESC, p.id DESC
    LIMIT 50
");
$stmt->bind_param('ss', $like, $like);
$stmt->execute();
$hasil = $stmt->get_result();

$labelStatus = [
  'menunggu_pembayaran' => ['Menunggu', 'pink'],
  'diproses'            => ['Diproses', 'yellow'],
  'dikirim'             => ['Dikirim',  'blue'],
  'selesai'             => ['Selesai',  'green'],
  'dibatalkan'          => ['Batal',    'gray'],
];

$judul = 'Kelola Pesanan';
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="content">
    <header class="topbar"><h1>Kelola Pesanan</h1></header>

    <section class="body body-wide">
      <?php $placeholder = 'Cari no pesanan, pelanggan...'; include __DIR__ . '/partials/search.php'; ?>

      <div class="panel panel-mid">
        <h2>Pesanan Terbaru</h2>
        <table>
          <thead><tr><th>No Pesanan</th><th>Pelanggan</th><th>Produk</th><th>Total</th><th>Status</th></tr></thead>
          <tbody>
          <?php if ($hasil->num_rows === 0): ?>
            <tr><td colspan="5" class="kosong">Pesanan tidak ditemukan.</td></tr>
          <?php endif; ?>
          <?php while ($r = $hasil->fetch_assoc()): [$teks, $warna] = $labelStatus[$r['status_pesanan']]; ?>
            <tr>
              <td>#<?= htmlspecialchars($r['no_pesanan']) ?></td>
              <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
              <td><?= htmlspecialchars($r['produk'] ?? '-') ?></td>
              <td><?= rupiah($r['total_bayar']) ?></td>
              <td><span class="badge <?= $warna ?>"><?= $teks ?></span></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>
</body>
</html>
