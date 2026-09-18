<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Models\Investor;
use Modules\Accounting\Models\InvestorCapital;
use Modules\Accounting\Models\InvestorDistribution;
use Modules\Accounting\Services\InvestmentService;
use Modules\Payment\Models\PaymentAccount;

class InvestmentController extends Controller
{
    public function __construct(
        private readonly InvestmentService $service,
    ) {}

    // ─── Dashboard ───

    public function dashboard()
    {
        bpAuthorize('accounting.view');
        return view('accounting::investment.dashboard', $this->service->dashboard());
    }

    // ─── Investors CRUD ───

    public function investorsIndex(Request $request)
    {
        bpAuthorize('accounting.view');
        $type = $request->input('type');
        $investors = Investor::query()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($request->input('search'), fn ($q, $s) =>
                $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")
                                       ->orWhere('phone', 'like', "%{$s}%")
                                       ->orWhere('email', 'like', "%{$s}%")))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $totalShares = (float) Investor::active()->shareholders()->sum('shares_owned');

        return view('accounting::investment.investors-index', compact('investors', 'type', 'totalShares'));
    }

    public function investorsCreate()
    {
        bpAuthorize('accounting.create');
        return view('accounting::investment.investors-create');
    }

    public function investorsStore(Request $request)
    {
        bpAuthorize('accounting.create');
        $data = $this->validateInvestor($request);
        Investor::create($data);

        return redirect()->route('investment.investors.index')
            ->with('success', __('Investor added.'));
    }

    public function investorsShow(Investor $investor)
    {
        bpAuthorize('accounting.view');
        $investor->load([
            'capital' => fn ($q) => $q->with('paymentAccount')->latest('transaction_date'),
            'distributions' => fn ($q) => $q->with('paymentAccount')->latest('distribution_date'),
        ]);

        $totals = [
            'net_capital'  => $investor->netCapital(),
            'distributed'  => $investor->totalDistributed(),
            'share_pct'    => $investor->effectiveSharePct(),
        ];

        return view('accounting::investment.investors-show', compact('investor', 'totals'));
    }

    public function investorsEdit(Investor $investor)
    {
        bpAuthorize('accounting.edit');
        return view('accounting::investment.investors-edit', compact('investor'));
    }

    public function investorsUpdate(Request $request, Investor $investor)
    {
        bpAuthorize('accounting.edit');
        $data = $this->validateInvestor($request);
        $investor->update($data);

        return redirect()->route('investment.investors.show', $investor)
            ->with('success', __('Investor updated.'));
    }

    public function investorsDestroy(Investor $investor)
    {
        bpAuthorize('accounting.delete');
        // Refuse to drop an investor that still has capital or distributions —
        // their GL entries would orphan and rebalancing gets ugly.
        if ($investor->capital()->exists() || $investor->distributions()->exists()) {
            return back()->with('error', __('Cannot delete an investor with capital or distribution history. Mark inactive instead.'));
        }
        $investor->delete();
        return redirect()->route('investment.investors.index')->with('success', __('Investor removed.'));
    }

    public function investorsToggleStatus(Investor $investor): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('accounting.edit');
        $this->service->toggleInvestorStatus($investor);

        return response()->json([
            'success'   => true,
            'is_active' => $investor->is_active,
            'message'   => __('Status updated.'),
        ]);
    }

    private function validateInvestor(Request $request): array
    {
        $data = $request->validate([
            'type'              => 'required|in:shareholder,investor',
            'name'              => 'required|string|max:150',
            'phone'             => 'nullable|string|max:30',
            'email'             => 'nullable|email|max:150',
            'address'           => 'nullable|string|max:500',
            'nid_or_tin'        => 'nullable|string|max:50',
            'join_date'         => 'required|date',
            'shares_owned'      => 'nullable|numeric|min:0|required_if:type,shareholder',
            'profit_share_pct'  => 'nullable|numeric|min:0|max:100|required_if:type,investor',
            'is_active'         => 'nullable|boolean',
            'notes'             => 'nullable|string|max:2000',
        ]);

        // Zero out the field that doesn't apply to this type, so reports stay clean.
        if ($data['type'] === 'shareholder') {
            $data['profit_share_pct'] = null;
        } else {
            $data['shares_owned'] = null;
        }
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        return $data;
    }

    // ─── Capital injection / withdrawal ───

    public function capitalCreate(Request $request)
    {
        bpAuthorize('accounting.create');
        $investors = Investor::active()->orderBy('name')->get();
        $accounts = PaymentAccount::where('is_active', true)->orderBy('name')->get();
        $preselectInvestor = $request->input('investor_id');
        return view('accounting::investment.capital-create', compact('investors', 'accounts', 'preselectInvestor'));
    }

    public function capitalStore(Request $request)
    {
        bpAuthorize('accounting.create');
        $data = $request->validate([
            'investor_id'        => 'required|exists:investors,id',
            'type'               => 'required|in:inject,withdraw',
            'transaction_date'   => 'required|date',
            'amount'             => 'required|numeric|min:0.01',
            'payment_account_id' => 'required|exists:payment_accounts,id',
            'reference'          => 'nullable|string|max:100',
            'note'               => 'nullable|string|max:500',
        ]);

        $this->service->recordCapital($data);

        return redirect()->route('investment.investors.show', $data['investor_id'])
            ->with('success', __('Capital transaction recorded.'));
    }

    public function capitalDestroy(InvestorCapital $capital)
    {
        bpAuthorize('accounting.delete');
        $investorId = $capital->investor_id;
        $this->service->deleteCapital($capital);
        return redirect()->route('investment.investors.show', $investorId)
            ->with('success', __('Capital transaction reversed.'));
    }

    // ─── Profit distributions ───

    public function distributionsIndex(Request $request)
    {
        bpAuthorize('accounting.view');
        $distributions = InvestorDistribution::with(['investor', 'paymentAccount'])
            ->when($request->input('batch'), fn ($q, $b) => $q->where('batch_ref', $b))
            ->latest('distribution_date')
            ->paginate(20)
            ->withQueryString();

        return view('accounting::investment.distributions-index', compact('distributions'));
    }

    public function distributionsCreate(Request $request)
    {
        bpAuthorize('accounting.create');
        // Default to the current month so the period inputs are never empty.
        $from = $request->input('period_start') ?: now()->startOfMonth()->toDateString();
        $to   = $request->input('period_end') ?: now()->toDateString();
        $preview = null;

        if ($request->filled(['period_start', 'period_end'])) {
            $preview = $this->service->previewDistribution($from, $to);
        }

        $accounts = PaymentAccount::where('is_active', true)->orderBy('name')->get();

        return view('accounting::investment.distributions-create', compact('from', 'to', 'preview', 'accounts'));
    }

    public function distributionsStore(Request $request)
    {
        bpAuthorize('accounting.create');
        $data = $request->validate([
            'period_start'       => 'required|date',
            'period_end'         => 'required|date|after_or_equal:period_start',
            'distribution_date'  => 'required|date',
            'payment_account_id' => 'required|exists:payment_accounts,id',
            'net_profit_snapshot'=> 'required|numeric',
            'note'               => 'nullable|string|max:500',
            'rows'               => 'required|array|min:1',
            'rows.*.investor_id' => 'required|exists:investors,id',
            'rows.*.share_pct'   => 'required|numeric|min:0',
            'rows.*.amount'      => 'required|numeric|min:0',
        ]);

        $created = $this->service->postDistribution($data);

        return redirect()->route('investment.distributions.index')
            ->with('success', __(':n distribution(s) posted.', ['n' => count($created)]));
    }

    public function distributionsDestroy(InvestorDistribution $distribution)
    {
        bpAuthorize('accounting.delete');
        $this->service->deleteDistribution($distribution);
        return redirect()->route('investment.distributions.index')
            ->with('success', __('Distribution reversed.'));
    }
}
