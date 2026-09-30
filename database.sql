-- =====================================================================
-- DATABASE: db_ayam_erp  (Geprek Mantul - E-commerce + ERP)
-- =====================================================================
-- CATATAN PENTING UNTUK YANG MENGEDIT FILE INI:
-- File SQL asli yang diberikan (struktur_database.docx) memiliki beberapa
-- kesalahan sintaks MySQL, sudah diperbaiki di file ini, yaitu:
--   1. Nama kolom/tabel sebelumnya memakai tanda kutip satu ('kolom') -> 
--      diganti ke backtick (`kolom`), karena tanda kutip satu di MySQL
--      berarti teks/string, bukan nama kolom. Kalau dibiarkan, semua
--      perintah CREATE TABLE akan ERROR saat dijalankan.
--   2. ENUM('superadmin','gudang','kasir' 'pelanggan') -> kurang koma,
--      sudah ditambahkan jadi ENUM('superadmin','gudang','kasir','pelanggan').
--   3. `hpp_satuan` DECIMAL(12.,) -> tipe data tidak valid, diperbaiki
--      menjadi DECIMAL(12,2).
--   4. Beberapa baris CREATE TABLE kurang tanda koma di antar kolom/
--      constraint (produk, mutasi_stok, jurnal_keuangan, akun_rekening) ->
--      sudah dirapikan.
-- Struktur TABEL dan NAMA KOLOM semuanya TETAP SAMA seperti file asli
-- Anda (pengguna, kategori, produk, pesanan, detail_pesanan, mutasi_stok,
-- akun_rekening, jurnal_keuangan) — jadi tetap kompatibel dengan rencana
-- ERP Anda sebelumnya.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS db_ayam_erp DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
USE db_ayam_erp;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS jurnal_keuangan;
DROP TABLE IF EXISTS akun_rekening;
DROP TABLE IF EXISTS mutasi_stok;
DROP TABLE IF EXISTS detail_pesanan;
DROP TABLE IF EXISTS pesanan;
DROP TABLE IF EXISTS keranjang;   -- tabel tambahan, lihat catatan di bawah
DROP TABLE IF EXISTS produk;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS pengguna;
DROP TABLE IF EXISTS faq;         -- tabel tambahan
DROP TABLE IF EXISTS pesan_kontak; -- tabel tambahan
DROP TABLE IF EXISTS promo;        -- tabel tambahan

-- =====================================================================
-- ============ BAGIAN A: TABEL DARI FILE ASLI ANDA (SUDAH DIPERBAIKI) =
-- =====================================================================

-- TABEL 1: PENGGUNA (Tabel Master Akun & Hak Akses)
CREATE TABLE pengguna (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL COMMENT 'Nama lengkap pemilik akun',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT 'Email aktif untuk login',
    kata_sandi VARCHAR(225) NOT NULL COMMENT 'Password yang dienkripsi dengan password_hash()',
    no_telp VARCHAR(20) DEFAULT NULL COMMENT 'Nomor kontak WhatsApp / HP',
    alamat TEXT DEFAULT NULL COMMENT 'Alamat lengkap pengiriman atau tempat tinggal',
    peran ENUM('superadmin', 'gudang', 'kasir', 'pelanggan') NOT NULL DEFAULT 'pelanggan'
        COMMENT 'Hak akses dalam sistem',
    status_aktif TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Aktif, 0 = Diblokir/Nonaktif',
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pendaftaran akun',
    diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tabel data pengguna dan hak akses ERP';

-- TABEL 2: KATEGORI (Tabel Master Kategori Menu)
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL COMMENT 'Nama kategori makanan/minuman/paket',
    slug VARCHAR(120) NOT NULL UNIQUE COMMENT 'Slug ramah URL',
    deskripsi TEXT DEFAULT NULL COMMENT 'Penjelasan singkat kategori',
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Master Kategori Menu Geprek Mantul';

-- TABEL 3: PRODUK (Master Data Menu & Persediaan)
CREATE TABLE produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT NOT NULL COMMENT 'Relasi ke tabel kategori',
    kode_produk VARCHAR(30) NOT NULL UNIQUE COMMENT 'Kode menu',
    nama_produk VARCHAR(150) NOT NULL COMMENT 'Nama makanan atau minuman',
    slug VARCHAR(180) NOT NULL UNIQUE COMMENT 'Slug untuk URL menu',
    deskripsi TEXT DEFAULT NULL COMMENT 'Deskripsi menu',
    harga_beli DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'HPP (Harga Pokok Pembelian)',
    harga_jual DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Harga jual ke konsumen',
    stok INT NOT NULL DEFAULT 0 COMMENT 'Jumlah stok menu saat ini',
    stok_minimum INT NOT NULL DEFAULT 5 COMMENT 'Batas peringatan stok menipis',
    berat_gram INT NOT NULL DEFAULT 500 COMMENT 'Perkiraan berat untuk pengiriman',
    gambar VARCHAR(255) DEFAULT 'default_produk.jpg' COMMENT 'Nama file foto makanan',
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif' COMMENT 'Status menu di katalog',
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_produk_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Master Menu dan Persediaan Geprek Mantul';

-- TABEL 4: PESANAN (Header Transaksi Penjualan E-Commerce)
-- Ditambah 2 kolom opsional (kode_promo, potongan_promo) untuk fitur PROMO
-- yang diminta -> lihat catatan "TAMBAHAN" di kolomnya.
CREATE TABLE pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_pesanan VARCHAR(40) NOT NULL UNIQUE COMMENT 'Nomor unik transaksi (contoh: ORD-20260912-001)',
    pengguna_id INT NOT NULL COMMENT 'ID pelanggan yang berbelanja',
    tanggal_pesanan DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu checkout dilakukan',
    total_belanja DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Subtotal harga produk',
    kode_promo VARCHAR(30) DEFAULT NULL COMMENT '[TAMBAHAN] Kode promo yang dipakai, jika ada',
    potongan_promo DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '[TAMBAHAN] Nilai potongan dari promo',
    ongkir DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Biaya pengiriman',
    total_bayar DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'total_belanja - potongan_promo + ongkir',
    status_pesanan ENUM('menunggu_pembayaran', 'diproses', 'dikirim', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'menunggu_pembayaran',
    status_pembayaran ENUM('belum_lunas', 'lunas') NOT NULL DEFAULT 'belum_lunas',
    metode_pembayaran ENUM('transfer_bank', 'COD') NOT NULL DEFAULT 'transfer_bank',
    kurir VARCHAR(50) NOT NULL DEFAULT 'Kurir Internal',
    nomor_resi VARCHAR(50) DEFAULT NULL,
    nama_penerima VARCHAR(100) NOT NULL,
    no_telp_penerima VARCHAR(20) NOT NULL,
    alamat_pengiriman TEXT NOT NULL,
    catatan TEXT DEFAULT NULL,
    bukti_transfer VARCHAR(255) DEFAULT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pesanan_pengguna FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Header Transaksi Pesanan Geprek Mantul';

-- TABEL 5: DETAIL_PESANAN (Rincian Item yang Dipesan)
CREATE TABLE detail_pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pesanan_id INT NOT NULL COMMENT 'Relasi ke tabel pesanan',
    produk_id INT NOT NULL COMMENT 'Relasi ke tabel produk',
    kuantitas INT NOT NULL DEFAULT 1 COMMENT 'Jumlah item yang dibeli',
    harga_satuan DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Harga jual satuan saat transaksi',
    hpp_satuan DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'HPP satuan saat transaksi (untuk laba/rugi)',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'kuantitas * harga_satuan',
    CONSTRAINT fk_detail_pesanan FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detail_produk FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Rincian menu dalam setiap pesanan';

-- TABEL 6: MUTASI_STOK (Kartu Stok & Audit Trail Inventaris)
CREATE TABLE mutasi_stok (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produk_id INT NOT NULL COMMENT 'ID menu yang mengalami perubahan stok',
    jenis_mutasi ENUM('masuk_restock', 'keluar_penjualan', 'penyesuaian_opname', 'retur_masuk') NOT NULL,
    jumlah INT NOT NULL COMMENT 'Jumlah stok yang bertambah (+) atau berkurang (-)',
    stok_sebelum INT NOT NULL,
    stok_sesudah INT NOT NULL,
    nomor_referensi VARCHAR(50) DEFAULT NULL COMMENT 'Nomor Pesanan atau No Faktur Restock',
    keterangan TEXT DEFAULT NULL,
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
    pengguna_id INT DEFAULT NULL COMMENT 'Staf/pelanggan pemicu perubahan stok',
    CONSTRAINT fk_mutasi_produk FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_mutasi_pengguna FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Riwayat perubahan stok menu';

-- TABEL 7: AKUN_REKENING (Chart of Accounts) — disiapkan untuk modul
-- akuntansi/admin di masa depan, BELUM dipakai oleh sisi user pada paket
-- kode ini.
CREATE TABLE akun_rekening (
    kode_akun VARCHAR(20) PRIMARY KEY,
    nama_akun VARCHAR(100) NOT NULL,
    kelompok_akun ENUM('Aset', 'Kewajiban', 'Ekuitas', 'Pendapatan', 'Beban_Pokok', 'Beban_Operasional') NOT NULL,
    posisi_normal ENUM('Debit', 'Kredit') NOT NULL,
    saldo_awal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bagan Akun Keuangan (Chart of Accounts)';

-- TABEL 8: JURNAL_KEUANGAN (Buku Jurnal Umum Double-Entry) — sama seperti
-- akun_rekening, disiapkan untuk modul admin/akuntansi berikutnya.
CREATE TABLE jurnal_keuangan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_jurnal VARCHAR(40) NOT NULL,
    tanggal DATE NOT NULL,
    kode_akun VARCHAR(20) NOT NULL,
    debit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    kredit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    keterangan VARCHAR(255) NOT NULL,
    referensi_transaksi VARCHAR(50) DEFAULT NULL,
    pengguna_id INT DEFAULT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_jurnal_akun FOREIGN KEY (kode_akun) REFERENCES akun_rekening(kode_akun) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_jurnal_pengguna FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tabel Jurnal Umum Keuangan Double-Entry';

-- =====================================================================
-- ============ BAGIAN B: TABEL TAMBAHAN (TIDAK ADA DI FILE ASLI) =======
-- Empat tabel di bawah ini WAJIB ditambahkan karena fitur yang diminta
-- (keranjang belanja, FAQ, form kontak, promo) tidak punya tempat
-- penyimpanan pada struktur database asli Anda.
-- =====================================================================

-- [TAMBAHAN] Keranjang belanja tiap pengguna sebelum checkout
CREATE TABLE keranjang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengguna_id INT NOT NULL,
    produk_id INT NOT NULL,
    kuantitas INT NOT NULL DEFAULT 1,
    catatan VARCHAR(255) DEFAULT NULL COMMENT 'Contoh: level pedas, tanpa nasi, dll',
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_keranjang_pengguna FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE CASCADE,
    CONSTRAINT fk_keranjang_produk FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='[TAMBAHAN] Keranjang belanja sementara sebelum checkout';

-- [TAMBAHAN] FAQ untuk halaman "Pertanyaan Umum"
CREATE TABLE faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pertanyaan VARCHAR(255) NOT NULL,
    jawaban TEXT NOT NULL,
    urutan INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='[TAMBAHAN] Data FAQ';

-- [TAMBAHAN] Pesan masuk dari form Kontak
CREATE TABLE pesan_kontak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subjek VARCHAR(150) DEFAULT NULL,
    pesan TEXT NOT NULL,
    status ENUM('baru','dibaca','dibalas') NOT NULL DEFAULT 'baru',
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='[TAMBAHAN] Pesan masuk dari form kontak';

-- [TAMBAHAN] Data promo / voucher diskon dengan syarat & ketentuan
CREATE TABLE promo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_promo VARCHAR(30) NOT NULL UNIQUE COMMENT 'Kode yang dimasukkan pelanggan saat checkout',
    judul VARCHAR(150) NOT NULL,
    jenis_diskon ENUM('persen','nominal') NOT NULL DEFAULT 'persen',
    nilai_diskon DECIMAL(12,2) NOT NULL COMMENT 'Contoh: 10 (untuk 10%) atau 5000 (untuk Rp5.000)',
    maksimal_potongan DECIMAL(12,2) DEFAULT NULL COMMENT 'Batas atas potongan jika jenis persen (boleh NULL = tak terbatas)',
    minimal_belanja DECIMAL(12,2) NOT NULL DEFAULT 0,
    tanggal_mulai DATE NOT NULL,
    tanggal_berakhir DATE NOT NULL,
    syarat_ketentuan TEXT NOT NULL COMMENT 'Ditampilkan ke pelanggan sebagai S&K',
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='[TAMBAHAN] Data promo/voucher diskon';

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- ============ DATA AWAL (SEED) ========================================
-- =====================================================================

-- Kategori menu
INSERT INTO kategori (nama_kategori, slug, deskripsi) VALUES
('Menu Utama', 'menu-utama', 'Ayam geprek dan menu makanan utama'),
('Paket Hemat', 'paket-hemat', 'Paket kombinasi makanan hemat'),
('Minuman', 'minuman', 'Minuman pendamping'),
('Tambahan', 'tambahan', 'Menu tambahan / extra');

-- 5 Produk sesuai permintaan
INSERT INTO produk (kategori_id, kode_produk, nama_produk, slug, deskripsi, harga_beli, harga_jual, stok, stok_minimum, berat_gram, status) VALUES
(1, 'GM-001', 'Ayam Geprek', 'ayam-geprek', 'Ayam crispy digeprek dengan sambal bawang pedas khas Geprek Mantul, disajikan hangat.', 8000, 15000, 50, 10, 350, 'aktif'),
(2, 'GM-002', 'Paket Hemat Ayam + Nasi', 'paket-hemat-ayam-nasi', 'Satu porsi Ayam Geprek lengkap dengan nasi putih hangat, paket paling laris!', 10000, 20000, 40, 10, 550, 'aktif'),
(3, 'GM-003', 'Air Mineral', 'air-mineral', 'Air mineral kemasan botol 600ml, segar dan menyehatkan.', 2500, 5000, 100, 20, 600, 'aktif'),
(3, 'GM-004', 'Teh Pucuk', 'teh-pucuk', 'Teh pucuk harum dalam kemasan botol, cocok menemani makan pedas.', 3500, 6000, 80, 20, 500, 'aktif'),
(4, 'GM-005', 'Extra Sambal', 'extra-sambal', 'Tambahan sambal bawang khas Geprek Mantul untuk pecinta pedas maksimal.', 1000, 3000, 100, 20, 100, 'aktif');

-- FAQ
INSERT INTO faq (pertanyaan, jawaban, urutan) VALUES
('Bagaimana cara memesan makanan di Ayam Geprek Mantul?', 'Pilih menu yang diinginkan, masukkan ke keranjang, isi data pemesanan dan alamat, kemudian pilih metode pembayaran yang tersedia.', 1),
('Berapa lama waktu pengiriman?', 'Estimasi pengiriman 30-60 menit tergantung jarak lokasi Anda dan jam ramai.', 2),
('Bagaimana jika pesanan yang diterima tidak sesuai?', 'Pelanggan dapat menghubungi Ayam Geprek Mantul melalui kontak yang tersedia di website dengan menyertakan informasi pesanan agar dapat kami bantu.', 3),
('Bagaimana cara menggunakan kode promo?', 'Masukkan kode promo pada halaman checkout sebelum menekan tombol "Buat Pesanan", potongan akan otomatis terhitung jika syarat terpenuhi.', 4),
('Apakah bisa membatalkan pesanan?', 'Pembatalan hanya bisa dilakukan sebelum status pesanan menjadi "Diproses". Silakan hubungi kami lewat halaman Kontak.', 5);

-- Promo dengan syarat & ketentuan
INSERT INTO promo (kode_promo, judul, jenis_diskon, nilai_diskon, maksimal_potongan, minimal_belanja, tanggal_mulai, tanggal_berakhir, syarat_ketentuan, status) VALUES
('MANTUL10', 'Diskon 10% Geprek Mantul', 'persen', 10, 10000, 25000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY),
'1. Minimal pembelanjaan Rp25.000 sebelum ongkir.
2. Maksimal potongan harga Rp10.000 per transaksi.
3. Berlaku untuk semua menu, tidak dapat digabung dengan promo lain.
4. Satu kode promo hanya dapat digunakan 1 kali per transaksi.
5. Promo berlaku selama periode yang ditentukan atau selama kuota masih tersedia.
6. Geprek Mantul berhak membatalkan promo apabila ditemukan indikasi kecurangan.', 'aktif');
