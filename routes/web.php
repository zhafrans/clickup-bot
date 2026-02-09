<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClickupController;
use App\Http\Controllers\SchedulerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});

Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Schedulers CRUD
    Route::get('schedulers', [SchedulerController::class, 'index'])->name('schedulers.index');
    Route::post('schedulers', [SchedulerController::class, 'store'])->name('schedulers.store');
    Route::put('schedulers/{scheduler}', [SchedulerController::class, 'update'])->name('schedulers.update');
    Route::delete('schedulers/{scheduler}', [SchedulerController::class, 'destroy'])->name('schedulers.destroy');

    Route::get('clickup/send-report', [ClickupController::class, 'sendReport'])->name('clickup.send-report');
});

require __DIR__.'/settings.php';
