
<?php
require_once __DIR__ . '/../config/database.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = trim($_POST['kode_produk'] ?? '');
    $nama = trim($_POST['nama_produk'] ?? '');
    $satuan = trim($_POST['satuan'] ?? '');
    $hargaJual = $_POST['harga_jual'] ?? '';
    $hargaBeli = $_POST['harga_beli'] ?? '';
    $stok = $_POST['stok'] ?? '0';
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (
        $kode === '' || $nama === '' || $satuan === '' ||
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
            $sql = "INSERT INTO produk
                    (kode_produk, nama_produk, satuan,
                     harga_jual, harga_beli, stok, deskripsi)
                    VALUES
                    (:kode, :nama, :satuan,
                     :harga_jual, :harga_beli, :stok, :deskripsi)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'kode' => $kode,
                'nama' => $nama,
                'satuan' => $satuan,
                'harga_jual' => $hargaJual,
                'harga_beli' => $hargaBeli,
                'stok' => $stok,
                'deskripsi' => $deskripsi !== '' ? $deskripsi : null
            ]);

            $_SESSION['pesan'] = 'Produk berhasil ditambahkan!';
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            $error = 'Gagal menambahkan produk. Periksa kode produk dan data yang dimasukkan.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Tambah Produk</h2>

    <?php if ($error !== ''): ?>
        <div class="alert" style="background:#fee2e2;color:#991b1b">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label>Kode Produk *</label>
        <input type="text" name="kode_produk" required
               value="<?= htmlspecialchars($_POST['kode_produk'] ?? '') ?>">

        <label>Nama Produk *</label>
        <input type="text" name="nama_produk" required
               value="<?= htmlspecialchars($_POST['nama_produk'] ?? '') ?>">

        <label>Satuan *</label>
        <input type="text" name="satuan" required
               placeholder="Contoh: Pcs, Kg, Box"
               value="<?= htmlspecialchars($_POST['satuan'] ?? '') ?>">

        <label>Harga Jual *</label>
        <input type="number" name="harga_jual" min="0" step="0.01" required
               value="<?= htmlspecialchars($_POST['harga_jual'] ?? '') ?>">

        <label>Harga Beli *</label>
        <input type="number" name="harga_beli" min="0" step="0.01" required
               value="<?= htmlspecialchars($_POST['harga_beli'] ?? '') ?>">

        <label>Stok *</label>
        <input type="number" name="stok" min="0" required
               value="<?= htmlspecialchars($_POST['stok'] ?? '0') ?>">

        <label>Deskripsi</label>
        <textarea name="deskripsi" rows="4"><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>

        <button type="submit" class="btn-primary">Simpan Produk</button>
        <a href="index.php" class="btn btn-secondary">Kembali</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
