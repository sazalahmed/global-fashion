{{-- Sales History tab — rendered on page load and re-rendered via AJAX pagination --}}
<x-core::table>
    <x-core::table.header>
        <x-core::table.column>Invoice #</x-core::table.column>
        <x-core::table.column>Date</x-core::table.column>
        <x-core::table.column>Total</x-core::table.column>
        <x-core::table.column>Paid</x-core::table.column>
        <x-core::table.column>Due</x-core::table.column>
        <x-core::table.column>Status</x-core::table.column>
        <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>
    <tbody>
        @forelse($sales as $sale)
            <tr>
                <td><a href="{{ route('sales.show', $sale) }}">{{ $sale->invoice_number }}</a></td>
                <td>{{ $sale->sale_date->format('d M Y') }}</td>
                <td class="fw-700">{{ currency_symbol() }}
                    {{ number_format($sale->grand_total, 0) }}</td>
                <td class="text-success">{{ currency_symbol() }}
                    {{ number_format($sale->paid_amount, 0) }}</td>
                <td class="text-danger">
                    {{ $sale->due_amount > 0 ? currency_symbol() . ' ' . number_format($sale->due_amount, 0) : '0' }}
                </td>
                <td>
                    @if ($sale->payment_status === 'paid')
                        <span class="bp-badge bp-badge-success">Paid</span>
                    @elseif($sale->payment_status === 'partial')
                        <span class="bp-badge bp-badge-warning">Partial</span>
                    @else
                        <span class="bp-badge bp-badge-danger">Unpaid</span>
                    @endif
                </td>
                <td>
                    <div class="dropdown">
                        <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('sales.show', $sale) }}"><i
                                        class="fa-solid fa-eye me-2"></i> View</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <x-core::table.empty colspan="7" icon="fa-solid fa-chart-line" title="No sales history found." />
        @endforelse
    </tbody>
</x-core::table>
<x-core::table.pagination :paginator="$sales" item-label="sales" />
