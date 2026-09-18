<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\ResolvesModulePermission;

class BulkActionController extends Controller
{
    use ResolvesModulePermission;
    /**
     * Module configuration: model class + service class + action method map.
     */
    private function getModuleConfig(string $module): ?array
    {
        $map = [
            'sales' => [
                'model'   => \Modules\Sale\Models\Sale::class,
                'service' => \Modules\Sale\Services\SaleService::class,
                'actions' => [
                    'delete'    => 'deleteSale',
                    'cancelled' => 'cancelSale',
                ],
            ],
            'purchases' => [
                'model'   => \Modules\Purchase\Models\Purchase::class,
                'service' => \Modules\Purchase\Services\PurchaseService::class,
                'actions' => [
                    'delete'    => 'delete',
                    'approved'  => ['approve', 'withUserId'],
                    'cancelled' => 'cancel',
                ],
            ],
            'sale-returns' => [
                'model'   => \Modules\SaleReturn\Models\SaleReturn::class,
                'service' => \Modules\SaleReturn\Services\SaleReturnService::class,
                'actions' => [
                    'approved'  => 'approve',
                    'cancelled' => 'cancel',
                ],
            ],
            'purchase-returns' => [
                'model'   => \Modules\PurchaseReturn\Models\PurchaseReturn::class,
                'service' => \Modules\PurchaseReturn\Services\PurchaseReturnService::class,
                'actions' => [
                    'cancelled' => 'cancel',
                ],
            ],
            'products' => [
                'model'   => \Modules\Product\Models\Product::class,
                'service' => \Modules\Product\Services\ProductService::class,
                'actions' => [
                    'delete'   => 'delete',
                    'active'   => 'simpleStatus',
                    'inactive' => 'simpleStatus',
                ],
            ],
            'customers' => [
                'model'   => \Modules\Customer\Models\Customer::class,
                'service' => \Modules\Customer\Services\CustomerService::class,
                'actions' => [
                    'delete'   => 'delete',
                    'active'   => 'simpleStatus',
                    'inactive' => 'simpleStatus',
                ],
            ],
            'suppliers' => [
                'model'   => \Modules\Supplier\Models\Supplier::class,
                'service' => \Modules\Supplier\Services\SupplierService::class,
                'actions' => [
                    'delete'   => 'delete',
                    'active'   => 'simpleStatus',
                    'inactive' => 'simpleStatus',
                ],
            ],
            'employees' => [
                'model'   => \Modules\Employee\Models\Employee::class,
                'service' => \Modules\Employee\Services\EmployeeService::class,
                'actions' => [
                    'delete'   => 'delete',
                    'active'   => 'simpleStatus',
                    'inactive' => 'simpleStatus',
                ],
            ],
            'expenses' => [
                'model'   => \Modules\Expense\Models\Expense::class,
                'service' => \Modules\Expense\Services\ExpenseService::class,
                'actions' => [
                    'delete'    => 'delete',
                    'approved'  => 'approve',
                    'cancelled' => 'cancel',
                ],
            ],
            'payments' => [
                'model'   => \Modules\Payment\Models\Payment::class,
                'service' => \Modules\Payment\Services\PaymentService::class,
                'actions' => [
                    'delete' => 'delete',
                ],
            ],
            'payroll' => [
                'model'   => \Modules\Payroll\Models\Payroll::class,
                'service' => \Modules\Payroll\Services\PayrollService::class,
                'actions' => [
                    'delete'    => 'deletePayroll',
                    'approved'  => 'approvePayroll',
                    'cancelled' => 'cancelPayroll',
                ],
            ],
            'quotations' => [
                'model'   => \Modules\Quotation\Models\Quotation::class,
                'actions' => ['delete' => 'softDelete', 'cancelled' => 'simpleStatus'],
            ],
            'stock-adjustments' => [
                'model'   => \Modules\Inventory\Models\StockAdjustment::class,
                'service' => \Modules\Inventory\Services\InventoryService::class,
                'actions' => [
                    'delete'    => 'deleteAdjustment',
                    'approved'  => 'approveAdjustment',
                    'cancelled' => 'cancelAdjustment',
                ],
            ],
        ];

        return $map[$module] ?? null;
    }

    /**
     * Bulk delete selected records via service methods.
     */
    public function bulkDelete(Request $request, string $module)
    {
        bpAuthorize($this->permissionGroupForModule($module) . '.delete');
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $config = $this->getModuleConfig($module);
        if (!$config) {
            return back()->with('error', 'Bulk delete not supported for this module.');
        }

        return $this->processBulk($config, $request->input('ids'), 'delete', 'deleted');
    }

    /**
     * Bulk update status of selected records via service methods.
     */
    public function bulkStatusUpdate(Request $request, string $module)
    {
        bpAuthorize($this->permissionGroupForModule($module) . '.edit');
        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer',
            'status' => 'required|string',
        ]);

        $config = $this->getModuleConfig($module);
        if (!$config) {
            return back()->with('error', 'Bulk action not supported for this module.');
        }

        $status = $request->input('status');
        $statusLabel = ucfirst(str_replace('_', ' ', $status));

        return $this->processBulk($config, $request->input('ids'), $status, "updated to '{$statusLabel}'");
    }

    /**
     * Process bulk action with per-record error handling.
     */
    private function processBulk(array $config, array $ids, string $action, string $verb): \Illuminate\Http\RedirectResponse
    {
        $actionDef = $config['actions'][$action] ?? null;
        $service = isset($config['service']) ? app($config['service']) : null;
        $success = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $record = $config['model']::findOrFail($id);

                if ($actionDef === 'simpleStatus') {
                    $record->update(['status' => $action]);
                } elseif ($actionDef === 'softDelete') {
                    $record->delete();
                } elseif (is_array($actionDef) && ($actionDef[1] ?? null) === 'withUserId') {
                    $service->{$actionDef[0]}($record, auth()->id());
                } elseif ($actionDef && $service) {
                    $service->{$actionDef}($record);
                } elseif ($action === 'delete') {
                    $record->delete();
                } else {
                    $record->update(['status' => $action]);
                }

                $success++;
            } catch (\Throwable $e) {
                $label = $record->invoice_number ?? $record->return_number ?? $record->po_number
                    ?? $record->payment_number ?? $record->adjustment_number ?? $record->transfer_number
                    ?? $record->expense_number ?? $record->payroll_number ?? $record->name ?? "#{$id}";
                $errors[] = "{$label}: {$e->getMessage()}";
            }
        }

        $total = count($ids);
        $message = "{$success} of {$total} record(s) {$verb}.";

        if (!empty($errors)) {
            $message .= ' ' . count($errors) . ' failed: ' . implode('; ', array_slice($errors, 0, 5));
            return back()->with('warning', $message);
        }

        return back()->with('success', $message);
    }
}
