@extends('core::layouts.master')

@section('title', __('Settings'))
@section('page-title', __('Settings'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Settings</span>
@endsection

@section('page-actions')
@endsection

@section('content')

    <div class="row g-4">
        <!-- Settings Sidebar Nav -->
        <div class="col-xl-3 col-lg-4">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-sliders me-2"></i>Configuration</h5>
                </div>
                <div class="bp-card-body p-2">
                    <nav id="settingsNav">
                        <a class="bp-settings-nav-item active" href="#businessSettings" data-bs-toggle="tab">
                            <div class="bp-settings-nav-icon icon-primary"><i class="fa-solid fa-building"></i></div>
                            <div class="bp-settings-nav-text">
                                <div class="bp-settings-nav-label">Business Profile</div>
                                <div class="bp-settings-nav-desc">Company info, logo, tax IDs</div>
                            </div>
                            <i class="fa-solid fa-chevron-right bp-settings-nav-arrow"></i>
                        </a>
                        <a class="bp-settings-nav-item" href="#appearanceSettings" data-bs-toggle="tab">
                            <div class="bp-settings-nav-icon icon-accent"><i class="fa-solid fa-palette"></i></div>
                            <div class="bp-settings-nav-text">
                                <div class="bp-settings-nav-label">Appearance</div>
                                <div class="bp-settings-nav-desc">Storefront color scheme</div>
                            </div>
                            <i class="fa-solid fa-chevron-right bp-settings-nav-arrow"></i>
                        </a>
                        <a class="bp-settings-nav-item" href="#taxSettings" data-bs-toggle="tab">
                            <div class="bp-settings-nav-icon icon-warning"><i class="fa-solid fa-percent"></i></div>
                            <div class="bp-settings-nav-text">
                                <div class="bp-settings-nav-label">Tax / VAT</div>
                                <div class="bp-settings-nav-desc">Tax rates, Mushak forms</div>
                            </div>
                            <i class="fa-solid fa-chevron-right bp-settings-nav-arrow"></i>
                        </a>
                        <a class="bp-settings-nav-item" href="#courierSettings" data-bs-toggle="tab">
                            <div class="bp-settings-nav-icon icon-accent"><i class="fa-solid fa-truck-fast"></i></div>
                            <div class="bp-settings-nav-text">
                                <div class="bp-settings-nav-label">Courier & Delivery</div>
                                <div class="bp-settings-nav-desc">Pathao, Steadfast, eCourier</div>
                            </div>
                            <i class="fa-solid fa-chevron-right bp-settings-nav-arrow"></i>
                        </a>
                        <a class="bp-settings-nav-item" href="#invoiceSettings" data-bs-toggle="tab">
                            <div class="bp-settings-nav-icon icon-secondary"><i class="fa-solid fa-file-invoice"></i></div>
                            <div class="bp-settings-nav-text">
                                <div class="bp-settings-nav-label">Invoice & Receipt</div>
                                <div class="bp-settings-nav-desc">Templates, numbering, print</div>
                            </div>
                            <i class="fa-solid fa-chevron-right bp-settings-nav-arrow"></i>
                        </a>
                        <a class="bp-settings-nav-item" href="#localeSettings" data-bs-toggle="tab">
                            <div class="bp-settings-nav-icon icon-primary"><i class="fa-solid fa-language"></i></div>
                            <div class="bp-settings-nav-text">
                                <div class="bp-settings-nav-label">Localization</div>
                                <div class="bp-settings-nav-desc">Language, date, currency format</div>
                            </div>
                            <i class="fa-solid fa-chevron-right bp-settings-nav-arrow"></i>
                        </a>
                        <a class="bp-settings-nav-item" href="#smsSettings" data-bs-toggle="tab">
                            <div class="bp-settings-nav-icon icon-info"><i class="fa-solid fa-message"></i></div>
                            <div class="bp-settings-nav-text">
                                <div class="bp-settings-nav-label">SMS Gateway</div>
                                <div class="bp-settings-nav-desc">BulkSMSBD</div>
                            </div>
                            <i class="fa-solid fa-chevron-right bp-settings-nav-arrow"></i>
                        </a>
                        {{-- Landing Page settings hidden for now (feature WIP). Remove `d-none` here and on the tab-pane below to restore. --}}
                        <a class="bp-settings-nav-item d-none" href="#landingPageSettings" data-bs-toggle="tab">
                            <div class="bp-settings-nav-icon icon-accent"><i class="fa-solid fa-rocket"></i></div>
                            <div class="bp-settings-nav-text">
                                <div class="bp-settings-nav-label">Landing Page</div>
                                <div class="bp-settings-nav-desc">Landing mode, active page</div>
                            </div>
                            <i class="fa-solid fa-chevron-right bp-settings-nav-arrow"></i>
                        </a>
                        <a class="bp-settings-nav-item" href="#trackingSettings" data-bs-toggle="tab">
                            <div class="bp-settings-nav-icon icon-info"><i class="fa-solid fa-chart-simple"></i></div>
                            <div class="bp-settings-nav-text">
                                <div class="bp-settings-nav-label">Tracking & Analytics</div>
                                <div class="bp-settings-nav-desc">GTM, Facebook Pixel</div>
                            </div>
                            <i class="fa-solid fa-chevron-right bp-settings-nav-arrow"></i>
                        </a>
                        <a class="bp-settings-nav-item" href="#webhookSettings" data-bs-toggle="tab">
                            <div class="bp-settings-nav-icon icon-secondary"><i class="fa-solid fa-plug"></i></div>
                            <div class="bp-settings-nav-text">
                                <div class="bp-settings-nav-label">Webhooks</div>
                                <div class="bp-settings-nav-desc">External integrations & triggers</div>
                            </div>
                            <i class="fa-solid fa-chevron-right bp-settings-nav-arrow"></i>
                        </a>
                        <a class="bp-settings-nav-item" href="#systemSettings" data-bs-toggle="tab">
                            <div class="bp-settings-nav-icon icon-danger"><i class="fa-solid fa-screwdriver-wrench"></i>
                            </div>
                            <div class="bp-settings-nav-text">
                                <div class="bp-settings-nav-label">System Maintenance</div>
                                <div class="bp-settings-nav-desc">Clear cache, maintenance mode</div>
                            </div>
                            <i class="fa-solid fa-chevron-right bp-settings-nav-arrow"></i>
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="col-xl-9 col-lg-8">
            <div class="tab-content">

                <!-- ================================================================
                 BUSINESS PROFILE
                 ================================================================ -->
                <div class="tab-pane fade show active" id="businessSettings">
                    <div class="bp-settings-section-header mb-4">
                        <div class="bp-settings-section-icon icon-primary"><i class="fa-solid fa-building"></i></div>
                        <div>
                            <h4 class="bp-settings-section-title">Business Profile</h4>
                            <p class="bp-settings-section-desc">Update your company information, legal identifiers, and
                                financial settings. This information appears on all invoices and receipts.</p>
                        </div>
                    </div>

                    <form action="{{ route('settings.update', 'business') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <!-- Company Info Card -->
                        <div class="bp-card mb-4">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-id-card me-2 text-primary"></i>Company
                                    Information</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Company Name *</label>
                                        <input type="text" class="bp-form-control" name="company_name"
                                            value="{{ $settings['business']['company_name'] ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Company Phone *</label>
                                        <input type="text" class="bp-form-control" name="company_phone"
                                            value="{{ $settings['business']['company_phone'] ?? '' }}" data-phone>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Company Email</label>
                                        <input type="email" class="bp-form-control" name="company_email"
                                            value="{{ $settings['business']['company_email'] ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Website</label>
                                        <input type="text" class="bp-form-control" name="website"
                                            value="{{ $settings['business']['website'] ?? '' }}" placeholder="bizpos.com">
                                        <small class="text-muted fs-11">Just type the domain (e.g. <code>bizpos.com</code>)
                                            — we'll save it as a full address.</small>
                                    </div>
                                    <div class="col-12">
                                        <label class="bp-form-label">Address</label>
                                        <textarea class="bp-form-control" name="address" rows="2">{{ $settings['business']['address'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Legal & Tax IDs Card -->
                        <div class="bp-card mb-4">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-stamp me-2 text-warning"></i>Legal & Tax
                                    Identifiers</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="bp-form-label">TIN Number</label>
                                        <input type="text" class="bp-form-control" name="tin"
                                            value="{{ $settings['business']['tin'] ?? '' }}" placeholder="12-digit TIN">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="bp-form-label">BIN Number</label>
                                        <input type="text" class="bp-form-control" name="bin"
                                            value="{{ $settings['business']['bin'] ?? '' }}"
                                            placeholder="VAT Registration">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Trade License #</label>
                                        <input type="text" class="bp-form-control" name="trade_license"
                                            value="{{ $settings['business']['trade_license'] ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Social Links Card -->
                        <div class="bp-card mb-4">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-share-nodes me-2 text-info"></i>Social
                                    Links</h5>
                            </div>
                            <div class="bp-card-body">
                                <p class="fs-12 text-muted mb-3">Shown in the storefront footer. Leave blank to hide that
                                    icon.</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="bp-form-label"><i class="fa-brands fa-facebook-f me-1"></i>
                                            Facebook</label>
                                        <input type="url" class="bp-form-control" name="facebook_url"
                                            value="{{ $settings['business']['facebook_url'] ?? '' }}"
                                            placeholder="https://facebook.com/your-page">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label"><i class="fa-brands fa-instagram me-1"></i>
                                            Instagram</label>
                                        <input type="url" class="bp-form-control" name="instagram_url"
                                            value="{{ $settings['business']['instagram_url'] ?? '' }}"
                                            placeholder="https://instagram.com/your-handle">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label"><i class="fa-brands fa-youtube me-1"></i>
                                            YouTube</label>
                                        <input type="url" class="bp-form-control" name="youtube_url"
                                            value="{{ $settings['business']['youtube_url'] ?? '' }}"
                                            placeholder="https://youtube.com/@your-channel">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label"><i class="fa-brands fa-x-twitter me-1"></i> Twitter /
                                            X</label>
                                        <input type="url" class="bp-form-control" name="twitter_url"
                                            value="{{ $settings['business']['twitter_url'] ?? '' }}"
                                            placeholder="https://twitter.com/your-handle">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label"><i class="fa-brands fa-linkedin-in me-1"></i>
                                            LinkedIn</label>
                                        <input type="url" class="bp-form-control" name="linkedin_url"
                                            value="{{ $settings['business']['linkedin_url'] ?? '' }}"
                                            placeholder="https://linkedin.com/company/your-co">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label"><i class="fa-brands fa-tiktok me-1"></i>
                                            TikTok</label>
                                        <input type="url" class="bp-form-control" name="tiktok_url"
                                            value="{{ $settings['business']['tiktok_url'] ?? '' }}"
                                            placeholder="https://tiktok.com/@your-handle">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label"><i class="fa-brands fa-whatsapp me-1"></i>
                                            WhatsApp</label>
                                        <input type="text" class="bp-form-control" name="whatsapp_number"
                                            value="{{ $settings['business']['whatsapp_number'] ?? '' }}"
                                            placeholder="+8801XXXXXXXXX">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Branding & Finance Card -->
                        <div class="bp-card mb-4">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-palette me-2 text-secondary"></i>Branding
                                    & Finance</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Company Logo</label>
                                        <div class="bp-logo-upload-box">
                                            <div class="bp-logo-preview" id="logoPreview" data-placeholder-icon="image">
                                                @if (!empty($settings['business']['logo']))
                                                    <img src="{{ upload_url($settings['business']['logo']) }}"
                                                        alt="Company Logo">
                                                @else
                                                    <i class="fa-solid fa-image"></i>
                                                @endif
                                            </div>
                                            <div class="bp-logo-upload-info">
                                                <p class="fs-12 fw-600 mb-1">Upload Logo</p>
                                                <p class="fs-11 text-muted mb-2">PNG, JPG — Max 1MB</p>
                                                <label class="bp-btn bp-btn-sm bp-btn-outline bp-cursor-pointer">
                                                    <i class="fa-solid fa-upload me-1"></i> Browse
                                                    <input type="file" name="logo" id="logoInput" accept="image/*"
                                                        class="d-none" data-preview-target="#logoPreview">
                                                </label>
                                            </div>
                                        </div>

                                        <label class="bp-form-label mt-3">Favicon</label>
                                        <div class="bp-logo-upload-box">
                                            <div class="bp-logo-preview" id="faviconPreview"
                                                data-placeholder-icon="star">
                                                @if (!empty($settings['business']['favicon']))
                                                    <img src="{{ upload_url($settings['business']['favicon']) }}"
                                                        alt="Favicon">
                                                @else
                                                    <i class="fa-solid fa-star"></i>
                                                @endif
                                            </div>
                                            <div class="bp-logo-upload-info">
                                                <p class="fs-12 fw-600 mb-1">Upload Favicon</p>
                                                <p class="fs-11 text-muted mb-2">PNG or ICO — 32x32 / 64x64</p>
                                                <label class="bp-btn bp-btn-sm bp-btn-outline bp-cursor-pointer">
                                                    <i class="fa-solid fa-upload me-1"></i> Browse
                                                    <input type="file" name="favicon" id="faviconInput"
                                                        accept="image/png,image/x-icon,image/vnd.microsoft.icon"
                                                        class="d-none" data-preview-target="#faviconPreview">
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="bp-form-label">Financial Year Starts</label>
                                                <select class="bp-form-select w-100" name="financial_year_start">
                                                    <option
                                                        {{ ($settings['business']['financial_year_start'] ?? '') === 'January' ? 'selected' : '' }}>
                                                        January</option>
                                                    <option
                                                        {{ ($settings['business']['financial_year_start'] ?? 'July (Bangladesh Standard)') === 'July (Bangladesh Standard)' ? 'selected' : '' }}>
                                                        July (Bangladesh Standard)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="bp-form-label">Business Start Date</label>
                                                <input type="date" class="bp-form-control" name="business_start_date"
                                                    max="{{ now()->toDateString() }}"
                                                    value="{{ $settings['business']['business_start_date'] ?? '' }}">
                                                <div class="bp-form-hint">Reports and courier settlements ignore anything
                                                    dated before this. Leave blank to include all history.</div>
                                            </div>
                                            <input type="hidden" name="timezone" value="Asia/Dhaka (GMT+6)">
                                            <input type="hidden" name="primary_currency" value="BDT - Bangladeshi Taka">
                                            <div class="col-12">
                                                <hr class="my-2">
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch mt-2">
                                                    <input class="form-check-input" type="checkbox" id="allowBackdate"
                                                        name="allow_backdate"
                                                        {{ ($settings['business']['allow_backdate'] ?? '0') === '1' ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-600 fs-13" for="allowBackdate">Allow
                                                        Back-dated Transactions</label>
                                                </div>
                                                <div class="fs-11 text-muted mt-1">When disabled, users cannot create
                                                    sales, purchases, expenses, or payments with a date before today.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bp-card-footer text-end">
                                <button type="button" class="bp-btn bp-btn-danger me-2"><i
                                        class="fa-solid fa-xmark me-1"></i>Cancel</button>
                                <button type="submit" class="bp-btn bp-btn-success"><i
                                        class="fa-solid fa-save me-1"></i> Save Business Info</button>
                            </div>
                        </div>
                    </form>

                    {{-- Installable app (PWA) — regenerate manifest.json so the install
                         prompt + home-screen icon use the business name above. --}}
                    <div class="bp-card mt-3">
                        <div class="bp-card-header">
                            <h5 class="bp-card-title"><i class="fa-solid fa-mobile-screen-button me-2"></i>Installable App
                                (PWA)</h5>
                        </div>
                        <div class="bp-card-body">
                            <p class="fs-13 text-muted mb-3">When BizPOS is installed to a home screen, the install prompt
                                and app icon use a saved app name. Generate the manifest to set it to your business name
                                — <strong>{{ $settings['business']['company_name'] ?? 'BizPOS Pro' }}</strong>. Run this
                                again whenever you change the business name above (save first).</p>
                            <form method="POST" action="{{ route('settings.manifest.generate') }}">
                                @csrf
                                <button type="submit" class="bp-btn bp-btn-primary"><i
                                        class="fa-solid fa-rotate me-1"></i> Generate App Manifest</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ================================================================
                 APPEARANCE — COLOR SCHEME
                 ================================================================ -->
                <div class="tab-pane fade" id="appearanceSettings">
                    <div class="bp-settings-section-header mb-4">
                        <div class="bp-settings-nav-icon icon-accent bp-settings-section-icon-lg"><i
                                class="fa-solid fa-palette"></i></div>
                        <div>
                            <h4 class="bp-settings-section-title">Appearance — Color Scheme</h4>
                            <p class="bp-settings-section-desc">Customize the storefront color scheme. Changes apply to the
                                full website only (not landing pages). Leave blank to use default colors.</p>
                        </div>
                    </div>

                    <form action="{{ route('ecommerce.settings.appearance.update') }}" method="POST">
                        @csrf
                        <div class="bp-card mb-4">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-palette me-2 text-primary"></i>Storefront
                                    Colors</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    @php
                                        $colorFields = [
                                            [
                                                'key' => 'theme_primary_color',
                                                'label' => 'Primary Color',
                                                'default' => '#ffa500',
                                                'desc' => 'Buttons, links, focus states, headers',
                                            ],
                                            [
                                                'key' => 'theme_secondary_color',
                                                'label' => 'Secondary Color',
                                                'default' => '#0A69D8',
                                                'desc' => 'Badges, pagination active, flash sales',
                                            ],
                                            [
                                                'key' => 'theme_accent_color',
                                                'label' => 'Accent Color',
                                                'default' => '#FFD43A',
                                                'desc' => 'Discount badges, sale buttons, highlights',
                                            ],
                                            [
                                                'key' => 'theme_success_color',
                                                'label' => 'Success Color',
                                                'default' => '#05A845',
                                                'desc' => 'In stock, success badges, confirmation',
                                            ],
                                            [
                                                'key' => 'theme_danger_color',
                                                'label' => 'Danger Color',
                                                'default' => '#DB4437',
                                                'desc' => 'Out of stock, error states, delete',
                                            ],
                                            [
                                                'key' => 'theme_light_bg',
                                                'label' => 'Light Background',
                                                'default' => '#edf5ff',
                                                'desc' => 'Section backgrounds, category cards',
                                            ],
                                        ];
                                    @endphp
                                    @foreach ($colorFields as $field)
                                        @php
                                            $curVal = old(
                                                $field['key'],
                                                \Modules\Ecommerce\Models\EcommerceSetting::get($field['key']) ?:
                                                $field['default'],
                                            );
                                        @endphp
                                        <div class="col-md-6">
                                            <label class="bp-form-label">{{ $field['label'] }}</label>
                                            <div class="d-flex gap-2 align-items-center">
                                                <input type="color" class="bp-form-color bp-color-picker"
                                                    name="{{ $field['key'] }}" value="{{ $curVal }}"
                                                    data-default="{{ $field['default'] }}">
                                                <input type="text" class="bp-form-control bp-color-hex"
                                                    value="{{ $curVal }}" placeholder="{{ $field['default'] }}"
                                                    maxlength="7">
                                                <button type="button"
                                                    class="bp-btn bp-btn-sm bp-btn-icon bp-btn-outline bp-color-reset"
                                                    title="Reset to default"><i
                                                        class="fa-solid fa-rotate-left"></i></button>
                                            </div>
                                            <div class="fs-11 text-muted mt-1">{{ $field['desc'] }} — Default:
                                                {{ $field['default'] }}</div>
                                            @error($field['key'])
                                                <div class="text-danger fs-11 mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="bp-card-footer text-end">
                                <button type="submit" class="bp-btn bp-btn-success"><i
                                        class="fa-solid fa-save me-1"></i> Save Color Scheme</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ================================================================
                 TAX / VAT
                 ================================================================ -->
                <div class="tab-pane fade" id="taxSettings">
                    <div class="bp-settings-section-header mb-4">
                        <div class="bp-settings-nav-icon icon-warning bp-settings-section-icon-lg"><i
                                class="fa-solid fa-percent"></i></div>
                        <div>
                            <h4 class="bp-settings-section-title">Tax / VAT Settings</h4>
                            <p class="bp-settings-section-desc">Configure VAT rates as per NBR Bangladesh regulations and
                                set up Mushak form printing preferences.</p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <x-core::table>
                            <x-slot:filters>
                                <h5 class="bp-card-title"><i class="fa-solid fa-percent me-2 text-warning"></i>VAT Rate
                                    Configuration</h5>
                                @bpCan('settings.edit')
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-primary" id="btnAddTaxRate"
                                    data-bs-toggle="modal" data-bs-target="#taxRateModal">
                                    <i class="fa-solid fa-plus me-1"></i> Add Tax Rate
                                </button>
                                @endbpCan
                            </x-slot:filters>

                            <x-core::table.header>
                                <x-core::table.column>Tax Name</x-core::table.column>
                                <x-core::table.column>Rate</x-core::table.column>
                                <x-core::table.column>Type</x-core::table.column>
                                <x-core::table.column>Apply To</x-core::table.column>
                                <x-core::table.column>Default</x-core::table.column>
                                <x-core::table.column>Status</x-core::table.column>
                                <x-core::table.column align="right">Actions</x-core::table.column>
                            </x-core::table.header>

                            <tbody>
                                @forelse($taxRates as $rate)
                                    <tr>
                                        <td>
                                            <div class="fw-700">{{ $rate->name }}</div>
                                            @if ($rate->description)
                                                <div class="fs-11 text-muted">{{ $rate->description }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $badgeClass = match ($rate->type) {
                                                    'percentage' => 'bp-badge-warning',
                                                    'fixed' => 'bp-badge-info',
                                                    'exempt' => 'bp-badge-dark',
                                                    default => 'bp-badge-secondary',
                                                };
                                                $rateDisplay =
                                                    $rate->type === 'exempt'
                                                        ? '0%'
                                                        : ($rate->type === 'fixed'
                                                            ? currency_symbol() . ' ' . num((float) $rate->rate)
                                                            : rtrim(
                                                                    rtrim(
                                                                        number_format((float) $rate->rate, 4, '.', ''),
                                                                        '0',
                                                                    ),
                                                                    '.',
                                                                ) . '%');
                                            @endphp
                                            <span class="bp-badge {{ $badgeClass }}">{{ $rateDisplay }}</span>
                                        </td>
                                        <td class="text-capitalize">{{ $rate->type }}</td>
                                        <td>{{ $rate->apply_to ?: '' }}</td>
                                        <td>
                                            @if ($rate->is_default)
                                                <span class="bp-badge bp-badge-success"><i
                                                        class="fa-solid fa-check me-1"></i>Default</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($rate->is_active)
                                                <span class="bp-badge bp-badge-success">Active</span>
                                            @else
                                                <span class="bp-badge bp-badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @bpCan('settings.edit')
                                            <div class="dropdown">
                                                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button type="button"
                                                            class="dropdown-item btn-edit-tax-rate"
                                                            data-id="{{ $rate->id }}" data-name="{{ $rate->name }}"
                                                            data-rate="{{ $rate->rate }}" data-type="{{ $rate->type }}"
                                                            data-apply-to="{{ $rate->apply_to }}"
                                                            data-description="{{ $rate->description }}"
                                                            data-is-default="{{ $rate->is_default ? 1 : 0 }}"
                                                            data-is-active="{{ $rate->is_active ? 1 : 0 }}"
                                                            data-update-url="{{ route('settings.tax-rates.update', $rate->id) }}"
                                                            data-bs-toggle="modal" data-bs-target="#taxRateModal">
                                                            <i class="fa-solid fa-pen me-2"></i> Edit
                                                        </button>
                                                    </li>
                                                    @unless ($rate->is_default)
                                                    <li>
                                                        <form action="{{ route('settings.tax-rates.destroy', $rate->id) }}"
                                                            method="POST" onsubmit="return confirm('Delete this tax rate?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fa-solid fa-trash me-2"></i> Delete
                                                            </button>
                                                        </form>
                                                    </li>
                                                    @endunless
                                                </ul>
                                            </div>
                                            @endbpCan
                                        </td>
                                    </tr>
                                @empty
                                    <x-core::table.empty :colspan="7" icon="fa-percent"
                                        title="No tax rates configured yet."
                                        description="Click &quot;Add Tax Rate&quot; to create one." />
                                @endforelse
                            </tbody>
                        </x-core::table>
                    </div>

                    <form action="{{ route('settings.update', 'tax') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="bp-card">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i
                                        class="fa-solid fa-file-contract me-2 text-primary"></i>Mushak Form Settings</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="bp-toggle-card">
                                            <div class="bp-toggle-card-icon icon-primary"><i
                                                    class="fa-solid fa-file-invoice"></i></div>
                                            <div class="bp-toggle-card-body">
                                                <div class="fw-700 fs-13">Mushak 6.3</div>
                                                <div class="fs-11 text-muted">VAT Invoice</div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="mushak_63"
                                                    id="mushak63"
                                                    {{ $settings['tax']['mushak_63'] ?? false ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bp-toggle-card">
                                            <div class="bp-toggle-card-icon icon-warning"><i
                                                    class="fa-solid fa-file-lines"></i></div>
                                            <div class="bp-toggle-card-body">
                                                <div class="fw-700 fs-13">Mushak 6.5</div>
                                                <div class="fs-11 text-muted">Credit Note</div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="mushak_65"
                                                    id="mushak65"
                                                    {{ $settings['tax']['mushak_65'] ?? false ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bp-toggle-card">
                                            <div class="bp-toggle-card-icon icon-secondary"><i
                                                    class="fa-solid fa-calendar-check"></i></div>
                                            <div class="bp-toggle-card-body">
                                                <div class="fw-700 fs-13">Mushak 9.1</div>
                                                <div class="fs-11 text-muted">Monthly Return</div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="mushak_91"
                                                    id="mushak91"
                                                    {{ $settings['tax']['mushak_91'] ?? false ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bp-card-footer text-end">
                                <button type="submit" class="bp-btn bp-btn-success"><i
                                        class="fa-solid fa-save me-1"></i> Save Tax Settings</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ================================================================
                 COURIER & DELIVERY
                 ================================================================ -->
                <div class="tab-pane fade" id="courierSettings">
                    <div class="bp-settings-section-header mb-4">
                        <div class="bp-settings-nav-icon icon-accent bp-settings-section-icon-lg"><i
                                class="fa-solid fa-truck-fast"></i></div>
                        <div>
                            <h4 class="bp-settings-section-title">Courier & Delivery Management</h4>
                            <p class="bp-settings-section-desc">Connect delivery partners, configure COD settings, and
                                automate shipment booking directly from sales orders.</p>
                        </div>
                    </div>

                    <form action="{{ route('settings.update', 'courier') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <!-- Default Courier -->
                        <div class="bp-card mb-4">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-star me-2 text-warning"></i>Default
                                    Courier Settings</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Default Courier Partner</label>
                                        <select class="bp-form-select w-100" name="default_courier">
                                            <option
                                                {{ empty($settings['courier']['default_courier'] ?? '') ? 'selected' : '' }}>
                                                Select Default Courier</option>
                                            <option
                                                {{ ($settings['courier']['default_courier'] ?? '') === 'Pathao Courier' ? 'selected' : '' }}>
                                                Pathao Courier</option>
                                            <option
                                                {{ ($settings['courier']['default_courier'] ?? '') === 'Steadfast Courier' ? 'selected' : '' }}>
                                                Steadfast Courier</option>
                                            <option
                                                {{ ($settings['courier']['default_courier'] ?? '') === 'eCourier' ? 'selected' : '' }}>
                                                eCourier</option>
                                            <option
                                                {{ ($settings['courier']['default_courier'] ?? '') === 'Redx' ? 'selected' : '' }}>
                                                Redx</option>
                                            <option
                                                {{ ($settings['courier']['default_courier'] ?? '') === 'Paperfly' ? 'selected' : '' }}>
                                                Paperfly</option>
                                            <option
                                                {{ ($settings['courier']['default_courier'] ?? '') === 'Sundarban Courier' ? 'selected' : '' }}>
                                                Sundarban Courier</option>
                                            <option
                                                {{ ($settings['courier']['default_courier'] ?? '') === 'SA Paribahan' ? 'selected' : '' }}>
                                                SA Paribahan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Auto-Book Shipment</label>
                                        <select class="bp-form-select w-100" name="auto_book">
                                            <option
                                                {{ ($settings['courier']['auto_book'] ?? 'On Invoice Creation') === 'On Invoice Creation' ? 'selected' : '' }}>
                                                On Invoice Creation</option>
                                            <option
                                                {{ ($settings['courier']['auto_book'] ?? '') === 'On Payment Received' ? 'selected' : '' }}>
                                                On Payment Received</option>
                                            <option
                                                {{ ($settings['courier']['auto_book'] ?? '') === 'Manually' ? 'selected' : '' }}>
                                                Manually</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Default Delivery Type</label>
                                        <select class="bp-form-select w-100" name="delivery_type">
                                            <option
                                                {{ ($settings['courier']['delivery_type'] ?? 'Regular Delivery') === 'Regular Delivery' ? 'selected' : '' }}>
                                                Regular Delivery</option>
                                            <option
                                                {{ ($settings['courier']['delivery_type'] ?? '') === 'Same Day' ? 'selected' : '' }}>
                                                Same Day</option>
                                            <option
                                                {{ ($settings['courier']['delivery_type'] ?? '') === 'Next Day' ? 'selected' : '' }}>
                                                Next Day</option>
                                            <option
                                                {{ ($settings['courier']['delivery_type'] ?? '') === 'Express' ? 'selected' : '' }}>
                                                Express</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bp-toggle-card">
                                            <div class="bp-toggle-card-icon icon-success"><i
                                                    class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                                            <div class="bp-toggle-card-body">
                                                <div class="fw-700 fs-13">COD Collection</div>
                                                <div class="fs-11 text-muted">Cash on Delivery</div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="cod_enabled"
                                                    id="codEnabled"
                                                    {{ $settings['courier']['cod_enabled'] ?? false ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bp-toggle-card">
                                            <div class="bp-toggle-card-icon icon-primary"><i
                                                    class="fa-solid fa-location-dot"></i></div>
                                            <div class="bp-toggle-card-body">
                                                <div class="fw-700 fs-13">Live Tracking</div>
                                                <div class="fs-11 text-muted">Show tracking in orders</div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="tracking_enabled"
                                                    id="trackingEnabled"
                                                    {{ $settings['courier']['tracking_enabled'] ?? false ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bp-toggle-card">
                                            <div class="bp-toggle-card-icon icon-info"><i class="fa-solid fa-message"></i>
                                            </div>
                                            <div class="bp-toggle-card-body">
                                                <div class="fw-700 fs-13">SMS Notification</div>
                                                <div class="fs-11 text-muted">Notify customer on dispatch</div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="courier_sms"
                                                    id="courierSms"
                                                    {{ $settings['courier']['courier_sms'] ?? false ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Courier Partners -->
                        <div class="bp-card mb-4">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-truck-fast me-2"></i>Courier Partner API
                                    Credentials</h5>
                                @bpCan('settings.edit')
                                <button type="button" class="bp-btn bp-btn-sm bp-btn-primary"><i
                                        class="fa-solid fa-plus me-1"></i> Add Courier</button>
                                @endbpCan
                            </div>
                            <div class="bp-card-body" id="courierCardsContainer">

                                <!-- Pathao -->
                                @php $pathaoEnabled = $settings['courier']['pathao_enabled'] ?? false; @endphp
                                <div class="bp-courier-card mb-3" data-courier-name="Pathao Courier">
                                    <div class="bp-courier-card-header">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bp-courier-logo"><i class="fa-solid fa-truck-fast"></i></div>
                                            <div>
                                                <div class="fw-800 fs-14">Pathao Courier</div>
                                                <div class="fs-11 text-muted">pathao.com - Inside Dhaka & Nationwide</div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span
                                                class="bp-badge {{ $pathaoEnabled ? 'bp-badge-success' : 'bp-badge-dark' }}">{{ $pathaoEnabled ? 'Connected' : 'Not Connected' }}</span>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="pathao_enabled"
                                                    {{ $pathaoEnabled ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bp-courier-card-body {{ $pathaoEnabled ? '' : 'd-none' }}">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="bp-form-label">Client ID</label>
                                                <input type="text" class="bp-form-control" name="pathao_client_id"
                                                    value="{{ $settings['courier']['pathao_client_id'] ?? '' }}">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="bp-form-label">Client Secret</label>
                                                <input type="password" class="bp-form-control"
                                                    name="pathao_client_secret"
                                                    value="{{ $settings['courier']['pathao_client_secret'] ?? '' }}">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="bp-form-label">Merchant ID</label>
                                                <input type="text" class="bp-form-control" name="pathao_merchant_id"
                                                    value="{{ $settings['courier']['pathao_merchant_id'] ?? '' }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="bp-form-label">Inside Dhaka
                                                    ({{ currency_symbol() }})</label>
                                                <input type="number" class="bp-form-control" name="pathao_inside_dhaka"
                                                    value="{{ $settings['courier']['pathao_inside_dhaka'] ?? '' }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="bp-form-label">Outside Dhaka
                                                    ({{ currency_symbol() }})</label>
                                                <input type="number" class="bp-form-control" name="pathao_outside_dhaka"
                                                    value="{{ $settings['courier']['pathao_outside_dhaka'] ?? '' }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="bp-form-label">COD Charge (%)</label>
                                                <input type="number" class="bp-form-control" name="pathao_cod_charge"
                                                    value="{{ $settings['courier']['pathao_cod_charge'] ?? '' }}"
                                                    step="0.1">
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end">
                                                <button type="button"
                                                    class="bp-btn bp-btn-sm bp-btn-outline w-100 bp-conn-test-btn"
                                                    data-url="{{ route('settings.courier.test', 'pathao') }}"
                                                    data-fields='{"client_id":"pathao_client_id","client_secret":"pathao_client_secret"}'
                                                    data-loading="{{ __('Contacting courier API...') }}"
                                                    data-result="#pathaoTestResult">
                                                    <i class="fa-solid fa-rotate me-1"></i> Test Connection
                                                </button>
                                            </div>
                                            <div class="col-12 bp-courier-test-result d-none" id="pathaoTestResult"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Steadfast -->
                                @php $steadfastEnabled = $settings['courier']['steadfast_enabled'] ?? false; @endphp
                                <div class="bp-courier-card mb-3" data-courier-name="Steadfast Courier">
                                    <div class="bp-courier-card-header">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bp-courier-logo bp-courier-logo-secondary"><i
                                                    class="fa-solid fa-box"></i></div>
                                            <div>
                                                <div class="fw-800 fs-14">Steadfast Courier</div>
                                                <div class="fs-11 text-muted">steadfast-courier.com - Nationwide</div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span
                                                class="bp-badge {{ $steadfastEnabled ? 'bp-badge-success' : 'bp-badge-dark' }}">{{ $steadfastEnabled ? 'Connected' : 'Not Connected' }}</span>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="steadfast_enabled"
                                                    {{ $steadfastEnabled ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bp-courier-card-body {{ $steadfastEnabled ? '' : 'd-none' }}">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="bp-form-label">API Key</label>
                                                <input type="password" class="bp-form-control" name="steadfast_api_key"
                                                    value="{{ $settings['courier']['steadfast_api_key'] ?? '' }}">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="bp-form-label">API Secret</label>
                                                <input type="password" class="bp-form-control"
                                                    name="steadfast_api_secret"
                                                    value="{{ $settings['courier']['steadfast_api_secret'] ?? '' }}">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="bp-form-label">Webhook URL</label>
                                                <input type="text" class="bp-form-control"
                                                    value="{{ url('/api/webhook/receive') }}" readonly>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="bp-form-label">Inside Dhaka
                                                    ({{ currency_symbol() }})</label>
                                                <input type="number" class="bp-form-control"
                                                    name="steadfast_inside_dhaka"
                                                    value="{{ $settings['courier']['steadfast_inside_dhaka'] ?? '' }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="bp-form-label">Outside Dhaka
                                                    ({{ currency_symbol() }})</label>
                                                <input type="number" class="bp-form-control"
                                                    name="steadfast_outside_dhaka"
                                                    value="{{ $settings['courier']['steadfast_outside_dhaka'] ?? '' }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="bp-form-label">COD Charge (%)</label>
                                                <input type="number" class="bp-form-control" name="steadfast_cod_charge"
                                                    value="{{ $settings['courier']['steadfast_cod_charge'] ?? '' }}"
                                                    step="0.1">
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end">
                                                <button type="button"
                                                    class="bp-btn bp-btn-sm bp-btn-outline w-100 bp-conn-test-btn"
                                                    data-url="{{ route('settings.courier.test', 'steadfast') }}"
                                                    data-fields='{"api_key":"steadfast_api_key","api_secret":"steadfast_api_secret"}'
                                                    data-loading="{{ __('Contacting courier API...') }}"
                                                    data-result="#steadfastTestResult">
                                                    <i class="fa-solid fa-rotate me-1"></i> Test Connection
                                                </button>
                                            </div>
                                            <div class="col-12 bp-courier-test-result d-none" id="steadfastTestResult">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- eCourier -->
                                @php $ecourierEnabled = $settings['courier']['ecourier_enabled'] ?? false; @endphp
                                <div class="bp-courier-card mb-3" data-courier-name="eCourier">
                                    <div class="bp-courier-card-header">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bp-courier-logo bp-courier-logo-warning"><i
                                                    class="fa-solid fa-motorcycle"></i></div>
                                            <div>
                                                <div class="fw-800 fs-14">eCourier</div>
                                                <div class="fs-11 text-muted">ecourier.com.bd - Express & Regular</div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span
                                                class="bp-badge {{ $ecourierEnabled ? 'bp-badge-success' : 'bp-badge-dark' }}">{{ $ecourierEnabled ? 'Connected' : 'Not Connected' }}</span>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="ecourier_enabled"
                                                    {{ $ecourierEnabled ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bp-courier-card-body {{ $ecourierEnabled ? '' : 'd-none' }}">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="bp-form-label">API Key</label>
                                                <input type="text" class="bp-form-control" name="ecourier_api_key"
                                                    value="{{ $settings['courier']['ecourier_api_key'] ?? '' }}"
                                                    placeholder="Enter eCourier API key">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="bp-form-label">Username</label>
                                                <input type="text" class="bp-form-control" name="ecourier_username"
                                                    value="{{ $settings['courier']['ecourier_username'] ?? '' }}"
                                                    placeholder="Your eCourier username">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="bp-form-label">Password</label>
                                                <input type="password" class="bp-form-control" name="ecourier_password"
                                                    value="{{ $settings['courier']['ecourier_password'] ?? '' }}"
                                                    placeholder="Your password">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Redx -->
                                @php $redxEnabled = $settings['courier']['redx_enabled'] ?? false; @endphp
                                <div class="bp-courier-card mb-3" data-courier-name="Redx">
                                    <div class="bp-courier-card-header">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bp-courier-logo bp-courier-logo-danger"><i
                                                    class="fa-solid fa-truck"></i></div>
                                            <div>
                                                <div class="fw-800 fs-14">Redx</div>
                                                <div class="fs-11 text-muted">redx.com.bd - Parcel & Freight</div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span
                                                class="bp-badge {{ $redxEnabled ? 'bp-badge-success' : 'bp-badge-dark' }}">{{ $redxEnabled ? 'Connected' : 'Not Connected' }}</span>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="redx_enabled"
                                                    {{ $redxEnabled ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Paperfly -->
                                @php $paperflyEnabled = $settings['courier']['paperfly_enabled'] ?? false; @endphp
                                <div class="bp-courier-card" data-courier-name="Paperfly">
                                    <div class="bp-courier-card-header">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bp-courier-logo bp-courier-logo-primary"><i
                                                    class="fa-solid fa-paper-plane"></i></div>
                                            <div>
                                                <div class="fw-800 fs-14">Paperfly</div>
                                                <div class="fs-11 text-muted">paperfly.com.bd - Last Mile Delivery</div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span
                                                class="bp-badge {{ $paperflyEnabled ? 'bp-badge-success' : 'bp-badge-dark' }}">{{ $paperflyEnabled ? 'Connected' : 'Not Connected' }}</span>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="paperfly_enabled"
                                                    {{ $paperflyEnabled ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Return Policy -->
                        <div class="bp-card">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-rotate-left me-2 text-danger"></i>Return &
                                    Delivery Policy</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Return Window (Days)</label>
                                        <input type="number" class="bp-form-control" name="return_window"
                                            value="{{ $settings['courier']['return_window'] ?? '' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Return Courier</label>
                                        <select class="bp-form-select w-100" name="return_courier">
                                            <option
                                                {{ ($settings['courier']['return_courier'] ?? 'Same as delivery courier') === 'Same as delivery courier' ? 'selected' : '' }}>
                                                Same as delivery courier</option>
                                            <option
                                                {{ ($settings['courier']['return_courier'] ?? '') === 'Pathao Courier' ? 'selected' : '' }}>
                                                Pathao Courier</option>
                                            <option
                                                {{ ($settings['courier']['return_courier'] ?? '') === 'Steadfast Courier' ? 'selected' : '' }}>
                                                Steadfast Courier</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Who Pays Return Shipping</label>
                                        <select class="bp-form-select w-100" name="return_shipping_payer">
                                            <option
                                                {{ ($settings['courier']['return_shipping_payer'] ?? 'Buyer (defective exception)') === 'Buyer (defective exception)' ? 'selected' : '' }}>
                                                Buyer (defective exception)</option>
                                            <option
                                                {{ ($settings['courier']['return_shipping_payer'] ?? '') === 'Store (always)' ? 'selected' : '' }}>
                                                Store (always)</option>
                                            <option
                                                {{ ($settings['courier']['return_shipping_payer'] ?? '') === 'Buyer (always)' ? 'selected' : '' }}>
                                                Buyer (always)</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="bp-form-label">Return Policy Text (shown on invoices)</label>
                                        <textarea class="bp-form-control" name="return_policy" rows="2">{{ $settings['courier']['return_policy'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="bp-card-footer text-end">
                                <button type="submit" class="bp-btn bp-btn-success"><i
                                        class="fa-solid fa-save me-1"></i> Save Courier &amp; Delivery Settings</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ================================================================
                 INVOICE & RECEIPT
                 ================================================================ -->
                <div class="tab-pane fade" id="invoiceSettings">
                    <div class="bp-settings-section-header mb-4">
                        <div class="bp-settings-nav-icon icon-secondary bp-settings-section-icon-lg"><i
                                class="fa-solid fa-file-invoice"></i></div>
                        <div>
                            <h4 class="bp-settings-section-title">Invoice & Receipt</h4>
                            <p class="bp-settings-section-desc">Customize invoice numbering, thermal receipt format, and
                                what information appears on customer documents.</p>
                        </div>
                    </div>

                    <form action="{{ route('settings.update', 'invoice') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="bp-card mb-4">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-receipt me-2 text-secondary"></i>Thermal
                                    Receipt Settings</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Receipt Width</label>
                                        <select class="bp-form-select w-100" name="receipt_width">
                                            <option
                                                {{ ($settings['invoice']['receipt_width'] ?? '') === '58mm (Small)' ? 'selected' : '' }}>
                                                58mm (Small)</option>
                                            <option
                                                {{ ($settings['invoice']['receipt_width'] ?? '80mm (Standard)') === '80mm (Standard)' ? 'selected' : '' }}>
                                                80mm (Standard)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Print Accent Color</label>
                                        <input type="color" class="bp-form-control bp-color-input" name="accent_color"
                                            value="{{ $settings['invoice']['accent_color'] ?? '#ED1C24' }}"
                                            title="Brand color used on printed quotations/invoices (side tabs, footer bar, icons)">
                                        <small class="text-muted fs-11 d-block mt-1">Used on printed quotations &amp;
                                            invoices (side tabs, footer bar, icons).</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Invoice Footer Text</label>
                                    </div>
                                    <div class="col-12">
                                        <textarea class="bp-form-control" name="footer_text" rows="2">{{ $settings['invoice']['footer_text'] ?? '' }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="bp-form-label">Terms & Conditions</label>
                                        <textarea class="bp-form-control" name="terms" rows="3">{{ $settings['invoice']['terms'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bp-card">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-toggle-on me-2 text-info"></i>Print &
                                    Display Options</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="bp-toggle-card">
                                            <div class="bp-toggle-card-icon icon-primary"><i
                                                    class="fa-solid fa-image"></i></div>
                                            <div class="bp-toggle-card-body">
                                                <div class="fw-700 fs-13">Show Logo on Receipt</div>
                                                <div class="fs-11 text-muted">Prints company logo</div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="show_logo"
                                                    {{ $settings['invoice']['show_logo'] ?? false ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-none">
                                        <div class="bp-toggle-card">
                                            <div class="bp-toggle-card-icon icon-warning"><i
                                                    class="fa-solid fa-print"></i></div>
                                            <div class="bp-toggle-card-body">
                                                <div class="fw-700 fs-13">Auto Print After Sale</div>
                                                <div class="fs-11 text-muted">Print receipt on POS checkout</div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="auto_print"
                                                    {{ $settings['invoice']['auto_print'] ?? false ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-none">
                                        <div class="bp-toggle-card">
                                            <div class="bp-toggle-card-icon icon-info"><i
                                                    class="fa-solid fa-file-invoice"></i></div>
                                            <div class="bp-toggle-card-body">
                                                <div class="fw-700 fs-13">POS Print Format</div>
                                                <div class="fs-11 text-muted">What to print after POS sale</div>
                                            </div>
                                            <select class="bp-form-select" name="pos_print_format">
                                                <option value="pos_receipt"
                                                    {{ ($settings['invoice']['pos_print_format'] ?? 'pos_receipt') === 'pos_receipt' ? 'selected' : '' }}>
                                                    POS Receipt (Thermal)</option>
                                                <option value="full_invoice"
                                                    {{ ($settings['invoice']['pos_print_format'] ?? '') === 'full_invoice' ? 'selected' : '' }}>
                                                    Full Invoice (A4)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bp-toggle-card">
                                            <div class="bp-toggle-card-icon icon-success"><i
                                                    class="fa-solid fa-percent"></i></div>
                                            <div class="bp-toggle-card-body">
                                                <div class="fw-700 fs-13">Show VAT on Invoice</div>
                                                <div class="fs-11 text-muted">Display VAT line separately</div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="show_vat"
                                                    {{ $settings['invoice']['show_vat'] ?? false ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bp-toggle-card">
                                            <div class="bp-toggle-card-icon icon-secondary"><i
                                                    class="fa-solid fa-language"></i></div>
                                            <div class="bp-toggle-card-body">
                                                <div class="fw-700 fs-13">Amount in Words (Bangla)</div>
                                                <div class="fs-11 text-muted">e.g., Five thousand taka</div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox"
                                                    name="bangla_amount_words"
                                                    {{ $settings['invoice']['bangla_amount_words'] ?? false ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bp-card-footer text-end">
                                <button type="button" class="bp-btn bp-btn-primary me-2" data-bs-toggle="modal"
                                    data-bs-target="#invoicePreviewModal"><i class="fa-solid fa-receipt me-1"></i> Live
                                    Preview</button>
                                @php $previewSale = \Modules\Sale\Models\Sale::latest('id')->first(); @endphp
                                @if ($previewSale)
                                    <a href="{{ route('sales.print', $previewSale) }}" target="_blank"
                                        class="bp-btn bp-btn-warning me-2"><i class="fa-solid fa-eye me-1"></i> Preview
                                        Invoice</a>
                                @else
                                    <button type="button" class="bp-btn bp-btn-outline me-2" disabled
                                        title="No invoices yet"><i class="fa-solid fa-eye me-1"></i> Preview
                                        Invoice</button>
                                @endif
                                <button type="submit" class="bp-btn bp-btn-success"><i
                                        class="fa-solid fa-save me-1"></i> Save Invoice Settings</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ================================================================
                 LOCALIZATION
                 ================================================================ -->
                <div class="tab-pane fade" id="localeSettings">
                    <div class="bp-settings-section-header mb-4">
                        <div class="bp-settings-nav-icon icon-primary bp-settings-section-icon-lg"><i
                                class="fa-solid fa-language"></i></div>
                        <div>
                            <h4 class="bp-settings-section-title">Localization (Bangla Support)</h4>
                            <p class="bp-settings-section-desc">Configure language, date format, number system, and
                                Bangla-specific display options for invoices and UI.</p>
                        </div>
                    </div>

                    <form action="{{ route('settings.update', 'localization') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="bp-card">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-language me-2 text-primary"></i>Regional
                                    Preferences</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Country</label>
                                        @php $currentCountry = $settings['localization']['country'] ?? 'BD'; @endphp
                                        <select class="bp-form-select w-100" name="country" id="settingsCountry">
                                            <option value="BD" {{ $currentCountry === 'BD' ? 'selected' : '' }}>
                                                Bangladesh (+880)</option>
                                            <option value="IN" {{ $currentCountry === 'IN' ? 'selected' : '' }}>India
                                                (+91)</option>
                                            <option value="PK" {{ $currentCountry === 'PK' ? 'selected' : '' }}>
                                                Pakistan (+92)</option>
                                            <option value="LK" {{ $currentCountry === 'LK' ? 'selected' : '' }}>Sri
                                                Lanka (+94)</option>
                                            <option value="NP" {{ $currentCountry === 'NP' ? 'selected' : '' }}>Nepal
                                                (+977)</option>
                                            <option value="MM" {{ $currentCountry === 'MM' ? 'selected' : '' }}>
                                                Myanmar (+95)</option>
                                            <option value="MY" {{ $currentCountry === 'MY' ? 'selected' : '' }}>
                                                Malaysia (+60)</option>
                                            <option value="SG" {{ $currentCountry === 'SG' ? 'selected' : '' }}>
                                                Singapore (+65)</option>
                                            <option value="AE" {{ $currentCountry === 'AE' ? 'selected' : '' }}>UAE
                                                (+971)</option>
                                            <option value="SA" {{ $currentCountry === 'SA' ? 'selected' : '' }}>Saudi
                                                Arabia (+966)</option>
                                            <option value="QA" {{ $currentCountry === 'QA' ? 'selected' : '' }}>Qatar
                                                (+974)</option>
                                            <option value="KW" {{ $currentCountry === 'KW' ? 'selected' : '' }}>
                                                Kuwait (+965)</option>
                                            <option value="OM" {{ $currentCountry === 'OM' ? 'selected' : '' }}>Oman
                                                (+968)</option>
                                            <option value="BH" {{ $currentCountry === 'BH' ? 'selected' : '' }}>
                                                Bahrain (+973)</option>
                                            <option value="US" {{ $currentCountry === 'US' ? 'selected' : '' }}>
                                                United States (+1)</option>
                                            <option value="GB" {{ $currentCountry === 'GB' ? 'selected' : '' }}>
                                                United Kingdom (+44)</option>
                                        </select>
                                        <div class="fs-11 text-muted mt-1">Sets phone number format, placeholder, and
                                            validation across the system</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Default Language</label>
                                        <select class="bp-form-select w-100" name="language">
                                            <option
                                                {{ ($settings['localization']['language'] ?? 'English') === 'English' ? 'selected' : '' }}>
                                                English</option>
                                            <option
                                                {{ ($settings['localization']['language'] ?? '') === 'Bangla' ? 'selected' : '' }}>
                                                Bangla</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Date Format</label>
                                        <select class="bp-form-select w-100" name="date_format">
                                            <option
                                                {{ ($settings['localization']['date_format'] ?? 'DD-MM-YYYY (04-03-2026)') === 'DD-MM-YYYY (04-03-2026)' ? 'selected' : '' }}>
                                                DD-MM-YYYY (04-03-2026)</option>
                                            <option
                                                {{ ($settings['localization']['date_format'] ?? '') === 'YYYY-MM-DD (2026-03-04)' ? 'selected' : '' }}>
                                                YYYY-MM-DD (2026-03-04)</option>
                                            <option
                                                {{ ($settings['localization']['date_format'] ?? '') === 'MM/DD/YYYY (03/04/2026)' ? 'selected' : '' }}>
                                                MM/DD/YYYY (03/04/2026)</option>
                                            <option
                                                {{ ($settings['localization']['date_format'] ?? '') === 'DD MMM YYYY (04 Mar 2026)' ? 'selected' : '' }}>
                                                DD MMM YYYY (04 Mar 2026)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Number Format</label>
                                        <select class="bp-form-select w-100" name="number_format">
                                            <option
                                                {{ ($settings['localization']['number_format'] ?? '12,34,567.89 (BD Standard - Lakh)') === '12,34,567.89 (BD Standard - Lakh)' ? 'selected' : '' }}>
                                                12,34,567.89 (BD Standard - Lakh)</option>
                                            <option
                                                {{ ($settings['localization']['number_format'] ?? '') === '1,234,567.89 (International)' ? 'selected' : '' }}>
                                                1,234,567.89 (International)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Currency Symbol</label>
                                        <input type="text" class="bp-form-control" name="currency_symbol"
                                            maxlength="10"
                                            value="{{ $settings['localization']['currency_symbol'] ?? '৳' }}"
                                            placeholder="৳">
                                        <div class="fs-11 text-muted mt-1">Shown before amounts everywhere, e.g. <span
                                                class="fw-700">{{ $settings['localization']['currency_symbol'] ?? '৳' }}
                                                1,234.00</span>. Common: ৳, BDT, $, €.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bp-toggle-card">
                                            <div class="bp-toggle-card-icon icon-secondary"><i
                                                    class="fa-solid fa-font"></i></div>
                                            <div class="bp-toggle-card-body">
                                                <div class="fw-700 fs-13">Bangla Digits</div>
                                                <div class="fs-11 text-muted">0123456789</div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="bangla_digits"
                                                    id="banglaDigits"
                                                    {{ $settings['localization']['bangla_digits'] ?? false ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bp-toggle-card">
                                            <div class="bp-toggle-card-icon icon-primary"><i
                                                    class="fa-solid fa-receipt"></i></div>
                                            <div class="bp-toggle-card-body">
                                                <div class="fw-700 fs-13">Bangla on Receipt</div>
                                                <div class="fs-11 text-muted">Thermal printer support</div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="bangla_receipt"
                                                    id="banglaReceipt"
                                                    {{ $settings['localization']['bangla_receipt'] ?? false ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bp-toggle-card">
                                            <div class="bp-toggle-card-icon icon-info"><i
                                                    class="fa-solid fa-file-invoice"></i></div>
                                            <div class="bp-toggle-card-body">
                                                <div class="fw-700 fs-13">Bangla Invoice Amount</div>
                                                <div class="fs-11 text-muted">Amount in words</div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="bangla_invoice"
                                                    id="banglaInvoice"
                                                    {{ $settings['localization']['bangla_invoice'] ?? false ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bp-card-footer text-end">
                                <button type="submit" class="bp-btn bp-btn-success"><i
                                        class="fa-solid fa-save me-1"></i> Save Localization</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ================================================================
                 SMS GATEWAY
                 ================================================================ -->
                <div class="tab-pane fade" id="smsSettings">
                    <div class="bp-settings-section-header mb-4">
                        <div class="bp-settings-nav-icon icon-info bp-settings-section-icon-lg"><i
                                class="fa-solid fa-message"></i></div>
                        <div>
                            <h4 class="bp-settings-section-title">SMS Gateway Configuration</h4>
                            <p class="bp-settings-section-desc">Connect an SMS provider to send OTPs, payment receipts,
                                delivery updates, and marketing campaigns.</p>
                        </div>
                    </div>

                    <form action="{{ route('settings.update', 'sms') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="bp-card">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-message me-2 text-info"></i>Provider
                                    Configuration</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="bp-form-label">SMS Provider</label>
                                        <select class="bp-form-select w-100" name="provider">
                                            <option
                                                {{ ($settings['sms']['provider'] ?? 'BulkSMSBD') === 'BulkSMSBD' ? 'selected' : '' }}>
                                                BulkSMSBD</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Sender ID</label>
                                        <input type="text" class="bp-form-control" name="sender_id"
                                            value="{{ $settings['sms']['sender_id'] ?? '' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="bp-form-label">API Key</label>
                                        <input type="text" class="bp-form-control" name="api_key"
                                            value="{{ $settings['sms']['api_key'] ?? '' }}">
                                    </div>
                                    @php $smsConfigured = !empty($settings['sms']['api_key']) && !empty($settings['sms']['sender_id']); @endphp
                                    <div class="col-md-4">
                                        <label class="bp-form-label">SMS Balance</label>
                                        @if ($smsConfigured)
                                            <div
                                                class="bp-form-control d-flex align-items-center fw-600 text-muted bp-sms-balance">
                                                <i class="fa-solid fa-circle-info me-2"></i> Credentials saved — check your
                                                provider panel for live balance
                                            </div>
                                        @else
                                            <div
                                                class="bp-form-control d-flex align-items-center fw-600 text-danger bp-sms-balance">
                                                <i class="fa-solid fa-circle-xmark me-2"></i> Not connected — enter API
                                                credentials to use SMS
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Rates</label>
                                        <div class="bp-form-control fs-12 text-muted d-flex align-items-center">
                                            {{ $smsConfigured ? 'Rates depend on your provider plan' : 'Rates will show after connecting' }}
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="button"
                                            class="bp-btn bp-btn-outline w-100 bp-conn-test-btn"
                                            data-url="{{ route('settings.sms.test') }}"
                                            data-fields='{"api_key":"api_key"}'
                                            data-loading="{{ __('Contacting SMS gateway...') }}"
                                            data-balance-target=".bp-sms-balance"
                                            data-result="#smsTestResult">
                                            <i class="fa-solid fa-rotate me-1"></i> Test Connection
                                        </button>
                                    </div>
                                    <div class="col-md-4">
                                        {{-- No name attribute: this is a test-only field and must not be
                                             persisted with the SMS settings form on Save. --}}
                                        <label class="bp-form-label">Test Number</label>
                                        <input type="text" class="bp-form-control" id="smsTestNumber"
                                            placeholder="01XXXXXXXXX">
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="button"
                                            class="bp-btn bp-btn-outline w-100 bp-conn-test-btn"
                                            data-url="{{ route('settings.sms.send-test') }}"
                                            data-fields='{"api_key":"api_key","sender_id":"sender_id","number":"smsTestNumber"}'
                                            data-loading="{{ __('Sending test SMS...') }}"
                                            data-result="#smsTestResult">
                                            <i class="fa-solid fa-paper-plane me-1"></i> Send Test SMS
                                        </button>
                                    </div>
                                    <div class="col-12 bp-courier-test-result d-none" id="smsTestResult"></div>
                                </div>
                            </div>
                            <div class="bp-card-footer text-end">
                                <button type="submit" class="bp-btn bp-btn-success"><i
                                        class="fa-solid fa-save me-1"></i> Save SMS Settings</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ================================================================
                 LANDING PAGE
                 ================================================================ -->
                <div class="tab-pane fade d-none" id="landingPageSettings">
                    <div class="bp-settings-section-header mb-4">
                        <div class="bp-settings-nav-icon icon-accent bp-settings-section-icon-lg"><i
                                class="fa-solid fa-rocket"></i></div>
                        <div>
                            <h4 class="bp-settings-section-title">Landing Page Settings</h4>
                            <p class="bp-settings-section-desc">Switch between full eCommerce site and single-product
                                landing page mode.</p>
                        </div>
                    </div>

                    @php($lpMode = $settings['landing_page']['mode'] ?? 'full_site')
                    @php($lpActiveId = $settings['landing_page']['active_landing_page_id'] ?? '')

                    <form action="{{ route('settings.update', 'landing_page') }}" method="POST"
                        id="landingPageSettingsForm">
                        @csrf
                        @method('PUT')

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <i class="fa-solid fa-circle-exclamation me-2"></i>
                                <strong>Could not save:</strong>
                                <ul class="mb-0 mt-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="bp-card mb-4">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-toggle-on me-2"></i>Website Mode</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="bp-template-card {{ $lpMode === 'full_site' ? 'active' : '' }}"
                                            data-mode="full_site">
                                            <input type="radio" name="mode" value="full_site"
                                                {{ $lpMode === 'full_site' ? 'checked' : '' }} class="d-none">
                                            <div class="text-center">
                                                <i class="fa-solid fa-globe fs-2 d-block mb-2 text-primary"></i>
                                                <div class="fw-700">Full eCommerce Site</div>
                                                <div class="fs-12 text-muted">Shop, Cart, Checkout, Customer Dashboard —
                                                    all active</div>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-template-card {{ $lpMode === 'landing_page' ? 'active' : '' }}"
                                            data-mode="landing_page">
                                            <input type="radio" name="mode" value="landing_page"
                                                {{ $lpMode === 'landing_page' ? 'checked' : '' }} class="d-none">
                                            <div class="text-center">
                                                <i class="fa-solid fa-bullseye fs-2 d-block mb-2 text-warning"></i>
                                                <div class="fw-700">Landing Page Mode</div>
                                                <div class="fs-12 text-muted">Single landing page only — all storefront
                                                    routes redirect to it</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bp-card mb-4">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-file me-2"></i>Active Landing Page</h5>
                            </div>
                            <div class="bp-card-body">
                                @if ($landingPages->isEmpty())
                                    <div class="bp-empty-state text-center py-3">
                                        <i class="fa-solid fa-file-circle-plus fs-2 text-muted d-block mb-2"></i>
                                        <p class="text-muted mb-2">No landing pages exist yet.</p>
                                        <a href="{{ route('landing-pages.create') }}"
                                            class="bp-btn bp-btn-sm bp-btn-primary"><i
                                                class="fa-solid fa-plus me-1"></i> Create Your First Landing Page</a>
                                    </div>
                                @else
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-6">
                                            <label class="bp-form-label">Select Landing Page</label>
                                            <select
                                                class="bp-form-select w-100 @error('active_landing_page_id') is-invalid @enderror"
                                                name="active_landing_page_id" id="activeLandingPageSelect">
                                                <option value="">None</option>
                                                @foreach ($landingPages as $lp)
                                                    <option value="{{ $lp->id }}"
                                                        data-preview="{{ route('landing-pages.preview', $lp->id) }}"
                                                        {{ (string) $lpActiveId === (string) $lp->id ? 'selected' : '' }}>
                                                        {{ $lp->name }}
                                                        ({{ $lp->template }}){{ $lp->is_active ? ' — Active' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('active_landing_page_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <a href="#" id="previewLandingPageBtn"
                                                class="bp-btn bp-btn-outline w-100 justify-content-center"
                                                target="_blank"><i class="fa-solid fa-eye me-1"></i> Preview</a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="{{ route('landing-pages.index') }}"
                                                class="bp-btn bp-btn-outline w-100 justify-content-center"><i
                                                    class="fa-solid fa-external-link me-1"></i> Manage</a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="bp-card">
                            <div class="bp-card-footer text-end">
                                <button type="submit" class="bp-btn bp-btn-success"><i
                                        class="fa-solid fa-save me-1"></i> Save Landing Page Settings</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ================================================================
                 TRACKING & ANALYTICS
                 ================================================================ -->
                <div class="tab-pane fade" id="trackingSettings">
                    <div class="bp-settings-section-header mb-4">
                        <div class="bp-settings-section-icon icon-info"><i class="fa-solid fa-chart-simple"></i></div>
                        <div>
                            <h4 class="bp-settings-section-title">Tracking & Analytics</h4>
                            <p class="bp-settings-section-desc">Configure Google Tag Manager, Facebook Pixel and
                                Google Analytics 4 for conversion tracking across your storefront and landing pages.</p>
                        </div>
                    </div>

                    <form action="{{ route('settings.update', 'tracking') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- GTM -->
                        <div class="bp-card mb-4">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-code me-2"></i>Google Tag Manager</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Enable GTM</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="gtm_enabled"
                                                id="gtmEnabled"
                                                {{ $settings['tracking']['gtm_enabled'] ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="gtmEnabled">Active</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="bp-form-label">GTM Container ID</label>
                                        <input type="text"
                                            class="bp-form-control @error('gtm_container_id') is-invalid @enderror"
                                            name="gtm_container_id"
                                            value="{{ old('gtm_container_id', $settings['tracking']['gtm_container_id'] ?? '') }}"
                                            placeholder="GTM-XXXXXXX" pattern="GTM-[A-Za-z0-9]{4,10}"
                                            title="Must start with GTM- followed by 4–10 letters/digits, e.g. GTM-ABC1234">
                                        @error('gtm_container_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Facebook Pixel -->
                        <div class="bp-card mb-4">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-brands fa-facebook me-2"></i>Facebook Pixel</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Enable Pixel</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="fbpixel_enabled"
                                                id="fbPixelEnabled"
                                                {{ $settings['tracking']['fbpixel_enabled'] ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="fbPixelEnabled">Active</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="bp-form-label">Pixel ID</label>
                                        <input type="text"
                                            class="bp-form-control @error('fbpixel_id') is-invalid @enderror"
                                            name="fbpixel_id"
                                            value="{{ old('fbpixel_id', $settings['tracking']['fbpixel_id'] ?? '') }}"
                                            placeholder="1234567890" pattern="\d{10,20}"
                                            title="Facebook Pixel ID must be 10–20 digits">
                                        @error('fbpixel_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="bp-form-label">Conversions API Access Token <span
                                                class="text-muted">(optional — server-side events)</span></label>
                                        <input type="password" class="bp-form-control" name="fbpixel_access_token"
                                            value="{{ $settings['tracking']['fbpixel_access_token'] ?? '' }}"
                                            placeholder="EAAxxxxxxx...">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Meta Test Event Code <span class="text-muted">(QA
                                                only)</span></label>
                                        <input type="text" class="bp-form-control" name="fbpixel_test_event_code"
                                            value="{{ old('fbpixel_test_event_code', $settings['tracking']['fbpixel_test_event_code'] ?? '') }}"
                                            placeholder="TEST12345">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Suppress Purchase for risk levels</label>
                                        <input type="text" class="bp-form-control"
                                            name="purchase_block_risk_levels"
                                            value="{{ old('purchase_block_risk_levels', $settings['tracking']['purchase_block_risk_levels'] ?? 'high,critical') }}"
                                            placeholder="high,critical">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Google Analytics 4 -->
                        <div class="bp-card mb-4">
                            <div class="bp-card-header">
                                <h5 class="bp-card-title"><i class="fa-solid fa-chart-line me-2"></i>Google Analytics 4</h5>
                            </div>
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Enable GA4</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="ga4_enabled"
                                                id="ga4Enabled"
                                                {{ $settings['tracking']['ga4_enabled'] ?? false ? 'checked' : '' }}>
                                            <label class="form-check-label" for="ga4Enabled">Active</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="bp-form-label">GA4 Measurement ID</label>
                                        <input type="text"
                                            class="bp-form-control @error('ga4_measurement_id') is-invalid @enderror"
                                            name="ga4_measurement_id"
                                            value="{{ old('ga4_measurement_id', $settings['tracking']['ga4_measurement_id'] ?? '') }}"
                                            placeholder="G-XXXXXXX">
                                        @error('ga4_measurement_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">GA4 API Secret <span
                                                class="text-muted">(server-side purchase & refund)</span></label>
                                        <input type="password" class="bp-form-control" name="ga4_api_secret"
                                            value="{{ $settings['tracking']['ga4_api_secret'] ?? '' }}"
                                            placeholder="••••••••">
                                    </div>
                                </div>
                            </div>
                            <div class="bp-card-footer text-end">
                                <button type="submit" class="bp-btn bp-btn-success"><i
                                        class="fa-solid fa-save me-1"></i> Save Tracking Settings</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ================================================================
                 WEBHOOKS
                 ================================================================ -->
                <div class="tab-pane fade" id="webhookSettings">
                    <div class="bp-settings-section-header mb-4">
                        <div class="bp-settings-section-icon icon-secondary"><i class="fa-solid fa-plug"></i></div>
                        <div>
                            <h4 class="bp-settings-section-title">Webhooks</h4>
                            <p class="bp-settings-section-desc">Send real-time HTTP notifications to external systems when
                                events occur in BizPOS. Webhooks are signed with HMAC-SHA256 if a secret is provided.</p>
                        </div>
                    </div>

                    <!-- Add New Webhook -->
                    @bpCan('settings.edit')
                    <div class="bp-card mb-4">
                        <div class="bp-card-header">
                            <h5 class="bp-card-title"><i class="fa-solid fa-plus me-2"></i><span
                                    id="webhookFormTitle">{{ old('webhook_id') ? 'Edit Webhook' : 'Add Webhook' }}</span>
                            </h5>
                        </div>
                        <form action="{{ route('settings.webhooks.store') }}" method="POST" id="webhookForm">
                            @csrf
                            <input type="hidden" name="webhook_id" id="webhookId"
                                value="{{ old('webhook_id') }}">
                            <div class="bp-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="bp-form-label">Name *</label>
                                        <input type="text"
                                            class="bp-form-control @error('name') is-invalid @enderror" name="name"
                                            value="{{ old('name') }}" required
                                            placeholder="e.g. Order Sync, Inventory Alert">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-5">
                                        <label class="bp-form-label">Endpoint URL *</label>
                                        <input type="url" class="bp-form-control @error('url') is-invalid @enderror"
                                            name="url" value="{{ old('url') }}" required
                                            placeholder="https://yourapp.com/api/webhook">
                                        <small class="text-muted fs-11">Built-in test endpoint:
                                            <code>{{ url('/api/webhook/receive') }}</code></small>
                                        @error('url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="bp-form-label">Secret (optional)</label>
                                        <input type="text" class="bp-form-control" name="secret"
                                            value="{{ old('secret') }}" placeholder="HMAC signing key">
                                    </div>
                                    <div class="col-12">
                                        <label class="bp-form-label">Events *</label>
                                        <div class="row g-2">
                                            @foreach ($webhookEvents as $eventKey => $eventLabel)
                                                <div class="col-md-4 col-lg-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="events[]" value="{{ $eventKey }}"
                                                            id="evt_{{ Str::slug($eventKey, '_') }}"
                                                            {{ in_array($eventKey, old('events', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label fs-12"
                                                            for="evt_{{ Str::slug($eventKey, '_') }}">{{ $eventLabel }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('events')
                                            <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="bp-card-footer text-end">
                                <button type="button" id="webhookCancelEdit"
                                    class="bp-btn bp-btn-danger me-2 {{ old('webhook_id') ? '' : 'd-none' }}"><i
                                        class="fa-solid fa-xmark me-1"></i> Cancel Edit</button>
                                <button type="submit" id="webhookSubmitBtn" class="bp-btn bp-btn-success"><i
                                        class="fa-solid fa-plus me-1"></i> <span
                                        id="webhookSubmitLabel">{{ old('webhook_id') ? 'Update Webhook' : 'Create Webhook' }}</span></button>
                            </div>
                        </form>
                    </div>
                    @endbpCan

                    <!-- Existing Webhooks -->
                    <div class="bp-card">
                        <div class="bp-card-header">
                            <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Configured Webhooks</h5>
                            <span class="bp-badge bp-badge-info">{{ $webhooks->count() }}</span>
                        </div>
                        <div class="bp-card-body p-0">
                            @forelse($webhooks as $webhook)
                                <div class="d-flex align-items-start gap-3 p-3 border-bottom">
                                    <div class="bp-notif-icon icon-{{ $webhook->is_active ? 'success' : 'danger' }}">
                                        <i class="fa-solid fa-plug"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="fw-700 fs-14">{{ $webhook->name }}</span>
                                            <span
                                                class="bp-badge {{ $webhook->is_active ? 'bp-badge-success' : 'bp-badge-danger' }} fs-10">{{ $webhook->is_active ? 'Active' : 'Disabled' }}</span>
                                            @if ($webhook->failure_count > 0)
                                                <span
                                                    class="bp-badge bp-badge-warning fs-10">{{ $webhook->failure_count }}
                                                    failures</span>
                                            @endif
                                        </div>
                                        <div class="fs-12 text-muted mb-1"><code>{{ $webhook->url }}</code></div>
                                        <div class="d-flex flex-wrap gap-1 mb-2">
                                            @foreach ($webhook->events as $evt)
                                                <span class="bp-badge bp-badge-primary fs-10">{{ $evt }}</span>
                                            @endforeach
                                        </div>
                                        <div class="fs-11 text-muted">
                                            @if ($webhook->last_triggered_at)
                                                Last triggered: {{ $webhook->last_triggered_at->diffForHumans() }}
                                            @else
                                                Never triggered
                                            @endif
                                            @if ($webhook->creator)
                                                &middot; Created by {{ $webhook->creator->name }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        @bpCan('settings.edit')
                                        <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button type="button" class="dropdown-item btn-edit-webhook"
                                                    data-id="{{ $webhook->id }}" data-name="{{ $webhook->name }}"
                                                    data-url="{{ $webhook->url }}"
                                                    data-events="{{ implode(',', $webhook->events) }}"><i class="fa-solid fa-pen me-2"></i> Edit</button>
                                            </li>
                                            <li>
                                                <form action="{{ route('settings.webhooks.test', $webhook) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item"><i class="fa-solid fa-paper-plane me-2"></i> Send test ping</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="{{ route('settings.webhooks.toggle', $webhook) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item"><i class="fa-solid {{ $webhook->is_active ? 'fa-pause' : 'fa-play' }} me-2"></i> {{ $webhook->is_active ? 'Disable' : 'Enable' }}</button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('settings.webhooks.destroy', $webhook) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                        data-name="{{ $webhook->name }}"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                                                </form>
                                            </li>
                                        </ul>
                                        @endbpCan
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-plug fa-2x mb-2 d-block" style="opacity:0.3"></i>
                                    <div class="fs-13">No webhooks configured yet.</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- ================================================================
                 SYSTEM MAINTENANCE & CACHE
                 ================================================================ -->
                <div class="tab-pane fade" id="systemSettings">
                    <div class="bp-settings-section-header mb-4">
                        <div class="bp-settings-nav-icon icon-danger bp-settings-section-icon-lg"><i
                                class="fa-solid fa-screwdriver-wrench"></i></div>
                        <div>
                            <h4 class="bp-settings-section-title">System Maintenance</h4>
                            <p class="bp-settings-section-desc">Clear application caches and control maintenance mode. Use
                                these tools after configuration changes or before deployments.</p>
                        </div>
                    </div>

                    <!-- Clear Cache Card -->
                    <div class="bp-card mb-4">
                        <div class="bp-card-header">
                            <h5 class="bp-card-title"><i class="fa-solid fa-broom me-2 text-danger"></i>Clear Cache</h5>
                        </div>
                        <div class="bp-card-body">
                            <p class="fs-13 text-muted mb-3">Clears the application, configuration, route, and view
                                caches. Run this if settings changes don't appear to take effect or after updating the
                                system.</p>
                            @bpCan('settings.edit')
                            <form action="{{ route('settings.clear-cache') }}" method="POST">
                                @csrf
                                <button type="submit" class="bp-btn bp-btn-danger"><i
                                        class="fa-solid fa-broom me-1"></i> Clear All Caches</button>
                            </form>
                            @endbpCan
                        </div>
                    </div>

                    <!-- Maintenance Mode Card -->
                    <div class="bp-card mb-4">
                        <div class="bp-card-header">
                            <h5 class="bp-card-title"><i
                                    class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>Maintenance Mode</h5>
                        </div>
                        <div class="bp-card-body">
                            @if (app()->isDownForMaintenance())
                                <p class="fs-13 mb-3"><span class="bp-badge bp-badge-warning">Enabled</span> The site is
                                    currently in maintenance mode and is not publicly accessible.</p>
                                @bpCan('settings.edit')
                                <form action="{{ route('settings.maintenance') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="bp-btn bp-btn-success"><i
                                            class="fa-solid fa-play me-1"></i> Bring Site Live</button>
                                </form>
                                @endbpCan
                            @else
                                <p class="fs-13 mb-3"><span class="bp-badge bp-badge-success">Live</span> Enabling
                                    maintenance mode takes the site offline for visitors. A secret bypass link will be
                                    generated for your access.</p>
                                @bpCan('settings.edit')
                                <form action="{{ route('settings.maintenance') }}" method="POST"
                                    class="maintenance-toggle-form">
                                    @csrf
                                    <button type="submit" class="bp-btn bp-btn-warning"><i
                                            class="fa-solid fa-power-off me-1"></i> Enable Maintenance Mode</button>
                                </form>
                                @endbpCan
                            @endif
                        </div>
                    </div>
                </div>

            </div><!-- end tab-content -->
        </div><!-- end col -->
    </div><!-- end row -->

    {{-- ─────────── Tax Rate Add/Edit Modal ─────────── --}}
    <div class="modal fade" id="taxRateModal" tabindex="-1" aria-labelledby="taxRateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="taxRateForm" action="{{ route('settings.tax-rates.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="taxRateMethod" value="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="taxRateModalLabel"><i class="fa-solid fa-percent me-2"></i>Add Tax
                            Rate</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="bp-form-label">Tax Name *</label>
                                <input type="text" class="bp-form-control" name="name" id="taxRateName"
                                    required maxlength="100" placeholder="e.g. Standard VAT">
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Type *</label>
                                <select class="bp-form-select w-100" name="type" id="taxRateType" required>
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="exempt">Exempt</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="bp-form-label">Rate *</label>
                                <div class="input-group">
                                    <input type="number" class="bp-form-control" name="rate" id="taxRateRate"
                                        required step="0.0001" min="0" max="100" value="0">
                                    <span class="input-group-text" id="taxRateUnit">%</span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="bp-form-label">Apply To</label>
                                <input type="text" class="bp-form-control" name="apply_to" id="taxRateApplyTo"
                                    maxlength="100" placeholder="e.g. All Products, Services">
                            </div>
                            <div class="col-md-12">
                                <label class="bp-form-label">Description</label>
                                <input type="text" class="bp-form-control" name="description"
                                    id="taxRateDescription" maxlength="255" placeholder="Optional note">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_default" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_default"
                                        id="taxRateIsDefault" value="1">
                                    <label class="form-check-label" for="taxRateIsDefault">Set as Default</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active"
                                        id="taxRateIsActive" value="1" checked>
                                    <label class="form-check-label" for="taxRateIsActive">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                                class="fa-solid fa-xmark me-1"></i>Cancel</button>
                        <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i>
                            Save Tax Rate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─────────── Live Invoice Preview Modal (uses unsaved form values) ─────────── --}}
    <div class="modal fade" id="invoicePreviewModal" tabindex="-1" aria-labelledby="invoicePreviewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="invoicePreviewModalLabel"><i
                            class="fa-solid fa-receipt me-2"></i>Invoice Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="invoicePreviewBody"></div>
                    <p class="fs-11 text-muted mt-3 mb-0"><i class="fa-solid fa-circle-info me-1"></i> Representative
                        preview built from the values currently in the form — not yet saved. Sample line items are shown for
                        layout.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bp-btn bp-btn-danger" data-bs-dismiss="modal"><i
                            class="fa-solid fa-xmark me-1"></i>Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        'use strict';
        // Live preview for logo & favicon uploads (any file input with data-preview-target)
        $(document).on('change', 'input[type="file"][data-preview-target]', function() {
            var $target = $($(this).data('preview-target'));
            if (!$target.length) return;
            var file = this.files && this.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                var alt = $target.find('img').attr('alt') || 'Preview';
                $target.html('<img src="' + e.target.result + '" alt="' + alt + '">');
            };
            reader.readAsDataURL(file);
        });

        // ── Tax Rate add/edit modal ──
        (function() {
            var $modal = $('#taxRateModal');
            if (!$modal.length) return;

            var $form = $('#taxRateForm');
            var $title = $('#taxRateModalLabel');
            var $method = $('#taxRateMethod');
            var storeUrl = '{{ route('settings.tax-rates.store') }}';
            var taxCurrency = @json(currency_symbol());

            // Keep the rate unit in step with the selected type:
            // percentage → %, fixed → currency, exempt → no rate (0, read-only).
            function syncTaxRateUnit() {
                var type = $('#taxRateType').val();
                var $rate = $('#taxRateRate');
                if (type === 'fixed') {
                    $('#taxRateUnit').text(taxCurrency);
                    $rate.prop('readonly', false);
                } else if (type === 'exempt') {
                    $('#taxRateUnit').text('—');
                    $rate.val(0).prop('readonly', true);
                } else {
                    $('#taxRateUnit').text('%');
                    $rate.prop('readonly', false);
                }
            }
            $(document).on('change', '#taxRateType', syncTaxRateUnit);

            function resetForm() {
                $form.attr('action', storeUrl);
                $method.val('POST');
                $title.html('<i class="fa-solid fa-percent me-2"></i>Add Tax Rate');
                $('#taxRateName').val('');
                $('#taxRateRate').val(0);
                $('#taxRateType').val('percentage');
                $('#taxRateApplyTo').val('');
                $('#taxRateDescription').val('');
                $('#taxRateIsDefault').prop('checked', false);
                $('#taxRateIsActive').prop('checked', true);
                syncTaxRateUnit();
            }

            $(document).on('click', '#btnAddTaxRate', function() {
                resetForm();
            });

            $(document).on('click', '.btn-edit-tax-rate', function() {
                var $btn = $(this);
                $form.attr('action', $btn.data('update-url'));
                $method.val('PUT');
                $title.html('<i class="fa-solid fa-pen me-2"></i>Edit Tax Rate');
                $('#taxRateName').val($btn.data('name'));
                $('#taxRateRate').val($btn.data('rate'));
                $('#taxRateType').val($btn.data('type'));
                $('#taxRateApplyTo').val($btn.data('apply-to'));
                $('#taxRateDescription').val($btn.data('description'));
                $('#taxRateIsDefault').prop('checked', String($btn.data('is-default')) === '1');
                $('#taxRateIsActive').prop('checked', String($btn.data('is-active')) === '1');
                syncTaxRateUnit();
            });
        })();

        // Settings nav tab switching
        document.querySelectorAll('#settingsNav .bp-settings-nav-item').forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('#settingsNav .bp-settings-nav-item').forEach(function(i) {
                    i.classList.remove('active');
                });
                this.classList.add('active');
                var target = this.getAttribute('href');
                document.querySelectorAll('.tab-pane').forEach(function(p) {
                    p.classList.remove('show', 'active');
                });
                var pane = document.querySelector(target);
                if (pane) {
                    pane.classList.add('show', 'active');
                }
            });
        });

        // ── Appearance color pickers: keep the swatch and hex input in sync, reset to default ──
        $('.bp-color-picker').on('input', function() {
            $(this).siblings('.bp-color-hex').val($(this).val());
        });
        $('.bp-color-hex').on('input change', function() {
            var hex = $(this).val();
            if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
                $(this).siblings('.bp-color-picker').val(hex);
            }
        });
        $('.bp-color-reset').on('click', function() {
            var $picker = $(this).siblings('.bp-color-picker');
            var defaultVal = $picker.data('default');
            $picker.val(defaultVal);
            $(this).siblings('.bp-color-hex').val(defaultVal);
        });

        // Courier card toggle expand on switch click. The badge is updated for every
        // courier (whether or not the body exists), so cards without a body — Redx,
        // Paperfly — still flip Connected ↔ Not Connected when toggled.
        document.querySelectorAll('.bp-courier-card .form-check-input').forEach(function(sw) {
            sw.addEventListener('change', function() {
                var card = this.closest('.bp-courier-card');
                var body = card.querySelector('.bp-courier-card-body');
                var badge = card.querySelector('.bp-badge');
                if (badge) {
                    if (this.checked) {
                        badge.className = 'bp-badge bp-badge-success';
                        badge.textContent = 'Connected';
                    } else {
                        badge.className = 'bp-badge bp-badge-dark';
                        badge.textContent = 'Not Connected';
                    }
                }
                if (body) {
                    if (this.checked) body.classList.remove('d-none');
                    else body.classList.add('d-none');
                }
            });
        });

        // ── Reorder courier cards so the "Default Courier Partner" appears first ──
        (function() {
            var $defaultSel = $('select[name="default_courier"]');
            var $container = $('#courierCardsContainer');
            if (!$defaultSel.length || !$container.length) return;

            function reorder() {
                var name = ($defaultSel.val() || '').trim();
                if (!name) return;
                var $card = $container.find('.bp-courier-card[data-courier-name="' + name.replace(/"/g, '\\"') + '"]');
                if ($card.length) $card.prependTo($container);
            }

            reorder(); // on initial page load
            $defaultSel.on('change', reorder); // and whenever user picks a new default
        })();

        // ── "Test Connection" buttons (courier + SMS gateway, shared handler) ──
        $(document).on('click', '.bp-conn-test-btn', function() {
            var $btn = $(this);
            var url = $btn.data('url');
            var fields = $btn.data('fields') || {};
            var $result = $($btn.data('result'));
            var loadingMsg = $btn.data('loading') || '{{ __('Contacting API...') }}';
            var balanceTarget = $btn.data('balance-target');

            // Read the live form values into the request payload. Each mapped
            // selector is resolved by name first, then falls back to an id
            // (test-only inputs carry an id and no name so they aren't saved).
            var payload = {};
            Object.keys(fields).forEach(function(k) {
                var sel = fields[k];
                var $field = $('[name="' + sel + '"]');
                if (!$field.length) {
                    $field = $('#' + sel);
                }
                payload[k] = $field.val() || '';
            });

            var originalHtml = $btn.html();
            $btn.prop('disabled', true).html(
                '<i class="fa-solid fa-spinner fa-spin me-1"></i> {{ __('Testing...') }}');
            $result.removeClass('d-none alert alert-success alert-danger')
                .addClass('alert alert-info')
                .html('<i class="fa-solid fa-spinner fa-spin me-1"></i> ' + loadingMsg);

            $.ajax({
                    url: url,
                    method: 'POST',
                    data: payload,
                    dataType: 'json'
                })
                .done(function(res) {
                    $result.removeClass('alert-info alert-danger').addClass('alert alert-success')
                        .html('<i class="fa-solid fa-circle-check me-1"></i> ' + (res.message ||
                            '{{ __('Connected.') }}'));

                    // Reflect a live balance into a target field when provided.
                    if (balanceTarget && res.balance != null) {
                        $(balanceTarget).removeClass('text-danger text-muted')
                            .addClass('text-success')
                            .html('<i class="fa-solid fa-circle-check me-2"></i> ' +
                                '{{ __('Balance') }}: ' + res.balance);
                    }
                })
                .fail(function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ||
                        '{{ __('Connection test failed.') }}';
                    $result.removeClass('alert-info alert-success').addClass('alert alert-danger')
                        .html('<i class="fa-solid fa-circle-xmark me-1"></i> ' + msg);
                })
                .always(function() {
                    $btn.prop('disabled', false).html(originalHtml);
                });
        });

        /* ───────────── Landing Page Settings ───────────── */
        $(function() {
            var $modeCards = $('#landingPageSettingsForm .bp-template-card');
            var $select = $('#activeLandingPageSelect');
            var $previewBtn = $('#previewLandingPageBtn');

            $modeCards.on('click', function() {
                var mode = $(this).data('mode');
                $modeCards.removeClass('active');
                $(this).addClass('active');
                $(this).find('input[type="radio"]').prop('checked', true);
            });

            function syncPreview() {
                var url = $select.find('option:selected').data('preview');
                if (url) {
                    $previewBtn.attr('href', url).removeClass('disabled').css('pointer-events', '');
                } else {
                    $previewBtn.attr('href', '#').addClass('disabled').css('pointer-events', 'none');
                }
            }
            if ($select.length) {
                syncPreview();
                $select.on('change', syncPreview);
            }

            $('#landingPageSettingsForm').on('submit', function(e) {
                var mode = $(this).find('input[name="mode"]:checked').val();
                var activeId = $select.val();
                if (mode === 'landing_page' && !activeId) {
                    e.preventDefault();
                    alert('Please select an active landing page before switching to Landing Page Mode.');
                    $select.focus();
                }
            });
        });

        // ── Restore the active section after a save/redirect (keeps the user on the
        //    tab they just saved instead of bouncing back to Business Profile) ──
        @if (session('active_section'))
            (function() {
                var anchor = @json(session('active_section'));
                var nav = document.querySelector('#settingsNav .bp-settings-nav-item[href="#' + anchor + '"]');
                if (nav) {
                    nav.click();
                    var pane = document.getElementById(anchor);
                    if (pane && pane.scrollIntoView) {
                        pane.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            })();
        @endif

        // ── AJAX submit for section settings forms (toast + stay on section, no reload) ──
        //    Targets the group-update forms (those carrying _method=PUT) inside the tab
        //    content. The landing-page form keeps its bespoke validate/redirect flow.
        document.querySelectorAll('.tab-content form').forEach(function(form) {
            var method = form.querySelector('input[name="_method"]');
            if (!method || method.value.toUpperCase() !== 'PUT') {
                return;
            }
            if (form.id === 'landingPageSettingsForm') {
                return;
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = e.submitter || form.querySelector('button[type="submit"]');
                var original = btn ? btn.innerHTML : '';
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...';
                }

                form.querySelectorAll('.is-invalid').forEach(function(el) {
                    el.classList.remove('is-invalid');
                });

                // Post to a SAME-ORIGIN relative path. form.action is an absolute route()
                // URL built from APP_URL; if the page is opened on a different host
                // (e.g. localhost vs 127.0.0.1) that absolute URL is cross-origin and the
                // Content-Security-Policy (default-src 'self') blocks the fetch — surfacing
                // as a "Network error". Using the relative path keeps it same-origin.
                var actionUrl = new URL(form.action, window.location.href);
                var sameOriginAction = actionUrl.pathname + actionUrl.search;

                fetch(sameOriginAction, {
                        method: 'POST', // spoofed to PUT via the _method field in FormData
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: new FormData(form)
                    })
                    .then(function(res) {
                        return res.json().catch(function() {
                            return {};
                        }).then(function(data) {
                            return {
                                ok: res.ok,
                                status: res.status,
                                data: data
                            };
                        });
                    })
                    .then(function(r) {
                        if (r.ok) {
                            if (window.showToast) {
                                window.showToast((r.data && r.data.message) || 'Settings saved.',
                                    'success');
                            }
                        } else if (r.status === 422) {
                            var errors = (r.data && r.data.errors) || {};
                            var first = null;
                            Object.keys(errors).forEach(function(k) {
                                var field = form.querySelector('[name="' + k + '"]');
                                if (field) {
                                    field.classList.add('is-invalid');
                                    if (!first) {
                                        first = field;
                                    }
                                }
                            });
                            if (window.showToast) {
                                window.showToast((r.data && r.data.message) ||
                                    'Please fix the highlighted fields.', 'danger');
                            }
                            if (first && first.focus) {
                                first.focus();
                            }
                        } else if (window.showToast) {
                            window.showToast('Could not save settings (' + r.status + ').', 'danger');
                        }
                    })
                    .catch(function() {
                        if (window.showToast) {
                            window.showToast('Network error — settings were not saved.', 'danger');
                        }
                    })
                    .finally(function() {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = original;
                        }
                    });
            });
        });

        // ── Live invoice preview modal (builds a representative invoice from form values) ──
        (function() {
            var modal = document.getElementById('invoicePreviewModal');
            if (!modal) {
                return;
            }

            function esc(s) {
                var d = document.createElement('div');
                d.textContent = (s == null ? '' : String(s));
                return d.innerHTML;
            }

            function val(sel) {
                var el = document.querySelector(sel);
                return el ? el.value : '';
            }

            function checked(sel) {
                var el = document.querySelector(sel);
                return !!(el && el.checked);
            }

            modal.addEventListener('show.bs.modal', function() {
                var body = document.getElementById('invoicePreviewBody');
                if (!body) {
                    return;
                }

                var previewEl = document.getElementById('invoicePreview');
                var invNo = previewEl ? previewEl.textContent : '';
                var company = val('input[name="company_name"]') || 'Your Company';
                var footer = val('#invoiceSettings textarea[name="footer_text"]');
                var terms = val('#invoiceSettings textarea[name="terms"]');
                var width = val('#invoiceSettings select[name="receipt_width"]');
                var showLogo = checked('#invoiceSettings input[name="show_logo"]');
                var showVat = checked('#invoiceSettings input[name="show_vat"]');

                var vatLine = showVat ?
                    '<div class="d-flex justify-content-between fs-12"><span>VAT (15%)</span><span>{{ currency_symbol() }} 112.50</span></div>' :
                    '';

                var html = '' +
                    '<div class="border rounded p-3 mx-auto bg-white text-dark bp-invoice-preview-doc">' +
                    (showLogo ?
                        '<div class="text-center mb-2"><span class="bp-badge bp-badge-dark">LOGO</span></div>' :
                        '') +
                    '<div class="text-center fw-800 fs-14">' + esc(company) + '</div>' +
                    '<div class="text-center fs-12 text-muted mb-2">Tax Invoice</div>' +
                    '<div class="d-flex justify-content-between fs-12 mb-2"><span>Invoice #: <strong>' + esc(
                        invNo) + '</strong></span><span>' + esc(new Date().toLocaleDateString()) +
                    '</span></div>' +
                    '<table class="table table-sm fs-12 mb-2"><thead><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Price</th></tr></thead><tbody>' +
                    '<tr><td>Sample Product A</td><td class="text-end">2</td><td class="text-end">{{ currency_symbol() }} 500.00</td></tr>' +
                    '<tr><td>Sample Product B</td><td class="text-end">1</td><td class="text-end">{{ currency_symbol() }} 250.00</td></tr>' +
                    '</tbody></table>' +
                    '<div class="d-flex justify-content-between fs-12"><span>Subtotal</span><span>{{ currency_symbol() }} 1,250.00</span></div>' +
                    vatLine +
                    '<div class="d-flex justify-content-between fw-800 border-top pt-1 mt-1"><span>Total</span><span>{{ currency_symbol() }} ' +
                    (showVat ? '1,362.50' : '1,250.00') + '</span></div>' +
                    (footer ? '<div class="text-center fs-11 text-muted mt-3">' + esc(footer) + '</div>' : '') +
                    (terms ? '<div class="fs-10 text-muted mt-2"><strong>Terms:</strong> ' + esc(terms) +
                        '</div>' : '') +
                    '</div>';

                body.innerHTML = html;

                // Runtime-only width to mimic the chosen thermal receipt size (allowed dynamic CSS).
                var doc = body.firstChild;
                if (doc) {
                    doc.style.maxWidth = (width && width.indexOf('58') === 0) ? '300px' : '380px';
                }
            });
        })();

        // ── Tracking: disable dependent fields when their toggle is OFF ──
        (function() {
            function sync(toggleName, fieldNames) {
                var toggle = document.querySelector('input[name="' + toggleName + '"]');
                if (!toggle) {
                    return;
                }
                var fields = fieldNames.map(function(n) {
                    return document.querySelector('input[name="' + n + '"]');
                });

                function apply() {
                    fields.forEach(function(f) {
                        if (!f) {
                            return;
                        }
                        f.disabled = !toggle.checked;
                        f.style.opacity = toggle.checked ? '' : '0.5';
                    });
                }
                apply();
                toggle.addEventListener('change', apply);
            }
            sync('gtm_enabled', ['gtm_container_id']);
            sync('fbpixel_enabled', ['fbpixel_id', 'fbpixel_access_token']);
            sync('ga4_enabled', ['ga4_measurement_id', 'ga4_api_secret']);
        })();

        // ── Webhooks: populate the form for editing / reset back to create ──
        (function() {
            var form = document.getElementById('webhookForm');
            if (!form) {
                return;
            }
            var secret = form.querySelector('input[name="secret"]');

            $(document).on('click', '.btn-edit-webhook', function() {
                var $b = $(this);
                document.getElementById('webhookId').value = $b.data('id');
                form.querySelector('input[name="name"]').value = $b.data('name') || '';
                form.querySelector('input[name="url"]').value = $b.data('url') || '';
                if (secret) {
                    secret.value = '';
                    secret.setAttribute('placeholder', 'Leave blank to keep current secret');
                }

                var events = String($b.data('events') || '').split(',').map(function(s) {
                    return s.trim();
                });
                form.querySelectorAll('input[name="events[]"]').forEach(function(cb) {
                    cb.checked = events.indexOf(cb.value) !== -1;
                });

                document.getElementById('webhookFormTitle').textContent = 'Edit Webhook';
                document.getElementById('webhookSubmitLabel').textContent = 'Update Webhook';
                document.getElementById('webhookCancelEdit').classList.remove('d-none');
                form.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });

            $(document).on('click', '#webhookCancelEdit', function() {
                form.reset();
                document.getElementById('webhookId').value = '';
                if (secret) {
                    secret.setAttribute('placeholder', 'HMAC signing key');
                }
                document.getElementById('webhookFormTitle').textContent = 'Add Webhook';
                document.getElementById('webhookSubmitLabel').textContent = 'Create Webhook';
                this.classList.add('d-none');
            });
        })();
    </script>
@endpush

@push('scripts')
    <script>
        'use strict';
        (function() {
            // Open the settings tab referenced by the URL hash (e.g. /admin/settings#taxSettings),
            // so deep links from global search land directly on the right section.
            function activateFromHash() {
                var hash = window.location.hash;
                if (!hash || hash.length < 2) {
                    return;
                }
                // Only plain #idName fragments — a hash with CSS metacharacters would
                // make querySelector throw a SyntaxError.
                if (!/^#[\w-]+$/.test(hash)) {
                    return;
                }

                var trigger = document.querySelector('.bp-settings-nav-item[href="' + hash + '"]');
                if (trigger && window.bootstrap && bootstrap.Tab) {
                    bootstrap.Tab.getOrCreateInstance(trigger).show();
                    var pane = document.querySelector(hash);
                    if (pane) {
                        pane.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            }
            document.addEventListener('DOMContentLoaded', activateFromHash);
        })();
    </script>
@endpush
