<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ApiController::class, 'index']);
Route::get('/bukti/{rekomendasi}', [ApiController::class, 'downloadBukti'])->name('bukti.show');

// dashboard
Route::get('/countunit', [ApiController::class, 'countUnit']);
Route::get('/counttemuan', [ApiController::class, 'countTemuan']);
Route::get('/countrekomendasi', [ApiController::class, 'countRekomendasi']);
Route::get('/countvalidasi', [ApiController::class, 'countValidasi']);

// data
Route::get('/getunit', [ApiController::class, 'getUnit']);
Route::get('/getbidang', [ApiController::class, 'getBidang']);
// temuan
Route::post('/inputtemuan', [ApiController::class, 'inputTemuan']);
Route::post('/gettemuan', [ApiController::class, 'getTemuan']);
Route::post('/deletetemuan', [ApiController::class, 'deleteTemuan']);
Route::post('/updatetemuan', [ApiController::class, 'updateTemuan']);
Route::post('/kirimtemuan', [ApiController::class, 'kirimTemuan']);
Route::post('/prosestemuan', [ApiController::class, 'prosesTemuan']);

// validasi
Route::post('/gettemuanvalidasi', [ApiController::class, 'getTemuanValidasi']);
Route::post('/validasitemuan', [ApiController::class, 'validasiTemuan']);
Route::post('/unitcekvalidasi', [ApiController::class, 'unitCekValidasi']);

// rekomendasi
Route::post('/deleterekomendasi', [ApiController::class, 'deleteRekomendasi']);

// history
Route::post('/gettemuanhistory', [ApiController::class, 'getTemuanHistory']);

// tindaklanjut
Route::post('/inputtindaklanjut', [ApiController::class, 'inputTindakLanjut']);

// notifikasi
Route::post('/getnotifikasi', [ApiController::class, 'getNotifikasi']);
Route::post('/readnotifikasi', [ApiController::class, 'readNotifikasi']);
Route::post('/readallnotifikasi', [ApiController::class, 'readAllNotifikasi']);
