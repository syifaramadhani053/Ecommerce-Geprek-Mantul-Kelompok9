// =====================================================================
// FILE INI: INTERAKSI TAMPILAN (bukan logika bisnis) hanya untuk efek buka/tutup FAQ dan konfirmasi hapus. Tidak berhubungan dengan database sama sekali.
// =====================================================================
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.faq-q').forEach(function (q) {
    q.addEventListener('click', function () {
      q.parentElement.classList.toggle('open');
    });
  });

  document.querySelectorAll('.btn-confirm-delete').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      if (!confirm('Yakin ingin menghapus item ini?')) {
        e.preventDefault();
      }
    });
  });
});
