<?php

use App\Http\Controllers\API\TrainingController;
use App\Http\Controllers\API\VendorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Tanpa middleware 'web': Sanctum statefulApi() sudah memasang EncryptCookies,
// StartSession, dan ValidateCsrfToken sendiri. Kalau 'web' ikut dipasang,
// StartSession jalan dua kali dan sesi login hilang di request berikutnya.
Route::prefix('v1')->group(function () {

Route::prefix('/penyelenggara-pelatihan')->group(function () {
    Route::get('/', [VendorController::class, 'index']);
    Route::get('/{id}', [VendorController::class, 'show']);
    Route::post('/', [VendorController::class, 'store']);
    Route::put('/{id}', [VendorController::class, 'update']);
    Route::delete('/{id}', [VendorController::class, 'destroy']);
});

Route::prefix('/program-pelatihan')->group(function () {
    Route::get('/', [TrainingController::class, 'index']);
    Route::get('/{id}', [TrainingController::class, 'show']);
    Route::post('/', [TrainingController::class, 'store']);
    Route::put('/{id}', [TrainingController::class, 'update']);
    Route::delete('/{id}', [TrainingController::class, 'destroy']);
});

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
        require __DIR__.'/api/v1/organizers.php';
        require __DIR__.'/api/v1/trainings.php';
        require __DIR__.'/api/v1/training-realizations.php';
    });
});
