<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('nid')->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->after('department_id')->constrained('designations')->nullOnDelete();
        });

        // Backfill FK ids from the existing name strings, creating any managed
        // department/designation rows that don't exist yet so no value is lost.
        $this->backfill('employees', 'department', 'department_id', 'departments');
        $this->backfill('employees', 'designation', 'designation_id', 'designations');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['department', 'designation']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('department', 100)->nullable()->after('nid');
            $table->string('designation', 100)->nullable()->after('department');
        });

        // Restore name strings from the FK before dropping the FK columns.
        DB::table('employees')->whereNotNull('department_id')->orderBy('id')->each(function ($emp) {
            $name = DB::table('departments')->where('id', $emp->department_id)->value('name');
            DB::table('employees')->where('id', $emp->id)->update(['department' => $name]);
        });
        DB::table('employees')->whereNotNull('designation_id')->orderBy('id')->each(function ($emp) {
            $name = DB::table('designations')->where('id', $emp->designation_id)->value('name');
            DB::table('employees')->where('id', $emp->id)->update(['designation' => $name]);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('designation_id');
        });
    }

    private function backfill(string $table, string $nameCol, string $idCol, string $refTable): void
    {
        $names = DB::table($table)->whereNotNull($nameCol)->where($nameCol, '!=', '')->distinct()->pluck($nameCol);
        foreach ($names as $name) {
            $id = DB::table($refTable)->where('name', $name)->value('id')
                ?? DB::table($refTable)->insertGetId([
                    'name'       => $name,
                    'is_active'  => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            DB::table($table)->where($nameCol, $name)->update([$idCol => $id]);
        }
    }
};
