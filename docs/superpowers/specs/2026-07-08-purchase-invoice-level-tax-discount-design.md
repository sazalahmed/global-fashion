# Purchase — Invoice-level VAT/Tax & Discount (P3)

**Date:** 2026-07-08
**Module:** `Modules/Purchase`
**Status:** Approved

## Context
- `purchases` already has `discount_amount`, `tax_amount` (both summed from lines),
  `shipping_cost`, `subtotal`, `grand_total`.
- Per-line `discount` + `tax_rate` inputs already exist and work.
- Missing: invoice-level (whole-order) Discount and VAT/Tax.

## Schema (migration — add columns to purchases)
- `order_discount` decimal(15,2) default 0
- `order_tax_rate` decimal(6,2) default 0   (percent; used when order_tax_mode = 'percent')
- `order_tax_amount` decimal(15,2) default 0 (resolved amount, always stored)
- `order_tax_mode` string(10) default 'percent' (percent|flat)

## Calculation (`PurchaseService::calculateTotals`)
- lineTotalSum = Σ item.line_total (already includes per-line discount + tax)
- subtotal = Σ (qty × unit_price)   (unchanged)
- lineDiscount = Σ item.discount_amount
- lineTax = Σ item.tax_amount
- taxableForOrder = max(0, subtotal − lineDiscount − order_discount)
- order_tax_amount = order_tax_mode==='percent' ? round(taxableForOrder × order_tax_rate/100, 2) : order_tax_amount(input)
- grand_total = lineTotalSum − order_discount + order_tax_amount + shipping_cost
- `discount_amount` (header) = lineDiscount + order_discount (so the summary "Discount" reflects total)
- `tax_amount` (header) = lineTax + order_tax_amount
- due_amount = max(0, grand_total − paid_amount)

## Store/Update (service + requests)
- Accept `order_discount`, `order_tax_rate`, `order_tax_amount`, `order_tax_mode` on the
  purchase (persisted before calculateTotals; calc resolves order_tax_amount from mode).
- StorePurchaseRequest / UpdatePurchaseRequest: add nullable numeric ≥ 0 rules;
  `order_tax_mode` in:percent,flat; order_discount ≤ subtotal-ish (validated soft).

## Forms (create + edit)
- Add to the totals card: **Order Discount**, **Order VAT/Tax** (input + a %/flat toggle),
  alongside existing Shipping.
- JS live summary: Subtotal → − Line Discounts → − Order Discount → + Line Tax →
  + Order VAT → + Shipping → Grand Total. Recompute on any change.
- Keep per-line Discount + Tax% columns (clear headers).

## Show / Print / PDF
- Add "Order Discount" and "Order VAT/Tax" rows to the invoice breakdown when > 0.

## Verification
- Create PO: 2 lines, per-line discount + tax, order_discount, order VAT % → grand total matches formula; JE (recordPurchase) uses grand_total.
- Flat order tax mode works.
- Edit recomputes correctly.
- Show/print show the new lines.
- Regression: PO with no order-level values behaves exactly as before.
