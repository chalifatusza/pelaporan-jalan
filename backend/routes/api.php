<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\MapController;
use App\Http\Middleware\ApiAuthenticate;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// OAuth2 Social Login Routes (Public)
Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);
Route::get('/auth/github/redirect', [AuthController::class, 'githubRedirect']);
Route::get('/auth/github/callback', [AuthController::class, 'githubCallback']);

// Authenticated Routes (JWT / Basic / API-Key Authenticated)
Route::middleware(ApiAuthenticate::class)->group(function () {
    // Auth & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'session']);
    Route::get('/user/profile', [AuthController::class, 'profile']);
    Route::put('/user', [AuthController::class, 'updateProfile']);
    Route::post('/user/api-key', [AuthController::class, 'generateApiKey']);

    // Category (Kategori) CRUD
    Route::apiResource('kategori', KategoriController::class);

    // Reports CRUD
    Route::apiResource('laporan', ReportController::class);
    Route::apiResource('reports', ReportController::class);

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
