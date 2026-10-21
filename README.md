# Manajemen Gudang Barang

Aplikasi Laravel untuk mencatat transaksi barang masuk dan keluar, melihat stok bersih per barang, meninjau riwayat transaksi, dan mengelola faktur pembelian menggunakan Oracle Database.

## Menjalankan aplikasi

1. Atur koneksi Oracle pada file `.env` (`DB_CONNECTION=oracle`, `DB_HOST`, `DB_PORT`, `DB_SERVICE_NAME`, `DB_USERNAME`, dan `DB_PASSWORD`).
2. Jalankan migrasi untuk membuat tabel transaksi, faktur pembelian, dan sequence Oracle:

   ```bash
   php artisan migrate --database=oracle
   ```

3. Jalankan server Laravel:

   ```bash
   php artisan serve
   ```

Halaman manajemen gudang tersedia di `/`. Transaksi keluar ditolak jika jumlahnya melebihi stok yang tersedia. Tes dapat dijalankan dengan `php artisan test`.

Halaman `/faktur-pembelian` digunakan untuk mencatat dan mencetak faktur pembelian. Setiap detail barang yang disimpan otomatis dicatat sebagai barang masuk. Faktur menyimpan kode, nama barang, satuan, dan jumlah tanpa informasi harga atau total.
