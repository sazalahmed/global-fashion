@extends('ecommerce::storefront.layouts.master')

@section('title', 'Combo Packages')
@section('breadcrumb_title', 'Combo Packages')

@section('breadcrumb')
    <li><a href="{{ route('storefront.combos.index') }}">Combo Packages</a></li>
@endsection

@section('content')
    <!--============================ COMBO PACKAGES START =============================-->
    <h1 class="bp-visually-hidden">Combo Packages</h1>
    <section class="flash_sell_page pt_60 pb_70">
        <div class="container">
            @if ($combos->count() > 0)
                <div class="row">
                    @foreach ($combos as $c)
                        <div class="col-xl-1-5 col-6 col-md-4 col-xl-3 wow fadeInUp">
                            @include('ecommerce::storefront.partials.combo-card', [
                                'combo' => $c,
                                'service' => $service,
                            ])
                        </div>
                    @endforeach
                </div>

                @if ($combos->hasPages())
                    <div class="row">
                        <div class="pagination_area">
                            {{ $combos->links('ecommerce::storefront.partials.pagination') }}
                        </div>
                    </div>
                @endif
            @else
                <div class="row">
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-box-open fa-5x text-muted mb-4 d-block"></i>
                        <h3>No combo packages available right now</h3>
                        <p class="text-muted">Please check back soon.</p>
                        <a href="{{ route('storefront.shop.index') }}" class="common_btn mt-3">
                            Browse Products <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </section>
    <!--============================ COMBO PACKAGES END =============================-->
@endsection
