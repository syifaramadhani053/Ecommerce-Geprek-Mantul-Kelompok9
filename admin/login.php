<?php
/**
 * FILE: admin/login.php -- LOGIN ADMIN/STAF
 */
require_once __DIR__ . '/includes/bootstrap.php';
if (admin_login()) redirect(ADMIN_URL . '/dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s = $pdo->prepare("SELECT * FROM pengguna WHERE email = ? AND status_aktif = 1 AND peran IN ('superadmin','gudang','kasir')");
    $s->execute([trim($_POST['email'])]); $u = $s->fetch();
    if ($u && password_verify($_POST['password'], $u['kata_sandi'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $u['id']; $_SESSION['admin_nama'] = $u['nama_lengkap']; $_SESSION['admin_peran'] = $u['peran'];
        redirect(ADMIN_URL . '/dashboard.php');
    }
    $error = 'Email/password salah, atau akun ini bukan akun staf.';
}
$logo = gambar_url('pengaturan', setting('logo')) ?: ADMIN_URL . '/assets/img/Logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/admin.css">
</head>
<body class="login-bg">
  <div class="login-box">
  <div class="brand">
    <img src="<?= $logo ?>" alt=""><span><?= clean(strtoupper(setting('nama_toko', 'Geprek Mantul'))) ?></span>
  </div>
  <h2>Masuk</h2>
  <?php if ($error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endif; ?>
  <form method="POST"><?= csrf() ?>
    <div class="form-group"><label>Email</label><input type="email" name="email" required autofocus></div>
    <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
    <button class="btn btn-merah" style="width:100%">Masuk</button>
  </form>
  </div>
</body>
</html>
