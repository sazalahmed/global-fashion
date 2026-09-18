<?php

namespace App\Http\Controllers\Concerns;

trait ResolvesModulePermission
{
    /**
     * Map a {module} route segment to its permission group.
     * Folds umbrella sub-modules into their parent group.
     */
    protected function permissionGroupForModule(string $module): string
    {
        $map = [
            'sale' => 'sales', 'sales' => 'sales', 'sale-returns' => 'sales',
            'purchase' => 'purchases', 'purchases' => 'purchases', 'purchase-returns' => 'purchases',
            'customer' => 'customers', 'customers' => 'customers',
            'supplier' => 'suppliers', 'suppliers' => 'suppliers', 'payables' => 'suppliers',
            'product' => 'products', 'products' => 'products',
            'expense' => 'finance', 'expenses' => 'finance', 'expense-ledger' => 'finance',
            'asset' => 'finance', 'assets' => 'finance',
            'loan' => 'finance', 'loans' => 'finance', 'personal-loan-ledger' => 'finance',
            'employee' => 'hr', 'employees' => 'hr', 'attendance' => 'hr', 'payroll' => 'hr',
            'inventory' => 'inventory', 'quotation' => 'quotations', 'quotations' => 'quotations',
            'payment' => 'payments', 'payments' => 'payments',
        ];

        return $map[$module] ?? str_replace('-', '_', $module);
    }
}
