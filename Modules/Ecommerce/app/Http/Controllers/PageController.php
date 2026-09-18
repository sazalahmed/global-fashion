<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Helpers\Upload;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\StorePageRequest;
use Modules\Ecommerce\Models\Page;

class PageController extends Controller
{
    public function index(): View
    {
        $pages = Page::orderBy('title')->get();

        return view('ecommerce::pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('ecommerce::pages.create');
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published', false);

        if ($request->hasFile('seo_image')) {
            $data['seo_image'] = Upload::store($request->file('seo_image'), 'pages/seo');
        }

        Page::create($data);

        return redirect()->route('ecommerce.pages.index')->with('success', __('Page created successfully.'));
    }

    public function edit(Page $page): View
    {
        return view('ecommerce::pages.edit', compact('page'));
    }

    public function update(StorePageRequest $request, Page $page): RedirectResponse
    {
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published', false);

        if ($request->hasFile('seo_image')) {
            Upload::delete($page->seo_image);
            $data['seo_image'] = Upload::store($request->file('seo_image'), 'pages/seo');
        } else {
            unset($data['seo_image']);
        }

        $page->update($data);

        return redirect()->route('ecommerce.pages.index')->with('success', __('Page updated successfully.'));
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('ecommerce.pages.index')->with('success', __('Page deleted successfully.'));
    }

    public function toggleStatus(Page $page, Request $request): JsonResponse|RedirectResponse
    {
        $page->update(['is_published' => ! $page->is_published]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['is_active' => (bool) $page->is_published, 'message' => __('Page status updated.')]);
        }

        return back()->with('success', __('Page status updated.'));
    }
}
