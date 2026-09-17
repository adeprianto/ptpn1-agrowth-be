<?php

use App\Http\Controllers\Api\V1\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::prefix('organizations')->group(function () {
    Route::get('/', [OrganizationController::class, 'index']);
    Route::get('/tree', [OrganizationController::class, 'tree']); // harus di atas /{organization}
    Route::post('/', [OrganizationController::class, 'store']);
    Route::get('/{organization}', [OrganizationController::class, 'show']);
    Route::put('/{organization}', [OrganizationController::class, 'update']);
    Route::delete('/{organization}', [OrganizationController::class, 'destroy']);
});