<?php

namespace Modules\Payment\Http\Requests\Concerns;

use Carbon\Carbon;
use Modules\Payment\Models\PaymentAccount;

/**
 * Shared overdraft guard for any form that takes money out of a payment
 * account. Kept in one place so transfers, payments and expense payments
 * cannot drift into disagreeing about what an account can afford.
 */
trait GuardsAccountBalance
{
    /**
     * @param  array<int|string, float>  $outflows  account id => amount leaving it
     */
    protected function guardAccountBalance($validator, array $outflows, ?string $date, string $errorKey): void
    {
        foreach ($outflows as $accountId => $leaving) {
            $leaving = (float) $leaving;

            if ($leaving <= 0) {
                continue;
            }

            $account = PaymentAccount::find($accountId);

            if (! $account) {
                continue;
            }

            // A back-dated entry has to be covered both on its own date and
            // today: affordable then but not now means inserting it
            // retroactively would overdraw the account as it currently stands.
            $onDate = $date ? $account->currentBalance($date) : $account->currentBalance();
            $today = $account->currentBalance();
            $available = min($onDate, $today);

            if ($leaving <= $available + 0.001) {
                continue;
            }

            $money = fn ($v) => currency_symbol() . ' ' . number_format($v, 2);

            // Name whichever limit actually bites, so the figure quoted never
            // contradicts the date it is quoted against.
            $validator->errors()->add($errorKey, $onDate <= $today
                ? __(':account held only :balance on :date. Taking out :leaving would overdraw it by :short.', [
                    'account' => $account->name,
                    'balance' => $money($onDate),
                    'date'    => Carbon::parse($date ?: now())->format('d M Y'),
                    'leaving' => $money($leaving),
                    'short'   => $money($leaving - $onDate),
                ])
                : __(':account holds only :balance today, so back-dating :leaving to :date would leave it overdrawn by :short now.', [
                    'account' => $account->name,
                    'balance' => $money($today),
                    'leaving' => $money($leaving),
                    'date'    => Carbon::parse($date)->format('d M Y'),
                    'short'   => $money($leaving - $today),
                ]));
        }
    }

    /**
     * Collapse a splits[] array into one total per account, so paying the same
     * account twice in a single submission is checked against the combined
     * amount rather than each line on its own.
     */
    protected function outflowsFromSplits(array $splits, ?int $fallbackAccountId, float $total): array
    {
        if (empty($splits)) {
            return $fallbackAccountId ? [$fallbackAccountId => $total] : [];
        }

        $outflows = [];

        foreach ($splits as $split) {
            $id = $split['payment_account_id'] ?? $fallbackAccountId;

            if (! $id) {
                continue;
            }

            $outflows[$id] = ($outflows[$id] ?? 0) + (float) ($split['amount'] ?? 0);
        }

        return $outflows;
    }
}
