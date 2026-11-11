
<?php
require __DIR__ . '/partials/auth.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// =============================================
// PENGATURAN AWAL
// =============================================

if (!isset($_SESSION['csrf_dashboard'])) {
    $_SESSION['csrf_dashboard'] = bin2hex(random_bytes(32));
}

function e($nilai): string {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

function statKartu(
    mysqli $conn,
    string $tabel,
    string $kolomTgl,
    string $where = '1=1'
): array {
    $total = (int)$conn->query(
        "SELECT COUNT(*) AS c FROM $tabel WHERE $where"
    )->fetch_assoc()['c'];

    $hariIni = (int)$conn->query(
        "SELECT COUNT(*) AS c FROM $tabel
         WHERE $where AND DATE($kolomTgl) = CURDATE()"
    )->fetch_assoc()['c'];

    $kemarin = (int)$conn->query(
        "SELECT COUNT(*) AS c FROM $tabel
         WHERE $where
         AND DATE($kolomTgl) = CURDATE() - INTERVAL 1 DAY"
    )->fetch_assoc()['c'];

    if ($kemarin > 0) {
        $persen = round(($hariIni - $kemarin) / $kemarin * 100);
    } else {
        $persen = $hariIni > 0 ? 100 : 0;
    }

    return [$total, $persen];
}

// =============================================
// PROSES UBAH STATUS PESANAN
// =============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_status'])) {
    $pesan = '';
    $tipePesan = 'error';

    try {
        // Hanya superadmin dan kasir yang boleh mengubah status.
        if (!in_array($_SESSION['peran'], ['superadmin', 'kasir'], true)) {
            throw new RuntimeException(
                'Kamu tidak memiliki izin untuk mengubah status pesanan.'
            );
        }

        // Validasi token formulir.
        $token = $_POST['csrf_token'] ?? '';

        if (
            !is_string($token) ||
            !hash_equals($_SESSION['csrf_dashboard'], $token)
        ) {
            throw new RuntimeException(
                'Permintaan tidak valid. Silakan muat ulang halaman.'
            );
        }

        $idPesanan = filter_var(
            $_POST['pesanan_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $statusBaru = $_POST['status_baru'] ?? '';

        $daftarStatus = [
            'menunggu_pembayaran',
            'diproses',
            'dikirim',
            'selesai',
            'dibatalkan'
        ];

        if (!$idPesanan || $idPesanan < 1) {
            throw new RuntimeException('ID pesanan tidak valid.');
        }

        if (!in_array($statusBaru, $daftarStatus, true)) {
            throw new RuntimeException('Pilihan status tidak valid.');
        }

        // Ambil kondisi pesanan yang sebenarnya dari database.
        $stmt = $conn->prepare("
            SELECT status_pesanan, status_pembayaran, metode_pembayaran
            FROM pesanan
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $idPesanan);
        $stmt->execute();

        $pesanan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pesanan) {
            throw new RuntimeException('Pesanan tidak ditemukan.');
        }

        $statusLama = $pesanan['status_pesanan'];
        $statusBayar = $pesanan['status_pembayaran'];
        $metodeBayar = $pesanan['metode_pembayaran'];

        // Aturan perpindahan status.
        $transisi = [
            'menunggu_pembayaran' => ['diproses', 'dibatalkan'],
            'diproses'            => ['dikirim'],
            'dikirim'             => ['selesai'],
            'selesai'             => [],
            'dibatalkan'          => []
        ];

        if ($statusBaru === $statusLama) {
            throw new RuntimeException(
                'Status pesanan tidak berubah.'
            );
        }

        if (!in_array($statusBaru, $transisi[$statusLama] ?? [], true)) {
            throw new RuntimeException(
                'Perubahan status tersebut tidak diperbolehkan.'
            );
        }

        // Pesanan transfer harus lunas sebelum diproses.
        // COD diperbolehkan lanjut sesuai alur toko.
        if (
            $statusBaru === 'diproses' &&
            $statusBayar !== 'lunas' &&
            $metodeBayar !== 'COD'
        ) {
            throw new RuntimeException(
                'Pesanan belum lunas. Periksa pembayaran sebelum memproses pesanan.'
            );
        }

        // Pesanan yang sudah lunas tidak boleh dibatalkan
        // melalui dashboard sederhana ini.
        Pembatalan:
        if (
            $statusBaru === 'dibatalkan' &&
            $statusBayar === 'lunas'
        ) {
            throw new RuntimeException(
                'Pesanan sudah lunas. Gunakan proses pembatalan atau pengembalian dana yang sesuai.'
            );
        }

        // Simpan status baru menggunakan prepared statement.
        $stmt = $conn->prepare("
            UPDATE pesanan
            SET status_pesanan = ?
            WHERE id = ?
        ");
        $stmt->bind_param('si', $statusBaru, $idPesanan);
        $stmt->execute();
        $stmt->close();

        $pesan = 'Status pesanan berhasil diperbarui.';
        $tipePesan = 'success';

    } catch (Throwable $err) {
        $pesan = $err->getMessage();
        $tipePesan = 'error';
    }

    $_SESSION['flash_dashboard'] = [
        'pesan' => $pesan,
        'tipe' => $tipePesan
    ];

    // Menghindari pengiriman formulir ulang ketika halaman dimuat.
    header('Location: dashboard.php');
    exit;
}

// =============================================
// NOTIFIKASI
// =============================================

$notifikasi = $_SESSION['flash_dashboard'] ?? null;
unset($_SESSION['flash_dashboard']);

// =============================================
// STATISTIK DASHBOARD
// =============================================

[$totalPesanan, $pctPesanan] = statKartu(
    $conn,
    'pesanan',
    'tanggal_pesanan'
);

[$totalPelanggan, $pctPelanggan] = statKartu(
    $conn,
    'pengguna',
    'dibuat_pada',
    "peran = 'pelanggan'"
);

[$totalProduk, $pctProduk] = statKartu(
    $conn,
    'produk',
    'dibuat_pada',
    "status = 'aktif'"
);

// Pendapatan dari pesanan lunas yang tidak dibatalkan.
$pendapatan = (float)$conn->query("
    SELECT COALESCE(SUM(total_bayar), 0) AS total
    FROM pesanan
    WHERE status_pembayaran = 'lunas'
      AND status_pesanan <> 'dibatalkan'
")->fetch_assoc()['total'];

$kartu = [
    [
        'Total Pesanan',
        $totalPesanan,
        $pctPesanan,
        '<path d="M6 3h9l4 4v14H6z"/><path d="M9 11h7M9 15h7M9 7h3"/>'
    ],
    [
        'Total Pelanggan',
        $totalPelanggan,
        $pctPelanggan,
        '<circle cx="9" cy="8" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2M17 4a4 4 0 0 1 0 8M19 15a5 5 0 0 1 3 5"/>'
    ],
    [
        'Produk Aktif',
        $totalProduk,
        $pctProduk,
        '<path d="M6 8h12l1 12H5z"/><path d="M9 8c0-3 6-3 6 0"/>'
    ],
    [
        'Pendapatan Lunas',
        rupiah($pendapatan),
        null,
        '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'
    ]
];

// =============================================
// PESANAN TERBARU
// =============================================

$pesananBaru = $conn->query("
    SELECT
        p.id,
        p.no_pesanan,
        p.total_bayar,
        p.status_pesanan,
        p.status_pembayaran,
        p.metode_pembayaran,
        u.nama_lengkap
    FROM pesanan p
    LEFT JOIN pengguna u ON u.id = p.pengguna_id
    ORDER BY p.tanggal_pesanan DESC, p.id DESC
    LIMIT 5
");

// =============================================
// PRODUK DENGAN STOK MENIPIS
// =============================================

$stokMenipis = $conn->query("
    SELECT nama_produk, stok, stok_minimum
    FROM produk
    WHERE status = 'aktif'
      AND stok <= stok_minimum
    ORDER BY stok ASC
    LIMIT 5
");

// Label status pesanan.
$labelStatus = [
    'menunggu_pembayaran' => ['Menunggu pembayaran', 'pink'],
    'diproses'            => ['Diproses', 'yellow'],
    'dikirim'             => ['Dikirim', 'blue'],
    'selesai'             => ['Selesai', 'green'],
    'dibatalkan'          => ['Dibatalkan', 'gray']
];

// Label status pembayaran.
$labelBayar = [
    'belum_lunas' => 'Belum lunas',
    'lunas'       => 'Lunas'
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Dashboard Admin - Geprek Mantul</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
<div class="layout">

    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="content">

        <header class="topbar">
            <h1>Dashboard Admin</h1>

            <div class="user">
                <svg viewBox="0 0 24 24" width="36" height="36">
                    <circle cx="12" cy="12" r="12" fill="#555"/>
                    <circle cx="12" cy="9.5" r="4" fill="#fff"/>
                    <path d="M4.5 20c1.2-4 4-5.5 7.5-5.5s6.3 1.5 7.5 5.5" fill="#fff"/>
                </svg>
                <span><?= e($_SESSION['nama'] ?? 'Admin') ?></span>
            </div>
        </header>

        <section class="body">

            <p class="intro">
                Berikut adalah ringkasan aktivitas toko Geprek Mantul.
            </p>

            <?php if ($notifikasi): ?>
                <div class="alert-message <?= e($notifikasi['tipe']) ?>">
                    <?= e($notifikasi['pesan']) ?>
                </div>
            <?php endif; ?>

            <!-- KARTU STATISTIK -->
            <div class="cards">

                <?php foreach ($kartu as [$label, $nilai, $pct, $icon]): ?>
                    <div class="card">

                        <div class="card-icon">
                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="#e11d1d"
                                 stroke-width="1.8"
                                 stroke-linecap="round"
                                 stroke-linejoin="round">
                                <?= $icon ?>
                            </svg>
                        </div>

                        <div class="card-info">
                            <span class="card-label"><?= e($label) ?></span>
                            <strong><?= e($nilai) ?></strong>
                        </div>

                        <?php if ($pct !== null): ?>
                            <div class="card-growth <?= $pct < 0 ? 'down' : '' ?>">
                                <?= $pct < 0 ? '↓' : '↑' ?>
                                <b><?= abs($pct) ?>%</b>
                                dari kemarin
                            </div>
                        <?php else: ?>
                            <div class="card-growth">
                                Total pesanan lunas
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>

            </div>

            <!-- TABEL PESANAN -->
            <section class="panel dashboard-panel orders-panel">

                <h2>Pesanan Terbaru</h2>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Pembayaran</th>
                                <th>Status Pesanan</th>
                                <th>Ubah Status</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($pesananBaru->num_rows === 0): ?>
                            <tr>
                                <td colspan="6" class="kosong">
                                    Belum ada pesanan.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php while ($r = $pesananBaru->fetch_assoc()): ?>

                            <?php
                            $statusSaatIni = $r['status_pesanan'];

                            $statusTampil = $labelStatus[$statusSaatIni]
                                ?? ['Tidak diketahui', 'gray'];

                            $bolehUbah = in_array(
                                $_SESSION['peran'],
                                ['superadmin', 'kasir'],
                                true
                            );

                            $opsiStatus = [
                                $statusSaatIni => $statusTampil[0]
                            ];

                            if ($statusSaatIni === 'menunggu_pembayaran') {
                                if (
                                    $r['status_pembayaran'] === 'lunas' ||
                                    $r['metode_pembayaran'] === 'COD'
                                ) {
                                    $opsiStatus['diproses'] = 'Diproses';
                                }

                                if ($r['status_pembayaran'] !== 'lunas') {
                                    $opsiStatus['dibatalkan'] = 'Dibatalkan';
                                }
                            } elseif ($statusSaatIni === 'diproses') {
                                $opsiStatus['dikirim'] = 'Dikirim';
                            } elseif ($statusSaatIni === 'dikirim') {
                                $opsiStatus['selesai'] = 'Selesai';
                            }
                            ?>

                            <tr>
                                <td>
                                    #<?= e($r['no_pesanan']) ?>
                                </td>

                                <td>
                                    <?= e($r['nama_lengkap'] ?? 'Pelanggan') ?>
                                </td>

                                <td>
                                    <?= e(rupiah($r['total_bayar'])) ?>
                                </td>

                                <td>
                                    <span class="payment-label">
                                        <?= e($labelBayar[$r['status_pembayaran']] ?? $r['status_pembayaran']) ?>
                                    </span>
                                    <small class="payment-method">
                                        <?= e($r['metode_pembayaran']) ?>
                                    </small>
                                </td>

                                <td>
                                    <span class="badge <?= e($statusTampil[1]) ?>">
                                        <?= e($statusTampil[0]) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($bolehUbah && count($opsiStatus) > 1): ?>

                                        <form method="POST"
                                              action="dashboard.php"
                                              class="status-form">

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?= e($_SESSION['csrf_dashboard']) ?>">

                                            <input type="hidden"
                                                   name="pesanan_id"
                                                   value="<?= (int)$r['id'] ?>">

                                            <select name="status_baru"
                                                    class="status-select"
                                                    required>

                                                <?php foreach ($opsiStatus as $kode => $teks): ?>
                                                    <option value="<?= e($kode) ?>"
                                                        <?= $kode === $statusSaatIni ? 'selected' : '' ?>>
                                                        <?= e($teks) ?>
                                                    </option>
                                                <?php endforeach; ?>

                                            </select>

                                            <button type="submit"
                                                    name="ubah_status"
                                                    value="1"
                                                    class="btn-save">
                                                Simpan
                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <span class="kosong">
                                            <?= $bolehUbah ? 'Status final' : 'Hanya lihat' ?>
                                        </span>

                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                        </tbody>
                    </table>
                </div>

            </section>

            <!-- PERINGATAN STOK -->
            <section class="panel dashboard-panel stock-panel">

                <h2>Perhatian Stok Produk</h2>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Produk</th>
                                <th>Stok Tersedia</th>
                                <th>Stok Minimum</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($stokMenipis->num_rows === 0): ?>
                            <tr>
                                <td colspan="4" class="kosong">
                                    Tidak ada produk dengan stok menipis.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php while ($produk = $stokMenipis->fetch_assoc()): ?>
                            <tr>
                                <td><?= e($produk['nama_produk']) ?></td>
                                <td><?= (int)$produk['stok'] ?></td>
                                <td><?= (int)$produk['stok_minimum'] ?></td>
                                <td>
                                    <?php if ((int)$produk['stok'] <= 0): ?>
                                        <span class="badge gray">Stok habis</span>
                                    <?php else: ?>
                                        <span class="badge yellow">Stok menipis</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                        </tbody>
                    </table>
                </div>

            </section>

        </section>
    </main>
</div>
</body>
</html>
