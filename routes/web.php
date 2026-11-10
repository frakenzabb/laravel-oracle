<?php

use App\Http\Controllers\BarangController;
use App\Http\Controllers\BonKeluarController;
use App\Http\Controllers\FakturPembelianController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FakturPembelianController::class, 'index'])->name('barang.index');
Route::get('/penerimaan-barang/input', [FakturPembelianController::class, 'index'])
    ->name('penerimaan-barang.input');
Route::post('/mutasi', [BarangController::class, 'store'])->name('barang.store');
Route::delete('/mutasi/{id}', [BarangController::class, 'destroy'])->name('barang.destroy');

Route::redirect('/faktur-pembelian', '/');
Route::get('/purchase-orders/{id}/details', [FakturPembelianController::class, 'purchaseOrderDetails'])
    ->whereNumber('id')
    ->name('purchase-orders.details');
Route::get('/suppliers/{supplierId}/receipt-history', [FakturPembelianController::class, 'supplierReceiptHistory'])
    ->whereNumber('supplierId')
    ->name('suppliers.receipt-history');
Route::post('/penerimaan-barang', [FakturPembelianController::class, 'store'])
    ->name('penerimaan-barang.store');
Route::get('/barang-keluar', [BonKeluarController::class, 'index'])
    ->name('bon-keluar.index');
Route::post('/barang-keluar', [BonKeluarController::class, 'store'])
    ->name('bon-keluar.store');
