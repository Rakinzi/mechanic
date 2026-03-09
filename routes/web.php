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
use Illuminate\Auth\Access\AuthorizationException;
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

    if ($user->hasRole('client')) {
        return redirect()->route('client.repairs.index');
    }

    throw new AuthorizationException('No role has been assigned to this account.');
})->middleware(['auth', 'verified', 'active'])->name('dashboard');

Route::middleware(['auth', 'verified', 'active', 'role:admin'])->prefix('admin')->as('admin.')->group(function (): void {
    Route::get('dashboard', AdminDashboardController::class)->middleware('permission:view admin dashboard')->name('dashboard');

    Route::get('job-cards', [JobCardController::class, 'index'])->middleware('permission:manage job cards')->name('job-cards.index');
    Route::post('job-cards', [JobCardController::class, 'store'])->middleware('permission:manage job cards')->name('job-cards.store');
    Route::get('job-cards/{jobCard}', [JobCardController::class, 'show'])->middleware('permission:manage job cards')->name('job-cards.show');
    Route::patch('job-cards/{jobCard}/stages/{jobStage}', [JobCardController::class, 'updateStagePlan'])->middleware('permission:manage job cards')->name('job-cards.stages.update');
    Route::get('job-cards/{jobCard}/summary', [JobCardSummaryController::class, 'admin'])->middleware('permission:manage job cards')->name('job-cards.summary');
    Route::post('job-cards/{jobCard}/close', [JobCardController::class, 'close'])->middleware('permission:manage job cards')->name('job-cards.close');

    Route::get('stages', [StageController::class, 'index'])->middleware('permission:manage stages')->name('stages.index');
    Route::post('stages', [StageController::class, 'store'])->middleware('permission:manage stages')->name('stages.store');
    Route::put('stages/{stage}', [StageController::class, 'update'])->middleware('permission:manage stages')->name('stages.update');

    Route::post('delay-reports/{delayReport}/approve', [DelayReportReviewController::class, 'approve'])->middleware('permission:review delay reports')->name('delay-reports.approve');
    Route::post('delay-reports/{delayReport}/reject', [DelayReportReviewController::class, 'reject'])->middleware('permission:review delay reports')->name('delay-reports.reject');

    Route::get('users', [UserManagementController::class, 'index'])->middleware('permission:manage users')->name('users.index');
    Route::post('users', [UserManagementController::class, 'store'])->middleware('permission:manage users')->name('users.store');
    Route::put('users/{user}', [UserManagementController::class, 'update'])->middleware('permission:manage users')->name('users.update');
    Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->middleware('permission:manage users')->name('users.destroy');
    Route::get('reports', [ReportController::class, 'index'])->middleware('permission:view reports')->name('reports.index');
});

Route::middleware(['auth', 'verified', 'active', 'role:mechanic', 'permission:view assigned stages'])->prefix('mechanic')->as('mechanic.')->group(function (): void {
    Route::get('assigned-stages', [AssignedStageController::class, 'index'])->name('assigned-stages.index');
    Route::get('assigned-stages/{jobStage}', [AssignedStageController::class, 'show'])->name('assigned-stages.show');

    Route::post('job-stages/{jobStage}/start', [StageActionController::class, 'start'])->middleware('permission:run stage actions')->name('job-stages.start');
    Route::post('job-stages/{jobStage}/pause', [StageActionController::class, 'pause'])->middleware('permission:run stage actions')->name('job-stages.pause');
    Route::post('job-stages/{jobStage}/block', [StageActionController::class, 'block'])->middleware('permission:run stage actions')->name('job-stages.block');
    Route::post('job-stages/{jobStage}/complete', [StageActionController::class, 'complete'])->middleware('permission:run stage actions')->name('job-stages.complete');

    Route::post('job-stages/{jobStage}/delay-reports', [DelayReportController::class, 'store'])->middleware('permission:submit delay reports')->name('delay-reports.store');
});

Route::middleware(['auth', 'verified', 'active', 'role:client', 'permission:view own repairs'])->prefix('client')->as('client.')->group(function (): void {
    Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('repairs', [RepairController::class, 'index'])->name('repairs.index');
    Route::get('repairs/{jobCard}', [RepairController::class, 'show'])->name('repairs.show');
    Route::get('repairs/{jobCard}/summary', [JobCardSummaryController::class, 'client'])->name('repairs.summary');
});

require __DIR__.'/settings.php';
