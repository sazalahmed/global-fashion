@extends('core::layouts.master')

@section('title', __("Email Configuration"))
@section('page-title', __("Email Configuration"))

@section('breadcrumb')
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('settings.index') }}">Settings</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span>Email</span>
@endsection

@section('content')

<div class="row g-4">
  <!-- SMTP Settings -->
  <div class="col-xl-7">
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-envelope me-2"></i>SMTP Settings</h5>
      </div>
      <form action="{{ route('settings.email.save') }}" method="POST">
        @csrf
        <div class="bp-card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="bp-form-label">Mail Driver</label>
              <select class="bp-form-select w-100" name="mail_driver">
                <option value="smtp" {{ ($emailSettings['mail_driver'] ?? 'smtp') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                <option value="sendmail" {{ ($emailSettings['mail_driver'] ?? '') === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                <option value="log" {{ ($emailSettings['mail_driver'] ?? '') === 'log' ? 'selected' : '' }}>Log (Testing)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="bp-form-label">Encryption</label>
              <select class="bp-form-select w-100" name="mail_encryption">
                <option value="tls" {{ ($emailSettings['mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                <option value="ssl" {{ ($emailSettings['mail_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                <option value="" {{ empty($emailSettings['mail_encryption']) ? 'selected' : '' }}>None</option>
              </select>
            </div>
            <div class="col-md-8">
              <label class="bp-form-label">SMTP Host</label>
              <input type="text" class="bp-form-control" name="mail_host" value="{{ $emailSettings['mail_host'] ?? '' }}" placeholder="smtp.gmail.com">
            </div>
            <div class="col-md-4">
              <label class="bp-form-label">Port</label>
              <input type="number" class="bp-form-control" name="mail_port" value="{{ $emailSettings['mail_port'] ?? 587 }}">
            </div>
            <div class="col-md-6">
              <label class="bp-form-label">Username</label>
              <input type="text" class="bp-form-control" name="mail_username" value="{{ $emailSettings['mail_username'] ?? '' }}">
            </div>
            <div class="col-md-6">
              <label class="bp-form-label">Password</label>
              <input type="password" class="bp-form-control" name="mail_password" value="{{ $emailSettings['mail_password'] ?? '' }}">
            </div>
            <div class="col-md-6">
              <label class="bp-form-label">From Address</label>
              <input type="email" class="bp-form-control" name="mail_from_address" value="{{ $emailSettings['mail_from_address'] ?? '' }}" placeholder="noreply@yourstore.com">
            </div>
            <div class="col-md-6">
              <label class="bp-form-label">From Name</label>
              <input type="text" class="bp-form-control" name="mail_from_name" value="{{ $emailSettings['mail_from_name'] ?? $companyName }}">
            </div>
          </div>
        </div>
        <div class="bp-card-footer d-flex justify-content-between">
          <div>
            <div class="input-group input-group-sm">
              <input type="email" class="bp-form-control" name="test_email" form="emailTestForm" placeholder="test@email.com" required>
              <button type="submit" form="emailTestForm" class="bp-btn bp-btn-sm bp-btn-outline"><i class="fa-solid fa-paper-plane me-1"></i>Send Test</button>
            </div>
          </div>
          <button type="submit" class="bp-btn bp-btn-success"><i class="fa-solid fa-save me-1"></i> Save Settings</button>
        </div>
      </form>

      <form id="emailTestForm" action="{{ route('settings.email.test') }}" method="POST" class="d-none">
        @csrf
      </form>
    </div>
  </div>

  <!-- Email Templates -->
  <div class="col-xl-5">
    <div class="bp-card">
      <div class="bp-card-header">
        <h5 class="bp-card-title"><i class="fa-solid fa-file-lines me-2"></i>Email Templates</h5>
      </div>
      <div class="bp-card-body p-0">
        <div class="bp-table-wrapper">
          <table class="bp-table">
            <thead>
              <tr>
                <th>Template</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($templates as $tpl)
                <tr>
                  <td>
                    <div class="fw-600">{{ $tpl->name }}</div>
                    <div class="fs-11 text-muted">{{ $tpl->slug }}</div>
                  </td>
                  <td><span class="bp-badge {{ $tpl->is_active ? 'bp-badge-success' : 'bp-badge-danger' }}">{{ $tpl->is_active ? 'Active' : 'Off' }}</span></td>
                  <td>
                    @bpCan('settings.edit')
                    <div class="dropdown">
                      <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                      <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('settings.email.templates.edit', $tpl) }}"><i class="fa-solid fa-pen me-2"></i> Edit</a></li>
                      </ul>
                    </div>
                    @endbpCan
                  </td>
                </tr>
              @empty
                <x-core::table.empty :colspan="3" icon="fa-file-lines" title="No templates yet." description="Run the seeder." />
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
