{{-- Advance tab — stat cards reflect ALL transactions; the table below is
     paginated and re-rendered via AJAX pagination. --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="text-center p-3 rounded bp-profile-stat bp-profile-stat-accent">
            <div class="fs-12 text-muted fw-600">Advance Balance</div>
            <div class="fw-800 fs-4 bp-text-accent">{{ currency_symbol() }}
                {{ number_format($advanceStats['balance'], 0) }}</div>
            <div class="fs-12 text-muted">Available for adjustment</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="text-center p-3 rounded bp-profile-stat bp-profile-stat-primary">
            <div class="fs-12 text-muted fw-600">Total Advance Received</div>
            <div class="fw-800 fs-4 text-primary">{{ currency_symbol() }}
                {{ number_format($advanceStats['total_received'], 0) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="text-center p-3 rounded bp-profile-stat bp-profile-stat-success">
            <div class="fs-12 text-muted fw-600">Total Returned</div>
            <div class="fw-800 fs-4 text-success">{{ currency_symbol() }}
                {{ number_format($advanceStats['total_returned'], 0) }}</div>
        </div>
    </div>
</div>
<x-core::table>
    <x-core::table.header>
        <x-core::table.column>Date</x-core::table.column>
        <x-core::table.column>Type</x-core::table.column>
        <x-core::table.column>Amount</x-core::table.column>
        <x-core::table.column>Reference</x-core::table.column>
        <x-core::table.column>Note</x-core::table.column>
    </x-core::table.header>
    <tbody>
        @forelse($advanceTransactions as $txn)
            <tr>
                <td>{{ $txn->payment_date->format('d M Y') }}</td>
                <td>
                    @if ($txn->payment_type === 'advance_payment')
                        <span class="bp-badge bp-badge-primary">Received</span>
                    @else
                        <span class="bp-badge bp-badge-info">Returned</span>
                    @endif
                </td>
                <td class="fw-700 {{ $txn->payment_type === 'advance_return' ? 'text-danger' : 'text-success' }}">
                    {{ $txn->payment_type === 'advance_return' ? '-' : '' }}{{ currency_symbol() }}
                    {{ number_format($txn->amount, 0) }}
                </td>
                <td>{{ $txn->reference ?? $txn->payment_number }}</td>
                <td>{{ $txn->note ?? '--' }}</td>
            </tr>
        @empty
            <x-core::table.empty colspan="5" icon="fa-solid fa-wallet" title="No advance transactions found." />
        @endforelse
    </tbody>
</x-core::table>
<x-core::table.pagination :paginator="$advanceTransactions" item-label="transactions" />
