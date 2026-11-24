<?php
/**
 * FILE: admin/pelanggan/form.php -- TAMBAH & EDIT PELANGGAN
 * password_hash() dan penguncian peran='pelanggan'
 * (halaman ini tidak bisa dipakai membuat akun staf; gunakan menu Pengguna & Hak Akses).
 */
require_once __DIR__ . '/../includes/bootstrap.php';
need('pelanggan');
$id = (int)($_GET['id'] ?? 0); $u = null;
if ($id) { $s = $pdo->prepare("SELECT * FROM pengguna WHERE id=? AND peran='pelanggan'"); $s->execute([$id]); $u = $s->fetch(); if (!$u) redirect(ADMIN_URL . '/pelanggan/index.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pw = $_POST['password'] ?? '';
        if (!$u && strlen($pw) < 6) throw new Exception('Password minimal 6 karakter untuk pelanggan baru.');
        if ($u && $pw !== '' && strlen($pw) < 6) throw new Exception('Password baru minimal 6 karakter.');
        $d = [trim($_POST['nama_lengkap']), trim($_POST['email']), trim($_POST['no_telp']), trim($_POST['alamat']), (int)$_POST['status_aktif']];
        if ($u) {
            $pdo->prepare("UPDATE pengguna SET nama_lengkap=?,email=?,no_telp=?,alamat=?,status_aktif=? WHERE id=? AND peran='pelanggan'")->execute([...$d, $id]);
            if ($pw !== '') $pdo->prepare("UPDATE pengguna SET kata_sandi=? WHERE id=?")->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
        } else $pdo->prepare("INSERT INTO pengguna (nama_lengkap,email,no_telp,alamat,status_aktif,kata_sandi,peran) VALUES (?,?,?,?,?,?, 'pelanggan')")->execute([...$d, password_hash($pw, PASSWORD_DEFAULT)]);
        set_flash('success', 'Data pelanggan disimpan.'); redirect(ADMIN_URL . '/pelanggan/index.php');
    } catch (PDOException $e) { set_flash('danger', 'Email sudah terdaftar.'); } catch (Exception $e) { set_flash('danger', $e->getMessage()); }
    redirect(ADMIN_URL . '/pelanggan/form.php' . ($id ? "?id=$id" : ''));
}
$v = fn($k, $d = '') => clean($u[$k] ?? $d);
page_start($u ? 'Edit Pelanggan' : 'Tambah Pelanggan', 'pelanggan');
?>
<div class="panel" style="max-width:720px"><form method="POST"><?= csrf() ?>
  <div class="baris"><div class="form-group"><label>Nama Lengkap</label><input name="nama_lengkap" value="<?= $v('nama_lengkap') ?>" required></div><div class="form-group"><label>Email</label><input type="email" name="email" value="<?= $v('email') ?>" required></div></div>
  <div class="baris"><div class="form-group"><label>No. Telepon</label><input name="no_telp" value="<?= $v('no_telp') ?>"></div>
    <div class="form-group"><label>Password <?= $u ? '(kosongkan jika tidak diganti)' : '' ?></label><input type="password" name="password" <?= $u ? '' : 'required' ?>></div></div>
  <div class="form-group"><label>Alamat</label><textarea name="alamat" rows="3"><?= $v('alamat') ?></textarea></div>
  <div class="form-group"><label>Status Akun</label><select name="status_aktif"><option value="1" <?= ($u['status_aktif'] ?? 1) ? 'selected' : '' ?>>Aktif</option><option value="0" <?= isset($u) && !$u['status_aktif'] ? 'selected' : '' ?>>Diblokir (tidak bisa login)</option></select></div>
  <button class="btn btn-merah">Simpan</button> <a class="btn btn-abu" href="index.php">Batal</a></form></div>
<?php page_end();
