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

<section class="contact-page">
  <div class="contact-heading">
    <h1 class="section-title">Kontak Kami</h1>
    <p>Ada pertanyaan, kritik, atau saran? Tim Geprek Mantul siap membantu kamu.</p>
  </div>
  <div class="contact-layout">
    <div class="contact-info">
      <h3>Informasi Kontak</h3>
      <p>Hubungi kami melalui kontak berikut.</p>
      <a href="https://wa.me/6281234567890" target="_blank" rel="noopener noreferrer" class="contact-link">
        <span>📱</span>
        <div>
          <strong>WhatsApp</strong>
          <small>0812-3456-7890</small>
        </div>
      </a>
      <a href="mailto:geprekmantul.house@gmail.com" class="contact-link">
        <span>✉️</span>
        <div>
          <strong>Email</strong>
          <small>geprekmantul.house@gmail.com</small>
        </div>
      </a>
      <a href="https://www.instagram.com/geprekkmantull/" target="_blank" rel="noopener noreferrer" class="contact-link">
        <span>📸</span>
        <div>
          <strong>Instagram</strong>
          <small>@geprekmantul</small>
        </div>
      </a>
      
      <div class="contact-hours">
        <strong>🕒 Jam Operasional</strong>
        <p>Setiap hari, pukul 10.00–21.00 WIB</p>
      </div>
      <div class="contact-note">
        <strong>Butuh bantuan pesanan?</strong>
        <p>Sertakan nomor pesanan agar kami lebih mudah membantu.</p>
      </div>
    </div>
    
    <div class="contact-form-card">
      <h3>Kirim Pesan</h3>
      <p class="contact-description"> Isi formulir berikut dan sampaikan kebutuhan kamu. </p>
      <form method="POST" action="<?= BASE_URL ?>/modules/pages/kontak.php">
        <div class="form-group">
          <label for="nama">Nama</label>
          <input type="text" id="nama" name="nama" placeholder="Masukkan nama kamu" maxlength="100" required>
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="nama@email.com" maxlength="150" required>
        </div>
        <div class="form-group">
          <label for="subjek">Subjek</label>
          <input type="text" id="subjek" name="subjek" placeholder="Contoh: Pertanyaan pesanan" maxlength="150" required>
        </div>
        <div class="form-group">
          <label for="pesan">Pesan</label>
          <textarea id="pesan" name="pesan" rows="4" maxlength="3000" placeholder="Tuliskan pertanyaan, kritik, atau saran kamu..." required></textarea>
        </div>
        
        <button type="submit" class="btn-primary contact-submit"> Kirim Pesan → </button>
        <small class="contact-privacy"> Pesan kamu akan digunakan untuk membantu menanggapi pertanyaan. </small>
      </form>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
