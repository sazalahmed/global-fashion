<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Models\Factory;
use Modules\Manufacturing\Services\FactoryService;
use Illuminate\Http\Request;

class FactoryController extends Controller
{
    public function __construct(private FactoryService $factoryService)
    {
    }

    /**
     * Display a listing of factories.
     */
    public function index(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $factories = $this->factoryService->list(
            $request->only(['search', 'is_active'])
        );

        $stats = [
            'total' => Factory::count(),
            'active' => Factory::where('is_active', true)->count(),
            'inactive' => Factory::where('is_active', false)->count(),
        ];

        return view('manufacturing::factories.index', compact('factories', 'stats'));
    }

    /**
     * Show the form for creating a new factory.
     */
    public function create()
    {
        bpAuthorize('manufacturing.create');
        return view('manufacturing::factories.create');
    }

    /**
     * Store a newly created factory.
     */
    public function store(Request $request)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $this->factoryService->create($validated);

        return redirect()->route('manufacturing.factories.index')->with('success', __('Factory created successfully.'));
    }

    /**
     * Display the specified factory.
     */
    public function show(Factory $factory)
    {
        bpAuthorize('manufacturing.view');
        return view('manufacturing::factories.show', compact('factory'));
    }

    /**
     * Show the form for editing the specified factory.
     */
    public function edit(Factory $factory)
    {
        bpAuthorize('manufacturing.edit');
        return view('manufacturing::factories.edit', compact('factory'));
    }

    /**
     * Update the specified factory.
     */
    public function update(Request $request, Factory $factory)
    {
        bpAuthorize('manufacturing.edit');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $this->factoryService->update($factory, $validated);

        return redirect()->route('manufacturing.factories.index')->with('success', __('Factory updated successfully.'));
    }

    /**
     * Toggle the active status of the specified factory (AJAX).
     */
    public function toggleStatus(Factory $factory): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('manufacturing.edit');
        $factory->update(['is_active' => ! $factory->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $factory->is_active,
            'message' => __('Status updated.'),
        ]);
    }

    /**
     * Remove the specified factory.
     */
    public function destroy(Factory $factory)
    {
        bpAuthorize('manufacturing.delete');
        try {
            $this->factoryService->delete($factory);

            return redirect()->route('manufacturing.factories.index')->with('success', __('Factory deleted successfully.'));
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
