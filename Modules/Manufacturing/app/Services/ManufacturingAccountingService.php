<?php

namespace Modules\Manufacturing\Services;

use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Manufacturing\Models\DamageCompensation;
use Modules\Manufacturing\Models\FabricIssuance;
use Modules\Manufacturing\Models\FabricReturn;
use Modules\Manufacturing\Models\FabricReturnItem;
use Modules\Manufacturing\Models\ProductionDamage;
use Modules\Manufacturing\Models\ProductionLot;
use Modules\Manufacturing\Models\ProductWaste;
use Modules\Manufacturing\Models\RmPurchaseOrder;
use Modules\Manufacturing\Models\RmWaste;

class ManufacturingAccountingService
{
    // ── Account Code Constants ──

    const ACCOUNT_RM_INVENTORY = '1030';
    const ACCOUNT_WIP = '1035';
    const ACCOUNT_FACTORY_RECEIVABLE = '1040';
    const ACCOUNT_FACTORY_ADVANCE = '1045';
    const ACCOUNT_RM_SUPPLIER_ADVANCE = '1021';
    const ACCOUNT_ACCOUNTS_PAYABLE = '2001';
    const ACCOUNT_FACTORY_PAYABLE = '2020';
    const ACCOUNT_MANUFACTURING_COST = '5010';
    const ACCOUNT_DAMAGE_LOSS = '5015';
    const ACCOUNT_RM_WASTE_LOSS = '5020';
    const ACCOUNT_PRODUCT_WASTE_LOSS = '5025';
    const ACCOUNT_CASH = '1001';

    public function __construct(
        private readonly JournalEntryService $journalService,
    ) {}

    // ── RM Purchase ──

    /**
     * Record a raw material purchase order in the general ledger.
     * DR Raw Material Inventory (1030) / CR Accounts Payable (2001)
     */
    public function recordRmPurchase(RmPurchaseOrder $po): JournalEntry
    {
        $lines = [
            [
                'account_id'    => $this->getAccountId(self::ACCOUNT_RM_INVENTORY),
                'debit_amount'  => $po->grand_total,
                'credit_amount' => 0,
                'description'   => "RM Purchase: {$po->po_number}",
            ],
            [
                'account_id'    => $this->getAccountId(self::ACCOUNT_ACCOUNTS_PAYABLE),
                'debit_amount'  => 0,
                'credit_amount' => $po->grand_total,
                'description'   => "RM Payable: {$po->po_number}",
            ],
        ];

        return $this->journalService->createFromSource(
            'rm_purchase',
            $po->id,
            $lines,
            "RM Purchase: {$po->po_number}",
            $po->po_number,
            $po->po_date ?? $po->created_at,
        );
    }

    // ── Fabric Issuance ──

    /**
     * Record fabric issuance to factory.
     * DR Work-in-Progress (1035) / CR Raw Material Inventory (1030)
     */
    public function recordFabricIssuance(FabricIssuance $issuance): JournalEntry
    {
        $lines = [
            [
                'account_id'    => $this->getAccountId(self::ACCOUNT_WIP),
                'debit_amount'  => $issuance->total_cost,
                'credit_amount' => 0,
                'description'   => "Fabric issued: {$issuance->issuance_number}",
            ],
            [
                'account_id'    => $this->getAccountId(self::ACCOUNT_RM_INVENTORY),
                'debit_amount'  => 0,
                'credit_amount' => $issuance->total_cost,
                'description'   => "RM issued to production: {$issuance->issuance_number}",
            ],
        ];

        return $this->journalService->createFromSource(
            'manufacturing_fabric_issue',
            $issuance->id,
            $lines,
            "Fabric Issuance: {$issuance->issuance_number}",
            $issuance->issuance_number,
            $issuance->issuance_date ?? $issuance->created_at,
        );
    }

    // ── Fabric Return ──

    /**
     * Record fabric return from factory.
     * Good items: DR RM Inventory (1030) / CR WIP (1035)
     * Damaged items: DR Damage Loss (5015) / CR WIP (1035)
     */
    public function recordFabricReturn(FabricReturn $return): JournalEntry
    {
        $return->load('items');

        $goodCost = 0;
        $damagedCost = 0;

        foreach ($return->items as $item) {
            if ($item->condition === FabricReturnItem::CONDITION_GOOD) {
                $goodCost += (float) $item->line_total;
            } else {
                $damagedCost += (float) $item->line_total;
            }
        }

        $lines = [];

        if ($goodCost > 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId(self::ACCOUNT_RM_INVENTORY),
                'debit_amount'  => $goodCost,
                'credit_amount' => 0,
                'description'   => "Good fabric returned: {$return->return_number}",
            ];
        }

        if ($damagedCost > 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId(self::ACCOUNT_DAMAGE_LOSS),
                'debit_amount'  => $damagedCost,
                'credit_amount' => 0,
                'description'   => "Damaged fabric returned: {$return->return_number}",
            ];
        }

        $totalCost = $goodCost + $damagedCost;

        if ($totalCost > 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId(self::ACCOUNT_WIP),
                'debit_amount'  => 0,
                'credit_amount' => $totalCost,
                'description'   => "WIP reduced for fabric return: {$return->return_number}",
            ];
        }

        return $this->journalService->createFromSource(
            'manufacturing_fabric_return',
            $return->id,
            $lines,
            "Fabric Return: {$return->return_number}",
            $return->return_number,
            $return->return_date ?? $return->created_at,
        );
    }

    // ── Production Lot Received ──

    /**
     * Record a production lot received from factory.
     * DR WIP (1035) / CR Factory Payable (2020) — for making cost
     * DR WIP (1035) / CR Cash/Bank — for delivery/other costs
     */
    public function recordLotReceived(ProductionLot $lot): JournalEntry
    {
        $lines = [];
        $makingCost = (float) $lot->making_cost;
        $deliveryCost = (float) $lot->delivery_charge;
        $otherCosts = (float) $lot->other_costs;

        // Making cost — payable to factory
        if ($makingCost > 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId(self::ACCOUNT_WIP),
                'debit_amount'  => $makingCost,
                'credit_amount' => 0,
                'description'   => "Making cost: {$lot->lot_number}",
            ];
            $lines[] = [
                'account_id'    => $this->getAccountId(self::ACCOUNT_FACTORY_PAYABLE),
                'debit_amount'  => 0,
                'credit_amount' => $makingCost,
                'description'   => "Factory payable: {$lot->lot_number}",
            ];
        }

        // Delivery and other costs — paid in cash/bank
        $cashCosts = $deliveryCost + $otherCosts;
        if ($cashCosts > 0) {
            $lines[] = [
                'account_id'    => $this->getAccountId(self::ACCOUNT_WIP),
                'debit_amount'  => $cashCosts,
                'credit_amount' => 0,
                'description'   => "Delivery/other costs: {$lot->lot_number}",
            ];
            $lines[] = [
                'account_id'    => $this->getAccountId(self::ACCOUNT_CASH),
                'debit_amount'  => 0,
                'credit_amount' => $cashCosts,
                'description'   => "Cash paid for lot costs: {$lot->lot_number}",
            ];
        }

        return $this->journalService->createFromSource(
            'manufacturing_lot',
            $lot->id,
            $lines,
            "Production Lot: {$lot->lot_number}",
            $lot->lot_number,
            $lot->delivery_date ?? $lot->created_at,
        );
    }

    // ── Production Damage ──

    /**
     * Record production damage.
     * Factory fault: DR Factory Receivable (1040) / CR WIP (1035)
     * Own fault: DR Damage Loss (5015) / CR WIP (1035)
     */
    public function recordDamage(ProductionDamage $damage): JournalEntry
    {
        $isFactoryFault = in_array($damage->responsibility, [
            ProductionDamage::RESP_FACTORY,
            ProductionDamage::RESP_TRANSIT,
        ]);

        $debitAccount = $isFactoryFault
            ? self::ACCOUNT_FACTORY_RECEIVABLE
            : self::ACCOUNT_DAMAGE_LOSS;

        $debitLabel = $isFactoryFault
            ? "Factory receivable for damage"
            : "Damage loss (own fault)";

        $lines = [
            [
                'account_id'    => $this->getAccountId($debitAccount),
                'debit_amount'  => $damage->total_damage_cost,
                'credit_amount' => 0,
                'description'   => "{$debitLabel}: Lot #{$damage->lot_id}",
            ],
            [
                'account_id'    => $this->getAccountId(self::ACCOUNT_WIP),
                'debit_amount'  => 0,
                'credit_amount' => $damage->total_damage_cost,
                'description'   => "WIP reduced for damage: Lot #{$damage->lot_id}",
            ],
        ];

        $entry = $this->journalService->createFromSource(
            'manufacturing_damage',
            $damage->id,
            $lines,
            "Production Damage: Lot #{$damage->lot_id} ({$damage->responsibility})",
            null,
            $damage->damage_date ?? $damage->created_at,
        );

        $damage->update(['journal_entry_id' => $entry->id]);

        return $entry;
    }

    // ── RM Waste ──

    /**
     * Record raw material waste.
     * Normal waste: DR WIP (1035) / CR RM Inventory (1030) — absorbed into production cost
     * Abnormal waste: DR RM Waste Loss (5020) / CR RM Inventory (1030)
     */
    public function recordRmWaste(RmWaste $waste): JournalEntry
    {
        $debitAccount = $waste->is_normal
            ? self::ACCOUNT_WIP
            : self::ACCOUNT_RM_WASTE_LOSS;

        $debitLabel = $waste->is_normal
            ? "Normal RM waste absorbed in WIP"
            : "Abnormal RM waste loss";

        $lines = [
            [
                'account_id'    => $this->getAccountId($debitAccount),
                'debit_amount'  => $waste->total_cost,
                'credit_amount' => 0,
                'description'   => "{$debitLabel}: {$waste->waste_type}",
            ],
            [
                'account_id'    => $this->getAccountId(self::ACCOUNT_RM_INVENTORY),
                'debit_amount'  => 0,
                'credit_amount' => $waste->total_cost,
                'description'   => "RM Inventory reduced for waste",
            ],
        ];

        $entry = $this->journalService->createFromSource(
            'manufacturing_waste',
            $waste->id,
            $lines,
            "RM Waste: {$waste->waste_type} (" . ($waste->is_normal ? 'normal' : 'abnormal') . ")",
            null,
            $waste->waste_date ?? $waste->created_at,
        );

        $waste->update(['journal_entry_id' => $entry->id]);

        return $entry;
    }

    // ── Product Waste ──

    /**
     * Record product waste.
     * Normal waste is absorbed in lot cost (no separate journal entry needed).
     * Abnormal only: DR Product Waste Loss (5025) / CR WIP (1035)
     */
    public function recordProductWaste(ProductWaste $waste): ?JournalEntry
    {
        // Normal product waste is absorbed into production cost — no journal entry
        if ($waste->is_normal) {
            return null;
        }

        $lines = [
            [
                'account_id'    => $this->getAccountId(self::ACCOUNT_PRODUCT_WASTE_LOSS),
                'debit_amount'  => $waste->total_cost,
                'credit_amount' => 0,
                'description'   => "Abnormal product waste loss: {$waste->waste_type}",
            ],
            [
                'account_id'    => $this->getAccountId(self::ACCOUNT_WIP),
                'debit_amount'  => 0,
                'credit_amount' => $waste->total_cost,
                'description'   => "WIP reduced for product waste",
            ],
        ];

        $entry = $this->journalService->createFromSource(
            'manufacturing_waste',
            $waste->id,
            $lines,
            "Product Waste: {$waste->waste_type} (abnormal)",
            null,
            $waste->waste_date ?? $waste->created_at,
        );

        $waste->update(['journal_entry_id' => $entry->id]);

        return $entry;
    }

    // ── Damage Compensation ──

    /**
     * Record damage compensation received from factory.
     * Cash: DR Cash/Bank / CR Factory Receivable (1040)
     * Deduct from payable: DR Factory Payable (2020) / CR Factory Receivable (1040)
     */
    public function recordCompensation(DamageCompensation $comp): JournalEntry
    {
        $isCash = in_array($comp->method, ['cash', 'bank', 'mobile_banking']);

        $debitAccount = $isCash
            ? self::ACCOUNT_CASH
            : self::ACCOUNT_FACTORY_PAYABLE;

        $debitLabel = $isCash
            ? "Cash received as compensation"
            : "Factory payable reduced (damage deduction)";

        $lines = [
            [
                'account_id'    => $this->getAccountId($debitAccount),
                'debit_amount'  => $comp->amount,
                'credit_amount' => 0,
                'description'   => $debitLabel,
            ],
            [
                'account_id'    => $this->getAccountId(self::ACCOUNT_FACTORY_RECEIVABLE),
                'debit_amount'  => 0,
                'credit_amount' => $comp->amount,
                'description'   => "Compensation received for damage #{$comp->damage_id}",
            ],
        ];

        $entry = $this->journalService->createFromSource(
            'manufacturing_compensation',
            $comp->id,
            $lines,
            "Damage Compensation: Damage #{$comp->damage_id}",
            $comp->reference,
            $comp->compensation_date ?? $comp->created_at,
        );

        $comp->update(['journal_entry_id' => $entry->id]);

        return $entry;
    }

    // ── RM Supplier Payment ──

    /**
     * Record payment to RM supplier.
     * Against PO: DR Accounts Payable (2001) / CR Cash/Bank
     * Advance: DR RM Supplier Advance (1021) / CR Cash/Bank
     */
    public function recordRmSupplierPayment(string $paymentNumber, float $amount, string $paymentType, string $paymentDate): JournalEntry
    {
        $isAdvance = $paymentType === 'advance';

        $debitAccount = $isAdvance
            ? self::ACCOUNT_RM_SUPPLIER_ADVANCE
            : self::ACCOUNT_ACCOUNTS_PAYABLE;

        $debitLabel = $isAdvance
            ? "RM supplier advance paid"
            : "RM supplier payable cleared";

        $lines = [
            [
                'account_id'    => $this->getAccountId($debitAccount),
                'debit_amount'  => $amount,
                'credit_amount' => 0,
                'description'   => "{$debitLabel}: {$paymentNumber}",
            ],
            [
                'account_id'    => $this->getAccountId(self::ACCOUNT_CASH),
                'debit_amount'  => 0,
                'credit_amount' => $amount,
                'description'   => "Payment made: {$paymentNumber}",
            ],
        ];

        return $this->journalService->createFromSource(
            'rm_supplier_payment',
            0,
            $lines,
            "RM Supplier Payment: {$paymentNumber}",
            $paymentNumber,
            $paymentDate,
        );
    }

    // ── Factory Payment ──

    /**
     * Record payment to factory.
     * Against order: DR Factory Payable (2020) / CR Cash/Bank
     * Advance: DR Factory Advance (1045) / CR Cash/Bank
     * With deduction: DR Factory Payable (2020) / CR Factory Receivable (1040) + CR Cash/Bank
     */
    public function recordFactoryPayment(
        string $paymentNumber,
        float $amount,
        string $paymentType,
        string $paymentDate,
        float $deductionAmount = 0
    ): JournalEntry {
        $isAdvance = $paymentType === 'advance';
        $lines = [];

        if ($isAdvance) {
            $lines[] = [
                'account_id'    => $this->getAccountId(self::ACCOUNT_FACTORY_ADVANCE),
                'debit_amount'  => $amount,
                'credit_amount' => 0,
                'description'   => "Factory advance paid: {$paymentNumber}",
            ];
            $lines[] = [
                'account_id'    => $this->getAccountId(self::ACCOUNT_CASH),
                'debit_amount'  => 0,
                'credit_amount' => $amount,
                'description'   => "Payment made: {$paymentNumber}",
            ];
        } else {
            $totalPayable = $amount + $deductionAmount;

            // DR Factory Payable for full amount being settled
            $lines[] = [
                'account_id'    => $this->getAccountId(self::ACCOUNT_FACTORY_PAYABLE),
                'debit_amount'  => $totalPayable,
                'credit_amount' => 0,
                'description'   => "Factory payable settled: {$paymentNumber}",
            ];

            // CR Factory Receivable for deduction (damage offset)
            if ($deductionAmount > 0) {
                $lines[] = [
                    'account_id'    => $this->getAccountId(self::ACCOUNT_FACTORY_RECEIVABLE),
                    'debit_amount'  => 0,
                    'credit_amount' => $deductionAmount,
                    'description'   => "Damage deduction from payment: {$paymentNumber}",
                ];
            }

            // CR Cash/Bank for actual cash paid
            if ($amount > 0) {
                $lines[] = [
                    'account_id'    => $this->getAccountId(self::ACCOUNT_CASH),
                    'debit_amount'  => 0,
                    'credit_amount' => $amount,
                    'description'   => "Cash paid to factory: {$paymentNumber}",
                ];
            }
        }

        return $this->journalService->createFromSource(
            'factory_payment',
            0,
            $lines,
            "Factory Payment: {$paymentNumber}",
            $paymentNumber,
            $paymentDate,
        );
    }

    // ── Cost Adjustment ──

    /**
     * Record a cost adjustment journal entry when final cost differs from running avg.
     */
    public function recordCostAdjustment(int $orderId, string $orderNumber, float $adjustmentAmount, string $date): JournalEntry
    {
        if ($adjustmentAmount > 0) {
            // Under-costed: need to increase WIP
            $lines = [
                [
                    'account_id'    => $this->getAccountId(self::ACCOUNT_WIP),
                    'debit_amount'  => $adjustmentAmount,
                    'credit_amount' => 0,
                    'description'   => "Cost adjustment (increase): {$orderNumber}",
                ],
                [
                    'account_id'    => $this->getAccountId(self::ACCOUNT_MANUFACTURING_COST),
                    'debit_amount'  => 0,
                    'credit_amount' => $adjustmentAmount,
                    'description'   => "Manufacturing cost variance: {$orderNumber}",
                ],
            ];
        } else {
            // Over-costed: need to decrease WIP
            $absAmount = abs($adjustmentAmount);
            $lines = [
                [
                    'account_id'    => $this->getAccountId(self::ACCOUNT_MANUFACTURING_COST),
                    'debit_amount'  => $absAmount,
                    'credit_amount' => 0,
                    'description'   => "Manufacturing cost variance: {$orderNumber}",
                ],
                [
                    'account_id'    => $this->getAccountId(self::ACCOUNT_WIP),
                    'debit_amount'  => 0,
                    'credit_amount' => $absAmount,
                    'description'   => "Cost adjustment (decrease): {$orderNumber}",
                ],
            ];
        }

        return $this->journalService->createFromSource(
            'manufacturing_cost_adjustment',
            $orderId,
            $lines,
            "Cost Adjustment: {$orderNumber}",
            $orderNumber,
            $date,
        );
    }

    // ── Void ──

    /**
     * Void a journal entry by source type and source ID.
     */
    public function voidEntry(string $sourceType, int $sourceId): void
    {
        $entry = JournalEntry::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 'posted')
            ->first();

        if ($entry) {
            $this->journalService->void($entry, "Cancelled {$sourceType} #{$sourceId}");
        }
    }

    // ── Helper ──

    /**
     * Resolve account ID by account code.
     */
    private function getAccountId(string $code): int
    {
        return Account::where('account_code', $code)->value('id')
            ?? throw new \RuntimeException("Manufacturing account '{$code}' not found. Run the ManufacturingAccountsSeeder.");
    }
}
