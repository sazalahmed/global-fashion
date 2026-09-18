# BizPOS Issues — Fix Plan

Source: `BizPos Issues (2).xlsx` (sheet `new-issues`). 11 issues have content (Bug_99, 107–113, 115, 117, 118); Bug_98/114/116 and Bug_119–183 are empty placeholders.

Grouped by theme and ordered by priority (money/correctness first, then function, then design). Effort: **S** ≤1h, **M** ~half day, **L** ≥1 day.

---

## Group A — Pricing & data correctness (highest priority — affects money)

### Bug_118 — Storefront discount price mismatches admin "Price After Discount" — **M/L, high risk**
- **Where:** Admin product edit (`Modules/Product/.../edit.blade.php`) shows correct `Price After Discount` (e.g. sell 850 − fixed 155 = **695**). The model method `Product::displayPrice()` (`Modules/Product/app/Models/Product.php:340`) already computes this correctly (`fixed → sell − value`, `percentage → sell − sell*value/100`).
- **Root issue:** The storefront **add-to-cart and checkout** paths don't reuse `displayPrice()->effective`, so the charged/cart price diverges from the displayed one. Per the issue note: "check in the controller for add to cart, checkout process."
- **Plan:**
  1. Audit `Modules/Ecommerce/app/...` cart + checkout services/controllers (`StorefrontService`, cart controller, `CheckoutController`/order placement) for where unit price is taken — confirm whether they use `sell_price`, a re-derived discount, or `displayPrice()`.
  2. Make a single source of truth: route all storefront price reads (card, detail, cart add, cart line, checkout, order total) through `Product::displayPrice()->effective` (and the variant equivalent).
  3. Re-validate cart prices from DB at checkout against `displayPrice()` (CLAUDE.md price-security rule).
  4. Verify end-to-end with product 5: detail = cart = checkout = 695.

### Bug_117 — Storefront listing shows wrong/inconsistent prices — **M, medium risk**
- **Where:** Storefront shop cards (`Modules/Ecommerce/.../storefront/partials/product-card.blade.php` uses `displayPrice()`).
- **Likely cause:** Same family as 118 — variant-priced products show base price on the card but a different price on detail/cart, or the discount badge % vs price don't agree.
- **Plan:** Treat together with Bug_118. After unifying on `displayPrice()`, sweep the listing, detail, flash-deal, search, wishlist, compare partials to confirm they all read the same accessor. Add a quick regression check across a discounted simple product and a discounted variable product.

### Bug_115 — Product view: supplier not shown + not linked; rename "IV cost" → "Purchase Price" — **S/M, low risk**
- **Where:** Admin product show/edit (`Modules/Product/resources/views/show.blade.php` / `edit.blade.php`). Screenshot shows an empty **Supplier** row even after a purchase, and a cost stat to relabel.
- **Plan:**
  1. Populate the product's supplier on the view. Decide source: product's own `supplier_id`, or the latest linked purchase's supplier. Display the name as a link to the supplier view page (`route('suppliers.show', …)`).
  2. If `supplier_id` isn't being set on purchase receive, wire it (or derive "last purchased from").
  3. Rename the "IV Cost" / cost label to **"Purchase Price"** wherever it appears on the product view (grep `IV Cost`/`Total Cost` in product views).

---

## Group B — Functional bug

### Bug_99 — Inventory "Adjust" row action is a dead link — **S, low risk**
- **Where:** `Modules/Inventory/resources/views/index.blade.php:116` — `<a class="dropdown-item" href="#">… Adjust</a>` (the `#` is the bug). "History" beside it already works.
- **Plan:** Point "Adjust" to the New Stock Adjustment page, pre-selecting that product/variant — e.g. `route('inventory.adjustments', ['product_id' => $stock->product->id, 'variant_id' => $stock->variant_id ?? null])` (or a dedicated create route). Confirm `adjustments-create.blade.php` / its controller can accept and pre-fill the product/variant from query params; add that pre-fill if missing.

---

## Group C — Validation

### Bug_110 — Backdate limit should be max 1 year (currently 30 days) — **S, low risk**
- **Where:** `app/Rules/AllowedTransactionDate.php:34` — `backdate_limit_days` defaults to **30**.
- **Plan:**
  1. Change the default from `30` → `365`. This automatically applies to all 10 modules using the rule (Sale, Purchase, Purchase/Sale Return, Expense, Payment, Supplier Payment, Quotation, AdSpend, POS) — no per-module edits needed.
  2. (Recommended) Add a configurable **"Back-date limit (days)"** input to Settings → Business (`Modules/Setting/resources/views/index.blade.php`, next to "Allow Back-dated Transactions") and cast it as integer in `SettingService` so admins can tune it without code.
  3. Update the inline error/help copy that says "30 days" if any is hardcoded in views.

---

## Group D — Page-specific design fixes

### Bug_107 — PO "Add New Supplier" modal: make selected fields full-width (12-col) — **S, low risk**
- **Where:** `Modules/Purchase/resources/views/components/quick-add-supplier-modal.blade.php`. Contact Person, Phone, Email, Address are currently `col-md-6` (2-up).
- **Plan:** Change those fields to `col-12` (one per row / 12-column) as requested. Keep Company Name as-is. Verify modal still fits.

### Bug_112 — PO show: move "Receive Stock" into Actions; hide Actions card when empty — **S/M, low risk**
- **Where:** `Modules/Purchase/resources/views/show.blade.php` — "Receive Stock" currently lives in the **Receiving History** card header; the **Actions** card sometimes renders empty.
- **Plan:**
  1. Move the "Receive Stock" button into the **Actions** card (alongside Edit / Cancel PO / Delete), keeping its existing visibility condition (only when not fully received).
  2. Wrap the entire Actions card in a guard so it only renders when ≥1 action is available (otherwise hide the block). Remove "Receive Stock" from the Receiving History header.

### Bug_111 + Bug_113 — Supplier payment page redesign + field swap — **M, low risk** ⚠️ needs 1 confirmation
- **Where:** Supplier payment page = `Modules/Payment/resources/views/create.blade.php` (route `payments/create?direction=pay&party_type=supplier`). "Payment Details" section: Total Amount, Date, Payment Splits (Select Account / Amount / Ref-TXN).
- **Plan (111):** Re-lay the Payment Details fields to match the standard form grid used on other create pages — consistent column widths/order, the split row aligned to the grid rather than free-floating.
- **Plan (113):** Swap the two indicated fields. **Confirm which two** — the screenshot arrows point at **Total Amount** and the split-row **Amount**; my assumption is they want **Date** and **Total Amount** order corrected (or Amount/Account order in the split row). Will confirm before implementing this one.

---

## Group E — Cross-cutting design sweeps ("deep research" issues)

### Bug_108 — Standardize all table filter bars — **L, medium risk**
- **Findings:** Shared component `Modules/Core/resources/views/components/table/filter-bar.blade.php`; CSS `.bp-filter-bar` etc. in `public/css/style.css` (~L2123). **54 views use the component; only 3 are hand-rolled** (`Attendance/report.blade.php`, `Setting/logs/show.blade.php`, plus the component itself). The reference complaint (Purchase Orders) is the **CSS**: search fixed at 610px + `min-width:200px` selects + date inputs overflow, so a 2nd-row wrap and cramped look on laptops.
- **Plan:**
  1. Fix the component's CSS to be responsive: fluid/`max-width` search instead of fixed 610px, smaller `min-width` selects, consistent date-input widths, wrap gracefully, use `--bp-danger` var for the reset button (currently hardcoded `#C0392B`). Dark-mode overrides.
  2. Convert the 3 hand-rolled bars to the shared component.
  3. Spot-check the heavy bars (Purchase, Sale, Expense, Manufacturing production-orders, Product) at 1200/992/768px.

### Bug_109 — Remove "card-for-2-buttons" pattern across pages — **L, low/medium risk**
- **Findings:** ~25 real occurrences. High-value targets: show-page **"Actions"** cards (`Purchase`, `Manufacturing/rm-purchases`, `PurchaseReturn`, `SaleReturn`, `Accounting/debit-notes`, `journal-entries`), **"Danger Zone"** single-button cards (`Asset`, `Quotation`, `Payment`), and create/edit **footer Save/Cancel** cards (`Purchase/create` + `receive`, `Branch/create`, `Loan/create`, ~11 Ecommerce form pages, `Product/create`+`edit`). Keep genuine content cards (e.g. `Expense/show` approval, `Payroll/show`).
- **Plan:**
  1. Define one standard "action row" pattern (a right-aligned `d-flex gap-2` button group, no card) and, if useful, a small Blade partial/component for it.
  2. Replace the button-only cards with that pattern, module by module (start with Purchase to match the reported screenshots).
  3. Leave hybrid/content cards alone. Verify each touched page renders and buttons keep their conditions/forms.

---

## Suggested execution order
1. **Bug_99** (S) and **Bug_110** (S) — fast, isolated wins.
2. **Bug_118 + Bug_117** (pricing) — highest business impact; do together.
3. **Bug_115** (supplier + label) — small, related to purchasing data.
4. **Bug_107, Bug_112** (S) — quick page fixes.
5. **Bug_111 + Bug_113** — after confirming the field swap.
6. **Bug_108** (filters) then **Bug_109** (button-cards) — the big sweeps, last.

## Open question to confirm before coding
- **Bug_113:** exactly which two fields to swap on the supplier payment page.
