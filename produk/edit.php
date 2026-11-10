
<?php
require_once __DIR__ . '/../config/database.php';
session_start();

$kode = trim($_GET['kode'] ?? '');

$stmt = $pdo->prepare(
    "SELECT * FROM produk WHERE kode_produk = :kode"
);
$stmt->execute(['kode' => $kode]);

$data = $stmt->fetch();

if (!$data) {
    http_response_code(404);
    exit('Produk tidak ditemukan.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_produk'] ?? '');
    $satuan = trim($_POST['satuan'] ?? '');
    $hargaJual = $_POST['harga_jual'] ?? '';
    $hargaBeli = $_POST['harga_beli'] ?? '';
    $stok = $_POST['stok'] ?? '';
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (
        $nama === '' || $satuan === '' ||
        $hargaJual === '' || $hargaBeli === '' || $stok === ''
    ) {
        $error = 'Semua kolom wajib harus diisi.';
    } elseif (
        !is_numeric($hargaJual) || !is_numeric($hargaBeli) ||
        !filter_var($stok, FILTER_VALIDATE_INT) ||
        (float)$hargaJual < 0 || (float)$hargaBeli < 0 ||
        (int)$stok < 0
    ) {
        $error = 'Harga dan stok harus berupa angka yang valid dan tidak negatif.';
    } else {
        try {
            $sql = "UPDATE produk SET
                    nama_produk = :nama,
                    satuan = :satuan,
                    harga_jual = :harga_jual,
                    harga_beli = :harga_beli,
                    stok = :stok,
                    deskripsi = :deskripsi
                    WHERE kode_produk = :kode";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'nama' => $nama,
                'satuan' => $satuan,
                'harga_jual' => $hargaJual,
                'harga_beli' => $hargaBeli,
                'stok' => $stok,
                'deskripsi' => $deskripsi !== '' ? $deskripsi : null,
                'kode' => $kode
            ]);

            $_SESSION['pesan'] = 'Produk berhasil diperbarui!';
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            $error = 'Gagal memperbarui produk. Silakan periksa data.';
        }
    }

    $data = array_merge($data, [
        'nama_produk' => $nama,
        'satuan' => $satuan,
        'harga_jual' => $hargaJual,
        'harga_beli' => $hargaBeli,
        'stok' => $stok,
        'deskripsi' => $deskripsi
    ]);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Edit Produk</h2>

    <?php if ($error !== ''): ?>
        <div class="alert" style="background:#fee2e2;color:#991b1b">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label>Kode Produk</label>
        <input type="text" value="<?= htmlspecialchars($kode) ?>" disabled>

        <label>Nama Produk *</label>
        <input type="text" name="nama_produk" required
               value="<?= htmlspecialchars($data['nama_produk']) ?>">

        <label>Satuan *</label>
        <input type="text" name="satuan" required
               value="<?= htmlspecialchars($data['satuan']) ?>">

        <label>Harga Jual *</label>
        <input type="number" name="harga_jual" min="0" step="0.01" required
               value="<?= htmlspecialchars((string)$data['harga_jual']) ?>">

        <label>Harga Beli *</label>
        <input type="number" name="harga_beli" min="0" step="0.01" required
               value="<?= htmlspecialchars((string)$data['harga_beli']) ?>">

        <label>Stok *</label>
        <input type="number" name="stok" min="0" required
               value="<?= htmlspecialchars((string)$data['stok']) ?>">

        <label>Deskripsi</label>
        <textarea name="deskripsi" rows="4"><?= htmlspecialchars($data['deskripsi'] ?? '') ?></textarea>

        <button type="submit" class="btn-primary">Simpan Perubahan</button>
        <a href="index.php" class="btn btn-secondary">Kembali</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
