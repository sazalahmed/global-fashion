<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Http\Requests\StoreCouponRequest;
use Modules\Ecommerce\Http\Requests\StoreShippingZoneRequest;
use Modules\Ecommerce\Models\Coupon;
use Modules\Ecommerce\Models\CourierProvider;
use Modules\Ecommerce\Models\ShippingZone;
use Modules\Ecommerce\Services\EcommerceService;
use Modules\Ecommerce\Services\SitemapService;
use Modules\Product\Models\Product;

class EcommerceController extends Controller
{
    public function __construct(
        protected EcommerceService $service
    ) {}

    /**
     * Ecommerce admin dashboard.
     */
    public function index(): View
    {
        bpAuthorize('ecommerce.view');
        $stats = $this->service->getOrderStats();

        return view('ecommerce::index', compact('stats'));
    }

    /**
     * Display a listing of ecommerce products.
     */
    public function products(): View
    {
        bpAuthorize('ecommerce.view');
        $products = \Modules\Product\Models\Product::orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('ecommerce::products', compact('products'));
    }

    /**
     * Display a listing of coupons.
     */
    public function coupons(): View
    {
        bpAuthorize('ecommerce.view');
        $coupons = $this->service->listCoupons();

        return view('ecommerce::coupons', compact('coupons'));
    }

    /**
     * Show the form for creating a new coupon.
     */
    public function couponCreate(): View
    {
        bpAuthorize('ecommerce.create');
        $products = Product::with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'sell_price', 'category_id']);

        // Decorate categories with a product count via a single grouped query
        // (Category model has no products() relation).
        $counts = Product::selectRaw('category_id, COUNT(*) as c')
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->pluck('c', 'category_id');
        $categories = Category::orderBy('name')->get(['id', 'name'])->map(function ($cat) use ($counts) {
            $cat->products_count = (int) ($counts[$cat->id] ?? 0);
            return $cat;
        });

        return view('ecommerce::coupon-create', compact('products', 'categories'));
    }

    /**
     * Store a newly created coupon in storage.
     */
    public function couponStore(StoreCouponRequest $request): RedirectResponse
    {
        bpAuthorize('ecommerce.create');
        $this->service->createCoupon($request->validated());

        return redirect()->route('ecommerce.coupons')->with('success', __('Coupon created successfully.'));
    }

    /**
     * Show the form for editing an existing coupon. Reuses the same form
     * partial as couponCreate so any field changes ride through both.
     */
    public function couponEdit(Coupon $coupon): View
    {
        bpAuthorize('ecommerce.edit');
        $products = Product::with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'sell_price', 'category_id']);

        $counts = Product::selectRaw('category_id, COUNT(*) as c')
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->pluck('c', 'category_id');
        $categories = Category::orderBy('name')->get(['id', 'name'])->map(function ($cat) use ($counts) {
            $cat->products_count = (int) ($counts[$cat->id] ?? 0);
            return $cat;
        });

        return view('ecommerce::coupon-edit', compact('coupon', 'products', 'categories'));
    }

    public function couponUpdate(StoreCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $this->service->updateCoupon($coupon, $request->validated());

        return redirect()->route('ecommerce.coupons')->with('success', __('Coupon updated successfully.'));
    }

    /**
     * Delete a coupon.
     */
    public function couponDestroy(Coupon $coupon): RedirectResponse
    {
        bpAuthorize('ecommerce.delete');
        $this->service->deleteCoupon($coupon);

        return redirect()->route('ecommerce.coupons')->with('success', __('Coupon deleted successfully.'));
    }

    /**
     * Display a listing of shipping zones.
     */
    public function shipping(): View
    {
        bpAuthorize('ecommerce.view');
        $zones = $this->service->listShippingZones();

        return view('ecommerce::shipping', compact('zones'));
    }

    /**
     * Show the form for creating a new shipping zone.
     */
    public function shippingCreate(): View
    {
        bpAuthorize('ecommerce.create');
        $districts = \Modules\Location\Models\District::where('is_active', true)
            ->orderBy('district_name')
            ->get(['id', 'district_name']);

        $assignedDistrictIds = \DB::table('shipping_zone_districts')->pluck('district_id')->all();

        return view('ecommerce::shipping-create', compact('districts', 'assignedDistrictIds'));
    }

    public function shippingStore(StoreShippingZoneRequest $request): RedirectResponse
    {
        bpAuthorize('ecommerce.create');
        $this->service->createShippingZone($request->toZoneAttributes(), $request->districtIds());

        return redirect()->route('ecommerce.shipping')->with('success', __('Shipping zone created.'));
    }

    public function shippingEdit(ShippingZone $zone): View
    {
        bpAuthorize('ecommerce.edit');
        $zone->load('districts:id');
        $districts = \Modules\Location\Models\District::where('is_active', true)
            ->orderBy('district_name')
            ->get(['id', 'district_name']);

        $selectedIds = $zone->districts->pluck('id')->all();
        // Districts pinned to other zones — disable them in the form.
        $assignedDistrictIds = \DB::table('shipping_zone_districts')
            ->where('shipping_zone_id', '!=', $zone->id)
            ->pluck('district_id')
            ->all();

        return view('ecommerce::shipping-edit', compact('zone', 'districts', 'selectedIds', 'assignedDistrictIds'));
    }

    public function shippingUpdate(StoreShippingZoneRequest $request, ShippingZone $zone): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $this->service->updateShippingZone($zone, $request->toZoneAttributes(), $request->districtIds());

        return redirect()->route('ecommerce.shipping')->with('success', __('Shipping zone updated.'));
    }

    public function shippingDestroy(ShippingZone $zone): RedirectResponse
    {
        bpAuthorize('ecommerce.delete');
        $this->service->deleteShippingZone($zone);

        return redirect()->route('ecommerce.shipping')->with('success', __('Shipping zone deleted.'));
    }

    public function toggleShippingStatus(ShippingZone $zone): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('ecommerce.edit');
        $this->service->toggleStatus($zone);

        return response()->json(['success' => true, 'is_active' => $zone->is_active, 'message' => __('Status updated.')]);
    }

    /**
     * Display a listing of courier providers.
     */
    public function courierProviders(): View
    {
        bpAuthorize('ecommerce.view');
        $providers = CourierProvider::ordered()->get();

        return view('ecommerce::courier-providers', compact('providers'));
    }

    /**
     * Toggle courier provider active status.
     */
    public function toggleCourierProvider(CourierProvider $provider): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $provider->update(['is_active' => !$provider->is_active]);

        return back()->with('success', $provider->name . ' has been ' . ($provider->is_active ? 'activated' : 'deactivated') . '.');
    }

    /**
     * Update courier provider API credentials.
     */
    public function updateCourierProvider(Request $request, CourierProvider $provider): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $request->validate([
            'base_url' => 'nullable|url|max:255',
            'api_key' => 'nullable|string|max:500',
            'api_secret' => 'nullable|string|max:500',
            'store_id' => 'nullable|string|max:100',
        ]);

        $data = $request->only(['base_url', 'api_key', 'api_secret', 'store_id']);

        // api_key / api_secret render as a masked '********' placeholder when
        // already set. Treat that sentinel as "unchanged" so saving the form
        // without retyping doesn't overwrite the real credentials with the mask.
        foreach (['api_key', 'api_secret'] as $secret) {
            if (($data[$secret] ?? null) === '********') {
                unset($data[$secret]);
            }
        }

        // Discard any stored credential that can no longer be decrypted before
        // assigning the replacements — otherwise Eloquent's dirty check tries
        // to decrypt it to compare, and the save throws.
        $provider->forgetUnreadableCredentials();

        $provider->update($data);

        return back()->with('success', $provider->name . ' credentials updated successfully.');
    }

    /**
     * Display the ecommerce settings.
     */
    public function settings(SitemapService $sitemap): View
    {
        bpAuthorize('ecommerce.view');
        $settings = $this->service->getSettings();

        $seoPageKeys = ['home', 'shop', 'categories', 'blog', 'flash-deals'];
        $seoPages = [];
        foreach ($seoPageKeys as $key) {
            $seoPages[$key] = \Modules\Ecommerce\Models\SeoPage::forKey($key);
        }

        $sitemapInfo = [
            'generated_at' => $sitemap->lastGeneratedAt(),
            'url'          => $sitemap->publicUrl(),
        ];

        return view('ecommerce::settings', compact('settings', 'seoPages', 'sitemapInfo'));
    }

    /**
     * Regenerate the storefront sitemap on demand (admin button).
     */
    public function generateSitemap(SitemapService $sitemap): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $count = $sitemap->generate();

        $message = __('Sitemap generated successfully — :count URLs.', ['count' => $count]);
        if ($sitemap->exceedsLimit($count)) {
            $message .= ' ' . __('Note: over 50,000 URLs — consider a sitemap index.');
        }

        return back()->with('success', $message);
    }

    /**
     * Update the ecommerce settings.
     */
    public function settingsUpdate(Request $request): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        // Validate the contact phone/email lists BEFORE any side effects
        // (uploads, setting writes) so a bad value never gets stored.
        $validator = Validator::make($request->all(), [
            'contact_emails.*' => ['nullable', 'email:rfc'],
            'payment_icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'faq_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'page_banner_bg' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'faq_sub_title' => ['nullable', 'string', 'max:255'],
            'faq_title' => ['nullable', 'string', 'max:255'],
            'per_page_shop'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page_categories'  => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page_blog'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page_flash_deals' => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page_combos'      => ['nullable', 'integer', 'min:1', 'max:100'],
        ], [
            'contact_emails.*.email' => 'Enter a valid email address.',
            'per_page_shop.max'        => 'Items per page must be 100 or fewer.',
            'per_page_categories.max'  => 'Items per page must be 100 or fewer.',
            'per_page_blog.max'        => 'Items per page must be 100 or fewer.',
            'per_page_flash_deals.max' => 'Items per page must be 100 or fewer.',
            'per_page_combos.max'      => 'Items per page must be 100 or fewer.',
        ]);

        $validator->after(function ($v) use ($request) {
            foreach ((array) $request->input('contact_phones', []) as $i => $phone) {
                $phone = trim((string) $phone);
                if ($phone !== '' && !PhoneHelper::isValid($phone)) {
                    $v->errors()->add(
                        "contact_phones.$i",
                        'Enter a valid Bangladesh phone number (e.g. +8801712345678).'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('settings_error', 'Please fix the highlighted contact fields.');
        }

        // Handle the contact-page banner image upload / removal
        $this->service->handleContactBanner(
            (bool) $request->boolean('remove_contact_banner'),
            $request->file('contact_banner')
        );

        // Handle SEO image uploads (seo_default_image, seo_org_logo)
        $this->service->handleSeoImages($request);

        // Handle storefront auth-page illustrations
        $this->service->handleStorefrontImages($request);

        // Normalize a pasted Google Maps embed (accepts full <iframe> or URL)
        $data = $request->except([
            '_token', '_method', 'payment_icon', 'remove_payment_icon',
            'contact_banner', 'remove_contact_banner',
            'contact_phones', 'contact_emails',
            'seo_pages', 'seo_default_image', 'seo_org_logo',
            'auth_login_image', 'auth_register_image', 'auth_forgot_image', 'auth_reset_image', 'footer_bg_image',
            'remove_auth_login_image', 'remove_auth_register_image', 'remove_auth_forgot_image', 'remove_auth_reset_image', 'remove_footer_bg_image',
            'faq_image', 'remove_faq_image',
            'page_banner_bg', 'remove_page_banner_bg',
        ]);
        $data['contact_map_embed'] = $this->service->normalizeMapEmbed($request->input('contact_map_embed'));
        $data['contact_phones'] = $this->service->normalizePhoneList($request->input('contact_phones'));
        $data['contact_emails'] = $this->service->normalizeContactList($request->input('contact_emails'));

        $this->service->updateSettings($data);

        // Persist per-static-page SEO rows
        foreach ((array) $request->input('seo_pages', []) as $key => $vals) {
            \Modules\Ecommerce\Models\SeoPage::updateOrCreate(
                ['key' => $key],
                [
                    'title'       => $vals['title'] ?? null,
                    'description' => $vals['description'] ?? null,
                    'robots'      => $vals['robots'] ?? 'index,follow',
                ]
            );
        }

        return back()->with('success', __('eCommerce settings updated successfully.'));
    }

    /**
     * Update the storefront color scheme. Lives on the main Settings page
     * (Appearance section) but the values are stored as eCommerce settings,
     * which is where the storefront layout reads them from.
     */
    public function appearanceUpdate(Request $request): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        $keys = [
            'theme_primary_color', 'theme_secondary_color', 'theme_accent_color',
            'theme_success_color', 'theme_danger_color', 'theme_light_bg',
        ];

        $rules = [];
        $messages = [];
        foreach ($keys as $k) {
            $rules[$k] = ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'];
            $messages[$k . '.regex'] = __('Enter a valid hex color (e.g. #1B4F72).');
        }
        $request->validate($rules, $messages);

        $this->service->updateSettings($request->only($keys));

        return redirect()
            ->to(route('settings.index') . '#appearanceSettings')
            ->with('success', __('Appearance colors updated successfully.'));
    }
}
