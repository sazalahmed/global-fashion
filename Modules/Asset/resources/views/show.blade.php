@extends('core::layouts.master')

@section('title', $asset->name . ' — ' . $asset->asset_code)
@section('page-title', $asset->name)

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('assets.index') }}">Assets</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $asset->name }}</span>
@endsection

@section('page-actions')
    <a href="{{ route('assets.invoice', $asset) }}" target="_blank" rel="noopener" class="bp-btn bp-btn-info"><i
            class="fa-solid fa-file-invoice me-1"></i> Invoice</a>
    <a href="{{ route('assets.invoice.pdf', $asset) }}" class="bp-btn bp-btn-warning"><i
            class="fa-solid fa-download me-1"></i> PDF</a>
    <a href="{{ route('assets.ledger', $asset) }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-book me-1"></i>
        Ledger</a>
    @bpCan('finance.edit')
        <a href="{{ route('assets.edit', $asset) }}" class="bp-btn bp-btn-success"><i class="fa-solid fa-pen me-1"></i> Edit</a>
    @endbpCan
    <a href="{{ route('assets.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

    <div class="row g-4">

        <!-- Left Column -->
        <div class="col-xl-5">

            <!-- Asset Header -->
            <div class="bp-card mb-4">
                <div class="bp-card-body text-center py-5">
                    @if ($asset->photo)
                        <img src="{{ upload_url($asset->photo) }}" alt="{{ $asset->name }}" class="mb-3 rounded"
                            width="120">
                    @else
                        <div class="bp-stat-icon icon-primary mb-3 mx-auto"><i class="fa-solid fa-building"></i></div>
                    @endif
                    <h4 class="fw-800 mb-1">{{ $asset->name }}</h4>
                    <div class="mb-2"><code class="fs-12">{{ $asset->asset_code }}</code></div>
                    @if ($asset->status === 'active')
                        <span class="bp-badge bp-badge-success">Active</span>
                    @elseif($asset->status === 'under_maintenance')
                        <span class="bp-badge bp-badge-warning">Under Maintenance</span>
                    @elseif($asset->status === 'disposed')
                        <span class="bp-badge bp-badge-danger">Disposed</span>
                    @elseif($asset->status === 'written_off')
                        <span class="bp-badge bp-badge-dark">Written Off</span>
                    @endif
                </div>
            </div>

            <!-- Asset Info Card -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Asset Details</h5>
                </div>
                <div class="bp-card-body">
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Asset ID</div>
                        <div class="bp-info-value fw-700">{{ $asset->asset_code }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Category</div>
                        <div class="bp-info-value"><span
                                class="bp-badge bp-badge-primary">{{ $asset->category->name ?? '—' }}</span></div>
                    </div>
                    @if ($asset->serial_number)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Serial Number</div>
                            <div class="bp-info-value"><code class="fs-12">{{ $asset->serial_number }}</code></div>
                        </div>
                    @endif
                    <div class="bp-info-row d-none">
                        <div class="bp-info-label bp-info-label-lg">Location / Branch</div>
                        <div class="bp-info-value">{{ $asset->branch->name ?? ($asset->location ?? '—') }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Purchase Date</div>
                        <div class="bp-info-value">{{ $asset->purchase_date->format('d M Y') }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Purchase Price</div>
                        <div class="bp-info-value fw-800">{{ money($asset->purchase_price) }}</div>
                    </div>
                    @if ($asset->vendor_name)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Vendor</div>
                            <div class="bp-info-value">{{ $asset->vendor_name }}</div>
                        </div>
                    @endif
                    @if ($asset->vendor_invoice_no)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Vendor Invoice</div>
                            <div class="bp-info-value"><code class="fs-12">{{ $asset->vendor_invoice_no }}</code></div>
                        </div>
                    @endif
                    @if ($asset->warranty_expiry)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Warranty Expiry</div>
                            <div class="bp-info-value">
                                @if ($asset->warranty_expiry->isFuture())
                                    <span
                                        class="bp-badge bp-badge-success">{{ $asset->warranty_expiry->format('d M Y') }}</span>
                                @else
                                    <span class="bp-badge bp-badge-danger">Expired
                                        {{ $asset->warranty_expiry->format('d M Y') }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                    @if ($asset->warranty_info)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Warranty Info</div>
                            <div class="bp-info-value">{{ $asset->warranty_info }}</div>
                        </div>
                    @endif
                    @if ($asset->description)
                        <div class="bp-info-row bp-info-row-last">
                            <div class="bp-info-label bp-info-label-lg">Description</div>
                            <div class="bp-info-value">{{ $asset->description }}</div>
                        </div>
                    @endif
                    <div class="bp-info-row bp-info-row-last">
                        <div class="bp-info-label bp-info-label-lg">Created By</div>
                        <div class="bp-info-value">{{ $asset->creator->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <!-- Depreciation Card -->
            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-chart-line me-2 text-info"></i>Depreciation</h5>
                </div>
                <div class="bp-card-body">
                    <div class="bp-info-row">
                        <div class="bp-info-label bp-info-label-lg">Depreciable</div>
                        <div class="bp-info-value fw-600">{{ $asset->is_depreciable ? 'Yes' : 'No' }}</div>
                    </div>
                    @if ($asset->is_depreciable)
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Method</div>
                            <div class="bp-info-value fw-600">
                                {{ $asset->depreciation_method === 'straight_line' ? 'Straight Line' : 'Declining Balance' }}
                            </div>
                        </div>
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Useful Life</div>
                            <div class="bp-info-value">{{ $asset->useful_life_years }} Years</div>
                        </div>
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Purchase Price</div>
                            <div class="bp-info-value fw-700">{{ money($asset->purchase_price) }}</div>
                        </div>
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Salvage Value</div>
                            <div class="bp-info-value">{{ money($asset->salvage_value) }}</div>
                        </div>
                        @php
                            $depreciable = $asset->purchase_price - $asset->salvage_value;
                            $annualDep = $asset->useful_life_years > 0 ? $depreciable / $asset->useful_life_years : 0;
                        @endphp
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Depreciation / Year</div>
                            <div class="bp-info-value fw-700 text-danger">{{ money($annualDep) }}</div>
                        </div>
                        <div class="bp-info-row">
                            <div class="bp-info-label bp-info-label-lg">Accumulated Dep.</div>
                            <div class="bp-info-value fw-700">{{ money($asset->accumulated_depreciation) }}</div>
                        </div>
                        @if ($asset->last_depreciation_date)
                            <div class="bp-info-row">
                                <div class="bp-info-label bp-info-label-lg">Last Depreciation</div>
                                <div class="bp-info-value">{{ $asset->last_depreciation_date->format('d M Y') }}</div>
                            </div>
                        @endif
                    @endif
                    <div class="bp-info-row bp-info-row-last">
                        <div class="bp-info-label bp-info-label-lg">Current Book Value</div>
                        <div class="bp-info-value fw-800 text-success">{{ money($asset->current_value) }}</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column -->
        <div class="col-xl-7">

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">Purchase Price</div>
                            <div class="bp-stat-value">{{ number_format($asset->purchase_price, 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">Current Value</div>
                            <div class="bp-stat-value">{{ number_format($asset->current_value, 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-danger"><i class="fa-solid fa-chart-line"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">Depreciated</div>
                            <div class="bp-stat-value">
                                {{ $asset->purchase_price > 0 ? number_format(($asset->accumulated_depreciation / $asset->purchase_price) * 100, 0) : 0 }}%
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bp-stat-card">
                        <div class="bp-stat-icon icon-info"><i class="fa-solid fa-wrench"></i></div>
                        <div class="bp-stat-content">
                            <div class="bp-stat-label">Maintenances</div>
                            <div class="bp-stat-value">{{ $asset->maintenances->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Payment / Due -->
            <div class="bp-card mb-4">
                <div class="bp-card-header d-flex justify-content-between align-items-center">
                    <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i>Purchase
                        Payment</h5>
                    @php
                        $payStatusClass =
                            $asset->payment_status === 'paid'
                                ? 'success'
                                : ($asset->payment_status === 'partial'
                                    ? 'warning'
                                    : 'danger');
                    @endphp
                    <span class="bp-badge bp-badge-{{ $payStatusClass }}">{{ ucfirst($asset->payment_status) }}</span>
                </div>
                <div class="bp-card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <div class="bp-stat-label">Total</div>
                            <div class="fw-800">{{ money($asset->purchase_price) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="bp-stat-label">Paid</div>
                            <div class="fw-800 text-success">{{ money($asset->paid_amount) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="bp-stat-label">Due</div>
                            <div class="fw-800 text-danger">{{ money($asset->due_amount) }}</div>
                        </div>
                    </div>

                    @bpCan('finance.edit')
                        @if ($asset->due_amount > 0)
                            <button type="button" class="bp-btn bp-btn-primary bp-btn-sm" data-bs-toggle="modal"
                                data-bs-target="#recordPaymentModal">
                                <i class="fa-solid fa-bangladeshi-taka-sign me-1"></i> Record Payment
                            </button>
                        @endif
                    @endbpCan

                    @if ($asset->payments->count())
                        <div class="bp-table-wrapper mt-3">
                            <table class="bp-table">
                                <thead>
                                    <tr>
                                        <th>Receipt #</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Account</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($asset->payments as $payment)
                                        <tr>
                                            <td class="fw-700">{{ $payment->payment_number }}</td>
                                            <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                            <td class="fw-700 text-success">{{ money($payment->amount) }}</td>
                                            <td>{{ $payment->paymentAccount->name ?? '—' }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('assets.payment.receipt', $payment) }}" target="_blank"
                                                    rel="noopener" class="bp-btn bp-btn-sm bp-btn-outline"
                                                    title="Print Receipt"><i class="fa-solid fa-receipt"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Maintenance Log Tab -->
            <div class="bp-card mb-4">
                <div class="bp-card-header d-flex justify-content-between align-items-center">
                    <h5 class="bp-card-title"><i class="fa-solid fa-wrench me-2"></i>Maintenance Log</h5>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Cost ({{ currency_symbol() }})</th>
                                    <th>Performed By</th>
                                    <th>Next Due</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($asset->maintenances as $maintenance)
                                    <tr>
                                        <td>{{ $maintenance->maintenance_date->format('d M Y') }}</td>
                                        <td>{{ $maintenance->description }}</td>
                                        <td class="fw-700">{{ money($maintenance->cost) }}</td>
                                        <td>{{ $maintenance->performed_by ?? '—' }}</td>
                                        <td>{{ $maintenance->next_maintenance_date ? $maintenance->next_maintenance_date->format('d M Y') : '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <x-core::table.empty colspan="5" icon="fa-solid fa-wrench"
                                        title="No maintenance records yet" />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Maintenance -->
            @bpCan('finance.edit')
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-plus me-2"></i>Add Maintenance Record</h5>
                    </div>
                    <div class="bp-card-body">
                        <form action="{{ route('assets.maintenance', $asset) }}" method="POST" id="assetMaintenanceForm">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="bp-form-label">Maintenance Date *</label>
                                    <input type="date" class="bp-form-control" name="maintenance_date"
                                        value="{{ old('maintenance_date', date('Y-m-d')) }}" required>
                                    @error('maintenance_date')
                                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="bp-form-label">Cost ({{ currency_symbol() }})</label>
                                    <input type="number" class="bp-form-control" name="cost"
                                        value="{{ old('cost', 0) }}" min="0" step="0.01">
                                    @error('cost')
                                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="bp-form-label">Performed By</label>
                                    <input type="text" class="bp-form-control" name="performed_by"
                                        value="{{ old('performed_by') }}" placeholder="e.g. IT Support Team">
                                    @error('performed_by')
                                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="bp-form-label">Next Maintenance Date</label>
                                    <input type="date" class="bp-form-control" name="next_maintenance_date"
                                        value="{{ old('next_maintenance_date') }}">
                                    @error('next_maintenance_date')
                                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-12">
                                    <label class="bp-form-label">Description</label>
                                    <textarea class="bp-form-control" name="description" rows="2" placeholder="What was done...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="text-danger fs-12 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endbpCan

            <!-- Actions: Delete + Save Maintenance on one row -->
            <div class="row g-3 mb-4">
                @bpCan('finance.delete')
                    <div class="col-md-6">
                        <form action="{{ route('assets.destroy', $asset) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bp-btn bp-btn-danger w-100 justify-content-center delete-confirm"
                                data-name="{{ $asset->asset_code }} — {{ $asset->name }}"><i
                                    class="fa-solid fa-trash me-2"></i> Delete Asset</button>
                        </form>
                    </div>
                @endbpCan
                @bpCan('finance.edit')
                    <div class="col-md-6">
                        <button type="submit" form="assetMaintenanceForm"
                            class="bp-btn bp-btn-success w-100 justify-content-center"><i class="fa-solid fa-save me-1"></i>
                            Save Maintenance</button>
                    </div>
                @endbpCan
            </div>

        </div>

    </div>

    @bpCan('finance.edit')
        @if ($asset->due_amount > 0)
            <!-- Record Payment Modal -->
            <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('assets.payment', $asset) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Record Payment — {{ $asset->asset_code }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="bp-info-row bp-info-row-last">
                                            <div class="bp-info-label">Outstanding Due</div>
                                            <div class="bp-info-value fw-800 text-danger">{{ money($asset->due_amount) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Amount *</label>
                                        <input type="number" class="bp-form-control" name="amount" min="0.01"
                                            step="0.01" max="{{ $asset->due_amount }}"
                                            value="{{ num_input($asset->due_amount) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Payment Date *</label>
                                        <input type="date" class="bp-form-control" name="payment_date"
                                            value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Payment Account *</label>
                                        <select class="bp-form-select w-100" name="payment_account_id" required>
                                            <option value="">Select Account</option>
                                            @foreach (\Modules\Payment\Models\PaymentAccount::where('is_active', true)->orderBy('name')->get() as $acc)
                                                <option value="{{ $acc->id }}"
                                                    {{ $asset->payment_account_id == $acc->id ? 'selected' : '' }}>
                                                    {{ $acc->name }}
                                                    ({{ ucfirst(str_replace('_', ' ', $acc->account_type)) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="bp-form-label">Reference</label>
                                        <input type="text" class="bp-form-control" name="reference"
                                            placeholder="e.g. bKash TrxID">
                                    </div>
                                    <div class="col-12">
                                        <label class="bp-form-label">Note</label>
                                        <input type="text" class="bp-form-control" name="note"
                                            placeholder="Optional note">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="bp-btn bp-btn-outline" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="bp-btn bp-btn-primary"><i class="fa-solid fa-save me-1"></i>
                                    Record Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endbpCan

@endsection

@push('scripts')
    <script>
        'use strict';

        $(function() {
            // Disposal confirmation is handled globally by .delete-confirm in app.js
        });
    </script>
@endpush
