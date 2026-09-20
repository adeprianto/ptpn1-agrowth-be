<?php

use App\Http\Controllers\Api\V1\OrganizerController;
use Illuminate\Support\Facades\Route;

Route::prefix('organizers')->group(function () {
    Route::get('/', [OrganizerController::class, 'index']);
    Route::post('/', [OrganizerController::class, 'store']);
    Route::get('/{organizer}', [OrganizerController::class, 'show']);
    Route::put('/{organizer}', [OrganizerController::class, 'update']);
    Route::delete('/{organizer}', [OrganizerController::class, 'destroy']);
});