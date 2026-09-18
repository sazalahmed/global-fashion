# Manufacturing & Raw Materials Module — Comprehensive Plan

## Context

The user runs a garment business (shirts, panjabi). They buy **fabrics (raw materials)** from **suppliers**, hold them in inventory, then issue fabrics to **factories** for Cut-Make-Trim (CMT). Factories deliver finished garments in multiple **lots** (partial deliveries). Each lot may have **damaged** items. Need to track two inventory levels: **fabric stock** and **finished garment stock**. Costing is provisional per-lot with final reconciliation when PO completes.

**Business Flow:**
```
Supplier → [Fabric Purchase] → Raw Material Inventory
                                       ↓
                    [Fabric Issuance to Factory] (planned via BOM at PO creation)
                                       ↓
                              Factory (CMT Processing)
                                    ↓         ↓
                        [Production Lots]   [Fabric Returns] → Back to RM Inventory
                              ↓
              [Map to Product Variants] → Finished Goods Inventory → Sale
                              ↓
                    [Damage Tracking] → Cash Flow / Journal Entries
```

### Key Design Decisions

1. **Progressive Costing**: Each lot calculates provisional cost → finished goods enter main inventory immediately with best-known cost → final reconciliation adjusts cost when PO completes
2. **BOM at PO Creation**: Production order defines planned raw material usage + expected returns upfront
3. **Product Variant Mapping**: Each production order item (catalog × color × size) maps to an existing `ProductVariant` in the Product module — finished goods go directly into main sellable inventory
4. **Fabric Returns**: Factory returns unused raw materials → goes back to RM inventory with cost credit

### Integration with Existing Modules

- **Product Module**: Production order items link to `products.id` + `product_variants.id`
- **Variant Module**: Manufacturing `colors` and `sizes` are separate master data, but each production item maps to a `ProductVariant` (via `variant_attribute_values`)
- **Inventory Module**: Uses existing `InventoryService.adjustStock()` with new source_type `'manufacturing_output'` to add finished goods to `warehouse_stock` + `stock_ledger`
- **Accounting Module**: Uses existing `JournalEntryService` for all financial entries

---

## Module: `Manufacturing`

Single Nwidart module at `Modules/Manufacturing/` following existing patterns (Purchase, Expense, etc.).

---

## Database Schema (30 tables)

### Phase 1: Master Data (6 tables)

**`factories`** — Manufacturing vendors
- `id`, `name`, `code` (auto: FAC-001), `contact_person`, `phone`, `email`, `address`, `payment_terms` (nullable string — e.g., "Net 30", "Advance 50%", "COD"), `total_orders` (decimal 15,2), `total_paid` (decimal 15,2), `due_balance` (decimal 15,2), `advance_balance` (decimal 15,2 default 0 — prepayments held), `is_active`, `notes`, `created_by`, `timestamps`, `softDeletes`

**`catalogs`** — Garment types
- `id`, `name`, `code` (auto: CAT-001), `description`, `is_active`, `sort_order`, `timestamps`, `softDeletes`
- Seed: Half Shirt, Full Shirt, Panjabi

**`colors`** — Color master
- `id`, `name`, `code`, `hex_code` (nullable), `is_active`, `sort_order`, `timestamps`

**`sizes`** — Size master
- `id`, `name`, `code`, `sort_order`, `is_active`, `timestamps`
- Seed: XS, S, M, L, XL, XXL

**`raw_materials`** — Fabric/material definitions
- `id`, `name`, `code` (auto: RM-001), `category` (fabric, thread, button, zipper, other), `unit` (yard, meter, kg, piece, roll), `cost_price` (decimal 15,2), `last_purchase_price` (decimal 15,2), `reorder_level` (decimal 15,4), `is_active`, `notes`, `created_by`, `timestamps`, `softDeletes`

**`raw_material_suppliers`** — Independent from main Supplier module
- `id`, `company_name`, `contact_person`, `phone`, `email`, `address`, `division`, `district`, `area`, `bank_name`, `account_number`, `bank_branch`, `tin`, `payment_terms`, `opening_balance` (decimal 15,2), `total_purchase` (decimal 15,2), `total_paid` (decimal 15,2), `due_balance` (decimal 15,2), `advance_balance` (decimal 15,2), `is_active`, `notes`, `created_by`, `timestamps`, `softDeletes`

### Phase 2: Raw Material Procurement (6 tables)

**`rm_purchase_orders`** — PO to suppliers for fabrics
- `id`, `po_number` (auto: RMPO-XXXX), `po_date`, `supplier_id` → `raw_material_suppliers`, `subtotal`, `discount_amount`, `tax_amount`, `shipping_cost`, `grand_total`, `paid_amount`, `due_amount`, `status` (draft, pending, approved, partial_received, received, cancelled), `payment_status` (unpaid, partial, paid), `expected_delivery_date`, `notes`, `created_by`, `approved_by`, `approved_at`, `timestamps`, `softDeletes`

**`rm_purchase_items`** — Line items
- `id`, `purchase_order_id` → `rm_purchase_orders`, `raw_material_id` → `raw_materials`, `quantity` (decimal 15,4), `unit_price` (decimal 15,2), `tax_rate` (decimal 5,2), `tax_amount` (decimal 15,2), `discount_amount` (decimal 15,2), `line_total` (decimal 15,2), `received_quantity` (decimal 15,4 default 0), `timestamps`

**`rm_receives`** — GRN for fabric delivery
- `id`, `purchase_order_id` → `rm_purchase_orders`, `receive_number` (auto: RMGRN-XXXX), `receive_date`, `received_by` → `users`, `notes`, `timestamps`

**`rm_receive_items`** — Items received
- `id`, `receive_id` → `rm_receives`, `purchase_item_id` → `rm_purchase_items`, `raw_material_id` → `raw_materials`, `quantity_received` (decimal 15,4), `quantity_damaged` (decimal 15,4 default 0), `damage_notes`, `timestamps`

**`rm_stocks`** — Current fabric stock levels
- `id`, `raw_material_id` → `raw_materials`, `warehouse_id` → `warehouses`, `quantity` (decimal 15,4 default 0), `avg_cost` (decimal 15,2 default 0), `last_cost` (decimal 15,2 default 0), `timestamps`
- Unique: [`raw_material_id`, `warehouse_id`]

**`rm_stock_ledger`** — Audit trail of all fabric movements
- `id`, `raw_material_id`, `warehouse_id`, `type` (purchase_receive, damage, issue_to_factory, adjustment, return), `reference_type`, `reference_id`, `quantity_in` (decimal 15,4), `quantity_out` (decimal 15,4), `balance` (decimal 15,4), `unit_cost` (decimal 15,2), `notes`, `created_by`, `created_at`

### Phase 3: Production Management (14 tables)

**`production_orders`** — PO to factory for manufacturing
- `id`, `po_number` (auto: PO-XXXX), `factory_id` → `factories`, `order_date`, `expected_delivery_date`, `total_quantity` (int), `received_quantity` (int default 0), `damaged_quantity` (int default 0), `wasted_quantity` (int default 0 — product waste), `good_quantity` (int default 0 — received minus damaged minus wasted), `estimated_making_cost_per_unit` (decimal 15,2), `actual_making_cost_per_unit` (decimal 15,2 nullable), `estimated_total_cost` (decimal 15,2 default 0 — estimated_making_cost × total_quantity), `total_fabric_cost` (decimal 15,2 default 0), `total_making_cost` (decimal 15,2 default 0), `total_delivery_cost` (decimal 15,2 default 0), `total_other_cost` (decimal 15,2 default 0), `total_damage_cost` (decimal 15,2 default 0), `total_compensation` (decimal 15,2 default 0), `total_fabric_returned_cost` (decimal 15,2 default 0), `total_rm_waste_cost` (decimal 15,2 default 0 — normal RM waste absorbed into cost), `total_rm_waste_abnormal_cost` (decimal 15,2 default 0 — abnormal RM waste expensed separately), `total_product_waste_cost` (decimal 15,2 default 0 — normal product waste), `total_product_waste_abnormal_cost` (decimal 15,2 default 0 — abnormal product waste expensed), `grand_total` (decimal 15,2 default 0 — actual total cost once lots come in, updated progressively), `paid_amount` (decimal 15,2 default 0), `due_amount` (decimal 15,2 default 0), `advance_deducted` (decimal 15,2 default 0 — advance balance used for this order), `payment_status` (unpaid, partial, paid), `running_weighted_avg_cost` (decimal 15,2 nullable), `final_cost_per_unit` (decimal 15,2 nullable), `status` (draft, approved, in_progress, partial_delivered, completed, cancelled), `notes`, `created_by`, `approved_by`, `approved_at`, `completed_at`, `timestamps`, `softDeletes`

**`production_order_items`** — What to produce (catalog × color × size × qty) → mapped to Product Variants
- `id`, `production_order_id` → `production_orders`, `catalog_id` → `catalogs`, `color_id` → `colors`, `size_id` → `sizes`, **`product_id`** → `products` (the parent variable product), **`variant_id`** → `product_variants` (the specific variant for this color+size), `quantity` (int), `received_quantity` (int default 0), `damaged_quantity` (int default 0), `making_cost_per_unit` (decimal 15,2), `current_cost_per_unit` (decimal 15,2 nullable — running cost, updated each lot), `timestamps`
- **Key**: Each item maps to a sellable ProductVariant. When lots are received, good units go into that variant's `warehouse_stock`.

**`production_order_materials`** — Bill of Materials (BOM) — planned raw material usage, defined at PO creation
- `id`, `production_order_id` → `production_orders`, `raw_material_id` → `raw_materials`, `planned_quantity` (decimal 15,4 — how much to send to factory), `expected_return_quantity` (decimal 15,4 default 0 — how much factory will return unused), `actual_issued_quantity` (decimal 15,4 default 0 — filled when fabric is actually issued), `actual_returned_quantity` (decimal 15,4 default 0 — filled when factory returns leftover), `unit_cost` (decimal 15,2 — cost at time of planning), `estimated_consumption` (decimal 15,4 — planned_quantity minus expected_return), `notes`, `timestamps`
- **Purpose**: At PO creation, user defines exactly what raw materials will go to the factory and how much is expected back. This drives fabric issuance and return workflows.

**`fabric_issuances`** — Fabric sent from inventory to factory (based on BOM)
- `id`, `production_order_id` → `production_orders`, `issuance_number` (auto: FI-XXXX), `issuance_date`, `factory_id` → `factories`, `total_cost` (decimal 15,2), `notes`, `issued_by` → `users`, `timestamps`

**`fabric_issuance_items`** — Fabric line items issued
- `id`, `issuance_id` → `fabric_issuances`, `raw_material_id` → `raw_materials`, `warehouse_id` → `warehouses`, `bom_item_id` → `production_order_materials` (nullable — links back to BOM line), `quantity_issued` (decimal 15,4), `unit_cost` (decimal 15,2), `line_total` (decimal 15,2), `timestamps`

**`fabric_returns`** — Unused fabric returned from factory → back to RM inventory
- `id`, `production_order_id` → `production_orders`, `return_number` (auto: FR-XXXX), `return_date`, `factory_id` → `factories`, `total_cost` (decimal 15,2), `notes`, `received_by` → `users`, `timestamps`

**`fabric_return_items`** — Returned fabric line items
- `id`, `return_id` → `fabric_returns`, `raw_material_id` → `raw_materials`, `warehouse_id` → `warehouses`, `bom_item_id` → `production_order_materials` (nullable), `quantity_returned` (decimal 15,4), `unit_cost` (decimal 15,2), `line_total` (decimal 15,2), `condition` (good, damaged, partial_usable), `notes`, `timestamps`
- **On save**: Good condition → add back to `rm_stocks` via `RmStockService`. Damaged → record as RM damage. Updates `production_order_materials.actual_returned_quantity`.

**`production_lots`** — Each delivery batch from factory
- `id`, `production_order_id` → `production_orders`, `lot_number` (auto: LOT-PO0001-01), `delivery_date`, `making_cost` (decimal 15,2), `delivery_charge` (decimal 15,2 default 0), `other_costs` (decimal 15,2 default 0), `other_costs_note`, `total_received` (int default 0), `total_damaged` (int default 0), `total_wasted` (int default 0 — product waste for this lot), `total_good` (int default 0 — received minus damaged minus wasted), `rm_waste_cost` (decimal 15,2 default 0 — normal RM waste for this lot), `product_waste_cost` (decimal 15,2 default 0 — normal product waste for this lot), `provisional_cost_per_unit` (decimal 15,2 nullable), `stock_added` (boolean default false — whether finished goods were added to main inventory), `notes`, `received_by` → `users`, `timestamps`

**`production_lot_items`** — Items in each lot → triggers stock addition to main inventory
- `id`, `lot_id` → `production_lots`, `production_order_item_id` → `production_order_items`, `catalog_id`, `color_id`, `size_id`, **`product_id`** → `products`, **`variant_id`** → `product_variants`, `quantity_received` (int), `quantity_damaged` (int default 0), `quantity_good` (int), `unit_cost` (decimal 15,2 — provisional cost at time of lot receipt), `timestamps`
- **On save**: Calls `InventoryService.adjustStock(product_id, variant_id, warehouse_id, quantity_good, 'manufacturing_output', lot_id, unit_cost)` — immediately adds to sellable `warehouse_stock`.

**`production_damages`** — Damage records with responsibility tracking
- `id`, `lot_id` → `production_lots` (nullable), `production_order_id` → `production_orders`, `catalog_id`, `color_id`, `size_id`, `product_id` → `products`, `variant_id` → `product_variants`, `quantity` (int), `estimated_cost_per_unit` (decimal 15,2), `total_damage_cost` (decimal 15,2), `damage_type` (factory_fault, transit, storage, material_defect, other), `responsibility` (factory, own, supplier, transit), `compensation_amount` (decimal 15,2 default 0), `compensation_status` (pending, partial, received, written_off, deducted), `compensation_received` (decimal 15,2 default 0), `compensation_method` (cash, deduct_from_payable, replacement, mixed — how factory compensates), `damage_date`, `notes`, `journal_entry_id` (nullable), `created_by`, `timestamps`
- **Factory-fault compensation methods:**
  - `cash` — Factory pays cash/bKash/bank → DR Cash/Bank, CR Factory Receivable
  - `deduct_from_payable` — Deducted from factory's outstanding making cost payable → DR Factory Payable, CR Factory Receivable (offset, no cash movement)
  - `replacement` — Factory replaces damaged items in next lot → quantity added to lot, compensation marked as received at item cost value
  - `mixed` — Combination of above methods (tracked via `damage_compensations` table below)

**`damage_compensations`** — Individual compensation transactions for a damage record (supports partial & mixed methods)
- `id`, `damage_id` → `production_damages`, `compensation_date`, `amount` (decimal 15,2), `method` (cash, deduct_from_payable, replacement), `payment_account_id` → `accounts` (nullable — for cash payments), `factory_payment_id` → `factory_payments` (nullable — links to the factory payment it was deducted from), `replacement_lot_id` → `production_lots` (nullable — if replaced in a specific lot), `reference`, `notes`, `journal_entry_id` (nullable), `created_by`, `timestamps`
- **Purpose**: A single damage record (e.g., 10 damaged shirts = BDT 2,500) can be compensated in multiple ways over time:
  - BDT 1,000 deducted from next factory payment
  - BDT 500 cash received
  - 4 replacement shirts in LOT-3 (valued at BDT 1,000)
- Each compensation creates its own journal entry based on method

**`rm_wastes`** — Raw material waste per production order (cutting waste, spillage, etc.)
- `id`, `production_order_id` → `production_orders`, `lot_id` → `production_lots` (nullable — waste can be recorded per lot or per order), `raw_material_id` → `raw_materials`, `quantity_wasted` (decimal 15,4), `unit_cost` (decimal 15,2), `total_cost` (decimal 15,2), `waste_type` (cutting, defective_material, spillage, shrinkage, other), `is_normal` (boolean default true — normal/expected vs abnormal/unexpected), `waste_percentage` (decimal 5,2 nullable — % of issued quantity), `notes`, `journal_entry_id` (nullable), `created_by`, `waste_date`, `timestamps`
- **Normal waste** (e.g., 5% cutting waste is expected): Absorbed into production cost → increases cost per finished unit. No separate expense entry.
- **Abnormal waste** (e.g., machine malfunction ruined 50 yards): Expensed to Waste Loss account (5020). Does NOT inflate product cost — it's a period expense.
- **Tracked against BOM**: `actual_consumed = actual_issued - actual_returned - total_wasted`. Helps compare planned vs actual consumption.

**`product_wastes`** — Finished product waste per production order (quality rejections, total loss)
- `id`, `production_order_id` → `production_orders`, `lot_id` → `production_lots` (nullable), `catalog_id`, `color_id`, `size_id`, `product_id` → `products`, `variant_id` → `product_variants`, `quantity_wasted` (int), `unit_cost` (decimal 15,2 — estimated cost at time of waste), `total_cost` (decimal 15,2), `waste_type` (quality_rejection, manufacturing_defect, unrecoverable, other), `is_normal` (boolean default true), `waste_percentage` (decimal 5,2 nullable — % of lot received), `notes`, `journal_entry_id` (nullable), `created_by`, `waste_date`, `timestamps`
- **Key difference from damages**: Waste = total loss, no recovery or compensation possible. Damaged items may still be sold at discount or compensated by factory. Wasted items are completely written off.
- **Normal waste**: Absorbed into cost (fewer good units to spread cost over — same as damage effect)
- **Abnormal waste**: Expensed to Product Waste Loss (5025)
- **Updates**: `production_lots.total_good` is reduced by waste quantity (good = received - damaged - wasted)

### Phase 4: Payments — Integrated with Central Payment Module (2 tables + existing Payment system)

**CRITICAL**: All payments MUST flow through the existing **Payment Module** (`Modules/Payment/`) to appear in:
- Payment Ledger (`/payments` index)
- General Ledger (per-account transaction history)
- Cashflow Statement (cash in/out)
- Cash Movement Report

**Pattern**: Follow the existing `SupplierPayment` dual-system pattern:
1. Create module-specific payment record (for manufacturing-specific tracking)
2. Call `PaymentService::create()` to create the centralized `Payment` record with auto-posted journal entry
3. Use `PaymentAllocation` to link payment to RM Purchase Orders / Production Orders

**New `party_type` values** needed in the Payment system:
- `rm_supplier` — for raw material supplier payments
- `factory` — for factory payments

**`rm_supplier_payments`** — Module-specific tracking for RM supplier payments
- `id`, `payment_number` (auto: RMSP-XXXX), `supplier_id` → `raw_material_suppliers`, `purchase_order_id` → `rm_purchase_orders` (nullable), `payment_id` → `payments` (**links to central Payment record**), `payment_date`, `amount` (decimal 15,2), `payment_method`, `payment_account_id` → `payment_accounts`, `payment_type` (against_po, advance, advance_return), `reference`, `notes`, `created_by`, `timestamps`, `softDeletes`
- **On create**: Calls `PaymentService::create()` with:
  - `direction: 'pay'`
  - `party_type: 'rm_supplier'`
  - `party_id: supplier_id`
  - `payment_type: 'against_invoice'` or `'advance_payment'`
  - `allocations: [{ allocatable_type: RmPurchaseOrder, allocatable_id: purchase_order_id, amount }]`
  - → Auto-creates `Payment` + auto-posts `JournalEntry` (DR Accounts Payable / CR Cash/Bank)
- **Also updates**: `raw_material_suppliers.total_paid`, `due_balance`, `advance_balance`
- **Also updates**: `rm_purchase_orders.paid_amount`, `due_amount`, `payment_status`

**`factory_payments`** — Module-specific tracking for factory payments
- `id`, `payment_number` (auto: FP-XXXX), `factory_id` → `factories`, `production_order_id` → `production_orders` (nullable), `payment_id` → `payments` (**links to central Payment record**), `payment_date`, `amount` (decimal 15,2), `payment_method`, `payment_account_id` → `payment_accounts`, `payment_type` (against_order, advance, advance_return), `deduction_amount` (decimal 15,2 default 0 — damage compensation deducted from this payment), `net_amount` (decimal 15,2 — amount minus deduction, actual cash paid), `reference`, `notes`, `created_by`, `timestamps`, `softDeletes`
- **On create**: Calls `PaymentService::create()` with:
  - `direction: 'pay'`
  - `party_type: 'factory'`
  - `party_id: factory_id`
  - `amount: net_amount` (after damage deduction)
  - `allocations: [{ allocatable_type: ProductionOrder, allocatable_id: production_order_id, amount }]`
  - → Auto-creates `Payment` + auto-posts `JournalEntry` (DR Factory Payable / CR Cash/Bank)
- **If deduction_amount > 0**: Additionally creates compensation journal entry (DR Factory Payable / CR Factory Receivable) and updates linked `damage_compensations` records
- **Also updates**: `factories.total_paid`, `due_balance`

### Payment Flow Diagram
```
User clicks "Pay RM Supplier" or "Pay Factory"
         ↓
Manufacturing Module creates rm_supplier_payment / factory_payment
         ↓
Calls PaymentService::create(direction, party_type, amount, allocations)
         ↓
  ┌──────────────────────────────────────────────────────────┐
  │ Payment Module (centralized)                              │
  │                                                           │
  │  1. Creates Payment record (payment_number, direction...) │
  │  2. Creates PaymentAllocation (links to PO/Production)    │
  │  3. Calls JournalEntryService::createFromSource()         │
  │     → DR Payable / CR Cash/Bank (auto-posted)             │
  │  4. Returns payment_id                                    │
  └──────────────────────────────────────────────────────────┘
         ↓
Payment appears in:
  ✅ Payment Ledger (/payments)
  ✅ General Ledger (per account)
  ✅ Cashflow Statement (Operating → cash out)
  ✅ Cash Movement Report
  ✅ Supplier/Factory financial aggregates
```

### Where Each Payment Appears

| Report | RM Supplier Payment | Factory Payment | Factory Compensation (cash) |
|---|---|---|---|
| Payment Ledger (`/payments`) | ✅ party_type: rm_supplier, direction: pay | ✅ party_type: factory, direction: pay | ✅ party_type: factory, direction: receive |
| General Ledger (AP account) | ✅ DR Accounts Payable | — | — |
| General Ledger (Factory Payable) | — | ✅ DR Factory Payable | — |
| General Ledger (Factory Receivable) | — | — | ✅ CR Factory Receivable |
| General Ledger (Cash/Bank) | ✅ CR Cash | ✅ CR Cash | ✅ DR Cash |
| Cashflow Statement | ✅ Operating: cash out | ✅ Operating: cash out | ✅ Operating: cash in |
| Cash Movement Report | ✅ Paid out | ✅ Paid out | ✅ Received |
| RM Supplier Ledger | ✅ Shows in supplier show page | — | — |
| Factory Ledger | — | ✅ Shows in factory show page | ✅ Shows in factory show page |

### Payment Timing Options

#### A. Raw Material Supplier Payments — 5 options

**1. Pay on PO Creation (immediate full/partial payment)**
- On RM Purchase Order create form → "Payment" section with: amount, payment method, payment account
- If amount < grand_total → partial payment, PO starts with `payment_status: 'partial'`
- If amount = grand_total → full payment, PO starts with `payment_status: 'paid'`
- Creates `rm_supplier_payment` + `Payment` + `JournalEntry` immediately
- Journal: DR RM Inventory (1030) / CR Cash/Bank (for paid portion) + CR Accounts Payable (for due portion)

**2. Pay on Delivery (at GRN time)**
- On RM Receive (GRN) form → optional "Payment" section
- User can pay for received goods at delivery time
- Updates `rm_purchase_orders.paid_amount`

**3. Pay Later (credit/due)**
- No payment at PO creation or delivery
- PO stays `payment_status: 'unpaid'` or `'partial'`
- User pays separately via "Pay RM Supplier" action (from supplier show page or PO show page)
- `raw_material_suppliers.due_balance` tracks outstanding amount

**4. Advance Payment (before any PO)**
- Pay supplier without linking to any PO → `payment_type: 'advance'`
- Increases `raw_material_suppliers.advance_balance`
- Journal: DR Supplier Advance (1021) / CR Cash/Bank
- When a PO is created → option to "Deduct from advance" → reduces `advance_balance`, applies to PO
- Deduction journal: DR Accounts Payable / CR Supplier Advance (1021)

**5. Partial Payments (multiple payments over time)**
- Any PO can receive multiple payments
- Each payment updates `paid_amount` and `payment_status` (unpaid → partial → paid)
- Track via `PaymentAllocation` (multiple allocations for same PO)

#### B. Factory Payments — 6 options

**1. Pay on Production Order Creation (advance/deposit)**
- On Production Order create form → "Advance Payment" section
- Common pattern: "Pay 50% advance, rest on completion"
- Creates `factory_payment` with `payment_type: 'advance_on_order'`
- Updates `production_orders.paid_amount`
- Journal: DR Factory Advance (1045) / CR Cash/Bank

**2. Pay Per Lot (on each delivery)**
- When receiving a production lot → "Payment" section on lot receive form
- Pay making cost for that lot's units
- Amount suggested: `lot.making_cost + lot.delivery_charge + lot.other_costs`
- Creates `factory_payment` allocated to production order

**3. Pay on PO Complete (full settlement)**
- When marking PO as complete → "Final Settlement" step
- Shows: total owed, already paid, damage deductions, net due
- Single payment to settle everything
- Can auto-deduct damage compensation from final payment

**4. Pay Later (credit/due)**
- Factory has `payment_terms` (e.g., "Net 30")
- No payment at creation or lot time
- `factories.due_balance` tracks outstanding
- Pay separately via "Pay Factory" action

**5. Advance Payment (before any Production Order)**
- Pay factory without linking to any PO → `payment_type: 'advance'`
- Increases `factories.advance_balance`
- Journal: DR Factory Advance (1045) / CR Cash/Bank
- When Production Order is created → option to "Deduct from advance"
- Deduction: `production_orders.advance_deducted` field, reduces `advance_balance`
- Journal: DR Factory Payable (2020) / CR Factory Advance (1045)

**6. Pay with Damage Deduction**
- When paying factory → option to deduct pending damage compensation
- `factory_payments.deduction_amount` = damage offset
- `factory_payments.net_amount` = amount - deduction (actual cash paid)
- Cash journal: DR Factory Payable / CR Cash/Bank (net_amount only)
- Deduction journal: DR Factory Payable / CR Factory Receivable (deduction_amount)
- Both in same transaction for clarity

### New Account Codes for Advance Tracking
- `1021` — RM Supplier Advance (Asset — prepayments to suppliers)
- `1045` — Factory Advance (Asset — prepayments to factories)

### Payment UI Integration

**RM Purchase Order Create Form:**
```
┌─────────────────────────────────────────────────┐
│ RM Purchase Order                                │
│                                                   │
│ Supplier: [Select]    PO Date: [Date]            │
│ Items: [Raw material rows...]                     │
│                                                   │
│ ─── Payment (optional) ──────────────────────── │
│ □ Pay now                                         │
│   Amount: [________] (of BDT 72,000 total)       │
│   Method: [Cash ▾]   Account: [Cash in Hand ▾]  │
│                                                   │
│ □ Deduct from advance (Available: BDT 15,000)    │
│   Deduct: [________]                              │
│                                                   │
│ Due after payment: BDT XX,XXX                     │
│                                                   │
│ [Save as Draft]  [Save & Approve]                │
└─────────────────────────────────────────────────┘
```

**Production Order Create Form:**
```
┌─────────────────────────────────────────────────┐
│ Production Order                                  │
│                                                   │
│ Factory: [Select]    Order Date: [Date]          │
│ Items: [Catalog × Color × Size × Qty rows...]   │
│ BOM: [Raw material planned usage...]              │
│                                                   │
│ Estimated Making Cost: BDT 37,500                │
│                                                   │
│ ─── Advance Payment (optional) ──────────────── │
│ □ Pay advance                                     │
│   Amount: [________] (of BDT 37,500 estimated)   │
│   Method: [bKash ▾]   Account: [bKash ▾]        │
│                                                   │
│ □ Deduct from factory advance (Available: BDT X) │
│   Deduct: [________]                              │
│                                                   │
│ [Save as Draft]  [Save & Approve]                │
└─────────────────────────────────────────────────┘
```

**Lot Receive Form (payment section):**
```
┌─────────────────────────────────────────────────┐
│ Receive Lot — PO-0001 (LOT-PO0001-02)           │
│                                                   │
│ Items: [Received qty, Damaged qty per item...]   │
│ Making Cost: [15,000]  Delivery: [2,000]         │
│                                                   │
│ ─── Payment (optional) ──────────────────────── │
│ □ Pay for this lot                                │
│   Suggested: BDT 17,000 (making + delivery)      │
│   Amount: [________]                              │
│   Method: [Bank ▾]   Account: [DBBL ▾]          │
│                                                   │
│ □ Deduct damage compensation (Pending: BDT 1,500)│
│   Deduct: [________]                              │
│                                                   │
│ [Receive & Save]                                  │
└─────────────────────────────────────────────────┘
```

**Factory Final Settlement (on PO Complete):**
```
┌─────────────────────────────────────────────────┐
│ Complete PO-0001 — Final Settlement              │
│                                                   │
│ Total Making Cost:        BDT 37,500             │
│ Total Delivery:           BDT  5,300             │
│ Total Other:              BDT      0             │
│ ─────────────────────────────────                │
│ Gross Total:              BDT 42,800             │
│                                                   │
│ Less: Already Paid:      -BDT 32,000             │
│ Less: Advance Deducted:  -BDT  5,000             │
│ Less: Damage Deduction:  -BDT  1,500             │
│ ─────────────────────────────────                │
│ Net Due:                  BDT  4,300             │
│                                                   │
│ □ Pay now  Amount: [4,300]                        │
│   Method: [Cash ▾]   Account: [Cash in Hand ▾]  │
│                                                   │
│ □ Keep as due (pay later)                         │
│                                                   │
│ [Complete Order & Settle]                         │
└─────────────────────────────────────────────────┘
```

---

## Costing Logic — Progressive Cost with Final Reconciliation

### The Problem
Factory delivers in lots (e.g., 200 of 500). Each lot's finished goods must enter main inventory immediately so they can be sold. But the final cost isn't known until the entire PO is complete (more lots, damages, and returns may come).

### Solution: Three-Stage Progressive Costing

#### Stage 1: Per-Lot Provisional Cost (calculated when each lot arrives)
```
fabric_cost_share = (lot_good_units / total_order_qty) × total_fabric_issued_cost
                    ↑ proportional share of fabric cost based on this lot's good units

lot_direct_cost    = making_cost + delivery_charge + other_costs
net_damage_cost    = damage_cost_for_this_lot - factory_compensation_for_this_lot
fabric_return_credit = value of any fabric returned with this lot
normal_rm_waste    = normal RM waste cost for this lot (absorbed into product cost)
normal_product_waste = normal product waste cost for this lot (absorbed — fewer good units)

lot_total_cost = fabric_cost_share + lot_direct_cost + net_damage_cost 
                 - fabric_return_credit + normal_rm_waste
                 ↑ normal product waste is handled implicitly: fewer good_units in denominator

good_units = received - damaged - wasted  ← product waste reduces good units
provisional_cost_per_unit = lot_total_cost / good_units

Note: Abnormal waste (both RM and product) is NOT included here — it goes 
      directly to waste expense accounts, not into product cost.
```

**What happens**: 
1. `production_lots.provisional_cost_per_unit` is set
2. `production_lot_items.unit_cost` is set for each item
3. `InventoryService.adjustStock()` is called → good units enter `warehouse_stock` with this cost
4. `stock_ledger` records the entry with source_type `'manufacturing_output'`
5. `ProductVariant.cost_price` is updated (see Stage 2)

#### Stage 2: Running Weighted Average (updated after each lot)
After each lot, recalculate the running average across all lots received so far:
```
running_total_cost = sum(each_lot.provisional_cost_per_unit × each_lot.total_good) for all lots
running_total_good = sum(each_lot.total_good) for all lots
running_weighted_avg = running_total_cost / running_total_good
```

**What happens**:
1. `production_orders.running_weighted_avg_cost` is updated
2. `ProductVariant.cost_price` is updated to the running weighted average
3. This ensures the sell price vs cost price margin shown in the Product module is always based on the latest known cost

#### Stage 3: Final Cost Reconciliation (when PO marked complete)
```
total_fabric_cost      = sum(all fabric issuances) - sum(all fabric returns of good condition)
total_making_cost      = sum(all lots making_cost)
total_delivery_cost    = sum(all lots delivery_charge)
total_other_cost       = sum(all lots other_costs)
total_damage_cost      = sum(all damages total_damage_cost)
total_compensation     = sum(all damages compensation_received)
total_normal_rm_waste  = sum(all normal RM waste cost)      ← absorbed into product cost
total_good_units       = sum(all lots total_good)            ← already reduced by product waste

final_cost_per_unit = (total_fabric_cost + total_making_cost + total_delivery_cost 
                       + total_other_cost + total_damage_cost - total_compensation
                       + total_normal_rm_waste) 
                       / total_good_units

Note: Abnormal waste (RM + product) is already expensed separately and excluded.
      Normal product waste is implicitly handled via reduced good_units denominator.
      Normal RM waste is explicitly added to numerator.
```

**What happens**:
1. `production_orders.final_cost_per_unit` is set
2. If `final_cost_per_unit` ≠ `running_weighted_avg_cost`:
   - Calculate `cost_difference = final_cost_per_unit - running_weighted_avg_cost`
   - Create a **cost adjustment** stock ledger entry (source_type: `'manufacturing_cost_adjustment'`)
   - Update `ProductVariant.cost_price` to `final_cost_per_unit`
   - Create journal entry: if cost increased → DR Finished Goods Inventory, CR WIP; if decreased → DR WIP, CR Finished Goods Inventory
3. All `production_order_items.current_cost_per_unit` are updated to final cost

### Example Walkthrough
```
PO-0001: 500 Half Shirts (Blue, M) — Factory: ABC Garments
  Product: "Half Shirt" (variable), Variant: "Blue / M" (product_variant_id: 42)
  BOM: 600 yards cotton fabric (RM-001) @ BDT 120/yard, expected return: 40 yards
Fabric issued: 600 yards cotton @ BDT 120/yard = BDT 72,000

LOT 1 (Day 15): 200 received, 10 damaged (factory fault), 3 wasted (quality rejection, normal)
  RM waste: 20 yards cutting waste (normal, 3.3%)
    → normal_rm_waste_cost = 20 × 120 = BDT 2,400 (absorbed into cost)
    → journal: DR WIP 2,400 / CR RM Inventory 2,400
  Product waste: 3 shirts total loss (normal)
    → good_units = 200 - 10 - 3 = 187
  fabric_share = (187/500) × 72,000 = BDT 26,928
  making_cost = BDT 15,000 (BDT 75/unit × 200)
  delivery = BDT 2,000
  damage_cost = 10 × estimated_cost = BDT 1,500 (factory will compensate)
  net_damage = 1,500 - 1,500 = BDT 0
  lot_cost = 26,928 + 15,000 + 2,000 + 0 + 2,400 = BDT 46,328
  provisional_cost = 46,328 / 187 = BDT 247.74/unit
  → 187 units added to warehouse_stock at BDT 247.74 each ✓
  → ProductVariant (id:42).cost_price = BDT 247.74

LOT 2 (Day 25): 180 received, 5 damaged (own fault), 2 wasted (pressing, abnormal)
  RM waste: 15 yards cutting waste (normal) + 8 yards machine waste (abnormal)
    → normal_rm_waste = 15 × 120 = BDT 1,800 (absorbed)
    → abnormal_rm_waste = 8 × 120 = BDT 960 → journal: DR RM Waste Loss (5020) / CR WIP
  Product waste: 2 shirts pressing loss (abnormal)
    → abnormal_product_waste = 2 × ~248 = BDT 496 → journal: DR Product Waste Loss (5025) / CR WIP
    → good_units = 180 - 5 - 0 = 175 (abnormal waste NOT deducted from good_units for cost calc)
    Actually: good_units = 180 - 5 = 175 (only normal waste reduces good_units for cost absorption)
  fabric_share = (175/500) × 72,000 = BDT 25,200
  making_cost = BDT 13,500
  delivery = BDT 1,800
  damage_cost = 5 × 248 = BDT 1,240 (own fault, no compensation)
  lot_cost = 25,200 + 13,500 + 1,800 + 1,240 + 1,800 = BDT 43,540
  provisional_cost = 43,540 / 175 = BDT 248.80/unit
  → 175 units added to warehouse_stock at BDT 248.80 ✓
  → Running avg = (247.74×187 + 248.80×175) / 362 = BDT 248.25
  → ProductVariant.cost_price = BDT 248.25

LOT 3 (Day 35): 120 received, 0 damaged, 0 wasted + Factory returns 50 yards fabric (good)
  fabric_return_credit = 50 × 120 = BDT 6,000 → back to RM inventory
  adjusted_fabric_cost = 72,000 - 6,000 = BDT 66,000
  fabric_share = (120/500) × 66,000 = BDT 15,840
  making_cost = BDT 9,000
  delivery = BDT 1,500
  lot_cost = 15,840 + 9,000 + 1,500 = BDT 26,340
  provisional_cost = 26,340 / 120 = BDT 219.50/unit
  → 120 units added to warehouse_stock at BDT 219.50 ✓

PO COMPLETE: 500 ordered, 482 good (187+175+120), 15 damaged, 3 wasted (normal)
  BOM comparison:
    Planned: 600 yards, Expected return: 40 yards, Expected consumption: 560 yards
    Actual:  600 issued, 50 returned, 35 wasted (normal), 8 wasted (abnormal)
    Actual consumption: 600 - 50 = 550 yards (vs 560 planned → 10 yards saved ✓)
  
  Final cost reconciliation:
    fabric_cost    = 72,000 - 6,000 (returned) = BDT 66,000
    making_cost    = 37,500
    delivery_cost  = 5,300
    damage_cost    = 1,240 (net after compensation)
    normal_rm_waste = 4,200 (20×120 + 15×120)
    good_units     = 482
    
    final_cost = (66,000 + 37,500 + 5,300 + 1,240 + 4,200) / 482
               = BDT 114,240 / 482 = BDT 237.01/unit
    
    Abnormal costs (expensed separately, NOT in product cost):
      RM waste (abnormal): BDT 960 → RM Waste Loss (5020)
      Product waste (abnormal): BDT 496 → Product Waste Loss (5025)
    
  Adjustment: 237.01 - 248.25 = -11.24/unit → cost went DOWN
    → DR WIP, CR Finished Goods Inventory (for 482 units × BDT 11.24)
    → ProductVariant.cost_price updated to BDT 237.01
```

---

## Journal Entries & Cashflow Integration

Uses existing `AccountingIntegrationService` + `JournalEntryService`.

### How Cashflow Works in BizPOS
- **Source of truth**: Posted journal entries in the `journal_entries` + `journal_entry_lines` tables
- **Cashflow Statement**: `AccountingReportService::getCashFlowStatement()` filters all posted journal entry lines that touch Cash/Bank accounts (`is_bank_account = true` or codes 1001-1003)
- **Classification**: Groups by `source_type` → Operating / Investing / Financing
- **Current operating types**: `sale`, `purchase`, `expense`, `payment`, `manual`
- **Key file**: `Modules/Accounting/app/Services/AccountingReportService.php`

### New Account Codes Needed
- `1021` — RM Supplier Advance (Asset — prepayments to raw material suppliers)
- `1030` — Raw Material Inventory (Asset)
- `1035` — Work-in-Progress (Asset)
- `1040` — Factory Receivable (Asset — damage compensation owed by factory)
- `1045` — Factory Advance (Asset — prepayments to factories)
- `2020` — Factory Payable (Liability — making cost owed to factories)
- `5010` — Manufacturing Cost / COGS-Manufacturing (Expense)
- `5015` — Damage Loss (Expense)
- `5020` — Raw Material Waste Loss (Expense — abnormal waste only)
- `5025` — Product Waste Loss (Expense — abnormal waste only)

### New `source_type` Values for Journal Entries
These must be registered in `AccountingReportService::getCashFlowStatement()` under **Operating Activities**:

| source_type | Used by | Example |
|---|---|---|
| `rm_purchase` | RM purchase on credit | DR RM Inventory / CR AP |
| `rm_purchase_payment` | Payment to RM supplier | DR AP / CR Cash ← **CASH OUT** |
| `manufacturing_fabric_issue` | Fabric issued to factory | DR WIP / CR RM Inventory |
| `manufacturing_fabric_return` | Fabric returned from factory | DR RM Inventory / CR WIP |
| `manufacturing_lot` | Lot received (making + delivery) | DR WIP / CR Factory Payable + Cash |
| `manufacturing_lot_delivery` | Delivery/other costs paid | DR WIP / CR Cash ← **CASH OUT** |
| `manufacturing_output` | Good units → finished goods | DR FG Inventory / CR WIP |
| `manufacturing_damage` | Damage recorded | DR Receivable or Loss / CR WIP |
| `manufacturing_compensation` | Factory compensates damage | DR Cash or Payable / CR Receivable |
| `manufacturing_waste` | Waste recorded (abnormal) | DR Waste Loss / CR WIP |
| `manufacturing_cost_adjustment` | Final cost reconciliation | DR/CR FG Inventory ↔ WIP |
| `factory_payment` | Payment to factory | DR Factory Payable / CR Cash ← **CASH OUT** |
| `rm_supplier_advance` | Advance to RM supplier | DR Supplier Advance / CR Cash ← **CASH OUT** |
| `rm_advance_deduction` | Advance deducted from RM PO | DR AP / CR Supplier Advance (no cash) |
| `factory_advance` | Advance to factory | DR Factory Advance / CR Cash ← **CASH OUT** |
| `factory_advance_deduction` | Advance deducted from prod order | DR Factory Payable / CR Factory Advance (no cash) |

### Journal Entry Matrix (20 event types)

| # | Event | Debit | Credit | source_type | Hits Cash? | Cashflow |
|---|---|---|---|---|---|---|
| 1 | Raw material purchased | RM Inventory (1030) | Accounts Payable / Cash | `rm_purchase` | Only if cash payment | Out (Operating) |
| 2 | Fabric issued to factory | WIP (1035) | RM Inventory (1030) | `manufacturing_fabric_issue` | No | — |
| 3 | Fabric returned (good) | RM Inventory (1030) | WIP (1035) | `manufacturing_fabric_return` | No | — |
| 4 | Fabric returned (damaged) | Damage Loss (5015) | WIP (1035) | `manufacturing_fabric_return` | No | — |
| 5 | RM waste (normal) | WIP (1035) | RM Inventory (1030) | `manufacturing_waste` | No | — |
| 6 | RM waste (abnormal) | RM Waste Loss (5020) | WIP (1035) | `manufacturing_waste` | No | — |
| 7 | Lot received (making cost) | WIP (1035) | Factory Payable (2020) | `manufacturing_lot` | No | — |
| 8 | Lot received (delivery/other) | WIP (1035) | Cash/Bank | `manufacturing_lot_delivery` | **YES** | **Out (Operating)** |
| 9 | Good units → finished goods | FG Inventory (1020) | WIP (1035) | `manufacturing_output` | No | — |
| 10 | Product waste (normal) | *(no entry)* | *(no entry)* | — | No | — |
| 11 | Product waste (abnormal) | Product Waste Loss (5025) | WIP (1035) | `manufacturing_waste` | No | — |
| 12 | Damage (factory fault) | Factory Receivable (1040) | WIP (1035) | `manufacturing_damage` | No | — |
| 13 | Damage (own fault) | Damage Loss (5015) | WIP (1035) | `manufacturing_damage` | No | — |
| 14a | Compensation (cash) | Cash/Bank | Factory Receivable (1040) | `manufacturing_compensation` | **YES** | **In (Operating)** |
| 14b | Compensation (deduct from payable) | Factory Payable (2020) | Factory Receivable (1040) | `manufacturing_compensation` | No | — |
| 14c | Compensation (replacement) | FG Inventory (1020) | Factory Receivable (1040) | `manufacturing_compensation` | No | — |
| 15 | Final cost adjustment (up) | FG Inventory (1020) | WIP (1035) | `manufacturing_cost_adjustment` | No | — |
| 16 | Final cost adjustment (down) | WIP (1035) | FG Inventory (1020) | `manufacturing_cost_adjustment` | No | — |
| 17 | RM supplier payment (against PO) | Accounts Payable | Cash/Bank | `rm_purchase_payment` | **YES** | **Out (Operating)** |
| 18 | Factory payment (against order) | Factory Payable (2020) | Cash/Bank | `factory_payment` | **YES** | **Out (Operating)** |
| 19 | RM supplier advance payment | RM Supplier Advance (1021) | Cash/Bank | `rm_supplier_advance` | **YES** | **Out (Operating)** |
| 20 | RM advance deducted against PO | Accounts Payable | RM Supplier Advance (1021) | `rm_advance_deduction` | No | — |
| 21 | Factory advance payment | Factory Advance (1045) | Cash/Bank | `factory_advance` | **YES** | **Out (Operating)** |
| 22 | Factory advance deducted against order | Factory Payable (2020) | Factory Advance (1045) | `factory_advance_deduction` | No | — |

### Cashflow Summary — What Shows Up

**Cash OUT (Operating Activities):**
| Event | When | Amount |
|---|---|---|
| RM Supplier Payment (#17) | When you pay the fabric supplier | Payment amount |
| Factory Payment (#18) | When you pay the factory making cost | Payment amount (reduced if damage was deducted via 14b) |
| Delivery/Other Costs (#8) | When lot arrives and delivery is paid | Delivery charge + other costs |

**Cash IN (Operating Activities):**
| Event | When | Amount |
|---|---|---|
| Factory Damage Compensation (#14a) | When factory pays cash for damaged goods | Compensation amount |

**NOT in Cashflow (Accrual Only):**
All other entries (#2-7, #9-13, #14b, #14c, #15-16) are internal inventory movements, accrual bookings, or non-cash offsets. They affect the Balance Sheet (asset/liability accounts) but NOT the Cashflow Statement — until the actual cash payment happens via #17 or #18.

### Required Changes to Existing Files

**`Modules/Accounting/app/Services/AccountingReportService.php`** — Update `getCashFlowStatement()`:
```php
// Add manufacturing source_types to Operating Activities classification
$operatingTypes = [
    'sale', 'purchase', 'expense', 'payment', 'manual',
    // NEW: Manufacturing
    'rm_purchase', 'rm_purchase_payment',
    'manufacturing_lot_delivery', 'manufacturing_compensation',
    'factory_payment',
];
```

**`Modules/Report/app/Services/AdditionalReportService.php`** — Update `cashMovement()`:
- Include manufacturing payment types in the cash movement summary report

### Waste vs Damage — Key Distinction

| | **Damage** | **Waste** |
|---|---|---|
| **Definition** | Defective but exists physically | Completely lost, no recovery |
| **Recovery** | May be sold at discount, or compensated by factory | None — total write-off |
| **Compensation** | Factory may pay for factory-fault damages | No compensation possible |
| **Normal** | N/A (all damages tracked with responsibility) | Expected % (e.g., 5% cutting waste) — absorbed into cost |
| **Abnormal** | N/A | Unexpected excess — expensed separately |
| **Example (RM)** | Fabric arrives with defects from supplier | 30 yards of cutting waste from 600 yards issued |
| **Example (Product)** | 10 shirts with stitching defects (may be resewn or sold as B-grade) | 5 shirts completely ruined in pressing machine (total loss) |

---

## Service Layer

### Services (in `Modules/Manufacturing/app/Services/`)

1. **FactoryService** — CRUD + financial aggregates (total_orders, total_paid, due_balance)
2. **CatalogService** — CRUD for garment catalogs
3. **RawMaterialService** — CRUD for raw material master data
4. **RawMaterialSupplierService** — CRUD + financial aggregates
5. **RmPurchaseService** — PO lifecycle: create → approve → receive → complete/cancel
6. **RmStockService** — Stock adjustments, ledger entries, weighted avg cost calculation
7. **ProductionOrderService** — Production PO lifecycle: draft → approved → in_progress → partial_delivered → completed. Handles BOM creation at PO time
8. **FabricIssuanceService** — Issue fabric from RM stock to factory (validates against BOM planned quantities), deduct inventory, record WIP
9. **FabricReturnService** — Receive unused fabric back from factory, add to RM stock (good) or record damage (damaged), update BOM actual_returned_quantity
10. **ProductionLotService** — Receive lots from factory, calculate provisional cost per lot, call `InventoryService.adjustStock()` to add good units to main product inventory, update running weighted avg
11. **ProductionDamageService** — Track damages, assign responsibility, manage compensation, create journal entries
12. **RmWasteService** — Record raw material waste per order/lot, classify normal vs abnormal, calculate waste cost, create journal entries for abnormal waste
13. **ProductWasteService** — Record product waste per order/lot, classify normal vs abnormal, reduce good_units, create journal entries for abnormal waste
14. **ProductionCostService** — Per-lot provisional costing (includes normal waste) + running weighted avg + final reconciliation on PO complete + cost adjustment entries
15. **ManufacturingAccountingService** — All journal entry creation for manufacturing events (18 event types, extends AccountingIntegrationService pattern)
16. **ProductVariantMappingService** — Helper to map catalog+color+size to existing ProductVariant (or suggest creating new variants if not mapped)

---

## Controllers & Routes

### Route Structure (`Modules/Manufacturing/routes/web.php`)

```
/manufacturing/
├── factories/                         (CRUD: index, create, store, show, edit, update, destroy)
├── catalogs/                          (CRUD: index, create, store, edit, update, destroy)
├── colors/                            (CRUD: index, create, store, edit, update, destroy)
├── sizes/                             (CRUD: index, create, store, edit, update, destroy)
├── raw-materials/                     (CRUD: index, create, store, show, edit, update, destroy)
├── raw-material-suppliers/            (CRUD: index, create, store, show, edit, update, destroy)
│   └── {supplier}/payments/           (index, store)
├── rm-purchases/                      (CRUD + workflow)
│   ├── {purchase}/approve             (POST)
│   ├── {purchase}/cancel              (POST)
│   └── {purchase}/receive/            (create GET, store POST)
├── production-orders/                 (CRUD + workflow)
│   ├── {order}/approve                (POST)
│   ├── {order}/cancel                 (POST)
│   ├── {order}/complete               (POST — triggers final cost reconciliation)
│   ├── {order}/bom/                   (GET — view BOM, defined at PO creation in create form)
│   ├── {order}/issue-fabric/          (create GET, store POST — validates against BOM)
│   ├── {order}/return-fabric/         (create GET, store POST — factory returns unused)
│   ├── {order}/lots/                  (create GET, store POST)
│   │   └── {lot}/                     (show GET)
│   │       └── damages/               (store POST)
│   └── {order}/costing/               (show GET — cost summary, progressive vs final)
├── factory-payments/                  (index, store)
├── damages/                           (index GET — all damages across orders, filterable by status/responsibility)
│   ├── {damage}/                      (show GET — damage detail with compensation history)
│   └── {damage}/compensations/        (POST — record compensation: cash, deduct-from-payable, or replacement)
├── wastes/                            (waste management)
│   ├── raw-materials/                 (index GET — all RM waste, create GET/POST)
│   └── products/                      (index GET — all product waste, create GET/POST)
└── reports/
    ├── rm-stock/                      (GET — raw material stock report)
    ├── production/                    (GET — production status report)
    ├── damage/                        (GET — damage & compensation report)
    └── cost-analysis/                 (GET — cost analysis report)
```

---

## Blade Views

```
Modules/Manufacturing/resources/views/
├── factories/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── catalogs/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── colors/
│   └── index.blade.php            (inline add/edit with AJAX)
├── sizes/
│   └── index.blade.php            (inline add/edit with AJAX)
├── raw-materials/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php             (stock history, usage)
├── suppliers/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php             (payment history, PO history)
├── rm-purchases/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── show.blade.php
│   └── receive.blade.php          (GRN form)
├── production-orders/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── show.blade.php             (order overview: BOM status, lots timeline, cost summary)
│   ├── issue-fabric.blade.php     (fabric issuance — shows BOM planned vs issued)
│   ├── return-fabric.blade.php    (fabric return — unused material from factory)
│   ├── receive-lot.blade.php      (lot receiving with damage inputs + auto variant mapping)
│   ├── lot-details.blade.php      (single lot: items, damages, provisional cost)
│   └── costing.blade.php          (progressive cost: per-lot + running avg + final)
├── damages/
│   └── index.blade.php            (all damages, filterable by order/factory/status)
├── wastes/
│   ├── rm-waste.blade.php         (RM waste: index + create, normal vs abnormal, per order/lot)
│   └── product-waste.blade.php    (product waste: index + create, per order/lot)
└── reports/
    ├── rm-stock.blade.php          (current stock + movement history)
    ├── production.blade.php        (order status, completion rates)
    ├── damage.blade.php            (damage analysis, compensation tracking)
    ├── waste.blade.php             (waste analysis: normal vs abnormal, % trends, cost impact)
    └── cost-analysis.blade.php     (cost per unit trends, factory comparison, waste impact)
```

---

## Sidebar Integration

Add under a new **Manufacturing** group in sidebar (between "Transactions" and "People"):

```
Manufacturing
├── Dashboard (overview stats: active orders, pending lots, stock alerts)
├── Factories
├── Catalogs
├── Raw Materials
│   ├── Materials List
│   ├── Stock Report
│   └── Suppliers
├── Purchase Orders (RM)
├── Production Orders
│   ├── All Orders
│   ├── Fabric Issuance
│   └── Lot Tracking
├── Damages
├── Wastes
│   ├── Raw Material Waste
│   └── Product Waste
└── Reports
    ├── Production Report
    ├── Damage Report
    ├── Waste Report
    └── Cost Analysis
```

---

## Implementation Phases

### Phase 1: Master Data
- Module scaffolding (Nwidart module generation)
- Migrations: `factories`, `catalogs`, `colors`, `sizes`, `raw_materials`, `raw_material_suppliers`
- Models with relationships
- Services (CRUD)
- Controllers + Blade views (index, create, edit, show)
- Routes, sidebar entry
- Seeders for default data (sizes, sample catalogs)

### Phase 2: Raw Material Procurement
- Migrations: `rm_purchase_orders`, `rm_purchase_items`, `rm_receives`, `rm_receive_items`, `rm_stocks`, `rm_stock_ledger`
- Models, Services, Controllers, Views
- PO workflow: draft → approve → receive (GRN) → complete
- Stock management (adjust on receive, weighted avg cost calculation)
- Stock ledger entries
- Supplier payment tracking + financial aggregates

### Phase 3: Production Management
- Migrations: `production_orders`, `production_order_items`, `production_order_materials` (BOM), `fabric_issuances`, `fabric_issuance_items`, `fabric_returns`, `fabric_return_items`, `production_lots`, `production_lot_items`, `production_damages`, `rm_wastes`, `product_wastes`
- Production order creation with BOM (planned raw materials + expected returns) + product variant mapping
- Production order workflow: draft → approved → in_progress → partial_delivered → completed
- Fabric issuance (validates against BOM, deducts from RM stock, moves cost to WIP)
- Fabric returns (unused fabric from factory → back to RM stock or damage)
- Lot receiving: damage tracking per item, provisional cost calculation, **auto-add good units to main `warehouse_stock`** via `InventoryService.adjustStock()`
- Damage management with responsibility assignment
- **Raw material waste tracking**: Record cutting waste, spillage etc. per order/lot. Normal waste absorbed into cost, abnormal waste expensed to Waste Loss (5020)
- **Product waste tracking**: Record quality rejections, total losses per lot. Normal waste reduces good_units (implicit cost absorption), abnormal waste expensed to Product Waste Loss (5025)

### Phase 4: Payments & Accounting
- Migrations: `rm_supplier_payments`, `factory_payments` (with `payment_id` FK to central Payment table)
- **Integrate with existing Payment Module**: Add `rm_supplier` and `factory` as new `party_type` values, update `PaymentService::buildJournalLines()`, add `RmPurchaseOrder` and `ProductionOrder` as allocatable types
- New chart of accounts entries (1030, 1035, 1040, 2020, 5010, 5015, 5020, 5025)
- Three-stage progressive costing: per-lot provisional → running weighted avg → final reconciliation
- Cost adjustment entries when PO completes (if final ≠ running avg)
- Journal entries for all manufacturing events (20 event types — see journal entry matrix)
- Register manufacturing `source_type` values in `AccountingReportService::getCashFlowStatement()` under Operating Activities
- Update `ProductVariant.cost_price` at each stage
- RM supplier payments → creates Payment + JournalEntry → appears in payment ledger + general ledger + cashflow
- Factory payments (with damage deduction support) → creates Payment + JournalEntry → appears in all ledgers
- Factory damage compensation (cash) → creates receive Payment → appears as cash in

### Phase 5: Reports & Dashboard
- Manufacturing dashboard (active orders, pending lots, low stock alerts)
- Raw material stock report
- Production order status report
- Damage & compensation report
- Cost analysis report (per-unit cost trends, factory comparison)
- Integration with main dashboard widgets

---

## Key Existing Files to Modify

- `Modules/Core/resources/views/partials/sidebar.blade.php` — Add Manufacturing menu group
- `Modules/Accounting/app/Services/AccountingIntegrationService.php` — Add manufacturing journal entry methods
- `Modules/Accounting/app/Services/AccountingReportService.php` — Register manufacturing `source_type` values in `getCashFlowStatement()` under Operating Activities so cash transactions appear in cashflow
- `Modules/Report/app/Services/AdditionalReportService.php` — Include manufacturing payment types in `cashMovement()` report
- `Modules/Payment/app/Services/PaymentService.php` — Support new `party_type` values: `'rm_supplier'` and `'factory'`. Update `buildJournalLines()` to map these to correct accounts (Factory Payable 2020 instead of AP 2001 for factory payments)
- `Modules/Payment/app/Models/Payment.php` — Add `'rm_supplier'` and `'factory'` to `party_type` validation/enum. Add `party()` relationship resolver for `RawMaterialSupplier` and `Factory` models
- `Modules/Payment/app/Models/PaymentAllocation.php` — Ensure polymorphic support for `RmPurchaseOrder` and `ProductionOrder` models (allocatable_type)
- `Modules/Accounting/database/seeders/` — Seed new account codes (1030, 1035, 1040, 2020, 5010, 5015, 5020, 5025)
- `Modules/Inventory/app/Services/InventoryService.php` — Add `'manufacturing_output'` and `'manufacturing_cost_adjustment'` as valid source_types; called when finished goods enter main product inventory
- `Modules/Variant/app/Models/ProductVariant.php` — cost_price updated by ProductionCostService after each lot and on PO completion
- `Modules/Product/app/Models/Product.php` — Referenced by production_order_items for variant mapping

---

## Verification Plan

1. **Master data CRUD**: Create factory, catalog, colors, sizes, raw materials, suppliers — verify all pages work
2. **RM Purchase flow**: Create PO → approve → receive (GRN) → verify RM stock updates and ledger entries
3. **BOM + Variant Mapping**: Create production order with BOM (planned materials + expected returns) and map each item to a ProductVariant → verify BOM is saved and variants linked
4. **Fabric Issuance**: Issue fabric against BOM → verify RM stock deducted, WIP journal entry created, BOM `actual_issued_quantity` updated
5. **Lot Receiving + Progressive Cost**: Receive lot → verify:
   - Provisional cost calculated correctly
   - Good units added to `warehouse_stock` for the mapped ProductVariant
   - `stock_ledger` entry with source_type `'manufacturing_output'`
   - `ProductVariant.cost_price` updated to running weighted average
6. **Multiple lots**: Receive 2-3 lots → verify running avg updates after each → mark PO complete → verify final cost reconciliation + cost adjustment entry if needed
7. **Fabric Returns**: Factory returns unused fabric → verify RM stock increased (good condition) or damage recorded (damaged condition) → BOM `actual_returned_quantity` updated
8. **Damage flow**: Record factory-fault damage → verify journal entry → record compensation received → verify cash flow
9. **RM Waste**: Record 30 yards cutting waste (normal, 5%) → verify absorbed into lot cost → record 50 yards machine waste (abnormal) → verify expensed to RM Waste Loss (5020), NOT in product cost
10. **Product Waste**: Record 3 shirts quality rejection (normal) → verify `total_good` reduced, cost per unit increased → record 2 shirts pressing loss (abnormal) → verify expensed to Product Waste Loss (5025)
11. **Accounting**: Verify all 18 journal entry types are balanced (total debits = total credits)
12. **End-to-end**: Full cycle from fabric purchase → issue → lots (with waste + damages) → returns → complete → verify final product cost in Product module matches manual calculation. Verify BOM actual vs planned comparison
