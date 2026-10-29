<?php

use App\Http\Controllers\BarangController;
use App\Http\Controllers\FakturPembelianController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FakturPembelianController::class, 'index'])->name('barang.index');
Route::post('/mutasi', [BarangController::class, 'store'])->name('barang.store');
Route::delete('/mutasi/{id}', [BarangController::class, 'destroy'])->name('barang.destroy');

Route::redirect('/faktur-pembelian', '/');
Route::get('/purchase-orders/{id}/details', [FakturPembelianController::class, 'purchaseOrderDetails'])
    ->whereNumber('id')
    ->name('purchase-orders.details');
Route::post('/penerimaan-barang', [FakturPembelianController::class, 'store'])
    ->name('penerimaan-barang.store');
