@php
    /** @var \Modules\Marketing\Models\SmsCampaign|null $campaign */
    $campaign = $campaign ?? null;
    $isEdit = (bool) $campaign;
    $customNumbers =
        $campaign && $campaign->audience === 'custom' && is_array($campaign->recipient_numbers)
            ? implode("\n", $campaign->recipient_numbers)
            : '';
@endphp

<div class="row g-4">
    <div class="col-xl-8">
        <!-- Campaign Info -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-comment-sms me-2"></i>Campaign Information</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="bp-form-label">Campaign Name *</label>
                        <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name"
                            value="{{ old('name', $campaign->name ?? '') }}" required
                            placeholder="e.g., Eid Sale Alert">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="bp-form-label">SMS Gateway *</label>
                        <select class="bp-form-select w-100 @error('gateway') is-invalid @enderror" name="gateway"
                            required>
                            <option value="bulksmsbd"
                                {{ old('gateway', $campaign->gateway ?? 'bulksmsbd') == 'bulksmsbd' ? 'selected' : '' }}>
                                BulkSMSBD</option>
                        </select>
                        @error('gateway')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Recipients -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-users me-2"></i>Recipients</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    @php $aud = old('audience', $campaign->audience ?? 'all'); @endphp
                    <div class="col-12">
                        <label class="bp-form-label">Audience *</label>
                        <div class="d-flex flex-wrap gap-3">
                            <label class="bp-radio-label">
                                <input type="radio" class="bp-form-radio" name="audience" value="all"
                                    {{ $aud == 'all' ? 'checked' : '' }}> All Customers
                            </label>
                            <label class="bp-radio-label">
                                <input type="radio" class="bp-form-radio" name="audience" value="group"
                                    {{ $aud == 'group' ? 'checked' : '' }}> Select Group
                            </label>
                            <label class="bp-radio-label">
                                <input type="radio" class="bp-form-radio" name="audience" value="custom"
                                    {{ $aud == 'custom' ? 'checked' : '' }}> Custom Numbers
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 bp-audience-group" id="group-select">
                        <label class="bp-form-label">Customer Group</label>
                        <select class="bp-form-select w-100" name="customer_group">
                            <option value="">Select Group</option>
                            <option value="vip">VIP Customers</option>
                            <option value="regular">Regular Customers</option>
                            <option value="new">New Customers (Last 30 Days)</option>
                            <option value="inactive">Inactive Customers (90+ Days)</option>
                            <option value="loyalty_gold">Loyalty - Gold Tier</option>
                            <option value="loyalty_platinum">Loyalty - Platinum Tier</option>
                        </select>
                    </div>
                    <div class="col-12 bp-audience-custom" id="custom-numbers">
                        <label class="bp-form-label">Phone Numbers</label>
                        <textarea class="bp-form-control" name="recipient_numbers" rows="3"
                            placeholder="Enter phone numbers separated by commas or new lines&#10;e.g., 01712345678, 01812345678">{{ old('recipient_numbers', $customNumbers) }}</textarea>
                        <small class="text-muted fs-11">Enter numbers in Bangladesh format (01XXXXXXXXX)</small>
                    </div>
                    <div class="col-12">
                        <div class="bp-recipient-count fs-13">
                            <i class="fa-solid fa-users me-1"></i> Estimated Recipients: <strong
                                id="recipient-count">{{ $campaign->total_recipients ?? '--' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-message me-2"></i>Message</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between mb-2">
                            <label class="bp-form-label mb-0">Message Content *</label>
                            <span class="fs-12 text-muted"><span id="char-count">0</span>/160 characters</span>
                        </div>
                        <textarea class="bp-form-control @error('message') is-invalid @enderror" name="message" id="sms-message" rows="4"
                            maxlength="160" required placeholder="Type your SMS message here...">{{ old('message', $campaign->message ?? '') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="fs-11 text-muted mt-1">
                            <span id="sms-parts">1</span> SMS part(s) &middot; <span
                                id="sms-cost">{{ currency_symbol() }} 0.50</span> per recipient
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="bp-form-label">Quick Templates</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="bp-btn bp-btn-sm bp-btn-warning bp-sms-template"
                                data-template="Dear Customer, enjoy up to 50% OFF on all products! Visit our store today. Shop now at BizPOS. T&C apply.">
                                <i class="fa-solid fa-tag me-1"></i> Sale Alert
                            </button>
                            <button type="button" class="bp-btn bp-btn-sm bp-btn-primary bp-sms-template"
                                data-template="Hi! New arrivals just dropped at BizPOS. Check out the latest collection in store or online. Limited stock!">
                                <i class="fa-solid fa-box-open me-1"></i> New Arrival
                            </button>
                            <button type="button" class="bp-btn bp-btn-sm bp-btn-info bp-sms-template"
                                data-template="You have earned {points} loyalty points! Redeem them on your next purchase at BizPOS. Thank you for shopping with us.">
                                <i class="fa-solid fa-star me-1"></i> Loyalty Points
                            </button>
                            <button type="button" class="bp-btn bp-btn-sm bp-btn-success bp-sms-template"
                                data-template="Dear Customer, your order #{order_id} has been shipped. Track: {tracking_url}. Thank you for shopping at BizPOS.">
                                <i class="fa-solid fa-truck-fast me-1"></i> Order Update
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule -->
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-clock me-2"></i>Schedule</h5>
            </div>
            <div class="bp-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="bp-form-label">Send Time</label>
                        <div class="d-flex flex-wrap gap-3">
                            <label class="bp-radio-label">
                                <input type="radio" class="bp-form-radio" name="schedule" value="now"
                                    {{ old('schedule', 'now') == 'now' ? 'checked' : '' }}> Send Now
                            </label>
                            <label class="bp-radio-label">
                                <input type="radio" class="bp-form-radio" name="schedule" value="later"
                                    {{ old('schedule') == 'later' ? 'checked' : '' }}> Schedule Later
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 bp-schedule-fields" id="schedule-date">
                        <label class="bp-form-label">Date</label>
                        <input type="date" class="bp-form-control" name="scheduled_date"
                            value="{{ old('scheduled_date') }}">
                    </div>
                    <div class="col-md-4 bp-schedule-fields" id="schedule-time">
                        <label class="bp-form-label">Time</label>
                        <input type="time" class="bp-form-control" name="scheduled_time"
                            value="{{ old('scheduled_time') }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="bp-card">
            <div class="bp-card-footer d-flex justify-content-between">
                <button type="button" class="bp-btn bp-btn-outline" id="btn-send-test">
                    <i class="fa-solid fa-vial me-1"></i> Send Test SMS
                </button>
                <div>
                    <a href="{{ route('marketing.sms-campaigns') }}" class="bp-btn bp-btn-danger me-2"><i
                            class="fa-solid fa-xmark me-1"></i>Cancel</a>
                    <button type="submit" name="action" value="draft" class="bp-btn bp-btn-warning me-2">
                        <i class="fa-solid fa-save me-1"></i> {{ $isEdit ? 'Update Draft' : 'Save Draft' }}
                    </button>
                    <button type="submit" name="action" value="send" class="bp-btn bp-btn-success">
                        <i class="fa-solid fa-paper-plane me-1"></i> {{ $isEdit ? 'Update & Send' : 'Send Campaign' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Panel -->
    <div class="col-xl-4">
        <div class="bp-card bp-card-sticky">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-mobile-screen me-2"></i>SMS Preview</h5>
            </div>
            <div class="bp-card-body">
                <div class="bp-sms-preview">
                    <div class="bp-sms-preview-header">
                        <div class="fw-700 fs-13">BizPOS</div>
                        <div class="fs-11 text-muted">SMS Message</div>
                    </div>
                    <div class="bp-sms-preview-body">
                        <div class="bp-sms-bubble" id="sms-preview-text">
                            Your message will appear here...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Campaign Summary -->
        <div class="bp-card mt-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-receipt me-2"></i>Cost Estimate</h5>
            </div>
            <div class="bp-card-body">
                <div class="d-flex justify-content-between fs-13 mb-2">
                    <span class="text-muted">Recipients</span>
                    <span class="fw-600" id="cost-recipients">{{ $campaign->total_recipients ?? 0 }}</span>
                </div>
                <div class="d-flex justify-content-between fs-13 mb-2">
                    <span class="text-muted">SMS Parts</span>
                    <span class="fw-600" id="cost-parts">1</span>
                </div>
                <div class="d-flex justify-content-between fs-13 mb-2">
                    <span class="text-muted">Cost per SMS</span>
                    <span class="fw-600">{{ currency_symbol() }} 0.50</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fw-800 fs-14">
                    <span>Estimated Total</span>
                    <span class="bp-text-primary" id="cost-total">{{ currency_symbol() }} 0</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        'use strict';

        $(document).ready(function() {
            // Character counter and preview
            $('#sms-message').on('input', function() {
                var len = $(this).val().length;
                var parts = Math.ceil(len / 160) || 1;
                $('#char-count').text(len);
                $('#sms-parts').text(parts);
                $('#cost-parts').text(parts);
                $('#sms-preview-text').text($(this).val() || 'Your message will appear here...');

                var recipients = parseInt($('#cost-recipients').text().replace(/,/g, '')) || 0;
                var total = recipients * parts * 0.50;
                $('#cost-total').text('{{ currency_symbol() }} ' + Math.round(total).toLocaleString(
                    'en-IN'));
                $('#sms-cost').text('{{ currency_symbol() }} ' + window.fmtAmount(parts * 0.50));
            });

            // Template quick insert
            $('.bp-sms-template').on('click', function() {
                var template = $(this).data('template');
                $('#sms-message').val(template).trigger('input');
            });

            // Audience toggle
            function toggleAudience() {
                var val = $('input[name="audience"]:checked').val();
                $('#group-select').toggle(val === 'group');
                $('#custom-numbers').toggle(val === 'custom');
            }
            toggleAudience();

            $('input[name="audience"]').on('change', function() {
                toggleAudience();
            });

            // Schedule toggle
            function toggleSchedule() {
                var isLater = $('input[name="schedule"]:checked').val() === 'later';
                $('.bp-schedule-fields').toggle(isLater);
            }
            toggleSchedule();

            $('input[name="schedule"]').on('change', function() {
                toggleSchedule();
            });

            // Send test SMS
            $('#btn-send-test').on('click', function() {
                var testNumber = prompt('Enter test phone number (01XXXXXXXXX):');
                if (testNumber) {
                    alert('Test SMS sent to ' + testNumber);
                }
            });

            // Sync counter/preview with any prefilled message (edit screen).
            $('#sms-message').trigger('input');
        });
    </script>
@endpush
