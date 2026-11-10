
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
    $_SESSION['pesan'] = 'Kode pelanggan tidak valid.';
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        "DELETE FROM pelanggan
         WHERE kode_pelanggan = :kode"
    );

    $stmt->execute(['kode' => $kode]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['pesan'] = 'Pelanggan berhasil dihapus.';
    } else {
        $_SESSION['pesan'] = 'Data pelanggan tidak ditemukan.';
    }

} catch (PDOException $e) {
    if ($e->getCode() === '23503') {
        $_SESSION['pesan'] =
            'Pelanggan tidak dapat dihapus karena sudah memiliki transaksi penjualan.';
    } else {
        $_SESSION['pesan'] = 'Gagal menghapus data pelanggan.';
    }
}

header('Location: index.php');
exit;
