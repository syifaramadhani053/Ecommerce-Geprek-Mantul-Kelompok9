<?php
/**
 * =====================================================================
 * FILE: admin/includes/logika.php -- ATURAN BISNIS ERP (STOK, PESANAN, PENGIRIMAN)
 * =====================================================================
 * Alur pesanan:  Menunggu -> Diproses -> Dikirim -> Selesai   (atau Batal)
 *  - Batal   : stok dikembalikan otomatis + jurnal penjualan dibalik (bila sudah lunas)
 *  - Dikirim : data pengiriman dibuat/diperbarui otomatis (Logistik)
 *  - Selesai : COD otomatis dianggap lunas + jurnal keuangan tercatat; pengiriman -> Terkirim
 * BOLEH DIEDIT : teks pesan.
 * JANGAN DIUBAH: seluruh logika transaksi. Semua halaman admin memanggil fungsi ini,
 *                sehingga stok, pengiriman, dan keuangan selalu sinkron.
 * =====================================================================
 */
function mutasi_stok($pdo, $produkId, $jenis, $nilai, $ref, $ket, $adminId, $mode = 'delta') {
    $own = !$pdo->inTransaction(); if ($own) $pdo->beginTransaction();
    try {
        $s = $pdo->prepare("SELECT nama_produk, stok FROM produk WHERE id = ? FOR UPDATE"); $s->execute([$produkId]); $p = $s->fetch();
        if (!$p) throw new Exception('Produk tidak ditemukan.');
        $sebelum = (int)$p['stok'];
        $sesudah = $mode === 'set' ? (int)$nilai : $sebelum + (int)$nilai;
        if ($sesudah < 0) throw new Exception('Stok ' . $p['nama_produk'] . ' tidak mencukupi (tersisa ' . $sebelum . ').');
        $delta = $sesudah - $sebelum;
        $pdo->prepare("UPDATE produk SET stok = ? WHERE id = ?")->execute([$sesudah, $produkId]);
        $catat = $jenis === 'keluar_penjualan' ? abs($delta) : $delta;   // sama dengan format checkout situs user
        $pdo->prepare("INSERT INTO mutasi_stok (produk_id,jenis_mutasi,jumlah,stok_sebelum,stok_sesudah,nomor_referensi,keterangan,pengguna_id) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$produkId, $jenis, $catat, $sebelum, $sesudah, $ref ?: null, $ket ?: null, $adminId]);
        if ($own) $pdo->commit();
        return [$sebelum, $sesudah];
    } catch (Exception $e) { if ($own && $pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}

function pastikan_pengiriman($pdo, $p, $status) {
    $s = $pdo->prepare("SELECT id FROM pengiriman WHERE pesanan_id = ?"); $s->execute([$p['id']]); $id = $s->fetchColumn();
    $kirim = in_array($status, ['dikirim', 'terkirim']) ? date('Y-m-d H:i:s') : null;
    $terima = $status === 'terkirim' ? date('Y-m-d H:i:s') : null;
    if ($id) {
        $pdo->prepare("UPDATE pengiriman SET status=?, tanggal_kirim=COALESCE(tanggal_kirim,?), tanggal_terima=COALESCE(?,tanggal_terima), kurir=?, nomor_resi=COALESCE(nomor_resi,?) WHERE id=?")
            ->execute([$status, $kirim, $terima, $p['kurir'], $p['nomor_resi'], $id]);
    } else {
        $pdo->prepare("INSERT INTO pengiriman (pesanan_id,kurir,nomor_resi,biaya,status,tanggal_kirim,tanggal_terima) VALUES (?,?,?,?,?,?,?)")
            ->execute([$p['id'], $p['kurir'], $p['nomor_resi'], $p['ongkir'], $status, $kirim, $terima]);
    }
}

// Mengembalikan [berhasil(bool), pesan]
function ubah_status_pesanan($pdo, $id, $baru, $adminId, $dariPengiriman = false) {
    $valid = ['menunggu_pembayaran', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];
    if (!in_array($baru, $valid, true)) return [false, 'Status tidak valid.'];
    $own = !$pdo->inTransaction(); if ($own) $pdo->beginTransaction();
    try {
        $s = $pdo->prepare("SELECT * FROM pesanan WHERE id = ? FOR UPDATE"); $s->execute([$id]); $p = $s->fetch();
        if (!$p) throw new Exception('Pesanan tidak ditemukan.');
        if ($p['status_pesanan'] === $baru) { if ($own) $pdo->commit(); return [true, 'Status tidak berubah.']; }
        if (in_array($p['status_pesanan'], ['dibatalkan', 'selesai'], true)) throw new Exception('Pesanan yang sudah ' . status_info($p['status_pesanan'])[0] . ' tidak dapat diubah lagi.');
        if (in_array($baru, ['diproses', 'dikirim', 'selesai'], true) && WAJIB_LUNAS_TRANSFER
            && $p['metode_pembayaran'] === 'transfer_bank' && $p['status_pembayaran'] !== 'lunas')
            throw new Exception('Pembayaran transfer belum dikonfirmasi. Konfirmasi dulu di menu Pembayaran.');

        if ($baru === 'dibatalkan') {
            $it = $pdo->prepare("SELECT produk_id, kuantitas FROM detail_pesanan WHERE pesanan_id = ?"); $it->execute([$id]);
            foreach ($it->fetchAll() as $i) mutasi_stok($pdo, $i['produk_id'], 'retur_masuk', $i['kuantitas'], $p['no_pesanan'], 'Pesanan dibatalkan, stok dikembalikan', $adminId);
            jurnal_balik_penjualan($pdo, $p, $adminId);
        }
        if ($baru === 'selesai' && $p['status_pembayaran'] !== 'lunas') {            // COD dibayar saat barang diterima
            $pdo->prepare("UPDATE pesanan SET status_pesanan='diproses' WHERE id=?")->execute([$id]);   // agar sinkron_pembayaran tidak menimpa
            catat_pembayaran($pdo, $id, $p['metode_pembayaran'], $p['total_bayar'], $adminId, 'diterima', null, 'Otomatis: pesanan selesai (COD)');
        }
        $pdo->prepare("UPDATE pesanan SET status_pesanan = ? WHERE id = ?")->execute([$baru, $id]);
        if (!$dariPengiriman) {
            if ($baru === 'dikirim') pastikan_pengiriman($pdo, $p, 'dikirim');
            if ($baru === 'selesai') pastikan_pengiriman($pdo, $p, 'terkirim');
        }
        if ($own) $pdo->commit();
        return [true, 'Status pesanan diubah menjadi ' . status_info($baru)[0] . ($baru === 'dibatalkan' ? ' dan stok dikembalikan.' : '.')];
    } catch (Exception $e) { if ($own && $pdo->inTransaction()) $pdo->rollBack(); return [false, $e->getMessage()]; }
}

// Mengubah status pengiriman dan menyelaraskan status pesanan (Logistik). Mengembalikan [ok, pesan]
function set_status_pengiriman($pdo, $pengirimanId, $status, $adminId) {
    if (!in_array($status, ['disiapkan', 'dikirim', 'terkirim', 'gagal'], true)) return [false, 'Status pengiriman tidak valid.'];
    $own = !$pdo->inTransaction(); if ($own) $pdo->beginTransaction();
    try {
        $s = $pdo->prepare("SELECT * FROM pengiriman WHERE id = ? FOR UPDATE"); $s->execute([$pengirimanId]); $k = $s->fetch();
        if (!$k) throw new Exception('Data pengiriman tidak ditemukan.');
        $pdo->prepare("UPDATE pengiriman SET status=?, tanggal_kirim=IF(? IN ('dikirim','terkirim'), COALESCE(tanggal_kirim, NOW()), tanggal_kirim), tanggal_terima=IF(?='terkirim', COALESCE(tanggal_terima, NOW()), tanggal_terima) WHERE id=?")
            ->execute([$status, $status, $status, $pengirimanId]);
        $map = ['dikirim' => 'dikirim', 'terkirim' => 'selesai'];
        if (isset($map[$status])) {
            [$ok, $m] = ubah_status_pesanan($pdo, $k['pesanan_id'], $map[$status], $adminId, true);
            if (!$ok && strpos($m, 'tidak dapat diubah lagi') === false) throw new Exception($m);
        }
        if ($own) $pdo->commit();
        return [true, 'Status pengiriman diperbarui.'];
    } catch (Exception $e) { if ($pdo->inTransaction()) $pdo->rollBack(); return [false, $e->getMessage()]; }
}
