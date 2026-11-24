<?php
// Menu yang ada di daftar ini disembunyikan dari sidebar. Hapus namanya bila modulnya sudah jadi.
const MODUL_NONAKTIF = ['pembayaran', 'pengembalian', 'pengguna', 'pengaturan',
                        'keuangan', 'crm', 'analitik', 'promo', 'kontak'];
function modul_aktif($menu) { return !in_array($menu, MODUL_NONAKTIF, true); }