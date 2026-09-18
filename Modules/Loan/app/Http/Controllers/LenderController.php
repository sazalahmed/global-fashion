<?php

namespace Modules\Loan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Loan\Http\Requests\StoreLenderRequest;
use Modules\Loan\Http\Requests\UpdateLenderRequest;
use Modules\Loan\Models\Lender;
use Modules\Loan\Services\LoanService;

class LenderController extends Controller
{
    public function __construct(
        private readonly LoanService $service,
    ) {}

    public function index(Request $request)
    {
        bpAuthorize('finance.view');
        $stats = $this->service->getLenderStats();
        $lenders = $this->service->listLenders($request->only(['search', 'status', 'has_outstanding']));

        return view('loan::lenders.index', compact('stats', 'lenders'));
    }

    public function create()
    {
        bpAuthorize('finance.create');
        return view('loan::lenders.create');
    }

    public function store(StoreLenderRequest $request)
    {
        bpAuthorize('finance.create');
        $this->service->createLender($request->validated());

        return redirect()->route('lenders.index')->with('success', __('Lender created successfully.'));
    }

    public function show(Lender $lender, Request $request)
    {
        bpAuthorize('finance.view');
        $ledger = $this->service->getLenderLedger($lender, $request->only(['from', 'to']));

        return view('loan::lenders.show', [
            'lender'         => $lender,
            'ledgerEntries'  => $ledger['entries'],
            'totalDebit'     => $ledger['totalDebit'],
            'totalCredit'    => $ledger['totalCredit'],
            'currentBalance' => $ledger['currentBalance'],
        ]);
    }

    public function edit(Lender $lender)
    {
        bpAuthorize('finance.edit');
        return view('loan::lenders.edit', compact('lender'));
    }

    public function update(UpdateLenderRequest $request, Lender $lender)
    {
        bpAuthorize('finance.edit');
        $this->service->updateLender($lender, $request->validated());

        return redirect()->route('lenders.show', $lender)->with('success', __('Lender updated successfully.'));
    }

    public function toggleStatus(Lender $lender): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('finance.edit');
        $lender->update(['status' => $lender->status === 'active' ? 'inactive' : 'active']);

        return response()->json([
            'success'   => true,
            'is_active' => $lender->status === 'active',
            'message'   => __('Status updated.'),
        ]);
    }

    public function destroy(Lender $lender)
    {
        bpAuthorize('finance.delete');
        try {
            $this->service->deleteLender($lender);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('lenders.index')->with('success', __('Lender deleted.'));
    }
}
