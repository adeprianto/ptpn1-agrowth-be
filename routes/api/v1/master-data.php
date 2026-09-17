<?php

use App\Http\Controllers\Api\V1\BusinessTypeController;
use App\Http\Controllers\Api\V1\OperationalCategoryController;
use Illuminate\Support\Facades\Route;

Route::apiResource('business-types', BusinessTypeController::class);
Route::apiResource('operational-categories', OperationalCategoryController::class);