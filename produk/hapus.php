
<?php

require_once __DIR__ . '/../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$kode = trim($_POST['kode'] ?? '');

if ($kode === '') {
    $_SESSION['pesan'] = 'Kode produk tidak valid.';
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        "DELETE FROM produk WHERE kode_produk = :kode"
    );

    $stmt->execute(['kode' => $kode]);

    $_SESSION['pesan'] = $stmt->rowCount() > 0
        ? 'Produk berhasil dihapus!'
        : 'Produk tidak ditemukan.';

} catch (PDOException $e) {
    $_SESSION['pesan'] = 'Produk gagal dihapus. Produk mungkin sudah digunakan dalam transaksi.';
}

header('Location: index.php');
exit;
