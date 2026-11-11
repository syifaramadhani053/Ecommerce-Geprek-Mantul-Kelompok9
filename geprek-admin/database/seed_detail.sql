-- Opsional: isi rincian produk untuk 3 pesanan contoh (P001-P003) dari seed_admin.sql
-- supaya kolom Produk di halaman Pesanan dan angka Laporan ada isinya.
USE db_ayam_erp;

INSERT INTO detail_pesanan (pesanan_id, produk_id, kuantitas, harga_satuan, hpp_satuan, subtotal)
SELECT p.id, 1, 1, 15000, 8000, 15000 FROM pesanan p WHERE p.no_pesanan = 'P001';
INSERT INTO detail_pesanan (pesanan_id, produk_id, kuantitas, harga_satuan, hpp_satuan, subtotal)
SELECT p.id, 3, 1, 5000, 2500, 5000 FROM pesanan p WHERE p.no_pesanan = 'P001';
INSERT INTO detail_pesanan (pesanan_id, produk_id, kuantitas, harga_satuan, hpp_satuan, subtotal)
SELECT p.id, 2, 1, 20000, 10000, 20000 FROM pesanan p WHERE p.no_pesanan = 'P002';
INSERT INTO detail_pesanan (pesanan_id, produk_id, kuantitas, harga_satuan, hpp_satuan, subtotal)
SELECT p.id, 1, 1, 15000, 8000, 15000 FROM pesanan p WHERE p.no_pesanan = 'P003';

UPDATE pesanan SET total_belanja = 20000, total_bayar = 20000 WHERE no_pesanan = 'P001';
UPDATE pesanan SET metode_pembayaran = 'COD', status_pembayaran = 'lunas' WHERE no_pesanan = 'P002';
UPDATE pesanan SET status_pesanan = 'diproses' WHERE no_pesanan = 'P003';
