<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    // Rate limiting strict pour protéger contre les attaques par force brute
    Route::post('/login', [AuthController::class, 'loginApi'])
        ->middleware('throttle:5,1'); // 5 tentatives par minute
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logoutApi']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Rate limiting général pour les endpoints authentifiés
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::apiResource('roles', RoleController::class);
    
    Route::apiResource('users', UserController::class);
    Route::post('/users/{user}/roles', [UserController::class, 'assignRole']);
    Route::delete('/users/{user}/roles', [UserController::class, 'removeRole']);
    Route::put('/users/{user}/roles', [UserController::class, 'syncRoles']);
    
    Route::get('/vehicles/available', [VehicleController::class, 'available']);
    Route::apiResource('vehicles', VehicleController::class);
    Route::patch('/vehicles/{vehicle}/status', [VehicleController::class, 'updateStatus']);
    
    Route::get('/reservations/available-vehicles', [ReservationController::class, 'availableVehicles']);
    Route::apiResource('reservations', ReservationController::class);
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
    Route::post('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm']);
    Route::post('/reservations/{reservation}/complete', [ReservationController::class, 'complete']);
    Route::get('/users/{user}/reservations', [ReservationController::class, 'byUser']);
    Route::get('/vehicles/{vehicle}/reservations', [ReservationController::class, 'byVehicle']);
    
    // Export/Import
    Route::get('/export/reservations', [ExportController::class, 'exportReservations']);
    Route::post('/import/vehicles', [ImportController::class, 'importVehicles']);
    
    // Statistics
    Route::get('/statistics/general', [StatisticsController::class, 'general']);
    Route::get('/statistics/reservations-by-month', [StatisticsController::class, 'reservationsByMonth']);
    Route::get('/statistics/most-used-vehicles', [StatisticsController::class, 'mostUsedVehicles']);
    Route::get('/statistics/vehicle-occupancy', [StatisticsController::class, 'vehicleOccupancy']);
});
