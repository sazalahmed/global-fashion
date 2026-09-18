<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BulkActionController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;

// All web routes are handled by modules.
// See Modules/*/routes/web.php

// Returns a fresh CSRF token for the AJAX self-heal after a 419 (see
// public/js/csrf.js). The response also re-issues a current XSRF-TOKEN cookie,
// so the replayed request authenticates against the live session.
Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]))->name('csrf.token');

// Global search
Route::middleware('auth')->get('/search', [SearchController::class, 'search'])->name('search');
Route::middleware('auth')->get('/search/full', [SearchController::class, 'fullSearch'])->name('search.full');
Route::middleware('auth')->get('/search/results', [SearchController::class, 'results'])->name('search.results');

// Centralized export route for all modules
Route::middleware(['auth', 'throttle:heavy'])->get('/export/{module}', [ExportController::class, 'export'])->name('export');

// Centralized import route for all modules
Route::middleware(['auth', 'throttle:heavy'])->post('/import/{module}', [ImportController::class, 'import'])->name('import');

// Centralized bulk action routes
Route::middleware('auth')->prefix('bulk')->name('bulk.')->group(function () {
    Route::post('/{module}/delete', [BulkActionController::class, 'bulkDelete'])->name('delete');
    Route::post('/{module}/status', [BulkActionController::class, 'bulkStatusUpdate'])->name('status');
});

// Notification routes (page + JSON for the header dropdown).
// Under /admin like every other panel screen: outside it, the 404 handler
// treats the path as storefront and serves the customer-facing error page.
Route::middleware('auth')->prefix('admin/notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('markAllRead');
});
