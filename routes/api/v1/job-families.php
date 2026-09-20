<?php

use App\Http\Controllers\Api\V1\JobFamilyController;
use Illuminate\Support\Facades\Route;

Route::prefix('job-families')->group(function () {
    Route::get('/', [JobFamilyController::class, 'index']);
    Route::post('/', [JobFamilyController::class, 'store']);
    Route::get('/{jobFamily}', [JobFamilyController::class, 'show']);
    Route::put('/{jobFamily}', [JobFamilyController::class, 'update']);
    Route::delete('/{jobFamily}', [JobFamilyController::class, 'destroy']);
});