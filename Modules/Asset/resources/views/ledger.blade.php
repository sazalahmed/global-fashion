@extends('core::layouts.master')

@section('title', 'Asset Ledger — ' . $asset->asset_code)
@section('page-title', 'Asset Ledger')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('assets.index') }}">Assets</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('assets.show', $asset) }}">{{ $asset->name }}</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Ledger</span>
@endsection

@section('page-actions')
    <a href="{{ route('assets.invoice', $asset) }}" target="_blank" rel="noopener" class="bp-btn bp-btn-info"><i
            class="fa-solid fa-file-invoice me-1"></i> Invoice</a>
    <a href="{{ route('assets.show', $asset) }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i>
        Back</a>
@endsection

@section('content')

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-tag"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Purchase Price</div>
                    <div class="bp-stat-value">{{ number_format($asset->purchase_price, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-check"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Paid</div>
                    <div class="bp-stat-value">{{ number_format($asset->paid_amount, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Outstanding Due</div>
                    <div class="bp-stat-value">{{ number_format($asset->due_amount, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-info"><i class="fa-solid fa-scale-balanced"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Current Book Value</div>
                    <div class="bp-stat-value">{{ number_format($asset->current_value, 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="bp-card">
        <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-book me-2 text-primary"></i>{{ $asset->name }}
                ({{ $asset->asset_code }})</h5>
            <span class="bp-badge bp-badge-info">{{ $asset->vendor_name ?? 'No vendor' }}</span>
        </div>
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Particulars</th>
                            <th>Cost In</th>
                            <th>Paid</th>
                            <th>Depreciation</th>
                            <th>Due Balance</th>
                            <th>Book Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                                <td>{{ $row['particulars'] }}</td>
                                <td>{{ ($row['debit'] ?? 0) > 0 ? money($row['debit']) : '—' }}</td>
                                <td class="text-success">
                                    {{ ($row['credit'] ?? 0) > 0 ? money($row['credit']) : '—' }}</td>
                                <td class="text-danger">
                                    {{ ($row['depreciation'] ?? 0) > 0 ? money($row['depreciation']) : '—' }}</td>
                                <td class="fw-700">{{ money($row['due_balance']) }}</td>
                                <td class="fw-700">{{ money($row['book_value']) }}</td>
                            </tr>
                        @empty
                            <x-core::table.empty colspan="7" icon="fa-solid fa-book" title="No ledger entries." />
                        @endforelse
                    </tbody>
                    @if (count($entries))
                        <tfoot>
                            <tr class="bp-table-total-row">
                                <td colspan="5" class="text-end fw-700">Closing Balance</td>
                                <td class="fw-800 text-danger">{{ money($asset->due_amount) }}</td>
                                <td class="fw-800">{{ money($asset->current_value) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

@endsection
