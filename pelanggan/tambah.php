
<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = trim($_POST['kode_pelanggan'] ?? '');
    $nama = trim($_POST['nama_pelanggan'] ?? '');
    $telepon = trim($_POST['no_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if ($kode === '' || $nama === '') {
        $error = 'Kode dan nama pelanggan wajib diisi.';
    } elseif (strlen($kode) > 10) {
        $error = 'Kode pelanggan maksimal 10 karakter.';
    } elseif (strlen($nama) > 100) {
        $error = 'Nama pelanggan maksimal 100 karakter.';
    } elseif (strlen($telepon) > 25) {
        $error = 'Nomor telepon maksimal 25 karakter.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO pelanggan
                (kode_pelanggan, nama_pelanggan, no_telepon, alamat)
                VALUES (:kode, :nama, :telepon, :alamat)"
            );

            $stmt->execute([
                'kode' => $kode,
                'nama' => $nama,
                'telepon' => $telepon !== '' ? $telepon : null,
                'alamat' => $alamat !== '' ? $alamat : null
            ]);

            $_SESSION['pesan'] = 'Pelanggan berhasil ditambahkan.';
            header('Location: index.php');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $error = 'Kode pelanggan sudah digunakan.';
            } else {
                $error = 'Gagal menambahkan pelanggan: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">

    <div class="heading">
        <div>
            <h2>Tambah Pelanggan</h2>
            <p>Masukkan informasi pelanggan baru.</p>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Kode Pelanggan *</label>
        <input
            type="text"
            name="kode_pelanggan"
            maxlength="10"
            placeholder="Contoh: C004"
            value="<?= htmlspecialchars($_POST['kode_pelanggan'] ?? '') ?>"
            required
        >

        <label>Nama Pelanggan *</label>
        <input
            type="text"
            name="nama_pelanggan"
            maxlength="100"
            placeholder="Masukkan nama pelanggan"
            value="<?= htmlspecialchars($_POST['nama_pelanggan'] ?? '') ?>"
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
        <textarea
            name="alamat"
            rows="4"
            placeholder="Masukkan alamat pelanggan"
        ><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>

        <div class="actions">
            <button type="submit" class="btn-primary">
                Simpan
            </button>

            <a href="index.php" class="btn btn-secondary">
                Kembali
            </a>
        </div>

    </form>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
