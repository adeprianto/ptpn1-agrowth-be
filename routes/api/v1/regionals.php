<?php

use App\Http\Controllers\Api\V1\RegionalController;
use Illuminate\Support\Facades\Route;

Route::prefix('regionals')->group(function () {
    Route::get('/', [RegionalController::class, 'index']);
    Route::get('/summary', [RegionalController::class, 'summary']); // buat 3 card di atas tabel
    Route::post('/', [RegionalController::class, 'store']);
    Route::get('/{regional}', [RegionalController::class, 'show'])->middleware('entity.access:regional');
    Route::get('/{regional}/units', [RegionalController::class, 'units'])->middleware('entity.access:regional');
    Route::match(['put', 'patch'], '/{regional}', [RegionalController::class, 'update'])->middleware('entity.access:regional');
    Route::delete('/{regional}', [RegionalController::class, 'destroy'])->middleware('entity.access:regional');
});
