<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Models\DebitNote;
use Modules\Accounting\Services\DebitNoteService;
use Modules\Product\Models\Product;
use Modules\Supplier\Models\Supplier;

class DebitNoteController extends Controller
{
    public function __construct(
        private readonly DebitNoteService $service,
    ) {}

    public function index(Request $request)
    {
        bpAuthorize('accounting.view');
        $stats = $this->service->getStats();
        $debitNotes = $this->service->list(
            $request->only(['search', 'status', 'date_from', 'date_to']),
        );

        return view('accounting::debit-notes.index', compact('stats', 'debitNotes'));
    }

    public function create()
    {
        bpAuthorize('accounting.create');
        $suppliers = Supplier::orderBy('company_name')->get(['id', 'company_name']);
        $products = Product::orderBy('name')->get(['id', 'name', 'cost_price']);

        return view('accounting::debit-notes.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        bpAuthorize('accounting.create');
        $data = $request->validate([
            'supplier_id'          => 'nullable|exists:suppliers,id',
            'purchase_id'          => 'nullable|integer',
            'issue_date'           => 'required|date',
            'reason'               => 'required|string|max:500',
            'notes'                => 'nullable|string|max:2000',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'nullable|exists:products,id',
            'items.*.description'  => 'required|string|max:500',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.tax_amount'   => 'nullable|numeric|min:0',
        ]);

        $dn = $this->service->create($data);

        return redirect()->route('accounting.debit-notes.show', $dn)
            ->with('success', "Debit Note {$dn->debit_note_number} created.");
    }

    public function show(DebitNote $debitNote)
    {
        bpAuthorize('accounting.view');
        $debitNote = $this->service->find($debitNote->id);

        return view('accounting::debit-notes.show', compact('debitNote'));
    }

    public function issue(DebitNote $debitNote)
    {
        bpAuthorize('accounting.edit');
        $this->service->issue($debitNote);

        return back()->with('success', "Debit Note {$debitNote->debit_note_number} issued. Journal entry created.");
    }

    public function destroy(DebitNote $debitNote)
    {
        bpAuthorize('accounting.delete');
        try {
            $this->service->delete($debitNote);
            return redirect()->route('accounting.debit-notes.index')
                ->with('success', __('Debit note deleted.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(DebitNote $debitNote)
    {
        bpAuthorize('accounting.edit');
        try {
            $this->service->cancel($debitNote);
            return back()->with('success', "Debit Note {$debitNote->debit_note_number} cancelled.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function print(DebitNote $debitNote)
    {
        bpAuthorize('accounting.view');
        $debitNote = $this->service->find($debitNote->id);

        return view('accounting::debit-notes.print', compact('debitNote'));
    }

    public function pdf(DebitNote $debitNote)
    {
        bpAuthorize('accounting.view');
        $debitNote = $this->service->find($debitNote->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('accounting::debit-notes.print', [
            'debitNote' => $debitNote,
            'isPdf' => true,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("DebitNote-{$debitNote->debit_note_number}.pdf");
    }
}
