$(function () {

    "use strict";

    //======MENU FIX JS=======
    $(window).scroll(function () {
        if ($(this).scrollTop() > 1) {
            if ($('.main_menu').offset() != undefined) {
                if (!$('.main_menu').hasClass("menu_fix")) {
                    $('.main_menu').addClass("menu_fix");
                }
            }
        }
        else {
            if ($('.main_menu').offset() != undefined) {
                $('.main_menu').removeClass("menu_fix");
            }
        }
    });

    //=====CATEGORY MENU======
    $('.menu_category_bar').on('click', function () {
        $('.menu_category_area').toggleClass('show_category');
    });

    $('.menu_category_bar').on('click', function () {
        $('.menu_category_bar').toggleClass('ratate_arrow');
    });

    //===venobox js (guarded — venobox no longer loaded on the storefront; unused)===
    if ($.fn.venobox) { $('.venobox').venobox(); }

    //======countUp js=========
    $('.counter').countUp();

    //=======SELECT2========
    $(document).ready(function () {
        $('.select_2').select2();

        // Storefront search modal category dropdown (Bug_19): searchable Select2.
        // dropdownParent must be the modal so the search box is focusable inside it.
        $('.bp-search-select').each(function () {
            var $sel = $(this);
            var $modal = $sel.closest('.modal');
            $sel.select2({
                width: '100%',
                dropdownParent: $modal.length ? $modal : $(document.body)
            });
        });
    });

    //======NICE SELECT=======
    $('.select_js').niceSelect();

    //=====WOW JS======
    new WOW().init();

    //=====BANNER SLIDER=====
    $('.banner_slider').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 3000,
        dots: true,
        arrows: false,
        fade: true,

        responsive: [
            {
                breakpoint: 576,
                settings: {
                    dots: false
                }
            }
        ]
    });

    //=====CATEGORY SLIDER=====
    $('.category_slider').slick({
        slidesToShow: 6,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 2500,
        dots: false,
        arrows: true,
        nextArrow: '<i class="fas fa-arrow-right nextArrow"></i>',
        prevArrow: '<i class="fas fa-arrow-left prevArrow"></i>',

        responsive: [
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 5,
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 4,
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 3,
                }
            },
            {
                breakpoint: 576,
                settings: {
                    slidesToShow: 2,
                }
            }
        ]
    });

    //=====FLASH SELL SLIDER=====
    $('.flash_sell_slider').slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 3000,
        dots: false,
        arrows: true,
        nextArrow: '<i class="fas fa-arrow-right nextArrow"></i>',
        prevArrow: '<i class="fas fa-arrow-left prevArrow"></i>',

        responsive: [
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 3,
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 3,
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 2,
                    arrows: false
                }
            },
            {
                breakpoint: 576,
                settings: {
                    slidesToShow: 2,
                }
            }
        ]
    });

    //=====MARQUEE SLIDER=====
    $('.brand_marquee').marquee({
        speed: 70,
        gap: 0,
        delayBeforeStart: 0,
        direction: 'left',
        duplicated: true,
        pauseOnHover: true
    });

    //======TRENDING PRODUCT FILTER (pwstabs loaded only on pages that use it)==========
    if ($.fn.pwstabs) {
        $('.product_tabs').pwstabs({
            effect: 'slidedown',
            defaultTab: 1,
        });
    }

    //=====BANNER SLIDER (Swiper)=====
    // Home hero. Swiper is loaded only on the home page (@push pageVendorCss/Js),
    // so guard on the global + the markup. Single-slide fade with autoplay and
    // dots — matches the original slick config.
    if (typeof Swiper !== 'undefined' && document.querySelector('.banner_2_slider')) {
        new Swiper('.banner_2_slider', {
            slidesPerView: 1,
            spaceBetween: 0,
            loop: true,
            effect: 'fade',
            fadeEffect: {
                crossFade: true,
            },
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.banner_2_slider .swiper-pagination',
                clickable: true,
            }
        });
    }

    //=====FLASH SELL 2 SLIDER=====
    $('.flash_sell_2_slider').slick({
        slidesToShow: 5,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 2500,
        dots: false,
        arrows: true,
        nextArrow: '<i class="fas fa-arrow-right nextArrow"></i>',
        prevArrow: '<i class="fas fa-arrow-left prevArrow"></i>',

        responsive: [
            {
                breakpoint: 1600,
                settings: {
                    slidesToShow: 4,
                }
            },
            {
                breakpoint: 1400,
                settings: {
                    slidesToShow: 4,
                }
            },
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 3,
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 3,
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 2,
                    arrows: false
                }
            },
            {
                breakpoint: 576,
                settings: {
                    slidesToShow: 2,
                    arrows: false,
                }
            }
        ]
    });

    //=====CATEGORY 2 SLIDER=====
    $('.category_2_slider').slick({
        slidesToShow: 9,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 2500,
        dots: false,
        arrows: true,
        nextArrow: '<i class="fas fa-arrow-right nextArrow"></i>',
        prevArrow: '<i class="fas fa-arrow-left prevArrow"></i>',

        responsive: [
            {
                breakpoint: 1600,
                settings: {
                    slidesToShow: 7,
                }
            },
            {
                breakpoint: 1400,
                settings: {
                    slidesToShow: 6,
                }
            },
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 5,
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 4,
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 4,
                }
            },
            {
                breakpoint: 576,
                settings: {
                    slidesToShow: 3,
                    arrows: false,
                }
            }
        ]
    });

    //=====FAVOURITE PRODUCT 2 SLIDER=====
    $('.favourite_product_2_slider').slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 2500,
        dots: false,
        arrows: true,
        nextArrow: '<i class="fas fa-arrow-right nextArrow"></i>',
        prevArrow: '<i class="fas fa-arrow-left prevArrow"></i>',

        responsive: [
            {
                breakpoint: 1600,
                settings: {
                    slidesToShow: 3,
                }
            },
            {
                breakpoint: 1400,
                settings: {
                    slidesToShow: 3,
                }
            },
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 2,
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 3,
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 2,
                }
            },
            {
                breakpoint: 576,
                settings: {
                    slidesToShow: 2,
                    arrows: false,
                }
            }
        ]
    });

    //=====GROCERY BEST SELL SLIDER======
    $('.grocery_best_sell_slider').slick({
        slidesToShow: 3,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 4000,
        dots: false,
        arrows: true,
        nextArrow: '<i class="fas fa-arrow-right nextArrow"></i>',
        prevArrow: '<i class="fas fa-arrow-left prevArrow"></i>',

        responsive: [
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 2,
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 2,
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 2,
                }
            },
            {
                breakpoint: 576,
                settings: {
                    slidesToShow: 2,
                }
            }
        ]
    });


    //=====TESTIMONIAL SLIDER======
    $('.testi_slider').slick({
        slidesToShow: 3,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 4000,
        dots: true,
        arrows: false,

        responsive: [
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 2,
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 2,
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                }
            },
            {
                breakpoint: 576,
                settings: {
                    slidesToShow: 1,
                }
            }
        ]
    });

    //=====BEAUTI BANNER SLIDER=====
    $('.beauty_banner_slider_large').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: true,
        arrows: false,
        dots: false,
        fade: true,
        asNavFor: '.beauty_banner_slider_small'
    });

    $('.beauty_banner_slider_small').slick({
        slidesToShow: 3,
        slidesToScroll: 1,
        asNavFor: '.beauty_banner_slider_large',
        autoplay: true,
        autoplaySpeed: 3000,
        dots: false,
        arrows: false,
        centerMode: true,
        centerPadding: '0px',
        focusOnSelect: true,
        vertical: true,

        responsive: [
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 3,
                }
            }
        ]
    });


    //=====BEAUTY FEATURED SLIDER=====
    $('.beauty_featured_slider').slick({
        slidesToShow: 3,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 3000,
        dots: false,
        arrows: true,
        nextArrow: '<i class="fas fa-angle-right nextArrow"></i>',
        prevArrow: '<i class="fas fa-angle-left prevArrow"></i>',

        responsive: [
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 2,
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 3,
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 2,
                }
            },
            {
                breakpoint: 576,
                settings: {
                    slidesToShow: 2,
                }
            }
        ]
    });


    //=====BEAUTY CATEGORY SLIDER=====
    $('.beauty_category_slider').slick({
        slidesToShow: 7,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 3000,
        dots: false,
        arrows: true,
        nextArrow: '<i class="fas fa-angle-right nextArrow"></i>',
        prevArrow: '<i class="fas fa-angle-left prevArrow"></i>',

        responsive: [
            {
                breakpoint: 1600,
                settings: {
                    slidesToShow: 6,
                }
            },
            {
                breakpoint: 1400,
                settings: {
                    slidesToShow: 5,
                }
            },
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 4,
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 4,
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 3,
                }
            },
            {
                breakpoint: 576,
                settings: {
                    slidesToShow: 2,
                    arrows: false,
                }
            }
        ]
    });


    //======BEAUTY INSTAGRAM SLIDER======
    $('.beauty_instagram_slider').slick({
        slidesToShow: 8,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 3000,
        dots: false,
        arrows: false,

        responsive: [
            {
                breakpoint: 1600,
                settings: {
                    slidesToShow: 7,
                }
            },
            {
                breakpoint: 1400,
                settings: {
                    slidesToShow: 6,
                }
            },
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 5,
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 4,
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 3,
                }
            },
            {
                breakpoint: 576,
                settings: {
                    slidesToShow: 2,
                }
            }
        ]
    });


    //=====BEAUTY BRAND SLIDER=====
    $('.beauty_brand_slider').slick({
        slidesToShow: 6,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 3000,
        dots: false,
        arrows: true,
        nextArrow: '<i class="fas fa-angle-right nextArrow"></i>',
        prevArrow: '<i class="fas fa-angle-left prevArrow"></i>',

        responsive: [
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 5,
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 4,
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 3,
                }
            },
            {
                breakpoint: 576,
                settings: {
                    slidesToShow: 3,
                }
            }
        ]
    });


    //=====TESTIMONIAL 2 SLIDER======
    $('.testi_slider_2').slick({
        slidesToShow: 2,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 3000,
        dots: false,
        arrows: true,
        nextArrow: '<i class="fas fa-angle-right nextArrow"></i>',
        prevArrow: '<i class="fas fa-angle-left prevArrow"></i>',

        responsive: [
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 1,
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 2,
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                }
            },
            {
                breakpoint: 576,
                settings: {
                    slidesToShow: 1,
                }
            }
        ]
    });


    //======STICKY SIDEBAR (stickit loaded only on shop/category/blog/cart)======
    if ($.fn.stickit) {
        $("#sticky_sidebar").stickit({
            top: 70,
            screenMinWidth: 992,
        });
        $("#sticky_sidebar_2").stickit({
            top: 70,
            screenMinWidth: 1400,
        });
    }


    //=====RANGE SLIDER (legacy demo selectors; guarded — range_slider.js now loads only on shop/category)=====
    if ($.fn.alRangeSlider) {
        $('.basic').alRangeSlider();
        const options = {
            range: { min: 0, max: 1000, step: 1 },
            initialSelectedValues: { from: 100, to: 500 },
            grid: { minTicksStep: 1, marksStep: 5 },
            theme: "dark",
        };
        $('.range_slider').alRangeSlider(options);
    }


    //======PRODUCT FILTER======
    $(".shop_filter_btn").on("click", function () {
        $(".shop_filter_btn").toggleClass("show");
    });
    $(".shop_filter_btn").on("click", function () {
        $(".shop_filter_area").toggleClass("show");
    });


    //======PRODUCT DETAILS SLIDER (Swiper)======
    // Product + combo detail galleries. Swiper is loaded only on those two
    // pages (@push pageVendorCss/Js), so guard on the global + the markup.
    // The thumbnail rail is vertical on desktop and flips horizontal under
    // 768px, matching the original design.
    if (typeof Swiper !== 'undefined' && document.querySelector('.details_slider_thumb')) {
        var detailsMainSwiper = new Swiper('.details_slider_thumb', {
            slidesPerView: 1,
            spaceBetween: 0,
            loop: true,
            // Auto-advance the main image; the thumbnail rail auto-selects and
            // auto-scrolls to follow via the slideChange handler below.
            // disableOnInteraction:false keeps it going after a swipe;
            // pauseOnMouseEnter halts it while the user hovers/zooms (desktop).
            autoplay: {
                delay: 3500,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            },
        });

        // Thumbnail rail is a native scroll container (CSS) driven by jQuery —
        // NOT a Swiper. Swiper's thumbs auto-scroll jumped the rail to its far
        // edge going forward, cropping thumbs 50%. Here we scroll the active
        // thumb into view by the MINIMUM amount needed (one image, either
        // direction), smoothly, so it never over-scrolls.
        var $nav = $('.details_slider_nav');
        var $navItems = $('.details_slider_nav_item');

        function scrollThumbIntoView($item) {
            var nav = $nav.get(0);
            var el = $item.get(0);
            if (!nav || !el) return;
            var navRect = nav.getBoundingClientRect();
            var elRect = el.getBoundingClientRect();
            var pad = 12; // keep a small gap so the active thumb isn't flush to the edge
            var horizontal = nav.scrollWidth > nav.clientWidth + 1;
            if (horizontal) {
                if (elRect.left < navRect.left) {
                    nav.scrollBy({ left: elRect.left - navRect.left - pad, behavior: 'smooth' });
                } else if (elRect.right > navRect.right) {
                    nav.scrollBy({ left: elRect.right - navRect.right + pad, behavior: 'smooth' });
                }
            } else {
                if (elRect.top < navRect.top) {
                    nav.scrollBy({ top: elRect.top - navRect.top - pad, behavior: 'smooth' });
                } else if (elRect.bottom > navRect.bottom) {
                    nav.scrollBy({ top: elRect.bottom - navRect.bottom + pad, behavior: 'smooth' });
                }
            }
        }

        function setActiveThumb(i) {
            var $active = $navItems.removeClass('active').eq(i).addClass('active');
            scrollThumbIntoView($active);
        }

        $navItems.on('click', function () {
            // slideToLoop maps the real thumb index to the looped slide.
            detailsMainSwiper.slideToLoop(parseInt($(this).data('index'), 10) || 0);
        });
        detailsMainSwiper.on('slideChange', function () {
            setActiveThumb(detailsMainSwiper.realIndex);
        });

        // Cap the vertical desktop rail to the main image height so it scrolls
        // internally instead of stretching to fit every thumb (percentage
        // height is unreliable inside the flex column). On mobile the rail is a
        // horizontal strip, so clear the cap and let the CSS media query rule.
        var $mainSlider = $('.details_slider_thumb');
        function syncRailHeight() {
            var nav = $nav.get(0);
            if (!nav) return;
            if (window.matchMedia('(min-width: 768px)').matches) {
                var h = $mainSlider.outerHeight();
                if (h) nav.style.maxHeight = h + 'px';
            } else {
                nav.style.maxHeight = '';
            }
        }
        syncRailHeight();
        $(window).on('load resize', syncRailHeight);
    }

    //======DETAIL GALLERY IMAGE ZOOM (extm)======
    // Hover-to-zoom on the main gallery image — shared by the product AND combo
    // detail pages. Square lens overlay on the image + a side zoom panel.
    // Desktop only (>=992px). Must run after images have real dimensions, else
    // extm's internal width check sees 0 and waits for a 'load' that never fires
    // for cached images — so init on window 'load' (or now if already complete).
    function bpInitDetailZoom() {
        if (typeof $.fn.extm !== 'function') return;
        if (!window.matchMedia('(min-width: 992px)').matches) return;
        // Real main-slider slides only — skip Swiper loop duplicates. Fallbacks
        // cover the single-image gallery (no slider) on either page.
        var $imgs = $('.details_slider_thumb .swiper-slide:not(.swiper-slide-duplicate) .details_slider_thumb_item img');
        if (!$imgs.length) $imgs = $('.shop_details_slider_area .details_slider_thumb_item img');
        if (!$imgs.length) $imgs = $('#productMainImg');
        $imgs.each(function () {
            if ($(this).data('extmInit')) return; // guard against double-init
            $(this).data('extmInit', true);
            $(this).extm({
                squareOverlay: true,
                position: 'right',
                rightPad: 20,
                zoomLevel: 2.5
            });
        });
    }
    if (document.querySelector('.shop_details_slider_area')) {
        if (document.readyState === 'complete') {
            bpInitDetailZoom();
        } else {
            $(window).on('load', bpInitDetailZoom);
        }
    }


    //=====RATING JS=====
    const stars = document.querySelectorAll(".select_rating i");

    stars.forEach((star, index1) => {
        star.addEventListener("click", () => {
            stars.forEach((star, index2) => {
                index1 >= index2 ? star.classList.add("active") : star.classList.remove("active");
            });
        });
    });


    //=====MOBILE MENU TOGGLER=====
    // Only the +/- handle (.mobile_cat_toggle) expands/collapses a parent;
    // tapping the category name itself navigates to its page. Leaf items have
    // no handle, so they always navigate.
    const mobile_menu = document.querySelectorAll(".mobile_dropdown");
    function toggleMobileDropdown(dropdown) {
        const innerMenu = dropdown.querySelector(".inner_menu");
        if (!innerMenu) return;
        if (innerMenu.style.maxHeight) {
            innerMenu.style.maxHeight = null;
            dropdown.classList.remove("active");
        } else {
            mobile_menu.forEach((item) => {
                const menu = item.querySelector(".inner_menu");
                if (menu && menu !== innerMenu) {
                    menu.style.maxHeight = null;
                    item.classList.remove("active");
                }
            });
            innerMenu.style.maxHeight = innerMenu.scrollHeight + "px";
            dropdown.classList.add("active");
        }
    }
    mobile_menu.forEach((dropdown) => {
        const handle = dropdown.querySelector(":scope > .mobile_cat_toggle");
        if (!handle) return;
        handle.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleMobileDropdown(dropdown);
        });
        // Keyboard accessibility: Enter / Space toggles the handle.
        handle.addEventListener("keydown", (e) => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                toggleMobileDropdown(dropdown);
            }
        });
    });


    //=====REVIEW IMAGE UPLOAD=====
    if ($('.gallery').length && typeof $.fn.miv === 'function') {
        $('.gallery').miv({ image: '.cam', video: '.vid' });
    }


    //=====GADGET BANNER SLIDER=====
    $('.gadget_slider_active').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 3000,
        dots: false,
        arrows: true,
        fade: true,
        nextArrow: '<i class="fas fa-arrow-right nextArrow"></i>',
        prevArrow: '<i class="fas fa-arrow-left prevArrow"></i>',

        responsive: [
            {
                breakpoint: 576,
                settings: {
                    arrows: false
                }
            }
        ]
    });


    //=====BARFILLER JS=====
    $(document).ready(function () {
        if ($('.barfiller').length && typeof $.fn.barfiller === 'function') {
            $('.barfiller').barfiller();
        }
    });


});
