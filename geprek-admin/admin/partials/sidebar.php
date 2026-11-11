<?php
$halaman = basename($_SERVER['PHP_SELF'], '.php');
$menu = [
  'dashboard' => ['Dashboard', '<path d="M3 11l9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>'],
  'pesanan'   => ['Pesanan',   '<path d="M6 3h9l4 4v14H6z"/><path d="M9 11h7M9 15h7M9 7h3"/>'],
  'produk'    => ['Produk',    '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/>'],
  'pelanggan' => ['Pelanggan', '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5c2 .7 3.5 2.4 3.5 5.5"/>'],
  'laporan'   => ['Laporan',   '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 2h6v4H9zM9 12h6M9 16h6"/>'],
];
?>
<aside class="sidebar">
  <div class="brand">
    <img src="../assets/img/logo.png" alt="" onerror="this.outerHTML='<span class=&quot;logo-fallback&quot;>🍗</span>'">
    <span>GEPREK MANTUL</span>
  </div>
  <nav>
    <?php foreach ($menu as $slug => [$label, $icon]): ?>
      <a href="<?= $slug ?>.php" class="<?= $halaman === $slug ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $icon ?></svg>
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <a href="../logout.php" class="logout">
    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h5"/><path d="M14 8l4 4-4 4M18 12H8"/></svg>
    Logout
  </a>
</aside>
