# Geprek Mantul — E-commerce + ERP Sederhana (PHP Native)

## Instalasi
1. Import `database.sql` (membuat database `db_ayam_erp`, 12 tabel, 5 produk, FAQ, promo MANTUL10).
2. Edit `config/database.php` (host/user/pass MySQL).
3. Edit `config/config.php` -> `BASE_URL` (mis. `/geprek-mantul`, atau `''` jika di root domain).
4. Buka `http://localhost/geprek-mantul/`. Daftar akun lewat halaman Daftar.
Butuh PHP 8+ dengan ekstensi `pdo_mysql`.

## Peta File: mana yang boleh diedit?
| File | Fungsi | Edit? |
|---|---|---|
| assets/css/style.css | Tampilan & warna (`--merah:#F01414`) | BEBAS diedit |
| assets/js/script.js | Efek FAQ & konfirmasi hapus | BEBAS |
| includes/header.php, footer.php | Navbar & footer | Teks/menu boleh, logika PHP jangan |
| index.php, modules/pages/* | Beranda, Tentang, FAQ, Kontak | Teks boleh, query jangan |
| modules/katalog/* | Katalog, search, detail | Teks boleh, query jangan |
| modules/cart/* | Keranjang | Hati-hati (validasi stok) |
| modules/checkout/proses.php | Buat pesanan, kurangi stok, catat mutasi_stok | JANGAN diubah (inti ERP, transaksi DB) |
| modules/auth/* | Login/daftar | Jangan ubah hash/session |
| includes/functions.php | Fungsi bantu & hitung promo | Hanya rupiah()/status_label() |
| config/* | Koneksi & konfigurasi | Hanya konstanta di atas |

## Alur ERP
Checkout -> `pesanan` + `detail_pesanan` (menyimpan HPP) -> stok `produk` berkurang -> tercatat di `mutasi_stok` -> keranjang dikosongkan (satu transaksi DB).

## Promo
Kode `MANTUL10`: diskon 10%, min. belanja Rp25.000, maks. potongan Rp10.000, S&K ditampilkan di Beranda. Ubah/tambah promo di tabel `promo`.

## Catatan
- `akun_rekening` & `jurnal_keuangan` sudah ada di DB tetapi belum dipakai (untuk modul admin/akuntansi berikutnya).
- Panel admin belum dibuat (di luar permintaan kali ini).
