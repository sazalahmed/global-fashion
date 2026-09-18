@extends('core::layouts.master')

@section('title', $requisition->requisition_number)
@section('page-title', 'Requisition ' . $requisition->requisition_number)

@section('breadcrumb')
    <span class="sep">
        <i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('requisitions.index') }}">Requisitions</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $requisition->requisition_number }}</span>
@endsection

@section('page-actions')
    @php
        $statusClass = match ($requisition->status) {
            'approved' => 'info',
            'ordered' => 'primary',
            'fulfilled' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
            default => 'warning',
        };
    @endphp

    @if ($requisition->status === 'pending')
        @bpCan('purchases.approve')
            <form action="{{ route('requisitions.approve', $requisition) }}" method="POST" class="d-inline">@csrf
                <button class="bp-btn bp-btn-success"><i class="fa-solid fa-check me-1"></i> Approve</button>
            </form>
            <button type="button" class="bp-btn bp-btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal"><i
                    class="fa-solid fa-xmark me-1"></i> Reject</button>
        @endbpCan
        @bpCan('purchases.edit')
            <a href="{{ route('requisitions.edit', $requisition) }}" class="bp-btn bp-btn-warning"><i
                    class="fa-solid fa-pen me-1"></i> Edit</a>
        @endbpCan
    @endif

    @if ($requisition->status === 'approved')
        @bpCan('purchases.create')
            <a href="{{ route('requisitions.convert', $requisition) }}" class="bp-btn bp-btn-success"><i
                    class="fa-solid fa-cart-plus me-1"></i> Convert to Purchase</a>
        @endbpCan
        @bpCan('purchases.edit')
            <form action="{{ route('requisitions.cancel', $requisition) }}" method="POST" class="d-inline">@csrf
                <button class="bp-btn bp-btn-danger"><i class="fa-solid fa-ban me-1"></i> Cancel</button>
            </form>
        @endbpCan
    @endif

    @if (
        $requisition->status === 'ordered' &&
            $requisition->purchase &&
            in_array($requisition->purchase->status, ['received', 'partial_received']))
        @bpCan('purchases.edit')
            <form action="{{ route('requisitions.mark-fulfilled', $requisition) }}" method="POST" class="d-inline">@csrf
                <button class="bp-btn bp-btn-success"><i class="fa-solid fa-box-open me-1"></i> Mark Fulfilled</button>
            </form>
        @endbpCan
    @endif

    <a href="{{ route('requisitions.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i>
        Back</a>
@endsection

@section('content')

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="bp-card mb-4">
                <div class="bp-card-body text-center py-4">
                    <span class="bp-badge bp-badge-{{ $statusClass }} fs-13">{{ ucfirst($requisition->status) }}</span>
                    <h4 class="fw-800 mt-2 mb-0">{{ $requisition->requisition_number }}</h4>
                </div>
                <div class="bp-card-body pt-0">
                    <div class="bp-info-row">
                        <div class="bp-info-label">Requested By</div>
                        <div class="bp-info-value">{{ $requisition->requester->name ?? '—' }}</div>
                    </div>
                    @if ($requisition->department)
                        <div class="bp-info-row">
                            <div class="bp-info-label">Department</div>
                            <div class="bp-info-value">{{ $requisition->department }}</div>
                        </div>
                    @endif
                    <div class="bp-info-row">
                        <div class="bp-info-label">Required Date</div>
                        <div class="bp-info-value">
                            {{ $requisition->required_date ? $requisition->required_date->format('d M Y') : '—' }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label">Priority</div>
                        <div class="bp-info-value">{{ ucfirst($requisition->priority) }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label">Branch</div>
                        <div class="bp-info-value">{{ $requisition->branch->name ?? 'Default' }}</div>
                    </div>
                    @if ($requisition->reviewer)
                        <div class="bp-info-row">
                            <div class="bp-info-label">Reviewed By</div>
                            <div class="bp-info-value">{{ $requisition->reviewer->name }}</div>
                        </div>
                    @endif
                    @if ($requisition->note)
                        <div class="bp-info-row bp-info-row-last">
                            <div class="bp-info-label">Note</div>
                            <div class="bp-info-value">{{ $requisition->note }}</div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($requisition->status === 'rejected' && $requisition->rejection_reason)
                <div class="alert alert-danger"><strong>Rejected:</strong> {{ $requisition->rejection_reason }}</div>
            @endif

            @if ($requisition->purchase)
                <div class="bp-card mb-4">
                    <div class="bp-card-header">
                        <h5 class="bp-card-title"><i class="fa-solid fa-file-invoice me-2"></i>Linked Purchase</h5>
                    </div>
                    <div class="bp-card-body">
                        <div class="bp-info-row bp-info-row-last">
                            <div class="bp-info-label"><a
                                    href="{{ route('purchases.show', $requisition->purchase) }}">{{ $requisition->purchase->po_number }}</a>
                            </div>
                            <div class="bp-info-value"><span
                                    class="bp-badge bp-badge-info">{{ ucfirst(str_replace('_', ' ', $requisition->purchase->status)) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-xl-8">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-boxes-stacked me-2"></i>Requested Items</h5>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requisition->items as $item)
                                    <tr>
                                        <td class="fw-600">{{ $item->product->name ?? '—' }}@if ($item->variant)
                                                <div class="fs-11 text-muted">{{ $item->variant->variant_name }}</div>
                                            @endif
                                        </td>
                                        <td class="fw-700">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="text-muted fs-13">{{ $item->note ?? '—' }}</td>
                                    </tr>
                                    @empty
                                        <x-core::table.empty colspan="3" icon="fa-solid fa-boxes-stacked" title="No items" />
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($requisition->status === 'pending')
            <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('requisitions.reject', $requisition) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Reject Requisition</h5><button type="button" class="btn-close"
                                    data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <label class="bp-form-label">Reason</label>
                                <textarea class="bp-form-control" name="rejection_reason" rows="3" placeholder="Why is this being rejected?"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="bp-btn bp-btn-outline" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="bp-btn bp-btn-danger">Reject</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

    @endsection
