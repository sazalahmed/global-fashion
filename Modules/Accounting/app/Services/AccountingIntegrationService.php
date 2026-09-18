<?php

namespace Modules\Accounting\Services;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;

class AccountingIntegrationService
{
    public function __construct(
        private readonly JournalEntryService $journalService,
    ) {}

    /**
     * Record a sale in the general ledger.
     * DR Accounts Receivable / CR Sales Revenue + CR VAT Payable (if tax),
     * plus DR Cost of Goods Sold / CR Inventory for the goods that left.
     */
    public function recordSale($sale): JournalEntry
    {
        $lines = [];

        // DR Accounts Receivable
        $lines[] = [
            'account_id'    => $this->getAccountId('1010'),
            'debit_amount'  => $sale->grand_total,
            'credit_amount' => 0,
            'description'   => "Sale: {$sale->invoice_number}",
        ];

        // CR Sales Revenue (excl. tax)
        $revenueAmount = $sale->grand_total - ($sale->tax_amount ?? 0);
        $lines[] = [
            'account_id'    => $this->getAccountId('4001'),
            'debit_amount'  => 0,
            'credit_amount' => $revenueAmount,
            'description'   => "Revenue: {$sale->invoice_number}",
        ];

        // CR VAT Payable (if applicable)
        if (($sale->tax_amount ?? 0) > 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId('2010'),
                'debit_amount'  => 0,
                'credit_amount' => $sale->tax_amount,
                'description'   => "VAT on sale: {$sale->invoice_number}",
            ];
        }

        // Relieve inventory for what was actually shipped. Without this pair
        // Inventory grows for every purchase and is never reduced by a sale,
        // so the balance sheet overstates stock and gross profit is meaningless.
        $costOfGoods = $this->saleCostOfGoods($sale);
        if ($costOfGoods > 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId('5001'),
                'debit_amount'  => $costOfGoods,
                'credit_amount' => 0,
                'description'   => "COGS: {$sale->invoice_number}",
            ];
            $lines[] = [
                'account_id'    => $this->getAccountId('1020'),
                'debit_amount'  => 0,
                'credit_amount' => $costOfGoods,
                'description'   => "Inventory relieved: {$sale->invoice_number}",
            ];
        }

        return $this->journalService->createFromSource(
            'sale', $sale->id, $lines,
            "Sale: {$sale->invoice_number}",
            $sale->invoice_number,
            $sale->sale_date ?? $sale->created_at,
        );
    }

    /**
     * Cost of the goods this sale shipped, priced from the variant's cost
     * (falling back to the product's) against the quantities on the sale.
     *
     * Deliberately NOT read from stock_ledger.unit_cost: despite the name that
     * column is written with the item's SELLING price by the sale stock-out
     * path, so using it would make COGS equal revenue and gross profit zero.
     * Service lines are skipped — they consume no inventory.
     *
     * Caveat: cost_price is current cost, not cost as at the sale date, so a
     * repriced product shifts the cost of historical sales.
     */
    private function saleCostOfGoods($sale): float
    {
        $cost = (float) DB::table('sale_items as si')
            ->join('products as p', 'p.id', '=', 'si.product_id')
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'si.variant_id')
            ->where('si.sale_id', $sale->id)
            ->where('p.product_type', '!=', 'service')
            ->selectRaw('COALESCE(SUM(si.quantity * COALESCE(NULLIF(pv.cost_price, 0), p.cost_price, 0)), 0) AS cost')
            ->value('cost');

        return max(0.0, round($cost, 2));
    }

    /**
     * Whether a source document already has a live posted journal entry.
     * Lets callers stay idempotent instead of double-posting.
     */
    public function hasPostedJournal(string $sourceType, int $sourceId): bool
    {
        return JournalEntry::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 'posted')
            ->exists();
    }

    /**
     * Record a purchase in the general ledger.
     * DR Inventory (or COGS) / CR Accounts Payable + CR VAT (if applicable)
     */
    public function recordPurchase($purchase): JournalEntry
    {
        $lines = [];

        // DR Inventory
        $inventoryAmount = $purchase->grand_total - ($purchase->tax_amount ?? 0);
        $lines[] = [
            'account_id'    => $this->getAccountId('1020'),
            'debit_amount'  => $inventoryAmount,
            'credit_amount' => 0,
            'description'   => "Purchase: {$purchase->po_number}",
        ];

        // DR VAT (if applicable — input tax credit)
        if (($purchase->tax_amount ?? 0) > 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId('1025'), // Advance Tax / AIT
                'debit_amount'  => $purchase->tax_amount,
                'credit_amount' => 0,
                'description'   => "VAT on purchase: {$purchase->po_number}",
            ];
        }

        // CR Accounts Payable
        $lines[] = [
            'account_id'    => $this->getAccountId('2001'),
            'debit_amount'  => 0,
            'credit_amount' => $purchase->grand_total,
            'description'   => "Payable: {$purchase->po_number}",
        ];

        return $this->journalService->createFromSource(
            'purchase', $purchase->id, $lines,
            "Purchase: {$purchase->po_number}",
            $purchase->po_number,
            $purchase->po_date ?? $purchase->created_at,
        );
    }

    /**
     * Record an ecommerce order in the general ledger when it is confirmed/delivered.
     * Mirrors recordSale: DR Accounts Receivable / CR Sales Revenue + CR VAT.
     * Source type is 'ecommerce_order' so it can be voided independently of POS sales.
     */
    public function recordEcommerceOrder($order): JournalEntry
    {
        $lines = [];
        $taxAmount = (float) ($order->tax_amount ?? 0);
        $grandTotal = (float) $order->grand_total;
        $revenueAmount = $grandTotal - $taxAmount;

        $lines[] = [
            'account_id'    => $this->getAccountId('1010'),
            'debit_amount'  => $grandTotal,
            'credit_amount' => 0,
            'description'   => "Online order: {$order->order_number}",
        ];

        $lines[] = [
            'account_id'    => $this->getAccountId('4001'),
            'debit_amount'  => 0,
            'credit_amount' => $revenueAmount,
            'description'   => "Online revenue: {$order->order_number}",
        ];

        if ($taxAmount > 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId('2010'),
                'debit_amount'  => 0,
                'credit_amount' => $taxAmount,
                'description'   => "VAT on online order: {$order->order_number}",
            ];
        }

        return $this->journalService->createFromSource(
            'ecommerce_order', $order->id, $lines,
            "Online Order: {$order->order_number}",
            $order->order_number,
            $order->created_at,
        );
    }

    /**
     * Record a sale return in the general ledger.
     * DR Sales Returns / CR Accounts Receivable
     */
    public function recordSaleReturn($return): JournalEntry
    {
        $lines = [
            [
                'account_id'    => $this->getAccountId('4010'),
                'debit_amount'  => $return->total_amount,
                'credit_amount' => 0,
                'description'   => "Sale return: {$return->return_number}",
            ],
            [
                'account_id'    => $this->getAccountId('1010'),
                'debit_amount'  => 0,
                'credit_amount' => $return->total_amount,
                'description'   => "AR reduction: {$return->return_number}",
            ],
        ];

        return $this->journalService->createFromSource(
            'sale_return', $return->id, $lines,
            "Sale Return: {$return->return_number}",
            $return->return_number,
            $return->return_date ?? $return->created_at,
        );
    }

    /**
     * Record a purchase return in the general ledger.
     * DR Accounts Payable / CR Inventory
     */
    public function recordPurchaseReturn($return): JournalEntry
    {
        $total = (float) $return->total;
        $lines = [
            [
                'account_id'    => $this->getAccountId('2001'),
                'debit_amount'  => $total,
                'credit_amount' => 0,
                'description'   => "Purchase return: {$return->return_number}",
            ],
            [
                'account_id'    => $this->getAccountId('1020'),
                'debit_amount'  => 0,
                'credit_amount' => $total,
                'description'   => "Inventory reduction: {$return->return_number}",
            ],
        ];

        return $this->journalService->createFromSource(
            'purchase_return', $return->id, $lines,
            "Purchase Return: {$return->return_number}",
            $return->return_number,
            $return->return_date ?? $return->created_at,
        );
    }

    /**
     * Record a cash refund received from the supplier for a returned purchase.
     * DR Cash/Bank (from the refund account) / CR Inventory — the payable is NOT
     * reduced, because the supplier returned cash instead of a credit.
     */
    public function recordPurchaseReturnRefund($return): JournalEntry
    {
        $amount = (float) $return->refunded_amount;

        // Resolve the asset account (cash/bank) from the chosen payment account type.
        $typeToCode = ['cash' => '1001', 'mobile_banking' => '1002', 'bank' => '1004', 'card' => '1004'];
        $accountType = $return->refund_account_id
            ? \Modules\Payment\Models\PaymentAccount::where('id', $return->refund_account_id)->value('account_type')
            : 'cash';
        $assetCode = $typeToCode[$accountType] ?? '1001';

        $lines = [
            [
                'account_id'    => $this->getAccountId($assetCode),
                'debit_amount'  => $amount,
                'credit_amount' => 0,
                'description'   => "Refund received: {$return->return_number}",
            ],
            [
                'account_id'    => $this->getAccountId('1020'),
                'debit_amount'  => 0,
                'credit_amount' => $amount,
                'description'   => "Inventory reduction (refund): {$return->return_number}",
            ],
        ];

        return $this->journalService->createFromSource(
            'purchase_return', $return->id, $lines,
            "Purchase Return Refund: {$return->return_number}",
            $return->return_number,
            $return->return_date ?? $return->created_at,
        );
    }

    /**
     * Resolve the cash/bank ledger account code from a payment account's type.
     */
    private function cashAccountCodeFor(?int $paymentAccountId): string
    {
        $typeToCode = ['cash' => '1001', 'mobile_banking' => '1002', 'bank' => '1004', 'card' => '1004'];
        $type = $paymentAccountId
            ? \Modules\Payment\Models\PaymentAccount::where('id', $paymentAccountId)->value('account_type')
            : 'cash';

        return $typeToCode[$type] ?? '1001';
    }

    /**
     * Record an asset acquisition, splitting the credit side between the amount
     * paid now and the amount left on credit:
     *   DR Fixed Asset (1500) = full cost
     *   CR Cash/Bank          = amount paid now (if > 0)
     *   CR Accounts Payable   = amount left due (if > 0)
     */
    public function recordAssetAcquisition($asset, float $paidAmount = null, ?int $paymentAccountId = null): JournalEntry
    {
        $cost = (float) $asset->purchase_price;
        // Default: treat as fully paid when no paid amount is supplied (keeps
        // older callers working).
        $paid = $paidAmount === null ? $cost : max(0, min((float) $paidAmount, $cost));
        $due = round($cost - $paid, 2);
        $assetAccount = $asset->asset_account_code ?? '1500';
        $cashAccount = $this->cashAccountCodeFor($paymentAccountId ?? $asset->payment_account_id);

        $lines = [
            [
                'account_id'    => $this->getAccountId($assetAccount),
                'debit_amount'  => $cost,
                'credit_amount' => 0,
                'description'   => "Asset acquisition: {$asset->name} ({$asset->asset_code})",
            ],
        ];

        if ($paid > 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId($cashAccount),
                'debit_amount'  => 0,
                'credit_amount' => $paid,
                'description'   => "Cash paid for asset: {$asset->asset_code}",
            ];
        }

        if ($due > 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId('2001'),
                'debit_amount'  => 0,
                'credit_amount' => $due,
                'description'   => "Asset payable: {$asset->asset_code}",
            ];
        }

        return $this->journalService->createFromSource(
            'asset_acquisition', $asset->id, $lines,
            "Asset Acquired: {$asset->name}",
            $asset->asset_code,
            $asset->purchase_date ?? $asset->created_at,
        );
    }

    /**
     * Record a payment against an asset's outstanding purchase due.
     * DR Accounts Payable (2001) / CR Cash/Bank.
     */
    public function recordAssetPayment($payment): JournalEntry
    {
        $amount = (float) $payment->amount;
        $cashAccount = $this->cashAccountCodeFor($payment->payment_account_id);
        $asset = $payment->asset;
        $assetCode = $asset->asset_code ?? '';

        $lines = [
            [
                'account_id'    => $this->getAccountId('2001'),
                'debit_amount'  => $amount,
                'credit_amount' => 0,
                'description'   => "Asset payable settled: {$assetCode}",
            ],
            [
                'account_id'    => $this->getAccountId($cashAccount),
                'debit_amount'  => 0,
                'credit_amount' => $amount,
                'description'   => "Payment for asset: {$assetCode} ({$payment->payment_number})",
            ],
        ];

        return $this->journalService->createFromSource(
            'asset_payment', $asset->id, $lines,
            "Asset Payment: {$assetCode} — {$payment->payment_number}",
            $payment->reference ?? $payment->payment_number,
            $payment->payment_date ?? now()->toDateString(),
        );
    }

    /**
     * Record monthly depreciation for an asset.
     * DR Depreciation Expense (5150) / CR Accumulated Depreciation (1520)
     */
    public function recordDepreciation($asset, float $amount, string $period): JournalEntry
    {
        $lines = [
            [
                'account_id'    => $this->getAccountId('5150'),
                'debit_amount'  => $amount,
                'credit_amount' => 0,
                'description'   => "Depreciation {$period}: {$asset->name}",
            ],
            [
                'account_id'    => $this->getAccountId('1520'),
                'debit_amount'  => 0,
                'credit_amount' => $amount,
                'description'   => "Accumulated depreciation: {$asset->asset_code}",
            ],
        ];

        return $this->journalService->createFromSource(
            'asset_depreciation', $asset->id, $lines,
            "Depreciation: {$asset->name} ({$period})",
            $asset->asset_code . '-' . $period,
            $period . '-01',
        );
    }

    /**
     * Record asset disposal.
     * DR Cash (proceeds) + DR Accumulated Depreciation / CR Asset (cost) + CR/DR Gain/Loss
     */
    public function recordAssetDisposal($asset, float $disposalProceeds): JournalEntry
    {
        $cost = (float) $asset->purchase_price;
        $accumDep = (float) $asset->accumulated_depreciation;
        $bookValue = $cost - $accumDep;
        $gainLoss = $disposalProceeds - $bookValue;
        $assetAccount = $asset->asset_account_code ?? '1500';

        $lines = [];

        if ($disposalProceeds > 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId('1001'),
                'debit_amount'  => $disposalProceeds,
                'credit_amount' => 0,
                'description'   => "Disposal proceeds: {$asset->asset_code}",
            ];
        }

        if ($accumDep > 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId('1520'),
                'debit_amount'  => $accumDep,
                'credit_amount' => 0,
                'description'   => "Reverse accumulated depreciation: {$asset->asset_code}",
            ];
        }

        $lines[] = [
            'account_id'    => $this->getAccountId($assetAccount),
            'debit_amount'  => 0,
            'credit_amount' => $cost,
            'description'   => "Asset cost write-off: {$asset->asset_code}",
        ];

        if ($gainLoss > 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId('4040'),
                'debit_amount'  => 0,
                'credit_amount' => $gainLoss,
                'description'   => "Gain on disposal: {$asset->asset_code}",
            ];
        } elseif ($gainLoss < 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId('5200'),
                'debit_amount'  => abs($gainLoss),
                'credit_amount' => 0,
                'description'   => "Loss on disposal: {$asset->asset_code}",
            ];
        }

        return $this->journalService->createFromSource(
            'asset_disposal', $asset->id, $lines,
            "Asset Disposal: {$asset->name}",
            $asset->asset_code,
            today(),
        );
    }

    /**
     * Record an inventory adjustment.
     * Positive (stock found): DR Inventory / CR Inventory Gain (4050)
     * Negative (shrinkage):   DR Inventory Shrinkage (5210) / CR Inventory
     */
    public function recordInventoryAdjustment(string $reference, float $costDelta, string $reason, $sourceId = null, string $sourceType = 'inventory_adjustment'): ?JournalEntry
    {
        if (abs($costDelta) < 0.01) {
            return null;
        }

        if ($costDelta > 0) {
            $lines = [
                [
                    'account_id'    => $this->getAccountId('1020'),
                    'debit_amount'  => $costDelta,
                    'credit_amount' => 0,
                    'description'   => "Inventory increase: {$reason}",
                ],
                [
                    'account_id'    => $this->getAccountId('4050'),
                    'debit_amount'  => 0,
                    'credit_amount' => $costDelta,
                    'description'   => "Inventory gain: {$reason}",
                ],
            ];
        } else {
            $loss = abs($costDelta);
            $lines = [
                [
                    'account_id'    => $this->getAccountId('5210'),
                    'debit_amount'  => $loss,
                    'credit_amount' => 0,
                    'description'   => "Inventory shrinkage: {$reason}",
                ],
                [
                    'account_id'    => $this->getAccountId('1020'),
                    'debit_amount'  => 0,
                    'credit_amount' => $loss,
                    'description'   => "Inventory reduced: {$reason}",
                ],
            ];
        }

        return $this->journalService->createFromSource(
            $sourceType, $sourceId ?? 0, $lines,
            "Inventory Adjustment: {$reference}",
            $reference,
            today(),
        );
    }

    /**
     * Void a journal entry by source type and source ID.
     * Finds the posted journal entry and creates a reversing entry.
     */
    public function voidJournalEntry(string $sourceType, int $sourceId): void
    {
        $entry = JournalEntry::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 'posted')
            ->first();

        if ($entry) {
            $this->journalService->void($entry, "Cancelled {$sourceType} #{$sourceId}");
        }
    }

    /**
     * Resolve account ID by code.
     */
    private function getAccountId(string $code): int
    {
        return Account::where('account_code', $code)->value('id')
            ?? throw new \RuntimeException("System account '{$code}' not found. Run the ChartOfAccountsSeeder.");
    }
}
