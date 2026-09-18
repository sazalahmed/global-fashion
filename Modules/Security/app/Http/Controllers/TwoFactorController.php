<?php

namespace Modules\Security\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Security\Services\TwoFactorService;

class TwoFactorController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    public function show()
    {
        return view('security::users.two-factor', ['user' => auth()->user()]);
    }

    public function enable()
    {
        return response()->json($this->twoFactor->generateSecret(auth()->user()));
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);

        if (! $this->twoFactor->confirm(auth()->user(), $request->input('code'))) {
            return response()->json([
                'ok'      => false,
                'message' => __('That code is incorrect. Please try again.'),
            ], 422);
        }

        return response()->json(['ok' => true]);
    }

    public function disable(Request $request)
    {
        $request->validate(
            ['current_password' => ['required', 'current_password']],
            ['current_password.current_password' => __('The password is incorrect.')]
        );

        $this->twoFactor->disable(auth()->user());

        return redirect()->route('security.two-factor')
            ->with('success', __('Two-Factor Authentication disabled.'));
    }
}
