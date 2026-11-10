<?php
require_once __DIR__ . '/../config/database.php';

$noPenjualan = trim($_GET['no'] ?? '');
if ($noPenjualan === '') {
    http_response_code(400);
    exit('Nomor penjualan tidak ditemukan.');
}

// JOIN header penjualan dengan pelanggan.
$stmtHeader = $pdo->prepare("\n    SELECT p.no_penjualan, p.tgl_penjualan, p.total, p.keterangan,\n           pl.kode_pelanggan, pl.nama_pelanggan, pl.no_telepon, pl.alamat\n    FROM penjualan p\n    INNER JOIN pelanggan pl ON pl.kode_pelanggan = p.kode_pelanggan\n    WHERE p.no_penjualan = :no\n");
$stmtHeader->execute(['no' => $noPenjualan]);
$header = $stmtHeader->fetch(PDO::FETCH_ASSOC);

if (!$header) {
    http_response_code(404);
    exit('Data penjualan tidak ditemukan.');
}

// JOIN detail_penjualan (detil_penjualan) dengan produk untuk menampilkan barang.
$stmtDetail = $pdo->prepare("\n    SELECT dp.no_urut, dp.kode_produk, pr.nama_produk, pr.satuan,\n           dp.jumlah, dp.harga, dp.subtotal\n    FROM detil_penjualan dp\n    INNER JOIN produk pr ON pr.kode_produk = dp.kode_produk\n    WHERE dp.no_penjualan = :no\n    ORDER BY dp.no_urut ASC\n");
$stmtDetail->execute(['no' => $noPenjualan]);
$details = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="heading">
    <div>
        <h2>Detail Penjualan</h2>
        <p>Rincian barang dalam satu transaksi penjualan.</p>
    </div>
    <a href="index.php" class="btn btn-secondary">Kembali</a>
</div>

<div class="card">
    <h3>Informasi Transaksi</h3>
    <div class="table-responsive">
        <table>
            <tbody>
                <tr><th style="width: 220px;">Nomor Penjualan</th><td><?= htmlspecialchars($header['no_penjualan'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Tanggal Penjualan</th><td><?= date('d-m-Y', strtotime($header['tgl_penjualan'])) ?></td></tr>
                <tr><th>Kode Pelanggan</th><td><?= htmlspecialchars($header['kode_pelanggan'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Nama Pelanggan</th><td><?= htmlspecialchars($header['nama_pelanggan'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>No. Telepon</th><td><?= htmlspecialchars($header['no_telepon'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Alamat</th><td><?= htmlspecialchars($header['alamat'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php if (!empty($header['keterangan'])): ?>
                    <tr><th>Keterangan</th><td><?= htmlspecialchars($header['keterangan'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3>Barang yang Dijual</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode Produk</th>
                    <th>Nama Produk</th>
                    <th>Jumlah</th>
                    <th>Harga</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($details): ?>
                    <?php foreach ($details as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($item['kode_produk'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($item['nama_produk'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float) $item['jumlah'], 0, ',', '.') ?> <?= htmlspecialchars($item['satuan'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>Rp <?= number_format((float) $item['harga'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format((float) $item['subtotal'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">Belum ada detail barang untuk transaksi ini.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" style="text-align:right;">Total Penjualan</th>
                    <th>Rp <?= number_format((float) $header['total'], 0, ',', '.') ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
