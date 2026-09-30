<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = "FAQ";

$faqList = $pdo->query("SELECT * FROM faq ORDER BY urutan ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-title">Pertanyaan Umum (FAQ)</h1>
<p class="section-sub">Hal-hal yang sering ditanyakan pelanggan Geprek Mantul.</p>

<div>
  <?php foreach ($faqList as $f): ?>
    <div class="faq-item">
      <div class="faq-q"><?= clean($f['pertanyaan']) ?> <span>+</span></div>
      <div class="faq-a"><p><?= nl2br(clean($f['jawaban'])) ?></p></div>
    </div>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
