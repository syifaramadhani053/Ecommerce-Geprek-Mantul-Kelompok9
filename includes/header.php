<?php
/**
 * ================================
 * FILE INI: HEADER / NAVBAR
 * ================================
 */
require_once __DIR__ . '/../config/config.php';
$cartCount = is_login() ? cart_count($pdo, $_SESSION['pengguna_id']) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? clean($pageTitle) . ' - ' . SITE_NAME : SITE_NAME ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>

<body>
<header class="site-header">
  <div class="container header-inner">
    <a href="<?= BASE_URL ?>/index.php" class="logo">
      <img src="<?=BASE_URL ?>/assets/img/Logo.png" alt="Logo Geprek Mantul">  
      <span>Geprek <strong>Mantul</strong></span>
    </a>
    <nav class="main-nav" id="mainNav">
      <a href="<?= BASE_URL ?>/index.php">Beranda</a>
      <a href="<?= BASE_URL ?>/modules/katalog/index.php">Katalog</a>
      <a href="<?= BASE_URL ?>/modules/pages/tentang.php">Tentang Kami</a>
      <a href="<?= BASE_URL ?>/modules/pages/faq.php">FAQ</a>
      <a href="<?= BASE_URL ?>/modules/pages/kontak.php">Kontak</a>
    </nav>
    <form class="search-form" action="<?= BASE_URL ?>/modules/katalog/index.php" method="GET">
      <input type="text" name="q" placeholder="Cari menu..." value="<?= clean($_GET['q'] ?? '') ?>">
      <button type="submit">🔍</button>
    </form>
    <div class="header-actions">
      <a href="<?= BASE_URL ?>/modules/cart/index.php" class="cart-icon">🛒 <span class="badge"><?= $cartCount ?></span></a>
      <?php if (is_login()): ?>
        <div class="dropdown">
          <button class="dropbtn">👤 <?= clean(explode(' ', $_SESSION['pengguna_nama'])[0]) ?></button>
          <div class="dropdown-content">
            <a href="<?= BASE_URL ?>/modules/akun/riwayat_pesanan.php">Pesanan Saya</a>
            <a href="<?= BASE_URL ?>/modules/akun/profil.php">Profil</a>
            <a href="<?= BASE_URL ?>/modules/auth/logout.php">Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn-outline">Masuk</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main class="container main-content">
<?php show_flash(); ?>
