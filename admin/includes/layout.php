<?php
/**
 * FILE: admin/includes/layout.php -- KERANGKA TAMPILAN (SIDEBAR, TOPBAR, ALERT)
 */
function page_start($judul, $menu, $sub = '') {
    global $pdo;
    $q = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
    $badge = [
      'pesanan'      => $q("SELECT COUNT(*) FROM pesanan WHERE status_pesanan='menunggu_pembayaran'"),
      'stok'         => $q("SELECT COUNT(*) FROM produk WHERE status='aktif' AND stok <= stok_minimum"),
      'kontak'       => $q("SELECT COUNT(*) FROM pesan_kontak WHERE status='baru'"),
      'pengembalian' => $q("SELECT COUNT(*) FROM pengembalian WHERE status='diajukan'"),
    ];
    $habis = $pdo->query("SELECT nama_produk FROM produk WHERE status='aktif' AND stok <= 0 ORDER BY nama_produk")->fetchAll(PDO::FETCH_COLUMN);
    $logo = gambar_url('pengaturan', setting('logo')) ?: ADMIN_URL . '/assets/img/Logo.png';
    $fav  = gambar_url('pengaturan', setting('favicon')) ?: $logo;
    $nama = setting('nama_toko', 'Geprek Mantul');
    $grup = [
      'MENU UTAMA'         => [['dashboard', 'dashboard.php', 'Dashboard', 'home'], 
      ['pesanan', 'pesanan/index.php', 'Pesanan', 'file'], 
      ['produk', 'produk/index.php', 'Produk', 'box'], 
      ['pelanggan', 'pelanggan/index.php', 'Pelanggan', 'users']],
        
      'OPERASIONAL'        => [['kategori', 'kategori/index.php', 'Kategori', 'layers'], 
      ['stok', 'stok/index.php', 'Stok', 'archive'],
      ['pengiriman', 'pengiriman/index.php', 'Pengiriman', 'truck'], 
      ['pembayaran', 'pembayaran/index.php', 'Pembayaran', 'card'],
      ['pengembalian', 'pengembalian/index.php', 'Pengembalian', 'undo']],

      'KEUANGAN & ANALISIS'=> [['keuangan', 'keuangan/index.php', 'Laporan Keuangan', 'dollar'],
      ['analitik', 'analitik/index.php', 'Analitik', 'chart'],
      ['crm', 'crm/index.php', 'CRM', 'chat']],

      'KONTEN'             => [['promo', 'promo/index.php', 'Promo', 'tag'], 
      ['faq', 'faq/index.php', 'FAQ', 'help'], 
      ['kontak', 'kontak/index.php', 'Pesan Masuk', 'mail']],
      
      'SISTEM'             => [['pengguna', 'pengguna/index.php', 'Pengguna & Hak Akses', 'shield'],
      ['pengaturan', 'pengaturan/index.php', 'Pengaturan Website', 'gear']],
    ];
    ?>
    
    <!DOCTYPE html>
    <html lang="id">
    <head>
      <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?= clean($judul) ?> - Admin <?= clean($nama) ?></title>
      <link rel="icon" href="<?= $fav ?>">
      <link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/admin.css">
    </head>
    <body>
      <div class="shell">
        <aside class="sidebar" id="sidebar">
          <a class="brand" href="<?= ADMIN_URL ?>/dashboard.php"><img src="<?= $logo ?>" alt=""><span><?= clean(strtoupper($nama)) ?></span></a>
          <nav>
            <?php foreach ($grup as $nm => $items): $tampil = array_filter($items, fn($i) => can($i[0])); if (!$tampil) continue; ?>
            <div class="grup"><?= $nm ?></div>
            <?php foreach ($tampil as [$k, $href, $lbl, $ic]): ?>
              <a href="<?= ADMIN_URL . '/' . $href ?>" class="<?= $menu === $k ? 'aktif' : '' ?>"><?= icon($ic) ?><span><?= $lbl ?></span>
              <?php if (!empty($badge[$k])): ?><em class="bdg"><?= $badge[$k] ?></em><?php endif; ?></a>
                <?php endforeach; endforeach; ?>
          </nav>
          <a class="keluar" href="<?= ADMIN_URL ?>/logout.php"><?= icon('out') ?><span>Logout</span></a>
        </aside>
        <main class="main">
          <header class="topbar">
            <button class="burger" onclick="document.getElementById('sidebar').classList.toggle('buka')"><?= icon('menu') ?></button>
            <div><h1><?= clean($judul) ?></h1><?php if ($sub): ?><p class="sub"><?= clean($sub) ?></p><?php endif; ?></div>
              <div class="user"><span class="avatar"><?= icon('user') ?></span><div><strong><?= clean($_SESSION['admin_nama']) ?></strong><small><?= clean($_SESSION['admin_peran']) ?></small></div></div>
          </header>
          <section class="isi">
            <?php show_flash(); ?>
            <?php if ($habis && $menu !== 'stok'): ?>
              <div class="alert alert-warn"><?= icon('alert') ?> <div><strong><?= count($habis) ?> produk stok HABIS:</strong> <?= clean(implode(', ', $habis)) ?>.
              <?php if (can('stok')): ?><a href="<?= ADMIN_URL ?>/stok/index.php">Kelola stok &rarr;</a><?php endif; ?></div></div>
            <?php endif; ?>
            <?php }
            function page_end() { ?>
          </section>
        </main>
      </div>
    <script>
    // Pasang token CSRF pada semua form POST yang belum punya (aman untuk dibiarkan)
    document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(function(f){
      if(!f.querySelector('[name="_csrf"]')){var i=document.createElement('input');i.type='hidden';i.name='_csrf';i.value='<?= csrf_token() ?>';f.appendChild(i);}
    });
    // Form dengan atribut data-auto="1" terkirim otomatis saat pilihan diubah (dipakai ubah status cepat)
    document.querySelectorAll('select[data-auto]').forEach(function(s){ s.addEventListener('change', function(){ s.form.submit(); }); });
    </script>
    </body>
    </html>
<?php }

// Kartu statistik ala dashboard
function kartu($label, $nilai, $ic, $catatan = '', $kelas = '') {
    return '<div class="kartu ' . $kelas . '"><div class="kartu-ic">' . icon($ic) . '</div><div><div class="kartu-l">' . $label . '</div><div class="kartu-n">' . $nilai . '</div>' . ($catatan ? '<div class="kartu-c">' . $catatan . '</div>' : '') . '</div></div>';
}
