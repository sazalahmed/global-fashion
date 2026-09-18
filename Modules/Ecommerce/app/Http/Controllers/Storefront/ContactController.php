<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\ContactMessage;
use Modules\Ecommerce\Services\EcommerceService;

class ContactController extends Controller
{
    public function __construct(private readonly EcommerceService $service) {}

    /**
     * Show the contact page.
     */
    public function index(): View
    {
        return view('ecommerce::storefront.pages.contact.index', $this->service->getContactPageData());
    }

    /**
     * Store a contact form submission.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => ['nullable', 'string', 'max:30', new \App\Rules\PhoneNumber],
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $validated['ip_address'] = $request->ip();

        ContactMessage::create($validated);

        return redirect()->route('storefront.contact')
            ->with('success', __('Thank you! Your message has been sent. We will get back to you soon.'));
    }
}
