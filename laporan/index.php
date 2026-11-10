<?php
require_once __DIR__ . '/../config/database.php';

function rupiah($angka)
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function aman($nilai)
{
    return htmlspecialchars((string) ($nilai ?? '-'), ENT_QUOTES, 'UTF-8');
}

// Laporan 1: agregasi penjualan per pelanggan.
$penjualanPelanggan = $pdo->query("
    SELECT
        pl.kode_pelanggan,
        pl.nama_pelanggan,
        COUNT(p.no_penjualan) AS jumlah_transaksi,
        COALESCE(SUM(p.total), 0) AS total_penjualan
    FROM pelanggan pl
    LEFT JOIN penjualan p
        ON p.kode_pelanggan = pl.kode_pelanggan
    GROUP BY pl.kode_pelanggan, pl.nama_pelanggan
    ORDER BY total_penjualan DESC, pl.nama_pelanggan
")->fetchAll();

// Laporan 2: penjualan per produk.
$laporanProduk = $pdo->query("
    SELECT
        pr.kode_produk,
        pr.nama_produk,
        COALESCE(SUM(dp.jumlah), 0) AS total_kuantitas,
        COALESCE(SUM(dp.subtotal), 0) AS total_penjualan
    FROM produk pr
    LEFT JOIN detil_penjualan dp
        ON dp.kode_produk = pr.kode_produk
    GROUP BY pr.kode_produk, pr.nama_produk
    ORDER BY total_penjualan DESC, pr.nama_produk
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="heading">
    <div>
        <h2>Laporan Penjualan</h2>
        <p class="report-description">
            Menampilkan dua laporan sesuai ketentuan praktikum.
        </p>
    </div>
</div>

<div class="card report-section">
    <h3>1. Agregasi Penjualan per Pelanggan</h3>
    <p class="report-description">
        Menggunakan JOIN, COUNT(), SUM(), dan GROUP BY untuk mengetahui
        jumlah transaksi serta total nilai penjualan setiap pelanggan.
    </p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode Pelanggan</th>
                    <th>Nama Pelanggan</th>
                    <th>Jumlah Transaksi</th>
                    <th>Total Nilai Penjualan</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($penjualanPelanggan): ?>
                    <?php foreach ($penjualanPelanggan as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= aman($row['kode_pelanggan']) ?></td>
                            <td><?= aman($row['nama_pelanggan']) ?></td>
                            <td><?= number_format((int) $row['jumlah_transaksi']) ?></td>
                            <td><?= rupiah($row['total_penjualan']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">
                            Belum ada data pelanggan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card report-section">
    <h3>2. Laporan Penjualan per Produk</h3>
    <p class="report-description">
        Menggunakan JOIN dan fungsi agregasi untuk menghitung total kuantitas
        serta total nilai penjualan setiap produk.
    </p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode Produk</th>
                    <th>Nama Produk</th>
                    <th>Total Kuantitas Terjual</th>
                    <th>Total Nilai Penjualan</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($laporanProduk): ?>
                    <?php foreach ($laporanProduk as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= aman($row['kode_produk']) ?></td>
                            <td><?= aman($row['nama_produk']) ?></td>
                            <td><?= number_format((int) $row['total_kuantitas']) ?></td>
                            <td><?= rupiah($row['total_penjualan']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">
                            Belum ada data produk.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
