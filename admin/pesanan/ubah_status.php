<?php
/**
 * FILE: admin/pesanan/ubah_status.php -- PENERIMA UBAH STATUS CEPAT
 */
require_once __DIR__ . '/../includes/bootstrap.php';
need('pesanan');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(ADMIN_URL . '/pesanan/index.php');
[$ok, $pesan] = ubah_status_pesanan($pdo, (int)$_POST['id'], $_POST['status'], $_SESSION['admin_id']);
set_flash($ok ? 'success' : 'danger', $pesan);
$kembali = $_POST['kembali'] ?? '';
redirect(strpos($kembali, ADMIN_URL) === 0 ? $kembali : ADMIN_URL . '/pesanan/index.php');
