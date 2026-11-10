<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pelanggan = $pdo->query("
    SELECT kode_pelanggan, nama_pelanggan
    FROM pelanggan
    ORDER BY nama_pelanggan
")->fetchAll();

$produk = $pdo->query("
    SELECT kode_produk, nama_produk, harga_jual, stok
    FROM produk
    ORDER BY nama_produk
")->fetchAll();

$error = '';
$sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_penjualan = trim($_POST['no_penjualan'] ?? '');
    $tgl_penjualan = trim($_POST['tgl_penjualan'] ?? '');
    $kode_pelanggan = trim($_POST['kode_pelanggan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    $kode_produk = $_POST['produk'] ?? [];
    $jumlah_produk = $_POST['jumlah'] ?? [];

    try {
        // Mulai transaksi. Jika validasi atau proses penyimpanan gagal,
        // blok catch akan menjalankan ROLLBACK secara otomatis.
        $pdo->beginTransaction();

        if ($no_penjualan === '' || $tgl_penjualan === '' || $kode_pelanggan === '') {
            throw new Exception('Nomor penjualan, tanggal, dan pelanggan wajib diisi.');
        }

        if (strlen($no_penjualan) > 15) {
            throw new Exception('Nomor penjualan maksimal 15 karakter.');
        }

        $tanggalValid = DateTime::createFromFormat('Y-m-d', $tgl_penjualan);
        if (!$tanggalValid || $tanggalValid->format('Y-m-d') !== $tgl_penjualan) {
            throw new Exception('Format tanggal penjualan tidak valid.');
        }

        if (!is_array($kode_produk) || !is_array($jumlah_produk) ||
            count($kode_produk) < 2 || count($kode_produk) !== count($jumlah_produk)) {
            throw new Exception('Transaksi harus memiliki minimal dua baris detail produk.');
        }

        // Validasi format detail. Keberadaan kode produk diperiksa saat proses simpan,
        // agar kegagalan pada detail berikutnya dapat membatalkan header dan detail sebelumnya.
        foreach ($kode_produk as $i => $kode) {
            $kode = trim((string) $kode);
            $jumlah = filter_var($jumlah_produk[$i] ?? null, FILTER_VALIDATE_INT);

            if ($kode === '') {
                throw new Exception('Setiap baris detail wajib memilih produk.');
            }

            if ($jumlah === false || $jumlah <= 0) {
                throw new Exception('Jumlah setiap produk harus berupa bilangan bulat lebih dari nol.');
            }
        }

        // Simpan header lebih dahulu. Jika detail berikutnya gagal, header ikut di-rollback.
        $stmtHeader = $pdo->prepare("
            INSERT INTO penjualan
                (no_penjualan, tgl_penjualan, kode_pelanggan, total, keterangan)
            VALUES (?, ?, ?, 0, ?)
        ");
        $stmtHeader->execute([
            $no_penjualan,
            $tgl_penjualan,
            $kode_pelanggan,
            $keterangan !== '' ? $keterangan : null
        ]);

        $stmtProduk = $pdo->prepare("
            SELECT kode_produk, nama_produk, harga_jual, stok
            FROM produk
            WHERE kode_produk = ?
            FOR UPDATE
        ");

        $stmtDetail = $pdo->prepare("
            INSERT INTO detil_penjualan
                (no_penjualan, no_urut, kode_produk, jumlah, harga, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmtStok = $pdo->prepare("
            UPDATE produk
            SET stok = stok - ?
            WHERE kode_produk = ?
              AND stok >= ?
        ");

        $total = 0;

        foreach ($kode_produk as $i => $kode) {
            $kode = trim((string) $kode);
            $jumlah = (int) $jumlah_produk[$i];

            $stmtProduk->execute([$kode]);
            $barang = $stmtProduk->fetch();

            if (!$barang) {
                throw new Exception(
                    'Produk pada detail ke-' . ($i + 1) . ' tidak ditemukan. Transaksi dibatalkan.'
                );
            }

            if ((int) $barang['stok'] < $jumlah) {
                throw new Exception(
                    'Stok ' . $barang['nama_produk'] . ' tidak mencukupi. Transaksi dibatalkan.'
                );
            }

            $harga = (float) $barang['harga_jual'];
            $subtotal = $harga * $jumlah;

            // Simpan detail satu per satu.
            $stmtDetail->execute([
                $no_penjualan,
                $i + 1,
                $kode,
                $jumlah,
                $harga,
                $subtotal
            ]);

            // Kurangi stok dalam transaksi database yang sama.
            $stmtStok->execute([$jumlah, $kode, $jumlah]);

            if ($stmtStok->rowCount() !== 1) {
                throw new Exception(
                    'Gagal memperbarui stok ' . $barang['nama_produk'] . '. Transaksi dibatalkan.'
                );
            }

            $total += $subtotal;
        }

        // Perbarui total header setelah seluruh detail berhasil disimpan.
        $stmtUpdateTotal = $pdo->prepare("
            UPDATE penjualan
            SET total = ?
            WHERE no_penjualan = ?
        ");
        $stmtUpdateTotal->execute([$total, $no_penjualan]);

        // Semua proses berhasil: simpan permanen.
        $pdo->commit();

        $_SESSION['pesan'] =
            'Transaksi berhasil disimpan permanen (COMMIT). Stok produk telah diperbarui.';
        header('Location: index.php');
        exit;

    } catch (Throwable $e) {
        $terjadiRollback = $pdo->inTransaction();

        if ($terjadiRollback) {
            $pdo->rollBack();
        }

        if ($e instanceof PDOException && $e->getCode() === '23505') {
            $error = 'Nomor penjualan sudah digunakan. Seluruh perubahan transaksi dibatalkan (ROLLBACK jika transaksi sudah dimulai).';
        } else {
            $error = $e->getMessage();

            if ($terjadiRollback) {
                $error .= ' Seluruh perubahan telah dibatalkan (ROLLBACK).';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="heading">
    <div>
        <h2>Input Transaksi Penjualan</h2>
        <p>Masukkan pelanggan dan minimal dua baris produk yang dijual.</p>
    </div>
    <a href="index.php" class="btn btn-secondary">Daftar Penjualan</a>
</div>

<div class="card">
    <?php if ($error !== ''): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Nomor Penjualan</label>
            <input
                type="text"
                name="no_penjualan"
                maxlength="15"
                required
                value="<?= htmlspecialchars($_POST['no_penjualan'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Contoh: J20261110"
            >
        </div>

        <div class="form-group">
            <label>Tanggal Penjualan</label>
            <input
                type="date"
                name="tgl_penjualan"
                required
                value="<?= htmlspecialchars($_POST['tgl_penjualan'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="form-group">
            <label>Pelanggan</label>
            <select name="kode_pelanggan" required>
                <option value="">-- Pilih Pelanggan --</option>
                <?php foreach ($pelanggan as $p): ?>
                    <option
                        value="<?= htmlspecialchars($p['kode_pelanggan'], ENT_QUOTES, 'UTF-8') ?>"
                        <?= (($_POST['kode_pelanggan'] ?? '') === $p['kode_pelanggan']) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($p['nama_pelanggan'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <hr>

        <div class="heading">
            <div>
                <h3>Produk yang Dijual</h3>
                <p class="report-description">Minimal dua baris detail produk.</p>
            </div>
            <button type="button" class="btn btn-secondary" onclick="tambahBaris()">
                + Tambah Produk
            </button>
        </div>

        <div id="daftar-produk">
            <div class="produk-row">
                <div class="form-group">
                    <label>Produk</label>
                    <select name="produk[]" required>
                        <option value="">-- Pilih Produk --</option>
                        <?php foreach ($produk as $p): ?>
                            <option
                                value="<?= htmlspecialchars($p['kode_produk'], ENT_QUOTES, 'UTF-8') ?>"
                                data-stok="<?= (int) $p['stok'] ?>"
                            >
                                <?= htmlspecialchars($p['nama_produk'], ENT_QUOTES, 'UTF-8') ?>
                                (Stok: <?= (int) $p['stok'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jumlah</label>
                    <input type="number" name="jumlah[]" min="1" value="1" required>
                </div>

                <button type="button" class="btn btn-delete" onclick="hapusBaris(this)">
                    Hapus
                </button>
            </div>
        </div>

        <div class="form-group">
            <label>Keterangan</label>
            <textarea name="keterangan" rows="3"><?= htmlspecialchars($_POST['keterangan'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">
            Simpan Transaksi Penjualan
        </button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<script>
function tambahBaris() {
    const container = document.getElementById('daftar-produk');
    const baris = container.querySelector('.produk-row').cloneNode(true);

    baris.querySelector('select').selectedIndex = 0;
    baris.querySelector('input').value = 1;
    container.appendChild(baris);
}

function hapusBaris(button) {
    const container = document.getElementById('daftar-produk');

    if (container.querySelectorAll('.produk-row').length > 2) {
        button.closest('.produk-row').remove();
    } else {
        alert('Minimal harus ada dua baris detail produk.');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
