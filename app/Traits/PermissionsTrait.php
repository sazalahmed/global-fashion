<?php

namespace App\Traits;

trait PermissionsTrait
{
    /**
     * All permission groups with their permissions for BizPOS Pro.
     * Format: 'group_name' => ['permission.action', ...]
     */
    public static function permissionGroups(): array
    {
        return [
            'dashboard' => ['dashboard.view'],
            'products' => ['products.view', 'products.create', 'products.edit', 'products.delete', 'products.export'],
            'categories' => ['categories.view', 'categories.create', 'categories.edit', 'categories.delete'],
            'brands' => ['brands.view', 'brands.create', 'brands.edit', 'brands.delete', 'brands.export'],
            'units' => ['units.view', 'units.create', 'units.edit', 'units.delete'],
            'variants' => ['variants.view', 'variants.create', 'variants.edit', 'variants.delete'],
            'barcode' => ['barcode.view', 'barcode.generate'],
            'inventory' => ['inventory.view', 'inventory.create', 'inventory.edit', 'inventory.delete', 'inventory.export'],
            'purchases' => ['purchases.view', 'purchases.create', 'purchases.edit', 'purchases.delete', 'purchases.approve', 'purchases.export'],
            'sales' => ['sales.view', 'sales.create', 'sales.edit', 'sales.delete', 'sales.export'],
            'quotations' => ['quotations.view', 'quotations.create', 'quotations.edit', 'quotations.delete', 'quotations.export'],
            'customers' => ['customers.view', 'customers.create', 'customers.edit', 'customers.delete', 'customers.export'],
            'suppliers' => ['suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete', 'suppliers.export'],
            'payments' => ['payments.view', 'payments.create', 'payments.delete', 'payments.export'],
            'finance' => ['finance.view', 'finance.create', 'finance.edit', 'finance.delete', 'finance.export'],
            'accounting' => ['accounting.view', 'accounting.create', 'accounting.edit', 'accounting.delete', 'accounting.export'],
            'reports' => ['reports.view', 'reports.export'],
            'ecommerce' => ['ecommerce.view', 'ecommerce.create', 'ecommerce.edit', 'ecommerce.delete'],
            'marketing' => ['marketing.view', 'marketing.create', 'marketing.edit', 'marketing.delete'],
            'hr' => ['hr.view', 'hr.create', 'hr.edit', 'hr.delete', 'hr.export'],
            'manufacturing' => ['manufacturing.view', 'manufacturing.create', 'manufacturing.edit', 'manufacturing.delete'],
            'locations' => ['locations.view', 'locations.create', 'locations.edit', 'locations.delete'],
            'activities' => ['activities.view', 'activities.delete'],
            'users' => ['users.view', 'users.create', 'users.edit', 'users.delete'],
            'roles' => ['roles.view', 'roles.create', 'roles.edit', 'roles.delete'],
            'settings' => ['settings.view', 'settings.edit'],
        ];
    }

    /**
     * Get all unique permission group names.
     */
    public static function getPermissionGroups(): array
    {
        return array_keys(static::permissionGroups());
    }

    /**
     * Get permissions by group name.
     */
    public static function getPermissionsByGroup(string $groupName): array
    {
        return static::permissionGroups()[$groupName] ?? [];
    }

    /**
     * Get all permissions as a flat array.
     */
    public static function getAllDefinedPermissions(): array
    {
        $permissions = [];
        foreach (static::permissionGroups() as $group) {
            $permissions = array_merge($permissions, $group);
        }
        return $permissions;
    }
}
