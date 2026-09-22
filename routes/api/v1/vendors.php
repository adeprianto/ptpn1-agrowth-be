<?php

use App\Http\Controllers\Api\V1\VendorController;
use Illuminate\Support\Facades\Route;

Route::prefix('vendors')->group(function () {
    Route::get('/', [VendorController::class, 'index']);
    Route::post('/', [VendorController::class, 'store']);
    Route::get('/{vendor}', [VendorController::class, 'show']);
    Route::put('/{vendor}', [VendorController::class, 'update']);
    Route::delete('/{vendor}', [VendorController::class, 'destroy']);
});
