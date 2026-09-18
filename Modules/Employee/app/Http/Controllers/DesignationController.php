<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Employee\Models\Designation;

class DesignationController extends Controller
{
    public function index()
    {
        bpAuthorize('hr.view');
        $designations = Designation::orderBy('sort_order')->orderBy('name')->get();

        return view('employee::designations.index', compact('designations'));
    }

    public function store(Request $request)
    {
        bpAuthorize('hr.create');
        Designation::create($this->validateData($request));

        return back()->with('success', __('Designation created.'));
    }

    public function update(Request $request, Designation $designation)
    {
        bpAuthorize('hr.edit');
        $designation->update($this->validateData($request, $designation));

        return back()->with('success', __('Designation updated.'));
    }

    public function destroy(Designation $designation)
    {
        bpAuthorize('hr.delete');
        $designation->delete();

        return back()->with('success', __('Designation deleted.'));
    }

    public function toggleStatus(Designation $designation): JsonResponse
    {
        bpAuthorize('hr.edit');
        $designation->update(['is_active' => ! $designation->is_active]);

        return response()->json(['success' => true, 'is_active' => $designation->is_active, 'message' => __('Status updated.')]);
    }

    private function validateData(Request $request, ?Designation $designation = null): array
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:100', Rule::unique('designations', 'name')->ignore($designation?->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
