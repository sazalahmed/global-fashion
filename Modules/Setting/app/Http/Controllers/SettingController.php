<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Modules\Setting\Http\Requests\SaveSidebarConfigRequest;
use Modules\Setting\Http\Requests\StoreWebhookRequest;
use Modules\Setting\Http\Requests\UpdateSettingsRequest;
use Modules\Setting\Models\Setting;
use Modules\Setting\Models\TaxRate;
use Modules\Setting\Models\Webhook;
use Modules\Setting\Services\SettingService;

class SettingController extends Controller
{
    /**
     * Maps a settings group slug (used in the update URL) to the DOM id of the
     * matching tab pane / nav item in the settings view. Used to keep the user
     * on the section they just saved instead of bouncing to Business Profile.
     */
    public const GROUP_SECTIONS = [
        'business'     => 'businessSettings',
        'tax'          => 'taxSettings',
        'invoice'      => 'invoiceSettings',
        'courier'      => 'courierSettings',
        'notification' => 'notificationSettings',
        'localization' => 'localeSettings',
        'sms'          => 'smsSettings',
        'landing_page' => 'landingPageSettings',
        'tracking'     => 'trackingSettings',
    ];

    public function __construct(
        protected SettingService $service
    ) {}

    /**
     * Display the settings page with all groups.
     */
    public function index()
    {
        bpAuthorize('settings.view');
        $settings = $this->service->getAll();
        $branches = \Modules\Branch\Models\Branch::ordered()->get();
        $webhooks = Webhook::with('creator')->latest()->get();
        $webhookEvents = Webhook::AVAILABLE_EVENTS;
        $taxRates = TaxRate::orderByDesc('is_default')->orderBy('name')->get();
        $landingPages = class_exists(\Modules\LandingPage\Models\LandingPage::class)
            ? \Modules\LandingPage\Models\LandingPage::orderBy('name')->get(['id', 'name', 'template', 'is_active'])
            : collect();

        return view('setting::index', compact('settings', 'branches', 'webhooks', 'webhookEvents', 'taxRates', 'landingPages'));
    }

    /**
     * Update a specific settings group.
     */
    public function update(UpdateSettingsRequest $request, string $group)
    {
        bpAuthorize('settings.edit');
        $allowedGroups = ['business', 'tax', 'invoice', 'courier', 'notification', 'localization', 'sms', 'landing_page', 'tracking'];

        if (!in_array($group, $allowedGroups, true)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => __('Unknown settings group.')], 422);
            }

            return back()->with('error', __('Unknown settings group.'));
        }

        match ($group) {
            'business' => $this->service->updateBusinessProfile($request->all()),
            'tax' => $this->service->updateTaxSettings($request->all()),
            'invoice' => $this->service->updateInvoiceSettings($request->all()),
            'courier' => $this->service->updateCourierSettings($request->all()),
            'notification' => $this->service->updateNotificationSettings($request->all()),
            'localization' => $this->service->updateLocalization($request->all()),
            'sms' => $this->service->updateSmsGateway($request->all()),
            'landing_page' => $this->service->updateLandingPage($request->all()),
            'tracking' => $this->service->updateTracking($request->all()),
        };

        $message = ucfirst($group) . ' settings updated.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()
            ->with('success', $message)
            ->with('active_section', self::GROUP_SECTIONS[$group] ?? null);
    }

    // ── Webhooks ──

    public function webhookStore(StoreWebhookRequest $request)
    {
        bpAuthorize('settings.edit');
        $validated = $request->validated();

        // Editing an existing webhook (same form, populated client-side).
        if ($request->filled('webhook_id')) {
            $webhook = Webhook::findOrFail($request->input('webhook_id'));

            // A blank secret on edit means "keep the current one".
            if (!$request->filled('secret')) {
                unset($validated['secret']);
            }

            $webhook->update($validated);

            return back()->with('success', __('Webhook updated.'))->with('active_section', 'webhookSettings');
        }

        $validated['created_by'] = auth()->id();

        Webhook::create($validated);

        return back()->with('success', __('Webhook created.'))->with('active_section', 'webhookSettings');
    }

    public function webhookToggle(Webhook $webhook)
    {
        bpAuthorize('settings.edit');
        $webhook->update(['is_active' => !$webhook->is_active, 'failure_count' => 0]);

        return back()->with('success', 'Webhook ' . ($webhook->is_active ? 'enabled' : 'disabled') . '.')->with('active_section', 'webhookSettings');
    }

    public function webhookDestroy(Webhook $webhook)
    {
        bpAuthorize('settings.edit');
        $webhook->delete();

        return back()->with('success', __('Webhook deleted.'))->with('active_section', 'webhookSettings');
    }

    public function webhookTest(Webhook $webhook)
    {
        bpAuthorize('settings.edit');
        $result = $webhook->dispatch('test.ping', [
            'message' => 'This is a test webhook from BizPOS Pro.',
            'timestamp' => now()->toIso8601String(),
        ]);

        $webhook->refresh();

        return back()->with($result ? 'success' : 'error',
            $result ? 'Test webhook sent successfully.' : 'Webhook test failed: ' . ($webhook->last_response ?? 'No response'))
            ->with('active_section', 'webhookSettings');
    }

    /**
     * Receive incoming webhook (test endpoint, no auth required).
     */
    public function webhookReceive(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Webhook received', [
            'event'   => $request->input('event'),
            'data'    => $request->input('data'),
        ]);

        return response()->json(['status' => 'ok', 'received_at' => now()->toIso8601String()]);
    }

    /**
     * Sidebar menu configuration page.
     */
    public function sidebarConfig()
    {
        bpAuthorize('settings.view');
        $sidebarSettings = Setting::getGroup('sidebar');

        $menuItems = [
            'Main' => [
                'pos_terminal'     => 'POS Terminal',
            ],
            'Inventory' => [
                'products'         => 'Products',
                'stock'            => 'Stock',
            ],
            'Transactions' => [
                'purchases'        => 'Purchases',
                'sales'            => 'Sales',
            ],
            'People' => [
                'customers'        => 'Customers',
                'suppliers'        => 'Suppliers',
                'employees_link'   => 'Employees',
            ],
            'Finance' => [
                'payments'         => 'Payments',
                'payment_accounts' => 'Payment Accounts',
                'expenses'         => 'Expenses',
                'assets'           => 'Assets',
                'accounting'       => 'Accounting',
            ],
            'Online' => [
                'ecommerce'        => 'eCommerce',
            ],
            'Analytics' => [
                'reports'          => 'Reports',
            ],
            'HR' => [
                'staff_hr'         => 'Staff (HR)',
                'marketing'        => 'Marketing',
            ],
            'System' => [
                'branches'         => 'Branches',
                'activity_log'     => 'Activity Log',
                'security'         => 'Security',
            ],
        ];

        return view('setting::sidebar-config', compact('sidebarSettings', 'menuItems'));
    }

    /**
     * Save sidebar configuration.
     */
    public function saveSidebarConfig(SaveSidebarConfigRequest $request)
    {
        bpAuthorize('settings.edit');
        $items = $request->input('sidebar', []);

        // Get all possible keys from the menu structure
        $allKeys = [
            'pos_terminal', 'products', 'stock',
            'purchases', 'sales', 'customers', 'suppliers', 'employees_link',
            'payments', 'payment_accounts', 'expenses', 'assets', 'accounting',
            'ecommerce', 'reports', 'staff_hr', 'marketing', 'branches',
            'activity_log', 'security',
        ];

        foreach ($allKeys as $key) {
            $value = in_array($key, $items) ? '1' : '0';
            Setting::set('sidebar', $key, $value);
        }

        return back()->with('success', __('Sidebar configuration saved successfully.'));
    }

    /**
     * Regenerate public/manifest.json so the installable PWA uses the business
     * name from Settings. Triggered from the Business Profile section.
     */
    public function generateManifest(\App\Services\ManifestGenerator $generator)
    {
        bpAuthorize('settings.edit');

        $manifest = $generator->generate();

        return back()
            ->with('success', __('App manifest generated. The install name is now ":name".', ['name' => $manifest['name']]))
            ->with('active_section', 'businessSettings');
    }

    /**
     * Toggle maintenance mode.
     */
    public function toggleMaintenance()
    {
        bpAuthorize('settings.edit');
        if (app()->isDownForMaintenance()) {
            Artisan::call('up');
            return back()->with('success', __('Maintenance mode disabled. Site is live.'));
        }

        $secret = \Illuminate\Support\Str::random(32);
        Artisan::call('down', ['--secret' => $secret]);

        return back()->with('success', "Maintenance mode enabled. Access via: " . url($secret));
    }

    /**
     * Clear application caches.
     */
    public function clearCache()
    {
        bpAuthorize('settings.edit');
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return back()->with('success', __('All caches cleared successfully (app, config, route, view).'));
    }

    /**
     * Live-test a courier provider connection using credentials submitted from
     * the courier settings form. Tests the *currently typed* credentials so
     * users can verify before saving.
     *
     * Returns JSON: { success, message, details? }
     */
    public function testCourierConnection(string $slug, Request $request)
    {
        bpAuthorize('settings.edit');
        try {
            $result = match ($slug) {
                'steadfast' => $this->testSteadfastConnection(
                    (string) $request->input('api_key'),
                    (string) $request->input('api_secret'),
                ),
                'pathao' => $this->testPathaoConnection(
                    (string) $request->input('client_id'),
                    (string) $request->input('client_secret'),
                ),
                default => ['success' => false, 'message' => __('Unsupported courier.')],
            };
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'message' => __('Test failed: :err', ['err' => $e->getMessage()]),
            ];
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Live-test the SMS gateway connection using the API key submitted from the
     * SMS settings form. Tests the *currently typed* key (before saving) by
     * querying BulkSMSBD's balance endpoint — a 202 response confirms the key
     * is valid and returns the current SMS balance.
     *
     * Returns JSON: { success, message, balance?, details? }
     */
    public function testSmsConnection(Request $request)
    {
        bpAuthorize('settings.edit');

        $apiKey = trim((string) $request->input('api_key'));

        if ($apiKey === '') {
            return response()->json([
                'success' => false,
                'message' => __('API Key is required to test the connection.'),
            ], 422);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(20)
                ->acceptJson()
                ->get('https://bulksmsbd.net/api/getBalanceApi', ['api_key' => $apiKey]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('Cannot reach the SMS gateway: :err', ['err' => $e->getMessage()]),
            ], 422);
        }

        $body = $response->json() ?? [];
        $code = (int) ($body['response_code'] ?? 0);
        $balance = $body['balance'] ?? null;

        // 202 = success. Some responses omit response_code but include a balance.
        if ($code === 202 || $balance !== null) {
            return response()->json([
                'success' => true,
                'message' => $balance !== null
                    ? __('Connected. SMS balance: :bal', ['bal' => number_format((float) $balance, 2)])
                    : __('Connected to BulkSMSBD.'),
                'balance' => $balance !== null ? number_format((float) $balance, 2) : null,
                'details' => $body,
            ]);
        }

        $providerMsg = $body['error_message'] ?? $body['success_message'] ?? null;

        return response()->json([
            'success' => false,
            'message' => $providerMsg
                ? __('Connection failed: :msg', ['msg' => $providerMsg])
                : __('Invalid API key or the SMS gateway rejected the request.'),
            'details' => $body,
        ], 422);
    }

    /**
     * Send a one-off test SMS using the credentials currently typed in the SMS
     * settings form (before saving) so users can confirm end-to-end delivery to
     * a real handset. Posts to BulkSMSBD's send endpoint — response_code 202
     * means the message was accepted.
     *
     * Returns JSON: { success, message, details? }
     */
    public function sendTestSms(Request $request)
    {
        bpAuthorize('settings.edit');

        $apiKey   = trim((string) $request->input('api_key'));
        $senderId = trim((string) $request->input('sender_id'));
        $number   = preg_replace('/\s+/', '', (string) $request->input('number'));

        if ($apiKey === '' || $senderId === '') {
            return response()->json([
                'success' => false,
                'message' => __('API Key and Sender ID are required.'),
            ], 422);
        }

        if ($number === '') {
            return response()->json([
                'success' => false,
                'message' => __('Enter a phone number to send the test SMS to.'),
            ], 422);
        }

        $businessName = Setting::get('business', 'company_name', config('app.name', 'BizPOS Pro'));
        $message = __(':business test message — your SMS gateway is configured correctly.', ['business' => $businessName]);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->post('https://bulksmsbd.net/api/smsapi', [
                    'api_key'  => $apiKey,
                    'senderid' => $senderId,
                    'number'   => $number,
                    'message'  => $message,
                    'type'     => 'text',
                ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('Cannot reach the SMS gateway: :err', ['err' => $e->getMessage()]),
            ], 422);
        }

        $body = $response->json() ?? [];
        $code = (int) ($body['response_code'] ?? 0);

        if ($code === 202) {
            return response()->json([
                'success' => true,
                'message' => __('Test SMS sent to :num.', ['num' => $number]),
                'details' => $body,
            ]);
        }

        $providerMsg = $body['error_message'] ?? $body['success_message'] ?? null;

        return response()->json([
            'success' => false,
            'message' => $providerMsg
                ? __('Send failed: :msg', ['msg' => $providerMsg])
                : __('The SMS gateway rejected the request (code :code).', ['code' => $code]),
            'details' => $body,
        ], 422);
    }

    private function testSteadfastConnection(string $apiKey, string $apiSecret): array
    {
        if ($apiKey === '' || $apiSecret === '') {
            return ['success' => false, 'message' => __('API Key and API Secret are required.')];
        }

        $client = new \Nayemuf\SteadfastCourier\SteadfastCourier(
            apiKey:    $apiKey,
            secretKey: $apiSecret,
        );

        $response = $client->balance()->getCurrentBalance();
        $balance = $response['current_balance'] ?? $response['balance'] ?? null;

        return [
            'success' => true,
            'message' => $balance !== null
                ? __('Connected. Current balance: :sym :bal', ['sym' => currency_symbol(), 'bal' => number_format((float) $balance, 2)])
                : __('Connected to Steadfast.'),
            'details' => $response,
        ];
    }

    private function testPathaoConnection(string $clientId, string $clientSecret): array
    {
        if ($clientId === '' || $clientSecret === '') {
            return ['success' => false, 'message' => __('Client ID and Client Secret are required.')];
        }

        // Pathao OAuth requires a merchant username + password in addition
        // to the client credentials (grant_type=password). The settings form
        // doesn't carry those, so we do a reachability + client-credentials
        // sanity check against the issue-token endpoint. A 400/422 from the
        // server confirms the API is reachable and the client_id is recognised;
        // 401 means the credentials are bad.
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->acceptJson()
                ->post('https://api-hermes.pathao.com/aladdin/api/v1/issue-token', [
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type'    => 'client_credentials',
                ]);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => __('Cannot reach Pathao API: :err', ['err' => $e->getMessage()])];
        }

        $status = $response->status();
        $body = $response->json() ?? [];

        // 200 — unlikely with client_credentials but means full success
        if ($response->successful()) {
            return ['success' => true, 'message' => __('Pathao credentials accepted.'), 'details' => $body];
        }

        // 401 — bad client_id/secret
        if ($status === 401) {
            return ['success' => false, 'message' => __('Invalid Client ID or Client Secret.'), 'details' => $body];
        }

        // 400/422 — API reachable, but needs username/password (expected with this grant type)
        if (in_array($status, [400, 422], true)) {
            return [
                'success' => true,
                'message' => __('Pathao API reachable. Full sign-in requires merchant username + password — verify those in the Pathao merchant panel.'),
                'details' => $body,
            ];
        }

        return [
            'success' => false,
            'message' => __('Pathao API returned HTTP :code.', ['code' => $status]),
            'details' => $body,
        ];
    }
}
