<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Widen the enum first so existing 'sent' rows can be converted.
        DB::statement("ALTER TABLE quotations MODIFY status ENUM('draft', 'sent', 'pending', 'accepted', 'rejected', 'expired', 'converted') NOT NULL DEFAULT 'pending'");
        DB::table('quotations')->where('status', 'sent')->update(['status' => 'pending']);
        DB::statement("ALTER TABLE quotations MODIFY status ENUM('draft', 'pending', 'accepted', 'rejected', 'expired', 'converted') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE quotations MODIFY status ENUM('draft', 'sent', 'pending', 'accepted', 'rejected', 'expired', 'converted') NOT NULL DEFAULT 'draft'");
        DB::table('quotations')->where('status', 'pending')->update(['status' => 'sent']);
        DB::statement("ALTER TABLE quotations MODIFY status ENUM('draft', 'sent', 'accepted', 'rejected', 'expired', 'converted') NOT NULL DEFAULT 'draft'");
    }
};
