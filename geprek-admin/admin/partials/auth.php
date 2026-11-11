<?php
// Include di paling atas setiap halaman admin
require_once __DIR__ . '/../../config/koneksi.php';

if (!isset($_SESSION['pengguna_id']) || !in_array($_SESSION['peran'], ['superadmin', 'gudang', 'kasir'])) {
    header('Location: ../login.php');
    exit;
}
