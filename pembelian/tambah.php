
<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$supplier = $pdo->query("
    SELECT kode_supplier, nama_supplier
    FROM supplier
    ORDER BY nama_supplier
")->fetchAll();

$produk = $pdo->query("
    SELECT kode_produk, nama_produk, harga_beli, stok, satuan
    FROM produk
    ORDER BY nama_produk
")->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_pembelian = trim($_POST['no_pembelian'] ?? '');
    $tgl_pembelian = $_POST['tgl_pembelian'] ?? '';
    $kode_supplier = $_POST['kode_supplier'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');

    $kode_produk = $_POST['produk'] ?? [];
    $jumlah_produk = $_POST['jumlah'] ?? [];

    try {
        if (
            $no_pembelian === '' ||
            $tgl_pembelian === '' ||
            $kode_supplier === ''
        ) {
            throw new Exception('Nomor transaksi, tanggal, dan supplier wajib diisi.');
        }

        if (strlen($no_pembelian) > 15) {
            throw new Exception('Nomor transaksi maksimal 15 karakter.');
        }

        if (!is_array($kode_produk) || !is_array($jumlah_produk) || count($kode_produk) === 0) {
            throw new Exception('Tambahkan minimal satu produk.');
        }

        $pdo->beginTransaction();

        // Periksa supplier
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM supplier
            WHERE kode_supplier = ?
        ");
        $stmt->execute([$kode_supplier]);

        if (!$stmt->fetchColumn()) {
            throw new Exception('Supplier tidak ditemukan.');
        }

        // Gabungkan produk yang sama
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
                SELECT kode_produk, nama_produk, harga_beli
                FROM produk
                WHERE kode_produk = ?
                FOR UPDATE
            ");
            $stmt->execute([$kode]);
            $barang = $stmt->fetch();

            if (!$barang) {
                throw new Exception('Produk tidak ditemukan.');
            }

            $harga = (float) $barang['harga_beli'];
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
            INSERT INTO pembelian
            (no_pembelian, tgl_pembelian, kode_supplier, total, keterangan)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $no_pembelian,
            $tgl_pembelian,
            $kode_supplier,
            $total,
            $keterangan
        ]);

        // Simpan detail dan tambahkan stok
        $stmtDetail = $pdo->prepare("
            INSERT INTO detil_pembelian
            (no_pembelian, no_urut, kode_produk, jumlah, harga, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmtStok = $pdo->prepare("
            UPDATE produk
            SET stok = stok + ?
            WHERE kode_produk = ?
        ");

        foreach ($detail as $i => $item) {
            $stmtDetail->execute([
                $no_pembelian,
                $i + 1,
                $item['kode_produk'],
                $item['jumlah'],
                $item['harga'],
                $item['subtotal']
            ]);

            $stmtStok->execute([
                $item['jumlah'],
                $item['kode_produk']
            ]);
        }

        $pdo->commit();

        $_SESSION['pesan'] = 'Transaksi pembelian berhasil ditambahkan.';
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
        <h2>Tambah Pembelian</h2>
        <p>Masukkan informasi pembelian produk dari supplier.</p>
    </div>
    <a href="index.php" class="btn btn-secondary">Kembali</a>
</div>

<div class="card">
    <?php if ($error): ?>
        <div class="alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Nomor Pembelian</label>
            <input
                type="text"
                name="no_pembelian"
                maxlength="15"
                required
                value="<?= htmlspecialchars($_POST['no_pembelian'] ?? '') ?>"
                placeholder="Contoh: B20261110001"
            >
        </div>

        <div class="form-group">
            <label>Tanggal Pembelian</label>
            <input
                type="date"
                name="tgl_pembelian"
                required
                value="<?= htmlspecialchars($_POST['tgl_pembelian'] ?? date('Y-m-d')) ?>"
            >
        </div>

        <div class="form-group">
            <label>Supplier</label>
            <select name="kode_supplier" required>
                <option value="">-- Pilih Supplier --</option>
                <?php foreach ($supplier as $s): ?>
                    <option
                        value="<?= htmlspecialchars($s['kode_supplier']) ?>"
                        <?= (($_POST['kode_supplier'] ?? '') === $s['kode_supplier']) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($s['nama_supplier']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <hr>

        <div class="heading">
            <h3>Produk yang Dibeli</h3>
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
                            >
                                <?= htmlspecialchars($p['nama_produk']) ?>
                                (<?= htmlspecialchars($p['satuan']) ?>)
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
            <textarea
                name="keterangan"
                rows="3"
            ><?= htmlspecialchars($_POST['keterangan'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Pembelian</button>
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
