<?php

namespace Modules\Accounting\Http\Controllers;

use App\Helpers\Upload;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Http\Requests\StoreJournalEntryRequest;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\ChartOfAccountsService;
use Modules\Accounting\Services\JournalEntryService;

class JournalEntryController extends Controller
{
    public function __construct(
        private readonly JournalEntryService $journalService,
        private readonly ChartOfAccountsService $accountService,
    ) {}

    /**
     * Display a listing of journal entries.
     */
    public function index(Request $request)
    {
        bpAuthorize('accounting.view');
        $filters = $request->only(['search', 'status', 'source_type', 'date_from', 'date_to']);
        $stats = $this->journalService->getStats();
        $entries = $this->journalService->list($filters);

        return view('accounting::journal-entries', compact('stats', 'entries', 'filters'));
    }

    /**
     * Show the form for creating a new journal entry.
     */
    public function create()
    {
        bpAuthorize('accounting.create');
        $accounts = $this->accountService->getAccountsGroupedByType();
        $nextEntryNumber = $this->journalService->generateEntryNumber();

        return view('accounting::journal-entries-create', compact('accounts', 'nextEntryNumber'));
    }

    /**
     * Store a newly created journal entry.
     */
    public function store(StoreJournalEntryRequest $request)
    {
        bpAuthorize('accounting.create');
        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = \App\Helpers\Upload::store($request->file('attachment'), 'journal-attachments');
        }

        $entry = $this->journalService->create($data);

        // If the user clicked "Post Entry" button
        if ($request->input('action') === 'post') {
            try {
                $this->journalService->post($entry);
                return redirect()->route('accounting.journal-entries.show', $entry)
                    ->with('success', __('Journal entry created and posted successfully.'));
            } catch (\RuntimeException $e) {
                return redirect()->route('accounting.journal-entries.show', $entry)
                    ->with('error', 'Entry saved as draft: ' . $e->getMessage());
            }
        }

        return redirect()->route('accounting.journal-entries.show', $entry)
            ->with('success', __('Journal entry saved as draft.'));
    }

    /**
     * Display the specified journal entry.
     */
    public function show(JournalEntry $entry)
    {
        bpAuthorize('accounting.view');
        $entry->load(['lines.account', 'creator', 'branch', 'postedByUser', 'voidedByUser']);

        return view('accounting::journal-entries-show', compact('entry'));
    }

    /**
     * Post a draft journal entry.
     */
    public function post(JournalEntry $entry)
    {
        bpAuthorize('accounting.edit');
        try {
            $this->journalService->post($entry);

            return back()->with('success', __('Journal entry posted successfully.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Correct the date of a draft or posted journal entry (e.g. a payment
     * that was recorded under the wrong date). Unlike amounts/accounts,
     * which require a void + reversing entry to change, the date is
     * corrected in place — but only via this explicit action, and every
     * change is captured by the entry's activity log.
     */
    public function updateDate(Request $request, JournalEntry $entry)
    {
        bpAuthorize('accounting.edit');
        $request->validate([
            'entry_date' => ['required', 'date', new \App\Rules\AllowedTransactionDate],
        ]);

        try {
            $this->journalService->updateDate($entry, $request->entry_date);

            return back()->with('success', __('Journal entry date updated.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Void a posted journal entry.
     */
    public function void(Request $request, JournalEntry $entry)
    {
        bpAuthorize('accounting.edit');
        $request->validate(['void_reason' => 'required|string|max:500']);

        try {
            $this->journalService->void($entry, $request->void_reason);

            return back()->with('success', __('Journal entry voided. A reversing entry has been created.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a draft journal entry.
     */
    public function destroy(JournalEntry $entry)
    {
        bpAuthorize('accounting.delete');
        try {
            $this->journalService->delete($entry);

            return redirect()->route('accounting.journal-entries')
                ->with('success', __('Draft entry deleted.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
