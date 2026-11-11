<?php
// Koneksi ke database ERP Geprek Mantul
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_ayam_erp';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}
