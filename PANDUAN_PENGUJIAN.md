# Panduan Pengujian Sistem Penjualan PHP Native

## Fitur yang dipertahankan sesuai modul

1. CRUD Produk: tampil, tambah, edit, hapus, dan pencarian berdasarkan nama.
2. JOIN Transaksi Penjualan: nomor penjualan, tanggal, nama pelanggan, dan total transaksi.
3. Agregasi Penjualan per Pelanggan: `COUNT()`, `SUM()`, `GROUP BY`, dan `JOIN`.
4. Laporan Penjualan per Produk: tabel `produk` dan `detil_penjualan` dengan fungsi agregasi.
5. Transaksi Penjualan: satu header dan minimal dua baris detail, disimpan memakai PDO transaction.

Menu aplikasi disederhanakan menjadi Dashboard, Produk, Transaksi Penjualan, dan Laporan.
Data pelanggan tetap digunakan sebagai data referensi untuk transaksi. Tabel pembelian dan supplier yang mungkin masih ada di database tidak dihapus.

## Cara menjalankan

1. Pastikan PostgreSQL aktif dan database `db_kelompok3` tersedia.
2. Pastikan pengaturan koneksi di `config/database.php` sesuai dengan komputer.
3. Jalankan server dari folder proyek:

   ```powershell
   php -S localhost:8000
   ```

4. Buka `http://localhost:8000`.

## Alur COMMIT otomatis

1. Buka menu Transaksi Penjualan.
2. Klik Input Penjualan.
3. Isi nomor, tanggal, pelanggan, dan minimal dua baris produk.
4. Klik Simpan Transaksi Penjualan.
5. Jika semua proses berhasil, aplikasi menjalankan `commit()` otomatis.
6. Pesan berhasil akan menyebutkan bahwa transaksi disimpan permanen (COMMIT).
7. Stok setiap produk akan berkurang sesuai jumlah yang dijual.

## Pengujian ROLLBACK pada database pengujian

Jangan lakukan skenario gagal pada database utama. Buat salinan database terlebih dahulu.

1. Tutup aplikasi yang sedang terhubung ke `db_kelompok3`.
2. Di pgAdmin, buka Query Tool pada database `postgres`, lalu jalankan:

   ```sql
   CREATE DATABASE db_kelompok3_test
   WITH TEMPLATE db_kelompok3
   OWNER postgres;
   ```

   Jika PostgreSQL menyatakan database sumber sedang digunakan, tutup koneksi aktif ke `db_kelompok3`, lalu ulangi.

3. Buka `config/database.php`, ubah nilai `$dbname` dari `db_kelompok3` menjadi `db_kelompok3_test` untuk sementara.
4. Jalankan aplikasi kembali.
5. Uji kondisi berhasil dengan dua produk valid. Pastikan satu header dan dua detail tersimpan serta stok berkurang.
6. Untuk menguji kegagalan setelah header dan detail pertama sempat dimasukkan:
   - Isi transaksi dengan nomor penjualan baru dan dua baris produk.
   - Pilih produk valid pada kedua baris dan isi jumlah yang tersedia.
   - Sebelum mengirim form, buka Developer Tools browser (F12), pilih tab Console, lalu jalankan:

   ```javascript
   const selects = document.querySelectorAll('select[name="produk[]"]');
   selects[1].add(new Option('Produk tidak tersedia', 'KODE_TIDAK_ADA'));
   selects[1].value = 'KODE_TIDAK_ADA';
   ```

   - Kirim form.
   - Baris pertama akan sempat diproses, sedangkan produk pada detail kedua tidak ditemukan. Aplikasi harus menjalankan `rollBack()` otomatis.

7. Periksa database `db_kelompok3_test`:
   - Nomor transaksi yang gagal tidak boleh ada di tabel `penjualan`.
   - Tidak boleh ada detail dengan nomor transaksi tersebut di `detil_penjualan`.
   - Stok produk valid pada baris pertama harus kembali ke jumlah sebelum pengujian.

8. Setelah selesai, kembalikan `$dbname` di `config/database.php` ke `db_kelompok3`.

## Catatan

- Validasi format, jumlah, pelanggan, produk, dan stok dilakukan pada saat proses transaksi. Jika gagal setelah `beginTransaction()`, blok `catch` menjalankan `rollBack()` selama transaksi masih aktif.
- Jangan menampilkan pilihan COMMIT/ROLLBACK kepada pengguna. Keduanya berjalan otomatis.
- Tabel `pelanggan` tetap diperlukan karena transaksi penjualan mempunyai foreign key ke pelanggan.
- Jangan menghapus tabel database `supplier`, `pembelian`, atau `detil_pembelian` hanya karena menu aplikasinya tidak digunakan pada tugas ini.
- Jangan mengunggah `config/database.php` yang berisi kata sandi database ke repositori publik.


## Melihat JOIN dan Detail Penjualan
1. Buka menu **Transaksi Penjualan** untuk melihat daftar transaksi. Daftar ini menggunakan `INNER JOIN` antara `penjualan` dan `pelanggan`.
2. Klik tombol **Detail** pada salah satu transaksi. Halaman detail menampilkan informasi header transaksi/pelanggan dan daftar barang.
3. Rincian barang menggunakan `INNER JOIN` antara `detil_penjualan` dan `produk`, berdasarkan `kode_produk`. Pastikan satu transaksi yang diuji memiliki minimal dua baris detail barang.
