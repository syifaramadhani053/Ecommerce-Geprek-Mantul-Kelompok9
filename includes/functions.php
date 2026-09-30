<?php
/**
 * ================================
 * FILE INI: FUNGSI BANTUAN UMUM
 * ================================
 */

function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}

function is_login() {
    return isset($_SESSION['pengguna_id']);
}

function require_login() {
    if (!is_login()) {
        header("Location: " . BASE_URL . "/modules/auth/login.php");
        exit;
    }
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function clean($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function generate_no_pesanan() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

function cart_count($pdo, $pengguna_id) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(kuantitas),0) as total FROM keranjang WHERE pengguna_id = ?");
    $stmt->execute([$pengguna_id]);
    return (int)$stmt->fetch()['total'];
}

function set_flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function show_flash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        $class = $f['type'] === 'success' ? 'alert-success' : 'alert-danger';
        echo '<div class="alert ' . $class . '">' . clean($f['msg']) . '</div>';
        unset($_SESSION['flash']);
    }
}

// Label & warna badge untuk status pesanan
function status_label($status) {
    $map = [
        'menunggu_pembayaran' => ['Menunggu Pembayaran', 'secondary'],
        'diproses'            => ['Diproses', 'info'],
        'dikirim'             => ['Dikirim', 'warning'],
        'selesai'             => ['Selesai', 'success'],
        'dibatalkan'          => ['Dibatalkan', 'danger'],
    ];
    return $map[$status] ?? [$status, 'secondary'];
}

/**
 * Hitung potongan promo berdasarkan data tabel `promo`.
 */
function hitung_potongan_promo($promo, $subtotal) {
    if (!$promo) return 0;
    if ($subtotal < $promo['minimal_belanja']) return 0;

    if ($promo['jenis_diskon'] === 'persen') {
        $potongan = $subtotal * ((float)$promo['nilai_diskon'] / 100);
        if (!empty($promo['maksimal_potongan'])) {
            $potongan = min($potongan, (float)$promo['maksimal_potongan']);
        }
    } else {
        $potongan = (float)$promo['nilai_diskon'];
    }
    return min($potongan, $subtotal); // potongan tidak boleh melebihi subtotal
}
