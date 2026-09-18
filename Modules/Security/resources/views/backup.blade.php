@extends('core::layouts.master')

@section('title', __('Backup & Restore'))
@section('page-title', __('Backup & Restore'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Security</span>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Backup &amp; Restore</span>
@endsection

@section('page-actions')
    @bpCan('users.create')
        <form action="{{ route('security.backup.create') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="bp-btn bp-btn-primary">
                <i class="fa-solid fa-plus"></i> Create Backup
            </button>
        </form>
    @endbpCan
@endsection

@section('content')

    <!-- Storage & Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-primary"><i class="fa-solid fa-database"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Total Backups</div>
                    <div class="bp-stat-value">{{ $stats['total_backups'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-success"><i class="fa-solid fa-circle-check"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Last Backup</div>
                    <div class="bp-stat-value fs-13">{{ $stats['last_backup'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="bp-stat-card">
                <div class="bp-stat-icon icon-accent"><i class="fa-solid fa-hard-drive"></i></div>
                <div class="bp-stat-content">
                    <div class="bp-stat-label">Storage Used</div>
                    <div class="bp-stat-value">{{ $stats['storage_used'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup History -->
    <x-core::table>
        <x-core::table.header>
            <x-core::table.column>Backup Date</x-core::table.column>
            <x-core::table.column>Size</x-core::table.column>
            <x-core::table.column>Type</x-core::table.column>
            <x-core::table.column>Status</x-core::table.column>
            <x-core::table.column>Actions</x-core::table.column>
        </x-core::table.header>

        <tbody>
            @forelse($backups as $backup)
                <tr>
                    <td class="fw-600 fs-13">{{ $backup['created_at'] }}</td>
                    <td class="fs-13">{{ number_format($backup['size'] / 1048576, 1) }} MB</td>
                    <td>
                        @if ($backup['type'] === 'Full')
                            <span class="bp-badge bp-badge-primary">Full</span>
                        @else
                            <span class="bp-badge bp-badge-info">Database</span>
                        @endif
                    </td>
                    <td><span class="bp-badge bp-badge-success">{{ ucfirst($backup['status']) }}</span></td>
                    <td>
                        <div class="dropdown">
                            <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                    class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <form action="{{ route('security.backup.restore') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="backup_id" value="{{ $backup['id'] }}">
                                        <button type="submit" class="dropdown-item restore-confirm"><i
                                                class="fa-solid fa-rotate-left me-2"></i> Restore</button>
                                    </form>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <form action="{{ route('security.backup.delete') }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="backup_id" value="{{ $backup['id'] }}">
                                        <button type="submit" class="dropdown-item text-danger backup-delete-confirm"><i
                                                class="fa-solid fa-trash me-2"></i> Delete</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <x-core::table.empty :colspan="5" icon="fa-database" title="No backups found."
                    description="Create your first backup to get started." />
            @endforelse
        </tbody>
    </x-core::table>

@endsection

@push('scripts')
    <script>
        'use strict';

        $(document).ready(function() {
            // Restore confirmation
            $('.restore-confirm').on('click', function(e) {
                if (!confirm(
                        'Are you sure you want to restore this backup? This will overwrite current data.'
                        )) {
                    e.preventDefault();
                }
            });

            // Delete confirmation
            $('.backup-delete-confirm').on('click', function(e) {
                if (!confirm('Are you sure you want to delete this backup? This cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endpush
