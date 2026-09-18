<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes on columns used by the global search feature.
     */
    public function up(): void
    {
        $indexes = [
            'products'           => ['sku', 'barcode', 'name'],
            'sales'              => ['invoice_number', 'reference_number'],
            'purchases'          => ['po_number'],
            'customers'          => ['phone', 'name'],
            'suppliers'          => ['company_name', 'phone'],
            'quotations'         => ['quotation_number'],
            'employees'          => ['employee_id', 'name'],
            'sale_returns'       => ['return_number'],
            'purchase_returns'   => ['return_number'],
            'accounts'           => ['account_code', 'account_name'],
            'expenses'           => ['expense_number'],
            'payment_receipts'   => ['receipt_number'],
            'stock_adjustments'  => ['adjustment_number'],
            'stock_transfers'    => ['transfer_number'],
            'assets'             => ['asset_code', 'name'],
            'loans'              => ['loan_number'],
            'production_orders'  => ['po_number'],
            'journal_entries'    => ['entry_number'],
            'delivery_challans'  => ['challan_number'],
            'installment_plans'  => ['plan_number'],
            'ecommerce_orders'   => ['order_number'],
            'ad_campaigns'       => ['ad_number', 'campaign_name'],
        ];

        foreach ($indexes as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            // Snapshot existing indexes for this table — Schema::table closure
            // queues operations and runs them in one ALTER, so a try/catch
            // inside the closure cannot catch a duplicate-index error.
            $existing = $this->existingIndexNames($table);

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns, $existing) {
                foreach ($columns as $column) {
                    if (!Schema::hasColumn($table, $column)) {
                        continue;
                    }

                    $indexName = "{$table}_{$column}_index";
                    if (in_array($indexName, $existing, true)) {
                        continue;
                    }

                    $blueprint->index($column, $indexName);
                }
            });
        }
    }

    /**
     * Return all index names defined on a table (lower-cased).
     */
    private function existingIndexNames(string $table): array
    {
        try {
            $rows = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM `{$table}`");
            return array_values(array_unique(array_map(
                fn ($row) => strtolower($row->Key_name ?? ''),
                $rows
            )));
        } catch (\Throwable $e) {
            // Non-MySQL drivers — fall back to Laravel 11+ schema introspection.
            try {
                return array_map(
                    fn ($idx) => strtolower($idx['name'] ?? ''),
                    Schema::getIndexes($table)
                );
            } catch (\Throwable $e2) {
                return [];
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'products'           => ['sku', 'barcode', 'name'],
            'sales'              => ['invoice_number', 'reference_number'],
            'purchases'          => ['po_number'],
            'customers'          => ['phone', 'name'],
            'suppliers'          => ['company_name', 'phone'],
            'quotations'         => ['quotation_number'],
            'employees'          => ['employee_id', 'name'],
            'sale_returns'       => ['return_number'],
            'purchase_returns'   => ['return_number'],
            'accounts'           => ['account_code', 'account_name'],
            'expenses'           => ['expense_number'],
            'payment_receipts'   => ['receipt_number'],
            'stock_adjustments'  => ['adjustment_number'],
            'stock_transfers'    => ['transfer_number'],
            'assets'             => ['asset_code', 'name'],
            'loans'              => ['loan_number'],
            'production_orders'  => ['po_number'],
            'journal_entries'    => ['entry_number'],
            'delivery_challans'  => ['challan_number'],
            'installment_plans'  => ['plan_number'],
            'ecommerce_orders'   => ['order_number'],
            'ad_campaigns'       => ['ad_number', 'campaign_name'],
        ];

        foreach ($indexes as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    $indexName = "{$table}_{$column}_index";
                    try {
                        $blueprint->dropIndex($indexName);
                    } catch (\Exception $e) {
                        // Index doesn't exist
                    }
                }
            });
        }
    }
};
