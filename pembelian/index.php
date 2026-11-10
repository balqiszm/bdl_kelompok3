
<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$keyword = trim($_GET['cari'] ?? '');

if ($keyword !== '') {
    $stmt = $pdo->prepare("
        SELECT p.*, s.nama_supplier
        FROM pembelian p
        JOIN supplier s ON p.kode_supplier = s.kode_supplier
        WHERE p.no_pembelian ILIKE ?
           OR s.nama_supplier ILIKE ?
        ORDER BY p.tgl_pembelian DESC, p.no_pembelian DESC
    ");
    $stmt->execute(["%$keyword%", "%$keyword%"]);
    $data = $stmt->fetchAll();
} else {
    $data = $pdo->query("
        SELECT p.*, s.nama_supplier
        FROM pembelian p
        JOIN supplier s ON p.kode_supplier = s.kode_supplier
        ORDER BY p.tgl_pembelian DESC, p.no_pembelian DESC
    ")->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="heading">
    <div>
        <h2>Data Pembelian</h2>
        <p>Kelola transaksi pembelian produk dari supplier.</p>
    </div>
    <a href="tambah.php" class="btn btn-primary">+ Tambah Pembelian</a>
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
            placeholder="Cari nomor transaksi atau supplier..."
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
                    <th>No. Pembelian</th>
                    <th>Tanggal</th>
                    <th>Supplier</th>
                    <th>Total</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($data) > 0): ?>
                    <?php foreach ($data as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($row['no_pembelian']) ?></td>
                            <td><?= date('d-m-Y', strtotime($row['tgl_pembelian'])) ?></td>
                            <td><?= htmlspecialchars($row['nama_supplier']) ?></td>
                            <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                            <td>
                                <div class="actions">
                                    <a
                                        href="detail.php?no=<?= urlencode($row['no_pembelian']) ?>"
                                        class="btn btn-edit"
                                    >Detail</a>

                                    <form
                                        method="POST"
                                        action="hapus.php"
                                        onsubmit="return confirm('Yakin ingin menghapus transaksi pembelian ini?')"
                                    >
                                        <input
                                            type="hidden"
                                            name="no_pembelian"
                                            value="<?= htmlspecialchars($row['no_pembelian']) ?>"
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
                            Belum ada data pembelian.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
