
<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$no = trim($_POST['no_pembelian'] ?? '');

try {
    $pdo->beginTransaction();

    // Ambil detail pembelian
    $stmt = $pdo->prepare("
        SELECT kode_produk, SUM(jumlah) AS jumlah
        FROM detil_pembelian
        WHERE no_pembelian = ?
        GROUP BY kode_produk
    ");
    $stmt->execute([$no]);
    $detail = $stmt->fetchAll();

    // Kurangi kembali stok yang sebelumnya ditambahkan
    $stmtStok = $pdo->prepare("
        UPDATE produk
        SET stok = stok - ?
        WHERE kode_produk = ?
          AND stok >= ?
    ");

    foreach ($detail as $item) {
        $jumlah = (int) $item['jumlah'];

        $stmtStok->execute([
            $jumlah,
            $item['kode_produk'],
            $jumlah
        ]);

        if ($stmtStok->rowCount() !== 1) {
            throw new Exception(
                'Stok tidak mencukupi untuk membatalkan pembelian. ' .
                'Produk mungkin sudah terjual. Transaksi tidak dihapus.'
            );
        }
    }

    // Hapus transaksi utama; detail terhapus melalui ON DELETE CASCADE
    $stmt = $pdo->prepare("
        DELETE FROM pembelian
        WHERE no_pembelian = ?
    ");
    $stmt->execute([$no]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Transaksi pembelian tidak ditemukan.');
    }

    $pdo->commit();

    $_SESSION['pesan'] = 'Transaksi pembelian berhasil dihapus dan stok diperbarui.';

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['pesan'] = 'Gagal menghapus pembelian: ' . $e->getMessage();
}

header('Location: index.php');
exit;
