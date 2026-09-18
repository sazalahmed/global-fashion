<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Accounting\Services\CreditNoteService;
use Modules\Accounting\Services\DebitNoteService;

class AccountingApiController extends BaseApiController
{
    public function __construct(
        private readonly JournalEntryService $journalService,
        private readonly CreditNoteService $creditNoteService,
        private readonly DebitNoteService $debitNoteService,
    ) {}

    public function accounts(): JsonResponse
    {
        $accounts = Account::with('children')
            ->whereNull('parent_id')
            ->orderBy('account_code')
            ->get();
        return $this->success($accounts, 'Chart of accounts retrieved');
    }

    public function accountShow(int $id): JsonResponse
    {
        return $this->success(Account::with('parent')->findOrFail($id));
    }

    public function accountLedger(Request $request, int $id): JsonResponse
    {
        $account = Account::findOrFail($id);
        $entries = \Modules\Accounting\Models\JournalEntryLine::with('journalEntry')
            ->where('account_id', $id)
            ->when($request->date_from, fn ($q, $d) => $q->whereHas('journalEntry', fn ($j) => $j->where('entry_date', '>=', $d)))
            ->when($request->date_to, fn ($q, $d) => $q->whereHas('journalEntry', fn ($j) => $j->where('entry_date', '<=', $d)))
            ->latest('id')
            ->paginate($request->input('per_page', 20));
        return $this->paginatedSuccess($entries);
    }

    public function journalList(Request $request): JsonResponse
    {
        return $this->paginatedSuccess($this->journalService->list($request->all(), $request->input('per_page', 15)));
    }

    public function journalShow(int $id): JsonResponse
    {
        return $this->success($this->journalService->find($id));
    }

    public function journalStore(Request $request): JsonResponse
    {
        $v = $request->validate([
            'entry_date' => 'required|date', 'reference' => 'nullable|string|max:255',
            'description' => 'required|string', 'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer|exists:accounts,id',
            'lines.*.debit_amount' => 'nullable|numeric|min:0',
            'lines.*.credit_amount' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ]);
        return $this->success($this->journalService->create($v), 'Journal entry created', 201);
    }

    public function creditNoteList(Request $request): JsonResponse
    {
        return $this->paginatedSuccess($this->creditNoteService->list($request->all(), $request->input('per_page', 15)));
    }

    public function creditNoteShow(int $id): JsonResponse
    {
        return $this->success($this->creditNoteService->find($id));
    }

    public function creditNoteStore(Request $request): JsonResponse
    {
        $v = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id', 'issue_date' => 'required|date',
            'reason' => 'required|string', 'items' => 'required|array|min:1',
            'items.*.description' => 'required|string', 'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);
        return $this->success($this->creditNoteService->create($v), 'Credit note created', 201);
    }

    public function debitNoteList(Request $request): JsonResponse
    {
        return $this->paginatedSuccess($this->debitNoteService->list($request->all(), $request->input('per_page', 15)));
    }

    public function debitNoteStore(Request $request): JsonResponse
    {
        $v = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id', 'issue_date' => 'required|date',
            'reason' => 'required|string', 'items' => 'required|array|min:1',
            'items.*.description' => 'required|string', 'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);
        return $this->success($this->debitNoteService->create($v), 'Debit note created', 201);
    }
}
