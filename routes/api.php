<?php

use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\DashboardSummaryApiController;
use App\Http\Controllers\Api\ReportApiController;
use App\Http\Controllers\Api\SavingsApiController;
use App\Http\Controllers\MeetingScheduledDateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/login', [ApiAuthController::class, 'login']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $r) => $r->user()->append('role'));
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardApiController::class, 'index']);
    Route::get('/dashboard/last-meeting', [DashboardSummaryApiController::class, 'lastMeeting']);
    Route::get('/savings', [SavingsApiController::class, 'index']);
    Route::get('/meeting-scheduled-dates', [MeetingScheduledDateController::class, 'index']);
    Route::get('/meeting-scheduled-dates/next', [MeetingScheduledDateController::class, 'next']);
    Route::get('/reports/groups-comparison', [ReportApiController::class, 'groupsComparison']);
    Route::get('/reports/groups/{group}/summary', [ReportApiController::class, 'groupSummary']);
    Route::get('/admin/groups', [AdminApiController::class, 'groups']);
    Route::get('/admin/groups/{group}/members', [AdminApiController::class, 'members']);
});
