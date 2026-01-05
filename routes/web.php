<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\LeaveRequestController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// employees
Route::resource('/employees', EmployeeController::class)->middleware(['role:HR']);

// departments
Route::resource('/departments', DepartmentController::class)->middleware(['role:HR']);

// presences
Route::resource('/presences', PresenceController::class)->middleware(['role:HR,Developer,Sales,Data Science']);

// payrolls
Route::resource('/payrolls', PayrollController::class)->middleware(['role:HR,Developer,Sales,Data Science']);

// leave-requests
Route::resource('/leave-requests', LeaveRequestController::class)->middleware(['role:HR,Developer,Sales,Data Science']);
Route::get('/leave-requests/confirm/{id}', [LeaveRequestController::class, 'confirm'])->name('leave-requests.confirm')->middleware(['role:HR']);
Route::get('/leave-requests/reject/{id}', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject')->middleware(['role:HR']);



// Roles
Route::resource('/roles', RoleController::class)->middleware(['role:HR']);

// tasks
Route::resource('/tasks', TaskController::class)->middleware(['role:HR,Developer,Sales,Data Science']);
Route::get('/tasks/done/{id}', [TaskController::class, 'done'])->name('tasks.done')->middleware(['role:HR,Developer,Sales,Data Science']);
Route::get('/tasks/pending/{id}', [TaskController::class, 'pending'])->name('tasks.pending')->middleware(['role:HR,Developer,Sales,Data Science']);
});
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
