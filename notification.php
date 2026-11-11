<?php
/**
 * =====================================================================
 * FILE INI: WEBHOOK NOTIFIKASI MIDTRANS (Payment Notification URL)
 * =====================================================================
 * Daftarkan URL file ini di dashboard Midtrans:
 *   Settings -> Configuration -> Payment Notification URL
 *   contoh: https://domain-anda.com/geprek-mantul-erp/api/notification.php
 *
 * Catatan:
 *  - Harus bisa diakses publik (bukan localhost). Untuk pengujian lokal
 *    gunakan tunnel seperti ngrok / cloudflared.
 *  - File ini TIDAK memakai login/session karena dipanggil server Midtrans.
 *  - Keamanan: setiap notifikasi diverifikasi lewat signature_key.
 */
define('NO_SESSION', true);
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

function balas(int $kode, bool $ok, string $pesan): void
{
    http_response_code($kode);
    echo json_encode(['ok' => $ok, 'message' => $pesan]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    balas(405, false, 'Method not allowed. Gunakan HTTP POST.');
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    balas(400, false, 'Body bukan JSON yang valid.');
}

foreach (['order_id', 'status_code', 'gross_amount', 'signature_key', 'transaction_status'] as $wajib) {
    if (!isset($payload[$wajib]) || !is_scalar($payload[$wajib])) {
        balas(400, false, "Field $wajib tidak ada.");
    }
}

// Verifikasi Signature Key: SHA512(order_id + status_code + gross_amount + ServerKey)
if (!midtrans_verify_signature($payload)) {
    error_log('[MIDTRANS] Signature tidak valid untuk order_id ' . $payload['order_id']
        . ' dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    balas(403, false, 'Signature tidak valid.');
}

// Perbarui pesanan (lunas / dibatalkan / kedaluwarsa + kembalikan stok jika perlu)
$hasil = midtrans_apply_status($pdo, (string) $payload['order_id'], $payload);

balas($hasil['http'], $hasil['ok'], $hasil['message']);
