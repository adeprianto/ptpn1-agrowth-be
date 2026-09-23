<?php

use App\Http\Controllers\Api\V1\TrainingController;
use Illuminate\Support\Facades\Route;

Route::prefix('trainings')->group(function () {
    Route::get('/', [TrainingController::class, 'index']);
    // Harus di atas `/{training}`, kalau tidak "tags" ikut dibaca sebagai id.
    Route::get('/tags', [TrainingController::class, 'tags']);
    Route::post('/', [TrainingController::class, 'store']);
    Route::get('/{training}', [TrainingController::class, 'show']);
    Route::put('/{training}', [TrainingController::class, 'update']);
    Route::delete('/{training}', [TrainingController::class, 'destroy']);
});
