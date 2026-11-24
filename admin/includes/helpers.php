<?php
/**
 * FILE: admin/includes/helpers.php -- FUNGSI BANTU (format, ikon, upload, tombol aksi)
 * BOLEH DIEDIT: teks label di status_info(), daftar ikon, batas ukuran upload.
 * JANGAN DIUBAH: fungsi clean(), upload_gambar() (validasi keamanan file), csrf().
 * Fungsi umum diberi pengaman function_exists agar tidak bentrok dengan functions.php situs user.
 */
if (!function_exists('rupiah'))    { function rupiah($a){ return "Rp ".number_format((float)$a,0,',','.'); } }
if (!function_exists('clean'))     { function clean($s){ return htmlspecialchars(trim((string)$s), ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('redirect'))  { function redirect($u){ header("Location: ".$u); exit; } }
if (!function_exists('set_flash')) { function set_flash($t,$m){ $_SESSION['flash']=['type'=>$t,'msg'=>$m]; } }
if (!function_exists('show_flash')){ function show_flash(){ if(isset($_SESSION['flash'])){ $f=$_SESSION['flash']; echo '<div class="alert '.($f['type']==='success'?'alert-success':'alert-danger').'">'.clean($f['msg']).'</div>'; unset($_SESSION['flash']); } } }

function tgl($d, $jam = false) { return $d ? date($jam ? 'd M Y H:i' : 'd M Y', strtotime($d)) : '-'; }
function angka($n) { return number_format((float)$n, 0, ',', '.'); }

function csrf() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return '<input type="hidden" name="_csrf" value="' . $_SESSION['csrf'] . '">';
}
function csrf_token() { csrf(); return $_SESSION['csrf']; }

// Tombol aksi berbentuk form POST (aman dari CSRF), dipakai untuk hapus/ubah cepat
function aksi($url, $label, $kelas = 'btn-abu', $hidden = [], $konfirmasi = '') {
    $h = csrf();
    foreach ($hidden as $k => $v) $h .= '<input type="hidden" name="' . clean($k) . '" value="' . clean($v) . '">';
    $c = $konfirmasi ? ' onsubmit="return confirm(\'' . clean($konfirmasi) . '\')"' : '';
    return '<form method="POST" action="' . $url . '" style="display:inline"' . $c . '>' . $h . '<button class="btn btn-sm ' . $kelas . '">' . $label . '</button></form>';
}

function pill($teks, $kelas = 'abu') { return '<span class="pill pill-' . $kelas . '">' . clean($teks) . '</span>'; }
function status_info($s) {
    static $m = [
        'menunggu_pembayaran' => ['Menunggu', 'pink'], 'diproses' => ['Diproses', 'kuning'], 'dikirim' => ['Dikirim', 'biru'],
        'selesai' => ['Selesai', 'hijau'], 'dibatalkan' => ['Batal', 'abu'],
        'lunas' => ['Lunas', 'hijau'], 'belum_lunas' => ['Belum Lunas', 'pink'],
        'diterima' => ['Diterima', 'hijau'], 'menunggu' => ['Menunggu', 'pink'], 'ditolak' => ['Ditolak', 'abu'],
        'disiapkan' => ['Disiapkan', 'kuning'], 'terkirim' => ['Terkirim', 'hijau'], 'gagal' => ['Gagal', 'merah'],
        'diajukan' => ['Diajukan', 'pink'], 'disetujui' => ['Disetujui', 'biru'],
        'aktif' => ['Aktif', 'hijau'], 'nonaktif' => ['Nonaktif', 'abu'],
    ];
    return $m[$s] ?? [ucfirst(str_replace('_', ' ', $s)), 'abu'];
}
function pill_status($s) { [$l, $c] = status_info($s); return pill($l, $c); }

function icon($n) {
    static $i = [
     'home'=>'<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
     'file'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
     'box'=>'<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
     'users'=>'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
     'clip'=>'<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>',
     'truck'=>'<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
     'card'=>'<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
     'layers'=>'<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
     'archive'=>'<polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/>',
     'undo'=>'<polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>',
     'gear'=>'<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
     'shield'=>'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
     'chart'=>'<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
     'dollar'=>'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
     'chat'=>'<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
     'tag'=>'<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
     'help'=>'<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
     'mail'=>'<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
     'out'=>'<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
     'search'=>'<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
     'pulse'=>'<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
     'user'=>'<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
     'menu'=>'<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
     'alert'=>'<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    ];
    return '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . ($i[$n] ?? '') . '</svg>';
}

// Upload gambar aman: hanya jpg/png/webp/gif, maks 2 MB, wajib benar-benar gambar.
// Mengembalikan nama file baru, null jika tidak ada upload, atau melempar Exception jika tidak valid.
function upload_gambar($field, $sub) {
    if (empty($_FILES[$field]['name'])) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload gambar gagal (kode ' . $f['error'] . ').');
    if ($f['size'] > 2 * 1024 * 1024) throw new Exception('Ukuran gambar maksimal 2 MB.');
    $info = @getimagesize($f['tmp_name']);
    $ok = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($ok[$info[2]])) throw new Exception('File harus berupa gambar JPG, PNG, GIF, atau WEBP.');
    $dir = UPLOAD_FS . '/' . $sub;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) throw new Exception('Folder upload tidak bisa dibuat: ' . $dir);
    $nama = $sub . '_' . date('ymdHis') . '_' . bin2hex(random_bytes(3)) . '.' . $ok[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $nama)) throw new Exception('Gagal menyimpan gambar ke folder upload.');
    return $nama;
}
function hapus_gambar($sub, $nama) {
    if ($nama && $nama !== 'default_produk.jpg' && preg_match('/^[\w.\-]+$/', $nama)) @unlink(UPLOAD_FS . '/' . $sub . '/' . $nama);
}
function gambar_url($sub, $nama) {
    if (!$nama || $nama === 'default_produk.jpg' || !is_file(UPLOAD_FS . '/' . $sub . '/' . $nama)) return null;
    return UPLOAD_URL . '/' . $sub . '/' . rawurlencode($nama);
}
function img_tag($sub, $nama, $kelas = 'thumb') {
    $u = gambar_url($sub, $nama);
    return $u ? '<img class="' . $kelas . '" src="' . $u . '" alt="">' : '<div class="' . $kelas . ' thumb-kosong">' . icon('box') . '</div>';
}

// Pengaturan situs (tabel `pengaturan`), di-cache per request
function setting($kunci, $default = '') {
    global $pdo; static $c = null;
    if ($c === null) { try { $c = $pdo->query("SELECT kunci, nilai FROM pengaturan")->fetchAll(PDO::FETCH_KEY_PAIR); } catch (Exception $e) { $c = []; } }
    return ($c[$kunci] ?? '') !== '' ? $c[$kunci] : $default;
}

// Paginasi sederhana. Pakai: [$hal,$off,$nav] = paginate($total, 15);
function paginate($total, $per = 15) {
    $hal = max(1, (int)($_GET['hal'] ?? 1)); $max = max(1, (int)ceil($total / $per)); $hal = min($hal, $max);
    $q = $_GET; $nav = '';
    if ($max > 1) {
        $nav = '<div class="paging">';
        for ($i = 1; $i <= $max; $i++) { $q['hal'] = $i; $nav .= '<a class="' . ($i == $hal ? 'on' : '') . '" href="?' . clean(http_build_query($q)) . '">' . $i . '</a>'; }
        $nav .= '</div>';
    }
    return [$hal, ($hal - 1) * $per, $nav];
}

// Memotong teks panjang. Aman dipakai walau ekstensi mbstring belum aktif.
function potong($s, $n) {
    $s = (string)$s;
    if (function_exists('mb_strimwidth')) return mb_strimwidth($s, 0, $n, '…');
    return strlen($s) > $n ? substr($s, 0, $n - 1) . '…' : $s;
}
