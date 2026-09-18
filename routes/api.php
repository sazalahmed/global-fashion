<?php

use App\Http\Controllers\Api\V1\AccountingApiController;
use App\Http\Controllers\Api\V1\AttendanceApiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandApiController;
use App\Http\Controllers\Api\V1\CategoryApiController;
use App\Http\Controllers\Api\V1\EcommerceApiController;
use App\Http\Controllers\Api\V1\EmployeeApiController;
use App\Http\Controllers\Api\V1\ExpenseApiController;
use App\Http\Controllers\Api\V1\InventoryApiController;
use App\Http\Controllers\Api\V1\PayrollApiController;
use App\Http\Controllers\Api\V1\PurchaseApiController;
use App\Http\Controllers\Api\V1\QuotationApiController;
use App\Http\Controllers\Api\V1\ReportApiController;
use App\Http\Controllers\Api\V1\SettingsApiController;
use App\Http\Controllers\Api\V1\CustomerApiController;
use App\Http\Controllers\Api\V1\CustomerGroupApiController;
use App\Http\Controllers\Api\V1\DashboardApiController;
use App\Http\Controllers\Api\V1\NotificationApiController;
use App\Http\Controllers\Api\V1\PaymentApiController;
use App\Http\Controllers\Api\V1\PosController;
use App\Http\Controllers\Api\V1\ProductApiController;
use App\Http\Controllers\Api\V1\SaleApiController;
use App\Http\Controllers\Api\V1\SaleReturnApiController;
use App\Http\Controllers\Api\V1\SupplierApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Mobile App
|--------------------------------------------------------------------------
|
| These routes serve the BizPOS Mobile React Native app.
| All routes are prefixed with /api automatically by Laravel.
|
*/

// ── Webhook Receiver (no auth, no CSRF) ──

Route::post('webhook/receive', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::info('Webhook received', [
        'event' => $request->input('event'),
        'data'  => $request->input('data'),
    ]);

    return response()->json(['status' => 'ok', 'received_at' => now()->toIso8601String()]);
})->name('webhook.receive');

Route::post('webhooks/steadfast', [\Modules\Ecommerce\Http\Controllers\Webhooks\SteadfastWebhookController::class, 'handle'])
    ->name('webhooks.steadfast');

// ── Public (no auth required) ──

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
});

// ── Protected (Sanctum token required) ──

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('auth/profile', [AuthController::class, 'profile']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // POS
    Route::get('pos/init', [PosController::class, 'init']);
    Route::get('pos/search-products', [PosController::class, 'searchProducts']);
    Route::get('pos/barcode', [PosController::class, 'getByBarcode']);
    Route::post('pos/process-sale', [PosController::class, 'processSale']);
    Route::get('pos/search-customers', [PosController::class, 'searchCustomers']);
    Route::get('pos/customer-advance', [PosController::class, 'customerAdvance']);
    Route::post('pos/quick-add-customer', [PosController::class, 'quickAddCustomer']);
    Route::get('pos/receipt/{sale}', [PosController::class, 'receipt']);

    // Products
    Route::get('products/form-options', [ProductApiController::class, 'formOptions']);
    Route::get('products', [ProductApiController::class, 'index']);
    Route::get('products/{id}', [ProductApiController::class, 'show']);
    Route::post('products', [ProductApiController::class, 'store']);
    Route::post('products/{id}', [ProductApiController::class, 'update']);
    Route::delete('products/{id}', [ProductApiController::class, 'destroy']);
    Route::get('products/{id}/stock', [ProductApiController::class, 'stock']);

    // Categories
    Route::get('categories', [CategoryApiController::class, 'index']);
    Route::get('categories/{id}', [CategoryApiController::class, 'show']);
    Route::post('categories', [CategoryApiController::class, 'store']);
    Route::put('categories/{id}', [CategoryApiController::class, 'update']);
    Route::delete('categories/{id}', [CategoryApiController::class, 'destroy']);

    // Brands
    Route::get('brands', [BrandApiController::class, 'index']);
    Route::post('brands', [BrandApiController::class, 'store']);
    Route::put('brands/{id}', [BrandApiController::class, 'update']);
    Route::delete('brands/{id}', [BrandApiController::class, 'destroy']);

    // Sale Returns
    Route::get('sale-returns', [SaleReturnApiController::class, 'index']);
    Route::get('sale-returns/{id}', [SaleReturnApiController::class, 'show']);
    Route::post('sale-returns', [SaleReturnApiController::class, 'store']);

    // Customers
    Route::get('customers', [CustomerApiController::class, 'index']);
    Route::get('customers/{id}', [CustomerApiController::class, 'show']);
    Route::post('customers', [CustomerApiController::class, 'store']);
    Route::put('customers/{id}', [CustomerApiController::class, 'update']);
    Route::delete('customers/{id}', [CustomerApiController::class, 'destroy']);
    Route::get('customers/{id}/ledger', [CustomerApiController::class, 'ledger']);
    Route::get('customers/{id}/dues', [CustomerApiController::class, 'dues']);

    // Customer Groups
    Route::get('customer-groups', [CustomerGroupApiController::class, 'index']);
    Route::post('customer-groups', [CustomerGroupApiController::class, 'store']);
    Route::put('customer-groups/{id}', [CustomerGroupApiController::class, 'update']);
    Route::delete('customer-groups/{id}', [CustomerGroupApiController::class, 'destroy']);

    // Suppliers
    Route::get('suppliers', [SupplierApiController::class, 'index']);
    Route::get('suppliers/{id}', [SupplierApiController::class, 'show']);
    Route::post('suppliers', [SupplierApiController::class, 'store']);
    Route::put('suppliers/{id}', [SupplierApiController::class, 'update']);
    Route::delete('suppliers/{id}', [SupplierApiController::class, 'destroy']);
    Route::get('suppliers/{id}/ledger', [SupplierApiController::class, 'ledger']);
    Route::get('supplier-groups', [SupplierApiController::class, 'groups']);
    Route::post('supplier-groups', [SupplierApiController::class, 'storeGroup']);

    // Payments
    Route::get('payments', [PaymentApiController::class, 'index']);
    Route::get('payments/{id}', [PaymentApiController::class, 'show']);
    Route::post('payments/receive', [PaymentApiController::class, 'receive']);
    Route::post('payments/make', [PaymentApiController::class, 'make']);
    Route::get('payments/outstanding-invoices', [PaymentApiController::class, 'outstandingInvoices']);
    Route::get('payment-accounts', [PaymentApiController::class, 'accounts']);
    Route::post('payment-accounts/transfer', [PaymentApiController::class, 'transfer']);

    // Purchases
    Route::get('purchases/form-options', [PurchaseApiController::class, 'formOptions']);
    Route::get('purchases', [PurchaseApiController::class, 'index']);
    Route::get('purchases/{id}', [PurchaseApiController::class, 'show']);
    Route::post('purchases', [PurchaseApiController::class, 'store']);
    Route::put('purchases/{id}', [PurchaseApiController::class, 'update']);
    Route::post('purchases/{id}/approve', [PurchaseApiController::class, 'approve']);
    Route::post('purchases/{id}/cancel', [PurchaseApiController::class, 'cancel']);
    Route::get('purchases/{id}/grn', [PurchaseApiController::class, 'grnList']);
    Route::post('purchases/{id}/grn', [PurchaseApiController::class, 'createGrn']);

    // Quotations
    Route::get('quotations', [QuotationApiController::class, 'index']);
    Route::get('quotations/{id}', [QuotationApiController::class, 'show']);
    Route::post('quotations', [QuotationApiController::class, 'store']);
    Route::put('quotations/{id}', [QuotationApiController::class, 'update']);
    Route::post('quotations/{id}/convert', [QuotationApiController::class, 'convertToSale']);
    Route::delete('quotations/{id}', [QuotationApiController::class, 'destroy']);

    // Expenses
    Route::get('expenses/form-options', [ExpenseApiController::class, 'formOptions']);
    Route::get('expenses', [ExpenseApiController::class, 'index']);
    Route::get('expenses/{id}', [ExpenseApiController::class, 'show']);
    Route::post('expenses', [ExpenseApiController::class, 'store']);
    Route::put('expenses/{id}', [ExpenseApiController::class, 'update']);
    Route::post('expenses/{id}/approve', [ExpenseApiController::class, 'approve']);
    Route::post('expenses/{id}/reject', [ExpenseApiController::class, 'reject']);
    Route::get('expense-categories', [ExpenseApiController::class, 'categories']);
    Route::post('expense-categories', [ExpenseApiController::class, 'storeCategory']);

    // Inventory
    Route::get('inventory/stock', [InventoryApiController::class, 'stockLevels']);
    Route::get('inventory/stock/{productId}', [InventoryApiController::class, 'productStock']);
    Route::get('inventory/adjustments', [InventoryApiController::class, 'adjustmentList']);
    Route::get('inventory/adjustments/{id}', [InventoryApiController::class, 'adjustmentShow']);
    Route::post('inventory/adjustments', [InventoryApiController::class, 'adjustmentStore']);
    Route::post('inventory/adjustments/{id}/approve', [InventoryApiController::class, 'adjustmentApprove']);
    Route::get('inventory/low-stock', [InventoryApiController::class, 'lowStock']);

    // Sales
    Route::get('sales', [SaleApiController::class, 'index']);
    Route::get('sales/{id}', [SaleApiController::class, 'show']);

    // Dashboard
    Route::get('dashboard', [DashboardApiController::class, 'index']);
    Route::get('dashboard/sales-trend', [DashboardApiController::class, 'salesTrend']);
    Route::get('dashboard/payment-breakdown', [DashboardApiController::class, 'paymentBreakdown']);
    Route::get('dashboard/top-products', [DashboardApiController::class, 'topProducts']);
    Route::get('dashboard/expense-summary', [DashboardApiController::class, 'expenseSummary']);
    Route::get('dashboard/purchase-summary', [DashboardApiController::class, 'purchaseSummary']);
    Route::get('dashboard/cash-flow', [DashboardApiController::class, 'cashFlow']);
    Route::get('dashboard/receivables', [DashboardApiController::class, 'receivables']);
    Route::get('dashboard/payables', [DashboardApiController::class, 'payables']);
    Route::get('dashboard/inventory-summary', [DashboardApiController::class, 'inventorySummary']);

    // Notifications
    Route::get('notifications', [NotificationApiController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationApiController::class, 'unreadCount']);
    Route::post('notifications/{id}/read', [NotificationApiController::class, 'markAsRead']);
    Route::post('notifications/mark-all-read', [NotificationApiController::class, 'markAllAsRead']);

    // Phase 6: HR — Employees
    Route::get('employees', [EmployeeApiController::class, 'index']);
    Route::get('employees/{id}', [EmployeeApiController::class, 'show']);
    Route::post('employees', [EmployeeApiController::class, 'store']);
    Route::put('employees/{id}', [EmployeeApiController::class, 'update']);
    Route::delete('employees/{id}', [EmployeeApiController::class, 'destroy']);

    // Phase 6: HR — Attendance
    Route::get('attendance', [AttendanceApiController::class, 'index']);
    Route::post('attendance', [AttendanceApiController::class, 'store']);
    Route::post('attendance/bulk', [AttendanceApiController::class, 'bulkStore']);
    Route::get('attendance/summary', [AttendanceApiController::class, 'summary']);
    Route::get('leaves', [AttendanceApiController::class, 'leaveList']);
    Route::post('leaves', [AttendanceApiController::class, 'leaveStore']);
    Route::post('leaves/{id}/approve', [AttendanceApiController::class, 'leaveApprove']);
    Route::post('leaves/{id}/reject', [AttendanceApiController::class, 'leaveReject']);

    // Phase 6: HR — Payroll
    Route::get('payrolls', [PayrollApiController::class, 'index']);
    Route::get('payrolls/{id}', [PayrollApiController::class, 'show']);
    Route::post('payrolls', [PayrollApiController::class, 'store']);
    Route::post('payrolls/{id}/approve', [PayrollApiController::class, 'approve']);
    Route::post('payrolls/{id}/pay', [PayrollApiController::class, 'pay']);
    Route::get('salary-structures', [PayrollApiController::class, 'salaryStructures']);

    // Phase 7: Accounting
    Route::get('accounts', [AccountingApiController::class, 'accounts']);
    Route::get('accounts/{id}', [AccountingApiController::class, 'accountShow']);
    Route::get('accounts/{id}/ledger', [AccountingApiController::class, 'accountLedger']);
    Route::get('journal-entries', [AccountingApiController::class, 'journalList']);
    Route::get('journal-entries/{id}', [AccountingApiController::class, 'journalShow']);
    Route::post('journal-entries', [AccountingApiController::class, 'journalStore']);
    Route::get('credit-notes', [AccountingApiController::class, 'creditNoteList']);
    Route::get('credit-notes/{id}', [AccountingApiController::class, 'creditNoteShow']);
    Route::post('credit-notes', [AccountingApiController::class, 'creditNoteStore']);
    Route::get('debit-notes', [AccountingApiController::class, 'debitNoteList']);
    Route::post('debit-notes', [AccountingApiController::class, 'debitNoteStore']);

    // Phase 8: Reports
    Route::get('reports/sales', [ReportApiController::class, 'sales']);
    Route::get('reports/sales-by-product', [ReportApiController::class, 'salesByProduct']);
    Route::get('reports/sales-by-customer', [ReportApiController::class, 'salesByCustomer']);
    Route::get('reports/purchases', [ReportApiController::class, 'purchases']);
    Route::get('reports/inventory-valuation', [ReportApiController::class, 'inventoryValuation']);
    Route::get('reports/profit-loss', [ReportApiController::class, 'profitAndLoss']);
    Route::get('reports/expense', [ReportApiController::class, 'expenseReport']);
    Route::get('reports/receivables-aging', [ReportApiController::class, 'receivablesAging']);
    Route::get('reports/payables-aging', [ReportApiController::class, 'payablesAging']);
    Route::get('reports/tax', [ReportApiController::class, 'taxReport']);

    // Phase 9: Settings & Admin
    Route::get('branches', [SettingsApiController::class, 'branches']);
    Route::post('branches', [SettingsApiController::class, 'storeBranch']);
    Route::put('branches/{id}', [SettingsApiController::class, 'updateBranch']);
    Route::get('users', [SettingsApiController::class, 'users']);
    Route::get('users/{id}', [SettingsApiController::class, 'userShow']);
    Route::post('users', [SettingsApiController::class, 'storeUser']);
    Route::put('users/{id}', [SettingsApiController::class, 'updateUser']);
    Route::get('roles', [SettingsApiController::class, 'roles']);
    Route::post('roles', [SettingsApiController::class, 'storeRole']);
    Route::put('roles/{id}', [SettingsApiController::class, 'updateRole']);
    Route::get('permissions', [SettingsApiController::class, 'permissions']);
    Route::get('settings/general', [SettingsApiController::class, 'generalSettings']);
    Route::put('settings/general', [SettingsApiController::class, 'updateGeneralSettings']);

    // Phase 10: E-commerce
    Route::get('ecommerce/dashboard', [EcommerceApiController::class, 'dashboard']);
    Route::get('ecommerce/orders', [EcommerceApiController::class, 'orderList']);
    Route::get('ecommerce/orders/{id}', [EcommerceApiController::class, 'orderShow']);
    Route::put('ecommerce/orders/{id}/status', [EcommerceApiController::class, 'updateOrderStatus']);
    Route::get('ecommerce/coupons', [EcommerceApiController::class, 'couponList']);
    Route::post('ecommerce/coupons', [EcommerceApiController::class, 'couponStore']);
    Route::put('ecommerce/coupons/{id}', [EcommerceApiController::class, 'couponUpdate']);
    Route::delete('ecommerce/coupons/{id}', [EcommerceApiController::class, 'couponDestroy']);
    Route::get('ecommerce/banners', [EcommerceApiController::class, 'bannerList']);
    Route::post('ecommerce/banners', [EcommerceApiController::class, 'bannerStore']);
    Route::put('ecommerce/banners/{id}', [EcommerceApiController::class, 'bannerUpdate']);
    Route::delete('ecommerce/banners/{id}', [EcommerceApiController::class, 'bannerDestroy']);
});
