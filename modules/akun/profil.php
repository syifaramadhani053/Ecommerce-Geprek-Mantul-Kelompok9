<?php
require_once __DIR__ . '/../../config/config.php';
require_login();
$pageTitle = "Profil Saya";

$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id = ?");
$stmt->execute([$_SESSION['pengguna_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = clean($_POST['nama_lengkap']);
    $no_telp = clean($_POST['no_telp']);
    $alamat = clean($_POST['alamat']);

    $stmt = $pdo->prepare("UPDATE pengguna SET nama_lengkap=?, no_telp=?, alamat=? WHERE id=?");
    $stmt->execute([$nama, $no_telp, $alamat, $_SESSION['pengguna_id']]);
    $_SESSION['pengguna_nama'] = $nama;

    set_flash('success', 'Profil berhasil diperbarui.');
    redirect(BASE_URL . '/modules/akun/profil.php');
}

include __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-title">Profil Saya</h1>

<div class="form-box" style="max-width:500px;">
  <form method="POST">
    <div class="form-group">
      <label>Nama Lengkap</label>
      <input type="text" name="nama_lengkap" value="<?= clean($user['nama_lengkap']) ?>" required>
    </div>
    <div class="form-group">
      <label>Email</label>
      <input type="email" value="<?= clean($user['email']) ?>" disabled>
    </div>
    <div class="form-group">
      <label>No. HP / WhatsApp</label>
      <input type="text" name="no_telp" value="<?= clean($user['no_telp']) ?>" required>
    </div>
    <div class="form-group">
      <label>Alamat</label>
      <textarea name="alamat" rows="3"><?= clean($user['alamat']) ?></textarea>
    </div>
    <button type="submit" class="btn-primary" style="width:100%">Simpan Perubahan</button>
  </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
