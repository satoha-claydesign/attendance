<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\TimestampsController;

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

Route::get('/', [TimestampsController::class, 'index'])->name('attendance.index');

Route::prefix('admin')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('admin.login');
    Route::post('login', [LoginController::class, 'store']);

    Route::middleware('auth:admin')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index']);
    });
});

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('/attendance', [TimestampsController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/punchin', [TimestampsController::class, 'punchIn'])->name('attendance.punchin');
    Route::post('/attendance/punchout', [TimestampsController::class, 'punchOut'])->name('attendance.punchout');
    Route::post('/attendance/breakin', [TimestampsController::class, 'breakIn'])->name('attendance.breakin');
    Route::post('/attendance/breakout', [TimestampsController::class, 'breakOut'])->name('attendance.breakout');
});

