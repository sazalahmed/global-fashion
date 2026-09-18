<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Display Type is reduced to two options: "button" (default) and
 * "color_swatch". The legacy "dropdown" and "radio" types always rendered
 * identically to a button on the storefront, so collapse them into "button".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('variant_attributes')
            ->whereIn('display_type', ['dropdown', 'radio'])
            ->update(['display_type' => 'button']);
    }

    public function down(): void
    {
        // Irreversible: the original dropdown/radio distinction is not retained
        // (both were visually equivalent to button). Nothing to roll back.
    }
};
