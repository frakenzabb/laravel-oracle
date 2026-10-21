<?php

use App\Http\Controllers\BarangController;
use App\Http\Controllers\FakturPembelianController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BarangController::class, 'index'])->name('barang.index');
Route::post('/mutasi', [BarangController::class, 'store'])->name('barang.store');
Route::delete('/mutasi/{id}', [BarangController::class, 'destroy'])->name('barang.destroy');

Route::get('/faktur-pembelian', [FakturPembelianController::class, 'index'])
    ->name('faktur-pembelian.index');
Route::post('/faktur-pembelian', [FakturPembelianController::class, 'store'])
    ->name('faktur-pembelian.store');
Route::get('/faktur-pembelian/{id}', [FakturPembelianController::class, 'show'])
    ->whereNumber('id')
    ->name('faktur-pembelian.show');
