<?php

namespace Modules\Security\Database\Seeders;

use App\Traits\PermissionsTrait;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    use PermissionsTrait;

    /**
     * Rebuild the RBAC catalog from scratch.
     *
     * Truncates the permission/role tables for a clean, duplicate-free state
     * (safe to run repeatedly on a server). To avoid locking anyone out, every
     * existing user->role assignment is captured BEFORE the truncate and
     * restored afterwards by role NAME (robust to role id changes).
     */
    public function run(): void
    {
        // Capture existing user->role assignments (by role name) so the reset
        // below never strips users — including the super admin — of their roles.
        $priorAssignments = DB::table('model_has_roles as mhr')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->get(['mhr.model_id', 'mhr.model_type', 'r.name', 'r.guard_name']);

        // Clean reset of all RBAC tables (no duplicates, deterministic).
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        Permission::truncate();
        Role::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Create all catalog permissions, grouped.
        foreach (static::permissionGroups() as $group => $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(
                    ['name' => $permission, 'guard_name' => 'web'],
                    ['group_name' => $group]
                );
            }
        }

        // 2. Super Admin — all permissions (Gate::before also covers it).
        $superAdmin = Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web'],
            ['description' => 'Full system access with all permissions', 'is_default' => false]
        );
        $superAdmin->syncPermissions(Permission::where('guard_name', 'web')->get());

        // 3. Manager — operations + reports, no system/users/roles/settings.
        $manager = Role::firstOrCreate(
            ['name' => 'Manager', 'guard_name' => 'web'],
            ['description' => 'Branch management, reports, and staff oversight', 'is_default' => false]
        );
        $manager->syncPermissions(Permission::where('guard_name', 'web')->whereIn('group_name', [
            'dashboard', 'products', 'categories', 'brands', 'units', 'variants', 'barcode',
            'inventory', 'purchases', 'sales', 'quotations', 'customers', 'suppliers',
            'payments', 'finance', 'hr', 'reports', 'manufacturing',
        ])->get());

        // 4. Cashier — POS-less sales floor: sell, view products/customers.
        $cashier = Role::firstOrCreate(
            ['name' => 'Cashier', 'guard_name' => 'web'],
            ['description' => 'Sales, basic customer and product view', 'is_default' => true]
        );
        $cashier->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', [
            'dashboard.view', 'products.view', 'sales.view', 'sales.create',
            'customers.view', 'customers.create',
        ])->get());

        // 5. Accountant — money + reports.
        $accountant = Role::firstOrCreate(
            ['name' => 'Accountant', 'guard_name' => 'web'],
            ['description' => 'Finance, accounting, payments, payroll, reports', 'is_default' => false]
        );
        $accountant->syncPermissions(Permission::where('guard_name', 'web')->whereIn('group_name', [
            'dashboard', 'payments', 'finance', 'accounting', 'reports', 'hr',
        ])->get());

        // 6. Inventory Staff — catalog + stock + purchasing.
        $inventoryStaff = Role::firstOrCreate(
            ['name' => 'Inventory Staff', 'guard_name' => 'web'],
            ['description' => 'Inventory, stock adjustments, receiving', 'is_default' => false]
        );
        $inventoryStaff->syncPermissions(Permission::where('guard_name', 'web')->whereIn('group_name', [
            'dashboard', 'products', 'categories', 'brands', 'units', 'variants', 'barcode',
            'inventory', 'purchases',
        ])->get());

        // 7. Sales Rep — customer + quotation + order processing.
        $salesRep = Role::firstOrCreate(
            ['name' => 'Sales Rep', 'guard_name' => 'web'],
            ['description' => 'Customers, quotations, order processing', 'is_default' => false]
        );
        $salesRep->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', [
            'dashboard.view', 'products.view',
            'sales.view', 'sales.create', 'sales.edit',
            'quotations.view', 'quotations.create', 'quotations.edit',
            'customers.view', 'customers.create', 'customers.edit',
        ])->get());

        // 8. Restore the captured user->role assignments by role name. Roles
        // not recreated by this seeder (legacy custom roles) are skipped.
        foreach ($priorAssignments as $assignment) {
            $role = Role::where('name', $assignment->name)
                ->where('guard_name', $assignment->guard_name)
                ->first();

            if ($role) {
                DB::table('model_has_roles')->insert([
                    'role_id'    => $role->id,
                    'model_type' => $assignment->model_type,
                    'model_id'   => $assignment->model_id,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
