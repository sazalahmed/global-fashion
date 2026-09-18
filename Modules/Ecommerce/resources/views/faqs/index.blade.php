@extends('core::layouts.master')

@section('title', __('FAQs — Website'))
@section('page-title', __('FAQs'))

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>FAQs</span>
@endsection

@section('page-actions')
    <a href="{{ route('ecommerce.faqs.create') }}" class="bp-btn bp-btn-primary">
        <i class="fa-solid fa-plus"></i> Add FAQ
    </a>
@endsection

@section('content')

    <div class="bp-card">
        <div class="bp-card-body p-0">
            <div class="bp-table-wrapper">
                <table class="bp-table">
                    <thead>
                        <tr>
                            <th class="bp-reorder-col" title="{{ __('Drag to reorder') }}">&nbsp;</th>
                            <th>{{ __('Question') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bp-reorderable" data-reorder-url="{{ route('ecommerce.faqs.reorder') }}">
                        @forelse($faqs as $faq)
                            <tr data-id="{{ $faq->id }}">
                                <td class="bp-drag-handle" title="{{ __('Drag to reorder') }}"><i
                                        class="fa-solid fa-grip-vertical"></i></td>
                                <td>
                                    <div class="fw-700 fs-13">{{ $faq->question }}</div>
                                    <div class="fs-11 text-muted">{{ Str::limit(strip_tags($faq->answer), 80) }}</div>
                                </td>
                                <td>
                                    <x-core::status-toggle :url="route('ecommerce.faqs.toggle-status', $faq)" :active="$faq->is_active" />
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="bp-btn bp-btn-sm bp-btn-outline" data-bs-toggle="dropdown"><i
                                                class="fa-solid fa-ellipsis-vertical me-1"></i>Action</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('ecommerce.faqs.edit', $faq) }}"><i
                                                        class="fa-solid fa-pen me-2"></i>Edit</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <form action="{{ route('ecommerce.faqs.destroy', $faq) }}" method="POST"
                                                    class="delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                        data-name="{{ $faq->question }}">
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
                                <td colspan="4" class="text-center text-muted py-4">
                                    {{ __('No FAQs yet. Create your first FAQ.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
