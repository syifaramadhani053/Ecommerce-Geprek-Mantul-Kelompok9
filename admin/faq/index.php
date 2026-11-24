<?php
/**
 * FILE: admin/faq/index.php  -- Kelola FAQ
 */

require_once __DIR__ . '/../includes/bootstrap.php';
need('faq');
// PROSES FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // HAPUS FAQ
    if (isset($_POST['hapus'])) {
        $id = (int) $_POST['hapus'];

        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM faq WHERE id = ?");
            $stmt->execute([$id]);

            set_flash('success', 'FAQ berhasil dihapus.');
        }
        redirect(BASE_URL . '/admin/faq/index.php');
    }
    // TAMBAH / EDIT FAQ
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $jawaban = trim($_POST['jawaban'] ?? '');
    $urutan = (int) ($_POST['urutan'] ?? 0);
    $id = (int) ($_POST['id'] ?? 0);

    if ($pertanyaan === '' || $jawaban === '') {
        set_flash('error', 'Pertanyaan dan jawaban wajib diisi.');
        redirect(BASE_URL . '/admin/faq/index.php');
    }

    if ($id > 0) {
        $stmt = $pdo->prepare(
            "UPDATE faq SET pertanyaan=?, jawaban=?, urutan=? WHERE id=?"
        );
        $stmt->execute([$pertanyaan, $jawaban, $urutan, $id]);

        set_flash('success', 'FAQ berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO faq (pertanyaan, jawaban, urutan) VALUES (?, ?, ?)"
        );
        $stmt->execute([$pertanyaan, $jawaban, $urutan]);

        set_flash('success', 'FAQ berhasil ditambahkan.');
    }
    redirect(BASE_URL . '/admin/faq/index.php');
}

// DATA EDIT
$e = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM faq WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $e = $stmt->fetch();

    if (!$e) {
        set_flash('error', 'Data FAQ tidak ditemukan.');
        redirect(BASE_URL . '/admin/faq/index.php');
    }
}

// DAFTAR FAQ
$list = $pdo->query(
    "SELECT * FROM faq ORDER BY urutan ASC, id ASC"
)->fetchAll();

page_start('Kelola FAQ', 'faq', 'Atur pertanyaan dan jawaban yang sering ditanyakan pelanggan.');
?>
<div class="faq-page">
    <!-- FORM FAQ -->
    <div class="faq-form-card">
        <div class="faq-card-header">
            <div>
                <h3><?= $e ? 'Edit Pertanyaan' : 'Tambah Pertanyaan' ?></h3>
                <p><?= $e ? 'Perbarui informasi pertanyaan dan jawaban.' : 'Tambahkan pertanyaan baru untuk membantu pelanggan.' ?> </p>
            </div>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/admin/faq/index.php" class="faq-form"> <?= csrf() ?>
            <?php if ($e): ?>
                <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
            <?php endif; ?>

            <div class="faq-form-group">
                <label for="pertanyaan">Pertanyaan</label>
                <input type="text" id="pertanyaan" name="pertanyaan" placeholder="Masukkan pertanyaan..." value="<?= clean($e['pertanyaan'] ?? '') ?>" required>
            </div>

            <div class="faq-form-group">
                <label for="jawaban">Jawaban</label>
                <textarea id="jawaban" name="jawaban" rows="4" placeholder="Masukkan jawaban..." required><?= clean($e['jawaban'] ?? '') ?></textarea>
            </div>

            <div class="faq-form-group faq-order">
                <label for="urutan">Urutan Tampilan</label>
                <input type="number" id="urutan" name="urutan" min="0" value="<?= (int) ($e['urutan'] ?? 0) ?>">
            </div>

            <div class="faq-form-actions">
                <button type="submit" class="faq-btn-save"> <?= $e ? 'Perbarui FAQ' : 'Simpan FAQ' ?></button>
                <?php if ($e): ?> 
                    <a href="<?= BASE_URL ?>/admin/faq/index.php" class="faq-btn-cancel">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- TABEL DAFTAR FAQ -->
    <div class="faq-list-card">
        <div class="faq-card-header">
            <div>
                <h3>Daftar FAQ</h3>
                <p>Informasi pertanyaan yang tersedia untuk pelanggan.</p>
            </div>
            <span class="faq-total">
                <?= count($list) ?> Pertanyaan
            </span>
        </div>
        <div class="faq-table-wrap">
            <table class="faq-table">
                <colgroup>
                    <col class="col-number">
                    <col class="col-question">
                    <col class="col-answer">
                    <col class="col-action">
                </colgroup>

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Pertanyaan</th>
                        <th>Jawaban</th>
                        <th class="faq-action-heading">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($list) > 0): ?>
                    <?php foreach ($list as $f): ?>
                        <tr>
                            <td class="faq-number">
                                <?= (int) $f['urutan'] ?>
                            </td>

                            <td class="faq-question">
                                <?= clean($f['pertanyaan']) ?>
                            </td>

                            <td class="faq-answer">
                                <?= clean(mb_strimwidth(
                                    $f['jawaban'], 0, 100, '...'
                                )) ?>
                            </td>

                            <td class="faq-action-cell">
                                <div class="faq-action-buttons">
                                    <!-- EDIT -->
                                    <a href="<?= BASE_URL ?>/admin/faq/index.php?edit=<?= (int) $f['id'] ?>" class="faq-icon-btn faq-edit" title="Edit FAQ" aria-label="Edit FAQ">
                                        <svg width="17" height="17"
                                             viewBox="0 0 24 24"
                                             fill="none" stroke="currentColor"
                                             stroke-width="2"
                                             stroke-linecap="round"
                                             stroke-linejoin="round">
                                            <path d="M12 20h9"/>
                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>
                                        </svg>
                                    </a>

                                    <!-- HAPUS -->
                                    <form method="POST" action="<?= BASE_URL ?>/admin/faq/index.php" onsubmit="return confirm('Yakin ingin menghapus FAQ ini?')"> <?= csrf() ?>
                                        <button type="submit" name="hapus" value="<?= (int) $f['id'] ?>" class="faq-icon-btn faq-delete" title="Hapus FAQ" aria-label="Hapus FAQ">
                                            <svg width="17" height="17"
                                                 viewBox="0 0 24 24"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 stroke-width="2"
                                                 stroke-linecap="round"
                                                 stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6l-1 14H6L5 6"/>
                                                <path d="M10 11v6"/>
                                                <path d="M14 11v6"/>
                                                <path d="M9 6V4h6v2"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="faq-empty"> Belum ada pertanyaan FAQ.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</div>
</body>
</html>
