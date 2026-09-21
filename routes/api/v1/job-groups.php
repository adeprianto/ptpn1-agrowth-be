<?php

use App\Http\Controllers\Api\V1\JobGroupController;
use Illuminate\Support\Facades\Route;

Route::prefix('job-groups')->group(function () {
    Route::get('/', [JobGroupController::class, 'index']);
    Route::post('/', [JobGroupController::class, 'store']);
    Route::get('/{jobGroup}', [JobGroupController::class, 'show']);
    Route::put('/{jobGroup}', [JobGroupController::class, 'update']);
    Route::delete('/{jobGroup}', [JobGroupController::class, 'destroy']);
});