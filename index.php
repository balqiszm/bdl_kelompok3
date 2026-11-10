<?php
require_once __DIR__ . '/config/database.php';

function ambilAngka($pdo, $sql)
{
    return (int) $pdo->query($sql)->fetchColumn();
}

function ambilTotal($pdo, $sql)
{
    return (float) $pdo->query($sql)->fetchColumn();
}

// Ringkasan data
$jumlahProduk = ambilAngka($pdo, "SELECT COUNT(*) FROM produk");
$jumlahPelanggan = ambilAngka($pdo, "SELECT COUNT(*) FROM pelanggan");
$jumlahSupplier = ambilAngka($pdo, "SELECT COUNT(*) FROM supplier");
$jumlahPenjualan = ambilAngka($pdo, "SELECT COUNT(*) FROM penjualan");
$jumlahPembelian = ambilAngka($pdo, "SELECT COUNT(*) FROM pembelian");

$totalPenjualan = ambilTotal($pdo, "SELECT COALESCE(SUM(total), 0) FROM penjualan");
$totalPembelian = ambilTotal($pdo, "SELECT COALESCE(SUM(total), 0) FROM pembelian");

// Transaksi penjualan terbaru
$penjualanTerbaru = $pdo->query("
    SELECT p.no_penjualan, p.tgl_penjualan,
           pl.nama_pelanggan, p.total
    FROM penjualan p
    JOIN pelanggan pl ON p.kode_pelanggan = pl.kode_pelanggan
    ORDER BY p.tgl_penjualan DESC, p.no_penjualan DESC
    LIMIT 5
")->fetchAll();

// Transaksi pembelian terbaru
$pembelianTerbaru = $pdo->query("
    SELECT p.no_pembelian, p.tgl_pembelian,
           s.nama_supplier, p.total
    FROM pembelian p
    JOIN supplier s ON p.kode_supplier = s.kode_supplier
    ORDER BY p.tgl_pembelian DESC, p.no_pembelian DESC
    LIMIT 5
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="heading">
    <div>
        <h2>Dashboard</h2>
        <p class="report-description">
            Selamat datang di Sistem Penjualan | PHP Native.
            Berikut ringkasan data sistem saat ini.
        </p>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card dashboard-stat">
        <span class="stat-label">Total Produk</span>
        <h3><?= number_format($jumlahProduk, 0, ',', '.') ?></h3>
        <a href="/produk/">Lihat produk →</a>
    </div>

    <div class="card dashboard-stat">
        <span class="stat-label">Total Pelanggan</span>
        <h3><?= number_format($jumlahPelanggan, 0, ',', '.') ?></h3>
        <a href="/pelanggan/">Lihat pelanggan →</a>
    </div>

    <div class="card dashboard-stat">
        <span class="stat-label">Total Supplier</span>
        <h3><?= number_format($jumlahSupplier, 0, ',', '.') ?></h3>
        <a href="/supplier/">Lihat supplier →</a>
    </div>

    <div class="card dashboard-stat">
        <span class="stat-label">Transaksi Penjualan</span>
        <h3><?= number_format($jumlahPenjualan, 0, ',', '.') ?></h3>
        <a href="/penjualan/">Lihat penjualan →</a>
    </div>

    <div class="card dashboard-stat">
        <span class="stat-label">Transaksi Pembelian</span>
        <h3><?= number_format($jumlahPembelian, 0, ',', '.') ?></h3>
        <a href="/pembelian/">Lihat pembelian →</a>
    </div>

    <div class="card dashboard-stat">
        <span class="stat-label">Total Nilai Penjualan</span>
        <h3>Rp <?= number_format($totalPenjualan, 0, ',', '.') ?></h3>
        <a href="/laporan/">Lihat laporan →</a>
    </div>

    <div class="card dashboard-stat">
        <span class="stat-label">Total Nilai Pembelian</span>
        <h3>Rp <?= number_format($totalPembelian, 0, ',', '.') ?></h3>
        <a href="/pembelian/">Lihat pembelian →</a>
    </div>
</div>

<div class="dashboard-columns">

    <section class="card">
        <div class="section-heading">
            <h3>Penjualan Terbaru</h3>
            <a href="/penjualan/">Lihat semua</a>
        </div>

        <div class="table-wrapper">
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
                    <?php if (count($penjualanTerbaru) > 0): ?>
                        <?php foreach ($penjualanTerbaru as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['no_penjualan']) ?></td>
                                <td><?= htmlspecialchars($item['nama_pelanggan']) ?></td>
                                <td><?= date('d/m/Y', strtotime($item['tgl_penjualan'])) ?></td>
                                <td>Rp <?= number_format($item['total'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">Belum ada transaksi penjualan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="section-heading">
            <h3>Pembelian Terbaru</h3>
            <a href="/pembelian/">Lihat semua</a>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No. Pembelian</th>
                        <th>Supplier</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pembelianTerbaru) > 0): ?>
                        <?php foreach ($pembelianTerbaru as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['no_pembelian']) ?></td>
                                <td><?= htmlspecialchars($item['nama_supplier']) ?></td>
                                <td><?= date('d/m/Y', strtotime($item['tgl_pembelian'])) ?></td>
                                <td>Rp <?= number_format($item['total'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">Belum ada transaksi pembelian.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>