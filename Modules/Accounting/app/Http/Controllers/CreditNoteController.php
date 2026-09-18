<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Models\CreditNote;
use Modules\Accounting\Services\CreditNoteService;
use Modules\Product\Models\Product;

class CreditNoteController extends Controller
{
    public function __construct(
        private readonly CreditNoteService $service,
    ) {}

    public function index(Request $request)
    {
        bpAuthorize('accounting.view');
        $stats = $this->service->getStats();
        $creditNotes = $this->service->list(
            $request->only(['search', 'status', 'date_from', 'date_to']),
        );

        return view('accounting::credit-notes.index', compact('stats', 'creditNotes'));
    }

    public function create()
    {
        bpAuthorize('accounting.create');
        $products = Product::orderBy('name')->get(['id', 'name', 'sell_price']);

        return view('accounting::credit-notes.create', compact('products'));
    }

    public function store(Request $request)
    {
        bpAuthorize('accounting.create');
        $data = $request->validate([
            'customer_id'          => 'nullable|integer',
            'sale_id'              => 'nullable|integer',
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

        $cn = $this->service->create($data);

        return redirect()->route('accounting.credit-notes.show', $cn)
            ->with('success', "Credit Note {$cn->credit_note_number} created.");
    }

    public function show(CreditNote $creditNote)
    {
        bpAuthorize('accounting.view');
        $creditNote = $this->service->find($creditNote->id);

        return view('accounting::credit-notes.show', compact('creditNote'));
    }

    public function issue(CreditNote $creditNote)
    {
        bpAuthorize('accounting.edit');
        $this->service->issue($creditNote);

        return back()->with('success', "Credit Note {$creditNote->credit_note_number} issued. Journal entry created.");
    }

    public function destroy(CreditNote $creditNote)
    {
        bpAuthorize('accounting.delete');
        try {
            $this->service->delete($creditNote);
            return redirect()->route('accounting.credit-notes.index')
                ->with('success', __('Credit note deleted.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(CreditNote $creditNote)
    {
        bpAuthorize('accounting.edit');
        try {
            $this->service->cancel($creditNote);
            return back()->with('success', "Credit Note {$creditNote->credit_note_number} cancelled.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function print(CreditNote $creditNote)
    {
        bpAuthorize('accounting.view');
        $creditNote = $this->service->find($creditNote->id);

        return view('accounting::credit-notes.print', compact('creditNote'));
    }

    public function pdf(CreditNote $creditNote)
    {
        bpAuthorize('accounting.view');
        $creditNote = $this->service->find($creditNote->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('accounting::credit-notes.print', [
            'creditNote' => $creditNote,
            'isPdf' => true,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("CreditNote-{$creditNote->credit_note_number}.pdf");
    }
}
