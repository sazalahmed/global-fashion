<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Combos no longer support a discount — the admin sets the combo price
     * directly. Neutralise any discount on existing combos so their effective
     * price equals the combo price everywhere (storefront, cart, checkout).
     * The legacy columns are intentionally kept (no destructive drop); they are
     * simply forced to neutral values and never written again from the form.
     */
    public function up(): void
    {
        if (! Schema::hasTable('combos')) {
            return;
        }

        DB::table('combos')->update([
            'discount_type'  => 'none',
            'discount_value' => 0,
        ]);
    }

    public function down(): void
    {
        // No-op: the original per-combo discount values are not recoverable.
    }
};
