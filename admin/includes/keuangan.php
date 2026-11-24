<?php
/**
 * =====================================================================
 * FILE: admin/includes/keuangan.php -- PENCATATAN KEUANGAN OTOMATIS (JURNAL)
 * =====================================================================
 * Menghubungkan transaksi dengan tabel akun_rekening & jurnal_keuangan
 * (jurnal berpasangan debit/kredit):
 *   Pesanan LUNAS   : Dr Kas/Bank, Dr Potongan Promo | Cr Penjualan, Cr Ongkir
 *                     Dr HPP | Cr Persediaan
 *   Pembatalan/Pembayaran dibalik : jurnal pembalik (JR-)
 *   Pengeluaran     : Dr Beban (6xxx) | Cr Kas
 *   Retur/Refund    : Dr Retur Penjualan | Cr Kas (+ Dr Persediaan | Cr HPP untuk barang layak jual)
 * BOLEH DIEDIT : nilai konstanta kode akun (jika bagan akun Anda berbeda).
 * JANGAN DIUBAH: seluruh fungsi di bawah. Salah edit = laporan keuangan tidak seimbang.
 * =====================================================================
 */
const AKUN_KAS = '1101', AKUN_PERSEDIAAN = '1201', AKUN_PENJUALAN = '4101', AKUN_POTONGAN = '4102',
      AKUN_ONGKIR = '4103', AKUN_RETUR = '4104', AKUN_HPP = '5101';

function jurnal_tulis($pdo, $noJurnal, $tanggal, array $baris, $ket, $ref, $adminId) {
    $d = 0; $k = 0;
    foreach ($baris as $b) { $d += $b[1]; $k += $b[2]; }
    if (abs($d - $k) > 0.009) throw new Exception("Jurnal $noJurnal tidak seimbang (debit $d, kredit $k).");
    $st = $pdo->prepare("INSERT INTO jurnal_keuangan (no_jurnal,tanggal,kode_akun,debit,kredit,keterangan,referensi_transaksi,pengguna_id) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($baris as $b) {
        if ($b[1] == 0 && $b[2] == 0) continue;
        $st->execute([$noJurnal, $tanggal, $b[0], $b[1], $b[2], $ket, $ref, $adminId]);
    }
}

function jurnal_hitung($pdo, $noPesanan, $prefix) {
    $s = $pdo->prepare("SELECT COUNT(DISTINCT no_jurnal) FROM jurnal_keuangan WHERE referensi_transaksi = ? AND no_jurnal LIKE ?");
    $s->execute([$noPesanan, $prefix . '-%']);
    return (int)$s->fetchColumn();
}
function jurnal_terposting($pdo, $noPesanan) { return jurnal_hitung($pdo, $noPesanan, 'JP') > jurnal_hitung($pdo, $noPesanan, 'JR'); }

function jurnal_baris_penjualan($pdo, $p) {
    $s = $pdo->prepare("SELECT COALESCE(SUM(hpp_satuan*kuantitas),0) FROM detail_pesanan WHERE pesanan_id = ?");
    $s->execute([$p['id']]); $hpp = (float)$s->fetchColumn();
    return [
        [AKUN_KAS, (float)$p['total_bayar'], 0], [AKUN_POTONGAN, (float)$p['potongan_promo'], 0],
        [AKUN_PENJUALAN, 0, (float)$p['total_belanja']], [AKUN_ONGKIR, 0, (float)$p['ongkir']],
        [AKUN_HPP, $hpp, 0], [AKUN_PERSEDIAAN, 0, $hpp],
    ];
}
function jurnal_posting_penjualan($pdo, $p, $adminId) {
    if (jurnal_terposting($pdo, $p['no_pesanan'])) return;
    $n = jurnal_hitung($pdo, $p['no_pesanan'], 'JP') + 1;
    jurnal_tulis($pdo, 'JP-' . $p['no_pesanan'] . '-' . $n, date('Y-m-d'), jurnal_baris_penjualan($pdo, $p),
        'Penjualan ' . $p['no_pesanan'], $p['no_pesanan'], $adminId);
}
function jurnal_balik_penjualan($pdo, $p, $adminId) {
    if (!jurnal_terposting($pdo, $p['no_pesanan'])) return;
    $n = jurnal_hitung($pdo, $p['no_pesanan'], 'JR') + 1;
    $baris = array_map(fn($b) => [$b[0], $b[2], $b[1]], jurnal_baris_penjualan($pdo, $p));
    jurnal_tulis($pdo, 'JR-' . $p['no_pesanan'] . '-' . $n, date('Y-m-d'), $baris,
        'Pembalikan penjualan ' . $p['no_pesanan'], $p['no_pesanan'], $adminId);
}

// Menyamakan status_pembayaran pesanan dengan total pembayaran "diterima", plus jurnalnya.
function sinkron_pembayaran($pdo, $pesananId, $adminId) {
    $own = !$pdo->inTransaction(); if ($own) $pdo->beginTransaction();
    try {
        $s = $pdo->prepare("SELECT * FROM pesanan WHERE id = ? FOR UPDATE"); $s->execute([$pesananId]); $p = $s->fetch();
        if (!$p || $p['status_pesanan'] === 'dibatalkan') { if ($own) $pdo->commit(); return; }
        $q = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM pembayaran WHERE pesanan_id = ? AND status = 'diterima'");
        $q->execute([$pesananId]); $sum = (float)$q->fetchColumn();
        $lunas = $p['total_bayar'] > 0 && $sum + 0.009 >= (float)$p['total_bayar'];
        if ($lunas && $p['status_pembayaran'] !== 'lunas') {
            $pdo->prepare("UPDATE pesanan SET status_pembayaran='lunas' WHERE id=?")->execute([$pesananId]);
            jurnal_posting_penjualan($pdo, $p, $adminId);
            if ($p['status_pesanan'] === 'menunggu_pembayaran') $pdo->prepare("UPDATE pesanan SET status_pesanan='diproses' WHERE id=?")->execute([$pesananId]);
        } elseif (!$lunas && $p['status_pembayaran'] === 'lunas') {
            $pdo->prepare("UPDATE pesanan SET status_pembayaran='belum_lunas' WHERE id=?")->execute([$pesananId]);
            jurnal_balik_penjualan($pdo, $p, $adminId);
        }
        if ($own) $pdo->commit();
    } catch (Exception $e) { if ($own && $pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}

function catat_pembayaran($pdo, $pesananId, $metode, $jumlah, $adminId, $status = 'diterima', $bukti = null, $catatan = null, $tanggal = null) {
    $pdo->prepare("INSERT INTO pembayaran (pesanan_id,metode,jumlah,status,tanggal_bayar,bukti,catatan,dicatat_oleh) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$pesananId, $metode, $jumlah, $status, $tanggal ?: date('Y-m-d H:i:s'), $bukti, $catatan, $adminId]);
    $id = $pdo->lastInsertId();
    sinkron_pembayaran($pdo, $pesananId, $adminId);
    return $id;
}

// Pengeluaran operasional: Dr Beban | Cr Kas
function catat_pengeluaran($pdo, $kodeAkun, $jumlah, $tanggal, $ket, $adminId) {
    $no = 'JB-' . date('ymdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
    jurnal_tulis($pdo, $no, $tanggal, [[$kodeAkun, $jumlah, 0], [AKUN_KAS, 0, $jumlah]], $ket, 'PENGELUARAN', $adminId);
    return $no;
}
