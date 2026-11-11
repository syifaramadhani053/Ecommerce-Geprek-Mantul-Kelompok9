-- Jalankan SETELAH database.sql di-import (db_ayam_erp)
-- Login: admin@geprekmantul.com / admin123  (ganti setelah login pertama)
USE db_ayam_erp;

INSERT INTO pengguna (nama_lengkap, email, kata_sandi, peran, status_aktif) VALUES
('Admin', 'admin@geprekmantul.com', '$2y$10$tVPjocWfTCcYu3RnxUwxmuSMeZOQrWK4Grmu5chq/vy8vsxcePx9W', 'superadmin', 1);

-- Opsional: data contoh biar "Pesanan Terbaru" ada isinya
INSERT INTO pengguna (nama_lengkap, email, kata_sandi, peran) VALUES
('Kiki',  'kiki@mail.com',  '$2y$10$tVPjocWfTCcYu3RnxUwxmuSMeZOQrWK4Grmu5chq/vy8vsxcePx9W', 'pelanggan'),
('Tiara', 'tiara@mail.com', '$2y$10$tVPjocWfTCcYu3RnxUwxmuSMeZOQrWK4Grmu5chq/vy8vsxcePx9W', 'pelanggan'),
('Ucup',  'ucup@mail.com',  '$2y$10$tVPjocWfTCcYu3RnxUwxmuSMeZOQrWK4Grmu5chq/vy8vsxcePx9W', 'pelanggan');

INSERT INTO pesanan (no_pesanan, pengguna_id, total_belanja, total_bayar, status_pesanan, nama_penerima, no_telp_penerima, alamat_pengiriman)
SELECT 'P001', id, 30000, 30000, 'diproses', 'Kiki', '0800000001', 'Jakarta' FROM pengguna WHERE email='kiki@mail.com';
INSERT INTO pesanan (no_pesanan, pengguna_id, total_belanja, total_bayar, status_pesanan, nama_penerima, no_telp_penerima, alamat_pengiriman)
SELECT 'P002', id, 20000, 20000, 'selesai', 'Tiara', '0800000002', 'Jakarta' FROM pengguna WHERE email='tiara@mail.com';
INSERT INTO pesanan (no_pesanan, pengguna_id, total_belanja, total_bayar, status_pesanan, nama_penerima, no_telp_penerima, alamat_pengiriman)
SELECT 'P003', id, 15000, 15000, 'menunggu_pembayaran', 'Ucup', '0800000003', 'Jakarta' FROM pengguna WHERE email='ucup@mail.com';
