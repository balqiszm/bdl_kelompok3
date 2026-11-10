
<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$kode = trim($_POST['kode'] ?? '');

if ($kode === '') {
    $_SESSION['pesan'] = 'Kode supplier tidak valid.';
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        "DELETE FROM supplier WHERE kode_supplier = :kode"
    );

    $stmt->execute(['kode' => $kode]);

    $_SESSION['pesan'] = $stmt->rowCount() > 0
        ? 'Supplier berhasil dihapus.'
        : 'Data supplier tidak ditemukan.';

} catch (PDOException $e) {
    if ($e->getCode() === '23503') {
        $_SESSION['pesan'] =
            'Supplier tidak dapat dihapus karena sudah memiliki transaksi pembelian.';
    } else {
        $_SESSION['pesan'] = 'Gagal menghapus data supplier.';
    }
}

header('Location: index.php');
exit;
