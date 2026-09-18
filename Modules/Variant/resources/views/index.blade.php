@extends('core::layouts.master')

@section('title', __("Product Variants"))
@section('page-title', __("Product Variants"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('products.index') }}">Products</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Variants</span>
@endsection

@section('page-actions')
  @bpCan('variants.create')
  <a href="{{ route('variants.create') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-plus"></i> Add Variant Attribute
  </a>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-swatchbook"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Attributes</div>
          <div class="bp-stat-value">{{ $totalAttributes }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-tags"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Values</div>
          <div class="bp-stat-value">{{ $totalValues }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Products Using Variants</div>
          <div class="bp-stat-value">{{ $productsUsing }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Variant Attributes Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar action="{{ route('variants.index') }}" searchPlaceholder="Search attributes...">
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column>Attribute Name</x-core::table.column>
      <x-core::table.column>Values</x-core::table.column>
      <x-core::table.column :sortable="true" field="products_count">Products Using</x-core::table.column>
      <x-core::table.column>Display Type</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($attributes as $attribute)
        <tr>
          <td class="fw-700 fs-13">{{ $attribute->name }}</td>
          <td>
            <div class="d-flex flex-wrap gap-1">
              @foreach($attribute->values as $value)
                <span class="bp-badge bp-badge-primary">{{ $value->value }}</span>
              @endforeach
            </div>
          </td>
          <td class="fw-700">{{ $attribute->product_variants_count ?? 0 }}</td>
          <td><span class="bp-badge {{ $attribute->display_type === 'color_swatch' ? 'bp-badge-info' : ($attribute->display_type === 'button' ? 'bp-badge-primary' : 'bp-badge-secondary') }}">{{ $attribute->display_type_label }}</span></td>
          <td><x-core::status-toggle :url="route('variants.toggle-status', $attribute->id)" :active="$attribute->status === 'active'" /></td>
          <td>
            @bpCanAny('variants.view','variants.edit','variants.delete')
            <div class="dropdown">
              <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
              <ul class="dropdown-menu dropdown-menu-end">
                @bpCan('variants.view')
                <li><a class="dropdown-item" href="{{ route('variants.show', $attribute) }}"><i class="fa-solid fa-eye"></i> View</a></li>
                @endbpCan
                @bpCan('variants.edit')
                <li><a class="dropdown-item" href="{{ route('variants.edit', $attribute) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
                @endbpCan
                <li><hr class="dropdown-divider"></li>
                @bpCan('variants.delete')
                <li>
                  <form action="{{ route('variants.destroy', $attribute) }}" method="POST" class="bp-delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $attribute->name }}"><i class="fa-solid fa-trash"></i> Delete</button>
                  </form>
                </li>
                @endbpCan
              </ul>
            </div>
            @endbpCanAny
          </td>
        </tr>
      @empty
        <x-core::table.empty :colspan="6" icon="fa-swatchbook" :title="__('No variant attributes found')" />
      @endforelse
    </tbody>

    <x-slot:pagination>
      <x-core::table.pagination :paginator="$attributes" itemLabel="attributes" />
    </x-slot:pagination>
  </x-core::table>

@endsection
