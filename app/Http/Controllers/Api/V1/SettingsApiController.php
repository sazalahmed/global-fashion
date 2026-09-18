<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Modules\Branch\Models\Branch;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SettingsApiController extends BaseApiController
{
    // Branches
    public function branches(): JsonResponse
    {
        return $this->success(Branch::orderBy('name')->get());
    }

    public function storeBranch(Request $request): JsonResponse
    {
        $v = $request->validate(['name' => 'required|string|max:255', 'phone' => 'nullable|string|max:20', 'email' => 'nullable|email', 'address' => 'nullable|string', 'is_active' => 'nullable|boolean']);
        $v['code'] = 'BR-' . str_pad(Branch::count() + 1, 3, '0', STR_PAD_LEFT);
        return $this->success(Branch::create($v), 'Branch created', 201);
    }

    public function updateBranch(Request $request, int $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);
        $branch->update($request->validate(['name' => 'sometimes|string|max:255', 'phone' => 'nullable|string', 'email' => 'nullable|email', 'address' => 'nullable|string', 'is_active' => 'nullable|boolean']));
        return $this->success($branch->fresh());
    }

    // Users
    public function users(Request $request): JsonResponse
    {
        $users = User::with('roles')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            ->latest()->paginate($request->input('per_page', 15));
        return $this->paginatedSuccess($users);
    }

    public function userShow(int $id): JsonResponse
    {
        return $this->success(User::with('roles.permissions')->findOrFail($id));
    }

    public function storeUser(Request $request): JsonResponse
    {
        $v = $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|unique:users', 'password' => 'required|string|min:8', 'phone' => 'nullable|string', 'branch_id' => 'nullable|integer', 'role' => 'nullable|string']);
        $user = User::create(['name' => $v['name'], 'email' => $v['email'], 'password' => bcrypt($v['password']), 'phone' => $v['phone'] ?? null, 'branch_id' => $v['branch_id'] ?? null, 'status' => 'active']);
        if (!empty($v['role'])) { $user->assignRole($v['role']); }
        return $this->success($user->load('roles'), 'User created', 201);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $v = $request->validate(['name' => 'sometimes|string|max:255', 'email' => 'sometimes|email|unique:users,email,' . $id, 'phone' => 'nullable|string', 'branch_id' => 'nullable|integer', 'status' => 'nullable|in:active,inactive', 'role' => 'nullable|string']);
        $user->update(collect($v)->except('role')->toArray());
        if (isset($v['role'])) { $user->syncRoles([$v['role']]); }
        return $this->success($user->fresh()->load('roles'));
    }

    // Roles & Permissions
    public function roles(): JsonResponse
    {
        return $this->success(Role::withCount('users')->with('permissions:id,name')->get());
    }

    public function storeRole(Request $request): JsonResponse
    {
        $v = $request->validate(['name' => 'required|string|max:255|unique:roles', 'permissions' => 'nullable|array', 'permissions.*' => 'string|exists:permissions,name']);
        $role = Role::create(['name' => $v['name'], 'guard_name' => 'web']);
        if (!empty($v['permissions'])) { $role->syncPermissions($v['permissions']); }
        return $this->success($role->load('permissions'), 'Role created', 201);
    }

    public function updateRole(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $v = $request->validate(['name' => 'sometimes|string|max:255', 'permissions' => 'nullable|array', 'permissions.*' => 'string']);
        if (isset($v['name'])) { $role->update(['name' => $v['name']]); }
        if (isset($v['permissions'])) { $role->syncPermissions($v['permissions']); }
        return $this->success($role->fresh()->load('permissions'));
    }

    public function permissions(): JsonResponse
    {
        $perms = Permission::all()->groupBy(fn ($p) => explode('.', $p->name)[0]);
        return $this->success($perms);
    }

    // General Settings
    public function generalSettings(): JsonResponse
    {
        $settings = \Modules\Setting\Models\Setting::pluck('value', 'key');
        return $this->success($settings);
    }

    public function updateGeneralSettings(Request $request): JsonResponse
    {
        foreach ($request->all() as $key => $value) {
            \Modules\Setting\Models\Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        return $this->success(null, 'Settings updated');
    }
}
