<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DelayReportReviewController;
use App\Http\Controllers\Admin\JobCardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StageController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Client\RepairController;
use App\Http\Controllers\Client\VehicleController;
use App\Http\Controllers\JobCardSummaryController;
use App\Http\Controllers\Mechanic\AssignedStageController;
use App\Http\Controllers\Mechanic\DelayReportController;
use App\Http\Controllers\Mechanic\StageActionController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): RedirectResponse {
    if (request()->user()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

Route::get('dashboard', function (): RedirectResponse {
    $user = request()->user();

    if ($user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->hasRole('mechanic')) {
        return redirect()->route('mechanic.assigned-stages.index');
    }

    return redirect()->route('client.repairs.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->as('admin.')->group(function (): void {
    Route::get('dashboard', AdminDashboardController::class)->name('dashboard');

    Route::get('job-cards', [JobCardController::class, 'index'])->name('job-cards.index');
    Route::post('job-cards', [JobCardController::class, 'store'])->name('job-cards.store');
    Route::get('job-cards/{jobCard}', [JobCardController::class, 'show'])->name('job-cards.show');
    Route::get('job-cards/{jobCard}/summary', [JobCardSummaryController::class, 'admin'])->name('job-cards.summary');
    Route::post('job-cards/{jobCard}/close', [JobCardController::class, 'close'])->name('job-cards.close');

    Route::get('stages', [StageController::class, 'index'])->name('stages.index');
    Route::post('stages', [StageController::class, 'store'])->name('stages.store');
    Route::put('stages/{stage}', [StageController::class, 'update'])->name('stages.update');

    Route::post('delay-reports/{delayReport}/approve', [DelayReportReviewController::class, 'approve'])->name('delay-reports.approve');
    Route::post('delay-reports/{delayReport}/reject', [DelayReportReviewController::class, 'reject'])->name('delay-reports.reject');

    Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
});

Route::middleware(['auth', 'verified', 'role:mechanic'])->prefix('mechanic')->as('mechanic.')->group(function (): void {
    Route::get('assigned-stages', [AssignedStageController::class, 'index'])->name('assigned-stages.index');
    Route::get('assigned-stages/{jobStage}', [AssignedStageController::class, 'show'])->name('assigned-stages.show');

    Route::post('job-stages/{jobStage}/start', [StageActionController::class, 'start'])->name('job-stages.start');
    Route::post('job-stages/{jobStage}/pause', [StageActionController::class, 'pause'])->name('job-stages.pause');
    Route::post('job-stages/{jobStage}/block', [StageActionController::class, 'block'])->name('job-stages.block');
    Route::post('job-stages/{jobStage}/complete', [StageActionController::class, 'complete'])->name('job-stages.complete');

    Route::post('job-stages/{jobStage}/delay-reports', [DelayReportController::class, 'store'])->name('delay-reports.store');
});

Route::middleware(['auth', 'verified', 'role:client'])->prefix('client')->as('client.')->group(function (): void {
    Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('repairs', [RepairController::class, 'index'])->name('repairs.index');
    Route::get('repairs/{jobCard}', [RepairController::class, 'show'])->name('repairs.show');
    Route::get('repairs/{jobCard}/summary', [JobCardSummaryController::class, 'client'])->name('repairs.summary');
});

require __DIR__.'/settings.php';
