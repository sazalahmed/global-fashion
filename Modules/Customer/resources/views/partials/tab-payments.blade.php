{{-- Payments tab — rendered on page load and re-rendered via AJAX pagination --}}
<x-core::table>
    <x-core::table.header>
        <x-core::table.column>Date</x-core::table.column>
        <x-core::table.column>Reference</x-core::table.column>
        <x-core::table.column>Amount</x-core::table.column>
        <x-core::table.column>Method</x-core::table.column>
        <x-core::table.column>Type</x-core::table.column>
        <x-core::table.column>Note</x-core::table.column>
    </x-core::table.header>
    <tbody>
        @forelse($payments as $payment)
            <tr>
                <td>{{ $payment->payment_date->format('d M Y') }}</td>
                <td>{{ $payment->payment_number }}</td>
                <td class="fw-700 text-success">{{ currency_symbol() }}
                    {{ number_format($payment->amount, 0) }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $payment->payment_type)) }}</td>
                <td>{{ $payment->note ?? '--' }}</td>
            </tr>
        @empty
            <x-core::table.empty colspan="6" icon="fa-solid fa-bangladeshi-taka-sign"
                title="No payment records found." />
        @endforelse
    </tbody>
</x-core::table>
<x-core::table.pagination :paginator="$payments" item-label="payments" />
