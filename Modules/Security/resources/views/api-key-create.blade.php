@extends('core::layouts.master')

@section('title', __("Create API Key"))
@section('page-title', __("Create API Key"))

@section('breadcrumb')
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Security</span>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <a href="{{ route('security.api-keys') }}">API Keys</a>
  <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
  <span>Create</span>
@endsection

@section('page-actions')
  <a href="{{ route('security.api-keys') }}" class="bp-btn bp-btn-primary">
    <i class="fa-solid fa-arrow-left"></i> Back to API Keys
  </a>
@endsection

@section('content')

  <form action="{{ route('security.api-keys.store') }}" method="POST">
    @csrf

    <div class="row g-4">
      <div class="col-xl-8">
        <!-- Key Information -->
        <div class="bp-card mb-4">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-key me-2"></i>Key Information</h5>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="bp-form-label">Key Name *</label>
                <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required placeholder="e.g., eCommerce Integration">
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Expiry</label>
                <select class="bp-form-select w-100 @error('expiry') is-invalid @enderror" name="expiry">
                  <option value="never" {{ old('expiry') == 'never' ? 'selected' : '' }}>Never</option>
                  <option value="30" {{ old('expiry') == '30' ? 'selected' : '' }}>30 Days</option>
                  <option value="90" {{ old('expiry') == '90' ? 'selected' : '' }}>90 Days</option>
                  <option value="180" {{ old('expiry') == '180' ? 'selected' : '' }}>180 Days</option>
                  <option value="365" {{ old('expiry') == '365' ? 'selected' : '' }}>1 Year</option>
                </select>
                @error('expiry')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12">
                <label class="bp-form-label">Description</label>
                <textarea class="bp-form-control @error('description') is-invalid @enderror" name="description" rows="2" placeholder="What will this key be used for?">{{ old('description') }}</textarea>
                @error('description')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Rate Limit (requests/minute)</label>
                <input type="number" class="bp-form-control @error('rate_limit') is-invalid @enderror" name="rate_limit" value="{{ old('rate_limit', 60) }}" min="1" max="1000">
                @error('rate_limit')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted fs-11">Maximum API requests per minute</small>
              </div>
              <div class="col-md-6">
                <label class="bp-form-label">Environment</label>
                <select class="bp-form-select w-100 @error('environment') is-invalid @enderror" name="environment">
                  <option value="live" {{ old('environment', 'live') == 'live' ? 'selected' : '' }}>Live (Production)</option>
                  <option value="test" {{ old('environment') == 'test' ? 'selected' : '' }}>Test (Sandbox)</option>
                </select>
                @error('environment')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
        </div>

        <!-- Permissions -->
        <div class="bp-card mb-4">
          <div class="bp-card-header d-flex align-items-center justify-content-between">
            <h5 class="bp-card-title"><i class="fa-solid fa-lock me-2"></i>API Permissions</h5>
            <div class="d-flex gap-2">
              <button type="button" class="bp-btn bp-btn-sm bp-btn-info" id="btn-select-all">Select All</button>
              <button type="button" class="bp-btn bp-btn-sm bp-btn-warning" id="btn-deselect-all">Deselect All</button>
            </div>
          </div>
          <div class="bp-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="bp-permission-group">
                  <h6 class="fw-700 fs-13 mb-2"><i class="fa-solid fa-boxes-stacked me-1 text-muted"></i> Products</h6>
                  <div class="d-flex flex-column gap-2 ms-3">
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="products.read"> Read Products</label>
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="products.write"> Create/Update Products</label>
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="products.delete"> Delete Products</label>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="bp-permission-group">
                  <h6 class="fw-700 fs-13 mb-2"><i class="fa-solid fa-chart-line me-1 text-muted"></i> Sales</h6>
                  <div class="d-flex flex-column gap-2 ms-3">
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="sales.read"> Read Sales</label>
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="sales.write"> Create Sales</label>
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="sales.refund"> Process Refunds</label>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="bp-permission-group">
                  <h6 class="fw-700 fs-13 mb-2"><i class="fa-solid fa-users me-1 text-muted"></i> Customers</h6>
                  <div class="d-flex flex-column gap-2 ms-3">
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="customers.read"> Read Customers</label>
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="customers.write"> Create/Update Customers</label>
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="customers.delete"> Delete Customers</label>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="bp-permission-group">
                  <h6 class="fw-700 fs-13 mb-2"><i class="fa-solid fa-warehouse me-1 text-muted"></i> Inventory</h6>
                  <div class="d-flex flex-column gap-2 ms-3">
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="inventory.read"> Read Stock</label>
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="inventory.write"> Update Stock</label>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="bp-permission-group">
                  <h6 class="fw-700 fs-13 mb-2"><i class="fa-solid fa-bangladeshi-taka-sign me-1 text-muted"></i> Finance</h6>
                  <div class="d-flex flex-column gap-2 ms-3">
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="finance.read"> Read Transactions</label>
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="finance.reports"> Generate Reports</label>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="bp-permission-group">
                  <h6 class="fw-700 fs-13 mb-2"><i class="fa-solid fa-bell me-1 text-muted"></i> Webhooks</h6>
                  <div class="d-flex flex-column gap-2 ms-3">
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="webhooks.manage"> Manage Webhooks</label>
                    <label class="bp-check-label"><input type="checkbox" class="bp-form-check api-perm" name="api_permissions[]" value="webhooks.receive"> Receive Events</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="d-flex justify-content-end gap-2 mb-4">
          <a href="{{ route('security.api-keys') }}" class="bp-btn bp-btn-danger"><i class="fa-solid fa-xmark me-1"></i>Cancel</a>
          <button type="submit" class="bp-btn bp-btn-primary"><i class="fa-solid fa-key me-1"></i> Generate API Key</button>
        </div>
      </div>

      <!-- Sidebar Info -->
      <div class="col-xl-4">
        <div class="bp-card">
          <div class="bp-card-header">
            <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2"></i>Important Notes</h5>
          </div>
          <div class="bp-card-body">
            <div class="bp-info-list">
              <div class="bp-info-item mb-3">
                <div class="fw-600 fs-13 mb-1"><i class="fa-solid fa-lock me-1"></i> Security</div>
                <p class="fs-12 text-muted mb-0">Your API key will only be shown once after creation. Store it securely and never expose it in client-side code.</p>
              </div>
              <div class="bp-info-item mb-3">
                <div class="fw-600 fs-13 mb-1"><i class="fa-solid fa-gauge-high me-1"></i> Rate Limiting</div>
                <p class="fs-12 text-muted mb-0">API calls exceeding the rate limit will receive a 429 Too Many Requests response. Implement exponential backoff in your integration.</p>
              </div>
              <div class="bp-info-item mb-3">
                <div class="fw-600 fs-13 mb-1"><i class="fa-solid fa-shield-halved me-1"></i> Permissions</div>
                <p class="fs-12 text-muted mb-0">Grant only the minimum permissions required. You can always update permissions later without regenerating the key.</p>
              </div>
              <div class="bp-info-item">
                <div class="fw-600 fs-13 mb-1"><i class="fa-solid fa-clock me-1"></i> Expiry</div>
                <p class="fs-12 text-muted mb-0">Setting an expiry date is recommended for security. Expired keys will stop working automatically.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </form>

@endsection

@push('scripts')
<script>
  'use strict';

  $(document).ready(function () {
    // Select all API permissions
    $('#btn-select-all').on('click', function () {
      $('.api-perm').prop('checked', true);
    });

    // Deselect all API permissions
    $('#btn-deselect-all').on('click', function () {
      $('.api-perm').prop('checked', false);
    });
  });
</script>
@endpush
