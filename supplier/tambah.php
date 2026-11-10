
<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = trim($_POST['kode_supplier'] ?? '');
    $nama = trim($_POST['nama_supplier'] ?? '');
    $telepon = trim($_POST['no_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if ($kode === '' || $nama === '') {
        $error = 'Kode dan nama supplier wajib diisi.';
    } elseif (strlen($kode) > 10) {
        $error = 'Kode supplier maksimal 10 karakter.';
    } elseif (strlen($nama) > 100) {
        $error = 'Nama supplier maksimal 100 karakter.';
    } elseif (strlen($telepon) > 25) {
        $error = 'Nomor telepon maksimal 25 karakter.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO supplier
                (kode_supplier, nama_supplier, no_telepon, alamat)
                VALUES (:kode, :nama, :telepon, :alamat)"
            );

            $stmt->execute([
                'kode' => $kode,
                'nama' => $nama,
                'telepon' => $telepon !== '' ? $telepon : null,
                'alamat' => $alamat !== '' ? $alamat : null
            ]);

            $_SESSION['pesan'] = 'Supplier berhasil ditambahkan.';
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $error = 'Kode supplier sudah digunakan.';
            } else {
                $error = 'Gagal menambahkan supplier.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">

    <div class="heading">
        <div>
            <h2>Tambah Supplier</h2>
            <p>Masukkan informasi supplier baru.</p>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">

        <label>Kode Supplier *</label>
        <input
            type="text"
            name="kode_supplier"
            maxlength="10"
            placeholder="Contoh: S003"
            value="<?= htmlspecialchars($_POST['kode_supplier'] ?? '') ?>"
            required
        >

        <label>Nama Supplier *</label>
        <input
            type="text"
            name="nama_supplier"
            maxlength="100"
            placeholder="Masukkan nama supplier"
            value="<?= htmlspecialchars($_POST['nama_supplier'] ?? '') ?>"
            required
        >

        <label>Nomor Telepon</label>
        <input
            type="text"
            name="no_telepon"
            maxlength="25"
            placeholder="Masukkan nomor telepon"
            value="<?= htmlspecialchars($_POST['no_telepon'] ?? '') ?>"
        >

        <label>Alamat</label>
        <textarea name="alamat" rows="4"
            placeholder="Masukkan alamat supplier"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>

        <div class="actions">
            <button type="submit" class="btn-primary">Simpan</button>
            <a href="index.php" class="btn btn-secondary">Kembali</a>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
