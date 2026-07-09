<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\MeetingTotalController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanPaymentController;
use App\Http\Controllers\FineController;
use App\Http\Controllers\BankExpenseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GroupSelectorController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\MeetingScheduledDateController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Auth::routes();

Route::middleware('auth')->group(function () {

    // Group selector – admin only, no group required
    Route::get('group-selector',        [GroupSelectorController::class, 'index'])->name('group.selector')->middleware('admin');
    Route::get('group-selector/search', [GroupSelectorController::class, 'search'])->name('group.selector.search')->middleware('admin');
    Route::post('group-selector/select',[GroupSelectorController::class, 'select'])->name('group.selector.select')->middleware('admin');
    Route::post('group-selector/clear', [GroupSelectorController::class, 'clear'])->name('group.selector.clear')->middleware('admin');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('group.selected');
    Route::get('/home', [DashboardController::class, 'index'])->name('home')->middleware('group.selected');

    // READ routes – all authenticated roles (admin requires group selected)
    Route::middleware('group.selected')->group(function () {
        Route::get('members/register',     [MemberController::class,  'create'])->name('members.create')->middleware('role:admin,admin_grupo,tesorero,secretario');
        Route::get('members/search-users', [MemberController::class,  'searchUsers'])->name('members.search-users')->middleware('role:admin,admin_grupo,tesorero,secretario');
        Route::resource('members',         MemberController::class)->only(['index', 'show']);
        Route::get('meetings/new',         [MeetingController::class, 'create'])->name('meetings.create')->middleware('role:admin,admin_grupo,tesorero,secretario');
        Route::resource('meetings',        MeetingController::class)->only(['index', 'show']);
        Route::resource('loans',           LoanController::class)->only(['index', 'show'])->where(['loan' => '[0-9]+']);
        Route::get('loans/members/{groupId}',         [LoanController::class,       'getMembersByGroup'])->name('loans.members');
        Route::get('loans/meetings/{groupId}',        [LoanController::class,       'getMeetingsByGroup'])->name('loans.meetings');
        Route::get('fines/members/{groupId}',         [FineController::class,       'getMembersByGroup'])->name('fines.members');
        Route::get('fines/meetings/{groupId}',        [FineController::class,       'getMeetingsByGroup'])->name('fines.meetings');
        Route::get('bank-expenses/meetings/{groupId}',[BankExpenseController::class,'getMeetingsByGroup'])->name('bank-expenses.meetings');
        Route::resource('fines',         FineController::class)->only(['index']);
        Route::resource('bank-expenses', BankExpenseController::class)->only(['index']);
        Route::get('reports',             [ReportController::class, 'index'])->name('reports.index');
        Route::post('reports/generate',   [ReportController::class, 'generate'])->name('reports.generate');
        Route::get('reports/members/{group}', [ReportController::class, 'membersByGroup'])->name('reports.members');

        // Meeting scheduled dates (read)
        Route::get('meeting-scheduled-dates',       [MeetingScheduledDateController::class, 'index'])->name('meeting-scheduled-dates.index');
        Route::get('meeting-scheduled-dates/next',  [MeetingScheduledDateController::class, 'next'])->name('meeting-scheduled-dates.next');
    });

    // Groups – admin only
    Route::middleware('admin')->group(function () {
        Route::get('groups/new', [GroupController::class, 'create'])->name('groups.create');
        Route::resource('groups', GroupController::class)->only(['index', 'show', 'store', 'edit', 'update', 'destroy']);
    });

    // WRITE routes – admin, admin_grupo, tesorero, secretario (not observador)
    Route::middleware('role:admin,admin_grupo,tesorero,secretario')->group(function () {

        // Members
        Route::resource('members', MemberController::class)->only(['store', 'edit', 'update']);
        Route::get('groups/{group}/members/new', [MemberController::class, 'createForGroup'])
            ->name('groups.members.create');
        Route::post('members/{member}/membership-paid', [MemberController::class, 'markMembershipPaid'])
            ->name('members.membership-paid');
        Route::post('members/{member}/link-user',     [MemberController::class, 'linkUser'])->name('members.link-user');
        Route::post('members/{member}/create-user',   [MemberController::class, 'createUser'])->name('members.create-user');
        Route::delete('members/{member}/unlink-user', [MemberController::class, 'unlinkUser'])->name('members.unlink-user');

        // Meetings
        Route::resource('meetings', MeetingController::class)->only(['store', 'edit', 'update']);
        Route::post('meetings/{meeting}/close',  [MeetingController::class, 'close'])->name('meetings.close');
        Route::post('meetings/{meeting}/reopen', [MeetingController::class, 'reopen'])->name('meetings.reopen');

        // Attendance
        Route::post('meetings/{meeting}/attendance', [AttendanceController::class, 'update'])
            ->name('meetings.attendance.update');

        // Contributions
        Route::put('meetings/{meeting}/contributions/{contribution}', [ContributionController::class, 'update'])
            ->name('meetings.contributions.update');
        Route::post('meetings/{meeting}/contributions/bulk', [ContributionController::class, 'bulkUpdate'])
            ->name('meetings.contributions.bulk');
        Route::put('meetings/{meeting}/totals', [MeetingTotalController::class, 'update'])
            ->name('meetings.totals.update');

        // Loans write
        Route::get('loans/new', [LoanController::class, 'create'])->name('loans.create');
        Route::resource('loans', LoanController::class)->only(['store']);

        // Loan Payments
        Route::post('loan-payments', [LoanPaymentController::class, 'store'])->name('loan-payments.store');

        // Fines write
        Route::get('fines/new', [FineController::class, 'create'])->name('fines.create');
        Route::resource('fines', FineController::class)->only(['store']);
        Route::post('fines/{fine}/mark-paid', [FineController::class, 'markPaid'])->name('fines.mark-paid');

        // Bank Expenses write
        Route::get('bank-expenses/new', [BankExpenseController::class, 'create'])->name('bank-expenses.create');
        Route::resource('bank-expenses', BankExpenseController::class)->only(['store', 'edit', 'update']);

        // Meeting scheduled dates write
        Route::post('meeting-scheduled-dates',           [MeetingScheduledDateController::class, 'store'])->name('meeting-scheduled-dates.store');
        Route::delete('meeting-scheduled-dates/{scheduledDate}', [MeetingScheduledDateController::class, 'destroy'])->name('meeting-scheduled-dates.destroy');
    });

    // DELETE routes – admin only
    Route::middleware('admin')->group(function () {
        Route::delete('members/{member}',           [MemberController::class,     'destroy'])->name('members.destroy');
        Route::delete('meetings/{meeting}',         [MeetingController::class,    'destroy'])->name('meetings.destroy');
        Route::delete('loans/{loan}',               [LoanController::class,       'destroy'])->name('loans.destroy');
        Route::delete('loan-payments/{loanPayment}',[LoanPaymentController::class,'destroy'])->name('loan-payments.destroy');
        Route::delete('fines/{fine}',               [FineController::class,       'destroy'])->name('fines.destroy');
        Route::delete('bank-expenses/{bankExpense}',[BankExpenseController::class,'destroy'])->name('bank-expenses.destroy');
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
