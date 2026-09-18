@extends('core::layouts.master')

@section('title', __('Manage Banks'))
@section('page-title', __('Manage Banks'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('payment-accounts.index') }}">Payment Accounts</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Banks</span>
@endsection

@section('page-actions')
    <a href="{{ route('payment-accounts.index') }}" class="bp-btn bp-btn-primary"><i class="fa-solid fa-arrow-left me-1"></i>
        Back</a>
@endsection

@section('content')

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-plus me-2"></i>Add Bank</h5>
                </div>
                <div class="bp-card-body">
                    <form action="{{ route('payment-accounts.banks.store') }}" method="POST">
                        @csrf
                        <div class="d-flex gap-2">
                            <input type="text" class="bp-form-control @error('name') is-invalid @enderror" name="name"
                                value="{{ old('name') }}" placeholder="Bank name (e.g., DBBL, BRAC Bank)" required>
                            <button type="submit" class="bp-btn bp-btn-primary"><i class="fa-solid fa-plus"></i></button>
                        </div>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-building-columns me-2"></i>Banks ({{ $banks->count() }})
                    </h5>
                </div>
                <div class="bp-card-body p-0">
                    <div class="bp-table-wrapper">
                        <table class="bp-table">
                            <thead>
                                <tr>
                                    <th>Bank Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($banks as $bank)
                                    <tr>
                                        <td class="fw-700">{{ $bank->name }}</td>
                                        <td>
                                            @bpCan('payments.delete')
                                            <div class="dropdown">
                                                <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <form action="{{ route('payment-accounts.banks.destroy', $bank) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger"
                                                                onclick="return confirm('Delete this bank?')"><i class="fa-solid fa-trash me-2"></i> Delete</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                            @endbpCan
                                        </td>
                                    </tr>
                                @empty
                                    <x-core::table.empty colspan="2" icon="fa-solid fa-building-columns" title="No banks added yet" />
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
