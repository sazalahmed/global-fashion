@extends('core::layouts.master')

@section('title', __("SMS Campaigns"))
@section('page-title', __("SMS Campaigns"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Marketing</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>SMS Campaigns</span>
@endsection

@section('page-actions')
  @bpCan('marketing.create')
    <a href="{{ route('marketing.sms-campaigns.create') }}" class="bp-btn bp-btn-primary">
      <i class="fa-solid fa-plus"></i> New Campaign
    </a>
  @endbpCan
@endsection

@section('content')

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-comment-sms"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Campaigns</div>
          <div class="bp-stat-value">{{ number_format($campaigns->total()) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-success"><i class="fa-solid fa-paper-plane"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Messages Sent</div>
          <div class="bp-stat-value">{{ number_format($campaigns->sum('sent_count')) }}</div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-check-double"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Delivery Rate</div>
          <div class="bp-stat-value">
            @php
              $totalSent = $campaigns->sum('sent_count');
              $totalFailed = $campaigns->sum('failed_count');
              $rate = $totalSent > 0 ? round((($totalSent - $totalFailed) / $totalSent) * 100, 1) : 0;
            @endphp
            {{ $rate }}%
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6">
      <div class="bp-stat-card">
        <div class="bp-stat-icon icon-info"><i class="fa-solid fa-coins"></i></div>
        <div class="bp-stat-content">
          <div class="bp-stat-label">Total Cost</div>
          <div class="bp-stat-value">{{ currency_symbol() }} {{ number_format($campaigns->sum('total_cost'), 0, '.', ',') }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Campaigns Table -->
  <x-core::table>
    <x-slot:filters>
      <x-core::table.filter-bar searchPlaceholder="Search campaigns...">
        <select class="bp-form-select" name="status" onchange="this.form.submit()">
          <option value="">All Status</option>
          <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
          <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
          <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
          <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
        </select>
      </x-core::table.filter-bar>
    </x-slot:filters>

    <x-core::table.header>
      <x-core::table.column :sortable="true" field="name">Campaign Name</x-core::table.column>
      <x-core::table.column :sortable="true" field="recipients">Recipients</x-core::table.column>
      <x-core::table.column>Sent</x-core::table.column>
      <x-core::table.column>Failed</x-core::table.column>
      <x-core::table.column :sortable="true" field="date">Date</x-core::table.column>
      <x-core::table.column>Status</x-core::table.column>
      <x-core::table.column>Actions</x-core::table.column>
    </x-core::table.header>

    <tbody>
      @forelse($campaigns as $campaign)
        <tr>
          <td>
            <div>
              <span class="fw-700 fs-13">{{ $campaign->name }}</span>
              <div class="fs-11 text-muted">BulkSMSBD</div>
            </div>
          </td>
          <td class="fw-600 fs-13">{{ number_format($campaign->total_recipients) }}</td>
          <td class="fw-600 fs-13">{{ number_format($campaign->sent_count) }}</td>
          <td class="fw-600 fs-13 bp-text-danger">{{ number_format($campaign->failed_count) }}</td>
          <td class="fs-12 text-muted">
            @if($campaign->scheduled_at)
              {{ $campaign->scheduled_at->format('d M Y, h:i A') }}
            @elseif($campaign->sent_at)
              {{ $campaign->sent_at->format('d M Y') }}
            @else
              {{ $campaign->created_at->format('d M Y') }}
            @endif
          </td>
          <td>
            @switch($campaign->status)
              @case('completed')
                <span class="bp-badge bp-badge-success">Completed</span>
                @break
              @case('scheduled')
                <span class="bp-badge bp-badge-warning">Scheduled</span>
                @break
              @case('sending')
                <span class="bp-badge bp-badge-info">Sending</span>
                @break
              @case('failed')
                <span class="bp-badge bp-badge-danger">Failed</span>
                @break
              @default
                <span class="bp-badge bp-badge-dark">Draft</span>
            @endswitch
          </td>
          <td>
            @bpCanAny('marketing.view', 'marketing.edit', 'marketing.create', 'marketing.delete')
              <div class="dropdown">
                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                <ul class="dropdown-menu dropdown-menu-end">
                  @if($campaign->status === 'draft')
                    @bpCan('marketing.edit')
                      <li><a class="dropdown-item" href="{{ route('marketing.sms-campaigns.edit', $campaign) }}"><i class="fa-solid fa-pen"></i> Edit</a></li>
                    @endbpCan
                    @bpCan('marketing.edit')
                      <li>
                        <form action="{{ route('marketing.sms-campaigns.send', $campaign) }}" method="POST">
                          @csrf
                          <button type="submit" class="dropdown-item js-confirm" data-confirm="Send this campaign now? This will use SMS credits.">
                            <i class="fa-solid fa-paper-plane"></i> Send Now
                          </button>
                        </form>
                      </li>
                    @endbpCan
                  @else
                    @bpCan('marketing.view')
                    <li><a class="dropdown-item" href="{{ route('marketing.sms-campaigns.show', $campaign) }}"><i class="fa-solid fa-eye"></i> View Report</a></li>
                    @endbpCan
                    @bpCan('marketing.create')
                      <li>
                        <form action="{{ route('marketing.sms-campaigns.duplicate', $campaign) }}" method="POST">
                          @csrf
                          <button type="submit" class="dropdown-item"><i class="fa-solid fa-copy"></i> Duplicate</button>
                        </form>
                      </li>
                    @endbpCan
                  @endif
                  @bpCan('marketing.delete')
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <form action="{{ route('marketing.sms-campaigns.destroy', $campaign) }}" method="POST" class="delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger delete-confirm" data-name="{{ $campaign->name }}">
                          <i class="fa-solid fa-trash"></i> Delete
                        </button>
                      </form>
                    </li>
                  @endbpCan
                </ul>
              </div>
            @endbpCanAny
          </td>
        </tr>
      @empty
        <x-core::table.empty colspan="7" icon="fa-comment-sms" title="No SMS campaigns found.">
          @bpCan('marketing.create')
            <a href="{{ route('marketing.sms-campaigns.create') }}" class="bp-btn bp-btn-sm bp-btn-primary"><i class="fa-solid fa-plus me-1"></i> Create Campaign</a>
          @endbpCan
        </x-core::table.empty>
      @endforelse
    </tbody>

    <x-slot:pagination>
      <x-core::table.pagination :paginator="$campaigns" itemLabel="campaigns" />
    </x-slot:pagination>
  </x-core::table>

@endsection

@push('scripts')
<script>
  'use strict';
  // Generic confirm-before-submit for non-destructive actions (e.g. Send Now).
  $(document).on('click', '.js-confirm', function (e) {
    if (!window.confirm($(this).data('confirm') || 'Are you sure?')) {
      e.preventDefault();
    }
  });
</script>
@endpush
