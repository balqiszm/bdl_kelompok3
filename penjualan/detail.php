
<?php
require_once __DIR__ . '/../config/database.php';

$no = trim($_GET['no'] ?? '');

$stmt = $pdo->prepare("
    SELECT p.*, pl.nama_pelanggan, pl.no_telepon, pl.alamat
    FROM penjualan p
    JOIN pelanggan pl ON p.kode_pelanggan = pl.kode_pelanggan
    WHERE p.no_penjualan = ?
");
$stmt->execute([$no]);
$penjualan = $stmt->fetch();

if (!$penjualan) {
    die('Data penjualan tidak ditemukan.');
}

$stmt = $pdo->prepare("
    SELECT d.*, pr.nama_produk, pr.satuan
    FROM detil_penjualan d
    JOIN produk pr ON d.kode_produk = pr.kode_produk
    WHERE d.no_penjualan = ?
    ORDER BY d.no_urut
");
$stmt->execute([$no]);
$detail = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="heading">
    <div>
        <h2>Detail Penjualan</h2>
        <p>Informasi lengkap transaksi penjualan.</p>
    </div>
    <a href="index.php" class="btn btn-secondary">Kembali</a>
</div>

<div class="card">
    <h3>Informasi Transaksi</h3>

    <table>
        <tr>
            <th width="180">Nomor Penjualan</th>
            <td><?= htmlspecialchars($penjualan['no_penjualan']) ?></td>
        </tr>
        <tr>
            <th>Tanggal</th>
            <td><?= date('d-m-Y', strtotime($penjualan['tgl_penjualan'])) ?></td>
        </tr>
        <tr>
            <th>Pelanggan</th>
            <td><?= htmlspecialchars($penjualan['nama_pelanggan']) ?></td>
        </tr>
        <tr>
            <th>No. Telepon</th>
            <td><?= htmlspecialchars($penjualan['no_telepon'] ?? '-') ?></td>
        </tr>
        <tr>
            <th>Alamat</th>
            <td><?= htmlspecialchars($penjualan['alamat'] ?? '-') ?></td>
        </tr>
        <tr>
            <th>Keterangan</th>
            <td><?= htmlspecialchars($penjualan['keterangan'] ?? '-') ?></td>
        </tr>
    </table>
</div>

<div class="card">
    <h3>Daftar Produk</h3>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Produk</th>
                    <th>Jumlah</th>
                    <th>Harga</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detail as $i => $d): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($d['nama_produk']) ?></td>
                        <td><?= number_format($d['jumlah']) ?> <?= htmlspecialchars($d['satuan']) ?></td>
                        <td>Rp <?= number_format($d['harga'], 0, ',', '.') ?></td>
                        <td>Rp <?= number_format($d['subtotal'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" style="text-align:right;">Total</th>
                    <th>Rp <?= number_format($penjualan['total'], 0, ',', '.') ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<a href="index.php" class="btn btn-secondary">Kembali ke Daftar</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
