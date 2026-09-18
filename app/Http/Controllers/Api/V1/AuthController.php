<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseApiController
{
    /**
     * Login and issue a Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->error('Invalid email or password.', 401);
        }

        if ($user->status !== 'active') {
            return $this->error('Your account is inactive. Please contact the administrator.', 403);
        }

        $deviceName = $validated['device_name'] ?? 'mobile-app';
        $token = $user->createToken($deviceName)->plainTextToken;

        $user->update(['last_login_at' => now()]);

        return $this->success([
            'user'  => new UserResource($user),
            'token' => $token,
        ], 'Login successful.');
    }

    /**
     * Get the authenticated user's profile.
     */
    public function profile(Request $request): JsonResponse
    {
        return $this->success(
            new UserResource($request->user()),
            'Profile retrieved successfully.'
        );
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'  => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $request->user()->update($validated);

        return $this->success(
            new UserResource($request->user()->fresh()),
            'Profile updated successfully.'
        );
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $request->user()->password)) {
            return $this->error('Current password is incorrect.', 422, [
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $request->user()->update(['password' => $validated['password']]);

        return $this->success(null, 'Password changed successfully.');
    }

    /**
     * Logout — revoke the current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully.');
    }
}
