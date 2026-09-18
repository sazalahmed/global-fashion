<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Ecommerce\Models\EcommerceSetting;
use Modules\Ecommerce\Models\Faq;
use Modules\Ecommerce\Support\BuildsSeo;

class FaqController extends Controller
{
    use BuildsSeo;

    public function index(): View
    {
        $faqs = Faq::active()->ordered()->get();
        $cartItems = session('cart', []);

        // Admin-managed FAQ page header (Ecommerce → Settings → FAQ Page),
        // each falling back to the bundled default when left blank.
        $faqImage = EcommerceSetting::get('faq_image');
        $faqHeader = [
            'sub_title' => EcommerceSetting::get('faq_sub_title') ?: 'general question here',
            'title'     => EcommerceSetting::get('faq_title') ?: 'Some Frequently Asked Questions.',
            'image'     => $faqImage ? upload_url($faqImage) : asset('website/assets/images/faq_img_1.png'),
        ];

        $seo = $this->staticPageSeo('faq', [
            'title'       => 'Frequently Asked Questions',
            'description' => 'Answers to frequently asked questions about ordering, shipping, returns, and payments.',
        ]);

        return view('ecommerce::storefront.pages.faq.index', compact('faqs', 'cartItems', 'seo', 'faqHeader'));
    }
}
