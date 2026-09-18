<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Rules\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Services\CustomerOtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\Ecommerce\Models\EcommerceSetting;
use Modules\Ecommerce\Models\StorefrontCustomer;
use Modules\Location\Models\District;
use Throwable;

class CustomerAuthController extends Controller
{
    /**
     * Check if customer auth is enabled via admin settings.
     */
    private function ensureAuthEnabled(): ?RedirectResponse
    {
        if (EcommerceSetting::get('customer_auth_enabled', '1') !== '1') {
            return redirect()->route('storefront.home');
        }

        return null;
    }

    /**
     * Whether phone OTP verification is required before registration.
     */
    private function otpRequired(): bool
    {
        return EcommerceSetting::get('customer_otp_required', '1') === '1';
    }

    /**
     * Show the customer login form.
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureAuthEnabled()) {
            return $redirect;
        }

        if ($request->has('redirect')) {
            session(['customer_redirect' => $request->input('redirect')]);
        }

        return view('ecommerce::storefront.pages.customer.login', ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,nofollow')]);
    }

    /**
     * Handle customer login.
     */
    public function login(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureAuthEnabled()) {
            return $redirect;
        }

        $validated = $request->validate([
            'phone'    => 'required|string|max:30',
            'password' => 'required|string',
        ]);

        $credentials = [
            'phone'     => $validated['phone'],
            'password'  => $validated['password'],
            'is_active' => true,
        ];

        if (Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $redirect = session()->pull('customer_redirect', route('storefront.customer.profile'));

            session()->flash('bp_track', ['event' => 'Login', 'ga' => ['method' => 'password'], 'fb' => []]);

            return redirect()->to($redirect);
        }

        return back()->withInput($request->only('phone', 'remember'))
            ->withErrors(['phone' => 'Invalid phone number or password.']);
    }

    /**
     * Redirect the customer to Google's OAuth consent screen.
     */
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureAuthEnabled()) {
            return $redirect;
        }

        if ($request->has('redirect')) {
            session(['customer_redirect' => $request->input('redirect')]);
        }

        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect()->route('storefront.customer.login')
                ->withErrors(['phone' => 'Google login is not configured. Please contact support.']);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the OAuth callback from Google.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureAuthEnabled()) {
            return $redirect;
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::warning('Google OAuth callback failed: ' . $e->getMessage());

            return redirect()->route('storefront.customer.login')
                ->withErrors(['phone' => 'Unable to sign in with Google. Please try again.']);
        }

        // Match against ALL customers, including soft-deleted ones. The
        // customers.email unique index ignores deleted_at, so a soft-deleted
        // record with the same email/Google ID must be reused — otherwise the
        // create() below collides with it (duplicate-key 500).
        // 1) Match an account already linked to this Google ID.
        $customer = StorefrontCustomer::withTrashed()->where('google_id', $googleUser->getId())->first();

        // 2) Otherwise match an existing account with the same email.
        if (! $customer && $googleUser->getEmail()) {
            $customer = StorefrontCustomer::withTrashed()->where('email', $googleUser->getEmail())->first();
        }

        if ($customer) {
            // Reuse (and undelete) the existing record, then link the Google ID.
            if ($customer->trashed()) {
                $customer->restore();
            }
            $customer->google_id = $googleUser->getId();
            $customer->save();
        } else {
            // 3) Otherwise create a fresh customer.
            $customer = StorefrontCustomer::create([
                'name'      => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Customer',
                'email'     => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'is_active' => true,
            ]);
        }

        if (! $customer->is_active) {
            return redirect()->route('storefront.customer.login')
                ->withErrors(['phone' => 'Your account is inactive. Please contact support.']);
        }

        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        $redirect = session()->pull('customer_redirect', route('storefront.customer.profile'));

        return redirect()->to($redirect);
    }

    /* -------------------------------------------------------
     * Forgot / Reset Password (phone OTP based)
     * ----------------------------------------------------- */

    /**
     * Show the "forgot password" request form (enter phone).
     */
    public function showForgotPasswordForm(): View|RedirectResponse
    {
        if ($redirect = $this->ensureAuthEnabled()) {
            return $redirect;
        }

        return view('ecommerce::storefront.pages.customer.forgot-password', ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,nofollow')]);
    }

    /**
     * Send a password-reset OTP to the customer's phone, then move to the
     * reset form. Only sends if an account with that phone exists.
     */
    public function sendResetOtp(Request $request, CustomerOtpService $otp): RedirectResponse
    {
        if ($redirect = $this->ensureAuthEnabled()) {
            return $redirect;
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30', new PhoneNumber],
        ]);

        if (! StorefrontCustomer::where('phone', $validated['phone'])->exists()) {
            return back()->withInput($request->only('phone'))
                ->withErrors(['phone' => __('No account found with this number.')]);
        }

        $result = $otp->send($validated['phone'], 'pwd');

        if (($result['ok'] ?? false) !== true) {
            $messages = [
                'cooldown'    => __('Please wait a moment before requesting another code.'),
                'send_failed' => __("Couldn't send the code. Please try again."),
            ];

            return back()->withInput($request->only('phone'))
                ->withErrors(['phone' => $messages[$result['reason'] ?? ''] ?? __('Unable to send code.')]);
        }

        // Store the raw phone (as stored on the customer) so the later lookup
        // matches; the OTP service normalizes internally for its cache keys.
        session(['reset_phone' => $validated['phone']]);

        return redirect()->route('storefront.customer.password.reset')
            ->with('success', __('A verification code has been sent to your phone.'));
    }

    /**
     * Show the reset form (enter OTP + new password). Requires a pending
     * reset request in the session.
     */
    public function showResetForm(): View|RedirectResponse
    {
        if ($redirect = $this->ensureAuthEnabled()) {
            return $redirect;
        }

        if (! session('reset_phone')) {
            return redirect()->route('storefront.customer.password.request');
        }

        return view('ecommerce::storefront.pages.customer.reset-password', [
            'seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,nofollow'),
        ]);
    }

    /**
     * Verify the OTP and set the new password for the session's phone.
     */
    public function resetPassword(Request $request, CustomerOtpService $otp): RedirectResponse
    {
        if ($redirect = $this->ensureAuthEnabled()) {
            return $redirect;
        }

        $phone = session('reset_phone');

        if (! $phone) {
            return redirect()->route('storefront.customer.password.request')
                ->withErrors(['phone' => __('Your reset session has expired. Please start again.')]);
        }

        $validated = $request->validate([
            'code'     => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! $otp->verify($phone, $validated['code'], 'pwd')) {
            return back()->withErrors(['code' => __('Invalid or expired code.')]);
        }

        $customer = StorefrontCustomer::where('phone', $phone)->first();

        if (! $customer) {
            session()->forget('reset_phone');

            return redirect()->route('storefront.customer.password.request')
                ->withErrors(['phone' => __('No account found with this number.')]);
        }

        $customer->update(['password' => $validated['password']]);
        session()->forget('reset_phone');

        return redirect()->route('storefront.customer.login')
            ->with('success', __('Your password has been reset. Please sign in.'));
    }

    /**
     * Show the customer registration form.
     */
    public function showRegistrationForm(): View|RedirectResponse
    {
        if ($redirect = $this->ensureAuthEnabled()) {
            return $redirect;
        }

        $districts = District::where('is_active', true)
            ->with(['thanas' => fn ($q) => $q->where('is_active', true)->orderBy('thana_name')])
            ->orderBy('district_name')
            ->get(['id', 'district_name']);

        return view('ecommerce::storefront.pages.customer.register', [
            'otpRequired'   => $this->otpRequired(),
            'verifiedPhone' => session('register_verified_phone'),
            'districts'     => $districts,
            'seo'           => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,nofollow'),
        ]);
    }

    /**
     * Handle customer registration.
     */
    public function register(Request $request, CustomerOtpService $otp): RedirectResponse
    {
        if ($redirect = $this->ensureAuthEnabled()) {
            return $redirect;
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => ['required', 'string', 'max:30', 'unique:customers,phone', new PhoneNumber],
            'email'    => 'nullable|email|max:255|unique:customers,email',
            'password' => 'required|string|min:6|confirmed',
            'district' => 'nullable|string|max:50',
            'upazila'  => 'nullable|string|max:50',
            'address'  => 'nullable|string|max:500',
        ]);

        if ($this->otpRequired() && session('register_verified_phone') !== $otp->normalize($validated['phone'])) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['phone' => __('Please verify your phone number before registering.')]);
        }

        $customer = StorefrontCustomer::create([
            'name'     => $validated['name'],
            'phone'    => $validated['phone'],
            'email'    => $validated['email'] ?? null,
            'password' => $validated['password'],
            'district' => $validated['district'] ?? null,
            'upazila'  => $validated['upazila'] ?? null,
            'address'  => $validated['address'] ?? null,
        ]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        session()->forget('register_verified_phone');

        session()->flash('bp_track', ['event' => 'CompleteRegistration', 'ga' => [], 'fb' => ['data' => []]]);

        return redirect()->route('storefront.customer.profile')
            ->with('success', __('Account created successfully!'));
    }

    /**
     * Step 1 (AJAX): validate phone, block duplicates, send an SMS OTP.
     */
    public function sendRegisterOtp(Request $request, CustomerOtpService $otp): JsonResponse
    {
        if (EcommerceSetting::get('customer_auth_enabled', '1') !== '1' || ! $this->otpRequired()) {
            return response()->json(['ok' => false, 'message' => __('Registration is unavailable.')]);
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30', new PhoneNumber],
        ]);

        if (StorefrontCustomer::where('phone', $validated['phone'])->exists()) {
            return response()->json([
                'ok'      => false,
                'code'    => 'exists',
                'message' => __('This number is already registered — please sign in.'),
            ]);
        }

        $result = $otp->send($validated['phone']);

        if (($result['ok'] ?? false) === true) {
            return response()->json(['ok' => true, 'message' => __('A verification code has been sent to your phone.')]);
        }

        $messages = [
            'cooldown'    => __('Please wait a moment before requesting another code.'),
            'send_failed' => __("Couldn't send the code. Please try again."),
        ];

        return response()->json([
            'ok'      => false,
            'message' => $messages[$result['reason'] ?? ''] ?? __('Unable to send code.'),
        ]);
    }

    /**
     * Step 2 (AJAX): verify the OTP and remember the verified phone in session.
     */
    public function verifyRegisterOtp(Request $request, CustomerOtpService $otp): JsonResponse
    {
        if (EcommerceSetting::get('customer_auth_enabled', '1') !== '1' || ! $this->otpRequired()) {
            return response()->json(['ok' => false, 'message' => __('Registration is unavailable.')]);
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30', new PhoneNumber],
            'code'  => ['required', 'digits:6'],
        ]);

        if (! $otp->verify($validated['phone'], $validated['code'])) {
            return response()->json(['ok' => false, 'message' => __('Invalid or expired code.')]);
        }

        session(['register_verified_phone' => $otp->normalize($validated['phone'])]);

        return response()->json(['ok' => true]);
    }

    /**
     * Handle customer logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront.home');
    }

    /**
     * Show customer dashboard (overview with stats).
     */
    public function showProfile(): View
    {
        $customer = Auth::guard('customer')->user();

        $stats = [
            'total'     => EcommerceOrder::where('customer_id', $customer->id)->count(),
            'delivered'  => EcommerceOrder::where('customer_id', $customer->id)->where('status', 'delivered')->count(),
            'pending'   => EcommerceOrder::where('customer_id', $customer->id)->where('status', 'pending')->count(),
            'cancelled' => EcommerceOrder::where('customer_id', $customer->id)->where('status', 'cancelled')->count(),
            'total_spent' => (float) EcommerceOrder::where('customer_id', $customer->id)
                ->whereIn('status', ['delivered', 'confirmed', 'processing', 'shipped'])
                ->sum('grand_total'),
        ];

        $recentOrders = EcommerceOrder::where('customer_id', $customer->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('ecommerce::storefront.pages.customer.profile', compact('customer', 'stats', 'recentOrders') + ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,nofollow')]);
    }

    /**
     * Show profile edit form.
     */
    public function editProfile(): View
    {
        $customer = Auth::guard('customer')->user();

        $districts = District::where('is_active', true)
            ->with(['thanas' => fn ($q) => $q->where('is_active', true)->orderBy('thana_name')])
            ->orderBy('district_name')
            ->get(['id', 'district_name']);

        return view('ecommerce::storefront.pages.customer.profile-edit', compact('customer', 'districts') + ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,nofollow')]);
    }

    /**
     * Update customer profile.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|email|max:255|unique:customers,email,' . $customer->id,
            'district' => 'nullable|string|max:50',
            'upazila'  => 'nullable|string|max:50',
            'address'  => 'nullable|string|max:500',
            'shipping_address' => 'nullable|string|max:500',
            'photo'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Store a newly uploaded avatar (randomized name), replacing the old one.
        if ($request->hasFile('photo')) {
            $validated['photo'] = upload_replace($customer->photo, $request->file('photo'), 'customers');
        } else {
            unset($validated['photo']);
        }

        $customer->update($validated);

        return redirect()->route('storefront.customer.profile.edit')
            ->with('success', __('Profile updated successfully!'));
    }

    /**
     * Show change password form.
     */
    public function changePassword(): View
    {
        return view('ecommerce::storefront.pages.customer.change-password', ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,nofollow')]);
    }

    /**
     * Update customer password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $customer->password)) {
            return back()->with('error', __('Current password is incorrect.'));
        }

        $customer->update(['password' => $validated['password']]);

        return redirect()->route('storefront.customer.password')
            ->with('success', __('Password changed successfully!'));
    }

    /**
     * Show the customer's wishlist inside the dashboard (product-card grid).
     */
    public function wishlist(): View
    {
        $wishlist = (array) session('wishlist', []);
        $products = collect();

        if (! empty($wishlist)) {
            $products = \Modules\Product\Models\Product::storefrontVisible()
                ->whereIn('id', $wishlist)
                ->with(['images', 'category', 'brand'])
                ->get()
                ->sortBy(fn ($p) => array_search($p->id, $wishlist))
                ->values();
        }

        return view('ecommerce::storefront.pages.customer.wishlist', compact('products') + ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,nofollow')]);
    }

    /**
     * Show customer orders.
     */
    public function orders(): View
    {
        $customer = Auth::guard('customer')->user();

        $orders = EcommerceOrder::where('customer_id', $customer->id)
            ->latest()
            ->paginate(10);

        return view('ecommerce::storefront.pages.customer.orders', compact('orders') + ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,nofollow')]);
    }

    /**
     * Show order detail.
     */
    public function orderDetail(string $orderNumber): View
    {
        $customer = Auth::guard('customer')->user();

        $order = EcommerceOrder::where('customer_id', $customer->id)
            ->where('order_number', $orderNumber)
            ->with('items')
            ->firstOrFail();

        return view('ecommerce::storefront.pages.customer.order-detail', compact('order') + ['seo' => \Modules\Ecommerce\Support\Seo::make()->robots('noindex,nofollow')]);
    }
}
