
<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$kode = trim($_GET['kode'] ?? '');
$error = '';

if ($kode === '') {
    $_SESSION['pesan'] = 'Kode supplier tidak ditemukan.';
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT * FROM supplier WHERE kode_supplier = :kode"
);
$stmt->execute(['kode' => $kode]);
$supplier = $stmt->fetch();

if (!$supplier) {
    $_SESSION['pesan'] = 'Data supplier tidak ditemukan.';
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_supplier'] ?? '');
    $telepon = trim($_POST['no_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if ($nama === '') {
        $error = 'Nama supplier wajib diisi.';
    } elseif (strlen($nama) > 100) {
        $error = 'Nama supplier maksimal 100 karakter.';
    } elseif (strlen($telepon) > 25) {
        $error = 'Nomor telepon maksimal 25 karakter.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "UPDATE supplier
                 SET nama_supplier = :nama,
                     no_telepon = :telepon,
                     alamat = :alamat
                 WHERE kode_supplier = :kode"
            );

            $stmt->execute([
                'nama' => $nama,
                'telepon' => $telepon !== '' ? $telepon : null,
                'alamat' => $alamat !== '' ? $alamat : null,
                'kode' => $kode
            ]);

            $_SESSION['pesan'] = 'Data supplier berhasil diperbarui.';
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            $error = 'Gagal memperbarui data supplier.';
        }
    }

    $supplier['nama_supplier'] = $nama;
    $supplier['no_telepon'] = $telepon;
    $supplier['alamat'] = $alamat;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">

    <div class="heading">
        <div>
            <h2>Edit Supplier</h2>
            <p>Perbarui informasi supplier.</p>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">

        <label>Kode Supplier</label>
        <input
            type="text"
            value="<?= htmlspecialchars($supplier['kode_supplier']) ?>"
            readonly
        >

        <label>Nama Supplier *</label>
        <input
            type="text"
            name="nama_supplier"
            maxlength="100"
            value="<?= htmlspecialchars($supplier['nama_supplier']) ?>"
            required
        >

        <label>Nomor Telepon</label>
        <input
            type="text"
            name="no_telepon"
            maxlength="25"
            value="<?= htmlspecialchars($supplier['no_telepon'] ?? '') ?>"
        >

        <label>Alamat</label>
        <textarea name="alamat" rows="4"><?= htmlspecialchars($supplier['alamat'] ?? '') ?></textarea>

        <div class="actions">
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
            <a href="index.php" class="btn btn-secondary">Kembali</a>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
