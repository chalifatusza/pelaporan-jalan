<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\MapController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Authenticated Routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Auth & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'session']);
    Route::get('/user/profile', [AuthController::class, 'profile']);
    Route::put('/user', [AuthController::class, 'updateProfile']);

    // Reports CRUD
    Route::apiResource('laporan', LaporanController::class);

    // Maps Coordinate Feed
    Route::get('/laporan-map', [MapController::class, 'getMapLaporan']);

    // Stats endpoints
    Route::get('/stats', [StatsController::class, 'getGeneralStats']);
    Route::get('/stats/user', [StatsController::class, 'getUserStats']);
    Route::get('/stats/status', [StatsController::class, 'getStatusStats']);
    Route::get('/stats/kerusakan', [StatsController::class, 'getKerusakanStats']);
    Route::get('/stats/kecamatan', [StatsController::class, 'getKecamatanStats']);

    // Admin endpoints
    Route::get('/admin/users', [AdminController::class, 'getUsers']);
    Route::put('/admin/users/{id}/role', [AdminController::class, 'updateUserRole']);
    Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser']);
    Route::get('/admin/laporan', [AdminController::class, 'getLaporanAdmin']);
    Route::put('/admin/laporan/{id}/status', [AdminController::class, 'updateStatus']);
});

