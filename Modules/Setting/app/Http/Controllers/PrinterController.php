<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Branch\Models\Branch;
use Modules\Setting\Http\Requests\StorePrinterRequest;
use Modules\Setting\Http\Requests\UpdatePrinterRequest;
use Modules\Setting\Models\Printer;

class PrinterController extends Controller
{
    public function index()
    {
        bpAuthorize('settings.view');
        $printers = Printer::with('branch')->latest()->get();

        return view('setting::printers.index', compact('printers'));
    }

    public function create()
    {
        bpAuthorize('settings.edit');
        $branches = Branch::where('is_active', true)->get();

        return view('setting::printers.create', compact('branches'));
    }

    public function store(StorePrinterRequest $request)
    {
        bpAuthorize('settings.edit');
        $validated = $request->validated();
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();

        // If setting as default, unset other defaults for same purpose+branch
        if ($validated['is_default']) {
            Printer::where('purpose', $validated['purpose'])
                ->where('branch_id', $validated['branch_id'])
                ->update(['is_default' => false]);
        }

        Printer::create($validated);

        return redirect()->route('settings.printers.index')->with('success', __('Printer added successfully.'));
    }

    public function edit(Printer $printer)
    {
        bpAuthorize('settings.edit');
        $branches = Branch::where('is_active', true)->get();

        return view('setting::printers.edit', compact('printer', 'branches'));
    }

    public function update(UpdatePrinterRequest $request, Printer $printer)
    {
        bpAuthorize('settings.edit');
        $validated = $request->validated();
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active', true);

        if ($validated['is_default']) {
            Printer::where('purpose', $validated['purpose'])
                ->where('branch_id', $validated['branch_id'])
                ->where('id', '!=', $printer->id)
                ->update(['is_default' => false]);
        }

        $printer->update($validated);

        return redirect()->route('settings.printers.index')->with('success', __('Printer updated successfully.'));
    }

    public function destroy(Printer $printer)
    {
        bpAuthorize('settings.edit');
        $printer->delete();

        return redirect()->route('settings.printers.index')->with('success', __('Printer deleted.'));
    }

    public function testConnection(Printer $printer): JsonResponse
    {
        bpAuthorize('settings.edit');
        $result = $printer->testConnection();

        return response()->json($result);
    }

    public function toggleStatus(Printer $printer): JsonResponse
    {
        bpAuthorize('settings.edit');
        $printer->update(['is_active' => ! $printer->is_active]);

        return response()->json(['success' => true, 'is_active' => $printer->is_active, 'message' => __('Status updated.')]);
    }

}
