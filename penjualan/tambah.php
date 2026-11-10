
<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pelanggan = $pdo->query("
    SELECT kode_pelanggan, nama_pelanggan
    FROM pelanggan
    ORDER BY nama_pelanggan
")->fetchAll();

$produk = $pdo->query("
    SELECT kode_produk, nama_produk, harga_jual, stok
    FROM produk
    ORDER BY nama_produk
")->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_penjualan = trim($_POST['no_penjualan'] ?? '');
    $tgl_penjualan = $_POST['tgl_penjualan'] ?? '';
    $kode_pelanggan = $_POST['kode_pelanggan'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');

    $kode_produk = $_POST['produk'] ?? [];
    $jumlah_produk = $_POST['jumlah'] ?? [];

    try {
        if ($no_penjualan === '' || $tgl_penjualan === '' || $kode_pelanggan === '') {
            throw new Exception('Nomor transaksi, tanggal, dan pelanggan wajib diisi.');
        }

        if (strlen($no_penjualan) > 15) {
            throw new Exception('Nomor transaksi maksimal 15 karakter.');
        }

        if (count($kode_produk) === 0) {
            throw new Exception('Tambahkan minimal satu produk.');
        }

        $pdo->beginTransaction();

        // Pastikan pelanggan tersedia
        $cekPelanggan = $pdo->prepare("
            SELECT COUNT(*) FROM pelanggan
            WHERE kode_pelanggan = ?
        ");
        $cekPelanggan->execute([$kode_pelanggan]);

        if (!$cekPelanggan->fetchColumn()) {
            throw new Exception('Pelanggan tidak ditemukan.');
        }

        // Gabungkan jika produk yang sama dimasukkan lebih dari sekali
        $items = [];

        foreach ($kode_produk as $i => $kode) {
            $kode = trim($kode);
            $jumlah = filter_var(
                $jumlah_produk[$i] ?? null,
                FILTER_VALIDATE_INT
            );

            if ($kode === '' || $jumlah === false || $jumlah <= 0) {
                throw new Exception('Pilih produk dan masukkan jumlah yang valid.');
            }

            $items[$kode] = ($items[$kode] ?? 0) + $jumlah;
        }

        $total = 0;
        $detail = [];

        foreach ($items as $kode => $jumlah) {
            $stmt = $pdo->prepare("
                SELECT kode_produk, nama_produk, harga_jual, stok
                FROM produk
                WHERE kode_produk = ?
                FOR UPDATE
            ");
            $stmt->execute([$kode]);
            $barang = $stmt->fetch();

            if (!$barang) {
                throw new Exception('Produk tidak ditemukan.');
            }

            if ($barang['stok'] < $jumlah) {
                throw new Exception(
                    'Stok ' . $barang['nama_produk'] . ' tidak mencukupi.'
                );
            }

            $harga = (float) $barang['harga_jual'];
            $subtotal = $harga * $jumlah;
            $total += $subtotal;

            $detail[] = [
                'kode_produk' => $kode,
                'jumlah' => $jumlah,
                'harga' => $harga,
                'subtotal' => $subtotal
            ];
        }

        // Simpan transaksi utama
        $stmt = $pdo->prepare("
            INSERT INTO penjualan
            (no_penjualan, tgl_penjualan, kode_pelanggan, total, keterangan)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $no_penjualan,
            $tgl_penjualan,
            $kode_pelanggan,
            $total,
            $keterangan
        ]);

        // Simpan detail dan kurangi stok
        $stmtDetail = $pdo->prepare("
            INSERT INTO detil_penjualan
            (no_penjualan, no_urut, kode_produk, jumlah, harga, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmtStok = $pdo->prepare("
            UPDATE produk
            SET stok = stok - ?
            WHERE kode_produk = ? AND stok >= ?
        ");

        foreach ($detail as $i => $item) {
            $stmtDetail->execute([
                $no_penjualan,
                $i + 1,
                $item['kode_produk'],
                $item['jumlah'],
                $item['harga'],
                $item['subtotal']
            ]);

            $stmtStok->execute([
                $item['jumlah'],
                $item['kode_produk'],
                $item['jumlah']
            ]);

            if ($stmtStok->rowCount() !== 1) {
                throw new Exception('Gagal memperbarui stok produk.');
            }
        }

        $pdo->commit();

        $_SESSION['pesan'] = 'Transaksi penjualan berhasil ditambahkan.';
        header('Location: index.php');
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e->getCode() === '23505'
            ? 'Nomor transaksi sudah digunakan.'
            : $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="heading">
    <div>
        <h2>Tambah Penjualan</h2>
        <p>Masukkan informasi transaksi dan produk yang dijual.</p>
    </div>
    <a href="index.php" class="btn btn-secondary">Kembali</a>
</div>

<div class="card">
    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Nomor Penjualan</label>
            <input
                type="text"
                name="no_penjualan"
                maxlength="15"
                required
                value="<?= htmlspecialchars($_POST['no_penjualan'] ?? '') ?>"
                placeholder="Contoh: J20261103"
            >
        </div>

        <div class="form-group">
            <label>Tanggal Penjualan</label>
            <input
                type="date"
                name="tgl_penjualan"
                required
                value="<?= htmlspecialchars($_POST['tgl_penjualan'] ?? date('Y-m-d')) ?>"
            >
        </div>

        <div class="form-group">
            <label>Pelanggan</label>
            <select name="kode_pelanggan" required>
                <option value="">-- Pilih Pelanggan --</option>
                <?php foreach ($pelanggan as $p): ?>
                    <option
                        value="<?= htmlspecialchars($p['kode_pelanggan']) ?>"
                        <?= (($_POST['kode_pelanggan'] ?? '') === $p['kode_pelanggan']) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($p['nama_pelanggan']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <hr>

        <div class="heading">
            <h3>Produk yang Dijual</h3>
            <button type="button" class="btn btn-secondary" onclick="tambahBaris()">
                + Tambah Produk
            </button>
        </div>

        <div id="daftar-produk">
            <div class="produk-row">
                <div class="form-group">
                    <label>Produk</label>
                    <select name="produk[]" required>
                        <option value="">-- Pilih Produk --</option>
                        <?php foreach ($produk as $p): ?>
                            <option
                                value="<?= htmlspecialchars($p['kode_produk']) ?>"
                                data-harga="<?= htmlspecialchars($p['harga_jual']) ?>"
                                data-stok="<?= htmlspecialchars($p['stok']) ?>"
                            >
                                <?= htmlspecialchars($p['nama_produk']) ?>
                                (Stok: <?= $p['stok'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jumlah</label>
                    <input type="number" name="jumlah[]" min="1" value="1" required>
                </div>

                <button type="button" class="btn btn-delete" onclick="hapusBaris(this)">
                    Hapus
                </button>
            </div>
        </div>

        <div class="form-group">
            <label>Keterangan</label>
            <textarea name="keterangan" rows="3"><?= htmlspecialchars($_POST['keterangan'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Penjualan</button>
        <a href="index.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<script>
function tambahBaris() {
    const container = document.getElementById('daftar-produk');
    const baris = container.querySelector('.produk-row').cloneNode(true);

    baris.querySelector('select').selectedIndex = 0;
    baris.querySelector('input').value = 1;

    container.appendChild(baris);
}

function hapusBaris(button) {
    const container = document.getElementById('daftar-produk');

    if (container.querySelectorAll('.produk-row').length > 1) {
        button.closest('.produk-row').remove();
    } else {
        alert('Minimal harus ada satu produk.');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
