<?php
/**
 * FILE: admin/includes/status_form.php -- DROPDOWN UBAH STATUS PESANAN (dipakai di Dashboard & Daftar Pesanan)
 * Variabel yang dibutuhkan: $p (baris pesanan). Mengirim ke pesanan/ubah_status.php.
 * BOLEH DIEDIT: tampilan. JANGAN DIUBAH: nama field (id, status, kembali) & atribut data-auto.
 */
if (in_array($p['status_pesanan'], ['dibatalkan', 'selesai'], true)) { echo pill_status($p['status_pesanan']); return; }
?>
<form method="POST" action="<?= ADMIN_URL ?>/pesanan/ubah_status.php" style="display:inline"><?= csrf() ?>
  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="kembali" value="<?= clean($_SERVER['REQUEST_URI']) ?>">
  <select name="status" class="inline-status" data-auto="1">
    <?php foreach (['menunggu_pembayaran', 'diproses', 'dikirim', 'selesai', 'dibatalkan'] as $s): ?>
      <option value="<?= $s ?>" <?= $p['status_pesanan'] === $s ? 'selected' : '' ?>><?= status_info($s)[0] ?></option>
    <?php endforeach; ?>
  </select>
</form>
