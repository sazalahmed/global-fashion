<?php

namespace Modules\Ecommerce\Services;

use App\Helpers\Upload;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Accounting\Services\AccountingIntegrationService;
use Modules\Ecommerce\Models\Coupon;
use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\Ecommerce\Models\EcommerceSetting;
use Modules\Ecommerce\Models\ShippingZone;
use Modules\Sale\Services\SaleService;

class EcommerceService
{
    public function __construct(
        private readonly AccountingIntegrationService $accountingService,
        private readonly SaleService $saleService,
    ) {}

    /**
     * Map an ecommerce order status to the linked sale's workflow status, so
     * the mirrored sale (and its stock) stays in sync when the order moves.
     */
    private const ORDER_TO_SALE_STATUS = [
        'pending'    => 'pending',
        'confirmed'  => 'confirmed',
        'processing' => 'packing',
        'shipped'    => 'courier',
        'delivered'  => 'delivered',
        'cancelled'  => 'cancelled',
        'refunded'   => 'cancelled',
    ];


    // Orders

    public function listOrders(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return EcommerceOrder::with('customer')
            ->when($filters['search'] ?? null, function ($q, $s) {
                $q->where(fn ($q) => $q->where('order_number', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhere('customer_phone', 'like', "%{$s}%"));
            })
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['payment_status'] ?? null, fn ($q, $s) => $q->where('payment_status', $s))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findOrder(int $id): EcommerceOrder
    {
        return EcommerceOrder::with('items.product', 'customer', 'sale', 'deliveryChallan', 'courierProvider')->findOrFail($id);
    }

    public function updateOrderStatus(EcommerceOrder $order, string $status): EcommerceOrder
    {
        $previousStatus = $order->status;
        $order->update(['status' => $status]);

        // Keep the mirrored sale in sync — this is what actually moves stock
        // (deduct on fulfilment, restore on cancel), idempotently.
        if ($order->sale_id && isset(self::ORDER_TO_SALE_STATUS[$status])) {
            $sale = \Modules\Sale\Models\Sale::find($order->sale_id);
            if ($sale) {
                $this->saleService->changeStatus($sale, self::ORDER_TO_SALE_STATUS[$status]);
            }
        }

        // Recognize revenue when an online order is confirmed (or delivered for COD).
        // Reverse it if a previously-recognized order is cancelled.
        $hasJournal = \Modules\Accounting\Models\JournalEntry::where('source_type', 'ecommerce_order')
            ->where('source_id', $order->id)
            ->where('status', 'posted')
            ->exists();

        $recognizeStatuses = ['confirmed', 'processing', 'shipped', 'delivered'];
        $reverseStatuses = ['cancelled', 'refunded'];

        if (!$hasJournal && in_array($status, $recognizeStatuses, true) && (float) $order->grand_total > 0) {
            try {
                $this->accountingService->recordEcommerceOrder($order);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to record ecommerce order JE {$order->order_number}: {$e->getMessage()}");
            }
        } elseif ($hasJournal && in_array($status, $reverseStatuses, true)) {
            try {
                $this->accountingService->voidJournalEntry('ecommerce_order', $order->id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to void ecommerce order JE {$order->order_number}: {$e->getMessage()}");
            }
        }

        // Report the Refund conversion when a reported purchase is cancelled or
        // refunded. Once-guarded + fraud-symmetric inside the service.
        if (in_array($status, $reverseStatuses, true)) {
            try {
                app(\Modules\Ecommerce\Services\TrackingService::class)->reportRefund($order);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Refund tracking failed {$order->order_number}: {$e->getMessage()}");
            }
        }

        return $order->fresh();
    }

    public function getOrderStats(): array
    {
        return [
            'total_orders' => EcommerceOrder::count(),
            'pending' => EcommerceOrder::pending()->count(),
            'processing' => EcommerceOrder::byStatus('processing')->count(),
            'delivered' => EcommerceOrder::byStatus('delivered')->count(),
            'total_revenue' => EcommerceOrder::where('payment_status', 'paid')->sum('grand_total'),
        ];
    }

    // Coupons

    public function listCoupons(int $perPage = 15): LengthAwarePaginator
    {
        return Coupon::orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }

    public function createCoupon(array $data): Coupon
    {
        return Coupon::create($data);
    }

    public function updateCoupon(Coupon $coupon, array $data): Coupon
    {
        $coupon->update($data);

        return $coupon->fresh();
    }

    public function deleteCoupon(Coupon $coupon): void
    {
        $coupon->delete();
    }

    public function validateCoupon(string $code, float $orderAmount): array
    {
        $coupon = Coupon::where('code', $code)->first();
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Invalid coupon code.'];
        }
        if (!$coupon->isValid($orderAmount)) {
            return ['valid' => false, 'message' => 'Coupon is not valid for this order.'];
        }

        return [
            'valid' => true,
            'discount' => $coupon->calculateDiscount($orderAmount),
            'coupon' => $coupon,
        ];
    }

    // Shipping Zones

    public function listShippingZones(): Collection
    {
        return ShippingZone::with('districts:id,district_name')->orderBy('name')->get();
    }

    public function createShippingZone(array $attributes, array $districtIds = []): ShippingZone
    {
        $zone = ShippingZone::create($attributes);
        if ($districtIds) {
            // Each district can only belong to one zone (pivot UNIQUE). Detach
            // any prior assignment first so re-assigning a district doesn't
            // raise an integrity violation.
            \DB::table('shipping_zone_districts')->whereIn('district_id', $districtIds)->delete();
            $zone->districts()->sync($districtIds);
        }
        return $zone->load('districts');
    }

    public function updateShippingZone(ShippingZone $zone, array $attributes, ?array $districtIds = null): ShippingZone
    {
        $zone->update($attributes);
        if ($districtIds !== null) {
            \DB::table('shipping_zone_districts')
                ->whereIn('district_id', $districtIds)
                ->where('shipping_zone_id', '!=', $zone->id)
                ->delete();
            $zone->districts()->sync($districtIds);
        }
        return $zone->fresh('districts');
    }

    public function deleteShippingZone(ShippingZone $zone): void
    {
        $zone->delete();
    }

    public function toggleStatus(ShippingZone $zone): ShippingZone
    {
        $zone->update(['is_active' => ! $zone->is_active]);

        return $zone;
    }

    // Settings

    public function getSettings(): array
    {
        return EcommerceSetting::pluck('value', 'key')->toArray();
    }

    public function updateSettings(array $data): void
    {
        foreach ($data as $key => $value) {
            EcommerceSetting::set($key, $value);
        }

        // The price separator flag is cached by the storefront price helper;
        // drop it so the new value takes effect immediately.
        Cache::forget('ecommerce.price_thousands_separator');
    }

    /**
     * Resolved contact-page content for the storefront (admin-managed).
     *
     * @return array<string, mixed>
     */
    public function getContactPageData(): array
    {
        $banner = EcommerceSetting::get('contact_banner');

        return [
            'contactHeading'  => EcommerceSetting::get('contact_heading') ?: 'Get In Touch 👋',
            'contactPhones'   => $this->decodeContactList(EcommerceSetting::get('contact_phones')),
            'contactEmails'   => $this->decodeContactList(EcommerceSetting::get('contact_emails')),
            'contactMapEmbed' => EcommerceSetting::get('contact_map_embed') ?: null,
            'contactBanner'   => $banner ? upload_url($banner) : asset('website/assets/images/contact_message.jpg'),
        ];
    }

    /**
     * Decode a stored JSON list of contact values (phones / emails) into a
     * clean array of strings. Tolerates empty or legacy non-JSON values.
     *
     * @return array<int, string>
     */
    public function decodeContactList(?string $json): array
    {
        $list = json_decode((string) $json, true);

        return is_array($list)
            ? array_values(array_filter(array_map('trim', $list), fn ($v) => $v !== ''))
            : [];
    }

    /**
     * Normalize a posted list of contact values (e.g. emails) into a JSON
     * array string: trims, drops blanks, and de-duplicates while preserving
     * order. Returns '[]' when nothing usable is provided.
     *
     * @param  array<int, string>|null  $values
     */
    public function normalizeContactList(?array $values): string
    {
        $clean = [];
        foreach ((array) $values as $value) {
            $value = trim((string) $value);
            if ($value !== '' && !in_array($value, $clean, true)) {
                $clean[] = $value;
            }
        }

        return json_encode($clean);
    }

    /**
     * Normalize a posted list of phone numbers into a JSON array string of
     * bare digits (Bangladesh local format, e.g. 01712345678). Strips any
     * spacing/dashes/dial-code punctuation, drops blanks, and de-duplicates.
     * Display formatting (01712-345678) is applied via PhoneHelper::format().
     *
     * @param  array<int, string>|null  $values
     */
    public function normalizePhoneList(?array $values): string
    {
        $clean = [];
        foreach ((array) $values as $value) {
            $digits = preg_replace('/\D+/', '', (string) $value);

            // Convert a leading Bangladesh dial code (880…) to the local
            // 0-prefixed form so 11-digit BD numbers format consistently.
            if (str_starts_with($digits, '880')) {
                $digits = '0' . substr($digits, 3);
            }

            if ($digits !== '' && !in_array($digits, $clean, true)) {
                $clean[] = $digits;
            }
        }

        return json_encode($clean);
    }

    /**
     * Handle the contact-page banner image upload / removal.
     */
    public function handleContactBanner(bool $remove = false, $file = null): void
    {
        $existing = EcommerceSetting::get('contact_banner');

        if (($remove || $file) && $existing) {
            Upload::delete($existing);
            EcommerceSetting::set('contact_banner', '');
        }

        if ($file) {
            EcommerceSetting::set('contact_banner', Upload::store($file, 'contact'));
        }
    }

    /**
     * Handle SEO image uploads: seo_default_image and seo_org_logo.
     * Stores the file path back into EcommerceSetting (string key).
     */
    public function handleSeoImages(\Illuminate\Http\Request $request): void
    {
        foreach (['seo_default_image', 'seo_org_logo'] as $field) {
            if ($request->hasFile($field) && $request->file($field)->isValid()) {
                $existing = EcommerceSetting::get($field);
                if ($existing) {
                    Upload::delete($existing);
                }
                EcommerceSetting::set($field, Upload::store($request->file($field), 'seo'));
            }
        }
    }

    /**
     * Handle storefront image uploads / removals for the customer auth pages
     * (login, register, forgot, reset) and the blog sidebar ad. Each field
     * stores a relative upload path under EcommerceSetting; the storefront
     * falls back to a bundled theme asset when a key is empty.
     */
    public function handleStorefrontImages(\Illuminate\Http\Request $request): void
    {
        $fields = [
            'auth_login_image',
            'auth_register_image',
            'auth_forgot_image',
            'auth_reset_image',
            'footer_bg_image',
            'page_banner_bg',
            'payment_icon',
            'faq_image',
        ];

        foreach ($fields as $field) {
            $existing = EcommerceSetting::get($field);

            // Replace: a new file was uploaded — drop the old one, store the new.
            if ($request->hasFile($field) && $request->file($field)->isValid()) {
                if ($existing) {
                    Upload::delete($existing);
                }
                EcommerceSetting::set($field, Upload::store($request->file($field), 'storefront'));
                continue;
            }

            // Remove: the image-upload component flags removal via remove_<field>=1.
            if ($request->input('remove_' . $field) === '1' && $existing) {
                Upload::delete($existing);
                EcommerceSetting::set($field, '');
            }
        }
    }

    /**
     * Normalize a pasted Google Maps embed value to a bare src URL.
     * Accepts either a full <iframe …src="…"> snippet or a plain URL, and
     * rejects anything that isn't an http(s) URL (defends the iframe src).
     */
    public function normalizeMapEmbed(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        // Extract the src="…" when an admin pastes the whole <iframe> tag.
        if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/i', $value, $m)) {
            $value = $m[1];
        }

        return preg_match('/^https?:\/\//i', $value) ? $value : '';
    }
}
