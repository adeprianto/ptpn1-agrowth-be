<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('web')->group(function () {

    // route publik login
    require __DIR__.'/api/v1/auth.php';

    Route::middleware('auth:sanctum')->group(function () {
        require __DIR__.'/api/v1/entities.php';
        require __DIR__.'/api/v1/master-data.php';
        require __DIR__.'/api/v1/entity-operationals.php';
        require __DIR__.'/api/v1/job-families.php';
        require __DIR__.'/api/v1/job-functions.php';
        require __DIR__.'/api/v1/organization-types.php';
        require __DIR__.'/api/v1/organizations.php';
        require __DIR__.'/api/v1/position-titles.php';
        // require __DIR__.'/api/v1/units.php';
    });
});