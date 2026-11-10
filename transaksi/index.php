
<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$hasil = $_SESSION['hasil_transaksi'] ?? null;
unset($_SESSION['hasil_transaksi']);

$produk = $pdo->query("
    SELECT kode_produk, nama_produk, stok
    FROM produk
    ORDER BY nama_produk
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = trim($_POST['kode_produk'] ?? '');
    $aksi = $_POST['aksi'] ?? '';
    $jumlah = filter_var($_POST['jumlah'] ?? null, FILTER_VALIDATE_INT);
    $metode = $_POST['metode'] ?? '';

    try {
        if ($kode === '' || !in_array($aksi, ['tambah', 'kurang'], true)) {
            throw new Exception('Pilih produk dan jenis perubahan stok.');
        }

        if ($jumlah === false || $jumlah <= 0) {
            throw new Exception('Jumlah harus berupa angka lebih dari nol.');
        }

        if (!in_array($metode, ['commit', 'rollback'], true)) {
            throw new Exception('Metode transaksi tidak valid.');
        }

        $pdo->beginTransaction();

        // Ambil stok dan kunci baris selama transaksi berlangsung
        $stmt = $pdo->prepare("
            SELECT nama_produk, stok
            FROM produk
            WHERE kode_produk = ?
            FOR UPDATE
        ");
        $stmt->execute([$kode]);
        $barang = $stmt->fetch();

        if (!$barang) {
            throw new Exception('Produk tidak ditemukan.');
        }

        $stokSebelum = (int) $barang['stok'];

        if ($aksi === 'tambah') {
            $stmt = $pdo->prepare("
                UPDATE produk
                SET stok = stok + ?
                WHERE kode_produk = ?
            ");
            $stmt->execute([$jumlah, $kode]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE produk
                SET stok = stok - ?
                WHERE kode_produk = ?
                  AND stok >= ?
            ");
            $stmt->execute([$jumlah, $kode, $jumlah]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception('Stok tidak mencukupi untuk dikurangi.');
            }
        }

        // Baca hasil perubahan sebelum transaksi diselesaikan
        $stmt = $pdo->prepare("
            SELECT stok FROM produk WHERE kode_produk = ?
        ");
        $stmt->execute([$kode]);
        $stokSetelah = (int) $stmt->fetchColumn();

        if ($metode === 'commit') {
            $pdo->commit();
            $status = 'COMMIT BERHASIL';
            $keterangan = 'Perubahan stok berhasil disimpan ke database.';
            $stokAkhir = $stokSetelah;
        } else {
            $pdo->rollBack();

            $status = 'ROLLBACK BERHASIL';
            $keterangan = 'Perubahan dibatalkan. Stok dikembalikan ke nilai awal.';
            $stokAkhir = $stokSebelum;
        }

        $_SESSION['hasil_transaksi'] = [
            'status' => $status,
            'keterangan' => $keterangan,
            'nama_produk' => $barang['nama_produk'],
            'stok_sebelum' => $stokSebelum,
            'stok_perubahan' => $stokSetelah,
            'stok_akhir' => $stokAkhir,
            'metode' => strtoupper($metode)
        ];

        header('Location: index.php');
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="heading">
    <div>
        <h2>Pengujian Transaksi Database</h2>
        <p>Menguji proses COMMIT dan ROLLBACK pada perubahan stok produk.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert-error">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if ($hasil): ?>
    <div class="card">
        <h3><?= htmlspecialchars($hasil['status']) ?></h3>
        <p><?= htmlspecialchars($hasil['keterangan']) ?></p>

        <table>
            <tr>
                <th>Produk</th>
                <td><?= htmlspecialchars($hasil['nama_produk']) ?></td>
            </tr>
            <tr>
                <th>Metode</th>
                <td><?= htmlspecialchars($hasil['metode']) ?></td>
            </tr>
            <tr>
                <th>Stok Sebelum</th>
                <td><?= number_format($hasil['stok_sebelum']) ?></td>
            </tr>
            <tr>
                <th>Stok Setelah Perubahan</th>
                <td><?= number_format($hasil['stok_perubahan']) ?></td>
            </tr>
            <tr>
                <th>Stok Akhir Database</th>
                <td><strong><?= number_format($hasil['stok_akhir']) ?></strong></td>
            </tr>
        </table>
    </div>
<?php endif; ?>

<div class="card">
    <h3>Form Pengujian Transaksi</h3>
    <p class="report-description">
        Pilih produk, jenis perubahan stok, jumlah, dan metode transaksi.
    </p>

    <form method="POST">
        <div class="form-group">
            <label>Produk</label>
            <select name="kode_produk" required>
                <option value="">-- Pilih Produk --</option>
                <?php foreach ($produk as $p): ?>
                    <option value="<?= htmlspecialchars($p['kode_produk']) ?>">
                        <?= htmlspecialchars($p['nama_produk']) ?>
                        (Stok: <?= number_format($p['stok']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Jenis Perubahan</label>
            <select name="aksi" required>
                <option value="">-- Pilih Perubahan --</option>
                <option value="tambah">Tambah Stok</option>
                <option value="kurang">Kurangi Stok</option>
            </select>
        </div>

        <div class="form-group">
            <label>Jumlah</label>
            <input
                type="number"
                name="jumlah"
                min="1"
                required
                placeholder="Masukkan jumlah perubahan"
            >
        </div>

        <div class="form-group">
            <label>Metode Transaksi</label>
            <select name="metode" required>
                <option value="">-- Pilih Metode --</option>
                <option value="commit">COMMIT (Simpan Perubahan)</option>
                <option value="rollback">ROLLBACK (Batalkan Perubahan)</option>
            </select>
        </div>

        <button
            type="submit"
            class="btn btn-primary"
            onclick="return confirm('Jalankan pengujian transaksi database?')"
        >
            Jalankan Pengujian
        </button>
    </form>
</div>

<div class="card">
    <h3>Perbedaan COMMIT dan ROLLBACK</h3>

    <table>
        <thead>
            <tr>
                <th>Perintah</th>
                <th>Fungsi</th>
                <th>Hasil</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>BEGIN</td>
                <td>Memulai transaksi database.</td>
                <td>Transaksi dimulai.</td>
            </tr>
            <tr>
                <td>COMMIT</td>
                <td>Menyimpan perubahan.</td>
                <td>Perubahan permanen.</td>
            </tr>
            <tr>
                <td>ROLLBACK</td>
                <td>Membatalkan perubahan.</td>
                <td>Kembali ke kondisi awal.</td>
            </tr>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
