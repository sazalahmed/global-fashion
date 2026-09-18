<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for entities added to the global search scope after the initial
 * search-index migration: Payment, Lender, CustomerGroup, SupplierGroup.
 * Mirrors the defensive approach of 2026_04_13 add_global_search_indexes.
 */
return new class extends Migration
{
    private array $indexes = [
        'payments'        => ['payment_number', 'reference'],
        'lenders'         => ['name', 'company_name', 'phone'],
        'customer_groups' => ['name'],
        'supplier_groups' => ['name'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

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

    private function existingIndexNames(string $table): array
    {
        try {
            $rows = DB::select("SHOW INDEX FROM `{$table}`");
            return array_values(array_unique(array_map(
                fn ($row) => strtolower($row->Key_name ?? ''),
                $rows
            )));
        } catch (\Throwable $e) {
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

    public function down(): void
    {
        foreach ($this->indexes as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    try {
                        $blueprint->dropIndex("{$table}_{$column}_index");
                    } catch (\Exception $e) {
                        // Index doesn't exist
                    }
                }
            });
        }
    }
};
