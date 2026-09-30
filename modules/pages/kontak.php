<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = "Kontak";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = clean($_POST['nama']);
    $email = clean($_POST['email']);
    $subjek = clean($_POST['subjek']);
    $pesan = clean($_POST['pesan']);

    $stmt = $pdo->prepare("INSERT INTO pesan_kontak (nama, email, subjek, pesan) VALUES (?,?,?,?)");
    $stmt->execute([$nama, $email, $subjek, $pesan]);

    set_flash('success', 'Pesan Anda berhasil dikirim. Tim kami akan segera membalas.');
    redirect(BASE_URL . '/modules/pages/kontak.php');
}

include __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-title">Hubungi Kami</h1>
<div class="detail-wrap">
  <div style="flex:1;">
    <div style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px;">
      <h3>Informasi Kontak</h3>
      <p style="margin-top:10px;">📱 WhatsApp: 0812-3456-7890</p>
      <p>✉️ Email: halo@geprekmantul.id</p>
      <p>📸 Instagram: @geprekmantul</p>
      <p>🕒 Jam Operasional: Setiap hari, 10.00 - 21.00 WIB</p>
    </div>
  </div>
  <div style="flex:1.3;min-width:300px;">
    <div style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px;">
      <h3>Kirim Pesan</h3>
      <form method="POST">
        <div class="form-group">
          <label>Nama</label>
          <input type="text" name="nama" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" required>
        </div>
        <div class="form-group">
          <label>Subjek</label>
          <input type="text" name="subjek" required>
        </div>
        <div class="form-group">
          <label>Pesan</label>
          <textarea name="pesan" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn-primary">Kirim Pesan</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
