@extends('core::layouts.master')

@section('title', 'Salary Structure — ' . $structure->name)
@section('page-title', $structure->name)

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>HR</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('payroll.salary-structures') }}">Salary Structures</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>{{ $structure->name }}</span>
@endsection

@section('page-actions')
  @bpCan('hr.edit')
  <a href="{{ route('payroll.salary-structures.edit', $structure) }}" class="bp-btn bp-btn-outline"><i class="fa-solid fa-pen me-1"></i> Edit</a>
  @endbpCan
  <a href="{{ route('payroll.salary-structures') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
@endsection

@section('content')

<div class="row g-4">
  <div class="col-xl-8">

    <!-- Structure Info -->
    <div class="bp-card mb-4">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-sitemap me-2"></i>Structure Details</h5>
        <span class="bp-badge {{ $structure->is_active ? 'bp-badge-success' : 'bp-badge-danger' }}">{{ $structure->is_active ? 'Active' : 'Inactive' }}</span>
      </div>
      <div class="bp-card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="bp-form-label">Name</label>
            <div class="fw-700">{{ $structure->name }}</div>
          </div>
          <div class="col-md-4">
            <label class="bp-form-label">Code</label>
            <div><code>{{ $structure->code }}</code></div>
          </div>
          <div class="col-md-4">
            <label class="bp-form-label">Created</label>
            <div>{{ $structure->created_at->format('d M Y') }}</div>
          </div>
          @if($structure->description)
          <div class="col-12">
            <label class="bp-form-label">Description</label>
            <div class="text-muted">{{ $structure->description }}</div>
          </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Components Table -->
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-puzzle-piece me-2"></i>Components</h5>
        <span class="bp-badge bp-badge-info">{{ $structure->components->count() }} {{ Str::plural('Component', $structure->components->count()) }}</span>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Type</th>
                <th>Calculation</th>
                <th class="text-end">Value</th>
              </tr>
            </thead>
            <tbody>
              @forelse($structure->components as $index => $comp)
                <tr>
                  <td class="text-muted">{{ $index + 1 }}</td>
                  <td class="fw-700">{{ $comp->name }}</td>
                  <td>
                    <span class="bp-badge {{ $comp->type === 'earning' ? 'bp-badge-success' : 'bp-badge-danger' }}">
                      {{ ucfirst($comp->type) }}
                    </span>
                  </td>
                  <td>{{ ucfirst($comp->calculation_type) }}{{ $comp->calculation_type === 'percentage' ? ' of ' . ucfirst($comp->percentage_of ?? 'basic') : '' }}</td>
                  <td class="text-end fw-700">
                    @if($comp->calculation_type === 'fixed')
                      {{ currency_symbol() }} {{ number_format($comp->amount, 0) }}
                    @else
                      {{ number_format($comp->percentage, 1) }}%
                    @endif
                  </td>
                </tr>
              @empty
                <x-core::table.empty colspan="5" icon="fa-solid fa-puzzle-piece" title="No components defined" />
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

  <div class="col-xl-4">
    <!-- Danger Zone -->
    @bpCan('hr.delete')
    <div class="d-flex flex-column gap-2 mb-4">
      <form action="{{ route('payroll.salary-structures.destroy', $structure) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit" class="bp-btn bp-btn-danger w-100 justify-content-center delete-confirm" data-name="{{ $structure->name }}"><i class="fa-solid fa-trash me-2"></i> Delete Structure</button>
      </form>
    </div>
    @endbpCan
  </div>
</div>

@endsection
