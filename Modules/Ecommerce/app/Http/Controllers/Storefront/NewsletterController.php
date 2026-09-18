<?php

namespace Modules\Ecommerce\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\NewsletterSubscriber;

class NewsletterController extends Controller
{
    /**
     * Capture a newsletter subscription. Idempotent — re-subscribing an
     * existing email is reported gracefully rather than erroring.
     */
    public function subscribe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => $validated['email']],
            ['is_active' => true],
        );

        $message = $subscriber->wasRecentlyCreated
            ? __('Thanks for subscribing!')
            : __('You are already subscribed.');

        if ($subscriber->wasRecentlyCreated) {
            session()->flash('bp_track', ['event' => 'Lead', 'ga' => [], 'fb' => ['data' => ['content_name' => 'newsletter']]]);
        }

        return back()->with('newsletter_status', $message);
    }
}
