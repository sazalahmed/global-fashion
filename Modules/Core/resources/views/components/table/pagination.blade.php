{{-- Table pagination footer --}}
@props(['paginator', 'itemLabel' => 'records'])

@if($paginator->hasPages())
    <div class="bp-card-footer">
        <div class="bp-pagination">
            <span class="page-info">
                Showing {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} of {{ number_format($paginator->total()) }} {{ $itemLabel }}
            </span>
            <nav>
                {{ $paginator->appends(request()->query())->onEachSide(1)->links('core::components.table.pagination-links') }}
            </nav>
        </div>
    </div>
@endif
