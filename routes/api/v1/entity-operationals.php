<?php

use App\Http\Controllers\Api\V1\CommodityController;
use App\Http\Controllers\Api\V1\EntityOperationalController;
use Illuminate\Support\Facades\Route;

Route::apiResource('entity-operationals', EntityOperationalController::class);

Route::get('entity-operationals/{entityOperational}/commodity', [CommodityController::class, 'show']);
Route::put('entity-operationals/{entityOperational}/commodity', [CommodityController::class, 'upsert']);
Route::delete('entity-operationals/{entityOperational}/commodity', [CommodityController::class, 'destroy']);