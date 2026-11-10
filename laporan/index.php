
<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function rupiah($angka) {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function aman($nilai) {
    return htmlspecialchars((string) ($nilai ?? '-'), ENT_QUOTES, 'UTF-8');
}

// 1. Daftar transaksi penjualan beserta pelanggan
$transaksi = $pdo->query("
    SELECT p.no_penjualan, p.tgl_penjualan,
           c.nama_pelanggan, p.total
    FROM penjualan p
    JOIN pelanggan c
        ON c.kode_pelanggan = p.kode_pelanggan
    ORDER BY p.tgl_penjualan DESC, p.no_penjualan DESC
")->fetchAll();

// 2. Rincian transaksi penjualan berdasarkan nomor transaksi
$no = trim($_GET['no'] ?? '');
$detail = [];

if ($no !== '') {
    $stmt = $pdo->prepare("
        SELECT dp.no_penjualan, pr.nama_produk,
               dp.jumlah, dp.harga, dp.subtotal
        FROM detil_penjualan dp
        JOIN produk pr
            ON pr.kode_produk = dp.kode_produk
        WHERE dp.no_penjualan = ?
        ORDER BY dp.no_urut
    ");
    $stmt->execute([$no]);
    $detail = $stmt->fetchAll();
}

// 3. Produk terlaris berdasarkan total unit terjual
$produkTerlaris = $pdo->query("
    SELECT pr.kode_produk, pr.nama_produk,
           COALESCE(SUM(dp.jumlah), 0) AS total_unit_terjual
    FROM produk pr
    LEFT JOIN detil_penjualan dp
        ON dp.kode_produk = pr.kode_produk
    GROUP BY pr.kode_produk, pr.nama_produk
    ORDER BY total_unit_terjual DESC, pr.nama_produk
")->fetchAll();

// 4. Total pembelian per supplier
$pembelianSupplier = $pdo->query("
    SELECT s.kode_supplier, s.nama_supplier,
           COALESCE(SUM(pb.total), 0) AS total_pembelian
    FROM supplier s
    LEFT JOIN pembelian pb
        ON pb.kode_supplier = s.kode_supplier
    GROUP BY s.kode_supplier, s.nama_supplier
    ORDER BY total_pembelian DESC
")->fetchAll();

// 5. Pemeriksaan konsistensi total penjualan
$cekPenjualan = $pdo->query("
    SELECT p.no_penjualan,
           p.total AS total_header,
           COALESCE(SUM(dp.subtotal), 0) AS total_detail
    FROM penjualan p
    LEFT JOIN detil_penjualan dp
        ON dp.no_penjualan = p.no_penjualan
    GROUP BY p.no_penjualan, p.total
    HAVING p.total <> COALESCE(SUM(dp.subtotal), 0)
")->fetchAll();

// 6. Pemeriksaan konsistensi total pembelian
$cekPembelian = $pdo->query("
    SELECT pb.no_pembelian,
           pb.total AS total_header,
           COALESCE(SUM(dp.subtotal), 0) AS total_detail
    FROM pembelian pb
    LEFT JOIN detil_pembelian dp
        ON dp.no_pembelian = pb.no_pembelian
    GROUP BY pb.no_pembelian, pb.total
    HAVING pb.total <> COALESCE(SUM(dp.subtotal), 0)
")->fetchAll();

// 7. Agregasi penjualan per pelanggan
$penjualanPelanggan = $pdo->query("
    SELECT pl.kode_pelanggan,
           pl.nama_pelanggan,
           COUNT(p.no_penjualan) AS jumlah_transaksi,
           COALESCE(SUM(p.total), 0) AS total_penjualan
    FROM pelanggan pl
    LEFT JOIN penjualan p
        ON p.kode_pelanggan = pl.kode_pelanggan
    GROUP BY pl.kode_pelanggan, pl.nama_pelanggan
    ORDER BY total_penjualan DESC
")->fetchAll();

// 8. Laporan penjualan per produk
$laporanProduk = $pdo->query("
    SELECT pr.kode_produk,
           pr.nama_produk,
           COALESCE(SUM(dp.jumlah), 0) AS total_terjual,
           COALESCE(SUM(dp.subtotal), 0) AS total_penjualan
    FROM produk pr
    LEFT JOIN detil_penjualan dp
        ON dp.kode_produk = pr.kode_produk
    GROUP BY pr.kode_produk, pr.nama_produk
    ORDER BY total_penjualan DESC
")->fetchAll();

$totalTransaksi = count($transaksi);
$totalNilaiPenjualan = 0;

foreach ($transaksi as $row) {
    $totalNilaiPenjualan += (float) $row['total'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="heading">
    <div>
        <h2>Laporan Sistem</h2>
        <p>Ringkasan transaksi dan informasi penjualan serta pembelian.</p>
    </div>
</div>

<!-- RINGKASAN -->
<div class="report-summary">
    <div class="card">
        <span>Total Transaksi Penjualan</span>
        <h2><?= number_format($totalTransaksi) ?></h2>
    </div>

    <div class="card">
        <span>Total Nilai Penjualan</span>
        <h2><?= rupiah($totalNilaiPenjualan) ?></h2>
    </div>

    <div class="card">
        <span>Jumlah Produk</span>
        <h2><?= count($produkTerlaris) ?></h2>
    </div>
</div>

<!-- 1. DAFTAR TRANSAKSI -->
<div class="card report-section">
    <h3>1. Daftar Transaksi Penjualan</h3>
    <p class="report-description">
        Menampilkan transaksi penjualan beserta nama pelanggan menggunakan JOIN.
    </p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>No. Penjualan</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Total</th>
                    <th>Rincian</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transaksi): ?>
                    <?php foreach ($transaksi as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= aman($row['no_penjualan']) ?></td>
                            <td><?= date('d-m-Y', strtotime($row['tgl_penjualan'])) ?></td>
                            <td><?= aman($row['nama_pelanggan']) ?></td>
                            <td><?= rupiah($row['total']) ?></td>
                            <td>
                                <a
                                    href="?no=<?= urlencode($row['no_penjualan']) ?>#rincian"
                                    class="btn btn-edit"
                                >Lihat</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">
                            Belum ada transaksi penjualan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 2. RINCIAN TRANSAKSI -->
<div class="card report-section" id="rincian">
    <h3>2. Rincian Transaksi Penjualan</h3>
    <p class="report-description">
        Pilih nomor penjualan untuk melihat produk yang terdapat dalam transaksi.
    </p>

    <form method="GET" class="search">
        <select name="no" required>
            <option value="">-- Pilih Nomor Penjualan --</option>
            <?php foreach ($transaksi as $row): ?>
                <option
                    value="<?= aman($row['no_penjualan']) ?>"
                    <?= $no === $row['no_penjualan'] ? 'selected' : '' ?>
                >
                    <?= aman($row['no_penjualan']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Tampilkan</button>
    </form>

    <?php if ($no !== ''): ?>
        <?php if ($detail): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Produk</th>
                            <th>Jumlah</th>
                            <th>Harga</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $totalDetail = 0; ?>
                        <?php foreach ($detail as $i => $row): ?>
                            <?php $totalDetail += (float) $row['subtotal']; ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= aman($row['nama_produk']) ?></td>
                                <td><?= number_format($row['jumlah']) ?></td>
                                <td><?= rupiah($row['harga']) ?></td>
                                <td><?= rupiah($row['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" style="text-align:right;">Total</th>
                            <th><?= rupiah($totalDetail) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <p>Nomor transaksi tidak ditemukan atau belum memiliki detail.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- 3. PENJUALAN PER PELANGGAN -->
<div class="card report-section">
    <h3>3. Agregasi Penjualan per Pelanggan</h3>
    <p class="report-description">
        Menggunakan COUNT(), SUM(), GROUP BY, dan JOIN untuk menghitung
        jumlah transaksi dan total penjualan setiap pelanggan.
    </p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode Pelanggan</th>
                    <th>Nama Pelanggan</th>
                    <th>Jumlah Transaksi</th>
                    <th>Total Penjualan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($penjualanPelanggan as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= aman($row['kode_pelanggan']) ?></td>
                        <td><?= aman($row['nama_pelanggan']) ?></td>
                        <td><?= number_format($row['jumlah_transaksi']) ?></td>
                        <td><?= rupiah($row['total_penjualan']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 4. PRODUK TERLARIS -->
<div class="card report-section">
    <h3>4. Produk Terlaris</h3>
    <p class="report-description">
        Menghitung total unit setiap produk yang telah terjual.
    </p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode Produk</th>
                    <th>Nama Produk</th>
                    <th>Total Unit Terjual</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produkTerlaris as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= aman($row['kode_produk']) ?></td>
                        <td><?= aman($row['nama_produk']) ?></td>
                        <td><?= number_format($row['total_unit_terjual']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 5. LAPORAN PENJUALAN PER PRODUK -->
<div class="card report-section">
    <h3>5. Laporan Penjualan per Produk</h3>
    <p class="report-description">
        Menampilkan total kuantitas terjual dan total nilai penjualan
        setiap produk.
    </p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode Produk</th>
                    <th>Nama Produk</th>
                    <th>Total Terjual</th>
                    <th>Total Penjualan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($laporanProduk as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= aman($row['kode_produk']) ?></td>
                        <td><?= aman($row['nama_produk']) ?></td>
                        <td><?= number_format($row['total_terjual']) ?></td>
                        <td><?= rupiah($row['total_penjualan']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 6. PEMBELIAN PER SUPPLIER -->
<div class="card report-section">
    <h3>6. Total Pembelian per Supplier</h3>
    <p class="report-description">
        Menghitung nilai pembelian berdasarkan masing-masing supplier.
    </p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode Supplier</th>
                    <th>Nama Supplier</th>
                    <th>Total Pembelian</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pembelianSupplier as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= aman($row['kode_supplier']) ?></td>
                        <td><?= aman($row['nama_supplier']) ?></td>
                        <td><?= rupiah($row['total_pembelian']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 7. CEK KONSISTENSI PENJUALAN -->
<div class="card report-section">
    <h3>7. Pemeriksaan Konsistensi Penjualan</h3>
    <p class="report-description">
        Membandingkan total pada tabel penjualan dengan penjumlahan subtotal detail.
        Hanya transaksi yang memiliki perbedaan yang ditampilkan.
    </p>

    <?php if (!$cekPenjualan): ?>
        <div class="alert">
            Semua total transaksi penjualan sudah sesuai dengan detail.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No. Penjualan</th>
                        <th>Total Header</th>
                        <th>Total Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cekPenjualan as $row): ?>
                        <tr>
                            <td><?= aman($row['no_penjualan']) ?></td>
                            <td><?= rupiah($row['total_header']) ?></td>
                            <td><?= rupiah($row['total_detail']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- 8. CEK KONSISTENSI PEMBELIAN -->
<div class="card report-section">
    <h3>8. Pemeriksaan Konsistensi Pembelian</h3>
    <p class="report-description">
        Membandingkan total pada tabel pembelian dengan penjumlahan subtotal detail.
        Hanya transaksi yang memiliki perbedaan yang ditampilkan.
    </p>

    <?php if (!$cekPembelian): ?>
        <div class="alert">
            Semua total transaksi pembelian sudah sesuai dengan detail.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No. Pembelian</th>
                        <th>Total Header</th>
                        <th>Total Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cekPembelian as $row): ?>
                        <tr>
                            <td><?= aman($row['no_pembelian']) ?></td>
                            <td><?= rupiah($row['total_header']) ?></td>
                            <td><?= rupiah($row['total_detail']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
