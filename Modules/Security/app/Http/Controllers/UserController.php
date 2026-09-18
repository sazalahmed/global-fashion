<?php

namespace Modules\Security\Http\Controllers;

use App\Helpers\Upload;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Modules\Security\Services\TwoFactorService;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        bpAuthorize('users.view');
        $query = User::with('roles')->excludingSuperAdmin();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->input('role'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $users = $query->latest()->paginate(15);
        $roles = $this->assignableRoles();

        return view('security::users.index', [
            'users'       => $users,
            'roles'       => $roles,
            'totalUsers'  => User::excludingSuperAdmin()->count(),
            'activeUsers' => User::excludingSuperAdmin()->where('status', 'active')->count(),
            'totalRoles'  => $roles->count(),
            'newThisMonth'=> User::excludingSuperAdmin()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        bpAuthorize('users.create');
        return view('security::users.create', [
            'roles' => $this->assignableRoles(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        bpAuthorize('users.create');
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email',
            'phone'     => 'nullable|string|max:20',
            'password'  => 'required|string|min:8|confirmed',
            'role'      => ['required', 'string', 'exists:roles,name', Rule::notIn([User::SUPER_ADMIN_ROLE])],
            'branch_id' => 'nullable|integer',
            'status'    => 'required|string|in:active,inactive',
            'image'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'phone'     => $validated['phone'] ?? null,
            'password'  => Hash::make($validated['password']),
            'branch_id' => $validated['branch_id'] ?? null,
            'status'    => $validated['status'],
        ]);

        if ($request->hasFile('image')) {
            $user->update([
                'image' => \App\Helpers\Upload::store($request->file('image'), 'users'),
            ]);
        }

        $user->assignRole($validated['role']);

        return redirect()->route('security.users.index')->with('success', __('User created successfully.'));
    }

    /**
     * Self-service "Edit Profile" form for the authenticated user.
     * Updates profile fields only (name, email, phone, image) — never roles or
     * status, so the super admin can safely edit their own profile without any
     * demotion/lockout risk.
     */
    public function editProfile()
    {
        return view('security::users.profile-edit', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update the authenticated user's own profile fields.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if ($request->hasFile('image')) {
            $user->update([
                'image' => \App\Helpers\Upload::store($request->file('image'), 'users'),
            ]);
        } elseif ($request->boolean('remove_image')) {
            $user->update(['image' => null]);
        }

        return redirect()
            ->route('security.users.show', $user->id)
            ->with('success', __('Profile updated successfully.'));
    }

    /**
     * Self-service "Change Password" form for the authenticated user.
     * Password-only — never touches roles/status, so it is safe even for the
     * super admin (no demotion/lockout risk).
     */
    public function changePassword()
    {
        return view('security::users.change-password', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update only the authenticated user's password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => __('The current password is incorrect.'),
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return redirect()
            ->route('security.change-password')
            ->with('success', __('Password changed successfully.'));
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        bpAuthorize('users.view');
        $user = User::with('roles', 'permissions')->findOrFail($id);
        // A user may always view their own profile (e.g. header "My Profile"),
        // even the super admin; other super admins stay hidden.
        $this->guardSuperAdmin($user, allowSelf: true);

        return view('security::users.show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($id)
    {
        bpAuthorize('users.edit');
        $user = User::with('roles')->findOrFail($id);
        $this->guardSuperAdmin($user);

        return view('security::users.create', [
            'user'  => $user,
            'roles' => $this->assignableRoles(),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        bpAuthorize('users.edit');
        $user = User::findOrFail($id);
        $this->guardSuperAdmin($user);

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email,' . $id,
            'phone'     => 'nullable|string|max:20',
            'password'  => 'nullable|string|min:8|confirmed',
            'role'      => ['required', 'string', 'exists:roles,name', Rule::notIn([User::SUPER_ADMIN_ROLE])],
            'branch_id' => 'nullable|integer',
            'status'    => 'required|string|in:active,inactive',
            'image'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user->update([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'phone'     => $validated['phone'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'status'    => $validated['status'],
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        if ($request->hasFile('image')) {
            $user->update([
                'image' => \App\Helpers\Upload::store($request->file('image'), 'users'),
            ]);
        }

        $user->syncRoles([$validated['role']]);

        return redirect()->route('security.users.show', $user->id)->with('success', __('User updated successfully.'));
    }

    /**
     * Remove the specified user (soft delete).
     */
    public function destroy($id)
    {
        bpAuthorize('users.delete');
        $user = User::findOrFail($id);
        $this->guardSuperAdmin($user);

        if ($user->id === auth()->id()) {
            return redirect()->route('security.users.index')->with('error', __('You cannot delete your own account.'));
        }

        $user->delete();

        return redirect()->route('security.users.index')->with('success', __('User deleted successfully.'));
    }

    /**
     * Toggle user status (active/inactive) via AJAX.
     */
    public function toggleStatus(Request $request, $id)
    {
        bpAuthorize('users.edit');
        $user = User::findOrFail($id);
        $this->guardSuperAdmin($user);

        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'You cannot change your own status.'], 403);
        }

        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
        ]);

        return response()->json([
            'success' => true,
            'status'  => $user->status,
            'message' => 'User status updated.',
        ]);
    }

    /**
     * Clear a user's Two-Factor enrollment (e.g. they lost their device).
     * The user falls back to SMS reset and may re-enroll themselves.
     */
    public function resetTwoFactor($id, TwoFactorService $twoFactor)
    {
        bpAuthorize('users.edit');
        $user = User::findOrFail($id);
        $this->guardSuperAdmin($user);

        $twoFactor->disable($user);

        return redirect()->route('security.users.edit', $user->id)
            ->with('success', __('Two-Factor Authentication has been reset for this user.'));
    }

    /**
     * Roles that may be assigned/filtered in the UI — never the Super Admin role.
     */
    private function assignableRoles()
    {
        return Role::where('guard_name', 'web')
            ->where('name', '!=', User::SUPER_ADMIN_ROLE)
            ->get();
    }

    /**
     * Block any management action against a protected Super Admin account.
     */
    private function guardSuperAdmin(User $user, bool $allowSelf = false): void
    {
        // The currently authenticated user may act on their own record when
        // $allowSelf is set (used for self-service screens like "My Profile").
        if ($allowSelf && $user->id === auth()->id()) {
            return;
        }

        abort_if($user->isSuperAdmin(), 404);
    }
}
