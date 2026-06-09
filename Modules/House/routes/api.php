<?php

use Illuminate\Support\Facades\Route;
use Modules\House\Http\Controllers\HouseController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('houses', HouseController::class)->names('house');
});
