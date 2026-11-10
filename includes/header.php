
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$halaman = basename(dirname($_SERVER['SCRIPT_NAME']));

if ($halaman === '.' || $halaman === '') {
    $halaman = 'dashboard';
}

function menuAktif($menu, $halaman) {
    return $menu === $halaman ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Penjualan | PHP Native</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>

<nav class="navbar">
    <div class="navbar-inner">

        <a href="/" class="brand">
            <span class="brand-icon">S</span>
            <span>
                <strong>Sistem Penjualan</strong>
                <small>PHP Native</small>
            </span>
        </a>

        <div class="nav-menu">
            <a href="/" class="<?= menuAktif('dashboard', $halaman) ?>">
                Dashboard
            </a>

            <a href="/produk/"
               class="<?= menuAktif('produk', $halaman) ?>">
                Produk
            </a>

            <a href="/pelanggan/"
               class="<?= menuAktif('pelanggan', $halaman) ?>">
                Pelanggan
            </a>

            <a href="/supplier/"
               class="<?= menuAktif('supplier', $halaman) ?>">
                Supplier
            </a>

            <a href="/penjualan/"
               class="<?= menuAktif('penjualan', $halaman) ?>">
                Penjualan
            </a>

            <a href="/pembelian/"
               class="<?= menuAktif('pembelian', $halaman) ?>">
                Pembelian
            </a>

            <a href="/laporan/"
                class="<?= menuAktif('laporan', $halaman) ?>">
                Laporan
            </a>

            <a href="/transaksi/"
                 class="<?= menuAktif('transaksi', $halaman) ?>">
                  Transaksi
            </a>
        </div>

    </div>
</nav>

<main class="container">
