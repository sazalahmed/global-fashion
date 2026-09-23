<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\StoreTestimonialRequest;
use Modules\Ecommerce\Models\Testimonial;
use App\Helpers\Upload;

class TestimonialController extends Controller
{
    public function index(): View
    {
        $testimonials = Testimonial::latest()->get();

        return view('ecommerce::testimonials.index', compact('testimonials'));
    }

    public function create(): View
    {
        return view('ecommerce::testimonials.create');
    }

    public function store(StoreTestimonialRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $data['image'] = Upload::store($request->file('image'), 'testimonials');
        }

        Testimonial::create($data);

        return redirect()->route('ecommerce.testimonials.index')->with('success', __('Testimonial created successfully.'));
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('ecommerce::testimonials.edit', compact('testimonial'));
    }

    public function update(StoreTestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            Upload::delete($testimonial->image);
            $data['image'] = Upload::store($request->file('image'), 'testimonials');
        }

        $testimonial->update($data);

        return redirect()->route('ecommerce.testimonials.index')->with('success', __('Testimonial updated successfully.'));
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        if ($testimonial->image) {
            Upload::delete($testimonial->image);
        }
        
        $testimonial->delete();

        return redirect()->route('ecommerce.testimonials.index')->with('success', __('Testimonial deleted successfully.'));
    }

    public function toggleStatus(Testimonial $testimonial, Request $request): JsonResponse|RedirectResponse
    {
        $testimonial->update(['is_active' => ! $testimonial->is_active]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['is_active' => (bool) $testimonial->is_active, 'message' => __('Testimonial status updated.')]);
        }

        return back()->with('success', __('Testimonial status updated.'));
    }
}
