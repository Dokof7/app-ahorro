<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\SavingsApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/login', [ApiAuthController::class, 'login']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $r) => $r->user());
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardApiController::class, 'index']);
    Route::get('/savings', [SavingsApiController::class, 'index']);
});
