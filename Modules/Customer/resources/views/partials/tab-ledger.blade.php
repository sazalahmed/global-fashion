{{-- Ledger / Statement tab — rendered on page load and re-rendered via AJAX
     pagination. Entries stay in chronological order so the running balance
     reads naturally across pages. --}}
@php($isSimpleMode = \Modules\Setting\Services\SettingService::isSimpleMode())
<x-core::table>
    <x-core::table.header>
        <x-core::table.column>Date</x-core::table.column>
        <x-core::table.column>Description</x-core::table.column>
        <x-core::table.column>{{ $isSimpleMode ? 'Charged' : 'Debit' }}</x-core::table.column>
        <x-core::table.column>{{ $isSimpleMode ? 'Paid' : 'Credit' }}</x-core::table.column>
        <x-core::table.column>{{ $isSimpleMode ? 'Due' : 'Balance' }}</x-core::table.column>
    </x-core::table.header>
    <tbody>
        @forelse($ledger['entries'] as $entry)
            <tr>
                <td>{{ \Carbon\Carbon::parse($entry->date)->format('d M Y') }}</td>
                <td>{{ $entry->description }}</td>
                <td>
                    @if ($entry->debit > 0)
                        <span class="text-danger">{{ currency_symbol() }}
                            {{ number_format($entry->debit, 0) }}</span>
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if ($entry->credit > 0)
                        <span class="text-success">{{ currency_symbol() }}
                            {{ number_format($entry->credit, 0) }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="fw-700">{{ currency_symbol() }}
                    {{ number_format($entry->balance, 0) }}</td>
            </tr>
        @empty
            <x-core::table.empty colspan="5" icon="fa-solid fa-book" title="No transactions found." />
        @endforelse
    </tbody>
</x-core::table>
<x-core::table.pagination :paginator="$ledger['entries']" item-label="entries" />
