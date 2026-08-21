<?php

use App\Http\Controllers\Admin\DeleteSubsistenceMinimumController;
use App\Http\Controllers\Admin\Document\AdminIndexDocumentController;
use App\Http\Controllers\Admin\Document\AdminIndexDocumentStatusesController;
use App\Http\Controllers\Admin\Document\AdminShowDocumentController;
use App\Http\Controllers\Admin\IndexSubsistenceMinimumController;
use App\Http\Controllers\Admin\StoreSubsistenceMinimumController;
use App\Http\Controllers\Admin\UpdateSubsistenceMinimumController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Document\DeleteDocumentController;
use App\Http\Controllers\Document\DeleteTaxHistoryController;
use App\Http\Controllers\Document\FileDocumentController;
use App\Http\Controllers\Document\IndexDocumentController;
use App\Http\Controllers\Document\ShowDocumentController;
use App\Http\Controllers\Document\StoreTaxHistoryController;
use App\Http\Controllers\Document\UpdateTaxHistoryController;
use App\Http\Controllers\Document\UploadDocumentController;
use App\Http\Controllers\Notification\IndexNotificationController;
use App\Http\Controllers\Notification\MarkAllNotificationsAsReadController;
use App\Http\Controllers\Notification\MarkNotificationAsReadController;
use App\Http\Controllers\PensionCalculation\IndexPensionCalculationController;
use App\Http\Controllers\PensionCalculation\ShowPensionCalculationController;
use App\Http\Controllers\PensionCalculation\StorePensionCalculationController;
use App\Http\Controllers\PensionCoefficient\DeletePensionCoefficientController;
use App\Http\Controllers\PensionCoefficient\IndexPensionCoefficientController;
use App\Http\Controllers\PensionCoefficient\StorePensionCoefficientController;
use App\Http\Controllers\PensionCoefficient\UpdatePensionCoefficientController;
use App\Models\PfuNews;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $pfuNews = PfuNews::latest()->take(3)->get();
    return Inertia::render('Welcome', [
        'pfuNews' => $pfuNews,
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth'])->prefix('notifications')->group(function () {
    Route::get('/', IndexNotificationController::class)->name('notifications.index');
    Route::post('/read-all', MarkAllNotificationsAsReadController::class)->name('notifications.read-all');
    Route::post('/{id}/read', MarkNotificationAsReadController::class)->name('notifications.read');
});

Route::middleware(['auth'])->prefix('pension-calculations')->group(function () {
    Route::get('/', IndexPensionCalculationController::class)->name('pension-calculations.index');
    Route::post('/', StorePensionCalculationController::class)->name('pension-calculations.store');
    Route::get('/{id}', ShowPensionCalculationController::class)->name('pension-calculations.show');
});

Route::middleware(['auth'])->prefix('documents')->group(function () {
    Route::get('/', IndexDocumentController::class)->name('documents.index');
    Route::post('/upload', UploadDocumentController::class)->name('documents.upload');
    Route::post('/tax-histories', StoreTaxHistoryController::class)->name('documents.tax-histories.store');
    Route::put('/tax-histories/{id}', UpdateTaxHistoryController::class)->name('documents.tax-histories.update');
    Route::delete('/tax-histories/{id}', DeleteTaxHistoryController::class)->name('documents.tax-histories.destroy');
    Route::get('/{id}', ShowDocumentController::class)->name('documents.show');
    Route::get('/{id}/file', FileDocumentController::class)->name('documents.file');
    Route::delete('/{id}', DeleteDocumentController::class)->name('documents.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin/documents')->group(function () {
    Route::get('/', AdminIndexDocumentController::class)->name('admin.documents.index');
    Route::get('/statuses', AdminIndexDocumentStatusesController::class)->name('admin.documents.statuses');
    Route::get('/{id}', AdminShowDocumentController::class)->name('admin.documents.show');
});

Route::middleware(['auth', 'admin'])->prefix('admin/subsistence-minimums')->group(function () {
    Route::get('/', IndexSubsistenceMinimumController::class)->name('admin.subsistence-minimums.index');
    Route::post('/', StoreSubsistenceMinimumController::class)->name('admin.subsistence-minimums.store');
    Route::put('/{id}', UpdateSubsistenceMinimumController::class)->name('admin.subsistence-minimums.update');
    Route::delete('/{id}', DeleteSubsistenceMinimumController::class)->name('admin.subsistence-minimums.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('pension-coefficients')->group(function () {
    Route::get('/', IndexPensionCoefficientController::class)->name('pension-coefficients.index');
    Route::post('/', StorePensionCoefficientController::class)->name('pension-coefficients.store');
    Route::put('/{id}', UpdatePensionCoefficientController::class)->name('pension-coefficients.update');
    Route::delete('/{id}', DeletePensionCoefficientController::class)->name('pension-coefficients.destroy');
});

require __DIR__.'/settings.php';
