@extends('core::layouts.master')

@section('title', __('Website Settings'))
@section('page-title', __('Website Settings'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Settings</span>
@endsection

@section('page-actions')
    @bpCan('ecommerce.edit')
    <button type="submit" form="ecommerceSettingsForm" class="bp-btn bp-btn-success">
        <i class="fa-solid fa-save me-1"></i> Save Settings
    </button>
    @endbpCan
@endsection

@section('content')

    <form action="{{ route('ecommerce.settings.update') }}" method="POST" id="ecommerceSettingsForm"
        enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            <!-- Left Column: Settings Sections -->
            <div class="col-xl-8">

                <!-- Price Display -->
                <div class="bp-card mb-4" id="priceDisplay">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-bangladeshi-taka-sign me-2"></i>Price Display</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="form-check form-switch">
                            <input type="hidden" name="price_thousands_separator" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="priceThousandsSeparator"
                                name="price_thousands_separator" value="1"
                                {{ ($settings['price_thousands_separator'] ?? '0') === '1' ? 'checked' : '' }}>
                            <label class="form-check-label fw-600" for="priceThousandsSeparator">Show thousands separator
                                in storefront prices</label>
                        </div>
                        <div class="fs-11 text-muted mt-1">When on, prices show a comma (e.g. {{ currency_symbol() }}
                            1,190). When off, no comma ({{ currency_symbol() }} 1190).</div>
                    </div>
                </div>

                <!-- Contact Page -->
                <div class="bp-card mb-4" id="contactPage">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-headset me-2"></i>Contact Page</h5>
                    </div>
                    <div class="bp-card-body">
                        <p class="fs-12 text-muted mb-3">Controls the storefront Contact Us page. Add one or more phone
                            numbers and email addresses below — leave both lists empty to fall back to
                            <strong>Settings → Business Profile</strong>. The Our Location card and map use the business
                            address.
                        </p>
                        <div class="row g-3">
                            @php
                                $oldPhones = old('contact_phones');
                                $contactPhones =
                                    $oldPhones !== null
                                        ? $oldPhones
                                        : array_map(
                                            fn($p) => \App\Helpers\PhoneHelper::formatIntl($p),
                                            json_decode($settings['contact_phones'] ?? '[]', true) ?: [],
                                        );
                                $oldEmails = old('contact_emails');
                                $contactEmails =
                                    $oldEmails !== null
                                        ? $oldEmails
                                        : (json_decode($settings['contact_emails'] ?? '[]', true) ?:
                                        []);
                                if (empty($contactPhones)) {
                                    $contactPhones = [''];
                                }
                                if (empty($contactEmails)) {
                                    $contactEmails = [''];
                                }
                            @endphp
                            <div class="col-md-6">
                                <label class="bp-form-label">Phone Numbers</label>
                                <div id="contactPhonesContainer">
                                    @foreach ($contactPhones as $i => $phone)
                                        <div class="d-flex gap-2 mb-2 contact-phone-row">
                                            <input type="text"
                                                class="bp-form-control @error('contact_phones.' . $i) is-invalid @enderror"
                                                name="contact_phones[]" value="{{ $phone }}"
                                                placeholder="+8801XXXXXXXXX">
                                            <button type="button"
                                                class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-contact-row"
                                                title="Remove"><i class="fa-solid fa-xmark"></i></button>
                                        </div>
                                        @error('contact_phones.' . $i)
                                            <div class="text-danger fs-11 mb-2">{{ $message }}</div>
                                        @enderror
                                    @endforeach
                                </div>
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-success" id="addContactPhone">
                                    <i class="fa-solid fa-plus me-1"></i> Add Phone
                                </button>
                                <div class="fs-11 text-muted mt-1">Bangladesh number, shown as +880 (e.g.
                                    +8801712345678). Appears in the “Call Us” card. Leave empty to use the business phone
                                    number.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Email Addresses</label>
                                <div id="contactEmailsContainer">
                                    @foreach ($contactEmails as $i => $email)
                                        <div class="d-flex gap-2 mb-2 contact-email-row">
                                            <input type="email"
                                                class="bp-form-control @error('contact_emails.' . $i) is-invalid @enderror"
                                                name="contact_emails[]" value="{{ $email }}"
                                                placeholder="support@example.com">
                                            <button type="button"
                                                class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-contact-row"
                                                title="Remove"><i class="fa-solid fa-xmark"></i></button>
                                        </div>
                                        @error('contact_emails.' . $i)
                                            <div class="text-danger fs-11 mb-2">{{ $message }}</div>
                                        @enderror
                                    @endforeach
                                </div>
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-success" id="addContactEmail">
                                    <i class="fa-solid fa-plus me-1"></i> Add Email
                                </button>
                                <div class="fs-11 text-muted mt-1">Shown in the “Email Us” card. Leave empty to use the
                                    business email.</div>
                            </div>

                            <div class="col-md-12">
                                @if (!empty($settings['contact_banner']))
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="remove_contact_banner"
                                            value="1" id="removeContactBanner">
                                        <label class="form-check-label fs-12" for="removeContactBanner">Remove current
                                            banner</label>
                                    </div>
                                @endif
                                <x-core::image-upload name="contact_banner" label="Contact Image"
                                    hint="Recommended: portrait image (~520×585px), JPG/PNG. Leave blank to keep the default banner."
                                    :current="!empty($settings['contact_banner'])
                                        ? upload_url($settings['contact_banner'])
                                        : null" />
                            </div>


                            <div class="col-md-12">
                                <label class="bp-form-label">Contact Form Heading</label>
                                <input type="text" class="bp-form-control" name="contact_heading"
                                    value="{{ old('contact_heading', $settings['contact_heading'] ?? '') }}"
                                    placeholder="Get In Touch 👋">
                                <div class="fs-11 text-muted mt-1">Heading shown above the contact form. Leave blank for the
                                    default.</div>
                            </div>



                            <div class="col-md-12">
                                <label class="bp-form-label">Google Map Embed</label>
                                <textarea class="bp-form-control" name="contact_map_embed" rows="2"
                                    placeholder="Paste the Google Maps “Embed a map” iframe or its src URL">{{ old('contact_map_embed', $settings['contact_map_embed'] ?? '') }}</textarea>
                                <div class="fs-11 text-muted mt-1">In Google Maps: <strong>Share → Embed a map → Copy
                                        HTML</strong>, then paste here. Leave blank to auto-generate the map from the
                                    business address.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Authentication -->
                <div class="bp-card mb-4" id="customerAuth">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-user-shield me-2"></i>Customer Authentication</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="bp-form-label">Customer Registration/Login</label>
                                <select class="bp-form-select w-100" name="customer_auth_enabled">
                                    <option value="1"
                                        {{ old('customer_auth_enabled', $settings['customer_auth_enabled'] ?? '1') == '1' ? 'selected' : '' }}>
                                        Enabled — Show login/register on storefront</option>
                                    <option value="0"
                                        {{ old('customer_auth_enabled', $settings['customer_auth_enabled'] ?? '1') == '0' ? 'selected' : '' }}>
                                        Disabled — Hide login/register from storefront</option>
                                </select>
                                <div class="fs-11 text-muted mt-1">When disabled, login/register links and forms are
                                    hidden. Guest checkout still works.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Registration OTP Verification</label>
                                <select class="bp-form-select w-100" name="customer_otp_required">
                                    <option value="1"
                                        {{ old('customer_otp_required', $settings['customer_otp_required'] ?? '1') == '1' ? 'selected' : '' }}>
                                        Enabled — Require phone OTP before registration</option>
                                    <option value="0"
                                        {{ old('customer_otp_required', $settings['customer_otp_required'] ?? '1') == '0' ? 'selected' : '' }}>
                                        Disabled — Allow registration without OTP</option>
                                </select>
                                <div class="fs-11 text-muted mt-1">When enabled, new customers must verify their
                                    phone via an SMS code before the signup form appears. Reduces spam signups.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Auth Images -->
                <div class="bp-card mb-4" id="storefrontImages">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-images me-2"></i>Auth Images</h5>
                    </div>
                    <div class="bp-card-body">
                        <p class="fs-12 text-muted mb-3">Side illustrations on the storefront login, registration, forgot
                            &amp; reset password pages. Leave any blank to keep the bundled default.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <x-core::image-upload name="auth_login_image" label="Login Page Image"
                                    remove-name="remove_auth_login_image"
                                    hint="Recommended: portrait ~400×500px, JPG/PNG/WebP" :current="!empty($settings['auth_login_image'])
                                        ? upload_url($settings['auth_login_image'])
                                        : null" />
                            </div>
                            <div class="col-md-6">
                                <x-core::image-upload name="auth_register_image" label="Registration Page Image"
                                    remove-name="remove_auth_register_image"
                                    hint="Recommended: portrait ~400×500px, JPG/PNG/WebP" :current="!empty($settings['auth_register_image'])
                                        ? upload_url($settings['auth_register_image'])
                                        : null" />
                            </div>
                            <div class="col-md-6">
                                <x-core::image-upload name="auth_forgot_image" label="Forgot Password Page Image"
                                    remove-name="remove_auth_forgot_image"
                                    hint="Recommended: portrait ~400×500px, JPG/PNG/WebP" :current="!empty($settings['auth_forgot_image'])
                                        ? upload_url($settings['auth_forgot_image'])
                                        : null" />
                            </div>
                            <div class="col-md-6">
                                <x-core::image-upload name="auth_reset_image" label="Reset Password Page Image"
                                    remove-name="remove_auth_reset_image"
                                    hint="Recommended: portrait ~400×500px, JPG/PNG/WebP" :current="!empty($settings['auth_reset_image'])
                                        ? upload_url($settings['auth_reset_image'])
                                        : null" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bp-card mb-4" id="footerSettings">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-window-maximize me-2"></i>Footer</h5>
                    </div>
                    <div class="bp-card-body">
                        <p class="fs-12 text-muted mb-3">Background image shown behind the storefront footer. Leave blank
                            to keep the bundled default.</p>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <x-core::image-upload name="footer_bg_image" label="Footer Background Image"
                                    remove-name="remove_footer_bg_image"
                                    hint="Recommended: wide image (~1920×400px), JPG/PNG/WebP" :current="!empty($settings['footer_bg_image'])
                                        ? upload_url($settings['footer_bg_image'])
                                        : null" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Page Banner -->
                <div class="bp-card mb-4" id="pageBannerSettings">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-bars-staggered me-2"></i>Breadcrumb</h5>
                    </div>
                    <div class="bp-card-body">
                        <p class="fs-12 text-muted mb-3">Background image shown behind the breadcrumb banner at the top of
                            inner storefront pages (Shop, Combo, Blog, etc.). Leave blank to keep the bundled default.</p>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <x-core::image-upload name="page_banner_bg" label="Breadcrumb Banner Image"
                                    remove-name="remove_page_banner_bg"
                                    hint="Recommended: wide image (~1920×400px), JPG/PNG/WebP" :current="!empty($settings['page_banner_bg'])
                                        ? upload_url($settings['page_banner_bg'])
                                        : null" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Icons (Footer) -->
                <div class="bp-card mb-4" id="paymentIcons">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-credit-card me-2"></i>Footer Payment Icons</h5>
                    </div>
                    <div class="bp-card-body">
                        <p class="fs-12 text-muted mb-3">A single image showing the payment methods you accept, displayed
                            in the storefront footer. Leave blank to hide the footer payment section.</p>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <x-core::image-upload name="payment_icon" label="Payment Image"
                                    remove-name="remove_payment_icon"
                                    hint="Recommended: a wide strip of payment logos, JPG/PNG/WebP" :current="!empty($settings['payment_icon'])
                                        ? upload_url($settings['payment_icon'])
                                        : null" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Page -->
                <div class="bp-card mb-4" id="faqPage">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-circle-question me-2"></i>FAQ Page</h5>
                    </div>
                    <div class="bp-card-body">
                        <p class="fs-12 text-muted mb-3">Heading area shown above the question list on the storefront FAQ
                            page. Leave any field blank to keep the bundled default.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="bp-form-label">Subtitle <span class="text-muted">(small text above the heading)</span></label>
                                <input type="text" name="faq_sub_title" class="bp-form-control" maxlength="255"
                                    value="{{ $settings['faq_sub_title'] ?? '' }}" placeholder="general question here">
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Heading</label>
                                <input type="text" name="faq_title" class="bp-form-control" maxlength="255"
                                    value="{{ $settings['faq_title'] ?? '' }}"
                                    placeholder="Some Frequently Asked Questions.">
                            </div>
                            <div class="col-md-12">
                                <x-core::image-upload name="faq_image" label="FAQ Image"
                                    remove-name="remove_faq_image"
                                    hint="Recommended: portrait ~600×590px, JPG/PNG/WebP" :current="!empty($settings['faq_image'])
                                        ? upload_url($settings['faq_image'])
                                        : null" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO Settings -->
                <div class="bp-card mb-4" id="seoSettings">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-magnifying-glass me-2"></i>SEO Settings</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="bp-form-label">Site Name</label>
                                <input type="text" class="bp-form-control" name="seo_site_name"
                                    value="{{ old('seo_site_name', $settings['seo_site_name'] ?? '') }}"
                                    placeholder="e.g. MyStore">
                                <div class="fs-11 text-muted mt-1">Appended to page titles (e.g. Product — MyStore)</div>
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Title Separator</label>
                                <input type="text" class="bp-form-control" name="seo_title_separator"
                                    value="{{ old('seo_title_separator', $settings['seo_title_separator'] ?? '—') }}"
                                    placeholder="e.g. — or |" maxlength="10">
                            </div>
                            <div class="col-md-12">
                                <label class="bp-form-label">Default Meta Title</label>
                                <input type="text" class="bp-form-control" name="seo_default_title"
                                    value="{{ old('seo_default_title', $settings['seo_default_title'] ?? '') }}"
                                    placeholder="Default page title for the storefront">
                                <div class="fs-11 text-muted mt-1">Used when a page has no specific title set. Recommended:
                                    50–60 characters.</div>
                            </div>
                            <div class="col-md-12">
                                <label class="bp-form-label">Default Meta Description</label>
                                <textarea class="bp-form-control" name="seo_default_description" rows="2"
                                    placeholder="Default meta description for all storefront pages">{{ old('seo_default_description', $settings['seo_default_description'] ?? '') }}</textarea>
                                <div class="fs-11 text-muted mt-1">Recommended: 120–160 characters.</div>
                            </div>
                            <div class="col-md-12">
                                <x-core::image-upload name="seo_default_image" label="Default OG / Social Share Image"
                                    hint="Recommended: 1200×630px, JPG/PNG. Used as the default Open Graph image."
                                    :current="!empty($settings['seo_default_image'])
                                        ? upload_url($settings['seo_default_image'])
                                        : null" />
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Twitter Handle</label>
                                <input type="text" class="bp-form-control" name="seo_twitter_handle"
                                    value="{{ old('seo_twitter_handle', $settings['seo_twitter_handle'] ?? '') }}"
                                    placeholder="@yourhandle">
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Organisation Name</label>
                                <input type="text" class="bp-form-control" name="seo_org_name"
                                    value="{{ old('seo_org_name', $settings['seo_org_name'] ?? '') }}"
                                    placeholder="Legal or brand name for structured data">
                            </div>
                            <div class="col-md-12">
                                <x-core::image-upload name="seo_org_logo" label="Organisation Logo"
                                    hint="Used in JSON-LD Organisation structured data. Recommended: square PNG/SVG."
                                    :current="!empty($settings['seo_org_logo'])
                                        ? upload_url($settings['seo_org_logo'])
                                        : null" />
                            </div>
                            <div class="col-md-12">
                                <label class="bp-form-label">Google Site Verification</label>
                                <input type="text" class="bp-form-control" name="google_site_verification"
                                    value="{{ old('google_site_verification', $settings['google_site_verification'] ?? '') }}"
                                    placeholder="Paste the content value from Google Search Console meta tag">
                                <div class="fs-11 text-muted mt-1">Content value only — without the full meta tag markup.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sitemap -->
                <div class="bp-card mb-4" id="sitemap">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-sitemap me-2"></i>Sitemap</h5>
                    </div>
                    <div class="bp-card-body">
                        <p class="fs-12 text-muted mb-3">Generate <code>sitemap.xml</code> for search engines. It lists the
                            home, shop, categories, blog and flash-deals pages plus all active products, active categories
                            and published blog posts, and refreshes <code>robots.txt</code>. It is also regenerated
                            automatically once a day.</p>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            @bpCan('ecommerce.edit')
                            <button type="submit" form="generateSitemapForm" class="bp-btn bp-btn-primary">
                                <i class="fa-solid fa-rotate me-1"></i> Generate Sitemap Now
                            </button>
                            @endbpCan
                            @if (!empty($sitemapInfo['generated_at']))
                                <span class="fs-12 text-muted">
                                    <i class="fa-solid fa-clock me-1"></i>
                                    Last generated:
                                    {{ $sitemapInfo['generated_at']->timezone(config('app.timezone'))->format('d M Y, h:i A') }}
                                </span>
                                <a href="{{ $sitemapInfo['url'] }}" target="_blank" rel="noopener"
                                    class="bp-btn bp-btn-sm bp-btn-outline">
                                    <i class="fa-solid fa-up-right-from-square me-1"></i> View sitemap.xml
                                </a>
                            @else
                                <span class="fs-12 text-muted"><i class="fa-solid fa-circle-info me-1"></i> Not generated
                                    yet.</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Per-Page SEO -->
                <div class="bp-card mb-4" id="perPageSeo">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-file-lines me-2"></i>Per-Page SEO</h5>
                    </div>
                    <div class="bp-card-body">
                        <p class="fs-12 text-muted mb-3">Override the default SEO title, description, and robots directive
                            for each static storefront page.</p>
                        @php
                            $seoPageLabels = [
                                'home' => 'Home Page',
                                'shop' => 'Shop / All Products',
                                'categories' => 'Categories',
                                'blog' => 'Blog',
                                'flash-deals' => 'Flash Deals',
                            ];
                        @endphp
                        @foreach ($seoPageLabels as $pageKey => $pageLabel)
                            @php $sp = $seoPages[$pageKey] ?? null; @endphp
                            <div class="bp-settings-group mb-4">
                                <div class="fw-700 fs-14 mb-3"><i
                                        class="fa-solid fa-file me-2 text-muted"></i>{{ $pageLabel }}</div>
                                <div class="row g-3 ms-4">
                                    <div class="col-md-12">
                                        <label class="bp-form-label">SEO Title</label>
                                        <input type="text" class="bp-form-control"
                                            name="seo_pages[{{ $pageKey }}][title]"
                                            value="{{ old('seo_pages.' . $pageKey . '.title', $sp->title ?? '') }}"
                                            placeholder="Leave blank to use the default title">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="bp-form-label">Meta Description</label>
                                        <textarea class="bp-form-control" name="seo_pages[{{ $pageKey }}][description]" rows="2"
                                            placeholder="Leave blank to use the default description">{{ old('seo_pages.' . $pageKey . '.description', $sp->description ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="bp-form-label">Robots</label>
                                        <select class="bp-form-select w-100"
                                            name="seo_pages[{{ $pageKey }}][robots]">
                                            @php $currentRobots = old('seo_pages.' . $pageKey . '.robots', $sp->robots ?? 'index,follow'); @endphp
                                            <option value="index,follow"
                                                {{ $currentRobots === 'index,follow' ? 'selected' : '' }}>index, follow
                                            </option>
                                            <option value="noindex,follow"
                                                {{ $currentRobots === 'noindex,follow' ? 'selected' : '' }}>noindex, follow
                                            </option>
                                            <option value="noindex,nofollow"
                                                {{ $currentRobots === 'noindex,nofollow' ? 'selected' : '' }}>noindex,
                                                nofollow</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            @if (!$loop->last)
                                <hr>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Pagination -->
                <div class="bp-card mb-4" id="pagination">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-table-cells me-2"></i>Pagination</h5>
                    </div>
                    <div class="bp-card-body">
                        <p class="fs-12 text-muted mb-3">How many items each storefront listing shows per page (1–100). On
                            the Shop page this sets the default; customers can still change it from the "Show" dropdown.</p>
                        @php
                            $perPageFields = [
                                'per_page_shop' => ['label' => 'Shop / All Products', 'default' => 12],
                                'per_page_categories' => ['label' => 'Categories', 'default' => 24],
                                'per_page_blog' => ['label' => 'Blog', 'default' => 9],
                                'per_page_flash_deals' => ['label' => 'Flash Deals', 'default' => 12],
                                'per_page_combos' => ['label' => 'Combo Packages', 'default' => 12],
                            ];
                        @endphp
                        <div class="row g-3">
                            @foreach ($perPageFields as $ppKey => $ppMeta)
                                <div class="col-md-6">
                                    <label class="bp-form-label">{{ $ppMeta['label'] }}</label>
                                    <input type="number" class="bp-form-control" name="{{ $ppKey }}" min="1" max="100"
                                        step="1"
                                        value="{{ old($ppKey, $settings[$ppKey] ?? $ppMeta['default']) }}">
                                    @error($ppKey)
                                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Navigation -->
            <div class="col-xl-4">

                <!-- Quick Navigation -->
                <div class="bp-card mb-4" style="position: sticky; top: 80px;">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Sections</h5>
                    </div>
                    <div class="bp-card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="#contactPage" class="list-group-item list-group-item-action">
                                <i class="fa-solid fa-headset me-2 text-muted"></i> Contact Page
                            </a>
                            <a href="#customerAuth" class="list-group-item list-group-item-action">
                                <i class="fa-solid fa-user-shield me-2 text-muted"></i> Customer Auth
                            </a>
                            <a href="#storefrontImages" class="list-group-item list-group-item-action">
                                <i class="fa-solid fa-images me-2 text-muted"></i> Auth Images
                            </a>
                            <a href="#footerSettings" class="list-group-item list-group-item-action">
                                <i class="fa-solid fa-window-maximize me-2 text-muted"></i> Footer
                            </a>
                            <a href="#pageBannerSettings" class="list-group-item list-group-item-action">
                                <i class="fa-solid fa-bars-staggered me-2 text-muted"></i> Breadcrumb
                            </a>
                            <a href="#paymentIcons" class="list-group-item list-group-item-action">
                                <i class="fa-solid fa-credit-card me-2 text-muted"></i> Payment Icons
                            </a>
                            <a href="#faqPage" class="list-group-item list-group-item-action">
                                <i class="fa-solid fa-circle-question me-2 text-muted"></i> FAQ Page
                            </a>
                            <a href="#seoSettings" class="list-group-item list-group-item-action">
                                <i class="fa-solid fa-magnifying-glass me-2 text-muted"></i> SEO Settings
                            </a>
                            <a href="#sitemap" class="list-group-item list-group-item-action">
                                <i class="fa-solid fa-sitemap me-2 text-muted"></i> Sitemap
                            </a>
                            <a href="#perPageSeo" class="list-group-item list-group-item-action">
                                <i class="fa-solid fa-file-lines me-2 text-muted"></i> Per-Page SEO
                            </a>
                            <a href="#pagination" class="list-group-item list-group-item-action">
                                <i class="fa-solid fa-table-cells me-2 text-muted"></i> Pagination
                            </a>
                        </div>
                    </div>
                    @bpCan('ecommerce.edit')
                    <div class="bp-card-footer">
                        <button type="submit" form="ecommerceSettingsForm"
                            class="bp-btn bp-btn-success w-100 justify-content-center">
                            <i class="fa-solid fa-save me-2"></i> Save All Settings
                        </button>
                    </div>
                    @endbpCan
                </div>

            </div>
        </div>

    </form>

    {{-- Standalone form for the sitemap action. Kept outside the settings form
     because HTML forms cannot be nested; the button targets it via form="". --}}
    <form id="generateSitemapForm" action="{{ route('ecommerce.settings.sitemap.generate') }}" method="POST"
        class="d-none">
        @csrf
    </form>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Contact repeaters: add a new phone / email row, remove any row.
            $('#addContactPhone').on('click', function() {
                $('#contactPhonesContainer').append(
                    '<div class="d-flex gap-2 mb-2 contact-phone-row">' +
                    '<input type="text" class="bp-form-control" name="contact_phones[]" placeholder="+8801XXXXXXXXX">' +
                    '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-contact-row" title="Remove"><i class="fa-solid fa-xmark"></i></button>' +
                    '</div>'
                );
            });
            $('#addContactEmail').on('click', function() {
                $('#contactEmailsContainer').append(
                    '<div class="d-flex gap-2 mb-2 contact-email-row">' +
                    '<input type="email" class="bp-form-control" name="contact_emails[]" placeholder="support@example.com">' +
                    '<button type="button" class="bp-btn bp-btn-sm bp-btn-icon bp-btn-danger remove-contact-row" title="Remove"><i class="fa-solid fa-xmark"></i></button>' +
                    '</div>'
                );
            });
            $(document).on('click', '.remove-contact-row', function() {
                $(this).closest('.contact-phone-row, .contact-email-row').remove();
            });

            // Smooth scroll for section navigation
            $('.list-group-item').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                $('html, body').animate({
                    scrollTop: $(target).offset().top - 90
                }, 300);
            });

        });
    </script>
@endpush
