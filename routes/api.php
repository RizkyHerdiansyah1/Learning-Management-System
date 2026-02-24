<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\KelasController;
use App\Http\Controllers\Api\MateriController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Journey Learn LMS
|--------------------------------------------------------------------------
|
| Semua route di sini diakses dengan prefix /api
| Contoh: POST /api/login
|
*/

// ──────────────────────────────────────────────
// PUBLIC ROUTES (Tidak perlu login)
// ──────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// ──────────────────────────────────────────────
// PROTECTED ROUTES (Perlu token)
// ──────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Kelas
    Route::get('/kelas', [KelasController::class, 'index']);
    Route::get('/kelas-saya', [KelasController::class, 'kelasSaya']);
    Route::get('/kelas/{id}', [KelasController::class, 'show']);
    Route::post('/kelas/{id}/enroll', [KelasController::class, 'enroll']);

    // Materi
    Route::get('/materi/{id}', [MateriController::class, 'show']);
    Route::post('/materi/{id}/complete', [MateriController::class, 'complete']);
    Route::post('/materi/{id}/submit-quiz', [MateriController::class, 'submitQuiz']);
});
