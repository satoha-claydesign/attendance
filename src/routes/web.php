<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
// AdminDashboardController removed (dashboard not needed)
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\TimestampsController;
use App\Http\Controllers\DetailController;
use App\Http\Controllers\AdminApprovalController;
use App\Http\Controllers\AdminAttendanceController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Redirect root to the authenticated attendance page so URL shows /attendance
Route::redirect('/', '/attendance');

Route::prefix('admin')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('admin.login');
    Route::post('login', [LoginController::class, 'store']);

    Route::middleware('auth:admin')->group(function () {
        // dashboard removed — admins will land on attendance list
    });
});

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('/attendance', [TimestampsController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/list', [TimestampsController::class, 'list'])->name('attendance.list');
    Route::post('/attendance/punchin', [TimestampsController::class, 'punchIn'])->name('attendance.punchin');
    Route::post('/attendance/punchout', [TimestampsController::class, 'punchOut'])->name('attendance.punchout');
    Route::post('/attendance/breakin', [TimestampsController::class, 'breakIn'])->name('attendance.breakin');
    Route::post('/attendance/breakout', [TimestampsController::class, 'breakOut'])->name('attendance.breakout');
    // accept optional id so links can be generated like route('attendance.detail', $id)
    Route::get('/attendance/detail/{id?}', [DetailController::class, 'detail'])->name('attendance.detail');
    Route::put('/attendance/{id}', [TimestampsController::class, 'update'])->name('attendance.update');
});

// Admin-only approval POST (approve/reject). Listing and viewing are available to web users as well.
Route::post('/stamp_correction_request/approve/{id}', [AdminApprovalController::class, 'approve'])->middleware('auth:admin');

// Listing is available to authenticated web users and admins (controller filters by guard).
Route::get('/stamp_correction_request/list', [AdminApprovalController::class, 'index'])->middleware('auth');

// Approval detail (show) should be accessible to authenticated users so users can view their own requests,
// while POST remain admin-only. Use auth:web for regular users and admin may still access via their guard.
Route::get('/stamp_correction_request/approve/{id}', [AdminApprovalController::class, 'show'])->middleware('auth');

// Admin attendance list (daily)
Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index']);
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show']);
    Route::put('/admin/attendance/{id}', [AdminAttendanceController::class, 'update']);
    // CSV export for staff monthly attendance
    Route::get('/admin/attendance/staff/{id}/csv', [\App\Http\Controllers\AdminStaffController::class, 'exportCsv']);
    // Admin-only approvals list (so admin menu links don't trigger web login)
    Route::get('/admin/stamp_correction_request/list', [AdminApprovalController::class, 'index']);
    // Admin view for a single approval (show) and admin-prefixed approve POST
    Route::get('/admin/stamp_correction_request/approve/{id}', [AdminApprovalController::class, 'show']);
    Route::post('/admin/stamp_correction_request/approve/{id}', [AdminApprovalController::class, 'approve']);
    // staff management
    Route::get('/admin/staff/list', [\App\Http\Controllers\AdminStaffController::class, 'index']);
    Route::get('/admin/attendance/staff/{id}', [\App\Http\Controllers\AdminStaffController::class, 'staffAttendance']);
});

