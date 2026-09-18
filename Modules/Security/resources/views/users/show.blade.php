@extends('core::layouts.master')

@section('title', $user->name ?? 'User Details')
@section('page-title', $user->name ?? 'User Details')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Security</span>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('security.users.index') }}">Users</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $user->name ?? 'User' }}</span>
@endsection

@section('page-actions')
    <a href="{{ route('security.users.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i>
        Back</a>
    @if ($user->id === auth()->id())
        <a href="{{ route('security.profile') }}" class="bp-btn bp-btn-info"><i class="fa-solid fa-user-pen me-1"></i> Edit
            Profile</a>
        <a href="{{ route('security.change-password') }}" class="bp-btn bp-btn-warning"><i class="fa-solid fa-key me-1"></i>
            Change Password</a>
    @elseif(!$user->isSuperAdmin())
        @bpCan('users.edit')
        <a href="{{ route('security.users.edit', $user->id) }}" class="bp-btn bp-btn-info"><i
                class="fa-solid fa-pen me-1"></i> Edit User</a>
        @endbpCan
    @endif
@endsection

@section('content')

    <div class="row g-4">

        <!-- Left Column: Profile Card -->
        <div class="col-xl-4">

            <div class="bp-card mb-4">
                <div class="bp-card-body text-center bp-user-profile-card">
                    <div class="bp-user-avatar-lg mb-3">
                        @if ($user->image)
                            <img src="{{ upload_url($user->image) }}" alt="{{ $user->name }}">
                        @else
                            <span>{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                        @endif
                    </div>
                    <h5 class="fw-800 mb-1">{{ $user->name }}</h5>
                    <div class="fs-13 text-muted mb-2">{{ $user->email }}</div>
                    @foreach ($user->roles as $role)
                        @if ($role->name === 'Super Admin')
                            <span class="bp-badge bp-badge-danger">{{ $role->name }}</span>
                        @elseif($role->name === 'Manager')
                            <span class="bp-badge bp-badge-success">{{ $role->name }}</span>
                        @else
                            <span class="bp-badge bp-badge-primary">{{ $role->name }}</span>
                        @endif
                    @endforeach
                    <div class="mt-3">
                        @if ($user->status === 'active')
                            <span class="bp-badge bp-badge-success"><i
                                    class="fa-solid fa-circle me-1 fs-8"></i>Active</span>
                        @else
                            <span class="bp-badge bp-badge-danger"><i
                                    class="fa-solid fa-circle me-1 fs-8"></i>Inactive</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Info -->
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Details</h5>
                </div>
                <div class="bp-card-body">
                    <div class="bp-info-row">
                        <div class="bp-info-label">Phone</div>
                        <div class="bp-info-value">{{ $user->phone ?? '--' }}</div>
                    </div>
                    <div class="bp-info-row d-none">
                        <div class="bp-info-label">Branch</div>
                        <div class="bp-info-value">{{ $user->branch->name ?? 'All Branches' }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label">Last Login</div>
                        <div class="bp-info-value">
                            {{ $user->last_login_at ? $user->last_login_at->format('d M Y H:i') : 'Never' }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label">Created</div>
                        <div class="bp-info-value">{{ $user->created_at->format('d M Y') }}</div>
                    </div>
                    <div class="bp-info-row">
                        <div class="bp-info-label">Updated</div>
                        <div class="bp-info-value">{{ $user->updated_at->format('d M Y') }}</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Permissions -->
        <div class="col-xl-8">

            <div class="bp-card mb-4">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-key me-2 text-warning"></i>Permissions (via Role)</h5>
                    @if ($user->roles->first())
                        <span class="bp-badge bp-badge-primary">{{ $user->roles->first()->name }}</span>
                    @endif
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table bp-permissions-table">
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th class="text-center">View</th>
                                    <th class="text-center">Create</th>
                                    <th class="text-center">Edit</th>
                                    <th class="text-center">Delete</th>
                                    <th class="text-center">Export</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
                                    $groups = \App\Traits\PermissionsTrait::permissionGroups();
                                @endphp
                                @foreach ($groups as $group => $permissions)
                                    <tr>
                                        <td><span
                                                class="fw-600 fs-13 text-capitalize">{{ str_replace('_', ' ', $group) }}</span>
                                        </td>
                                        @php
                                            $actions = ['view', 'create', 'edit', 'delete', 'export'];
                                        @endphp
                                        @foreach ($actions as $action)
                                            <td class="text-center">
                                                @if (in_array($group . '.' . $action, $permissions))
                                                    @if (in_array($group . '.' . $action, $userPermissions))
                                                        <i class="fa-solid fa-circle-check text-success"></i>
                                                    @else
                                                        <i class="fa-solid fa-circle-xmark text-muted bp-opacity-30"></i>
                                                    @endif
                                                @else
                                                    <span class="text-muted">--</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>

@endsection
