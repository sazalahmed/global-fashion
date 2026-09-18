<?php

namespace Modules\Location\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Location\Models\District;
use Modules\Location\Models\Thana;
use Modules\Location\Services\LocationService;

class LocationController extends Controller
{
    public function __construct(protected LocationService $service)
    {
    }

    /**
     * Show the management page with districts and thanas.
     */
    public function index(Request $request)
    {
        bpAuthorize('locations.view');
        $tab = $request->input('tab', 'districts');

        $districts = $this->service->listDistricts([
            'search' => $request->input('district_search'),
        ]);

        $thanas = $this->service->listThanas([
            'search'      => $request->input('thana_search'),
            'district_id' => $request->input('filter_district_id'),
        ]);

        $allDistricts = District::orderBy('district_name')->get(['id', 'district_name']);

        return view('location::index', compact('districts', 'thanas', 'allDistricts', 'tab'));
    }

    // ── District CRUD ──

    public function storeDistrict(Request $request)
    {
        bpAuthorize('locations.create');
        $validated = $request->validate([
            'district_name' => ['required', 'string', 'max:100', 'unique:districts,district_name'],
            'bn_name'       => ['nullable', 'string', 'max:100'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $this->service->createDistrict($validated);

        return redirect()->route('locations.index', ['tab' => 'districts'])->with('success', __('District created successfully.'));
    }

    public function updateDistrict(Request $request, District $district)
    {
        bpAuthorize('locations.edit');
        $validated = $request->validate([
            'district_name' => ['required', 'string', 'max:100', 'unique:districts,district_name,' . $district->id],
            'bn_name'       => ['nullable', 'string', 'max:100'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $this->service->updateDistrict($district, $validated);

        return redirect()->route('locations.index', ['tab' => 'districts'])->with('success', __('District updated successfully.'));
    }

    public function destroyDistrict(District $district)
    {
        bpAuthorize('locations.delete');
        if ($district->thanas()->exists()) {
            return redirect()->route('locations.index', ['tab' => 'districts'])->with('error', __('Cannot delete district that has thanas. Delete or move the thanas first.'));
        }

        $this->service->deleteDistrict($district);

        return redirect()->route('locations.index', ['tab' => 'districts'])->with('success', __('District deleted successfully.'));
    }

    // ── Thana CRUD ──

    public function storeThana(Request $request)
    {
        bpAuthorize('locations.create');
        $validated = $request->validate([
            'district_id' => ['required', 'exists:districts,id'],
            'thana_name'  => ['required', 'string', 'max:100'],
            'bn_name'     => ['nullable', 'string', 'max:100'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $this->service->createThana($validated);

        return redirect()->route('locations.index', ['tab' => 'thanas'])->with('success', __('Thana created successfully.'));
    }

    public function updateThana(Request $request, Thana $thana)
    {
        bpAuthorize('locations.edit');
        $validated = $request->validate([
            'district_id' => ['required', 'exists:districts,id'],
            'thana_name'  => ['required', 'string', 'max:100'],
            'bn_name'     => ['nullable', 'string', 'max:100'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $this->service->updateThana($thana, $validated);

        return redirect()->route('locations.index', ['tab' => 'thanas'])->with('success', __('Thana updated successfully.'));
    }

    public function destroyThana(Thana $thana)
    {
        bpAuthorize('locations.delete');
        $this->service->deleteThana($thana);

        return redirect()->route('locations.index', ['tab' => 'thanas'])->with('success', __('Thana deleted successfully.'));
    }

    /**
     * Return active thanas for the given district as JSON (used by dependent
     * dropdowns on sale create/edit and similar forms).
     */
    public function thanasByDistrict(District $district)
    {
        bpAuthorizeAny('locations.view', 'sales.view', 'customers.view', 'suppliers.view');
        $thanas = $district->thanas()
            ->where('is_active', true)
            ->orderBy('thana_name')
            ->get(['id', 'thana_name', 'bn_name']);

        return response()->json([
            'success' => true,
            'data'    => $thanas,
        ]);
    }
}
