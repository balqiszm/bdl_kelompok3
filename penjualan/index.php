
<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$keyword = trim($_GET['cari'] ?? '');

if ($keyword !== '') {
    $stmt = $pdo->prepare("
        SELECT p.*, pl.nama_pelanggan
        FROM penjualan p
        JOIN pelanggan pl ON p.kode_pelanggan = pl.kode_pelanggan
        WHERE p.no_penjualan ILIKE :keyword
           OR pl.nama_pelanggan ILIKE :keyword
        ORDER BY p.tgl_penjualan DESC, p.no_penjualan DESC
    ");
    $stmt->execute(['keyword' => "%$keyword%"]);
    $data = $stmt->fetchAll();
} else {
    $data = $pdo->query("
        SELECT p.*, pl.nama_pelanggan
        FROM penjualan p
        JOIN pelanggan pl ON p.kode_pelanggan = pl.kode_pelanggan
        ORDER BY p.tgl_penjualan DESC, p.no_penjualan DESC
    ")->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="heading">
    <div>
        <h2>Data Penjualan</h2>
        <p>Kelola transaksi penjualan produk.</p>
    </div>
    <a href="tambah.php" class="btn btn-primary">+ Tambah Penjualan</a>
</div>

<div class="card">
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
            placeholder="Cari nomor transaksi atau pelanggan..."
            value="<?= htmlspecialchars($keyword) ?>"
        >
        <button type="submit" class="btn btn-primary">Cari</button>
        <a href="index.php" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>No. Penjualan</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Total</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($data) > 0): ?>
                    <?php foreach ($data as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($row['no_penjualan']) ?></td>
                            <td><?= date('d-m-Y', strtotime($row['tgl_penjualan'])) ?></td>
                            <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                            <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                            <td>
                                <div class="actions">
                                    <a
                                        href="detail.php?no=<?= urlencode($row['no_penjualan']) ?>"
                                        class="btn btn-edit"
                                    >Detail</a>

                                    <form
                                        method="POST"
                                        action="hapus.php"
                                        onsubmit="return confirm('Yakin ingin menghapus transaksi ini?')"
                                    >
                                        <input
                                            type="hidden"
                                            name="no_penjualan"
                                            value="<?= htmlspecialchars($row['no_penjualan']) ?>"
                                        >
                                        <button type="submit" class="btn btn-delete">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">
                            Belum ada data penjualan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
