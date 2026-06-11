<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanPaymentController;
use App\Http\Controllers\FineController;
use App\Http\Controllers\BankExpenseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MemberPortalController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Auth::routes();

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index'])->name('home');

    // READ routes – all authenticated roles
    Route::resource('groups',  GroupController::class)->only(['index', 'show']);
    Route::resource('members', MemberController::class)->only(['index', 'show']);
    Route::resource('meetings', MeetingController::class)->only(['index', 'show']);
    Route::resource('loans', LoanController::class)->only(['index', 'show'])
        ->where(['loan' => '[0-9]+']);
    Route::get('loans/members/{groupId}',  [LoanController::class, 'getMembersByGroup'])->name('loans.members');
    Route::get('loans/meetings/{groupId}', [LoanController::class, 'getMeetingsByGroup'])->name('loans.meetings');
    Route::get('fines/members/{groupId}',  [FineController::class, 'getMembersByGroup'])->name('fines.members');
    Route::get('fines/meetings/{groupId}', [FineController::class, 'getMeetingsByGroup'])->name('fines.meetings');
    Route::get('bank-expenses/meetings/{groupId}', [BankExpenseController::class, 'getMeetingsByGroup'])->name('bank-expenses.meetings');
    Route::resource('fines', FineController::class)->only(['index']);
    Route::resource('bank-expenses', BankExpenseController::class)->only(['index']);

    // Reports – all roles (observador can generate/print)
    Route::get('reports',                          [ReportController::class, 'index'])->name('reports.index');
    Route::post('reports/generate',               [ReportController::class, 'generate'])->name('reports.generate');
    Route::get('reports/members/{group}',         [ReportController::class, 'membersByGroup'])->name('reports.members');

    // WRITE routes – admin, tesorero, secretario (not observador)
    Route::middleware('role:admin,tesorero,secretario')->group(function () {

        // Groups
        Route::get('groups/new',  [GroupController::class, 'create'])->name('groups.create');
        Route::resource('groups', GroupController::class)->only(['store', 'edit', 'update', 'destroy']);

        // Members
        Route::get('members/search-users', [MemberController::class, 'searchUsers'])
            ->name('members.search-users');
        Route::get('members/register', [MemberController::class, 'create'])
            ->name('members.create');
        Route::resource('members', MemberController::class)->only(['store', 'edit', 'update', 'destroy']);
        Route::get('groups/{group}/members/new', [MemberController::class, 'createForGroup'])
            ->name('groups.members.create');
        Route::post('members/{member}/membership-paid', [MemberController::class, 'markMembershipPaid'])
            ->name('members.membership-paid');
        Route::post('members/{member}/link-user',     [MemberController::class, 'linkUser'])->name('members.link-user');
        Route::post('members/{member}/create-user',   [MemberController::class, 'createUser'])->name('members.create-user');
        Route::delete('members/{member}/unlink-user', [MemberController::class, 'unlinkUser'])->name('members.unlink-user');

        // Meetings
        Route::get('meetings/new', [MeetingController::class, 'create'])->name('meetings.create');
        Route::resource('meetings', MeetingController::class)->only(['store', 'edit', 'update', 'destroy']);
        Route::post('meetings/{meeting}/close',  [MeetingController::class, 'close'])->name('meetings.close');
        Route::post('meetings/{meeting}/reopen', [MeetingController::class, 'reopen'])->name('meetings.reopen');

        // Attendance
        Route::post('meetings/{meeting}/attendance', [AttendanceController::class, 'update'])
            ->name('meetings.attendance.update');

        // Contributions (financial – also accessible to tesorero+secretario for now)
        Route::put('meetings/{meeting}/contributions/{contribution}', [ContributionController::class, 'update'])
            ->name('meetings.contributions.update');
        Route::post('meetings/{meeting}/contributions/bulk', [ContributionController::class, 'bulkUpdate'])
            ->name('meetings.contributions.bulk');

        // Loans write
        Route::get('loans/new', [LoanController::class, 'create'])->name('loans.create');
        Route::resource('loans', LoanController::class)->except(['edit', 'update', 'show', 'index', 'create']);

        // Loan Payments
        Route::post('loan-payments',                      [LoanPaymentController::class, 'store'])->name('loan-payments.store');
        Route::delete('loan-payments/{loanPayment}',      [LoanPaymentController::class, 'destroy'])->name('loan-payments.destroy');

        // Fines write
        Route::get('fines/new', [FineController::class, 'create'])->name('fines.create');
        Route::resource('fines', FineController::class)->except(['edit', 'update', 'show', 'index', 'create']);
        Route::post('fines/{fine}/mark-paid', [FineController::class, 'markPaid'])->name('fines.mark-paid');

        // Bank Expenses write
        Route::get('bank-expenses/new', [BankExpenseController::class, 'create'])->name('bank-expenses.create');
        Route::resource('bank-expenses', BankExpenseController::class)->except(['show', 'index', 'create']);
    });

    // Users – admin only
    Route::middleware('admin')->group(function () {
        Route::get('users/new', [UserController::class, 'create'])->name('users.create');
        Route::resource('users', UserController::class)->except(['show', 'create']);
    });

    // Member portal – miembro role only
    Route::middleware('role:miembro')->prefix('portal')->name('portal.')->group(function () {
        Route::get('contributions', [MemberPortalController::class, 'contributions'])->name('contributions');
        Route::get('loans',         [MemberPortalController::class, 'loans'])->name('loans');
    });
});
