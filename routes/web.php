<?php

use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DamagedItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentSourceController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\IncomingDocumentController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LowStockController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\StockReturnController;
use App\Http\Controllers\TrackingDashboardController;
use App\Http\Controllers\UnitOfMeasureController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

    Route::post('/register/sections', [LoginController::class, 'sectionsByDivision'])->name('register.sections');
    Route::post('/register/provinces', [LoginController::class, 'provincesByRegion'])->name('register.provinces');
    Route::post('/register/cities', [LoginController::class, 'citiesByProvince'])->name('register.cities');

Route::middleware(['guest'])->group(function () {
    Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
    Route::get('/register', [LoginController::class, 'showRegisterForm'])->name('register');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::post('/register', [LoginController::class, 'register'])->name('register.post');
    Route::post('/register/check-email', [LoginController::class, 'checkEmail'])->name('register.check-email');

    Route::get('/auth/google', [\App\Http\Controllers\OAuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [\App\Http\Controllers\OAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

    Route::get('/forgot-password', [\App\Http\Controllers\OtpRecoveryController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password/send-otp', [\App\Http\Controllers\OtpRecoveryController::class, 'sendOtp'])->name('password.otp.send');
    Route::get('/verify-otp', [\App\Http\Controllers\OtpRecoveryController::class, 'showVerifyForm'])->name('password.otp.verify');
    Route::post('/verify-otp', [\App\Http\Controllers\OtpRecoveryController::class, 'verifyOtp'])->name('password.otp.submit');
    Route::get('/reset-password', [\App\Http\Controllers\OtpRecoveryController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/reset-password', [\App\Http\Controllers\OtpRecoveryController::class, 'resetPassword'])->name('password.update');
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', \App\Http\Middleware\EnsureProfileIsComplete::class])->group(function () {
    Route::get('/locale/{locale}', function (string $locale) {
        if (! in_array($locale, ['en', 'fil'], true)) {
            $locale = config('app.fallback_locale', 'en');
        }

        session(['app_locale' => $locale]);

        return redirect()->back();
    })->name('locale.set');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/items-by-category', [DashboardController::class, 'getItemsByCategory'])->name('dashboard.items-by-category');
    Route::get('/dashboard/tracking-dashboard', [TrackingDashboardController::class, 'index'])->name('tracking-dashboard.index');
    Route::get('/dashboard/tracking-dashboard/data', [TrackingDashboardController::class, 'data'])->name('tracking-dashboard.data');
    Route::get('/dashboard/tracking-dashboard/export/json', [TrackingDashboardController::class, 'exportJson'])->name('tracking-dashboard.export.json');
    Route::get('/dashboard/tracking-dashboard/export/csv', [TrackingDashboardController::class, 'exportCsv'])->name('tracking-dashboard.export.csv');
    Route::get('/dashboard/tracking-dashboard/export/pdf', [TrackingDashboardController::class, 'exportPdf'])->name('tracking-dashboard.export.pdf');
    Route::get('low-stock', [LowStockController::class, 'index'])->name('low-stock.index');

    Route::resource('categories', CategoryController::class);
    Route::resource('unit_of_measures', UnitOfMeasureController::class);
    Route::resource('items', ItemController::class);
    Route::get('stock-in', [StockInController::class, 'index'])->name('stock-in.index');
    Route::get('stock-in/create', [StockInController::class, 'create'])->name('stock-in.create');
    Route::post('stock-in/store', [StockInController::class, 'store'])->name('stock-in.store');
    Route::get('stock-in/edit/{item_id}', [StockInController::class, 'edit'])->name('stock-in.edit');
    Route::delete('stock-in/destroy/{item_id}', [StockInController::class, 'destroy'])->name('stock-in.destroy');
    Route::delete('stock-in/transaction/destroy/{id}', [StockInController::class, 'destroyTransaction'])->name('stock-in.transaction.destroy');
    Route::post('stock-in/transaction/store/{item_id}', [StockInController::class, 'storeTransaction'])->name('stock-in.transaction.store');
    Route::post('stock-in/bulk-store/{item_id}', [StockInController::class, 'bulkStore'])->name('stock-in.bulk-store');
    Route::post('stock-in/mark-printed', [StockInController::class, 'markAsPrinted'])->name('stock-in.mark-printed');
    Route::get('public/stock-in/item/{id}', [StockInController::class, 'getItemDetails'])->name('stock-in.get-item');

    // Stock Out Routes
    Route::get('stock-out', [StockOutController::class, 'index'])->name('stock-out.index');
    Route::get('stock-out/create', [StockOutController::class, 'create'])->name('stock-out.create');
    Route::post('stock-out/store', [StockOutController::class, 'store'])->name('stock-out.store');
    Route::get('stock-out/show/{id}', [StockOutController::class, 'show'])->name('stock-out.show');
    Route::post('stock-out/preview', [StockOutController::class, 'preview'])->name('stock-out.preview');
    Route::post('stock-out/group', [StockOutController::class, 'storeGroup'])->name('stock-out.group');
    Route::post('stock-out/group/update-receiver/{id}', [StockOutController::class, 'updateReceiver'])->name('stock-out.group.update-receiver');
    Route::get('stock-out/print/{id}', [StockOutController::class, 'print'])->name('stock-out.print');
    Route::get('stock-out/units/{item_id}', [StockOutController::class, 'getAvailableUnits'])->name('stock-out.units');
    Route::post('stock-out/find-unit', [StockOutController::class, 'findUnit'])->name('stock-out.find-unit');

    // Borrowing & Return Routes
    Route::get('borrowings/units/{item_id}', [BorrowingController::class, 'getAvailableUnits'])->name('borrowings.units');
    Route::resource('borrowings', BorrowingController::class);
    // Damaged Items Routes
    Route::get('damaged-items', [DamagedItemController::class, 'index'])->name('damaged-items.index');
    Route::get('damaged-items/create', [DamagedItemController::class, 'create'])->name('damaged-items.create');
    Route::post('damaged-items/store', [DamagedItemController::class, 'store'])->name('damaged-items.store');
    Route::get('damaged-items/show/{id}', [DamagedItemController::class, 'show'])->name('damaged-items.show');
    Route::post('damaged-items/preview', [DamagedItemController::class, 'preview'])->name('damaged-items.preview');
    Route::post('damaged-items/group', [DamagedItemController::class, 'storeGroup'])->name('damaged-items.group');
    Route::post('damaged-items/group/update-receiver/{id}', [DamagedItemController::class, 'updateReceiver'])->name('damaged-items.group.update-receiver');
    Route::get('damaged-items/print/{id}', [DamagedItemController::class, 'print'])->name('damaged-items.print');
    Route::get('damaged-items/units/{item_id}', [DamagedItemController::class, 'getAvailableUnits'])->name('damaged-items.units');
    Route::post('damaged-items/find-unit', [DamagedItemController::class, 'findUnit'])->name('damaged-items.find-unit');
    Route::resource('returns', StockReturnController::class);

    Route::get('incoming-documents/monthly-report', [IncomingDocumentController::class, 'monthlyReport'])->name('incoming-documents.monthly-report');
    Route::resource('incoming-documents', IncomingDocumentController::class);
    Route::get('inbox', [IncomingDocumentController::class, 'inbox'])->name('inbox.index');
    Route::get('inbox/batch-receive', [IncomingDocumentController::class, 'inboxBatch'])->name('inbox.batch');
    Route::post('inbox/batch-receive', [IncomingDocumentController::class, 'inboxBatchCreate'])->name('inbox.batch.create');
    Route::get('inbox/batch-receive/received-documents', [IncomingDocumentController::class, 'inboxBatchReceivedList'])->name('inbox.batch.received');
    Route::post('inbox/batch-receive/pin/status', [IncomingDocumentController::class, 'inboxBatchPinStatus'])->name('inbox.batch.pin.status');
    Route::post('inbox/batch-receive/pin/create', [IncomingDocumentController::class, 'inboxBatchPinCreate'])->name('inbox.batch.pin.create');
    Route::post('inbox/batch-receive/pin/reset', [IncomingDocumentController::class, 'inboxBatchPinReset'])->name('inbox.batch.pin.reset');
    Route::post('inbox/pin/create', [IncomingDocumentController::class, 'inboxPinCreate'])->name('inbox.pin.create');
    Route::get('inbox/batch-receive/{batch}/documents', [IncomingDocumentController::class, 'inboxBatchDocuments'])
        ->whereNumber('batch')
        ->name('inbox.batch.documents');
    Route::post('inbox/batch-receive/{batch}/receive', [IncomingDocumentController::class, 'inboxBatchReceiveWithPin'])
        ->whereNumber('batch')
        ->name('inbox.batch.receive');
    Route::post('inbox/{recipient}/receive', [IncomingDocumentController::class, 'inboxReceive'])->name('inbox.receive');
    Route::post('incoming-documents/{incomingDocument}/forward', [IncomingDocumentController::class, 'forward'])->name('incoming-documents.forward');
    Route::post('incoming-documents/{incomingDocument}/add-update', [IncomingDocumentController::class, 'addUpdateLog'])->name('incoming-documents.add-update');
    Route::put('incoming-documents/{incomingDocument}/logs/{documentLog}', [IncomingDocumentController::class, 'updateLog'])->name('incoming-documents.logs.update');
    Route::get('incoming-documents/lookups/groups', [IncomingDocumentController::class, 'groupOptions'])->name('incoming-documents.lookups.groups');
    Route::get('incoming-documents/lookups/staff', [IncomingDocumentController::class, 'staffSearch'])->name('incoming-documents.lookups.staff');
    Route::get('inbox/lookups/active-users', [IncomingDocumentController::class, 'inboxActiveUsers'])->name('inbox.lookups.active-users');
    Route::post('incoming-documents/{incomingDocument}/receive', [IncomingDocumentController::class, 'receive'])->name('incoming-documents.receive');

    Route::resource('document-sources', DocumentSourceController::class)->except(['show', 'create']);
    Route::resource('document-types', DocumentTypeController::class)->except(['show', 'create']);

    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [App\Http\Controllers\ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/access', [App\Http\Controllers\AccessController::class, 'index'])->name('access.index');
    Route::get('/access/config', [App\Http\Controllers\AccessController::class, 'getConfig'])->name('access.config');
    Route::post('/access', [App\Http\Controllers\AccessController::class, 'update'])->name('access.update');

    Route::resource('users', UserManagementController::class)->only(['index', 'edit', 'update']);
    Route::resource('groups', GroupController::class);
});
