<?php
/**
 * =====================================================================
 * FILE INI: HELPER MIDTRANS (Snap API via cURL, tanpa Composer)
 * =====================================================================
 */

if (!class_exists('MidtransException')) {
    class MidtransException extends Exception {}
}

/* =====================================================================
 * 1. URL ENDPOINT
 * ===================================================================== */

function midtrans_snap_js_url(): string
{
    return MIDTRANS_IS_PRODUCTION
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js';
}

function midtrans_snap_api_url(): string
{
    return MIDTRANS_IS_PRODUCTION
        ? 'https://app.midtrans.com/snap/v1/transactions'
        : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
}

function midtrans_core_api_url(): string
{
    return MIDTRANS_IS_PRODUCTION
        ? 'https://api.midtrans.com'
        : 'https://api.sandbox.midtrans.com';
}

/* =====================================================================
 * 2. HTTP CLIENT (cURL)
 * ===================================================================== */

function midtrans_http(string $method, string $url, ?array $body = null, int $timeout = 20): array
{
    if (!function_exists('curl_init')) {
        throw new MidtransException('Ekstensi cURL PHP belum aktif (aktifkan extension=curl di php.ini).');
    }
    
    $serverKey = trim((string) MIDTRANS_SERVER_KEY);
    if ($serverKey === '' || strpos($serverKey, 'GANTI_DENGAN') !== false) {
        throw new MidtransException('MIDTRANS_SERVER_KEY belum diisi di config/config.php.');
    }

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: GeprekMantul-ERP/1.0 (PHP ' . PHP_VERSION . ')',
        'Authorization: Basic ' . base64_encode($serverKey . ':'),
    ];

    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => MIDTRANS_SSL_VERIFY,
        CURLOPT_SSL_VERIFYHOST => MIDTRANS_SSL_VERIFY ? 2 : 0,
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    curl_setopt_array($ch, $opts);

    $raw   = curl_exec($ch);
    $errno = curl_errno($ch);
    $err   = curl_error($ch);
    $http  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($raw === false) {
        throw new MidtransException("Gagal terhubung ke Midtrans (cURL #$errno): $err");
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new MidtransException("Respons Midtrans tidak valid (HTTP $http).");
    }

    return ['http' => $http, 'data' => $data];
}

function midtrans_key_info(): string
{
    $key = trim((string) MIDTRANS_SERVER_KEY);
    return 'mode=' . (MIDTRANS_IS_PRODUCTION ? 'PRODUCTION' : 'SANDBOX')
        . ', endpoint=' . midtrans_snap_api_url()
        . ', server_key awalan "' . substr($key, 0, 14) . '"'
        . ', panjang ' . strlen($key)
        . ', akhir "...' . substr($key, -3) . '"';
}

/* =====================================================================
 * 3. SNAP TOKEN
 * ===================================================================== */

function midtrans_build_snap_params(string $orderId, int $grossAmount, array $items, array $customer): array
{
    $jumlahItem = 0;
    foreach ($items as $item) {
        $jumlahItem += ((int) $item['price']) * ((int) $item['quantity']);
    }
    
    // PERBAIKAN: Kode pengurangan matematika yang sebelumnya terpotong
    $selisih = $grossAmount - $jumlahItem;
    
    if ($selisih !== 0) {
        $items[] = ['id' => 'PENYESUAIAN', 'price' => $selisih, 'quantity' => 1, 'name' => 'Penyesuaian Pembulatan'];
    }

    $params = [
        'transaction_details' => [
            'order_id'     => $orderId,
            'gross_amount' => $grossAmount,
        ],
        'item_details'     => array_values($items),
        'customer_details' => [
            'first_name' => (string) ($customer['nama'] ?? 'Pelanggan'),
            'email'      => (string) ($customer['email'] ?? ''),
            'phone'      => (string) ($customer['telp'] ?? ''),
        ],
        'expiry' => [
            'unit'     => 'minutes',
            'duration' => (int) MIDTRANS_EXPIRY_MINUTES,
        ],
    ];

    $enabled = MIDTRANS_ENABLED_PAYMENTS;
    if (is_array($enabled) && count($enabled) > 0) {
        $params['enabled_payments'] = array_values($enabled);
    }

    return $params;
}

function midtrans_create_snap_token(array $params): array
{
    $res = midtrans_http('POST', midtrans_snap_api_url(), $params, 25);

    if ($res['http'] >= 200 && $res['http'] < 300 && !empty($res['data']['token'])) {
        return [
            'token'        => (string) $res['data']['token'],
            'redirect_url' => $res['data']['redirect_url'] ?? null,
        ];
    }

    if (isset($res['data']['error_messages'])) {
        $pesan = implode('; ', (array) $res['data']['error_messages']);
    } else {
        $pesan = json_encode($res['data']);
    }
    if (defined('MIDTRANS_DEBUG') && MIDTRANS_DEBUG) {
        $pesan .= ' [' . midtrans_key_info() . ']';
    }
    throw new MidtransException('Midtrans menolak permintaan (HTTP ' . $res['http'] . '): ' . $pesan);
}

function midtrans_make_order_id(string $noPesanan): string
{
    $bersih = preg_replace('/[^A-Za-z0-9_\-.~]/', '', $noPesanan);
    return substr($bersih, 0, 38) . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/* =====================================================================
 * 4. STATUS TRANSAKSI & SIGNATURE
 * ===================================================================== */

function midtrans_get_status(string $orderId): array
{
    $url = midtrans_core_api_url() . '/v2/' . rawurlencode($orderId) . '/status';
    $res = midtrans_http('GET', $url, null, 10);

    $kode = (string) ($res['data']['status_code'] ?? '');
    if (!in_array($kode, ['200', '201', '202', '404', '407'], true)) {
        $pesan = (string) ($res['data']['status_message'] ?? 'tanpa pesan');
        throw new MidtransException("Cek status Midtrans gagal (status_code $kode): $pesan");
    }
    return $res['data'];
}

function midtrans_verify_signature(array $payload): bool
{
    foreach (['order_id', 'status_code', 'gross_amount', 'signature_key'] as $k) {
        if (!isset($payload[$k]) || !is_scalar($payload[$k])) {
            return false;
        }
    }
    $expected = hash('sha512',
        $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . trim((string) MIDTRANS_SERVER_KEY)
    );
    return hash_equals($expected, strtolower((string) $payload['signature_key']));
}

function midtrans_map_status(array $payload): ?string
{
    $transaksi = (string) ($payload['transaction_status'] ?? '');
    $fraud     = (string) ($payload['fraud_status'] ?? '');

    switch ($transaksi) {
        case 'capture':
            if ($fraud === 'deny') return 'deny';
            if ($fraud === 'challenge') return 'pending';
            return 'settlement';
        case 'settlement':
            return 'settlement';
        case 'pending':
            return 'pending';
        case 'deny':
        case 'failure':
            return 'deny';
        case 'cancel':
            return 'cancel';
        case 'expire':
            return 'expire';
        default:
            return null;
    }
}

function midtrans_apply_status(PDO $pdo, string $orderIdMidtrans, array $payload): array
{
    $baru = midtrans_map_status($payload);
    if ($baru === null) {
        return ['ok' => true, 'http' => 200,
            'message' => 'Status diabaikan: ' . (string) ($payload['transaction_status'] ?? '-')];
    }

    $tipe = isset($payload['payment_type']) ? substr((string) $payload['payment_type'], 0, 50) : null;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM pesanan WHERE order_id_midtrans = ? FOR UPDATE");
        $stmt->execute([$orderIdMidtrans]);
        $p = $stmt->fetch();

        if (!$p) {
            $pdo->rollBack();
            return ['ok' => false, 'http' => 200, 'message' => 'Pesanan tidak ditemukan, diabaikan.'];
        }

        if ($p['metode_pembayaran'] !== 'online') {
            $pdo->rollBack();
            return ['ok' => false, 'http' => 200, 'message' => 'Bukan pesanan online, diabaikan.'];
        }

        if (isset($payload['gross_amount'])
            && abs((float) $payload['gross_amount'] - (float) $p['total_bayar']) > 1) {
            $pdo->rollBack();
            error_log('[MIDTRANS] Nominal tidak cocok untuk ' . $orderIdMidtrans
                . ' (midtrans=' . $payload['gross_amount'] . ', db=' . $p['total_bayar'] . ')');
            return ['ok' => false, 'http' => 400, 'message' => 'Nominal tidak cocok.'];
        }

        $lama = $p['status_midtrans'];

        if ($lama === 'settlement') {
            $pdo->rollBack();
            return ['ok' => true, 'http' => 200, 'message' => 'Pesanan sudah lunas, diabaikan.'];
        }

        if (in_array($lama, ['cancel', 'expire'], true) && $baru !== 'settlement') {
            $pdo->rollBack();
            return ['ok' => true, 'http' => 200, 'message' => 'Pesanan sudah final, diabaikan.'];
        }

        if ($baru === 'settlement') {
            $pdo->prepare("UPDATE pesanan SET
                    status_midtrans = 'settlement',
                    status_pembayaran = 'lunas',
                    status_pesanan = CASE WHEN status_pesanan = 'menunggu_pembayaran' THEN 'diproses' ELSE status_pesanan END,
                    tipe_pembayaran_midtrans = COALESCE(?, tipe_pembayaran_midtrans),
                    waktu_bayar = NOW()
                WHERE id = ?")->execute([$tipe, $p['id']]);

            if ($p['status_pesanan'] === 'dibatalkan') {
                error_log('[MIDTRANS] PERHATIAN: pembayaran masuk untuk pesanan yang sudah dibatalkan: '
                    . $p['no_pesanan'] . '. Periksa manual (kirim pesanan atau refund).');
            }

        } elseif ($baru === 'cancel' || $baru === 'expire') {
            if ($p['status_pesanan'] === 'menunggu_pembayaran') {
                $stmtItems = $pdo->prepare("SELECT produk_id, kuantitas FROM detail_pesanan WHERE pesanan_id = ?");
                $stmtItems->execute([$p['id']]);
                $detail = $stmtItems->fetchAll();

                $stmtStok = $pdo->prepare("SELECT stok FROM produk WHERE id = ? FOR UPDATE");
                $stmtUp   = $pdo->prepare("UPDATE produk SET stok = stok + ? WHERE id = ?");
                $stmtMut  = $pdo->prepare("INSERT INTO mutasi_stok
                    (produk_id, jenis_mutasi, jumlah, stok_sebelum, stok_sesudah, nomor_referensi, keterangan, pengguna_id)
                    VALUES (?, 'retur_masuk', ?, ?, ?, ?, ?, NULL)");

                $alasan = ($baru === 'expire') ? 'kedaluwarsa' : 'dibatalkan';

                foreach ($detail as $d) {
                    $stmtStok->execute([$d['produk_id']]);
                    $stokSebelum = (int) $stmtStok->fetchColumn();
                    $stokSesudah = $stokSebelum + (int) $d['kuantitas'];

                    $stmtUp->execute([$d['kuantitas'], $d['produk_id']]);
                    $stmtMut->execute([
                        $d['produk_id'], $d['kuantitas'], $stokSebelum, $stokSesudah,
                        $p['no_pesanan'], 'Stok dikembalikan: pembayaran online ' . $alasan,
                    ]);
                }

                $pdo->prepare("UPDATE pesanan SET status_pesanan = 'dibatalkan' WHERE id = ?")->execute([$p['id']]);
            }

            $pdo->prepare("UPDATE pesanan SET status_midtrans = ?,
                    tipe_pembayaran_midtrans = COALESCE(?, tipe_pembayaran_midtrans)
                WHERE id = ?")->execute([$baru, $tipe, $p['id']]);

        } else {
            $pdo->prepare("UPDATE pesanan SET status_midtrans = ?,
                    tipe_pembayaran_midtrans = COALESCE(?, tipe_pembayaran_midtrans)
                WHERE id = ?")->execute([$baru, $tipe, $p['id']]);
        }

        $pdo->commit();
        return ['ok' => true, 'http' => 200, 'message' => "Status diperbarui: $baru"];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[MIDTRANS] Gagal memperbarui status ' . $orderIdMidtrans . ': ' . $e->getMessage());
        return ['ok' => false, 'http' => 500, 'message' => 'Kesalahan server saat memperbarui status.'];
    }
}

function midtrans_sync_order(PDO $pdo, array $pesanan): string
{
    if (empty($pesanan['order_id_midtrans'])) {
        return 'error';
    }
    try {
        $data = midtrans_get_status($pesanan['order_id_midtrans']);
    } catch (MidtransException $e) {
        error_log('[MIDTRANS] sync gagal: ' . $e->getMessage());
        return 'error';
    }

    if ((string) ($data['status_code'] ?? '') === '404') {
        return 'notfound';
    }

    $hasil = midtrans_apply_status($pdo, $pesanan['order_id_midtrans'], $data);
    return $hasil['ok'] ? 'updated' : 'error';
}

function midtrans_expire_stale_orders(PDO $pdo, ?int $penggunaId = null, int $limit = 10): int
{
    $menit = (int) MIDTRANS_EXPIRY_MINUTES + 5; 
    $limit = max(1, (int) $limit);

    $sql = "SELECT * FROM pesanan
            WHERE metode_pembayaran = 'online'
              AND status_pesanan = 'menunggu_pembayaran'
              AND status_midtrans IN ('pending', 'deny')
              AND dibuat_pada < DATE_SUB(NOW(), INTERVAL $menit MINUTE)";
    $params = [];
    if ($penggunaId !== null) {
        $sql .= " AND pengguna_id = ?";
        $params[] = $penggunaId;
    }
    $sql .= " ORDER BY dibuat_pada ASC LIMIT $limit";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $daftar = $stmt->fetchAll();

    $diproses = 0;
    foreach ($daftar as $p) {
        $hasil = midtrans_sync_order($pdo, $p);

        if ($hasil === 'notfound') {
            midtrans_apply_status($pdo, $p['order_id_midtrans'], ['transaction_status' => 'expire']);
            $diproses++;
        } elseif ($hasil === 'updated') {
            $diproses++;
        }
    }
    return $diproses;
}

/* =====================================================================
 * 5. HELPER TAMPILAN
 * ===================================================================== */

function midtrans_bisa_bayar(array $p): bool
{
    return ($p['metode_pembayaran'] ?? '') === 'online'
        && ($p['status_pesanan'] ?? '') === 'menunggu_pembayaran'
        && in_array($p['status_midtrans'] ?? '', ['pending', 'deny'], true)
        && !empty($p['snap_token']);
}

function midtrans_nama_metode(string $metode): string
{
    switch ($metode) {
        case 'online': return 'Pembayaran Online (QRIS & Bank)';
        case 'COD':    return 'COD (Bayar di Tempat)';
        default:       return 'Transfer Bank Manual';
    }
}

function midtrans_label_pembayaran(array $p): array
{
    $hijau  = ['#d4f4dd', '#1c7c41'];
    $kuning = ['#fff3cd', '#8a6d00'];
    $merah  = ['#fde2e1', '#c0392b'];

    if (($p['metode_pembayaran'] ?? '') === 'online') {
        switch ($p['status_midtrans'] ?? '') {
            case 'settlement': return ['Lunas', $hijau[0], $hijau[1]];
            case 'cancel':     return ['Dibatalkan', $merah[0], $merah[1]];
            case 'expire':     return ['Dibatalkan (Kedaluwarsa)', $merah[0], $merah[1]];
            case 'deny':       return ['Ditolak', $merah[0], $merah[1]];
            default:           return ['Pending', $kuning[0], $kuning[1]];
        }
    }

    if (($p['status_pembayaran'] ?? '') === 'lunas') {
        return ['Lunas', $hijau[0], $hijau[1]];
    }
    if (($p['status_pesanan'] ?? '') === 'dibatalkan') {
        return ['Dibatalkan', $merah[0], $merah[1]];
    }
    return ['Belum Lunas', $kuning[0], $kuning[1]];
}

function midtrans_badge_pembayaran(array $p): string
{
    [$teks, $bg, $fg] = midtrans_label_pembayaran($p);
    return '<span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700;'
        . 'background:' . $bg . ';color:' . $fg . ';">'
        . htmlspecialchars($teks, ENT_QUOTES, 'UTF-8') . '</span>';
}