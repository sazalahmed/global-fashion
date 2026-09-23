@extends('core::layouts.master')

@section('title', __('Testimonials — Website'))
@section('page-title', __('Testimonials'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>Testimonials</span>
@endsection

@section('page-actions')
    <a href="{{ route('ecommerce.testimonials.create') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-plus"></i> Add Testimonial
    </a>
@endsection

@section('content')

    <div class="bp-card">
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">{{ __('Image') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Rating') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($testimonials as $testimonial)
                            <tr>
                                <td>
                                    @if($testimonial->image)
                                        <img src="{{ upload_url($testimonial->image) }}" alt="image" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    @else
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #eee; display: flex; align-items: center; justify-content: center; color: #888;">
                                            <i class="fa-solid fa-user"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-700 fs-13">{{ $testimonial->name }}</div>
                                    @if($testimonial->designation)
                                        <div class="fs-11 text-muted">{{ $testimonial->designation }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fs-12 text-warning">
                                        @for($i=1; $i<=5; $i++)
                                            @if($i <= $testimonial->rating)
                                                <i class="fa-solid fa-star"></i>
                                            @else
                                                <i class="fa-regular fa-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                </td>
                                <td>
                                    <x-core::status-toggle :url="route('ecommerce.testimonials.toggle-status', $testimonial)" :active="$testimonial->is_active" />
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                                class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('ecommerce.testimonials.edit', $testimonial) }}"><i
                                                        class="fa-solid fa-pen me-2"></i>Edit</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <form action="{{ route('ecommerce.testimonials.destroy', $testimonial) }}" method="POST"
                                                    class="delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                        data-name="{{ $testimonial->name }}">
                                                        <i class="fa-solid fa-trash me-2"></i> Delete
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    {{ __('No testimonials yet. Add your first testimonial.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
