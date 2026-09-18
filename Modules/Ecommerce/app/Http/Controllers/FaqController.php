<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\StoreFaqRequest;
use Modules\Ecommerce\Models\Faq;

class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::ordered()->get();

        return view('ecommerce::faqs.index', compact('faqs'));
    }

    public function create(): View
    {
        return view('ecommerce::faqs.create');
    }

    public function store(StoreFaqRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['position'] = $data['position'] ?? ((int) Faq::max('position') + 1);

        Faq::create($data);

        return redirect()->route('ecommerce.faqs.index')->with('success', __('FAQ created successfully.'));
    }

    public function edit(Faq $faq): View
    {
        return view('ecommerce::faqs.edit', compact('faq'));
    }

    public function update(StoreFaqRequest $request, Faq $faq): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $faq->update($data);

        return redirect()->route('ecommerce.faqs.index')->with('success', __('FAQ updated successfully.'));
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('ecommerce.faqs.index')->with('success', __('FAQ deleted successfully.'));
    }

    public function toggleStatus(Faq $faq, Request $request): JsonResponse|RedirectResponse
    {
        $faq->update(['is_active' => ! $faq->is_active]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['is_active' => (bool) $faq->is_active, 'message' => __('FAQ status updated.')]);
        }

        return back()->with('success', __('FAQ status updated.'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids'   => ['required', 'array'],
            'ordered_ids.*' => ['integer', 'exists:faqs,id'],
        ]);

        foreach ($validated['ordered_ids'] as $index => $id) {
            Faq::where('id', $id)->update(['position' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }
}
