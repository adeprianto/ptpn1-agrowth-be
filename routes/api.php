<?php

use Illuminate\Support\Facades\Route;

// Tanpa middleware 'web': Sanctum statefulApi() sudah memasang EncryptCookies,
// StartSession, dan ValidateCsrfToken sendiri. Kalau 'web' ikut dipasang,
// StartSession jalan dua kali dan sesi login hilang di request berikutnya.
Route::prefix('v1')->group(function () {

    // route publik login
    require __DIR__.'/api/v1/auth.php';

    Route::middleware('auth:sanctum')->group(function () {
        require __DIR__.'/api/v1/entities.php';
        require __DIR__.'/api/v1/regionals.php';
        require __DIR__.'/api/v1/head-office.php';
        require __DIR__.'/api/v1/master-data.php';
        require __DIR__.'/api/v1/entity-operationals.php';
        require __DIR__.'/api/v1/job-groups.php';
        require __DIR__.'/api/v1/job-functions.php';
        require __DIR__.'/api/v1/organization-types.php';
        require __DIR__.'/api/v1/organizations.php';
        require __DIR__.'/api/v1/position-titles.php';
        require __DIR__.'/api/v1/units.php';
        require __DIR__.'/api/v1/employees.php';
        require __DIR__.'/api/v1/vendors.php';
        require __DIR__.'/api/v1/trainings.php';
        require __DIR__.'/api/v1/training-realizations.php';
    });
});
