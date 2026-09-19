<?php

use App\Http\Controllers\Api\V1\TrainingController;
use Illuminate\Support\Facades\Route;

Route::prefix('trainings')->group(function () {
    Route::get('/', [TrainingController::class, 'index']);
    Route::post('/', [TrainingController::class, 'store']);
    Route::get('/{training}', [TrainingController::class, 'show']);
    Route::put('/{training}', [TrainingController::class, 'update']);
    Route::delete('/{training}', [TrainingController::class, 'destroy']);
});