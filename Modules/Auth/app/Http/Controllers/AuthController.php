<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\ResetPasswordRequest;
use Modules\Auth\Services\AdminPasswordResetService;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin()
    {
        return view('auth::login');
    }

    /**
     * Handle login form submission.
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Invalid email or password. Please try again.',
        ])->onlyInput('email');
    }

    /**
     * Show the forgot password form.
     */
    public function showForgotPassword()
    {
        return view('auth::forgot-password');
    }

    /**
     * Step 1 — decide the verification method for an email and (for SMS) send
     * the code. Never sends email; never reveals whether an account exists.
     */
    public function requestReset(Request $request, AdminPasswordResetService $service)
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        return response()->json($service->initiate($validated['email']));
    }

    /**
     * Step 2 — verify the submitted code (TOTP or SMS) and return a one-time
     * reset token for the password form.
     */
    public function verifyReset(Request $request, AdminPasswordResetService $service)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code'  => ['required', 'string'],
        ]);

        $token = $service->verify($validated['email'], $validated['code']);

        if (! $token) {
            return response()->json([
                'ok'      => false,
                'message' => __('Invalid or expired code. Please try again.'),
            ], 422);
        }

        return response()->json(['ok' => true, 'token' => $token]);
    }

    /**
     * Show the reset password form.
     */
    public function showResetPassword(Request $request)
    {
        return view('auth::reset-password');
    }

    /**
     * Handle password reset form submission.
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $email = $request->input('email');
        $token = $request->input('token');

        $tokenRow = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$tokenRow) {
            return redirect()->route('password.request')
                ->with('error', __('Invalid or expired reset link. Please try again.'));
        }

        // Check expiry (60 minutes for the reset token)
        if (now()->diffInMinutes($tokenRow->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return redirect()->route('password.request')
                ->with('error', __('Reset link has expired. Please request a new OTP.'));
        }

        // Verify token
        if (!Hash::check($token, $tokenRow->token)) {
            return redirect()->route('password.request')
                ->with('error', __('Invalid reset token. Please try again.'));
        }

        // Update user password
        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('password.request')
                ->with('error', __('User not found.'));
        }

        $user->update(['password' => $request->input('password')]);

        // Invalidate the token
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return redirect()->route('password.reset')
            ->with('password_reset_success', true);
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
