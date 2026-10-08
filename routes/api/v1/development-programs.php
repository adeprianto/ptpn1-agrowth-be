<?php

use App\Http\Controllers\Api\V1\DevelopmentProgramController;
use App\Http\Controllers\Api\V1\ProgramBatchController;
use Illuminate\Support\Facades\Route;

// Program pengembangan berjenjang (PLDP): program -> angkatan -> peserta.
Route::get('/development-programs', [DevelopmentProgramController::class, 'index']);

Route::prefix('program-batches')->group(function () {
    Route::get('/', [ProgramBatchController::class, 'index']);
    Route::post('/', [ProgramBatchController::class, 'store']);
    // Harus di atas `/{programBatch}`, kalau tidak "options" ikut dibaca sebagai id.
    Route::get('/options', [ProgramBatchController::class, 'options']);
    Route::get('/{programBatch}', [ProgramBatchController::class, 'show']);
    Route::put('/{programBatch}', [ProgramBatchController::class, 'update']);
    Route::delete('/{programBatch}', [ProgramBatchController::class, 'destroy']);
});
