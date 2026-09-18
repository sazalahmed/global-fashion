<?php

namespace Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Payment\Http\Requests\Concerns\GuardsAccountBalance;

class StorePaymentRequest extends FormRequest
{
    use GuardsAccountBalance;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Drop allocation rows the user isn't actually paying toward before
     * validation. The invoice picker submits a row for every listed document:
     * unchecked rows arrive without an allocatable_id, and checked rows that
     * the auto-distribution zeroed out (payment fully consumed by earlier
     * invoices) arrive with amount = 0. Neither is a real allocation, so keep
     * only rows that have an invoice id AND a positive amount or discount —
     * a pure write-off row has amount = 0 with the whole due in discount, so
     * amount alone can't gate this the way it used to.
     */
    protected function prepareForValidation(): void
    {
        $allocations = $this->input('allocations');

        if (is_array($allocations)) {
            $kept = array_filter($allocations, function ($alloc) {
                if (!is_array($alloc) || empty($alloc['allocatable_id'])) {
                    return false;
                }

                $amount = is_numeric($alloc['amount'] ?? null) ? (float) $alloc['amount'] : 0;
                $discount = is_numeric($alloc['discount_amount'] ?? null) ? (float) $alloc['discount_amount'] : 0;

                // Matched to the 0.01 minimum below rather than a bare
                // "> 0". Auto-distribution can leave a sub-paisa residual
                // on the next invoice — a fraction of a poisha is not an
                // allocation, and letting it through failed the whole form
                // with "allocations.1.amount must be at least 0.01".
                return $amount >= 0.01 || $discount >= 0.01;
            });

            $this->merge(['allocations' => array_values($kept)]);
        }
    }

    public function rules(): array
    {
        return [
            'direction'          => 'required|in:receive,pay',
            'party_type'         => 'required|in:customer,supplier,employee',
            'party_id'           => 'required|integer|min:1',
            'payment_type'       => 'required|in:against_invoice,advance_payment,advance_return',
            // 0 is valid — a pure due-discount write-off collects no cash at
            // all. withValidator() below still rejects a payment that is
            // amount=0 with no discount either (nothing actually happened).
            'amount'             => 'required|numeric|min:0',
            'payment_method'     => 'nullable|string|max:50',
            'payment_account_id' => 'required_without:splits|nullable|exists:payment_accounts,id',
            'payment_date'       => ['required', 'date', new \App\Rules\AllowedTransactionDate],
            'reference'          => 'nullable|string|max:100',
            'note'               => 'nullable|string|max:2000',
            'branch_id'          => 'nullable|exists:branches,id',

            // Allocations (optional — for against_invoice payments)
            'allocations'                  => 'nullable|array',
            'allocations.*.allocatable_type' => 'required_with:allocations|string|max:100',
            'allocations.*.allocatable_id'   => 'required_with:allocations|integer|min:1',
            // A row can be amount-only, discount-only, or both — the pair is
            // enforced together in withValidator(), not with min:0.01 here.
            'allocations.*.amount'           => 'required_with:allocations|numeric|min:0',
            'allocations.*.discount_amount'  => 'nullable|numeric|min:0',

            // Split payments (optional — pay with multiple methods). A split
            // may legitimately be 0 (the sole split row on a pure discount
            // write-off) — same reasoning as 'amount' above.
            'splits'                    => 'nullable|array|min:1',
            'splits.*.amount'           => 'required_with:splits|numeric|min:0',
            'splits.*.payment_account_id' => 'required_with:splits|exists:payment_accounts,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $allocations = $this->input('allocations', []);
            $splits = $this->input('splits', []);
            $paymentAmount = (float) $this->input('amount', 0);
            $discountTotal = collect($allocations)->sum(fn ($a) => (float) ($a['discount_amount'] ?? 0));

            // amount is now allowed to be 0 (pure write-off), but amount = 0
            // with no discount either records nothing — reject it explicitly
            // rather than silently creating an empty payment.
            if ($paymentAmount <= 0 && $discountTotal <= 0) {
                $validator->errors()->add('amount', 'Enter a payment amount or a discount amount.');
            }

            if (!empty($allocations)) {
                $allocTotal = collect($allocations)->sum('amount');
                if (bccomp((string) $allocTotal, (string) $paymentAmount, 2) > 0) {
                    $validator->errors()->add('allocations', 'Total allocation amount cannot exceed payment amount.');
                }

                // Backend never trusts the frontend's "max" cap — re-check
                // amount + discount against each document's live due_amount,
                // fetched fresh so a stale page (or a tampered request) can't
                // write off more than is actually outstanding.
                foreach ($allocations as $i => $alloc) {
                    $type = $alloc['allocatable_type'] ?? null;
                    $id = $alloc['allocatable_id'] ?? null;
                    if (!$type || !$id || !class_exists($type)) {
                        continue;
                    }

                    $document = $type::find($id);
                    if (!$document || !isset($document->due_amount)) {
                        continue;
                    }

                    $claimed = (float) ($alloc['amount'] ?? 0) + (float) ($alloc['discount_amount'] ?? 0);
                    if ($claimed - (float) $document->due_amount > 0.01) {
                        $validator->errors()->add(
                            "allocations.{$i}.amount",
                            'Payment plus discount cannot exceed the outstanding due for this invoice.'
                        );
                    }
                }
            }

            if (!empty($splits)) {
                $splitTotal = collect($splits)->sum('amount');
                if (abs($splitTotal - $paymentAmount) > 0.01) {
                    $validator->errors()->add('splits', 'Split amounts must equal the total payment amount (' . currency_symbol() . ' ' . number_format($paymentAmount) . ').');
                }
            }

            // Only an outgoing payment draws on an account; a receipt adds to
            // it. Checked after the rules above so a split set that does not
            // add up is reported as that rather than as an overdraft.
            if ($validator->errors()->isEmpty() && $this->input('direction') === 'pay') {
                $this->guardAccountBalance(
                    $validator,
                    $this->outflowsFromSplits($splits, $this->input('payment_account_id'), $paymentAmount),
                    $this->input('payment_date'),
                    'amount',
                );
            }
        });
    }
}
