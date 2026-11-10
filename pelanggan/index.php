
<?php
require_once __DIR__ . '/../config/database.php';

$keyword = trim($_GET['cari'] ?? '');

if ($keyword !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM pelanggan
         WHERE nama_pelanggan ILIKE :keyword
         ORDER BY kode_pelanggan"
    );

    $stmt->execute([
        'keyword' => '%' . $keyword . '%'
    ]);
} else {
    $stmt = $pdo->query(
        "SELECT * FROM pelanggan ORDER BY kode_pelanggan"
    );
}

$pelanggan = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">

    <div class="heading">
        <div>
            <h2>Data Pelanggan</h2>
            <p>Kelola data pelanggan sistem penjualan.</p>
        </div>

        <a href="tambah.php" class="btn btn-primary">
            + Tambah Pelanggan
        </a>
    </div>

    <?php if (!empty($_SESSION['pesan'])): ?>
        <div class="alert">
            <?= htmlspecialchars($_SESSION['pesan']) ?>
        </div>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>

    <form method="GET" class="search">
        <input
            type="text"
            name="cari"
            placeholder="Cari nama pelanggan..."
            value="<?= htmlspecialchars($keyword) ?>"
        >

        <button class="btn-primary" type="submit">Cari</button>

        <a href="index.php" class="btn btn-secondary">Reset</a>
    </form>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Kode</th>
                <th>Nama Pelanggan</th>
                <th>No. Telepon</th>
                <th>Alamat</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
        <?php if (count($pelanggan) > 0): ?>
            <?php foreach ($pelanggan as $i => $p): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($p['kode_pelanggan']) ?></td>
                    <td><?= htmlspecialchars($p['nama_pelanggan']) ?></td>
                    <td><?= htmlspecialchars($p['no_telepon'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['alamat'] ?? '-') ?></td>
                    <td>
                        <div class="actions">
                            <a
                                href="edit.php?kode=<?= urlencode($p['kode_pelanggan']) ?>"
                                class="btn btn-edit"
                            >Edit</a>

                            <form method="POST" action="hapus.php"
                                  onsubmit="return confirm('Yakin ingin menghapus pelanggan ini?')">
                                <input
                                    type="hidden"
                                    name="kode"
                                    value="<?= htmlspecialchars($p['kode_pelanggan']) ?>"
                                >

                                <button type="submit" class="btn-delete">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center">
                    Data pelanggan tidak ditemukan.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <p>Total pelanggan ditemukan: <strong><?= count($pelanggan) ?></strong></p>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
