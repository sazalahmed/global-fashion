<?php

namespace Modules\Security\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\PermissionsTrait;
use Illuminate\Http\Request;
use Modules\Security\Http\Requests\StoreApiKeyRequest;
use Modules\Security\Models\ApiKey;
use Modules\Security\Services\ApiKeyService;
use Modules\Security\Services\BackupService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SecurityController extends Controller
{
    use PermissionsTrait;

    /**
     * Security module dashboard.
     */
    public function index()
    {
        bpAuthorize('roles.view');
        $stats = [
            // Exclude Super Admins so "Total/Active Users" matches the User
            // Management list (which hides Super Admins) — Super Admins are
            // surfaced as their own stat elsewhere.
            'users'      => User::excludingSuperAdmin()->count(),
            'roles'      => Role::where('guard_name', 'web')->count(),
            'api_keys'   => ApiKey::count(),
            'active_users' => User::excludingSuperAdmin()->where('status', 'active')->count(),
        ];

        return view('security::index', compact('stats'));
    }

    /**
     * Display a listing of roles.
     */
    public function roles(Request $request)
    {
        bpAuthorize('roles.view');
        $query = Role::where('guard_name', 'web')
            ->where('name', '!=', User::SUPER_ADMIN_ROLE)
            ->withCount('permissions', 'users');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $sortable = ['name', 'users_count', 'permissions_count', 'created_at'];
        $sort = in_array($request->input('sort'), $sortable, true) ? $request->input('sort') : 'created_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $roles = $query->orderBy($sort, $direction)->get();

        return view('security::roles', [
            'roles'            => $roles,
            'totalRoles'       => Role::where('guard_name', 'web')->where('name', '!=', User::SUPER_ADMIN_ROLE)->count(),
            // Manageable users only (exclude Super Admins) so this matches the
            // User Management page's Total Users; Super Admins have their own card.
            'totalUsers'       => User::excludingSuperAdmin()->count(),
            'totalPermissions' => Permission::where('guard_name', 'web')->count(),
            'superAdmins'      => User::role('Super Admin')->count(),
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function roleCreate()
    {
        bpAuthorize('roles.create');
        $permissionGroups = static::permissionGroups();

        return view('security::role-create', [
            'permissionGroups' => $permissionGroups,
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function roleStore(Request $request)
    {
        bpAuthorize('roles.create');
        $validated = $request->validate([
            'name'          => 'required|string|max:255|unique:roles,name',
            'description'   => 'nullable|string|max:500',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create([
            'name'        => $validated['name'],
            'guard_name'  => 'web',
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('security.roles')->with('success', __('Role created successfully.'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function roleEdit($id)
    {
        bpAuthorize('roles.edit');
        $role = Role::with('permissions')->findOrFail($id);
        abort_if($role->name === User::SUPER_ADMIN_ROLE, 404);
        $permissionGroups = static::permissionGroups();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('security::role-edit', [
            'role'             => $role,
            'permissionGroups' => $permissionGroups,
            'rolePermissions'  => $rolePermissions,
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function roleUpdate(Request $request, $id)
    {
        bpAuthorize('roles.edit');
        $role = Role::findOrFail($id);
        abort_if($role->name === User::SUPER_ADMIN_ROLE, 404);

        $validated = $request->validate([
            'name'          => 'required|string|max:255|unique:roles,name,' . $id,
            'description'   => 'nullable|string|max:500',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('security.roles')->with('success', __('Role updated successfully.'));
    }

    /**
     * Remove the specified role from storage.
     */
    public function roleDestroy($id)
    {
        bpAuthorize('roles.delete');
        $role = Role::findOrFail($id);

        if ($role->name === 'Super Admin') {
            return redirect()->route('security.roles')->with('error', __('Cannot delete the Super Admin role.'));
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('security.roles')->with('error', __('Cannot delete a role that has users assigned. Reassign them first.'));
        }

        $role->delete();

        return redirect()->route('security.roles')->with('success', __('Role deleted successfully.'));
    }

    /**
     * Display the backup management page.
     */
    public function backup(BackupService $backupService)
    {
        bpAuthorize('users.view');
        $backups = $backupService->list();
        $stats = $backupService->getStats();

        return view('security::backup', compact('backups', 'stats'));
    }

    /**
     * Create a new backup.
     */
    public function backupCreate(Request $request, BackupService $backupService)
    {
        bpAuthorize('users.create');
        $type = $request->input('type', 'database');

        try {
            $backupService->create($type);

            return redirect()->route('security.backup')->with('success', __('Backup created successfully.'));
        } catch (\Throwable $e) {
            return redirect()->route('security.backup')->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Restore from a backup.
     */
    public function backupRestore(Request $request, BackupService $backupService)
    {
        bpAuthorize('users.create');
        $request->validate(['backup_id' => 'required|string']);

        try {
            $backupService->restore($request->input('backup_id'));

            return redirect()->route('security.backup')->with('success', __('Backup restored successfully.'));
        } catch (\Throwable $e) {
            return redirect()->route('security.backup')->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete a backup file.
     */
    public function backupDelete(Request $request, BackupService $backupService)
    {
        bpAuthorize('users.delete');
        $request->validate(['backup_id' => 'required|string']);

        try {
            // basename() strips any path traversal — only a file inside the
            // backups directory can ever be targeted.
            $backupService->delete(basename($request->input('backup_id')));

            return redirect()->route('security.backup')->with('success', __('Backup deleted successfully.'));
        } catch (\Throwable $e) {
            return redirect()->route('security.backup')->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    /**
     * Display a listing of API keys.
     */
    public function apiKeys(ApiKeyService $apiKeyService)
    {
        bpAuthorize('users.view');
        $apiKeys = $apiKeyService->list();
        $stats = $apiKeyService->getStats();

        return view('security::api-keys', compact('apiKeys', 'stats'));
    }

    /**
     * Show the form for creating a new API key.
     */
    public function apiKeyCreate()
    {
        bpAuthorize('users.create');
        return view('security::api-key-create');
    }

    /**
     * Store a newly created API key in storage.
     */
    public function apiKeyStore(StoreApiKeyRequest $request, ApiKeyService $apiKeyService)
    {
        bpAuthorize('users.create');
        [$apiKey, $plainTextKey] = $apiKeyService->create($request->validated());

        return redirect()->route('security.api-keys')
            ->with('success', __('API key created successfully.'))
            ->with('new_api_key', $plainTextKey);
    }

    /**
     * Remove the specified API key from storage.
     */
    public function apiKeyDestroy($key, ApiKeyService $apiKeyService)
    {
        bpAuthorize('users.delete');
        $apiKeyService->delete($key);

        return redirect()->route('security.api-keys')->with('success', __('API key deleted successfully.'));
    }
}
