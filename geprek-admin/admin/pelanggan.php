<?php
require __DIR__ . '/partials/auth.php';

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

$stmt = $conn->prepare("
    SELECT u.id, u.nama_lengkap, u.email, u.no_telp, u.status_aktif,
           COUNT(p.id) AS jml_pesanan,
           COALESCE(SUM(CASE WHEN p.status_pesanan IN ('diproses','dikirim','selesai') THEN p.total_bayar END), 0) AS total_belanja
    FROM pengguna u
    LEFT JOIN pesanan p ON p.pengguna_id = u.id
    WHERE u.peran = 'pelanggan' AND (u.nama_lengkap LIKE ? OR u.email LIKE ? OR u.no_telp LIKE ?)
    GROUP BY u.id
    ORDER BY u.dibuat_pada DESC, u.id DESC
    LIMIT 100
");
$stmt->bind_param('sss', $like, $like, $like);
$stmt->execute();
$hasil = $stmt->get_result();

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$judul = 'Kelola Pelanggan';
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="content">
    <header class="topbar"><h1>Kelola Pelanggan</h1></header>

    <section class="body body-wide">
      <?php $placeholder = 'Cari nama, email, no telp...'; include __DIR__ . '/partials/search.php'; ?>
      <?php if ($flash): ?><div class="flash"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

      <div class="panel panel-mid panel-full">
        <h2>Daftar Pelanggan</h2>
        <table>
          <thead><tr><th>Nama</th><th>Email</th><th>No Telp</th><th>Pesanan</th><th>Total Belanja</th><th>Status</th></tr></thead>
          <tbody>
          <?php if ($hasil->num_rows === 0): ?>
            <tr><td colspan="6" class="kosong">Pelanggan tidak ditemukan.</td></tr>
          <?php endif; ?>
          <?php while ($r = $hasil->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
              <td><?= htmlspecialchars($r['email']) ?></td>
              <td><?= htmlspecialchars($r['no_telp'] ?? '-') ?></td>
              <td><?= (int)$r['jml_pesanan'] ?></td>
              <td><?= rupiah($r['total_belanja']) ?></td>
              <td>
                <form method="post" action="pelanggan_status.php" class="inline">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="badge <?= $r['status_aktif'] ? 'green' : 'gray' ?> badge-btn"
                          title="Klik untuk <?= $r['status_aktif'] ? 'nonaktifkan' : 'aktifkan' ?>">
                    <?= $r['status_aktif'] ? 'Aktif' : 'Nonaktif' ?>
                  </button>
                </form>
              </td>
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