<?php
require __DIR__ . '/config/koneksi.php';

$peranAdmin = ['superadmin', 'gudang', 'kasir'];

// Kalau sudah login, langsung arahkan sesuai peran
if (isset($_SESSION['pengguna_id'])) {
    header('Location: ' . (in_array($_SESSION['peran'], $peranAdmin) ? 'admin/dashboard.php' : 'index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pw    = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT id, nama_lengkap, kata_sandi, peran, status_aktif FROM pengguna WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();

    if (!$u || !password_verify($pw, $u['kata_sandi'])) {
        $error = 'Email atau password salah.';
    } elseif (!$u['status_aktif']) {
        $error = 'Akun kamu nonaktif. Hubungi pemilik toko.';
    } else {
        session_regenerate_id(true);
        $_SESSION['pengguna_id'] = $u['id'];
        $_SESSION['nama']        = $u['nama_lengkap'];
        $_SESSION['peran']       = $u['peran'];
        header('Location: ' . (in_array($u['peran'], $peranAdmin) ? 'admin/dashboard.php' : 'index.php'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Geprek Mantul</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="login-body">
  <form class="login-card" method="post" autocomplete="on">
    <div class="login-brand">
      <img src="assets/img/produk/Logo.png" alt="" onerror="this.outerHTML='<span class=&quot;logo-fallback&quot;>🍗</span>'">
      <span>GEPREK MANTUL</span>
    </div>
    <h1>Masuk</h1>
    <?php if ($error): ?><div class="login-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
    <button type="submit">Masuk</button>
  </form>
</body>
</html>
