<?php

use App\Http\Controllers\Api\V1\UnitController;
use Illuminate\Support\Facades\Route;

Route::prefix('units')->group(function () {
    Route::get('/', [UnitController::class, 'index']);
    Route::get('/summary', [UnitController::class, 'summary']); // buat card di atas tabel
    Route::post('/', [UnitController::class, 'store']);
    Route::get('/{unit}', [UnitController::class, 'show'])->middleware('entity.access:unit');
    Route::match(['put', 'patch'], '/{unit}', [UnitController::class, 'update'])->middleware('entity.access:unit');
    Route::delete('/{unit}', [UnitController::class, 'destroy'])->middleware('entity.access:unit');
});
