@extends('core::layouts.master')

@section('title', __("Loyalty Program"))
@section('page-title', __("Loyalty Program"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Marketing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Loyalty Program</span>
@endsection

@section('page-actions')
  <div class="d-flex align-items-center gap-3">
    <div class="d-flex align-items-center gap-2">
      <span class="fw-600 fs-13">Program Status:</span>
      <div class="form-check form-switch mb-0">
        <input class="form-check-input" type="checkbox" id="loyalty-toggle" checked>
        <label class="form-check-label fw-700 fs-13 bp-text-success" for="loyalty-toggle" id="loyalty-status-label">Active</label>
      </div>
    </div>
  </div>
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-users"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Active Members</div>
          <div class="bp-stat-value">{{ number_format($loyaltyStats['active_customers']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-star"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Points Earned</div>
          <div class="bp-stat-value">{{ number_format($loyaltyStats['total_points_earned']) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-gift"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Points Redeemed</div>
          <div class="bp-stat-value">{{ number_format(abs($loyaltyStats['total_points_redeemed'])) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-info"><i class="fa-solid fa-scale-balanced"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Net Points Outstanding</div>
          <div class="bp-stat-value">{{ number_format($loyaltyStats['total_points_earned'] + $loyaltyStats['total_points_redeemed']) }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Program Settings -->
    <div class="col-xl-4">
      <div class="bp-card mb-4">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-gears me-2"></i>Program Settings</h5>
        </div>
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="bp-form-label">Points per {{ currency_symbol() }} Spent</label>
              <input type="number" class="bp-form-control" name="points_per_bdt" value="1" min="1" max="100" disabled>
              <small class="text-muted fs-11">Earn 1 point for every {{ currency_symbol() }} 1 spent</small>
            </div>
            <div class="col-12">
              <label class="bp-form-label">Redemption Rate ({{ currency_symbol() }} per Point)</label>
              <input type="number" class="bp-form-control" name="redemption_rate" value="0.10" min="0.01" max="10" step="0.01" disabled>
              <small class="text-muted fs-11">1 point = {{ currency_symbol() }} 0.10 discount</small>
            </div>
            <div class="col-12">
              <label class="bp-form-label">Minimum Points to Redeem</label>
              <input type="number" class="bp-form-control" name="min_redeem" value="500" min="1" disabled>
              <small class="text-muted fs-11">Minimum 500 points required to redeem</small>
            </div>
            <div class="col-12">
              <label class="bp-form-label">Points Expiry (Days)</label>
              <input type="number" class="bp-form-control" name="points_expiry" value="365" min="0" disabled>
              <small class="text-muted fs-11">0 = Never expire</small>
            </div>
            <div class="col-12">
              <label class="bp-form-label">Welcome Bonus Points</label>
              <input type="number" class="bp-form-control" name="welcome_bonus" value="100" min="0" disabled>
              <small class="text-muted fs-11">Bonus points on signup</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Loyalty Tiers -->
      <div class="bp-card">
        <div class="bp-card-header">
          <h5 class="bp-card-title"><i class="fa-solid fa-trophy me-2"></i>Loyalty Tiers</h5>
        </div>
        <div class="bp-card-body p-0">
          <div class="bp-table-wrapper">
            <table class="bp-table">
              <thead>
                <tr>
                  <th>Tier</th>
                  <th>Points</th>
                  <th>Discount</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <span class="bp-tier-icon bp-tier-bronze"><i class="fa-solid fa-medal"></i></span>
                      <span class="fw-700 fs-13">Bronze</span>
                    </div>
                  </td>
                  <td class="fs-13">0 - 999</td>
                  <td class="fs-13 fw-600">2%</td>
                </tr>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <span class="bp-tier-icon bp-tier-silver"><i class="fa-solid fa-medal"></i></span>
                      <span class="fw-700 fs-13">Silver</span>
                    </div>
                  </td>
                  <td class="fs-13">1,000 - 4,999</td>
                  <td class="fs-13 fw-600">5%</td>
                </tr>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <span class="bp-tier-icon bp-tier-gold"><i class="fa-solid fa-medal"></i></span>
                      <span class="fw-700 fs-13">Gold</span>
                    </div>
                  </td>
                  <td class="fs-13">5,000 - 14,999</td>
                  <td class="fs-13 fw-600">10%</td>
                </tr>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <span class="bp-tier-icon bp-tier-platinum"><i class="fa-solid fa-gem"></i></span>
                      <span class="fw-700 fs-13">Platinum</span>
                    </div>
                  </td>
                  <td class="fs-13">15,000+</td>
                  <td class="fs-13 fw-600">15%</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Transactions -->
    <div class="col-xl-8">
      <x-core::table>
        <x-slot:filters>
          <x-core::table.filter-bar searchPlaceholder="Search transactions...">
            <select class="bp-form-select" name="type" onchange="this.form.submit()">
              <option value="">All Types</option>
              <option value="earn" {{ request('type') === 'earn' ? 'selected' : '' }}>Earned</option>
              <option value="redeem" {{ request('type') === 'redeem' ? 'selected' : '' }}>Redeemed</option>
              <option value="adjust" {{ request('type') === 'adjust' ? 'selected' : '' }}>Adjusted</option>
              <option value="expire" {{ request('type') === 'expire' ? 'selected' : '' }}>Expired</option>
            </select>
          </x-core::table.filter-bar>
        </x-slot:filters>

        <x-core::table.header>
          <x-core::table.column :sortable="true" field="date">Date</x-core::table.column>
          <x-core::table.column>Customer</x-core::table.column>
          <x-core::table.column>Type</x-core::table.column>
          <x-core::table.column :sortable="true" field="points">Points</x-core::table.column>
          <x-core::table.column>Description</x-core::table.column>
        </x-core::table.header>

        <tbody>
          @forelse($transactions as $transaction)
            <tr>
              <td class="fs-12 text-muted">{{ $transaction->created_at->format('d M Y, h:i A') }}</td>
              <td>
                @if($transaction->customer)
                  <span class="fw-700 fs-13">{{ $transaction->customer->name }}</span>
                @else
                  <span class="text-muted fs-13">--</span>
                @endif
              </td>
              <td>
                @switch($transaction->type)
                  @case('earn')
                    <span class="bp-badge bp-badge-success">Earned</span>
                    @break
                  @case('redeem')
                    <span class="bp-badge bp-badge-primary">Redeemed</span>
                    @break
                  @case('adjust')
                    <span class="bp-badge bp-badge-warning">Adjusted</span>
                    @break
                  @case('expire')
                    <span class="bp-badge bp-badge-danger">Expired</span>
                    @break
                @endswitch
              </td>
              <td class="fw-700 fs-13 {{ $transaction->points >= 0 ? 'bp-text-success' : 'bp-text-danger' }}">
                {{ $transaction->points >= 0 ? '+' : '' }}{{ number_format($transaction->points) }}
              </td>
              <td class="fs-12 text-muted">{{ $transaction->description ?? '' }}</td>
            </tr>
          @empty
            <x-core::table.empty colspan="5" icon="fa-gift" title="No loyalty transactions found." />
          @endforelse
        </tbody>

        <x-slot:pagination>
          <x-core::table.pagination :paginator="$transactions" itemLabel="transactions" />
        </x-slot:pagination>
      </x-core::table>
    </div>
  </div>

@endsection

@push('scripts')
<script>
  'use strict';

  $(document).ready(function () {
    // Loyalty program toggle
    $('#loyalty-toggle').on('change', function () {
      var isEnabled = $(this).prop('checked');
      var $label = $('#loyalty-status-label');
      if (isEnabled) {
        $label.text('Active').removeClass('bp-text-danger').addClass('bp-text-success');
      } else {
        $label.text('Disabled').removeClass('bp-text-success').addClass('bp-text-danger');
      }
    });
  });
</script>
@endpush
