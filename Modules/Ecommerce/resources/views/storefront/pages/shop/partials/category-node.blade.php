{{-- Recursive node for the shop sidebar category tree.
     Required vars: $category (Category model with recursiveChildren), $depth (int) --}}
@php
  $children    = $category->recursiveChildren ?? collect();
  $hasKids     = $children->count() > 0;
  $isActive    = request('category') === $category->slug;
  // Drop the size/variant selection when navigating to another category — size
  // values are category-specific (pant sizes vs shirt sizes), so a pant size
  // must not linger when switching to a shirt/panjabi category, and vice versa.
  $linkUrl     = route('storefront.shop.index', array_merge(request()->except(['page', 'category', 'variants']), ['category' => $category->slug]));
  // Auto-expand a branch when the active selection sits inside it.
  $branchOpen  = $isActive || ($hasKids && $children->contains(fn ($c) => $c->slug === request('category') || $c->recursiveChildren->isNotEmpty()));
@endphp
<li class="shop-cat-node shop-cat-node--depth-{{ $depth }} {{ $isActive ? 'is-active' : '' }} {{ $hasKids ? 'has-children' : '' }} {{ $branchOpen ? 'is-open' : '' }}">
  <div class="shop-cat-row">
    @if($hasKids)
      <button type="button" class="shop-cat-toggle" aria-expanded="{{ $branchOpen ? 'true' : 'false' }}" aria-label="Toggle subcategories">
        <i class="fas fa-{{ $branchOpen ? 'minus' : 'plus' }}"></i>
      </button>
    @else
      <span class="shop-cat-spacer"></span>
    @endif
    <a href="{{ $linkUrl }}" class="shop-cat-link {{ $isActive ? 'active' : '' }}">
      <span class="shop-cat-name">{{ $category->name }}</span>
      @if(isset($category->products_count))
        <span class="shop-cat-count">{{ str_pad($category->products_count, 2, '0', STR_PAD_LEFT) }}</span>
      @endif
    </a>
  </div>
  @if($hasKids)
    <ul class="shop-cat-children" {!! $branchOpen ? '' : 'hidden' !!}>
      @foreach($children as $child)
        @include('ecommerce::storefront.pages.shop.partials.category-node', ['category' => $child, 'depth' => $depth + 1])
      @endforeach
    </ul>
  @endif
</li>
