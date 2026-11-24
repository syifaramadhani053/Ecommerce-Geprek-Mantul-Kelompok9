<?php
/**
 * FILE: admin/produk/index.php - HALAMAN KELOLA PRODUK
 */
require_once __DIR__ . '/../includes/bootstrap.php';
need('produk');

$kondisiPencarian = '1=1';
$parameterPencarian = [];

// Pencarian berdasarkan nama/kode produk dan filter kategori
if (($_GET['q'] ?? '') !== '') {
  $kataKunci = $_GET['q'];
  
  $kondisiPencarian .= " AND (p.nama_produk LIKE ? OR p.kode_produk LIKE ?)";
  $parameterPencarian[] = '%' . $kataKunci . '%';
  $parameterPencarian[] = '%' . $kataKunci . '%';
}

if (($_GET['kategori'] ?? '') !== '') {
  $kondisiPencarian .= " AND p.kategori_id = ?";
  $parameterPencarian[] = $_GET['kategori'];
}

// Mengambil daftar produk dari database
$queryProduk = $pdo->prepare("
  SELECT p.*, k.nama_kategori
  FROM produk p
  JOIN kategori k ON p.kategori_id = k.id
  WHERE $kondisiPencarian
  ORDER BY p.id
");

$queryProduk->execute($parameterPencarian);
$daftarProduk = $queryProduk->fetchAll();

// Mengambil daftar kategori dari database
$queryKategori = $pdo->query("
  SELECT * FROM kategori
  ORDER BY nama_kategori
");

$daftarKategori = $queryKategori->fetchAll();

page_start('Kelola Produk','produk','Kelola menu, harga, dan ketersediaan produk.');
?>

<!-- Pencarian dan Filter Produk -->
<form class="filter-produk" method="GET">
  <div class="pencarian-produk">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"> <circle cx="11" cy="11" r="8"/> <path d="m21 21-4.3-4.3"/>
    </svg>
    
    <input type="text" name="q" placeholder="Cari nama produk atau kode..." value="<?= clean($_GET['q'] ?? '') ?>">
  </div>

  <select name="kategori">
    <option value="">Semua Kategori</option>
      <?php foreach ($daftarKategori as $kategori): ?>
        <option value="<?= $kategori['id'] ?>" <?= ($_GET['kategori'] ?? '') == $kategori['id'] ? 'selected' : '' ?>> <?= clean($kategori['nama_kategori']) ?>
        </option>
      <?php endforeach; ?>
  </select>
  
  <button type="submit" class="btn btn-merah">Cari</button>
  <?php if (
    ($_GET['q'] ?? '') !== '' ||
    ($_GET['kategori'] ?? '') !== ''
    ): ?>
    <a href="index.php" class="reset-pencarian-produk">Reset</a>
  <?php endif; ?>
</form>

<!-- Daftar Produk dan Tabel Produk -->
<div class="panel panel-daftar-produk">
  <div class="panel-head judul-daftar-produk">
    <div>
      <h2>Daftar Produk</h2>
      <span><?= count($daftarProduk) ?> produk ditemukan</span>
    </div>
    <a class="btn btn-merah" href="form.php">+ Tambah Produk</a>
  </div>
  
  <div class="tbl">
    <table class="tabel-produk">
      <thead>
        <tr>
          <th class="c">Foto</th>
          <th>Nama Produk</th>
          <th>Kategori</th>
          <th class="r">Harga Jual</th>
          <th class="c">Stok</th>
          <th class="c">Status</th>
          <th class="c">Aksi</th>
        </tr>
      </thead>
      
      <tbody>
        <?php foreach ($daftarProduk as $produk): ?>
          <tr>
            <!-- Foto Produk -->
            <td class="c">
              <div class="foto-produk">
                <?= img_tag('produk', $produk['gambar']) ?>
              </div>
            </td>
            <!-- Nama dan Kode Produk -->
            <td>
              <div class="identitas-produk">
                <strong><?= clean($produk['nama_produk']) ?></strong>
                <small><?= clean($produk['kode_produk']) ?></small>
              </div>
            </td>
            <!-- Kategori Produk -->
            <td>
              <span class="nama-kategori-produk"><?= clean($produk['nama_kategori']) ?></span>
            </td>
            <!-- Harga Jual -->
            <td class="r">
              <strong class="harga-produk"><?= rupiah($produk['harga_jual']) ?></strong>
            </td>
            <!-- Informasi Stok -->
            <td class="c">
              <?php
              if ($produk['stok'] <= 0) {
                $kelasStatusStok = 'stok-habis';
                $keteranganStok = 'Habis';
                } elseif ($produk['stok'] <= $produk['stok_minimum']) {
                  $kelasStatusStok = 'stok-menipis';
                  $keteranganStok = 'Menipis';
                  } else {
                    $kelasStatusStok = 'stok-aman';
                    $keteranganStok = 'Aman';
                    }
              ?>
              
              <div class="informasi-stok-produk">
                <strong><?= $produk['stok'] ?></strong>
                <span class="<?= $kelasStatusStok ?>"><?= $keteranganStok ?></span>
              </div>
            </td>
            <!-- Status Produk -->
            <td class="c">
              <?= pill_status($produk['status']) ?>
            </td>
            <!-- Tombol Aksi -->
            <td class="c">
              <div class="tombol-aksi-produk">
                <!-- Tombol Edit -->
                <a href="form.php?id=<?= $produk['id'] ?>" class="tombol-ikon-aksi tombol-edit-produk" title="Edit Produk" aria-label="Edit Produk">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>
                  </svg>
                </a>
                <!-- Tombol Hapus -->
                <span class="tombol-hapus-produk">
                  <?= aksi('hapus.php','<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 6h18"/>
                  <path d="M8 6V4h8v2"/>
                  <path d="M19 6l-1 14H6L5 6"/>
                  <path d="M10 11v5M14 11v5"/>
                  </svg>',
                  'tombol-ikon-aksi tombol-hapus-ikon',
                  ['id' => $produk['id']],'Anda yakin ingin hapus produk ini?') ?>
                </span>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        
        <!-- Tampilan Jika Produk Tidak Ditemukan -->
        <?php if (!$daftarProduk): ?>
          <tr>
            <td colspan="7" class="kosong"> Produk tidak ditemukan.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php page_end(); ?>
