@extends('core::layouts.master')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Notifications</span>
@endsection

@section('page-actions')
<form action="{{ route('notifications.markAllRead') }}" method="POST" class="d-inline">
  @csrf
  <button type="submit" class="bp-btn bp-btn-outline"><i class="fa-solid fa-check-double me-1"></i> Mark All Read</button>
</form>
@endsection

@section('content')

<div class="bp-card">
  <div class="bp-card-body p-0">
    @forelse($notifications as $notification)
      @php
        $data = $notification->data;
        $isUnread = $notification->read_at === null;
      @endphp
      <div class="d-flex align-items-start gap-3 p-3 border-bottom {{ $isUnread ? 'bg-light' : '' }}">
        <div class="bp-notif-icon icon-{{ $data['color'] ?? 'primary' }}">
          <i class="fa-solid {{ $data['icon'] ?? 'fa-bell' }}"></i>
        </div>
        <div class="flex-grow-1">
          <div class="d-flex align-items-center justify-content-between">
            <div class="fw-700 fs-14">{{ $data['title'] ?? 'Notification' }}</div>
            @if($isUnread)
              <span class="bp-badge bp-badge-primary fs-10">New</span>
            @endif
          </div>
          <div class="fs-13 text-muted mt-1">{{ $data['message'] ?? '' }}</div>
          <div class="fs-12 text-muted mt-2">
            <i class="fa-solid fa-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
            @if(!empty($data['url']))
              <a href="{{ $data['url'] }}" class="ms-3"><i class="fa-solid fa-arrow-right me-1"></i>View</a>
            @endif
            @if($isUnread)
              <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="d-inline ms-3">
                @csrf
                <button type="submit" class="bp-btn bp-btn-sm bp-btn-outline py-0 px-2 fs-11">Mark read</button>
              </form>
            @endif
          </div>
        </div>
      </div>
    @empty
      <div class="text-center py-5 text-muted">
        <i class="fa-solid fa-bell-slash fa-3x mb-3 d-block" style="opacity:0.3"></i>
        <div class="fs-14 fw-600">No notifications</div>
        <div class="fs-13 mt-1">You're all caught up!</div>
      </div>
    @endforelse
  </div>

  <x-core::table.pagination :paginator="$notifications" itemLabel="notifications" />
</div>

@endsection
