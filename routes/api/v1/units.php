<?php

use App\Http\Controllers\Api\V1\UnitController;
use Illuminate\Support\Facades\Route;

Route::prefix('units')->group(function () {
    Route::get('/', [UnitController::class, 'index']);
    Route::get('/summary', [UnitController::class, 'summary']); // buat 4 card di atas tabel
});
