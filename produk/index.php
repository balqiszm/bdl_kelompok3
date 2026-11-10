
<?php
require_once __DIR__ . '/../config/database.php';

$keyword = trim($_GET['cari'] ?? '');

if ($keyword !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM produk
         WHERE nama_produk ILIKE :keyword
         ORDER BY kode_produk"
    );

    $stmt->execute([
        'keyword' => '%' . $keyword . '%'
    ]);
} else {
    $stmt = $pdo->query(
        "SELECT * FROM produk ORDER BY kode_produk"
    );
}

$produk = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="heading">
        <div>
            <h2>Data Produk</h2>
            <p>Kelola data produk sistem penjualan.</p>
        </div>

        <a href="tambah.php" class="btn btn-primary">
            + Tambah Produk
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
            placeholder="Cari nama produk..."
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
                <th>Nama Produk</th>
                <th>Satuan</th>
                <th>Harga Jual</th>
                <th>Harga Beli</th>
                <th>Stok</th>
                <th>Deskripsi</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
        <?php if (count($produk) > 0): ?>
            <?php foreach ($produk as $i => $p): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($p['kode_produk']) ?></td>
                    <td><?= htmlspecialchars($p['nama_produk']) ?></td>
                    <td><?= htmlspecialchars($p['satuan']) ?></td>
                    <td>Rp <?= number_format((float)$p['harga_jual'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format((float)$p['harga_beli'], 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars((string)$p['stok']) ?></td>
                    <td><?= htmlspecialchars($p['deskripsi'] ?? '-') ?></td>
                    <td>
                        <div class="actions">
                            <a
                                href="edit.php?kode=<?= urlencode($p['kode_produk']) ?>"
                                class="btn btn-edit"
                            >Edit</a>

                            <form method="POST" action="hapus.php"
                                  onsubmit="return confirm('Yakin ingin menghapus produk ini?')">
                                <input type="hidden" name="kode"
                                       value="<?= htmlspecialchars($p['kode_produk']) ?>">
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
                <td colspan="9" style="text-align:center">
                    Data produk tidak ditemukan.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <p>Total produk ditemukan: <strong><?= count($produk) ?></strong></p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
