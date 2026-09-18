{{-- Table wrapper: bp-card > bp-table-wrapper > table.bp-table --}}
@props(['id' => null, 'selectable' => false])

<div class="bp-card" @if($id) id="{{ $id }}" @endif>
    {{-- Optional filter bar slot --}}
    @if(isset($filters))
        <div class="bp-card-header">
            {{ $filters }}
        </div>
    @endif

    {{-- Optional bulk actions slot --}}
    @if(isset($bulkActions))
        {{ $bulkActions }}
    @endif

    <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
            <table {{ $attributes->merge(['class' => 'bp-table']) }}>
                {{ $slot }}
            </table>
        </div>
    </div>

    {{-- Optional pagination slot --}}
    @if(isset($pagination))
        {{ $pagination }}
    @endif
</div>
