
<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$no = trim($_POST['no_penjualan'] ?? '');

try {
    $pdo->beginTransaction();

    // Ambil detail produk yang pernah dijual
    $stmt = $pdo->prepare("
        SELECT kode_produk, SUM(jumlah) AS jumlah
        FROM detil_penjualan
        WHERE no_penjualan = ?
        GROUP BY kode_produk
    ");
    $stmt->execute([$no]);
    $detail = $stmt->fetchAll();

    // Kembalikan stok
    $stmtStok = $pdo->prepare("
        UPDATE produk
        SET stok = stok + ?
        WHERE kode_produk = ?
    ");

    foreach ($detail as $item) {
        $stmtStok->execute([
            $item['jumlah'],
            $item['kode_produk']
        ]);
    }

    // Hapus transaksi utama (detail terhapus melalui ON DELETE CASCADE)
    $stmt = $pdo->prepare("
        DELETE FROM penjualan
        WHERE no_penjualan = ?
    ");
    $stmt->execute([$no]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Transaksi tidak ditemukan.');
    }

    $pdo->commit();

    $_SESSION['pesan'] = 'Transaksi berhasil dihapus dan stok telah dikembalikan.';

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['pesan'] = 'Gagal menghapus transaksi: ' . $e->getMessage();
}

header('Location: index.php');
exit;
