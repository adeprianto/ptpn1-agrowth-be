<?php

use App\Http\Controllers\Api\V1\OrganizationTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('organization-types')->group(function () {
    Route::get('/', [OrganizationTypeController::class, 'index']);
    Route::post('/', [OrganizationTypeController::class, 'store']);
    Route::get('/{organizationType}', [OrganizationTypeController::class, 'show']);
    Route::put('/{organizationType}', [OrganizationTypeController::class, 'update']);
    Route::delete('/{organizationType}', [OrganizationTypeController::class, 'destroy']);
});