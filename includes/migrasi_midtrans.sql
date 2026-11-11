-- =====================================================================
-- MIGRASI: INTEGRASI MIDTRANS (SNAP) - db_ayam_erp
-- Jalankan SEKALI di phpMyAdmin (tab SQL) atau MySQL CLI.
-- =====================================================================
-- CATATAN:
-- Tabel `pesanan` Anda SUDAH punya kolom `status_pembayaran`
-- ENUM('belum_lunas','lunas'). Kolom itu sengaja TIDAK diubah agar modul
-- lain (admin/laporan) yang memakainya tidak rusak. Status dari Midtrans
-- disimpan di kolom BARU `status_midtrans` dengan nilai yang Anda minta:
-- pending, settlement, expire, cancel, deny.
-- Saat webhook mengirim "settlement", sistem mengisi KEDUANYA:
--   status_midtrans    = 'settlement'
--   status_pembayaran  = 'lunas'
-- =====================================================================

USE db_ayam_erp;

ALTER TABLE pesanan
    -- 1) Tambah 'online' sebagai metode pembayaran
    MODIFY COLUMN metode_pembayaran ENUM('transfer_bank', 'COD', 'online') NOT NULL DEFAULT 'transfer_bank',

    -- 2) Kolom status transaksi dari Midtrans (NULL untuk pesanan non-online)
    ADD COLUMN status_midtrans ENUM('pending', 'settlement', 'expire', 'cancel', 'deny') DEFAULT NULL
        COMMENT 'Status transaksi Midtrans (khusus metode online)' AFTER status_pembayaran,

    -- 3) Snap token untuk membuka popup pembayaran
    ADD COLUMN snap_token VARCHAR(100) DEFAULT NULL
        COMMENT 'Snap token Midtrans (UUID 36 karakter)' AFTER status_midtrans,

    -- 4) Order ID yang dikirim ke Midtrans (unik, dipakai webhook untuk mencari pesanan)
    ADD COLUMN order_id_midtrans VARCHAR(50) DEFAULT NULL
        COMMENT 'order_id yang dikirim ke Midtrans' AFTER snap_token,

    -- 5) Channel pembayaran yang dipilih pelanggan (gopay, qris, bank_transfer, echannel, dll)
    ADD COLUMN tipe_pembayaran_midtrans VARCHAR(50) DEFAULT NULL
        COMMENT 'payment_type dari Midtrans' AFTER order_id_midtrans,

    -- 6) Waktu pembayaran diterima
    ADD COLUMN waktu_bayar DATETIME DEFAULT NULL
        COMMENT 'Waktu settlement diterima' AFTER tipe_pembayaran_midtrans,

    -- 7) Index unik agar webhook cepat dan tidak ada order_id ganda
    ADD UNIQUE KEY uk_pesanan_order_id_midtrans (order_id_midtrans);

-- Cek hasilnya:
-- DESCRIBE pesanan;

-- =====================================================================
-- (OPSIONAL) ROLLBACK, jika ingin membatalkan migrasi ini:
-- =====================================================================
-- ALTER TABLE pesanan
--     DROP INDEX uk_pesanan_order_id_midtrans,
--     DROP COLUMN waktu_bayar,
--     DROP COLUMN tipe_pembayaran_midtrans,
--     DROP COLUMN order_id_midtrans,
--     DROP COLUMN snap_token,
--     DROP COLUMN status_midtrans,
--     MODIFY COLUMN metode_pembayaran ENUM('transfer_bank', 'COD') NOT NULL DEFAULT 'transfer_bank';
