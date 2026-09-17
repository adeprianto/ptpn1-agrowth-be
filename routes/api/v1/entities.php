<?php

use App\Http\Controllers\Api\V1\EntityController;
use Illuminate\Support\Facades\Route;

Route::get('entities/tree', [EntityController::class, 'tree']);
Route::apiResource('entities', EntityController::class);