<?php

use App\Http\Controllers\Api\V1\TrainingRealizationController;
use App\Http\Controllers\Api\V1\TrainingRealizationDetailController;
use Illuminate\Support\Facades\Route;

Route::prefix('training-realizations')->group(function () {
    Route::get('/', [TrainingRealizationController::class, 'index']);
    Route::post('/', [TrainingRealizationController::class, 'store']);
    Route::get('/{trainingRealization}', [TrainingRealizationController::class, 'show']);
    Route::put('/{trainingRealization}', [TrainingRealizationController::class, 'update']);
    Route::delete('/{trainingRealization}', [TrainingRealizationController::class, 'destroy']);

    // peserta realisasi
    Route::get('/{trainingRealization}/details', [TrainingRealizationDetailController::class, 'index']);
    Route::post('/{trainingRealization}/details', [TrainingRealizationDetailController::class, 'store']);
    Route::put('/{trainingRealization}/details/{detail}', [TrainingRealizationDetailController::class, 'update']);
    Route::delete('/{trainingRealization}/details/{detail}', [TrainingRealizationDetailController::class, 'destroy']);
});