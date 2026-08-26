<?php

use App\Http\Controllers\Admin\DeleteSubsistenceMinimumController;
use App\Http\Controllers\Admin\Document\AdminDeleteDocumentController;
use App\Http\Controllers\Admin\Document\AdminDownloadDocumentController;
use App\Http\Controllers\Admin\Document\AdminIndexDocumentController;
use App\Http\Controllers\Admin\Document\AdminIndexDocumentStatusesController;
use App\Http\Controllers\Admin\Document\AdminShowDocumentController;
use App\Http\Controllers\Admin\IndexSubsistenceMinimumController;
use App\Http\Controllers\Admin\PensionCalculation\AdminDeletePensionCalculationController;
use App\Http\Controllers\Admin\PensionCalculation\AdminIndexPensionCalculationController;
use App\Http\Controllers\Admin\PensionCalculation\AdminShowPensionCalculationController;
use App\Http\Controllers\Admin\StoreSubsistenceMinimumController;
use App\Http\Controllers\Admin\Translation\AdminIndexTranslationController;
use App\Http\Controllers\Admin\Translation\AdminStoreTranslationController;
use App\Http\Controllers\Admin\Translation\AdminUpdateTranslationController;
use App\Http\Controllers\Admin\UpdateSubsistenceMinimumController;
use App\Http\Controllers\Admin\User\AdminDeleteUserController;
use App\Http\Controllers\Admin\User\AdminIndexUserController;
use App\Http\Controllers\Admin\User\AdminRestoreUserController;
use App\Http\Controllers\Admin\User\AdminShowUserController;
use App\Http\Controllers\Admin\User\AdminToggleUserSuspendController;
use App\Http\Controllers\Admin\User\AdminUpdateUserRoleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Document\DeleteDocumentController;
use App\Http\Controllers\Document\DeleteTaxHistoryController;
use App\Http\Controllers\Document\FileDocumentController;
use App\Http\Controllers\Document\IndexDocumentController;
use App\Http\Controllers\Document\ShowDocumentController;
use App\Http\Controllers\Document\StoreTaxHistoryController;
use App\Http\Controllers\Document\UpdateTaxHistoryController;
use App\Http\Controllers\Document\UploadDocumentController;
use App\Http\Controllers\FallbackController;
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
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');

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
    Route::get('/{id}/breakdown', \App\Http\Controllers\PensionCalculationBreakdownController::class)->name('pension-calculations.breakdown');
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

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return \Inertia\Inertia::render('admin/AdminDashboard');
    })->name('dashboard');

    // Section 1: User Management
    Route::get('/users', AdminIndexUserController::class)->name('users.index');
    Route::get('/users/{id}', AdminShowUserController::class)->name('users.show');
    Route::put('/users/{id}/role', AdminUpdateUserRoleController::class)->name('users.update-role');
    Route::post('/users/{id}/toggle-suspend', AdminToggleUserSuspendController::class)->name('users.toggle-suspend');
    Route::delete('/users/{id}', AdminDeleteUserController::class)->name('users.destroy');
    Route::post('/users/{id}/restore', AdminRestoreUserController::class)->name('users.restore');

    // Section 2: Calculation Results History
    Route::get('/pension-calculations', AdminIndexPensionCalculationController::class)->name('pension-calculations.index');
    Route::get('/pension-calculations/{id}', AdminShowPensionCalculationController::class)->name('pension-calculations.show');
    Route::delete('/pension-calculations/{id}', AdminDeletePensionCalculationController::class)->name('pension-calculations.destroy');

    // Section 3: Documents Management
    Route::get('/documents', AdminIndexDocumentController::class)->name('documents.index');
    Route::get('/documents/statuses', AdminIndexDocumentStatusesController::class)->name('documents.statuses');
    Route::get('/documents/{id}', AdminShowDocumentController::class)->name('documents.show');
    Route::get('/documents/{id}/download', AdminDownloadDocumentController::class)->name('documents.download');
    Route::delete('/documents/{id}', AdminDeleteDocumentController::class)->name('documents.destroy');

    // Section 4: Translation (i18n) Manager
    Route::get('/translations', AdminIndexTranslationController::class)->name('translations.index');
    Route::post('/translations', AdminStoreTranslationController::class)->name('translations.store');
    Route::put('/translations', AdminUpdateTranslationController::class)->name('translations.update');

    // Subsistence Minimums
    Route::get('/subsistence-minimums', IndexSubsistenceMinimumController::class)->name('subsistence-minimums.index');
    Route::post('/subsistence-minimums', StoreSubsistenceMinimumController::class)->name('subsistence-minimums.store');
    Route::put('/subsistence-minimums/{id}', UpdateSubsistenceMinimumController::class)->name('subsistence-minimums.update');
    Route::delete('/subsistence-minimums/{id}', DeleteSubsistenceMinimumController::class)->name('subsistence-minimums.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('pension-coefficients')->group(function () {
    Route::get('/', IndexPensionCoefficientController::class)->name('pension-coefficients.index');
    Route::post('/', StorePensionCoefficientController::class)->name('pension-coefficients.store');
    Route::put('/{id}', UpdatePensionCoefficientController::class)->name('pension-coefficients.update');
    Route::delete('/{id}', DeletePensionCoefficientController::class)->name('pension-coefficients.destroy');
});

require __DIR__.'/settings.php';

Route::fallback(FallbackController::class);
