<?php

use App\Http\Controllers\Api\V1\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::prefix('employees')->group(function () {
    Route::get('/', [EmployeeController::class, 'index']);
    Route::get('/summary', [EmployeeController::class, 'summary']); // buat 4 card di atas tabel
    Route::get('/{employee}', [EmployeeController::class, 'show']);
});
