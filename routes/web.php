<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ReservationController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\VehicleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Index');
})->name('index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    
    Route::prefix('admin')->middleware('can:access-admin')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Admin/Index');
        })->name('admin.index');
        
        // Routes pour la gestion des utilisateurs
        Route::resource('users', UserController::class)->names([
            'index' => 'admin.users.index',
            'create' => 'admin.users.create',
            'store' => 'admin.users.store',
            'show' => 'admin.users.show',
            'edit' => 'admin.users.edit',
            'update' => 'admin.users.update',
            'destroy' => 'admin.users.destroy',
        ]);
        
        Route::post('/users/{user}/regenerate-password', [UserController::class, 'regeneratePassword'])->name('admin.users.regenerate-password');
        
        // Routes pour la gestion des rôles des utilisateurs
        Route::post('/users/{user}/roles', [UserController::class, 'assignRole'])->name('admin.users.roles.assign');
        Route::post('/users/{user}/roles/remove', [UserController::class, 'removeRole'])->name('admin.users.roles.remove');
        
        // Routes pour la gestion des véhicules
        Route::resource('vehicles', VehicleController::class)->names([
            'index' => 'admin.vehicles.index',
            'create' => 'admin.vehicles.create',
            'store' => 'admin.vehicles.store',
            'show' => 'admin.vehicles.show',
            'edit' => 'admin.vehicles.edit',
            'update' => 'admin.vehicles.update',
            'destroy' => 'admin.vehicles.destroy',
        ]);
        
        Route::post('/vehicles/{vehicle}/status', [VehicleController::class, 'updateStatus'])->name('admin.vehicles.update-status');
        
        // Routes pour la gestion des réservations
        Route::resource('reservations', ReservationController::class)->names([
            'index' => 'admin.reservations.index',
            'create' => 'admin.reservations.create',
            'store' => 'admin.reservations.store',
            'show' => 'admin.reservations.show',
            'edit' => 'admin.reservations.edit',
            'update' => 'admin.reservations.update',
            'destroy' => 'admin.reservations.destroy',
        ]);
        
        Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('admin.reservations.cancel');
        Route::post('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])->name('admin.reservations.confirm');
        Route::post('/reservations/{reservation}/complete', [ReservationController::class, 'complete'])->name('admin.reservations.complete');
    });
});
