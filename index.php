<?php
require_once __DIR__ . '/config/database.php';

$jumlahProduk = (int) $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn();
$jumlahPelanggan = (int) $pdo->query("SELECT COUNT(*) FROM pelanggan")->fetchColumn();
$jumlahPenjualan = (int) $pdo->query("SELECT COUNT(*) FROM penjualan")->fetchColumn();
$totalPenjualan = (float) $pdo->query("
    SELECT COALESCE(SUM(total), 0) FROM penjualan
")->fetchColumn();

$penjualanTerbaru = $pdo->query("
    SELECT p.no_penjualan, p.tgl_penjualan,
           pl.nama_pelanggan, p.total
    FROM penjualan p
    JOIN pelanggan pl ON pl.kode_pelanggan = p.kode_pelanggan
    ORDER BY p.tgl_penjualan DESC, p.no_penjualan DESC
    LIMIT 5
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="heading">
    <div>
        <h2>Dashboard</h2>
        <p class="report-description">
            Ringkasan data produk dan aktivitas penjualan.
        </p>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card dashboard-stat">
        <span class="stat-label">Total Produk</span>
        <h3><?= number_format($jumlahProduk, 0, ',', '.') ?></h3>
        <a href="/produk/">Lihat produk</a>
    </div>

    <div class="card dashboard-stat">
        <span class="stat-label">Total Pelanggan</span>
        <h3><?= number_format($jumlahPelanggan, 0, ',', '.') ?></h3>
        <span class="stat-label">Data pelanggan untuk transaksi</span>
    </div>

    <div class="card dashboard-stat">
        <span class="stat-label">Transaksi Penjualan</span>
        <h3><?= number_format($jumlahPenjualan, 0, ',', '.') ?></h3>
        <a href="/penjualan/">Lihat transaksi</a>
    </div>

    <div class="card dashboard-stat">
        <span class="stat-label">Total Nilai Penjualan</span>
        <h3>Rp <?= number_format($totalPenjualan, 0, ',', '.') ?></h3>
        <a href="/laporan/">Lihat laporan</a>
    </div>
</div>

<div class="card">
    <div class="section-heading">
        <h3>Penjualan Terbaru</h3>
        <a href="/penjualan/">Lihat semua</a>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No. Penjualan</th>
                    <th>Pelanggan</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($penjualanTerbaru): ?>
                    <?php foreach ($penjualanTerbaru as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['no_penjualan']) ?></td>
                            <td><?= htmlspecialchars($item['nama_pelanggan']) ?></td>
                            <td><?= date('d/m/Y', strtotime($item['tgl_penjualan'])) ?></td>
                            <td>Rp <?= number_format((float) $item['total'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center;">
                            Belum ada transaksi penjualan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
