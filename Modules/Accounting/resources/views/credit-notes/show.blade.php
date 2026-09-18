@extends('core::layouts.master')

@section('title', 'Credit Note — ' . $creditNote->cn_number)
@section('page-title', 'Credit Note — ' . $creditNote->cn_number)

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Accounting</span>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('accounting.credit-notes.index') }}">Credit Notes</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>{{ $creditNote->cn_number }}</span>
@endsection

@section('page-actions')
<a href="{{ route('accounting.credit-notes.print', $creditNote) }}" class="bp-btn bp-btn-outline" target="_blank">
    <i class="fa-solid fa-print me-1"></i> Print
</a>
<a href="{{ route('accounting.credit-notes.pdf', $creditNote) }}" class="bp-btn bp-btn-outline">
    <i class="fa-solid fa-download me-1"></i> PDF
</a>
<a href="{{ route('accounting.credit-notes.index') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left me-1"></i> Back
</a>
@endsection

@section('content')

@php
    $statusBadge = match($creditNote->status) {
        'draft'     => 'bp-badge-dark',
        'issued'    => 'bp-badge-success',
        'applied'   => 'bp-badge-primary',
        'cancelled' => 'bp-badge-danger',
        default     => 'bp-badge-secondary',
    };

    $subtotal   = $creditNote->items->sum(fn($item) => $item->quantity * $item->unit_price);
    $totalTax   = $creditNote->items->sum('tax_amount');
    $grandTotal = $subtotal + $totalTax;
@endphp

<div class="row g-4">

    {{-- Left Column: CN Info + Actions --}}
    <div class="col-xl-4">

        {{-- Info Card --}}
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Credit Note Information</h5>
            </div>
            <div class="bp-card-body">
                <div class="bp-info-row">
                    <div class="bp-info-label bp-info-label-lg">CN Number</div>
                    <div class="bp-info-value fw-800">{{ $creditNote->cn_number }}</div>
                </div>
                <div class="bp-info-row">
                    <div class="bp-info-label bp-info-label-lg">Issue Date</div>
                    <div class="bp-info-value">{{ $creditNote->issue_date->format('d M Y') }}</div>
                </div>
                <div class="bp-info-row">
                    <div class="bp-info-label bp-info-label-lg">Status</div>
                    <div class="bp-info-value">
                        <span class="bp-badge {{ $statusBadge }}">{{ ucfirst($creditNote->status) }}</span>
                    </div>
                </div>
                <div class="bp-info-row">
                    <div class="bp-info-label bp-info-label-lg">Customer</div>
                    <div class="bp-info-value">{{ $creditNote->customer_name ?? 'N/A' }}</div>
                </div>
                @if($creditNote->sale_id)
                <div class="bp-info-row">
                    <div class="bp-info-label bp-info-label-lg">Related Sale</div>
                    <div class="bp-info-value">
                        <code class="fs-12">{{ $creditNote->sale_id }}</code>
                    </div>
                </div>
                @endif
                <div class="bp-info-row">
                    <div class="bp-info-label bp-info-label-lg">Reason</div>
                    <div class="bp-info-value">{{ $creditNote->reason }}</div>
                </div>
                @if($creditNote->notes)
                <div class="bp-info-row">
                    <div class="bp-info-label bp-info-label-lg">Notes</div>
                    <div class="bp-info-value">{{ $creditNote->notes }}</div>
                </div>
                @endif
                <div class="bp-info-row d-none">
                    <div class="bp-info-label bp-info-label-lg">Branch</div>
                    <div class="bp-info-value">{{ $creditNote->branch->name ?? 'N/A' }}</div>
                </div>
                <div class="bp-info-row">
                    <div class="bp-info-label bp-info-label-lg">Created By</div>
                    <div class="bp-info-value">{{ $creditNote->creator->name ?? 'N/A' }}</div>
                </div>
                <div class="bp-info-row bp-info-row-last">
                    <div class="bp-info-label bp-info-label-lg">Created At</div>
                    <div class="bp-info-value">{{ $creditNote->created_at->format('d M Y, h:i A') }}</div>
                </div>
            </div>
        </div>

        {{-- Issue Action (draft only) --}}
        @if($creditNote->status === 'draft')
        @bpCan('accounting.edit')
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-paper-plane me-2"></i>Issue Credit Note</h5>
            </div>
            <div class="bp-card-body">
                <form method="POST" action="{{ route('accounting.credit-notes.issue', $creditNote) }}">
                    @csrf
                    <p class="fs-13 text-muted mb-3">Issue this credit note to make it official and generate the journal entry.</p>
                    <button type="submit" class="bp-btn bp-btn-success w-100">
                        <i class="fa-solid fa-check me-1"></i> Issue Credit Note
                    </button>
                </form>
            </div>
        </div>
        @endbpCan
        @endif

        {{-- Cancel Action (issued only) --}}
        @if($creditNote->status === 'issued')
        @bpCan('accounting.edit')
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-ban me-2"></i>Cancel Credit Note</h5>
            </div>
            <div class="bp-card-body">
                <form method="POST" action="{{ route('accounting.credit-notes.cancel', $creditNote) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="bp-form-label">Cancellation Reason *</label>
                        <input type="text" name="cancel_reason" class="bp-form-control"
                               placeholder="Enter reason for cancellation..." required>
                    </div>
                    <button type="submit" class="bp-btn bp-btn-danger w-100"
                            onclick="return confirm('Cancel this credit note? This action cannot be undone.')">
                        <i class="fa-solid fa-ban me-1"></i> Cancel Credit Note
                    </button>
                </form>
            </div>
        </div>
        @endbpCan
        @endif

        {{-- Delete Action (draft only) --}}
        @if($creditNote->status === 'draft')
        @bpCan('accounting.delete')
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-trash me-2"></i>Delete</h5>
            </div>
            <div class="bp-card-body">
                <form method="POST" action="{{ route('accounting.credit-notes.destroy', $creditNote) }}">
                    @csrf
                    @method('DELETE')
                    <p class="fs-13 text-muted mb-3">Permanently delete this draft credit note.</p>
                    <button type="submit" class="bp-btn bp-btn-danger w-100"
                            onclick="return confirm('Delete credit note {{ $creditNote->cn_number }}? This cannot be undone.')">
                        <i class="fa-solid fa-trash me-1"></i> Delete Draft
                    </button>
                </form>
            </div>
        </div>
        @endbpCan
        @endif

    </div>

    {{-- Right Column: Items + Journal Entry --}}
    <div class="col-xl-8">

        {{-- Amount Summary --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="bp-stat-card">
                    <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-receipt"></i></div>
                    <div class="bp-stat-content">
                        <div class="bp-stat-label">Subtotal</div>
                        <div class="bp-stat-value">{{ money($subtotal) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="bp-stat-card">
                    <div class="bp-stat-icon icon-warning"><i class="fa-solid fa-percent"></i></div>
                    <div class="bp-stat-content">
                        <div class="bp-stat-label">Total Tax</div>
                        <div class="bp-stat-value">{{ money($totalTax) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="bp-stat-card">
                    <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                    <div class="bp-stat-content">
                        <div class="bp-stat-label">Grand Total</div>
                        <div class="bp-stat-value">{{ money($grandTotal) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="bp-card mb-4">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Items</h5>
            </div>
            <div class="bp-card-body p-0">
                <div class="bp-table-wrapper">
                    <table class="bp-table">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Product</th>
                                <th>Description</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Tax ({{ currency_symbol() }})</th>
                                <th class="text-end">Total ({{ currency_symbol() }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($creditNote->items as $index => $item)
                            <tr>
                                <td class="text-center fw-600">{{ $index + 1 }}</td>
                                <td class="fw-600">{{ $item->product->name ?? '—' }}</td>
                                <td>{{ $item->description ?? '—' }}</td>
                                <td class="text-end">{{ num($item->quantity) }}</td>
                                <td class="text-end">{{ money($item->unit_price) }}</td>
                                <td class="text-end">{{ money($item->tax_amount) }}</td>
                                <td class="text-end fw-700">
                                    {{ money($item->quantity * $item->unit_price + $item->tax_amount) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bp-table-totals-row">
                                <td colspan="6" class="text-end fw-700">Subtotal:</td>
                                <td class="text-end fw-700">{{ money($subtotal) }}</td>
                            </tr>
                            <tr class="bp-table-totals-row">
                                <td colspan="6" class="text-end fw-700">Total Tax:</td>
                                <td class="text-end fw-700">{{ money($totalTax) }}</td>
                            </tr>
                            <tr class="bp-table-totals-row">
                                <td colspan="6" class="text-end fw-800">Grand Total:</td>
                                <td class="text-end fw-800">{{ money($grandTotal) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Journal Entry Card --}}
        @if($creditNote->journalEntry)
        <div class="bp-card">
            <div class="bp-card-header">
                <h5 class="bp-card-title"><i class="fa-solid fa-book me-2"></i>Journal Entry</h5>
                <a href="{{ route('accounting.journal-entries.show', $creditNote->journalEntry) }}" class="bp-btn bp-btn-sm bp-btn-outline">
                    <i class="fa-solid fa-eye me-1"></i> {{ $creditNote->journalEntry->entry_number }}
                </a>
            </div>
            <div class="bp-card-body p-0">
                <div class="bp-table-wrapper">
                    <table class="bp-table">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Account Code</th>
                                <th>Account Name</th>
                                <th>Description</th>
                                <th class="text-end">Debit ({{ currency_symbol() }})</th>
                                <th class="text-end">Credit ({{ currency_symbol() }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($creditNote->journalEntry->lines as $index => $line)
                            <tr>
                                <td class="text-center fw-600">{{ $index + 1 }}</td>
                                <td><code class="fs-12">{{ $line->account->account_code ?? '—' }}</code></td>
                                <td class="fw-600">{{ $line->account->account_name ?? '—' }}</td>
                                <td>{{ $line->description ?? '—' }}</td>
                                <td class="text-end fw-700">
                                    @if($line->debit_amount > 0)
                                        {{ money($line->debit_amount) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end fw-700">
                                    @if($line->credit_amount > 0)
                                        {{ money($line->credit_amount) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bp-table-totals-row">
                                <td colspan="4" class="text-end fw-800">Totals:</td>
                                <td class="text-end fw-800">
                                    {{ money($creditNote->journalEntry->lines->sum('debit_amount')) }}
                                </td>
                                <td class="text-end fw-800">
                                    {{ money($creditNote->journalEntry->lines->sum('credit_amount')) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        @endif

    </div>

</div>

@endsection
