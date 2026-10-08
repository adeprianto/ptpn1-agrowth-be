<?php

use App\Http\Controllers\Api\V1\CompetencyController;
use App\Http\Controllers\Api\V1\CompetencyGroupController;
use Illuminate\Support\Facades\Route;

Route::prefix('competency-groups')->group(function () {
    Route::get('/', [CompetencyGroupController::class, 'index']);
    Route::post('/', [CompetencyGroupController::class, 'store']);
    Route::put('/{competencyGroup}', [CompetencyGroupController::class, 'update']);
    Route::delete('/{competencyGroup}', [CompetencyGroupController::class, 'destroy']);
});

Route::prefix('competencies')->group(function () {
    Route::get('/', [CompetencyController::class, 'index']);
    Route::post('/', [CompetencyController::class, 'store']);
    Route::get('/{competency}', [CompetencyController::class, 'show']);
    Route::put('/{competency}', [CompetencyController::class, 'update']);
    Route::delete('/{competency}', [CompetencyController::class, 'destroy']);
});
