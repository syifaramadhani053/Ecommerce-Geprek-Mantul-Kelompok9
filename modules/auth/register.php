<?php
/**
 * FILE INI: REGISTRASI PELANGGAN
 */
require_once __DIR__ . '/../../config/config.php';
$pageTitle = "Daftar";

if (is_login()) redirect(BASE_URL . '/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $no_telp = trim($_POST['no_telp']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id FROM pengguna WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        set_flash('danger', 'Email sudah terdaftar. Silakan gunakan email lain.');
    } elseif (strlen($password) < 6) {
        set_flash('danger', 'Password minimal 6 karakter.');
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        // peran selalu 'pelanggan' untuk pendaftaran mandiri lewat website
        $stmt = $pdo->prepare("INSERT INTO pengguna (nama_lengkap, email, kata_sandi, no_telp, peran) VALUES (?,?,?,?, 'pelanggan')");
        $stmt->execute([$nama, $email, $hash, $no_telp]);
        set_flash('success', 'Pendaftaran berhasil! Silakan masuk.');
        redirect(BASE_URL . '/modules/auth/login.php');
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="form-box">
  <h2>Buat Akun Baru</h2>
  <?php show_flash(); ?>
  <form method="POST">
    <div class="form-group">
      <label>Nama Lengkap</label>
      <input type="text" name="nama_lengkap" required>
    </div>
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" required>
    </div>
    <div class="form-group">
      <label>No. HP / WhatsApp</label>
      <input type="text" name="no_telp" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" required>
    </div>
    <button type="submit" class="btn-primary" style="width:100%">Daftar</button>
  </form>
  <div class="form-footer">
    Sudah punya akun? <a href="<?= BASE_URL ?>/modules/auth/login.php">Masuk di sini</a>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
