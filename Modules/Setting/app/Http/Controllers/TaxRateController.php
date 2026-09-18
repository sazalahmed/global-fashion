<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Setting\Http\Requests\StoreTaxRateRequest;
use Modules\Setting\Http\Requests\UpdateTaxRateRequest;
use Modules\Setting\Models\TaxRate;

class TaxRateController extends Controller
{
    public function store(StoreTaxRateRequest $request)
    {
        bpAuthorize('settings.edit');
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            if (!empty($data['is_default'])) {
                TaxRate::where('is_default', true)->update(['is_default' => false]);
            }
            TaxRate::create($data);
        });

        return back()->with('success', __('Tax rate added.'))->with('active_section', 'taxSettings');
    }

    public function update(UpdateTaxRateRequest $request, TaxRate $tax_rate)
    {
        bpAuthorize('settings.edit');
        $data = $request->validated();

        DB::transaction(function () use ($data, $tax_rate) {
            if (!empty($data['is_default'])) {
                TaxRate::where('id', '!=', $tax_rate->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
            $tax_rate->update($data);
        });

        return back()->with('success', __('Tax rate updated.'))->with('active_section', 'taxSettings');
    }

    public function destroy(TaxRate $tax_rate)
    {
        bpAuthorize('settings.edit');
        if ($tax_rate->is_default) {
            return back()->with('error', __('Cannot delete the default tax rate. Set another rate as default first.'))->with('active_section', 'taxSettings');
        }

        $tax_rate->delete();

        return back()->with('success', __('Tax rate deleted.'))->with('active_section', 'taxSettings');
    }
}
