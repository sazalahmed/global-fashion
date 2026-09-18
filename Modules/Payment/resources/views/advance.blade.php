@extends('core::layouts.master')

@section('title', __("Advance Balances"))
@section('page-title', __("Advance Balances"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('payments.index') }}">Payments</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Advances</span>
@endsection

@section('page-actions')
  @bpCan('payments.export')
  <x-core::export-dropdown module="payments" />
  @endbpCan
@endsection

@section('content')

  <!-- Tabs -->
  <ul class="nav nav-tabs bp-nav-tabs mb-4" role="tablist">
    <li class="nav-item">
      <button class="nav-link active fw-700" data-bs-toggle="tab" data-bs-target="#tabCustomerAdvances" type="button" role="tab">
        <i class="fa-solid fa-users me-1"></i> Customer Advances
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link fw-700" data-bs-toggle="tab" data-bs-target="#tabSupplierAdvances" type="button" role="tab">
        <i class="fa-solid fa-truck me-1"></i> Supplier Advances
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link fw-700" data-bs-toggle="tab" data-bs-target="#tabEmployeeAdvances" type="button" role="tab">
        <i class="fa-solid fa-id-badge me-1"></i> Employee Advances
      </button>
    </li>
  </ul>

  <div class="tab-content">

    <!-- Tab 1: Customer Advances -->
    <div class="tab-pane fade show active" id="tabCustomerAdvances" role="tabpanel">
      <x-core::table>
        <x-slot:filters>
          <h5 class="bp-card-title"><i class="fa-solid fa-wallet me-2 text-info"></i>Customer Advance Balances</h5>
          <span class="bp-badge bp-badge-info">{{ currency_symbol() }} {{ number_format($totals['customer'] ?? 0) }} Total</span>
        </x-slot:filters>

        <x-core::table.header>
          <x-core::table.column>Customer Name</x-core::table.column>
          <x-core::table.column>Phone</x-core::table.column>
          <x-core::table.column align="right">Total Advance Received</x-core::table.column>
          <x-core::table.column align="right">Total Returned</x-core::table.column>
          <x-core::table.column align="right">Current Balance</x-core::table.column>
          <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
          @forelse($advances['customer'] ?? [] as $advance)
            @php $balance = $advance->received - $advance->returned; @endphp
            <tr>
              <td class="fw-600 fs-13">{{ $partyNames['customer'][$advance->party_id] ?? 'Customer #'.$advance->party_id }}</td>
              <td class="fs-13"></td>
              <td class="text-end fs-13">{{ currency_symbol() }} {{ number_format($advance->received) }}</td>
              <td class="text-end fs-13">{{ currency_symbol() }} {{ number_format($advance->returned) }}</td>
              <td class="text-end fw-800 text-primary">{{ currency_symbol() }} {{ number_format($balance) }}</td>
              <td>
                <div class="dropdown">
                  <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('payments.create', ['direction' => 'receive', 'party_type' => 'customer']) }}"><i class="fa-solid fa-eye me-2"></i> View</a></li>
                  </ul>
                </div>
              </td>
            </tr>
          @empty
            <x-core::table.empty colspan="6" icon="fa-solid fa-wallet" title="No customer advances found" />
          @endforelse
        </tbody>
      </x-core::table>
    </div>

    <!-- Tab 2: Supplier Advances -->
    <div class="tab-pane fade" id="tabSupplierAdvances" role="tabpanel">
      <x-core::table>
        <x-slot:filters>
          <h5 class="bp-card-title"><i class="fa-solid fa-hand-holding-dollar me-2 text-warning"></i>Supplier Advance Balances</h5>
          <span class="bp-badge bp-badge-warning">{{ currency_symbol() }} {{ number_format($totals['supplier'] ?? 0) }} Total</span>
        </x-slot:filters>

        <x-core::table.header>
          <x-core::table.column>Supplier / Company</x-core::table.column>
          <x-core::table.column>Phone</x-core::table.column>
          <x-core::table.column align="right">Total Advance Given</x-core::table.column>
          <x-core::table.column align="right">Total Returned</x-core::table.column>
          <x-core::table.column align="right">Current Balance</x-core::table.column>
          <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
          @forelse($advances['supplier'] ?? [] as $advance)
            @php $balance = $advance->paid - $advance->returned; @endphp
            <tr>
              <td class="fw-600 fs-13">{{ $partyNames['supplier'][$advance->party_id] ?? 'Supplier #'.$advance->party_id }}</td>
              <td class="fs-13"></td>
              <td class="text-end fs-13">{{ currency_symbol() }} {{ number_format($advance->paid) }}</td>
              <td class="text-end fs-13">{{ currency_symbol() }} {{ number_format($advance->returned) }}</td>
              <td class="text-end fw-800 text-primary">{{ currency_symbol() }} {{ number_format($balance) }}</td>
              <td>
                <div class="dropdown">
                  <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('payments.create', ['direction' => 'pay', 'party_type' => 'supplier']) }}"><i class="fa-solid fa-eye me-2"></i> View</a></li>
                  </ul>
                </div>
              </td>
            </tr>
          @empty
            <x-core::table.empty colspan="6" icon="fa-solid fa-hand-holding-dollar" title="No supplier advances found" />
          @endforelse
        </tbody>
      </x-core::table>
    </div>

    <!-- Tab 3: Employee Advances -->
    <div class="tab-pane fade" id="tabEmployeeAdvances" role="tabpanel">
      <x-core::table>
        <x-slot:filters>
          <h5 class="bp-card-title"><i class="fa-solid fa-id-badge me-2 text-primary"></i>Employee Advance Balances</h5>
          <span class="bp-badge bp-badge-primary">{{ currency_symbol() }} {{ number_format($totals['employee'] ?? 0) }} Total</span>
        </x-slot:filters>

        <x-core::table.header>
          <x-core::table.column>Employee Name</x-core::table.column>
          <x-core::table.column>Department</x-core::table.column>
          <x-core::table.column align="right">Total Advance Given</x-core::table.column>
          <x-core::table.column align="right">Total Deducted</x-core::table.column>
          <x-core::table.column align="right">Current Balance</x-core::table.column>
          <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
          @forelse($advances['employee'] ?? [] as $advance)
            @php $balance = $advance->paid - $advance->returned; @endphp
            <tr>
              <td class="fw-600 fs-13">{{ $partyNames['employee'][$advance->party_id] ?? 'Employee #'.$advance->party_id }}</td>
              <td class="fs-13">{{ $employeeDepartments[$advance->party_id] ?? '' }}</td>
              <td class="text-end fs-13">{{ currency_symbol() }} {{ number_format($advance->paid) }}</td>
              <td class="text-end fs-13">{{ currency_symbol() }} {{ number_format($advance->returned) }}</td>
              <td class="text-end fw-800 text-primary">{{ currency_symbol() }} {{ number_format($balance) }}</td>
              <td>
                <div class="dropdown">
                  <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><button type="button" class="dropdown-item"><i class="fa-solid fa-minus me-2"></i> Deduct</button></li>
                    <li><button type="button" class="dropdown-item"><i class="fa-solid fa-rotate-left me-2"></i> Return</button></li>
                  </ul>
                </div>
              </td>
            </tr>
          @empty
            <x-core::table.empty colspan="6" icon="fa-solid fa-id-badge" title="No employee advances found" />
          @endforelse
        </tbody>
      </x-core::table>
    </div>

  </div>

@endsection
