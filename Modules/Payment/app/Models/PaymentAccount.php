<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAccount extends Model
{
    use SoftDeletes, LogsActivity;

    protected string $activityLogName = 'payment_accounts';

    protected $fillable = [
        'name', 'account_type', 'is_default', 'is_active',
        'mobile_bank_name', 'mobile_number',
        'bank_id', 'bank_account_type', 'bank_account_name',
        'bank_account_number', 'bank_branch',
        'card_type', 'card_holder_name', 'card_number',
        'service_charge', 'opening_balance', 'created_by',
    ];

    protected $casts = [
        'is_default'      => 'boolean',
        'is_active'       => 'boolean',
        'service_charge'  => 'decimal:2',
        'opening_balance' => 'decimal:2',
    ];

    // ── Relationships ──

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function balanceTransfersOut(): HasMany
    {
        return $this->hasMany(BalanceTransfer::class, 'from_account_id');
    }

    public function balanceTransfersIn(): HasMany
    {
        return $this->hasMany(BalanceTransfer::class, 'to_account_id');
    }

    // ── Balance ──

    /**
     * Account balance, summing every source of cash movement that names this
     * account. Pass $asOf to get the balance at the close of that date, which
     * is what a back-dated entry has to be checked against. Mirrors the
     * running-balance math in PaymentAccountController::ledger so the list
     * total agrees with the ledger — any source added to one must be added to
     * the other.
     */
    public function currentBalance(?string $asOf = null): float
    {
        $upto = fn ($column) => fn ($q) => $asOf ? $q->where($column, '<=', $asOf) : $q;

        $received = (float) Payment::where('payment_account_id', $this->id)
            ->where('direction', 'receive')->tap($upto('payment_date'))->sum('amount');
        $paid = (float) Payment::where('payment_account_id', $this->id)
            ->where('direction', 'pay')->tap($upto('payment_date'))->sum('amount');
        $transfersIn = (float) BalanceTransfer::where('to_account_id', $this->id)
            ->tap($upto('date'))->sum('amount');
        $transfersOut = (float) BalanceTransfer::where('from_account_id', $this->id)
            ->tap($upto('date'))
            ->selectRaw('COALESCE(SUM(amount + charge), 0) as t')->value('t');
        $charges = (float) AccountCharge::where('payment_account_id', $this->id)
            ->tap($upto('date'))->sum('amount');
        $capitalIn = (float) DB::table('investor_capital')
            ->where('payment_account_id', $this->id)->where('type', 'inject')
            ->tap($upto('transaction_date'))->sum('amount');
        $capitalOut = (float) DB::table('investor_capital')
            ->where('payment_account_id', $this->id)->where('type', 'withdraw')
            ->tap($upto('transaction_date'))->sum('amount');
        $distributions = (float) DB::table('investor_distributions')
            ->where('payment_account_id', $this->id)
            ->tap($upto('distribution_date'))->sum('distribution_amount');
        $expenses = (float) DB::table('expenses')
            ->where('payment_account_id', $this->id)->whereNull('deleted_at')
            ->tap($upto('expense_date'))->sum('paid_amount');

        $loanIn = (float) DB::table('personal_loan_transactions')
            ->where('payment_account_id', $this->id)->whereNull('deleted_at')
            ->whereIn('type', ['repayment', 'loan_taken'])->tap($upto('txn_date'))->sum('amount');
        $loanOut = (float) DB::table('personal_loan_transactions')
            ->where('payment_account_id', $this->id)->whereNull('deleted_at')
            ->whereIn('type', ['disbursement', 'loan_return'])->tap($upto('txn_date'))->sum('amount');
        $courierIn = (float) DB::table('courier_withdrawals')
            ->where('payment_account_id', $this->id)->whereNull('deleted_at')
            ->tap($upto('withdrawal_date'))->sum('amount');
        $assets = (float) DB::table('assets')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('payment_account_id', $this->id);
                if ($this->isDefaultCashAccount()) {
                    $q->orWhereNull('payment_account_id');
                }
            })
            ->tap($upto('purchase_date'))
            ->sum('paid_amount');
        $assetPayments = (float) DB::table('asset_payments')
            ->where('payment_account_id', $this->id)->whereNull('deleted_at')
            ->tap($upto('payment_date'))->sum('amount');
        $payroll = $this->isPrimaryForType()
            ? (float) DB::table('payroll_items as pi')
                ->join('payrolls as pr', 'pr.id', '=', 'pi.payroll_id')
                ->whereNull('pr.deleted_at')
                ->where('pi.payment_status', 'paid')
                ->where('pi.payment_method', $this->account_type)
                ->when($asOf, fn ($q) => $q->whereRaw('DATE(COALESCE(pi.approved_at, pi.updated_at)) <= ?', [$asOf]))
                ->sum('pi.net_salary')
            : 0.0;

        return (float) $this->opening_balance + $received - $paid + $transfersIn - $transfersOut
            - $charges + $capitalIn - $capitalOut - $distributions - $expenses
            + $loanIn - $loanOut + $courierIn - $assets - $assetPayments - $payroll;
    }

    /**
     * What can actually leave the account for an entry dated $on. A back-dated
     * transfer has to be covered both then and now: covered then but not now
     * means inserting it retroactively would overdraw the account today.
     */
    public function availableBalance(?string $on = null): float
    {
        return $on ? min($this->currentBalance($on), $this->currentBalance()) : $this->currentBalance();
    }

    /**
     * Whether this account is the one a bare account_type resolves to. Several
     * modules record only a type ('cash', 'bank') rather than an account id;
     * that type maps to the default account of the type, or to the only one
     * when there is no default. Accounts that lose the tie are excluded so a
     * type-only movement is never counted against two accounts at once.
     */
    public function isPrimaryForType(): bool
    {
        $peers = static::where('account_type', $this->account_type)->get();

        if ($peers->count() <= 1) {
            return true;
        }

        $default = $peers->firstWhere('is_default', true);

        return $default ? (int) $default->id === (int) $this->id : false;
    }

    /**
     * Modules that leave payment_account_id null fall back to cash — see
     * AccountingIntegrationService::cashAccountCodeFor() — so those movements
     * belong to whichever account a bare 'cash' resolves to.
     */
    public function isDefaultCashAccount(): bool
    {
        return $this->account_type === 'cash' && $this->isPrimaryForType();
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('account_type', $type);
    }

    // ── Accessors ──

    public function getDisplayNameAttribute(): string
    {
        return match ($this->account_type) {
            'mobile_banking' => ($this->mobile_bank_name ?? 'Mobile') . ' - ' . ($this->mobile_number ?? ''),
            'bank'           => ($this->bank?->name ?? 'Bank') . ' - ' . ($this->bank_account_number ?? ''),
            'card'           => ($this->card_type ?? 'Card') . ' - ' . substr($this->card_number ?? '', -4),
            default          => $this->name,
        };
    }

    /**
     * Account name plus the detail that identifies it — bank and account
     * number, wallet and mobile number, card and last four. Several accounts
     * can share a name (the same person's bank and bKash accounts), so the
     * name alone is ambiguous in a ledger. Falls back to the bare name when
     * the type carries no extra detail (cash).
     */
    public function getLedgerLabelAttribute(): string
    {
        $detail = match ($this->account_type) {
            'mobile_banking' => trim(($this->mobile_bank_name ?: 'Mobile') . ' ' . ($this->mobile_number ?: '')),
            'bank'           => trim(($this->bank?->name ?: 'Bank') . ' ' . ($this->bank_account_number ?: '')),
            'card'           => trim(($this->card_type ?: 'Card') . ' ' . ($this->card_number ? '****' . substr($this->card_number, -4) : '')),
            default          => '',
        };

        return $detail === '' ? $this->name : $this->name . ' — ' . $detail;
    }

    public function getAccountTypeLabelAttribute(): string
    {
        return match ($this->account_type) {
            'cash'           => 'Cash',
            'mobile_banking' => 'Mobile Banking',
            'bank'           => 'Bank Account',
            'card'           => 'Card',
            default          => ucfirst($this->account_type),
        };
    }
}
