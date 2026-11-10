
<?php
require_once __DIR__ . '/../config/database.php';

$keyword = trim($_GET['cari'] ?? '');

if ($keyword !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM supplier
         WHERE nama_supplier ILIKE :keyword
         ORDER BY kode_supplier"
    );

    $stmt->execute([
        'keyword' => '%' . $keyword . '%'
    ]);
} else {
    $stmt = $pdo->query(
        "SELECT * FROM supplier ORDER BY kode_supplier"
    );
}

$supplier = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">

    <div class="heading">
        <div>
            <h2>Data Supplier</h2>
            <p>Kelola data supplier sistem penjualan.</p>
        </div>

        <a href="tambah.php" class="btn btn-primary">
            + Tambah Supplier
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
            placeholder="Cari nama supplier..."
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
                <th>Nama Supplier</th>
                <th>No. Telepon</th>
                <th>Alamat</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
        <?php if (count($supplier) > 0): ?>
            <?php foreach ($supplier as $i => $s): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($s['kode_supplier']) ?></td>
                    <td><?= htmlspecialchars($s['nama_supplier']) ?></td>
                    <td><?= htmlspecialchars($s['no_telepon'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($s['alamat'] ?? '-') ?></td>

                    <td>
                        <div class="actions">
                            <a
                                href="edit.php?kode=<?= urlencode($s['kode_supplier']) ?>"
                                class="btn btn-edit"
                            >Edit</a>

                            <form method="POST" action="hapus.php"
                                onsubmit="return confirm('Yakin ingin menghapus supplier ini?')">

                                <input
                                    type="hidden"
                                    name="kode"
                                    value="<?= htmlspecialchars($s['kode_supplier']) ?>"
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
                    Data supplier tidak ditemukan.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <p>Total supplier ditemukan:
        <strong><?= count($supplier) ?></strong>
    </p>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
