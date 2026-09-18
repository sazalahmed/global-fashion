<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('checkUserHasPermission')) {
    /**
     * Check if the authenticated user has the given permission.
     */
    function checkUserHasPermission(string $permission): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->can($permission);
    }
}

if (!function_exists('checkUserHasRole')) {
    /**
     * Check if the authenticated user has the given role.
     */
    function checkUserHasRole(string $role): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->hasRole($role);
    }
}

if (!function_exists('isSuperAdmin')) {
    /**
     * Check if the authenticated user is a Super Admin.
     */
    function isSuperAdmin(): bool
    {
        return checkUserHasRole('Super Admin');
    }
}

if (!function_exists('bpCanAny')) {
    /**
     * True if the authenticated user has ANY of the given permissions.
     * Used in Blade to decide whether to render a whole menu group.
     */
    function bpCanAny(string ...$permissions): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('bpAuthorize')) {
    /**
     * Throw a PermissionDeniedException unless the authenticated user has the
     * given permission. Call as the first line of a gated controller method.
     */
    function bpAuthorize(string $permission): void
    {
        if (!checkUserHasPermission($permission)) {
            throw new \App\Exceptions\PermissionDeniedException();
        }
    }
}

if (!function_exists('bpAuthorizeAny')) {
    /**
     * Throw a PermissionDeniedException unless the user has ANY of the given permissions.
     */
    function bpAuthorizeAny(string ...$permissions): void
    {
        if (!bpCanAny(...$permissions)) {
            throw new \App\Exceptions\PermissionDeniedException();
        }
    }
}
