<?php

namespace Modules\Asset\Services;

use App\Helpers\Upload;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\AccountingIntegrationService;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Asset\Models\Asset;
use Modules\Asset\Models\AssetCategory;
use Modules\Asset\Models\AssetMaintenance;
use Modules\Asset\Models\AssetPayment;

class AssetService
{
    public function __construct(
        private readonly AccountingIntegrationService $accountingService,
        private readonly JournalEntryService $journalService,
    ) {}


    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Asset::with('category', 'branch')
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('asset_code', 'like', "%{$search}%")
                      ->orWhere('serial_number', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['category_id'] ?? null, fn($q, $c) => $q->where('asset_category_id', $c))
            ->when($filters['branch_id'] ?? null, fn($q, $b) => $q->where('branch_id', $b))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): Asset
    {
        return Asset::with('category', 'branch', 'maintenances', 'creator', 'payments.paymentAccount', 'paymentAccount')->findOrFail($id);
    }

    public function create(array $data): Asset
    {
        $data['asset_code'] = $this->generateAssetCode();
        $data['created_by'] = Auth::id();
        $data['current_value'] = $data['purchase_price'];

        // Payment split: paid now vs left on credit. A blank paid amount means
        // "fully paid" ONLY when a payment account is selected (the money has
        // to come from somewhere). Blank paid + no account = unpaid, all due.
        // Paid > 0 without an account is rejected by StoreAssetRequest.
        $price = (float) $data['purchase_price'];
        $paymentAccountId = $data['payment_account_id'] ?? null;
        $paidInput = $data['paid_amount'] ?? null;
        $paid = ($paidInput !== null && $paidInput !== '')
            ? max(0, min((float) $paidInput, $price))
            : ($paymentAccountId ? $price : 0.0);
        $due = round($price - $paid, 2);

        $data['paid_amount'] = $paid;
        $data['due_amount'] = $due;
        $data['payment_status'] = $due <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

        if (isset($data['photo']) && $data['photo']) {
            $data['photo'] = Upload::store($data['photo'], 'assets');
        }

        // Copy depreciation settings from category if not set
        if (!empty($data['asset_category_id'])) {
            $category = AssetCategory::find($data['asset_category_id']);
            if ($category) {
                $data['depreciation_method'] = $data['depreciation_method'] ?? $category->depreciation_method;
                $data['useful_life_years'] = $data['useful_life_years'] ?? $category->useful_life_years;
            }
        }

        $asset = Asset::create($data);

        if ($price > 0) {
            try {
                $this->accountingService->recordAssetAcquisition($asset, $paid, $paymentAccountId);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to record asset acquisition JE {$asset->asset_code}: {$e->getMessage()}");
            }
        }

        return $asset;
    }

    /**
     * Record a payment against an asset's outstanding purchase due. Pay any
     * amount on any date; posts a balanced journal entry and updates the
     * asset's paid/due/status.
     */
    public function recordPayment(Asset $asset, array $data): AssetPayment
    {
        $amount = (float) $data['amount'];
        $due = (float) $asset->due_amount;

        if ($amount <= 0) {
            throw new \RuntimeException('Payment amount must be greater than zero.');
        }
        if ($amount > $due + 0.01) {
            throw new \RuntimeException('Payment (' . currency_symbol() . ' ' . number_format($amount) . ') exceeds the outstanding due (' . currency_symbol() . ' ' . number_format($due) . ').');
        }

        return DB::transaction(function () use ($asset, $amount, $data) {
            $payment = AssetPayment::create([
                'asset_id'           => $asset->id,
                'payment_number'     => $this->generatePaymentNumber(),
                'amount'             => $amount,
                'payment_account_id' => $data['payment_account_id'] ?? $asset->payment_account_id,
                'payment_date'       => $data['payment_date'] ?? now()->toDateString(),
                'reference'          => $data['reference'] ?? null,
                'note'               => $data['note'] ?? null,
                'created_by'         => Auth::id(),
            ]);

            try {
                $je = $this->accountingService->recordAssetPayment($payment->load('asset'));
                $payment->update(['journal_entry_id' => $je->id]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to record asset payment JE {$payment->payment_number}: {$e->getMessage()}");
            }

            $newPaid = round((float) $asset->paid_amount + $amount, 2);
            $newDue = round((float) $asset->purchase_price - $newPaid, 2);
            $asset->update([
                'paid_amount'    => $newPaid,
                'due_amount'     => max(0, $newDue),
                'payment_status' => $newDue <= 0 ? 'paid' : 'partial',
            ]);

            return $payment;
        });
    }

    /**
     * Per-asset transaction ledger with two running balances: outstanding due
     * (purchase → payments) and book value (purchase → depreciation → disposal).
     */
    public function assetLedger(Asset $asset): array
    {
        $rows = [];

        // 1) Purchase
        $dueBalance = round((float) $asset->purchase_price - (float) $asset->paid_amount, 2);
        $bookValue = (float) $asset->purchase_price;
        $rows[] = [
            'date'        => $asset->purchase_date,
            'particulars' => 'Purchased' . ($asset->vendor_name ? ' from ' . $asset->vendor_name : ''),
            'debit'       => (float) $asset->purchase_price,
            'credit'      => (float) $asset->paid_amount,
            'type'        => 'purchase',
            'due_balance' => $dueBalance,
            'book_value'  => $bookValue,
        ];

        // 2) Payments
        foreach ($asset->payments as $payment) {
            $dueBalance = round($dueBalance - (float) $payment->amount, 2);
            $rows[] = [
                'date'        => $payment->payment_date,
                'particulars' => 'Payment ' . $payment->payment_number
                    . ($payment->reference ? ' (' . $payment->reference . ')' : ''),
                'debit'       => 0.0,
                'credit'      => (float) $payment->amount,
                'type'        => 'payment',
                'due_balance' => max(0, $dueBalance),
                'book_value'  => $bookValue,
            ];
        }

        // 3) Depreciation entries (from posted journal entries for this asset)
        $depEntries = \Modules\Accounting\Models\JournalEntry::where('source_type', 'asset_depreciation')
            ->where('source_id', $asset->id)
            ->whereNull('deleted_at')
            ->orderBy('entry_date')
            ->get(['entry_date', 'total_amount', 'description']);
        foreach ($depEntries as $dep) {
            $bookValue = round($bookValue - (float) $dep->total_amount, 2);
            $rows[] = [
                'date'        => $dep->entry_date,
                'particulars' => $dep->description ?: 'Depreciation',
                'debit'       => 0.0,
                'credit'      => 0.0,
                'depreciation' => (float) $dep->total_amount,
                'type'        => 'depreciation',
                'due_balance' => max(0, $dueBalance),
                'book_value'  => max(0, $bookValue),
            ];
        }

        // 4) Disposal
        if ($asset->status === 'disposed' && $asset->disposed_at) {
            $rows[] = [
                'date'        => $asset->disposed_at,
                'particulars' => 'Asset disposed',
                'debit'       => 0.0,
                'credit'      => 0.0,
                'type'        => 'disposal',
                'due_balance' => max(0, $dueBalance),
                'book_value'  => 0.0,
            ];
        }

        // Sort chronologically while keeping the purchase first.
        usort($rows, function ($a, $b) {
            return strcmp((string) $a['date'], (string) $b['date']);
        });

        return $rows;
    }

    private function generatePaymentNumber(): string
    {
        $year = now()->format('Y');
        $count = AssetPayment::withTrashed()->whereYear('created_at', $year)->count();

        return 'ASP-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    public function update(Asset $asset, array $data): Asset
    {
        if (isset($data['photo']) && $data['photo']) {
            if ($asset->photo) {
                Upload::delete($asset->photo);
            }
            $data['photo'] = Upload::store($data['photo'], 'assets');
        } else {
            unset($data['photo']);
        }

        // paid_amount / payment_account_id are creation-time concerns; later
        // payments go through recordPayment(). Never mass-update them here.
        unset($data['paid_amount'], $data['payment_account_id']);

        $asset->update($data);

        // Editing the price changes what's still owed — keep due/status honest.
        if (array_key_exists('purchase_price', $data)) {
            $due = round((float) $asset->purchase_price - (float) $asset->paid_amount, 2);
            $asset->update([
                'due_amount'     => max(0, $due),
                'payment_status' => $due <= 0 ? 'paid' : ((float) $asset->paid_amount > 0 ? 'partial' : 'unpaid'),
            ]);
        }

        return $asset->fresh();
    }

    public function dispose(Asset $asset, float $disposalValue = 0): void
    {
        try {
            $this->accountingService->recordAssetDisposal($asset, $disposalValue);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to record asset disposal JE {$asset->asset_code}: {$e->getMessage()}");
        }

        // Snapshot the book value at the moment of disposal and preserve
        // current_value (don't zero it) so historical reporting stays intact.
        $asset->update([
            'status' => 'disposed',
            'disposed_at' => now(),
            'disposal_book_value' => $asset->current_value,
        ]);
    }

    /**
     * Delete an asset along with its payment history and GL impact.
     *
     * Unlike Expense, an asset's payments are real rows (AssetPayment) — the
     * table's asset_id has a DB cascadeOnDelete(), but Asset uses SoftDeletes
     * so a normal delete() never issues the real SQL DELETE that cascade
     * needs; the payment rows were simply left behind, still linked to a
     * "gone" asset. Each of the purchase, every payment, depreciation runs
     * and any disposal also posted its own journal entry — those must be
     * voided (reversed), never hard-deleted, so the GL keeps a clean trail.
     */
    public function delete(Asset $asset): void
    {
        DB::transaction(function () use ($asset) {
            JournalEntry::where('source_id', $asset->id)
                ->whereIn('source_type', ['asset_acquisition', 'asset_payment', 'asset_depreciation', 'asset_disposal'])
                ->where('status', 'posted')
                ->get()
                ->each(function (JournalEntry $je) use ($asset) {
                    try {
                        $this->journalService->void($je, "Asset deleted: {$asset->asset_code}");
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Failed to void journal entry for asset delete: {$e->getMessage()}");
                    }
                });

            $asset->payments()->delete();

            if ($asset->photo) {
                Upload::delete($asset->photo);
            }

            $asset->delete();
        });
    }

    public function runDepreciation(string $month): int
    {
        $count = 0;
        $monthEnd = Carbon::parse($month . '-01')->endOfMonth();

        $assets = Asset::depreciable()
            ->where(function ($q) use ($month) {
                $q->whereNull('last_depreciation_date')
                  ->orWhere('last_depreciation_date', '<', $month . '-01');
            })
            ->get();

        DB::transaction(function () use ($assets, $monthEnd, &$count) {
            foreach ($assets as $asset) {
                $depreciable = $asset->purchase_price - $asset->salvage_value;
                if ($depreciable <= 0 || $asset->current_value <= $asset->salvage_value) {
                    continue;
                }

                if ($asset->depreciation_method === 'straight_line') {
                    $annualDep = $depreciable / $asset->useful_life_years;
                    $monthlyDep = round($annualDep / 12, 2);
                } else {
                    $rate = (1 / $asset->useful_life_years) * 2; // Double declining
                    $annualDep = $asset->current_value * $rate;
                    $monthlyDep = round($annualDep / 12, 2);
                }

                // Don't depreciate below salvage value
                $maxDep = $asset->current_value - $asset->salvage_value;
                $monthlyDep = min($monthlyDep, $maxDep);

                if ($monthlyDep <= 0) {
                    continue;
                }

                $asset->update([
                    'accumulated_depreciation' => $asset->accumulated_depreciation + $monthlyDep,
                    'current_value' => $asset->current_value - $monthlyDep,
                    'last_depreciation_date' => $monthEnd->toDateString(),
                ]);

                try {
                    $this->accountingService->recordDepreciation($asset, $monthlyDep, $monthEnd->format('Y-m'));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to record depreciation JE for {$asset->asset_code}: {$e->getMessage()}");
                }

                $count++;
            }
        });

        return $count;
    }

    public function recordMaintenance(Asset $asset, array $data): AssetMaintenance
    {
        $data['asset_id'] = $asset->id;
        $data['created_by'] = Auth::id();

        $maintenance = AssetMaintenance::create($data);

        if (!empty($data['next_maintenance_date'])) {
            $asset->update(['next_maintenance_date' => $data['next_maintenance_date']]);
        }

        return $maintenance;
    }

    public function getStats(): array
    {
        return [
            'total_assets' => Asset::count(),
            'active' => Asset::active()->count(),
            'total_value' => Asset::active()->sum('current_value'),
            'total_depreciation' => Asset::sum('accumulated_depreciation'),
            'maintenance_due' => Asset::active()
                ->whereNotNull('next_maintenance_date')
                ->where('next_maintenance_date', '<=', Carbon::today()->addDays(7)->toDateString())
                ->count(),
        ];
    }

    // Category management
    public function listCategories(): \Illuminate\Support\Collection
    {
        return AssetCategory::withCount('assets')->get();
    }

    public function createCategory(array $data): AssetCategory
    {
        return AssetCategory::create($data);
    }

    public function updateCategory(AssetCategory $category, array $data): AssetCategory
    {
        $category->update($data);
        return $category->fresh();
    }

    private function generateAssetCode(): string
    {
        $last = Asset::withTrashed()
            ->where('asset_code', 'like', 'AST-%')
            ->orderByDesc('id')
            ->value('asset_code');

        $nextNum = 1;
        if ($last && preg_match('/AST-(\d+)/', $last, $m)) {
            $nextNum = (int) $m[1] + 1;
        }

        return 'AST-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
}
