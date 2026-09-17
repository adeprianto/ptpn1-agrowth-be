<?php

use App\Http\Controllers\Api\V1\JobFunctionController;
use Illuminate\Support\Facades\Route;

Route::prefix('job-functions')->group(function () {
    Route::get('/', [JobFunctionController::class, 'index']);
    Route::post('/', [JobFunctionController::class, 'store']);
    Route::get('/{jobFunction}', [JobFunctionController::class, 'show']);
    Route::put('/{jobFunction}', [JobFunctionController::class, 'update']);
    Route::delete('/{jobFunction}', [JobFunctionController::class, 'destroy']);
});