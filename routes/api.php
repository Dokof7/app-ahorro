<?php

use App\Http\Controllers\Api\ActivityApiController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\AttendanceApiController;
use App\Http\Controllers\Api\ContributionApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\DashboardSummaryApiController;
use App\Http\Controllers\Api\LoanPaymentApiController;
use App\Http\Controllers\Api\MeetingWriteApiController;
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

    // Mobile write API (open meeting)
    Route::get('/meetings/open', [MeetingWriteApiController::class, 'open']);
    Route::post('/groups/{group}/meetings', [MeetingWriteApiController::class, 'store']);
    Route::post('/meetings/{meeting}/contributions/bulk', [ContributionApiController::class, 'bulkStore']);
    Route::put('/meetings/{meeting}/attendance/bulk', [AttendanceApiController::class, 'bulkUpdate']);
    Route::post('/loans/{loan}/payments', [LoanPaymentApiController::class, 'store']);
    Route::get('/groups/{group}/activities', [ActivityApiController::class, 'index']);
    Route::post('/groups/{group}/activities', [ActivityApiController::class, 'store']);
    Route::put('/activities/{activity}', [ActivityApiController::class, 'update']);
    Route::delete('/activities/{activity}', [ActivityApiController::class, 'destroy']);
});
