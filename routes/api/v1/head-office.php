<?php

use App\Http\Controllers\Api\V1\HeadOfficeController;
use Illuminate\Support\Facades\Route;

Route::get('head-office', [HeadOfficeController::class, 'show']);
