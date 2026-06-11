<?php

use Illuminate\Support\Facades\Route;
use Modules\House\Http\Controllers\HouseController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::post('houses/{id}/assign', [HouseController::class, 'assignResident']);
    Route::post('houses/{id}/remove', [HouseController::class, 'removeResident']);
    Route::get('houses/{id}/history', [HouseController::class, 'occupancyHistories']);
    Route::apiResource('houses', HouseController::class)->names('house');
});
