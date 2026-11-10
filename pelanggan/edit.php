
<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$kode = trim($_GET['kode'] ?? $_POST['kode_pelanggan'] ?? '');
$error = '';

if ($kode === '') {
    $_SESSION['pesan'] = 'Kode pelanggan tidak ditemukan.';
    header('Location: index.php');
    exit;
}

// Ambil data pelanggan
$stmt = $pdo->prepare(
    "SELECT * FROM pelanggan WHERE kode_pelanggan = :kode"
);
$stmt->execute(['kode' => $kode]);
$pelanggan = $stmt->fetch();

if (!$pelanggan) {
    $_SESSION['pesan'] = 'Data pelanggan tidak ditemukan.';
    header('Location: index.php');
    exit;
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_pelanggan'] ?? '');
    $telepon = trim($_POST['no_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if ($nama === '') {
        $error = 'Nama pelanggan wajib diisi.';
    } elseif (strlen($nama) > 100) {
        $error = 'Nama pelanggan maksimal 100 karakter.';
    } elseif (strlen($telepon) > 25) {
        $error = 'Nomor telepon maksimal 25 karakter.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "UPDATE pelanggan
                 SET nama_pelanggan = :nama,
                     no_telepon = :telepon,
                     alamat = :alamat
                 WHERE kode_pelanggan = :kode"
            );

            $stmt->execute([
                'nama' => $nama,
                'telepon' => $telepon !== '' ? $telepon : null,
                'alamat' => $alamat !== '' ? $alamat : null,
                'kode' => $kode
            ]);

            $_SESSION['pesan'] = 'Data pelanggan berhasil diperbarui.';
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            $error = 'Gagal memperbarui data pelanggan.';
        }
    }

    // Pertahankan input jika validasi gagal
    $pelanggan['nama_pelanggan'] = $nama;
    $pelanggan['no_telepon'] = $telepon;
    $pelanggan['alamat'] = $alamat;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">

    <div class="heading">
        <div>
            <h2>Edit Pelanggan</h2>
            <p>Perbarui informasi pelanggan.</p>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Kode Pelanggan</label>
        <input
            type="text"
            value="<?= htmlspecialchars($pelanggan['kode_pelanggan']) ?>"
            readonly
        >

        <label>Nama Pelanggan *</label>
        <input
            type="text"
            name="nama_pelanggan"
            maxlength="100"
            value="<?= htmlspecialchars($pelanggan['nama_pelanggan']) ?>"
            required
        >

        <label>Nomor Telepon</label>
        <input
            type="text"
            name="no_telepon"
            maxlength="25"
            value="<?= htmlspecialchars($pelanggan['no_telepon'] ?? '') ?>"
        >

        <label>Alamat</label>
        <textarea
            name="alamat"
            rows="4"
        ><?= htmlspecialchars($pelanggan['alamat'] ?? '') ?></textarea>

        <div class="actions">
            <button type="submit" class="btn-primary">
                Simpan Perubahan
            </button>

            <a href="index.php" class="btn btn-secondary">
                Kembali
            </a>
        </div>

    </form>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
