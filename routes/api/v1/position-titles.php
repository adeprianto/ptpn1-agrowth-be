<?php

use App\Http\Controllers\Api\V1\PositionTitleController;
use Illuminate\Support\Facades\Route;

Route::prefix('position-titles')->group(function () {
    Route::get('/', [PositionTitleController::class, 'index']);
    Route::post('/', [PositionTitleController::class, 'store']);
    Route::get('/{positionTitle}', [PositionTitleController::class, 'show']);
    Route::put('/{positionTitle}', [PositionTitleController::class, 'update']);
    Route::delete('/{positionTitle}', [PositionTitleController::class, 'destroy']);
});