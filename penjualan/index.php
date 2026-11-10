<?php
require_once __DIR__ . '/../config/database.php';

$keyword = trim($_GET['cari'] ?? '');

$sql = "
    SELECT
        p.no_penjualan,
        p.tgl_penjualan,
        pl.nama_pelanggan,
        p.total
    FROM penjualan p
    INNER JOIN pelanggan pl
        ON pl.kode_pelanggan = p.kode_pelanggan
";

if ($keyword !== '') {
    $sql .= "
        WHERE p.no_penjualan ILIKE :keyword
           OR pl.nama_pelanggan ILIKE :keyword
    ";
}

$sql .= " ORDER BY p.tgl_penjualan DESC, p.no_penjualan DESC";

$stmt = $pdo->prepare($sql);
if ($keyword !== '') {
    $stmt->execute(['keyword' => '%' . $keyword . '%']);
} else {
    $stmt->execute();
}
$data = $stmt->fetchAll();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="heading">
    <div>
        <h2>Transaksi Penjualan</h2>
        <p>Daftar transaksi penjualan beserta nama pelanggan menggunakan JOIN.</p>
    </div>
    <a href="tambah.php" class="btn btn-primary">+ Input Penjualan</a>
</div>

<div class="card">
    <?php if (!empty($_SESSION['pesan'])): ?>
        <div class="alert">
            <?= htmlspecialchars($_SESSION['pesan'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>

    <form method="GET" class="search">
        <input
            type="text"
            name="cari"
            placeholder="Cari nomor penjualan atau nama pelanggan..."
            value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
        >
        <button type="submit" class="btn btn-primary">Cari</button>
        <a href="index.php" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nomor Penjualan</th>
                    <th>Tanggal Penjualan</th>
                    <th>Nama Pelanggan</th>
                    <th>Total Transaksi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data): ?>
                    <?php foreach ($data as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($row['no_penjualan'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= date('d-m-Y', strtotime($row['tgl_penjualan'])) ?></td>
                            <td><?= htmlspecialchars($row['nama_pelanggan'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>Rp <?= number_format((float) $row['total'], 0, ',', '.') ?></td>
                            <td><a class="btn btn-secondary" href="detail.php?no=<?= rawurlencode($row['no_penjualan']) ?>">Detail</a></td>
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
