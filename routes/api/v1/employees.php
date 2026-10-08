<?php

use App\Http\Controllers\Api\V1\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::prefix('employees')->group(function () {
    Route::get('/', [EmployeeController::class, 'index']);
    Route::get('/summary', [EmployeeController::class, 'summary']); // buat 4 card di atas tabel
    // harus di atas /{employee}, kalau tidak "filter-options" ditangkap sebagai id
    Route::get('/filter-options', [EmployeeController::class, 'filterOptions']); // isi daftar centang di modal filter
    Route::get('/random-sample', [EmployeeController::class, 'randomSample']); // peserta acak per level BOD (Program Budaya)
    Route::get('/{employee}', [EmployeeController::class, 'show']);
});
