<?php

use App\Http\Controllers\API\TrainingController;
use App\Http\Controllers\API\VendorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('/penyelenggara-pelatihan')->group(function () {
    Route::get('/', [VendorController::class, 'index']);
    Route::get('/{id}', [VendorController::class, 'show']);
    Route::post('/', [VendorController::class, 'store']);
    Route::put('/{id}', [VendorController::class, 'update']);
    Route::delete('/{id}', [VendorController::class, 'destroy']);
});

Route::prefix('/program-pelatihan')->group(function () {
    Route::get('/', [TrainingController::class, 'index']);
    Route::get('/{id}', [TrainingController::class, 'show']);
    Route::post('/', [TrainingController::class, 'store']);
    Route::put('/{id}', [TrainingController::class, 'update']);
    Route::delete('/{id}', [TrainingController::class, 'destroy']);
});
