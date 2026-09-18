{{--
  Sale Details quick view — rendered into the modal on the Sales index page.
  Loaded via AJAX, so it must be self-contained (no @push/@stack/extends).

  Layout: sale meta (left) + To: customer (right), then line items with
  per-row Image/Variation, then totals and customer note.
--}}
@php
    $customerName = $sale->customer?->name ?? ($sale->customer_name_snapshot ?? __('Walk-in Customer'));
    $customerPhone = $sale->customer?->phone ?? $sale->customer_phone_snapshot;
    $customerEmail = $sale->customer?->email;
    $areaParts = array_filter([$sale->thana?->thana_name, $sale->district?->district_name]);
    $shipArea = implode(', ', $areaParts);

    $paymentStatusMap = [
        'paid' => ['bp-badge-success', 'Paid'],
        'partial' => ['bp-badge-warning', 'Partial'],
        'unpaid' => ['bp-badge-danger', 'Unpaid'],
    ];
    [$payClass, $payLabel] = $paymentStatusMap[$sale->payment_status] ?? [
        'bp-badge-secondary',
        ucfirst((string) $sale->payment_status),
    ];
    $statusLabel = \Modules\Sale\Models\Sale::STATUSES[$sale->status] ?? $sale->status;
@endphp

<div class="bp-invoice-quick-view">

    {{-- ── Sale meta (left) + To: customer (right) ── --}}
    <div class="row g-3 mb-3">
        <div class="col-md-7">
            <div class="bp-invoice-qv-party">
                <div class="bp-invoice-qv-party-label">{{ __('Sale Details') }}</div>
                <dl class="bp-invoice-qv-meta mb-0">
                    <dt>{{ __('Date') }}:</dt>
                    <dd>{{ $sale->sale_date?->format('d-m-Y') }}</dd>

                    @if ($sale->reference_number)
                        <dt>{{ __('Reference') }}:</dt>
                        <dd>{{ $sale->reference_number }}</dd>
                    @endif

                    <dt>{{ __('Invoice ID') }}:</dt>
                    <dd>{{ $sale->invoice_number }}</dd>

                    <dt>{{ __('Sale Status') }}:</dt>
                    <dd>
                        <span class="bp-badge bp-badge-info">{{ __($statusLabel) }}</span>
                        <span class="bp-badge {{ $payClass }} ms-1">{{ __($payLabel) }}</span>
                        @if ($sale->source)
                            <span class="bp-badge bp-badge-secondary ms-1">{{ strtoupper($sale->source) }}</span>
                        @endif
                    </dd>

                    @if ($sale->courier_name)
                        <dt>{{ __('Courier') }}:</dt>
                        <dd>
                            {{ $sale->courier_name }}
                            @if ($sale->courier_status)
                                <span class="bp-badge bp-badge-info ms-1">{{ $sale->courier_status }}</span>
                            @endif
                            @if ($sale->courier_tracking_link)
                                <a href="{{ $sale->courier_tracking_link }}" target="_blank" rel="noopener"
                                    class="ms-1">
                                    <i class="fa-solid fa-up-right-from-square fa-sm"></i>
                                </a>
                            @endif
                        </dd>
                    @endif

                    @if ($sale->creator)
                        <dt>{{ __('Staff') }}:</dt>
                        <dd>{{ $sale->creator->name }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="col-md-5">
            <div class="bp-invoice-qv-party h-100">
                <div class="bp-invoice-qv-party-label">{{ __('Bill To') }}</div>
                <div class="bp-invoice-qv-party-name">{{ $customerName }}</div>
                @if ($customerPhone)
                    <div class="fs-12"><i class="fa-solid fa-phone fa-sm me-1 text-muted"></i>{{ $customerPhone }}
                    </div>
                @endif
                @if ($customerEmail)
                    <div class="fs-12"><i class="fa-solid fa-envelope fa-sm me-1 text-muted"></i>{{ $customerEmail }}
                    </div>
                @endif
                @if ($shipArea)
                    <div class="fs-12"><i class="fa-solid fa-map fa-sm me-1 text-muted"></i>{{ $shipArea }}</div>
                @endif
                @if ($sale->customer_address)
                    <div class="fs-12"><i
                            class="fa-solid fa-location-dot fa-sm me-1 text-muted"></i>{{ $sale->customer_address }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Items table ── --}}
    <div class="bp-invoice-qv-items mb-3">
        <div class="bp-table-wrapper">
            <table class="bp-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th class="text-center bp-invoice-qv-img-col">{{ __('Image') }}</th>
                        <th>{{ __('Product') }}</th>
                        <th class="text-start">{{ __('Qty') }}</th>
                        <th class="text-start">{{ __('Unit Price') }}</th>
                        <th class="text-start">{{ __('Discount') }}</th>
                        <th class="text-start">{{ __('SubTotal') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sale->items as $idx => $item)
                        <tr>
                            <td class="text-muted">{{ $idx + 1 }}</td>
                            <td class="text-center bp-invoice-qv-img-col">
                                @php $thumb = $item->product?->thumbnail; @endphp
                                @if ($thumb)
                                    <img src="{{ asset($thumb) }}" alt="" class="bp-invoice-qv-thumb">
                                @else
                                    <div class="bp-invoice-qv-thumb bp-invoice-qv-thumb-placeholder"><i
                                            class="fa-solid fa-image"></i></div>
                                @endif
                            </td>
                            <td class="bp-invoice-qv-product">
                                @php($qvTitle = $item->product?->name ?: \Illuminate\Support\Str::beforeLast($item->product_name, ' — '))
                                @php($qvVariant = $item->variant ? $item->variant->attributeValues->map(fn($v) => trim(($v->attribute?->base_name ? $v->attribute->base_name . ': ' : '') . $v->value))->filter()->implode(', ') : $item->variant_label ?? '')
                                <div class="fw-700">{{ $qvTitle }}</div>
                                @if ($item->product?->model)
                                    <div class="fs-11 text-muted">{{ __('Model') }}: {{ $item->product->model }}
                                    </div>
                                @endif
                                @if ($qvVariant)
                                    <div class="fs-11 text-muted">{{ $qvVariant }}</div>
                                @endif
                            </td>
                            <td class="text-start fw-700">{{ $item->quantity }}</td>
                            <td class="text-start">{{ num($item->unit_price) }}</td>
                            <td class="text-start text-muted">{{ num($item->discount_amount) }}</td>
                            <td class="text-start fw-800">{{ num($item->subtotal) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">{{ __('No items') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">{{ __('Total') }}:</th>
                        <th class="text-start">{{ $sale->items->sum('quantity') }}</th>
                        <th></th>
                        <th class="text-start">{{ num($sale->items->sum('discount_amount')) }}</th>
                        <th class="text-start fw-800">{{ num($sale->subtotal) }}</th>
                    </tr>
                    @if ($sale->discount_amount > 0)
                        <tr>
                            <th colspan="6" class="text-end">{{ __('Discount') }}:</th>
                            <th class="text-start">{{ num($sale->discount_amount) }}</th>
                        </tr>
                    @endif
                    <tr>
                        <th colspan="6" class="text-end">{{ __('Shipping Cost') }}:</th>
                        <th class="text-start">{{ num($sale->shipping_charge) }}</th>
                    </tr>
                    <tr class="bp-invoice-qv-grand">
                        <th colspan="6" class="text-end">{{ __('Grand Total') }}:</th>
                        <th class="text-start fw-800">{{ num($sale->grand_total) }}</th>
                    </tr>
                    <tr>
                        <th colspan="6" class="text-end">{{ __('Paid') }}:</th>
                        <th class="text-start text-success fw-700">{{ num($sale->paid_amount) }}</th>
                    </tr>
                    <tr>
                        <th colspan="6" class="text-end">{{ __('Due') }}:</th>
                        <th class="text-start fw-700 {{ $sale->due_amount > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ num($sale->due_amount) }}</th>
                    </tr>
                    @if ($sale->notes)
                        <tr>
                            <th>{{ __('Note') }}</th>
                            <td colspan="6" class="fs-13 p-2">{{ $sale->notes }}</td>
                        </tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </div>

</div>
